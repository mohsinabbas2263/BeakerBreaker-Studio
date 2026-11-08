<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * NFR-10: authentication needs full coverage. FR-01.1, FR-01.2, FR-01.4, SR-03, SR-04, SR-05.
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-9';

    private function account(string $role = 'student', array $attributes = []): User
    {
        $user = User::factory()->state(['role' => $role, 'password' => Hash::make(self::PASSWORD)] + $attributes)->create();

        if ($role === 'student') {
            StudentProfile::create(['user_id' => $user->id]);
        }

        return $user;
    }

    private function login(User $user, string $password = self::PASSWORD)
    {
        return $this->postJson('/api/login', ['email' => $user->email, 'password' => $password]);
    }

    public static function roles(): array
    {
        return [
            'student' => ['student', '/student'],
            'teacher' => ['teacher', '/teacher'],
            'administrator' => ['administrator', '/admin'],
        ];
    }

    #[DataProvider('roles')]
    public function test_each_role_signs_in_and_is_sent_home(string $role, string $home): void
    {
        $user = $this->account($role);

        $response = $this->login($user)->assertOk()
            ->assertJsonPath('home', $home)
            ->assertJsonPath('user.role', $role)
            ->assertJsonMissingPath('user.password');

        $this->assertNotEmpty($response->json('token'));
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_the_token_is_valid_for_24_hours_from_issue(): void
    {
        // FR-01.2
        $this->freezeSecond();
        $user = $this->account();

        $this->login($user)->assertOk();

        $this->assertTrue($user->tokens()->first()->expires_at->equalTo(now()->addDay()));
    }

    public function test_an_expired_token_is_refused(): void
    {
        $user = $this->account();
        $token = $this->login($user)->json('token');

        $this->travel(24)->hours();
        $this->travel(1)->minutes();
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
    }

    public function test_me_returns_the_signed_in_user(): void
    {
        $user = $this->account('student', ['display_name' => 'Ayesha']);
        $token = $this->login($user)->json('token');

        $this->withToken($token)->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('user.display_name', 'Ayesha')
            ->assertJsonPath('user.total_points', 0)
            ->assertJsonPath('user.safety_tier_passed', 0);
    }

    public function test_logout_revokes_the_token(): void
    {
        $user = $this->account();
        $token = $this->login($user)->json('token');

        $this->withToken($token)->postJson('/api/logout')->assertOk();
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_a_fresh_login_ends_the_previous_session(): void
    {
        // SR-05, as the team decided: one active session per account.
        $user = $this->account();
        $first = $this->login($user)->json('token');
        $this->login($user)->assertOk();
        $this->app['auth']->forgetGuards();

        $this->withToken($first)->getJson('/api/me')->assertUnauthorized();
        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_wrong_password_and_unknown_email_give_the_same_answer(): void
    {
        $user = $this->account();

        $wrong = $this->login($user, 'not-the-password');
        $unknown = $this->postJson('/api/login', ['email' => 'nobody@school.test', 'password' => 'whatever-1']);

        $wrong->assertUnprocessable();
        $unknown->assertUnprocessable();
        $this->assertSame($wrong->json('errors'), $unknown->json('errors'));
    }

    public function test_a_suspended_account_cannot_sign_in(): void
    {
        $user = $this->account('student', ['status' => 'suspended']);

        $this->login($user)->assertForbidden();
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_the_sixth_attempt_inside_15_minutes_is_refused(): void
    {
        // FR-01.4, keyed by address as the team decided.
        $user = $this->account();

        foreach (range(1, 5) as $i) {
            $this->login($user, "wrong-{$i}")->assertUnprocessable();
        }

        $this->login($user)->assertStatus(429)->assertJsonStructure(['message', 'retry_after']);

        $this->travel(15)->minutes();
        $this->travel(1)->seconds();

        $this->login($user)->assertOk();
    }

    public function test_a_successful_login_clears_the_failure_count(): void
    {
        $user = $this->account();

        foreach (range(1, 4) as $i) {
            $this->login($user, "wrong-{$i}");
        }
        $this->login($user)->assertOk();

        foreach (range(1, 4) as $i) {
            $this->login($user, "wrong-{$i}");
        }
        $this->login($user)->assertOk();
    }

    public function test_input_is_validated_on_the_server(): void
    {
        // SR-07
        $this->postJson('/api/login', ['email' => 'not-an-email'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_protected_routes_need_a_token(): void
    {
        // SR-03
        $this->getJson('/api/me')->assertUnauthorized();
        $this->getJson('/api/safety/tiers')->assertUnauthorized();
        $this->postJson('/api/logout')->assertUnauthorized();
    }

    public function test_a_token_of_an_account_suspended_since_is_refused(): void
    {
        // EnsureRole re-checks the account on every role-guarded request.
        $user = $this->account();
        $token = $this->login($user)->json('token');

        $user->forceFill(['status' => 'suspended'])->saveQuietly();
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/safety/tiers')->assertUnauthorized();
    }

    public function test_a_role_cannot_use_another_roles_routes(): void
    {
        // SR-04
        $teacher = $this->account('teacher');
        $student = $this->account('student');

        $this->withToken($this->login($teacher)->json('token'))->getJson('/api/safety/tiers')->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->withToken($this->login($student)->json('token'))
            ->postJson('/api/teacher/broadcast', ['class_id' => 1, 'type' => 'announcement', 'body' => 'x'])
            ->assertForbidden();
    }

    public function test_role_helpers(): void
    {
        $this->assertTrue($this->account('student')->isStudent());
        $this->assertTrue($this->account('teacher')->isTeacher());
        $admin = $this->account('administrator');
        $this->assertTrue($admin->isAdministrator());
        $this->assertFalse($admin->isStudent());
    }

    public function test_relations_used_by_teacher_and_quiz_features_resolve(): void
    {
        $teacher = $this->account('teacher');
        $class = SchoolClass::factory()->create();
        $class->teachers()->attach($teacher->id, ['assigned_at' => now()]);

        $this->assertTrue($teacher->taughtClasses()->first()->is($class));
        $this->assertCount(0, $teacher->quizAttempts);
        $this->assertCount(0, $teacher->taskAssignments);
    }
}
