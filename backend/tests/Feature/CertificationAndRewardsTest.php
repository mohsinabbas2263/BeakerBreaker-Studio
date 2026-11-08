<?php

namespace Tests\Feature;

use App\Models\Experiment;
use App\Models\ExperimentSession;
use App\Models\ExperimentStep;
use App\Models\Reward;
use App\Models\SafetyAttempt;
use App\Models\SafetyTierQuestion;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\RewardService;
use App\Services\SafetyCertificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\Concerns\AnswersAttempts;
use Tests\TestCase;

/**
 * NFR-10: full coverage on safety certification, score computation and reward award.
 */
class CertificationAndRewardsTest extends TestCase
{
    use AnswersAttempts, RefreshDatabase;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->student = User::factory()->create();
        StudentProfile::create(['user_id' => $this->student->id]);
        Sanctum::actingAs($this->student);
    }

    private function passTier(int $tier, int $score = 90): void
    {
        SafetyAttempt::create([
            'user_id' => $this->student->id, 'tier_number' => $tier, 'score' => $score,
            'passed' => $score >= 80, 'question_ids' => [], 'submitted_answers' => [], 'attempted_at' => now(),
        ]);
    }

    // ---- safety tiers -------------------------------------------------------

    public function test_the_tier_list_opens_one_tier_at_a_time(): void
    {
        // FR-02.1, FR-02.2
        $this->getJson('/api/safety/tiers')->assertOk()
            ->assertJsonCount(4, 'tiers')
            ->assertJsonPath('tiers.0.available', true)
            ->assertJsonPath('tiers.1.available', false)
            ->assertJsonPath('certificate', null)
            ->assertJsonPath('highest_passed', 0);

        $this->passTier(1, 85);

        $this->getJson('/api/safety/tiers')->assertOk()
            ->assertJsonPath('tiers.0.passed', true)
            ->assertJsonPath('tiers.0.best_score', 85)
            ->assertJsonPath('tiers.1.available', true)
            ->assertJsonPath('tiers.2.available', false)
            ->assertJsonPath('highest_passed', 1);
    }

    public function test_a_locked_tier_cannot_be_started(): void
    {
        SafetyTierQuestion::factory()->tier(2)->count(10)->create();

        $this->postJson('/api/safety/tiers/2/start')->assertForbidden()->assertJsonPath('code', 'tier_locked');
    }

    public function test_an_out_of_range_tier_is_never_available(): void
    {
        $service = app(SafetyCertificationService::class);

        $this->assertFalse($service->tierIsAvailable($this->student, 0));
        $this->assertFalse($service->tierIsAvailable($this->student, 5));
    }

    public function test_a_tier_with_an_empty_bank_answers_404(): void
    {
        $this->postJson('/api/safety/tiers/1/start')->assertNotFound();
    }

    public function test_grading_refuses_a_tier_that_is_not_open(): void
    {
        $this->expectException(RuntimeException::class);

        app(SafetyCertificationService::class)->grade($this->student, 3, [1], [1 => 0]);
    }

    public function test_grading_refuses_questions_from_another_tier(): void
    {
        $other = SafetyTierQuestion::factory()->tier(2)->create();

        $this->expectException(RuntimeException::class);

        app(SafetyCertificationService::class)->grade($this->student, 1, [$other->id], [$other->id => 0]);
    }

    public function test_a_submission_for_a_tier_closed_since_start_is_refused(): void
    {
        // The attempt was drawn while tier 1 was open; the grade check still runs.
        SafetyTierQuestion::factory()->count(10)->create();
        $start = $this->postJson('/api/safety/tiers/1/start')->json();

        $service = $this->mock(SafetyCertificationService::class);
        $service->shouldReceive('grade')->andThrow(new RuntimeException('Tier 1 is not available to this student (FR-02.2).'));

        $this->postJson('/api/safety/tiers/1/submit', [
            'answers' => [], '_safety_session' => $start['_safety_session'],
        ])->assertForbidden();
    }

    public function test_a_below_80_tier_score_blocks_the_certificate(): void
    {
        // FR-02.8
        $this->passTier(1); $this->passTier(2); $this->passTier(3);
        $this->passTier(4, 70);

        $this->assertNull(app(SafetyCertificationService::class)->issueCertificateIfEarned($this->student));
        $this->assertDatabaseCount('certificates', 0);
    }

    public function test_a_certificate_is_issued_once_and_never_replaced(): void
    {
        // FR-02.7, FR-02.8
        foreach ([1, 2, 3, 4] as $tier) {
            $this->passTier($tier, 80 + $tier);
        }
        $service = app(SafetyCertificationService::class);

        $first = $service->issueCertificateIfEarned($this->student);
        $this->passTier(1, 100);
        $second = $service->issueCertificateIfEarned($this->student->fresh());

        $this->assertNotNull($first);
        $this->assertTrue($first->is($second));
        $this->assertSame(81, $second->tier1_score);
        $this->assertMatchesRegularExpression('/^BBS-\d{4}-[A-Z0-9]{6}$/', $first->certificate_reference);
        $this->assertDatabaseCount('certificates', 1);
    }

    public function test_the_student_reads_their_certificate(): void
    {
        $this->getJson('/api/safety/certificate')->assertNotFound();

        foreach ([1, 2, 3, 4] as $tier) {
            $this->passTier($tier);
        }
        app(SafetyCertificationService::class)->issueCertificateIfEarned($this->student);
        Sanctum::actingAs($this->student->fresh());   // a new request loads the user afresh

        $this->getJson('/api/safety/certificate')->assertOk()->assertJsonPath('certificate.tier4_score', 90);
    }

    public function test_passing_a_tier_twice_awards_its_points_once(): void
    {
        // FR-06.1, FR-04.3 style once-only award for tiers.
        SafetyTierQuestion::factory()->count(10)->create();

        $this->answerSafetyTier(1)->assertOk()->assertJsonPath('points_awarded', 100);
        $this->answerSafetyTier(1)->assertOk()->assertJsonPath('points_awarded', 0);

        $this->assertSame(100, $this->student->profile->fresh()->total_points);
    }

    // ---- quizzes -------------------------------------------------------------

    public function test_a_quiz_with_no_questions_cannot_be_scored(): void
    {
        $experiment = Experiment::factory()->create();
        ExperimentStep::create([
            'experiment_id' => $experiment->id, 'step_order' => 1, 'title' => 'Only',
            'instruction_text' => 'Do it.', 'preauthored_guidance' => 'Like this.',
        ]);
        ExperimentSession::create([
            'user_id' => $this->student->id, 'experiment_id' => $experiment->id, 'presentation_mode' => 'standard',
            'started_at' => now(), 'completed_at' => now(), 'steps_completed' => 1, 'completed_step_ids' => [1],
        ]);
        $quiz = $this->getJson("/api/experiments/{$experiment->id}/quiz")->assertOk()->json();

        $this->postJson("/api/experiments/{$experiment->id}/quiz", [
            'answers' => [], '_quiz_session' => $quiz['_quiz_session'],
        ])->assertUnprocessable();
    }

    // ---- rewards -------------------------------------------------------------

    public function test_points_never_go_down(): void
    {
        // FR-06.4
        $rewards = app(RewardService::class);
        $rewards->awardPoints($this->student, 50, 'experiment', 1);
        $rewards->awardPoints($this->student, -30, 'experiment', 2);

        $this->assertSame(50, $this->student->profile->fresh()->total_points);
    }

    public function test_a_badge_is_awarded_once(): void
    {
        // FR-06.3
        $rewards = app(RewardService::class);

        $this->assertTrue($rewards->awardBadge($this->student, 'first_experiment'));
        $this->assertFalse($rewards->awardBadge($this->student, 'first_experiment'));
        $this->assertSame(1, Reward::where('badge_code', 'first_experiment')->count());
    }

    public function test_activity_on_consecutive_days_builds_a_streak_and_its_badge(): void
    {
        // FR-06.5
        $profile = $this->student->profile;
        $profile->update(['current_streak_days' => 6, 'last_active_on' => now()->subDay()->toDateString()]);
        $rewards = app(RewardService::class);

        $rewards->recordActivity($this->student);
        $rewards->recordActivity($this->student);   // same day again: no change

        $this->assertSame(7, $profile->fresh()->current_streak_days);
        $this->assertTrue(Reward::where('badge_code', 'streak_7_days')->exists());

        $this->travel(3)->days();
        $rewards->recordActivity($this->student);
        $this->assertSame(1, $profile->fresh()->current_streak_days);
    }
}
