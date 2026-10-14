<?php

namespace Tests\Feature;

use App\Models\Experiment;
use App\Models\ExperimentQuizQuestion;
use App\Models\ExperimentStep;
use App\Models\SafetyAttempt;
use App\Models\SafetyTierQuestion;
use App\Models\SchoolClass;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        StudentProfile::create(['user_id' => $this->user->id]);

        SafetyAttempt::create([
            'user_id' => $this->user->id,
            'tier_number' => 1,
            'score' => 90,
            'passed' => true,
            'question_ids' => [],
            'submitted_answers' => [],
            'attempted_at' => now(),
        ]);

        Sanctum::actingAs($this->user);
    }

    protected function experimentWithSteps(int $steps = 3): Experiment
    {
        $experiment = Experiment::factory()->create(['base_points' => 50]);

        for ($i = 1; $i <= $steps; $i++) {
            ExperimentStep::create([
                'experiment_id' => $experiment->id,
                'step_order' => $i,
                'title' => "Step {$i}",
                'instruction_text' => "Do step {$i}.",
                'preauthored_guidance' => "Guidance for step {$i}.",
            ]);
        }

        return $experiment;
    }

    /** FR-03.4: a step cannot be completed out of order. */
    public function test_steps_must_be_completed_in_their_declared_order(): void
    {
        $experiment = $this->experimentWithSteps();
        $session = $this->postJson("/api/experiments/{$experiment->id}/start", [
            'presentation_mode' => 'standard',
        ])->json('session');

        $third = $experiment->steps()->where('step_order', 3)->first();

        $this->postJson("/api/sessions/{$session['id']}/steps", ['step_id' => $third->id])
            ->assertStatus(422)
            ->assertJsonPath('code', 'out_of_order');
    }

    /** FR-03.5: progress is stored, and comes back on resume. */
    public function test_progress_is_recorded_and_restored(): void
    {
        $experiment = $this->experimentWithSteps();
        $session = $this->postJson("/api/experiments/{$experiment->id}/start", [
            'presentation_mode' => 'standard',
        ])->json('session');

        $first = $experiment->steps()->where('step_order', 1)->first();

        $this->postJson("/api/sessions/{$session['id']}/steps", ['step_id' => $first->id])
            ->assertOk()
            ->assertJsonPath('steps_completed', 1)
            ->assertJsonPath('quiz_unlocked', false);

        $this->getJson("/api/experiments/{$experiment->id}")
            ->assertOk()
            ->assertJsonPath('resume.steps_completed', 1);
    }

    /** FR-04.1: the quiz stays shut until every step is done. */
    public function test_the_quiz_is_unavailable_until_all_steps_are_complete(): void
    {
        $experiment = $this->experimentWithSteps(2);
        ExperimentQuizQuestion::create([
            'experiment_id' => $experiment->id,
            'prompt' => 'Which gas?',
            'options' => ['Carbon dioxide', 'Oxygen'],
            'correct_option' => 0,
            'explanation' => 'Carbon dioxide.',
        ]);

        $this->getJson("/api/experiments/{$experiment->id}/quiz")
            ->assertStatus(403)
            ->assertJsonPath('code', 'steps_incomplete');

        $session = $this->postJson("/api/experiments/{$experiment->id}/start", [
            'presentation_mode' => 'standard',
        ])->json('session');

        foreach ($experiment->steps as $step) {
            $this->postJson("/api/sessions/{$session['id']}/steps", ['step_id' => $step->id])
                ->assertOk();
        }

        $this->getJson("/api/experiments/{$experiment->id}/quiz")->assertOk();
    }

    /** FR-04.2: a wrong answer comes back with its explanation, a right one does not. */
    public function test_quiz_feedback_explains_only_the_wrong_answers(): void
    {
        $experiment = $this->experimentWithSteps(1);
        $right = ExperimentQuizQuestion::create([
            'experiment_id' => $experiment->id,
            'prompt' => 'Right one?',
            'options' => ['Yes', 'No'],
            'correct_option' => 0,
            'explanation' => 'Because yes.',
        ]);
        $wrong = ExperimentQuizQuestion::create([
            'experiment_id' => $experiment->id,
            'prompt' => 'Wrong one?',
            'options' => ['Yes', 'No'],
            'correct_option' => 1,
            'explanation' => 'Because no.',
        ]);

        $session = $this->postJson("/api/experiments/{$experiment->id}/start", [
            'presentation_mode' => 'standard',
        ])->json('session');
        $this->postJson("/api/sessions/{$session['id']}/steps", [
            'step_id' => $experiment->steps()->first()->id,
        ]);

        $response = $this->postJson("/api/experiments/{$experiment->id}/quiz", [
            'answers' => [$right->id => 0, $wrong->id => 0],
        ])->assertOk();

        $feedback = collect($response->json('feedback'));

        $this->assertNull($feedback->firstWhere('question_id', $right->id)['explanation']);
        $this->assertSame('Because no.', $feedback->firstWhere('question_id', $wrong->id)['explanation']);
        $this->assertSame(50, $response->json('score'));
        $this->assertFalse($response->json('passed'));
        $this->assertSame(0, $response->json('points_awarded'));
    }

    /** FR-07.1 and SR-08: the leaderboard carries four fields and no scores. */
    public function test_the_leaderboard_exposes_only_the_permitted_fields(): void
    {
        $school = School::create([
            'name' => 'Test School',
            'district' => 'Gujrat',
            'licence_start' => now()->subMonth(),
            'licence_end' => now()->addYear(),
            'student_cap' => 100,
        ]);
        $class = SchoolClass::create([
            'school_id' => $school->id,
            'title' => 'Class 10-A',
            'grade_level' => 10,
            'academic_year' => '2026-2027',
        ]);

        $this->user->profile->update(['class_id' => $class->id]);

        $classmate = User::factory()->create(['school_id' => $school->id]);
        StudentProfile::create([
            'user_id' => $classmate->id,
            'class_id' => $class->id,
            'total_points' => 400,
            'rank_level' => 2,
        ]);

        $row = $this->getJson('/api/leaderboard')->assertOk()->json('rows.0');

        $this->assertEqualsCanonicalizing(
            ['position', 'display_name', 'avatar_key', 'rank_level', 'rank_name', 'total_points', 'is_me'],
            array_keys($row)
        );
        $this->assertArrayNotHasKey('email', $row);
    }

    /** SR-03: no token, no access. */
    public function test_protected_routes_refuse_an_unauthenticated_request(): void
    {
        app('auth')->forgetGuards();

        $this->getJson('/api/experiments')->assertStatus(401);
        $this->getJson('/api/safety/tiers')->assertStatus(401);
    }

    /** SR-04: a teacher's token does not reach a student route. */
    public function test_a_teacher_cannot_use_a_student_route(): void
    {
        Sanctum::actingAs(User::factory()->teacher()->create());

        $this->getJson('/api/experiments')->assertStatus(403);
    }

    /** FR-05.1: AR is offered only where it exists. */
    public function test_ar_mode_is_refused_where_the_experiment_has_none(): void
    {
        $experiment = $this->experimentWithSteps(1);
        $experiment->update(['ar_available' => false]);

        $this->postJson("/api/experiments/{$experiment->id}/start", ['presentation_mode' => 'ar'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'ar_unavailable');
    }

    /** FR-02.7: the certificate appears the moment the fourth tier is passed. */
    public function test_a_certificate_is_issued_once_all_four_tiers_pass(): void
    {
        foreach ([2, 3, 4] as $tier) {
            $questions = SafetyTierQuestion::factory()->tier($tier)->count(10)->create();

            if ($tier > 2) {
                // the previous tier is already passed by the loop below
            }

            $answers = $questions->mapWithKeys(fn ($q) => [$q->id => $q->correct_option])->all();

            $this->postJson("/api/safety/tiers/{$tier}/submit", ['answers' => $answers])
                ->assertOk()
                ->assertJsonPath('passed', true);
        }

        $this->getJson('/api/safety/certificate')
            ->assertOk()
            ->assertJsonPath('certificate.tier4_score', 100);

        $this->assertDatabaseCount('certificates', 1);
    }
}
