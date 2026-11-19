<?php

namespace Tests\Feature;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * SR-03: the request limits around the API, on top of the login lockout (FR-01.4).
 */
class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_is_limited_per_address(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/forgot-password', ['email' => "nobody{$i}@example.test"])->assertOk();
        }

        $this->postJson('/api/forgot-password', ['email' => 'nobody@example.test'])->assertStatus(429);
    }

    public function test_reset_password_is_limited_per_address(): void
    {
        $body = ['token' => 'guess', 'email' => 'x@example.test', 'password' => 'abcdefgh', 'password_confirmation' => 'abcdefgh'];

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/reset-password', $body)->assertStatus(422);
        }

        $this->postJson('/api/reset-password', $body)->assertStatus(429);
    }

    public function test_signed_in_requests_carry_a_per_user_limit(): void
    {
        $user = User::factory()->state(['role' => 'student'])->create();
        StudentProfile::create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $this->getJson('/api/me')->assertOk()->assertHeader('X-RateLimit-Limit', 300);
    }

    public function test_an_unknown_address_gets_the_same_answer_as_a_wrong_password(): void
    {
        $user = User::factory()->state(['role' => 'student', 'password' => Hash::make('right-one-1')])->create();

        $wrong = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong-one-1']);
        $unknown = $this->postJson('/api/login', ['email' => 'ghost@example.test', 'password' => 'wrong-one-1']);

        $wrong->assertStatus(422);
        $unknown->assertStatus(422);
        $this->assertSame($wrong->json('errors'), $unknown->json('errors'));
    }
}
