<?php

namespace Tests\Feature;

use App\Models\Experiment;
use App\Models\ExperimentStep;
use App\Models\SafetyAttempt;
use App\Models\StepAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\Concerns\MakesLicensedStudents;
use Tests\TestCase;

/**
 * docs/experiment-engine.md through the API: FR-02.5 (server judges), FR-03.4
 * (order), FR-03.5 and NFR-05 (resume mid-step), FR-08.1 (every move recorded).
 */
class GameEngineTest extends TestCase
{
    use MakesLicensedStudents, RefreshDatabase;

    private User $student;
    private Experiment $experiment;
    /** @var array<int, ExperimentStep> by step_order */
    private array $steps = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = $this->enrolledStudent();
        SafetyAttempt::create([
            'user_id' => $this->student->id, 'tier_number' => 1, 'score' => 90, 'passed' => true,
            'question_ids' => [], 'submitted_answers' => [], 'attempted_at' => now(),
        ]);

        $this->experiment = Experiment::factory()->create(['grade_level' => 9, 'board' => 'both']);

        $defs = [
            ['gear', ['items' => ['goggles', 'coat', 'sandals'], 'required' => ['goggles', 'coat'], 'forbidden' => ['sandals']],
                ['sandals' => 'Closed shoes only.']],
            ['measure', ['unit' => 'mL', 'min' => 0, 'max' => 50, 'step' => 0.5, 'target' => 25, 'tolerance' => 0.5], ['too_low' => 'More.']],
            ['add_until', ['unit' => 'mL', 'increments' => [1, 0.1], 'end' => 18.4, 'tolerance' => 0.3,
                'visual' => ['from' => 'pink', 'to' => 'colourless', 'fade_from' => 15.9]], []],
            ['read', ['unit' => 'mL', 'value_from' => 'step:3', 'tolerance' => 0.1], []],
            ['confirm', [], []],
        ];

        foreach ($defs as $i => [$kind, $config, $messages]) {
            $this->steps[$i + 1] = ExperimentStep::create([
                'experiment_id' => $this->experiment->id, 'step_order' => $i + 1, 'kind' => $kind,
                'title' => "Step ".($i + 1), 'instruction_text' => 'Do it.', 'preauthored_guidance' => 'Hint.',
                'config' => $config, 'messages' => ['intro' => "Intro {$kind}", 'success' => 'Nice!'] + $messages,
            ]);
        }

        Sanctum::actingAs($this->student);
    }

    private function start(): int
    {
        return $this->postJson("/api/experiments/{$this->experiment->id}/start", ['presentation_mode' => 'standard'])
            ->assertCreated()->json('session.id');
    }

    private function act(int $session, int $order, array $action, bool $hinted = false)
    {
        return $this->postJson("/api/sessions/{$session}/steps/{$this->steps[$order]->id}/actions",
            ['action' => $action, 'hinted' => $hinted]);
    }

    public function test_the_detail_sends_kind_public_config_and_intro_but_no_answers(): void
    {
        $steps = collect($this->getJson("/api/experiments/{$this->experiment->id}")->assertOk()->json('steps'))->keyBy('step_order');

        $this->assertSame('gear', $steps[1]['kind']);
        $this->assertSame('Intro gear', $steps[1]['intro']);
        $this->assertSame(['goggles', 'coat', 'sandals'], $steps[1]['config']['items']);
        $this->assertArrayNotHasKey('required', $steps[1]['config']);
        $this->assertArrayNotHasKey('forbidden', $steps[1]['config']);
        $this->assertArrayNotHasKey('target', $steps[2]['config']);
        $this->assertArrayNotHasKey('tolerance', $steps[3]['config']);
        $this->assertSame(18.4, $steps[3]['config']['end']);            // public: the colour change needs it
        $this->assertArrayNotHasKey('value_from', $steps[4]['config']);
    }

    public function test_a_whole_game_experiment_is_judged_on_the_server_and_opens_the_quiz(): void
    {
        $s = $this->start();

        $this->act($s, 1, ['item' => 'goggles'])->assertOk()->assertJsonPath('outcome', 'partial')->assertJsonPath('mood', 'point');
        $this->act($s, 1, ['item' => 'sandals'])->assertOk()
            ->assertJsonPath('outcome', 'mistake')->assertJsonPath('category', 'safety')
            ->assertJsonPath('mood', 'worried')->assertJsonPath('message', 'Closed shoes only.');
        $this->act($s, 1, ['item' => 'coat'])->assertOk()
            ->assertJsonPath('step_complete', true)->assertJsonPath('steps_completed', 1)->assertJsonPath('message', 'Nice!');

        $this->act($s, 2, ['value' => 20])->assertJsonPath('mistake_code', 'too_low')->assertJsonPath('message', 'More.');
        $this->act($s, 2, ['value' => 25])->assertJsonPath('step_complete', true);

        $this->act($s, 3, ['stop_at' => 18.6])->assertJsonPath('outcome', 'correct');
        // value_from: the student's own end point, not the book's.
        $this->act($s, 4, ['value' => 18.4])->assertJsonPath('outcome', 'mistake');
        $this->act($s, 4, ['value' => 18.6])->assertJsonPath('outcome', 'correct');

        $this->act($s, 5, [])->assertOk()
            ->assertJsonPath('quiz_unlocked', true)->assertJsonPath('mood', 'party')->assertJsonPath('steps_completed', 5);

        // FR-08.1: every move is recorded with its outcome and category.
        $this->assertSame(9, StepAction::where('session_id', $s)->count());   // 3 + 2 + 1 + 2 + 1
        $this->assertSame(1, StepAction::where('category', 'safety')->where('mistake_code', 'sandals')->count());
        $this->assertSame(1, StepAction::where('category', 'reagent')->where('mistake_code', 'too_low')->count());
    }

    public function test_a_game_step_cannot_be_skipped_through_the_old_endpoint(): void
    {
        $s = $this->start();

        $this->postJson("/api/sessions/{$s}/steps", ['step_id' => $this->steps[1]->id])
            ->assertStatus(422)->assertJsonPath('code', 'use_actions');

        $this->assertSame(0, $this->student->experimentSessions()->find($s)->steps_completed);
    }

    public function test_steps_stay_in_order(): void
    {
        $s = $this->start();

        $this->act($s, 2, ['value' => 25])->assertStatus(422)->assertJsonPath('code', 'out_of_order');
        $this->assertSame(0, StepAction::count());
    }

    public function test_a_badly_formed_action_is_refused_before_judging(): void
    {
        $s = $this->start();
        $this->act($s, 1, ['item' => 'goggles'])->assertOk();
        $this->act($s, 1, ['item' => 'coat'])->assertOk();

        $this->act($s, 2, ['value' => 'lots'])->assertStatus(422)->assertJsonValidationErrors('action.value');
        $this->act($s, 2, [])->assertStatus(422);
        $this->assertSame(2, StepAction::count());
    }

    public function test_a_refresh_resumes_mid_step_without_giving_the_answer_away(): void
    {
        $s = $this->start();
        $this->act($s, 1, ['item' => 'goggles'])->assertOk();
        $this->act($s, 1, ['item' => 'sandals'])->assertOk();

        $resume = $this->getJson("/api/experiments/{$this->experiment->id}")->assertOk()->json('resume.step_state');
        $this->assertSame($this->steps[1]->id, $resume['step_id']);
        $this->assertSame(1, $resume['mistakes']);
        $this->assertSame(['worn' => ['goggles']], $resume['progress']);

        // Carrying on from that state finishes the step with one more item.
        $this->act($s, 1, ['item' => 'coat'])->assertJsonPath('step_complete', true);
    }

    public function test_after_three_mistakes_the_answer_is_shown_and_doing_it_counts_as_revealed(): void
    {
        $s = $this->start();
        $this->act($s, 1, ['item' => 'goggles']);
        $this->act($s, 1, ['item' => 'coat']);

        $this->act($s, 2, ['value' => 10])->assertJsonPath('reveal', null);
        $this->act($s, 2, ['value' => 11])->assertJsonPath('reveal', null);
        $this->act($s, 2, ['value' => 12])->assertJsonPath('reveal.target', 25)->assertJsonPath('mistakes', 3);
        $this->act($s, 2, ['value' => 25])->assertJsonPath('outcome', 'revealed')->assertJsonPath('step_complete', true);

        $this->assertSame(3, StepAction::where('outcome', 'mistake')->where('step_id', $this->steps[2]->id)->count());
    }

    public function test_hints_are_recorded_with_the_move(): void
    {
        $s = $this->start();
        $this->act($s, 1, ['item' => 'goggles'], hinted: true)->assertOk();

        $this->assertTrue(StepAction::sole()->hinted);
    }

    public function test_another_students_session_cannot_be_played(): void
    {
        $s = $this->start();
        Sanctum::actingAs($this->enrolledStudent());

        $this->act($s, 1, ['item' => 'goggles'])->assertForbidden();
    }

    public function test_a_finished_session_takes_no_more_moves(): void
    {
        $s = $this->start();
        $this->student->experimentSessions()->find($s)->update(['completed_at' => now()]);

        $this->act($s, 1, ['item' => 'goggles'])->assertStatus(422);
    }

    public function test_a_step_from_another_experiment_is_refused(): void
    {
        $s = $this->start();
        $other = ExperimentStep::create([
            'experiment_id' => Experiment::factory()->create()->id, 'step_order' => 1, 'kind' => 'confirm',
            'title' => 'x', 'instruction_text' => 'x', 'preauthored_guidance' => 'x',
        ]);

        $this->postJson("/api/sessions/{$s}/steps/{$other->id}/actions", ['action' => []])->assertStatus(422);
    }

    public function test_step_actions_are_insert_only(): void
    {
        $s = $this->start();
        $this->act($s, 1, ['item' => 'goggles']);

        $this->expectException(RuntimeException::class);
        StepAction::sole()->update(['outcome' => 'correct']);
    }
}
