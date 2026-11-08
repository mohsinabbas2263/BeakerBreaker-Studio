<?php

namespace Tests\Feature;

use App\Filament\Resources\UserResource\Pages\ManageUsers;
use App\Models\Experiment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * wf_S16 to wf_S18. The administrator panel is the only place accounts are
 * created (FR-01.6), so if it does not load, no school can be onboarded.
 */
class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public static function panelPages(): array
    {
        return [
            'dashboard' => ['/admin'],
            'users' => ['/admin/users'],
            'schools' => ['/admin/schools'],
            'classes' => ['/admin/school-classes'],
            'experiments' => ['/admin/experiments'],
            'certificates' => ['/admin/certificates'],
            'audit log' => ['/admin/audit-logs'],
        ];
    }

    #[DataProvider('panelPages')]
    public function test_every_panel_page_loads_for_an_administrator(string $uri): void
    {
        $admin = User::factory()->administrator()->create(['display_name' => 'Head Admin']);

        $this->actingAs($admin)
            ->get($uri)
            ->assertOk()
            ->assertSee('Head Admin');
    }

    public function test_an_experiment_edit_page_loads(): void
    {
        $admin = User::factory()->administrator()->create();
        $experiment = Experiment::factory()->create();

        $this->actingAs($admin)
            ->get("/admin/experiments/{$experiment->id}/edit")
            ->assertOk();
    }

    /** SR-04: the panel is for administrators alone. */
    public function test_a_teacher_or_student_is_refused(): void
    {
        $this->actingAs(User::factory()->teacher()->create())->get('/admin')->assertForbidden();
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    }

    /** FR-10.1: a student created in the panel lands in the class chosen. */
    public function test_creating_a_student_puts_them_in_the_chosen_class(): void
    {
        $admin = User::factory()->administrator()->create();
        $school = School::create([
            'name' => 'Pilot School', 'district' => 'Gujrat',
            'licence_start' => now(), 'licence_end' => now()->addYear(), 'student_cap' => 100,
        ]);
        $class = SchoolClass::factory()->create(['school_id' => $school->id]);
        $this->actingAs($admin);

        Livewire::test(ManageUsers::class)
            ->callAction('create', data: [
                'school_id' => $school->id,
                'role' => 'student',
                'display_name' => 'Amna Pilot',
                'email' => 'amna@pilot.test',
                'password' => 'first-password',
                'status' => 'active',
                'class_id' => $class->id,
            ])
            ->assertHasNoActionErrors();

        $student = User::where('email', 'amna@pilot.test')->firstOrFail();
        $this->assertSame($class->id, $student->profile->class_id);
        $this->assertTrue(Hash::check('first-password', $student->password));
        $this->assertDatabaseHas('audit_logs', ['action_type' => 'user.created', 'target_reference' => "user:{$student->id}"]);
    }

    public function test_creating_a_teacher_makes_no_student_profile(): void
    {
        $this->actingAs(User::factory()->administrator()->create());

        Livewire::test(ManageUsers::class)
            ->callAction('create', data: [
                'role' => 'teacher',
                'display_name' => 'Sir Kamran',
                'email' => 'kamran@pilot.test',
                'password' => 'first-password',
                'status' => 'active',
            ])
            ->assertHasNoActionErrors();

        $this->assertNull(User::where('email', 'kamran@pilot.test')->firstOrFail()->profile);
    }

    public function test_editing_a_student_moves_them_to_another_class(): void
    {
        $this->actingAs(User::factory()->administrator()->create());
        $from = SchoolClass::factory()->create();
        $to = SchoolClass::factory()->create();
        $student = User::factory()->create();
        StudentProfile::create(['user_id' => $student->id, 'class_id' => $from->id]);

        Livewire::test(ManageUsers::class)
            ->callTableAction('edit', $student, data: ['class_id' => $to->id])
            ->assertHasNoTableActionErrors();

        $this->assertSame($to->id, $student->fresh()->profile->class_id);
        $this->assertDatabaseHas('audit_logs', ['action_type' => 'user.updated', 'target_reference' => "user:{$student->id}"]);
    }

    public function test_a_suspended_administrator_is_refused(): void
    {
        $this->actingAs(User::factory()->administrator()->suspended()->create())
            ->get('/admin')
            ->assertForbidden();
    }
}
