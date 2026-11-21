<?php

namespace App\Filament\Pages\Auth;

use App\Http\Controllers\Api\AuthController;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * FR-01.4 on the administrator panel too: after 5 failed attempts within 15
 * minutes, further sign-ins from that address are refused. The counter is the
 * same one the API login uses, so failures on the portal and the panel add up.
 * Filament's own limit (5 a minute) stays in place underneath.
 */
class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        $key = 'login:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, AuthController::MAX_ATTEMPTS)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);

            Notification::make()
                ->title('Too many failed attempts')
                ->body("Sign-in from this address is paused. Try again in {$minutes} minute(s).")
                ->danger()
                ->send();

            return null;
        }

        try {
            $response = parent::authenticate();
        } catch (ValidationException $e) {
            RateLimiter::hit($key, AuthController::DECAY_SECONDS);

            throw $e;
        }

        if ($response !== null) {
            RateLimiter::clear($key);
        }

        return $response;
    }
}
