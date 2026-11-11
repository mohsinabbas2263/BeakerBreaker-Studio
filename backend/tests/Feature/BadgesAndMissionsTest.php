<?php

namespace Tests\Feature;

use App\Models\DailyMission;
use App\Models\Experiment;
use App\Models\ExperimentSession;
use App\Models\ExperimentStep;
use App\Models\QuizAttempt;
use App\Models\Reward;
use App\Models\SafetyAttempt;
use App\Models\StepAction;
use App\Models\User;
use App\Services\AchievementService;
use App\Services\MissionService;
use App\Support\Badges;
use Database\Seeders\ExperimentSeeder;
use Database\Seeders\GameExperimentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\MakesLicensedStudents;
use Tests\TestCase;

/**
 * FR-06.3 (at least 60 distinct badges, each once), FR-07.3 (three daily missions,
 * replaced at midnight Pakistan time), and FR-06.2 (nothing rewards speed).
 */
class BadgesAndMissionsTest extends TestCase
{
    use MakesLicensedStudents, RefreshDatabase;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->student = $this->enrolledStudent();
        Sanctum::actingAs($this->student);
    }

    private function passTier(int $tier = 1): void
    {
        SafetyAttempt::create([
            'user_id' => $this->student->id, 'tier_number' => $tier, 'score' => 90, 'passed' => true,
            'question_ids' => [], 'submitted_answers' => [], 'attempted_at' => now(),
        ]);
    }

    public function test_the_catalogue_has_at_least_sixty_distinct_badges(): void
    {
        $this->seed([ExperimentSeeder::class, GameExperimentSeeder::class]);

        $catalogue = Badges::catalogue();

        $this->assertGreaterThanOrEqual(60, count($catalogue));
        $this->assertSame(count($catalogue), count(array_unique($catalogue)));

        // Every badge has a name a student can read.
        foreach (Badges::describe($catalogue) as $badge) {
            $this->assertNotSame('', $badge['name'], $badge['code']);
        }
    }

    public function test_no_achievement_is_about_speed(): void
    {
        foreach (Badges::ACHIEVEMENTS as $code => [$name, , $stat, , $how]) {
            $this->assertDoesNotMatchRegularExpression('/fast|quick|speed|second|minute|timed/i', "{$code} {$name} {$stat} {$how}");
        }
        foreach (MissionService::TEMPLATES as $code => [$title]) {
            $this->assertDoesNotMatchRegularExpression('/fast|quick|speed|second|minute|timed/i', "{$code} {$title}");
        }
    }

    public function test_a_student_gets_three_missions_and_the_same_three_all_day(): void
    {
        $first = $this->getJson('/api/missions')->assertOk()->json();
        $this->assertCount(3, $first['missions']);
        $this->assertSame($first['missions'], $this->getJson('/api/missions')->json('missions'));
        $this->assertSame(3, DailyMission::count());
    }

    public function test_before_tier_one_the_missions_are_about_safety(): void
    {
        $codes = collect($this->getJson('/api/missions')->json('missions'))->pluck('code');

        $this->assertEqualsCanonicalizing(['safety_quiz', 'safety_two', 'safety_pass'], $codes->all());
    }

    public function test_missions_are_replaced_at_midnight_pakistan_time(): void
    {
        $this->passTier();

        // 23:30 in Karachi is 18:30 UTC: still the same mission day.
        Carbon::setTestNow(Carbon::parse('2026-11-10 18:30:00', 'UTC'));
        $before = $this->getJson('/api/missions')->json();
        $this->assertSame('2026-11-10', $before['date']);

        // 00:10 in Karachi is 19:10 UTC: a new day and a new set.
        Carbon::setTestNow(Carbon::parse('2026-11-10 19:10:00', 'UTC'));
        $after = $this->getJson('/api/missions')->json();
        $this->assertSame('2026-11-11', $after['date']);
        $this->assertSame('2026-11-10T19:00:00+00:00', $before['resets_at']);
        $this->assertSame(6, DailyMission::where('user_id', $this->student->id)->count());

        Carbon::setTestNow();
    }

    public function test_a_mission_completes_from_the_students_own_records_and_earns_badges(): void
    {
        DailyMission::create(['user_id' => $this->student->id, 'mission_date' => app(MissionService::class)->today()[0],
            'code' => 'safety_quiz', 'target' => 1]);

        $this->getJson('/api/missions')->assertJsonPath('missions.0.completed', false);

        $this->passTier();
        $missions = $this->getJson('/api/missions')->json('missions');
        $this->assertTrue($missions[0]['completed']);
        $this->assertSame(1, $missions[0]['progress']);

        app(AchievementService::class)->refresh($this->student);
        $this->assertTrue(Reward::where('user_id', $this->student->id)->where('badge_code', 'missions_1')->exists());
    }

    public function test_achievements_count_safe_and_careful_practice_and_are_given_once(): void
    {
        $this->passTier();
        $experiment = Experiment::factory()->create(['grade_level' => 9, 'board' => 'both']);
        $step = ExperimentStep::create([
            'experiment_id' => $experiment->id, 'step_order' => 1, 'kind' => 'choose',
            'title' => 'x', 'instruction_text' => 'x', 'preauthored_guidance' => 'x',
        ]);
        $session = ExperimentSession::create([
            'user_id' => $this->student->id, 'experiment_id' => $experiment->id, 'presentation_mode' => 'ar',
            'started_at' => now(), 'completed_at' => now(), 'steps_completed' => 1, 'completed_step_ids' => [$step->id],
        ]);
        $move = fn (string $outcome, string $category = 'procedure', bool $hinted = false) => StepAction::create([
            'session_id' => $session->id, 'step_id' => $step->id, 'user_id' => $this->student->id, 'kind' => 'choose',
            'action' => [], 'outcome' => $outcome, 'category' => $category, 'hinted' => $hinted,
        ]);
        $move('mistake', 'reagent');
        $move('correct', 'procedure', true);
        QuizAttempt::create(['user_id' => $this->student->id, 'experiment_id' => $experiment->id, 'score' => 60,
            'passed' => false, 'submitted_answers' => [], 'submitted_at' => now()->subMinute()]);
        QuizAttempt::create(['user_id' => $this->student->id, 'experiment_id' => $experiment->id, 'score' => 100,
            'passed' => true, 'submitted_answers' => [], 'submitted_at' => now()]);

        $awarded = app(AchievementService::class)->refresh($this->student);

        foreach (['experiments_1', 'perfect_quiz_1', 'quiz_comeback', 'clean_run_1', 'recovery_1', 'curious_1', 'ar_1'] as $code) {
            $this->assertContains($code, $awarded);
        }
        $this->assertNotContains('safety_champion', $awarded);   // only one tier passed

        // FR-06.3: never twice.
        $this->assertSame([], app(AchievementService::class)->refresh($this->student));
    }

    public function test_a_safety_mistake_spoils_the_clean_run_and_a_revealed_answer_is_no_recovery(): void
    {
        $this->passTier();
        $experiment = Experiment::factory()->create();
        $step = ExperimentStep::create([
            'experiment_id' => $experiment->id, 'step_order' => 1, 'kind' => 'gear',
            'title' => 'x', 'instruction_text' => 'x', 'preauthored_guidance' => 'x',
        ]);
        $session = ExperimentSession::create([
            'user_id' => $this->student->id, 'experiment_id' => $experiment->id, 'presentation_mode' => 'standard',
            'started_at' => now(), 'completed_at' => now(), 'steps_completed' => 1, 'completed_step_ids' => [$step->id],
        ]);
        foreach (['mistake', 'mistake', 'mistake', 'revealed'] as $outcome) {
            StepAction::create(['session_id' => $session->id, 'step_id' => $step->id, 'user_id' => $this->student->id,
                'kind' => 'gear', 'action' => [], 'outcome' => $outcome, 'category' => 'safety']);
        }

        $stats = app(AchievementService::class)->stats($this->student);

        $this->assertSame(0, $stats['clean_runs']);
        $this->assertSame(0, $stats['recoveries']);
    }

    public function test_the_profile_says_how_many_badges_there_are(): void
    {
        $this->seed([ExperimentSeeder::class, GameExperimentSeeder::class]);

        $this->assertSame(count(Badges::catalogue()), $this->getJson('/api/profile')->assertOk()->json('badges_total'));
    }

    public function test_a_teacher_has_no_missions(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'teacher']));

        $this->getJson('/api/missions')->assertForbidden();
    }
}
