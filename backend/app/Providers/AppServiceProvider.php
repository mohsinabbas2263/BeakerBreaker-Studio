<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // SR-01: salted hash from an adaptive function, work factor not below 12.
        Hash::setRounds(max(12, (int) config('hashing.bcrypt.rounds', 12)));

        // SR-02: every URL the app writes in production (password-reset links, the
        // admin panel's assets) is https, even though the proxy talks to it over http.
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        // FR-01.3: the reset link opens the web portal's reset page. Without this,
        // Laravel looks for a `password.reset` route that an API-only backend does
        // not have, and every reset request for a real account fails with a 500.
        ResetPassword::createUrlUsing(function (User $user, string $token): string {
            return rtrim((string) config('services.frontend.url'), '/')
                .'/reset-password?'.http_build_query([
                    'token' => $token,
                    'email' => $user->getEmailForPasswordReset(),
                ]);
        });

        // SR-03: request limits, so no single account or address can flood the API.
        // Login has its own lockout (FR-01.4, AuthController); these sit around it.
        //
        // api: every signed-in request, per user. Generous, because a game step sends
        // a move per drag or click.
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(300)
            ->by($request->user()?->id ?: $request->ip()));

        // guidance: each call can reach Gemini, which is paid per request.
        RateLimiter::for('guidance', fn (Request $request) => Limit::perMinute(30)
            ->by($request->user()?->id ?: $request->ip()));

        // password: forgot and reset, per address, so nobody can flood an inbox with
        // reset mail or hammer reset tokens. Ten a minute still covers a whole lab.
        RateLimiter::for('password', fn (Request $request) => Limit::perMinute(10)
            ->by($request->ip()));
    }
}
