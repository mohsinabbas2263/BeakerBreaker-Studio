<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** FR-01.3: email-based reset, single-use token, expires 60 minutes after issue. */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private const NEW_PASSWORD = 'a-new-password-1';

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        config(['services.frontend.url' => 'https://portal.test']);
    }

    public function test_a_known_email_gets_a_link_to_the_web_portal(): void
    {
        $user = User::factory()->create(['email' => 'sana@school.test']);

        $this->postJson('/api/forgot-password', ['email' => 'sana@school.test'])->assertOk();

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $n) use ($user) {
            $url = $n->toMail($user)->actionUrl;

            return str_starts_with($url, 'https://portal.test/reset-password?token=')
                && str_contains($url, 'email=sana%40school.test');
        });
    }

    public function test_an_unknown_email_gets_the_same_reply_and_nothing_is_sent(): void
    {
        $known = User::factory()->create();

        $a = $this->postJson('/api/forgot-password', ['email' => $known->email]);
        $b = $this->postJson('/api/forgot-password', ['email' => 'nobody@school.test']);

        $a->assertOk();
        $b->assertOk();
        $this->assertSame($a->json('message'), $b->json('message'));
        Notification::assertSentTimes(ResetPassword::class, 1);
    }

    public function test_the_link_sets_a_new_password_once_and_signs_out_everywhere(): void
    {
        $user = User::factory()->create();
        $user->createToken('web');
        $this->assertSame(1, $user->tokens()->count());
        $token = $this->requestToken($user);
        $body = [
            'token' => $token, 'email' => $user->email,
            'password' => self::NEW_PASSWORD, 'password_confirmation' => self::NEW_PASSWORD,
        ];

        $this->postJson('/api/reset-password', $body)->assertOk();

        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $user->fresh()->password));
        $this->assertSame(0, $user->tokens()->count());
        $this->assertDatabaseHas('audit_logs', ['action_type' => 'password.reset']);

        // Single use.
        $this->postJson('/api/reset-password', $body)->assertStatus(422);
    }

    public function test_the_link_expires_after_60_minutes(): void
    {
        $user = User::factory()->create();
        $token = $this->requestToken($user);

        $this->travel(61)->minutes();

        $this->postJson('/api/reset-password', [
            'token' => $token, 'email' => $user->email,
            'password' => self::NEW_PASSWORD, 'password_confirmation' => self::NEW_PASSWORD,
        ])->assertStatus(422);

        $this->assertFalse(Hash::check(self::NEW_PASSWORD, $user->fresh()->password));
    }

    public function test_the_new_password_is_validated(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/reset-password', [
            'token' => $this->requestToken($user), 'email' => $user->email,
            'password' => 'short', 'password_confirmation' => 'different',
        ])->assertStatus(422)->assertJsonValidationErrors('password');
    }

    private function requestToken(User $user): string
    {
        $this->postJson('/api/forgot-password', ['email' => $user->email])->assertOk();

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $n) use (&$token) {
            $token = $n->token;

            return true;
        });

        return $token;
    }
}
