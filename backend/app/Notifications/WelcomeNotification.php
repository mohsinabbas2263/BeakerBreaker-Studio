<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Password;

/**
 * UC-18: every new account is sent a welcome message. It carries a link to
 * choose a password, made by the same single-use, 60-minute token as a reset
 * (FR-01.3), so the administrator who made the account never knows the password.
 *
 * Queued: making the token hashes it at the full work factor (SR-01), which for a
 * 500-row import (FR-10.2) would otherwise hold the administrator's request open.
 * The token is made when the mail is sent, so the 60 minutes start then.
 */
class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public static function linkFor(User $user): string
    {
        $token = Password::broker()->createToken($user);

        // The same address the reset email uses: the web portal's reset page.
        return (new ResetPassword($token))->toMail($user)->actionUrl;
    }

    public function toMail(object $notifiable): MailMessage
    {
        /** @var User $notifiable */
        $school = $notifiable->school?->name;

        return (new MailMessage)
            ->subject('Your Beaker Breakers Studio account')
            ->greeting("Assalam o Alaikum, {$notifiable->display_name}!")
            ->line($school
                ? "Your administrator at {$school} has made you a Beaker Breakers Studio account."
                : 'An administrator has made you a Beaker Breakers Studio account.')
            ->line("You sign in with this email address: {$notifiable->email}")
            ->action('Choose your password', self::linkFor($notifiable))
            ->line('This link works once and for 60 minutes. If it has expired, open the sign-in page and choose "Forgot password" to get a new one.')
            ->line('If you were not expecting this, you can ignore this email.');
    }
}
