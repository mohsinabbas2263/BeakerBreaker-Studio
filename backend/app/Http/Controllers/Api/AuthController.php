<?php

namespace App\Http\Controllers\Api;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * FR-01.1 role based login, FR-01.2 24 hour tokens, FR-01.3 password reset,
 * FR-01.4 lockout after 5 failures in 15 minutes, FR-01.6 no self registration.
 *
 * There is deliberately no register method on this controller, and no route
 * that reaches one. An account exists only because an administrator made it.
 */
class AuthController extends Controller
{
    /** FR-01.4: five failures from an address inside fifteen minutes. */
    public const MAX_ATTEMPTS = 5;

    public const DECAY_SECONDS = 900;

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $key = 'login:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'message' => "Too many failed attempts. Try again in {$seconds} seconds.",
                'retry_after' => $seconds,
            ], 429);
        }

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($key, self::DECAY_SECONDS);

            // The same message for both cases: no probing for valid addresses.
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        if (! $user->isActive()) {
            return response()->json(['message' => 'This account is suspended.'], 403);
        }

        RateLimiter::clear($key);

        // SR-05: a fresh login invalidates what came before it.
        $user->tokens()->delete();

        $token = $user->createToken(
            $credentials['device_name'] ?? 'web',
            ['*'],
            now()->addHours((int) config('sanctum.expiration', 1440) / 60)
        );

        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        return response()->json([
            'token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at,
            'user' => $this->userPayload($user),
            // FR-01.1: the client follows this rather than deciding for itself.
            'home' => $user->role->homeRoute(),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->userPayload($request->user())]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Signed out.']);
    }

    /** FR-01.3: a single-use token that expires sixty minutes after issue. */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($request->only('email'));

        // Always the same answer, whether or not the address is known.
        return response()->json([
            'message' => 'If that address belongs to an account, a reset link is on its way.',
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) use ($request) {
                $user->forceFill([
                    'password' => $password,            // SR-01: hashed by the cast
                    'remember_token' => Str::random(60),
                ])->save();

                // SR-05: every token of a user whose password changed is invalid.
                $user->tokens()->delete();

                AuditLog::record($user->id, 'password.reset', "user:{$user->id}", [], $request->ip());
            }
        );

        return $status === Password::PasswordReset
            ? response()->json(['message' => 'Password changed. Sign in with the new one.'])
            : response()->json(['message' => 'That reset link is no longer valid.'], 422);
    }

    protected function userPayload(User $user): array
    {
        $user->loadMissing('profile.schoolClass', 'school');

        return [
            'id' => $user->id,
            'display_name' => $user->display_name,
            'email' => $user->email,
            'role' => $user->role->value,
            'avatar_key' => $user->avatar_key,
            'school' => $user->school?->name,
            'class' => $user->profile?->schoolClass?->title,
            'total_points' => $user->profile?->total_points ?? 0,
            'rank_level' => $user->profile?->rank_level ?? 1,
            'rank_name' => $user->profile?->rankName(),
            'streak_days' => $user->profile?->current_streak_days ?? 0,
            'safety_tier_passed' => $user->highestSafetyTierPassed(),
        ];
    }
}
