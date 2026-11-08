<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Experiment;
use App\Models\ExperimentSession;
use App\Models\QuizAttempt;
use App\Models\ReadinessReport;
use App\Models\SafetyAttempt;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\ReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * M4: FR-08.1 to FR-08.6, SR-08, NFR-06.
 *
 * Done when (docs/build-order.md): a student cannot reach another student's values
 * by any route, and a report still generates with the Gemini key removed.
 */
class ReadinessTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private SchoolClass $class;
    private User $amna;
    private User $bilal;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.gemini.key' => null]);

        $school = School::create([
            'name' => 'Test School', 'district' => 'Gujrat', 'board' => 'punjab', 'licensed_grades' => [9, 10],
            'licence_start' => now()->subMonth(), 'licence_end' => now()->addYear(), 'student_cap' => 100,
        ]);
        $this->class = SchoolClass::create(['school_id' => $school->id, 'title' => 'Class 9-A', 'grade_level' => 9, 'academic_year' => '2026-2027']);

        $this->teacher = User::factory()->create(['role' => UserRole::Teacher, 'school_id' => $school->id]);
        $this->class->teachers()->attach($this->teacher->id);

        foreach (['amna' => 'Amna', 'bilal' => 'Bilal'] as $prop => $name) {
            $this->{$prop} = User::factory()->create(['display_name' => $name, 'school_id' => $school->id]);
            StudentProfile::create(['user_id' => $this->{$prop}->id, 'class_id' => $this->class->id]);
        }
    }

    /** A finished experiment: one session of $minutes against an estimate of 15. */
    private function finish(User $student, int $minutes = 15, bool $abandonedFirst = false): Experiment
    {
        $experiment = Experiment::factory()->create(['estimated_minutes' => 15]);
        $start = now()->subDays(2);

        if ($abandonedFirst) {
            ExperimentSession::create([
                'user_id' => $student->id, 'experiment_id' => $experiment->id, 'presentation_mode' => 'standard',
                'started_at' => $start->copy()->subHour(), 'steps_completed' => 1, 'completed_step_ids' => [],
            ]);
        }

        ExperimentSession::create([
            'user_id' => $student->id, 'experiment_id' => $experiment->id, 'presentation_mode' => 'standard',
            'started_at' => $start, 'completed_at' => $start->copy()->addMinutes($minutes),
            'steps_completed' => 3, 'completed_step_ids' => [],
        ]);

        return $experiment;
    }

    private function quiz(User $student, Experiment $experiment, int $score, int $minutesAgo = 60): void
    {
        QuizAttempt::create([
            'user_id' => $student->id, 'experiment_id' => $experiment->id, 'score' => $score,
            'passed' => $score >= 80, 'submitted_answers' => [], 'submitted_at' => now()->subMinutes($minutesAgo),
        ]);
    }

    private function safety(User $student, int $tier, int $score, int $minutesAgo = 600): void
    {
        SafetyAttempt::create([
            'user_id' => $student->id, 'tier_number' => $tier, 'score' => $score, 'passed' => $score >= 80,
            'question_ids' => [], 'submitted_answers' => [], 'attempted_at' => now()->subMinutes($minutesAgo),
        ]);
    }

    /** Three finished experiments with a mixed history, for numbers worked out by hand. */
    private function mixedHistory(User $s): void
    {
        $a = $this->finish($s, 15);                       // on time
        $b = $this->finish($s, 30, abandonedFirst: true); // half speed, restarted once
        $c = $this->finish($s, 10);                       // faster than estimate, capped at 100

        // A fourth experiment started and left unfinished.
        ExperimentSession::create([
            'user_id' => $s->id, 'experiment_id' => Experiment::factory()->create()->id, 'presentation_mode' => 'standard',
            'started_at' => now()->subDay(), 'steps_completed' => 1, 'completed_step_ids' => [],
        ]);

        $this->quiz($s, $a, 90);
        $this->quiz($s, $b, 60, 120);
        $this->quiz($s, $b, 85, 30);   // failed first, then recovered
        $this->quiz($s, $c, 70);       // failed, never recovered

        $this->safety($s, 1, 70, 900);
        $this->safety($s, 1, 90, 800); // a retry does not change the first score
        $this->safety($s, 2, 100);
    }

    // ---- FR-08.1: the six dimensions ---------------------------------------

    public function test_the_six_dimensions_follow_the_interim_formulas(): void
    {
        $this->mixedHistory($this->amna);

        $d = app(ReadinessService::class)->dimensions($this->amna);

        $this->assertSame([
            'procedural_accuracy' => 75,        // 3 finished of 4 begun
            'safety_compliance' => 85,          // first tries 70 and 100
            'reagent_management' => 67,         // 2 of 3 finished in the first sitting
            'time_efficiency' => 83,            // (100 + 50 + 100) / 3
            'conceptual_understanding' => 73,   // first tries 90, 60, 70
            'error_recovery' => 50,             // 1 of 2 first-time fails passed later
        ], $d);
    }

    /** Records moves as the engine would, in one session of a fresh experiment. */
    private function play(User $s, array $steps): void
    {
        $experiment = $this->finish($s);
        $session = ExperimentSession::where('user_id', $s->id)->where('experiment_id', $experiment->id)->first();

        foreach ($steps as $order => [$kind, $outcomes]) {
            $step = \App\Models\ExperimentStep::create([
                'experiment_id' => $experiment->id, 'step_order' => $order + 1, 'kind' => $kind,
                'title' => 's', 'instruction_text' => 's', 'preauthored_guidance' => 's',
            ]);
            foreach ($outcomes as $outcome) {
                [$result, $category] = array_pad(explode(':', $outcome), 2, null);
                \App\Models\StepAction::create([
                    'session_id' => $session->id, 'step_id' => $step->id, 'user_id' => $s->id, 'kind' => $kind,
                    'action' => [], 'outcome' => $result, 'category' => $category,
                ]);
            }
        }
    }

    public function test_once_game_steps_are_played_the_engine_formulas_apply(): void
    {
        // Experiment 1: a safety slip on the gear, a clean measure, an observe wrong then right.
        $this->play($this->amna, [
            ['gear', ['mistake:safety', 'partial', 'correct']],
            ['measure', ['correct']],
            ['observe', ['mistake:concept', 'correct']],
        ]);
        // Experiment 2: an overshoot needing the answer shown, a clean choose.
        $this->play($this->amna, [
            ['add_until', ['mistake:reagent', 'mistake:reagent', 'mistake:reagent', 'revealed']],
            ['choose', ['correct']],
        ]);
        $this->finish($this->amna);   // a third, older experiment with no game steps
        $q = Experiment::first();
        $this->quiz($this->amna, $q, 80);

        $service = app(ReadinessService::class);
        $this->assertSame(ReadinessService::ENGINE, $service->formulaVersion($this->amna));

        $this->assertSame([
            'procedural_accuracy' => 40,        // 2 clean (measure, choose) of 5 steps finished
            'safety_compliance' => 88,          // experiment 1: 75, experiment 2: 100
            'reagent_management' => 67,         // measure and choose right first time; add_until not
            'time_efficiency' => 100,           // as before, from sessions
            'conceptual_understanding' => 40,   // quiz 80 and observe right first time 0, averaged
            'error_recovery' => 67,             // gear and observe recovered; add_until needed the answer
        ], $service->dimensions($this->amna));
    }

    public function test_a_report_records_its_formulas_and_which_values_are_estimated(): void
    {
        $this->mixedHistory($this->amna);
        $this->play($this->bilal, [['choose', ['correct']]]);
        $this->finish($this->bilal);
        $this->finish($this->bilal);

        Sanctum::actingAs($this->teacher);
        $this->postJson("/api/teacher/students/{$this->amna->id}/readiness")->assertCreated()
            ->assertJsonPath('report.formula_version', 'interim-1')
            ->assertJsonPath('report.estimated', ['reagent_management', 'error_recovery']);
        $this->postJson("/api/teacher/students/{$this->bilal->id}/readiness")->assertCreated()
            ->assertJsonPath('report.formula_version', 'engine-1')
            ->assertJsonPath('report.estimated', []);
    }

    public function test_error_recovery_is_full_when_nothing_was_failed(): void
    {
        foreach (range(1, 3) as $i) {
            $this->quiz($this->amna, $this->finish($this->amna), 95);
        }

        $this->assertSame(100, app(ReadinessService::class)->dimensions($this->amna)['error_recovery']);
    }

    public function test_the_rule_classification_puts_safety_first(): void
    {
        $service = app(ReadinessService::class);
        $all = fn (int $v, int $safety) => array_merge(
            array_fill_keys(ReadinessReport::DIMENSIONS, $v),
            ['safety_compliance' => $safety],
        );

        $this->assertSame('ready', $service->ruleClassification($all(85, 95)));
        $this->assertSame('developing', $service->ruleClassification($all(95, 85)));
        $this->assertSame('developing', $service->ruleClassification($all(65, 65)));
        $this->assertSame('not_ready', $service->ruleClassification($all(40, 40)));
    }

    // ---- FR-08.2, FR-08.4, FR-08.5, NFR-06: generating ---------------------

    public function test_a_report_is_refused_under_three_completed_experiments_with_the_reason(): void
    {
        $this->finish($this->amna);
        $this->finish($this->amna);

        Sanctum::actingAs($this->teacher);
        $this->postJson("/api/teacher/students/{$this->amna->id}/readiness")
            ->assertStatus(422)
            ->assertJsonPath('code', 'not_eligible')
            ->assertJsonPath('completed', 2)
            ->assertJsonPath('required', 3);

        $this->assertSame(0, ReadinessReport::count());
    }

    public function test_with_no_gemini_key_the_report_still_generates_and_is_recorded(): void
    {
        $this->mixedHistory($this->amna);

        Sanctum::actingAs($this->teacher);
        $res = $this->postJson("/api/teacher/students/{$this->amna->id}/readiness")->assertCreated()
            ->assertJsonPath('report.ai_generated', false)
            ->assertJsonPath('report.classification', 'developing')  // average 72, safety 85
            ->assertJsonPath('report.dimensions.procedural_accuracy', 75);

        $this->assertStringContainsString('Approaching readiness', $res->json('report.narrative'));

        // FR-08.5: teacher, student, six values, classification and time are stored.
        $report = ReadinessReport::sole();
        $this->assertSame($this->teacher->id, $report->requesting_user_id);
        $this->assertSame($this->amna->id, $report->subject_user_id);
        $this->assertSame(85, (int) $report->safety_compliance);
        $this->assertNotNull($report->generated_at);
        $this->assertSame(1, AuditLog::where('action_type', 'readiness.generated')->count());
    }

    public function test_gemini_classifies_when_it_answers_but_never_ready_under_90_on_safety(): void
    {
        config(['services.gemini.key' => 'test-key']);
        Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [[
            'text' => json_encode(['classification' => 'ready', 'narrative' => 'Strong <b>work</b> overall.']),
        ]]]]]])]);
        $this->mixedHistory($this->amna);   // safety 85

        Sanctum::actingAs($this->teacher);
        $this->postJson("/api/teacher/students/{$this->amna->id}/readiness")->assertCreated()
            ->assertJsonPath('report.ai_generated', true)
            ->assertJsonPath('report.classification', 'developing')
            ->assertJsonPath('report.narrative', 'Strong work overall.');
    }

    public function test_an_unusable_gemini_answer_falls_back_to_the_rule(): void
    {
        config(['services.gemini.key' => 'test-key']);
        Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'not json at all']]]]]])]);
        $this->mixedHistory($this->amna);

        Sanctum::actingAs($this->teacher);
        $this->postJson("/api/teacher/students/{$this->amna->id}/readiness")->assertCreated()
            ->assertJsonPath('report.ai_generated', false);
    }

    public function test_a_gemini_timeout_falls_back_to_the_rule(): void
    {
        config(['services.gemini.key' => 'test-key']);
        Http::fake(fn () => throw new ConnectionException('timed out'));
        $this->mixedHistory($this->amna);

        Sanctum::actingAs($this->teacher);
        $this->postJson("/api/teacher/students/{$this->amna->id}/readiness")->assertCreated()
            ->assertJsonPath('report.ai_generated', false)
            ->assertJsonPath('report.classification', 'developing');
    }

    // ---- FR-09.1: teachers reach only their own students -----------------------

    public function test_a_teacher_cannot_generate_for_a_student_outside_their_classes(): void
    {
        $other = SchoolClass::create(['title' => 'Class 9-B', 'grade_level' => 9, 'academic_year' => '2026-2027']);
        $zara = User::factory()->create();
        StudentProfile::create(['user_id' => $zara->id, 'class_id' => $other->id]);
        $this->mixedHistory($zara);

        Sanctum::actingAs($this->teacher);
        $this->postJson("/api/teacher/students/{$zara->id}/readiness")->assertForbidden()->assertJsonPath('code', 'not_assigned');
        $this->postJson("/api/teacher/students/{$this->teacher->id}/readiness")->assertForbidden();
        $this->getJson("/api/teacher/classes/{$other->id}/readiness")->assertForbidden();

        $this->assertSame(0, ReadinessReport::count());
        $this->assertSame(3, AuditLog::where('action_type', 'teacher.refused')->count());
    }

    public function test_the_class_list_shows_progress_toward_eligibility_and_the_latest_report(): void
    {
        $this->mixedHistory($this->amna);
        $this->finish($this->bilal);

        Sanctum::actingAs($this->teacher);
        $this->postJson("/api/teacher/students/{$this->amna->id}/readiness")->assertCreated();

        $rows = collect($this->getJson("/api/teacher/classes/{$this->class->id}/readiness")->assertOk()
            ->assertJsonPath('required', 3)->json('students'))->keyBy('display_name');

        $this->assertSame(3, $rows['Amna']['completed']);
        $this->assertSame('developing', $rows['Amna']['report']['classification']);
        $this->assertSame(1, $rows['Bilal']['completed']);
        $this->assertNull($rows['Bilal']['report']);
    }

    // ---- FR-08.6 / SR-08: a student sees only their own --------------------------

    public function test_a_student_sees_their_own_latest_report_and_never_anothers(): void
    {
        $this->mixedHistory($this->amna);
        $this->mixedHistory($this->bilal);
        Sanctum::actingAs($this->teacher);
        $this->postJson("/api/teacher/students/{$this->amna->id}/readiness")->assertCreated();
        $bilalReport = $this->postJson("/api/teacher/students/{$this->bilal->id}/readiness")->assertCreated()->json('report.id');

        Sanctum::actingAs($this->amna);
        $mine = $this->getJson('/api/readiness')->assertOk()->json('report');
        $this->assertSame($this->amna->id, $mine['student']['id']);
        $this->assertNotSame($bilalReport, $mine['id']);

        // No route takes a student id or a report id from a student.
        $this->getJson("/api/readiness?student={$this->bilal->id}")->assertOk()->assertJsonPath('report.student.id', $this->amna->id);
        $this->getJson("/api/teacher/classes/{$this->class->id}/readiness")->assertForbidden();
        $this->postJson("/api/teacher/students/{$this->bilal->id}/readiness")->assertForbidden();
    }

    public function test_a_student_is_told_why_there_is_no_report(): void
    {
        Sanctum::actingAs($this->amna);
        $this->getJson('/api/readiness')->assertStatus(422)->assertJsonPath('code', 'not_eligible')->assertJsonPath('completed', 0);

        foreach (range(1, 3) as $i) {
            $this->finish($this->amna);
        }
        $this->getJson('/api/readiness')->assertNotFound()->assertJsonPath('code', 'no_report');
    }
}
