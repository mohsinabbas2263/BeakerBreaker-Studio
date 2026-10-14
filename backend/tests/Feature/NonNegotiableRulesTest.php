<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Experiment;
use App\Models\ExperimentStep;
use App\Models\SafetyAttempt;
use App\Models\SafetyTierQuestion;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\GuidanceService;
use App\Services\RewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

/**
 * The five rules from CLAUDE.md that are not design preferences.
 * If one of these fails, the build is wrong, not the test.
 */
class NonNegotiableRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function student(): User
    {
        $user = User::factory()->create();
        StudentProfile::create(['user_id' => $user->id]);

        return $user;
    }

    protected function tierQuestions(int $tier = 1, int $count = 10)
    {
        return SafetyTierQuestion::factory()->tier($tier)->count($count)->create();
    }

    /** Rule 1. FR-02.5, SR-07: a score sent by the client changes nothing. */
    public function test_a_client_supplied_score_is_ignored(): void
    {
        $user = $this->student();
        $questions = $this->tierQuestions();
        Sanctum::actingAs($user);

        // Every answer wrong, and a perfect score claimed in the body.
        $answers = $questions->mapWithKeys(fn ($q) => [$q->id => 3])->all();

        $response = $this->postJson('/api/safety/tiers/1/submit', [
            'answers' => $answers,
            'score' => 100,
            'passed' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('score', 0)
            ->assertJsonPath('passed', false);

        $this->assertSame(0, SafetyAttempt::first()->score);
    }

    /** Rule 1 again, from the other side: correct answers are marked correct. */
    public function test_the_server_marks_correct_answers(): void
    {
        $user = $this->student();
        $questions = $this->tierQuestions();
        Sanctum::actingAs($user);

        $answers = $questions->mapWithKeys(fn ($q) => [$q->id => $q->correct_option])->all();

        $this->postJson('/api/safety/tiers/1/submit', ['answers' => $answers])
            ->assertOk()
            ->assertJsonPath('score', 100)
            ->assertJsonPath('passed', true);
    }

    /** Rule 2. NFR-06: no API key, and the experiment is still completable. */
    public function test_guidance_falls_back_to_preauthored_text_without_an_api_key(): void
    {
        config(['services.gemini.key' => null]);

        $experiment = Experiment::factory()->create();
        $step = ExperimentStep::create([
            'experiment_id' => $experiment->id,
            'step_order' => 1,
            'title' => 'Add the acid',
            'instruction_text' => 'Pour the acid into the water.',
            'preauthored_guidance' => 'Acid into water, never the other way about.',
        ]);

        $result = app(GuidanceService::class)->forStep($step);

        $this->assertSame('preauthored', $result['source']);
        $this->assertSame('Acid into water, never the other way about.', $result['text']);
        $this->assertNotEmpty($result['text']);
    }

    /** Rule 3. FR-01.6: no account exists without administrator action. */
    public function test_there_is_no_registration_endpoint(): void
    {
        foreach (['/api/register', '/api/signup', '/api/users'] as $route) {
            $response = $this->postJson($route, [
                'email' => 'intruder@example.test',
                'password' => 'password',
                'display_name' => 'Intruder',
            ]);

            $this->assertContains(
                $response->status(),
                [404, 405],
                "{$route} answered {$response->status()}; FR-01.6 requires no such route."
            );
        }

        $this->assertDatabaseMissing('users', ['email' => 'intruder@example.test']);
    }

    /** Rule 4. FR-06.4: points never fall, and FR-04.3: awarded once. */
    public function test_points_are_never_reduced_and_experiment_points_award_once(): void
    {
        $user = $this->student();
        $rewards = app(RewardService::class);
        $experiment = Experiment::factory()->create(['base_points' => 50]);

        $rewards->awardPoints($user, 100, 'safety_tier', 1);
        $this->assertSame(100, $user->profile->fresh()->total_points);

        // A negative award is not an award.
        $this->assertSame(0, $rewards->awardPoints($user, -40, 'manual'));
        $this->assertSame(100, $user->profile->fresh()->total_points);

        $first = $rewards->awardExperimentPointsOnce($user, $experiment->id, 50);
        $second = $rewards->awardExperimentPointsOnce($user, $experiment->id, 50);

        $this->assertSame(50, $first);
        $this->assertSame(0, $second, 'FR-04.3 allows the points once only.');
        $this->assertSame(150, $user->profile->fresh()->total_points);
    }

    /** Rule 4, rank: a rank level never drops. */
    public function test_rank_level_never_drops(): void
    {
        $user = $this->student();
        app(RewardService::class)->awardPoints($user, 700, 'safety_tier', 1);

        $profile = $user->profile->fresh();
        $earnedRank = $profile->rank_level;
        $this->assertGreaterThan(1, $earnedRank);

        app(RewardService::class)->awardPoints($user, 1, 'manual');
        $this->assertGreaterThanOrEqual($earnedRank, $user->profile->fresh()->rank_level);
    }

    /** Rule 4, badges: FR-06.3 awards a badge at most once. */
    public function test_a_badge_is_awarded_at_most_once(): void
    {
        $user = $this->student();
        $rewards = app(RewardService::class);

        $this->assertTrue($rewards->awardBadge($user, 'safety_tier_1'));
        $this->assertFalse($rewards->awardBadge($user, 'safety_tier_1'));
        $this->assertSame(1, $user->rewards()->where('badge_code', 'safety_tier_1')->count());
    }

    /** Rule 5. FR-02.8: an issued certificate cannot be edited or deleted. */
    public function test_a_certificate_cannot_be_updated_or_deleted(): void
    {
        $user = $this->student();

        $certificate = Certificate::create([
            'user_id' => $user->id,
            'certificate_reference' => 'BBS-2026-TEST01',
            'tier1_score' => 90,
            'tier2_score' => 85,
            'tier3_score' => 95,
            'tier4_score' => 88,
            'issued_on' => now()->toDateString(),
        ]);

        try {
            $certificate->update(['tier1_score' => 40]);
            $this->fail('A certificate was updated; FR-02.8 forbids it.');
        } catch (RuntimeException) {
            // expected
        }

        try {
            $certificate->delete();
            $this->fail('A certificate was deleted; FR-02.8 forbids it.');
        } catch (RuntimeException) {
            // expected
        }

        $this->assertSame(90, $certificate->fresh()->tier1_score);
        $this->assertDatabaseCount('certificates', 1);
    }

    /** FR-02.3: nothing opens before Tier 1 is passed. */
    public function test_no_experiment_opens_before_safety_tier_one(): void
    {
        $user = $this->student();
        $experiment = Experiment::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson("/api/experiments/{$experiment->id}/start", ['presentation_mode' => 'standard'])
            ->assertStatus(403)
            ->assertJsonPath('code', 'safety_tier_1_required');

        SafetyAttempt::create([
            'user_id' => $user->id,
            'tier_number' => 1,
            'score' => 90,
            'passed' => true,
            'question_ids' => [],
            'submitted_answers' => [],
            'attempted_at' => now(),
        ]);

        $this->postJson("/api/experiments/{$experiment->id}/start", ['presentation_mode' => 'standard'])
            ->assertSuccessful();
    }

    /** FR-02.2: a tier stays shut until the one before it is passed. */
    public function test_a_later_tier_is_locked_until_the_previous_one_passes(): void
    {
        $user = $this->student();
        $this->tierQuestions(2);
        Sanctum::actingAs($user);

        $this->postJson('/api/safety/tiers/2/start')
            ->assertStatus(403)
            ->assertJsonPath('code', 'tier_locked');
    }

    /** FR-01.4: five failures from one address inside fifteen minutes. */
    public function test_login_locks_out_after_five_failed_attempts(): void
    {
        User::factory()->create([
            'email' => 'student@beakerbreakers.test',
            'password' => Hash::make('correct-password'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', [
                'email' => 'student@beakerbreakers.test',
                'password' => 'wrong-password',
            ])->assertStatus(422);
        }

        // The sixth is refused even though the password is now right.
        $this->postJson('/api/login', [
            'email' => 'student@beakerbreakers.test',
            'password' => 'correct-password',
        ])->assertStatus(429);
    }
}
