<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Experiment;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\RewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function test_a_student_can_rename_themself_and_it_is_audited(): void
    {
        $old = $this->student->display_name;

        $this->patchJson('/api/profile', ['display_name' => '  Ayesha   Khan '])
            ->assertOk()->assertJsonPath('display_name', 'Ayesha Khan');

        $this->assertSame('Ayesha Khan', $this->student->fresh()->display_name);
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $this->student->id,
            'action_type' => 'profile.name_changed',
            'target_reference' => "user:{$this->student->id}",
        ]);
        $entry = AuditLog::where('action_type', 'profile.name_changed')->first();
        $this->assertSame(['from' => $old, 'to' => 'Ayesha Khan'], $entry->context);
    }

    public function test_an_urdu_name_is_accepted(): void
    {
        $this->patchJson('/api/profile', ['display_name' => 'عائشہ خان'])->assertOk();

        $this->assertSame('عائشہ خان', $this->student->fresh()->display_name);
    }

    public function test_saving_the_same_name_writes_no_audit_entry(): void
    {
        $this->patchJson('/api/profile', ['display_name' => $this->student->display_name])->assertOk();

        $this->assertSame(0, AuditLog::where('action_type', 'profile.name_changed')->count());
    }

    /** @return array<string, array{string}> */
    public static function badNames(): array
    {
        return [
            'too short' => ['A'],
            'only spaces' => ['    '],
            'a link' => ['visit evil.example/x'],
            'digits' => ['Ali 123'],
            'symbols' => ['<b>Ali</b>'],
            'too long' => [str_repeat('a', 41)],
        ];
    }

    #[DataProvider('badNames')]
    public function test_a_name_that_is_not_a_name_is_refused(string $name): void
    {
        $before = $this->student->display_name;

        $this->patchJson('/api/profile', ['display_name' => $name])
            ->assertStatus(422)->assertJsonValidationErrors('display_name');

        $this->assertSame($before, $this->student->fresh()->display_name);
    }

    public function test_an_unknown_avatar_is_refused(): void
    {
        $this->patchJson('/api/profile', ['avatar_key' => 'https://evil.example/x.png'])
            ->assertStatus(422)->assertJsonValidationErrors('avatar_key');

        $this->assertSame('avatar-boy1', $this->student->fresh()->avatar_key);
    }
}
