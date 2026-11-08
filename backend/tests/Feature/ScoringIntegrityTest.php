<?php

namespace Tests\Feature;

use App\Models\Experiment;
use App\Models\ExperimentQuizQuestion;
use App\Models\ExperimentSession;
use App\Models\ExperimentStep;
use App\Models\SafetyAttempt;
use App\Models\SafetyTierQuestion;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\AnswersAttempts;
use Tests\TestCase;

/**
 * FR-02.5, SR-07: a score is computed over every question the server handed out.
 *
 * Before this was fixed, a client could send the answer to one question it knew,
 * leave out the rest, and score 100 percent on a safety tier or a quiz. That would
 * have let anyone pass all four tiers and hold a safety certificate without
 * knowing the material.
 */
class ScoringIntegrityTest extends TestCase
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

    // ---- safety tiers -------------------------------------------------------

    public function test_one_correct_answer_out_of_ten_scores_ten_percent_not_one_hundred(): void
    {
        SafetyTierQuestion::factory()->count(10)->create();
        $answered = 0;

        $response = $this->answerSafetyTier(1, function ($q) use (&$answered) {
            return $answered++ === 0 ? $q->correct_option : null;
        });

        $response->assertOk()
            ->assertJsonPath('score', 10)
            ->assertJsonPath('passed', false)
            ->assertJsonPath('points_awarded', 0);

        $this->assertFalse($this->student->hasPassedSafetyTier(1));
    }

    public function test_an_empty_submission_scores_zero(): void
    {
        SafetyTierQuestion::factory()->count(10)->create();

        $this->answerSafetyTier(1, fn () => null)
            ->assertOk()
            ->assertJsonPath('score', 0);
    }

    public function test_answers_to_questions_that_were_not_drawn_are_ignored(): void
    {
        // Twelve in the bank, ten drawn: two are never shown.
        SafetyTierQuestion::factory()->count(12)->create();
        $start = $this->postJson('/api/safety/tiers/1/start')->json();
        $drawn = collect($start['questions'])->pluck('id');
        $notDrawn = SafetyTierQuestion::whereNotIn('id', $drawn)->pluck('id');

        $answers = $notDrawn->mapWithKeys(fn ($id) => [$id => 0])->all();

        $this->postJson('/api/safety/tiers/1/submit', [
            'answers' => $answers,
            '_safety_session' => $start['_safety_session'],
        ])->assertOk()->assertJsonPath('score', 0);

        $this->assertEqualsCanonicalizing($drawn->all(), SafetyAttempt::first()->question_ids);
    }

    public function test_a_submission_without_a_started_attempt_is_refused(): void
    {
        $questions = SafetyTierQuestion::factory()->count(10)->create();

        $this->postJson('/api/safety/tiers/1/submit', [
            'answers' => $questions->mapWithKeys(fn ($q) => [$q->id => 0])->all(),
        ])->assertUnprocessable()->assertJsonValidationErrors('_safety_session');

        $this->assertDatabaseCount('safety_attempts', 0);
    }

    public function test_an_attempt_can_be_submitted_only_once(): void
    {
        SafetyTierQuestion::factory()->count(10)->create();
        $start = $this->postJson('/api/safety/tiers/1/start')->json();
        $body = ['answers' => [], '_safety_session' => $start['_safety_session']];

        $this->postJson('/api/safety/tiers/1/submit', $body)->assertOk();
        $this->postJson('/api/safety/tiers/1/submit', $body)
            ->assertUnprocessable()
            ->assertJsonPath('code', 'attempt_expired');

        $this->assertDatabaseCount('safety_attempts', 1);
    }

    public function test_one_students_attempt_cannot_be_submitted_by_another(): void
    {
        SafetyTierQuestion::factory()->count(10)->create();
        $start = $this->postJson('/api/safety/tiers/1/start')->json();

        $other = User::factory()->create();
        StudentProfile::create(['user_id' => $other->id]);
        Sanctum::actingAs($other);

        $this->postJson('/api/safety/tiers/1/submit', [
            'answers' => [],
            '_safety_session' => $start['_safety_session'],
        ])->assertUnprocessable()->assertJsonPath('code', 'attempt_expired');
    }

    public function test_an_attempt_drawn_for_one_tier_cannot_be_submitted_for_another(): void
    {
        SafetyTierQuestion::factory()->count(10)->create();
        $start = $this->postJson('/api/safety/tiers/1/start')->json();

        $this->postJson('/api/safety/tiers/2/submit', [
            'answers' => [],
            '_safety_session' => $start['_safety_session'],
        ])->assertUnprocessable()->assertJsonPath('code', 'attempt_expired');
    }

    public function test_correct_answers_given_in_the_shuffled_positions_pass(): void
    {
        // The positions shown are shuffled; marking must follow the shuffle.
        SafetyTierQuestion::factory()->count(10)->create();

        $this->answerSafetyTier(1)->assertOk()->assertJsonPath('score', 100);
    }

    // ---- experiment quizzes -------------------------------------------------

    public function test_a_partial_quiz_is_scored_over_every_question(): void
    {
        $experiment = $this->finishedExperimentWithQuiz(questions: 5);
        $answered = 0;

        $this->answerQuiz($experiment->id, function ($q) use (&$answered) {
            return $answered++ === 0 ? $q->correct_option : null;
        })
            ->assertOk()
            ->assertJsonPath('score', 20)
            ->assertJsonPath('passed', false)
            ->assertJsonPath('points_awarded', 0)
            ->assertJsonCount(5, 'feedback');
    }

    public function test_a_quiz_attempt_can_be_submitted_only_once(): void
    {
        $experiment = $this->finishedExperimentWithQuiz(questions: 2);
        $quiz = $this->getJson("/api/experiments/{$experiment->id}/quiz")->json();
        $body = ['answers' => [], '_quiz_session' => $quiz['_quiz_session']];

        $this->postJson("/api/experiments/{$experiment->id}/quiz", $body)->assertOk();
        $this->postJson("/api/experiments/{$experiment->id}/quiz", $body)
            ->assertUnprocessable()
            ->assertJsonPath('code', 'attempt_expired');
    }

    public function test_a_quiz_submission_without_an_opened_quiz_is_refused(): void
    {
        $experiment = $this->finishedExperimentWithQuiz(questions: 2);
        $ids = ExperimentQuizQuestion::pluck('id');

        $this->postJson("/api/experiments/{$experiment->id}/quiz", [
            'answers' => $ids->mapWithKeys(fn ($id) => [$id => 0])->all(),
        ])->assertUnprocessable()->assertJsonValidationErrors('_quiz_session');
    }

    public function test_a_quiz_opened_for_one_experiment_cannot_be_submitted_for_another(): void
    {
        $first = $this->finishedExperimentWithQuiz(questions: 2);
        $second = $this->finishedExperimentWithQuiz(questions: 2);
        $quiz = $this->getJson("/api/experiments/{$first->id}/quiz")->json();

        $this->postJson("/api/experiments/{$second->id}/quiz", [
            'answers' => [],
            '_quiz_session' => $quiz['_quiz_session'],
        ])->assertUnprocessable()->assertJsonPath('code', 'attempt_expired');
    }

    public function test_a_full_correct_quiz_passes_and_awards_points_once(): void
    {
        $experiment = $this->finishedExperimentWithQuiz(questions: 3);

        $this->answerQuiz($experiment->id)->assertOk()
            ->assertJsonPath('score', 100)
            ->assertJsonPath('points_awarded', 50);

        // FR-04.3: a second pass awards nothing.
        $this->answerQuiz($experiment->id)->assertOk()
            ->assertJsonPath('score', 100)
            ->assertJsonPath('points_awarded', 0);
    }

    private function finishedExperimentWithQuiz(int $questions): Experiment
    {
        $experiment = Experiment::factory()->create(['base_points' => 50]);
        ExperimentStep::create([
            'experiment_id' => $experiment->id,
            'step_order' => 1,
            'title' => 'Only step',
            'instruction_text' => 'Do it.',
            'preauthored_guidance' => 'Like this.',
        ]);

        for ($i = 0; $i < $questions; $i++) {
            ExperimentQuizQuestion::create([
                'experiment_id' => $experiment->id,
                'prompt' => "Question {$i}?",
                'options' => ["Right {$i}", "Wrong A {$i}", "Wrong B {$i}"],
                'correct_option' => 0,
                'explanation' => "Because {$i}.",
            ]);
        }

        ExperimentSession::create([
            'user_id' => $this->student->id,
            'experiment_id' => $experiment->id,
            'presentation_mode' => 'standard',
            'started_at' => now()->subMinutes(10),
            'completed_at' => now(),
            'steps_completed' => 1,
            'completed_step_ids' => [$experiment->steps()->first()->id],
        ]);

        return $experiment;
    }
}
