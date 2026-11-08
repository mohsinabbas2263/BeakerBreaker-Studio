<?php

namespace Tests\Feature;

use App\Models\Experiment;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\RewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** FR-06.3 badge names on the profile, and the avatar a student may pick. */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->student = User::factory()->create(['avatar_key' => 'avatar-boy1']);
        StudentProfile::create(['user_id' => $this->student->id]);
        Sanctum::actingAs($this->student);
    }

    public function test_badges_come_with_the_name_a_student_reads(): void
    {
        Experiment::factory()->create(['slug' => 'acid-base-neutral', 'title' => 'Neutralisation Titration']);
        $rewards = app(RewardService::class);
        $rewards->awardBadge($this->student, 'safety_tier_1');
        $rewards->awardBadge($this->student, 'experiment_acid-base-neutral');
        $rewards->awardBadge($this->student, 'experiment_gone-experiment');
        $rewards->awardBadge($this->student, 'streak_7_days');
        $rewards->awardBadge($this->student, 'mystery_award');

        $badges = collect($this->getJson('/api/profile')->assertOk()->json('badges'))->keyBy('code');

        $this->assertSame(
            ['code' => 'safety_tier_1', 'name' => 'Safety Tier 1', 'icon' => '🛡️'],
            $badges['safety_tier_1'],
        );
        $this->assertSame('Neutralisation Titration', $badges['experiment_acid-base-neutral']['name']);
        $this->assertSame('🔬', $badges['experiment_acid-base-neutral']['icon']);
        $this->assertSame('Gone experiment', $badges['experiment_gone-experiment']['name']);
        $this->assertSame('7-Day Streak', $badges['streak_7_days']['name']);
        $this->assertSame('Mystery award', $badges['mystery_award']['name']);
    }

    public function test_a_profile_with_no_badges_returns_an_empty_list(): void
    {
        $this->getJson('/api/profile')->assertOk()->assertJsonPath('badges', [])
            ->assertJsonPath('avatar_key', 'avatar-boy1');
    }

    public function test_a_student_can_pick_one_of_the_four_avatars(): void
    {
        $this->patchJson('/api/profile', ['avatar_key' => 'avatar-girl2'])->assertOk();

        $this->assertSame('avatar-girl2', $this->student->fresh()->avatar_key);
    }

    public function test_an_unknown_avatar_is_refused(): void
    {
        $this->patchJson('/api/profile', ['avatar_key' => 'https://evil.example/x.png'])
            ->assertStatus(422)->assertJsonValidationErrors('avatar_key');

        $this->assertSame('avatar-boy1', $this->student->fresh()->avatar_key);
    }
}
