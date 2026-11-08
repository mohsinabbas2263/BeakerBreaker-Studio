<?php

namespace Tests\Feature;

use App\Filament\Resources\SchoolResource\Pages\ManageSchools;
use App\Models\Experiment;
use App\Models\SafetyAttempt;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\Concerns\MakesLicensedStudents;
use Tests\TestCase;

/**
 * Licensing by class and board, decided 8 Nov 2026.
 *
 * A school is licensed for some of Classes 9 to 12 and follows one board. A student
 * sees their class's exam pair (9 and 10, or 11 and 12), limited to licensed classes,
 * and only experiments of their school's board or of both boards.
 */
class LicensingTest extends TestCase
{
    use MakesLicensedStudents, RefreshDatabase;

    private function experiment(int $grade, string $board = 'both'): Experiment
    {
        return Experiment::factory()->create(['grade_level' => $grade, 'board' => $board, 'title' => "G{$grade} {$board}"]);
    }

    private function visibleTitles(User $student): array
    {
        Sanctum::actingAs($student);

        return collect($this->getJson('/api/experiments')->assertOk()->json('experiments'))
            ->pluck('title')->sort()->values()->all();
    }

    private function passTier1(User $student): void
    {
        SafetyAttempt::create([
            'user_id' => $student->id, 'tier_number' => 1, 'score' => 90, 'passed' => true,
            'question_ids' => [], 'submitted_answers' => [], 'attempted_at' => now(),
        ]);
    }

    public function test_a_class_9_or_10_student_sees_both_classes_of_the_matric_pair(): void
    {
        foreach ([9, 10, 11, 12] as $g) {
            $this->experiment($g);
        }

        $this->assertSame(['G10 both', 'G9 both'], $this->visibleTitles($this->enrolledStudent(9, [9, 10, 11, 12])));
        $this->assertSame(['G10 both', 'G9 both'], $this->visibleTitles($this->enrolledStudent(10, [9, 10, 11, 12])));
    }

    public function test_a_class_11_or_12_student_sees_both_classes_of_the_intermediate_pair(): void
    {
        foreach ([9, 10, 11, 12] as $g) {
            $this->experiment($g);
        }

        $this->assertSame(['G11 both', 'G12 both'], $this->visibleTitles($this->enrolledStudent(11, [9, 10, 11, 12])));
        $this->assertSame(['G11 both', 'G12 both'], $this->visibleTitles($this->enrolledStudent(12, [11, 12])));
    }

    public function test_only_licensed_classes_are_shown(): void
    {
        $this->experiment(9);
        $this->experiment(10);

        // The school bought Class 10 only.
        $this->assertSame(['G10 both'], $this->visibleTitles($this->enrolledStudent(10, [10])));
        // A Class 9 student of a school that bought Class 10 only still sees Class 10 (their exam pair).
        $this->assertSame(['G10 both'], $this->visibleTitles($this->enrolledStudent(9, [10])));
        // Nothing licensed in the pair: nothing shown.
        $this->assertSame([], $this->visibleTitles($this->enrolledStudent(9, [11, 12])));
    }

    public function test_a_school_sees_its_own_board_and_shared_experiments(): void
    {
        $this->experiment(9, 'punjab');
        $this->experiment(9, 'federal');
        $this->experiment(9, 'both');

        $this->assertSame(['G9 both', 'G9 punjab'], $this->visibleTitles($this->enrolledStudent(9, [9, 10], 'punjab')));
        $this->assertSame(['G9 both', 'G9 federal'], $this->visibleTitles($this->enrolledStudent(9, [9, 10], 'federal')));
    }

    public function test_a_student_without_a_class_sees_nothing_and_is_told_why(): void
    {
        $experiment = $this->experiment(9);
        $school = School::create([
            'name' => 'No Class School', 'district' => 'Gujrat', 'board' => 'punjab', 'licensed_grades' => [9, 10],
            'licence_start' => now(), 'licence_end' => now()->addYear(), 'student_cap' => 10,
        ]);
        $student = User::factory()->create(['school_id' => $school->id]);
        StudentProfile::create(['user_id' => $student->id]);
        $this->passTier1($student);

        $this->assertSame([], $this->visibleTitles($student));

        $this->postJson("/api/experiments/{$experiment->id}/start", ['presentation_mode' => 'standard'])
            ->assertForbidden()
            ->assertJsonPath('code', 'no_class');
    }

    public function test_a_student_without_a_school_sees_nothing(): void
    {
        $this->experiment(9);
        $student = User::factory()->create(['school_id' => null]);
        StudentProfile::create(['user_id' => $student->id]);

        $this->assertSame([], $this->visibleTitles($student));
    }

    public function test_a_typed_url_cannot_open_an_unlicensed_experiment(): void
    {
        $other = $this->experiment(11);
        $wrongBoard = $this->experiment(9, 'federal');
        $student = $this->enrolledStudent(9, [9, 10, 11, 12], 'punjab');
        $this->passTier1($student);
        Sanctum::actingAs($student);

        foreach ([$other, $wrongBoard] as $experiment) {
            $this->getJson("/api/experiments/{$experiment->id}")->assertNotFound();
            $this->postJson("/api/experiments/{$experiment->id}/start", ['presentation_mode' => 'standard'])
                ->assertForbidden()
                ->assertJsonPath('code', 'not_licensed');
        }
    }

    public function test_a_licensed_experiment_still_needs_safety_tier_1(): void
    {
        $experiment = $this->experiment(9);
        Sanctum::actingAs($this->enrolledStudent(9));

        $this->postJson("/api/experiments/{$experiment->id}/start", ['presentation_mode' => 'standard'])
            ->assertForbidden()
            ->assertJsonPath('code', 'safety_tier_1_required');
    }

    public function test_the_administrator_sets_a_schools_board_and_classes(): void
    {
        $this->actingAs(User::factory()->administrator()->create());

        Livewire::test(ManageSchools::class)
            ->callAction('create', data: [
                'name' => 'City College', 'district' => 'Lahore', 'board' => 'federal',
                'licensed_grades' => [11, 12],
                'licence_start' => now()->toDateString(), 'licence_end' => now()->addYear()->toDateString(),
                'student_cap' => 300,
            ])
            ->assertHasNoActionErrors();

        $college = School::where('name', 'City College')->firstOrFail();
        $this->assertSame('federal', $college->board);
        $this->assertEqualsCanonicalizing([11, 12], array_map('intval', $college->licensed_grades));
        $this->assertTrue($college->licensesGrade(12));
        $this->assertFalse($college->licensesGrade(10));
    }

    public function test_a_school_must_be_licensed_for_at_least_one_class(): void
    {
        $this->actingAs(User::factory()->administrator()->create());

        Livewire::test(ManageSchools::class)
            ->callAction('create', data: [
                'name' => 'Empty Licence', 'district' => 'Lahore', 'board' => 'punjab', 'licensed_grades' => [],
                'licence_start' => now()->toDateString(), 'licence_end' => now()->addYear()->toDateString(),
            ])
            ->assertHasActionErrors(['licensed_grades' => 'required']);
    }
}
