<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
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
    }
}
