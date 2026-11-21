<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Filament\Resources\UserResource\Pages\ManageUsers;
use App\Models\Experiment;
use App\Models\ExperimentSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * FR-01.4 on the panel, SR-05 on every path that changes a password or suspends an
 * account, an object check on the quiz (FR-04.4), and the paid-call limit on readiness.
 */
class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('login:127.0.0.1');
    }

    private function panelLogin(string $email, string $password)
    {
        return Livewire::test(Login::class)
            ->set('data.email', $email)
            ->set('data.password', $password)
            ->call('authenticate');
    }

    /** FR-01.4: five failures in 15 minutes, and the sixth try is refused even with the right password. */
    public function test_the_panel_locks_an_address_after_five_failures(): void
    {
        $admin = User::factory()->administrator()->create(['password' => Hash::make('right-password')]);

        for ($i = 0; $i < 5; $i++) {
            $this->panelLogin($admin->email, 'wrong')->assertHasErrors('data.email');
            // Filament's own one-minute limit would stop the sixth anyway; step past it.
            $this->travel(61)->seconds();
        }

        $this->panelLogin($admin->email, 'right-password');
        $this->assertGuest();

        $this->travel(15)->minutes();
        $this->panelLogin($admin->email, 'right-password');
        $this->assertAuthenticatedAs($admin);
    }

    /** The panel and the API share one counter, so failures cannot be split between them. */
    public function test_failures_on_the_api_count_against_the_panel(): void
    {
        $admin = User::factory()->administrator()->create(['password' => Hash::make('right-password')]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', ['email' => $admin->email, 'password' => 'wrong'])->assertStatus(422);
        }

        $this->panelLogin($admin->email, 'right-password');
        $this->assertGuest();
    }

    /** SR-05: an administrator who resets a password in the panel ends the user's sessions. */
    public function test_changing_a_password_in_the_panel_ends_the_users_tokens(): void
    {
        $student = User::factory()->create();
        $student->createToken('phone');
        $this->actingAs(User::factory()->administrator()->create());

        Livewire::test(ManageUsers::class)
            ->callTableAction('edit', $student, data: ['password' => 'a-new-password'])
            ->assertHasNoTableActionErrors();

        $this->assertSame(0, $student->tokens()->count());
    }

    public function test_suspending_or_deleting_an_account_ends_its_tokens(): void
    {
        $a = User::factory()->create();
        $a->createToken('phone');
        $a->update(['status' => 'suspended']);
        $this->assertSame(0, $a->tokens()->count());

        $b = User::factory()->create();
        $b->createToken('phone');
        $b->delete();
        $this->assertSame(0, $b->tokens()->count());
    }

    public function test_an_ordinary_update_keeps_the_tokens(): void
    {
        $user = User::factory()->create();
        $user->createToken('phone');
        $user->update(['display_name' => 'New Name']);

        $this->assertSame(1, $user->tokens()->count());
    }

    /** FR-04.4: a quiz attempt cannot be filed against another student's run. */
    public function test_a_quiz_attempt_cannot_name_another_students_session(): void
    {
        $experiment = Experiment::factory()->create();
        $other = ExperimentSession::create([
            'user_id' => User::factory()->create()->id, 'experiment_id' => $experiment->id,
            'presentation_mode' => 'standard', 'started_at' => now(), 'completed_at' => now(),
        ]);

        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/experiments/{$experiment->id}/quiz", [
            'answers' => [], 'session_id' => $other->id, '_quiz_session' => 'x',
        ])->assertStatus(422)->assertJsonValidationErrors('session_id');
    }

    /** Each readiness report can call Gemini, which is paid per request. */
    public function test_readiness_generation_has_the_paid_call_limit(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        Sanctum::actingAs($teacher);

        for ($i = 0; $i < 30; $i++) {
            $this->postJson("/api/teacher/students/{$student->id}/readiness")->assertStatus(403);   // not their class
        }

        $this->postJson("/api/teacher/students/{$student->id}/readiness")->assertStatus(429);
    }
}
