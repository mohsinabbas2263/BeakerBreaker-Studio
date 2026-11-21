<?php

namespace Tests\Feature;

use App\Filament\Resources\SchoolClassResource\Pages\ManageSchoolClasses;
use App\Models\Certificate;
use App\Models\Experiment;
use App\Models\ExperimentSession;
use App\Models\SafetyAttempt;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\SchoolReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\Concerns\MakesLicensedStudents;
use Tests\TestCase;

/** FR-10.4 (experiment on or off per class) and FR-10.6 (school analytics). */
class SchoolReportTest extends TestCase
{
    use MakesLicensedStudents, RefreshDatabase;

    private function passTier1(User $student): void
    {
        SafetyAttempt::create([
            'user_id' => $student->id, 'tier_number' => 1, 'score' => 90, 'passed' => true,
            'question_ids' => [], 'submitted_answers' => [], 'attempted_at' => now(),
        ]);
    }

    private function classOf(User $student): SchoolClass
    {
        return $student->profile->schoolClass;
    }

    public function test_an_experiment_turned_off_for_a_class_disappears_at_once_and_comes_back(): void
    {
        $on = Experiment::factory()->create(['grade_level' => 9, 'board' => 'both', 'difficulty' => 'easy', 'title' => 'Stays on']);
        $off = Experiment::factory()->create(['grade_level' => 9, 'board' => 'both', 'difficulty' => 'easy', 'title' => 'Turned off']);
        $student = $this->enrolledStudent(9);
        $this->passTier1($student);
        $class = $this->classOf($student);

        $admin = User::factory()->administrator()->create(['school_id' => $class->school_id]);
        $this->actingAs($admin);

        // The form opens with every experiment ticked; untick one, as the administrator would.
        Livewire::test(ManageSchoolClasses::class)
            ->mountTableAction('experiments', $class)
            ->assertTableActionDataSet(['enabled' => [$on->id, $off->id]])
            ->set('mountedTableActionsData.0.enabled', [$on->id])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        Sanctum::actingAs($student);
        $titles = collect($this->getJson('/api/experiments')->assertOk()->json('experiments'))->pluck('title')->all();
        $this->assertSame(['Stays on'], $titles);

        // Not by address either.
        $this->getJson("/api/experiments/{$off->id}")->assertJsonPath('access.code', 'disabled_for_class');
        $this->assertDatabaseHas('audit_logs', ['action_type' => 'class.experiments_changed', 'target_reference' => "class:{$class->id}"]);

        $this->actingAs($admin);
        Livewire::test(ManageSchoolClasses::class)
            ->callTableAction('experiments', $class, data: ['enabled' => [$on->id, $off->id]]);

        Sanctum::actingAs($student);
        $this->assertCount(2, $this->getJson('/api/experiments')->json('experiments'));
    }

    public function test_an_experiment_turned_off_cannot_be_set_as_a_task(): void
    {
        $off = Experiment::factory()->create(['grade_level' => 9, 'board' => 'both']);
        $student = $this->enrolledStudent(9);
        $class = $this->classOf($student);
        $class->disabledExperiments()->attach($off->id);
        $teacher = User::factory()->teacher()->create(['school_id' => $class->school_id]);
        $class->teachers()->attach($teacher->id);

        Sanctum::actingAs($teacher);
        $this->postJson('/api/teacher/tasks', [
            'class_id' => $class->id, 'experiment_id' => $off->id, 'due_at' => now()->addWeek()->toIso8601String(), 'target' => 'class',
        ])->assertStatus(422);
    }

    public function test_the_report_counts_certification_completion_and_readiness(): void
    {
        $e1 = Experiment::factory()->create(['grade_level' => 9, 'board' => 'both']);
        Experiment::factory()->create(['grade_level' => 9, 'board' => 'both']);

        $a = $this->enrolledStudent(9);
        $class = $this->classOf($a);
        $b = User::factory()->create(['school_id' => $class->school_id]);
        $b->profile()->create(['class_id' => $class->id]);

        Certificate::create(['user_id' => $a->id, 'certificate_reference' => 'C-1',
            'tier1_score' => 90, 'tier2_score' => 90, 'tier3_score' => 90, 'tier4_score' => 90, 'issued_on' => now()]);

        // a completed e1 twice: counted once.
        foreach ([1, 2] as $_) {
            ExperimentSession::create(['user_id' => $a->id, 'experiment_id' => $e1->id, 'presentation_mode' => 'standard',
                'started_at' => now(), 'completed_at' => now(), 'steps_completed' => 1]);
        }

        // a's latest report is "ready"; an older "not_ready" does not count.
        foreach (['not_ready', 'ready'] as $classification) {
            DB::table('readiness_reports')->insert([
                'subject_user_id' => $a->id, 'requesting_user_id' => $a->id, 'classification' => $classification,
                'procedural_accuracy' => 0, 'safety_compliance' => 0, 'reagent_management' => 0,
                'time_efficiency' => 0, 'conceptual_understanding' => 0, 'error_recovery' => 0,
                'generated_at' => now(),
            ]);
        }

        $report = app(SchoolReportService::class)->build($class->school);

        $this->assertSame(2, $report['students']);
        $this->assertSame(50.0, $report['certification_rate']);
        $this->assertSame(25.0, $report['completion_rate']);               // 1 of 2 students x 2 experiments
        $this->assertSame(['ready' => 1, 'developing' => 0, 'not_ready' => 0, 'none' => 1], $report['readiness']);
        $this->assertSame(50.0, $report['by_class'][0]['certification_rate']);
    }

    public function test_an_empty_school_reports_no_data_rather_than_zero(): void
    {
        $student = $this->enrolledStudent(9);
        $school = $student->school;
        $student->forceDelete();

        $report = app(SchoolReportService::class)->build($school);

        $this->assertNull($report['certification_rate']);
        $this->assertNull($report['completion_rate']);
    }

    /** FR-10.7: the export comes back well within 10 seconds. */
    public function test_the_report_exports_as_csv_within_10_seconds(): void
    {
        $student = $this->enrolledStudent(9);
        $service = app(SchoolReportService::class);

        $started = microtime(true);
        $csv = $service->toCsv($service->build($student->school));

        $this->assertLessThan(10, microtime(true) - $started);
        $this->assertStringContainsString('Certification rate', $csv);
        $this->assertStringContainsString('Class 9-A', $csv);
    }
}
