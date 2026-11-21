<?php

namespace Tests\Feature;

use App\Filament\Pages\SchoolReports;
use App\Filament\Resources\AuditLogResource\Pages\ManageAuditLogs;
use App\Filament\Resources\CertificateResource\Pages\ManageCertificates;
use App\Filament\Resources\SchoolClassResource;
use App\Filament\Resources\SchoolClassResource\Pages\ManageSchoolClasses;
use App\Filament\Resources\UserResource\Pages\ManageUsers;
use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\Experiment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * UC-01 and UC-17: accounts are made by a *school* administrator, who reaches its
 * own school and nothing else (SR-04). A team administrator (no school) onboards
 * schools, sets licences and keeps the experiment library.
 */
class SchoolAdministratorTest extends TestCase
{
    use RefreshDatabase;

    private School $mine;

    private School $other;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mine = $this->school('Mine');
        $this->other = $this->school('Other');
        $this->admin = User::factory()->administrator()->create(['school_id' => $this->mine->id]);
    }

    private function school(string $name): School
    {
        return School::create([
            'name' => $name, 'district' => 'Gujrat', 'board' => 'punjab', 'licensed_grades' => [9, 10],
            'licence_start' => now()->subMonth(), 'licence_end' => now()->addYear(), 'student_cap' => 0,
        ]);
    }

    private function classIn(School $school, string $title = 'Class 9-A', int $grade = 9): SchoolClass
    {
        return SchoolClass::create(['school_id' => $school->id, 'title' => $title, 'grade_level' => $grade, 'academic_year' => '2026-2027']);
    }

    private function studentIn(School $school, ?SchoolClass $class = null, array $attrs = []): User
    {
        $student = User::factory()->create(['school_id' => $school->id] + $attrs);
        StudentProfile::create(['user_id' => $student->id, 'class_id' => $class?->id]);

        return $student;
    }

    public function test_a_school_administrator_lists_only_its_own_schools_accounts(): void
    {
        $own = $this->studentIn($this->mine);
        $foreign = $this->studentIn($this->other);
        $this->actingAs($this->admin);

        Livewire::test(ManageUsers::class)
            ->assertCanSeeTableRecords([$own])
            ->assertCanNotSeeTableRecords([$foreign]);
    }

    public function test_a_school_administrator_cannot_edit_or_delete_another_schools_account(): void
    {
        $foreign = $this->studentIn($this->other, null, ['display_name' => 'Untouched']);
        $this->actingAs($this->admin);

        // Filament resolves the record through the scoped query, so it is not found.
        try {
            Livewire::test(ManageUsers::class)->callTableAction('edit', $foreign, data: ['display_name' => 'Changed']);
        } catch (\Throwable) {
            // not found is the expected outcome
        }

        try {
            Livewire::test(ManageUsers::class)->callTableAction('delete', $foreign);
        } catch (\Throwable) {
        }

        $this->assertSame('Untouched', $foreign->fresh()->display_name);
        $this->assertNull($foreign->fresh()->deleted_at);
    }

    public function test_an_account_made_by_a_school_administrator_lands_in_its_school_whatever_was_sent(): void
    {
        Notification::fake();
        $class = $this->classIn($this->mine);
        $this->actingAs($this->admin);

        Livewire::test(ManageUsers::class)
            ->callAction('create', data: [
                'school_id' => $this->other->id,          // forged: the field is hidden from a school administrator
                'role' => 'student',
                'display_name' => 'Amna',
                'email' => 'amna@mine.test',
                'status' => 'active',
                'class_id' => $class->id,
            ])
            ->assertHasNoActionErrors();

        $amna = User::where('email', 'amna@mine.test')->firstOrFail();
        $this->assertSame($this->mine->id, $amna->school_id);
        $this->assertSame($class->id, $amna->profile->class_id);
    }

    public function test_a_student_cannot_be_put_in_another_schools_class(): void
    {
        $foreignClass = $this->classIn($this->other);
        $this->actingAs($this->admin);

        try {
            Livewire::test(ManageUsers::class)->callAction('create', data: [
                'role' => 'student', 'display_name' => 'Bilal', 'email' => 'bilal@mine.test',
                'status' => 'active', 'class_id' => $foreignClass->id,
            ]);
        } catch (\Throwable) {
        }

        $this->assertDatabaseMissing('users', ['email' => 'bilal@mine.test']);
    }

    /** UC-18: no password typed means a welcome email, and the administrator never knows the password. */
    public function test_an_account_made_without_a_password_is_sent_a_welcome_email(): void
    {
        Notification::fake();
        $this->actingAs($this->admin);

        Livewire::test(ManageUsers::class)
            ->callAction('create', data: [
                'role' => 'teacher', 'display_name' => 'Sir Kamran', 'email' => 'kamran@mine.test', 'status' => 'active',
            ])
            ->assertHasNoActionErrors();

        $kamran = User::where('email', 'kamran@mine.test')->firstOrFail();
        Notification::assertSentTo($kamran, WelcomeNotification::class);
    }

    public function test_an_account_made_with_a_password_gets_no_welcome_email(): void
    {
        Notification::fake();
        $this->actingAs($this->admin);

        Livewire::test(ManageUsers::class)
            ->callAction('create', data: [
                'role' => 'student', 'display_name' => 'No Email', 'email' => 'noemail@mine.test',
                'password' => 'typed-by-admin', 'status' => 'active',
            ])
            ->assertHasNoActionErrors();

        Notification::assertNothingSent();
    }

    public function test_the_welcome_email_links_to_the_portal_reset_page_with_a_working_token(): void
    {
        $user = $this->studentIn($this->mine);
        $mail = (new WelcomeNotification)->toMail($user);

        $this->assertStringContainsString('/reset-password?token=', $mail->actionUrl);
        parse_str((string) parse_url($mail->actionUrl, PHP_URL_QUERY), $query);

        $this->postJson('/api/reset-password', [
            'token' => $query['token'],
            'email' => $user->email,
            'password' => 'chosen-by-me',
            'password_confirmation' => 'chosen-by-me',
        ])->assertOk();
    }

    /** Licence and board are the team's: a school administrator cannot extend its own. */
    public function test_schools_and_the_experiment_library_are_for_the_team_only(): void
    {
        $experiment = Experiment::factory()->create();

        $this->actingAs($this->admin)->get('/admin/schools')->assertForbidden();
        $this->actingAs($this->admin)->get('/admin/experiments')->assertForbidden();
        $this->actingAs($this->admin)->get("/admin/experiments/{$experiment->id}/edit")->assertForbidden();
    }

    public function test_a_team_administrator_reaches_schools_and_the_library(): void
    {
        $team = User::factory()->administrator()->create();
        $this->assertTrue($team->isTeamAdministrator());

        $this->actingAs($team)->get('/admin/schools')->assertOk();
        $this->actingAs($team)->get('/admin/experiments')->assertOk();
    }

    public function test_the_pages_a_school_administrator_uses_all_load(): void
    {
        foreach (['/admin', '/admin/users', '/admin/school-classes', '/admin/certificates', '/admin/audit-logs', '/admin/import-students', '/admin/school-reports'] as $uri) {
            $this->actingAs($this->admin)->get($uri)->assertOk();
        }
    }

    public function test_classes_and_teachers_are_limited_to_the_school(): void
    {
        $own = $this->classIn($this->mine, 'Own class');
        $foreign = $this->classIn($this->other, 'Foreign class');
        $this->actingAs($this->admin);

        Livewire::test(ManageSchoolClasses::class)
            ->assertCanSeeTableRecords([$own])
            ->assertCanNotSeeTableRecords([$foreign]);
    }

    public function test_a_foreign_teacher_is_dropped_from_a_class(): void
    {
        $class = $this->classIn($this->mine);
        $ownTeacher = User::factory()->teacher()->create(['school_id' => $this->mine->id]);
        $foreignTeacher = User::factory()->teacher()->create(['school_id' => $this->other->id]);
        $class->teachers()->attach([$ownTeacher->id, $foreignTeacher->id]);

        SchoolClassResource::dropForeignTeachers($class);

        $this->assertSame([$ownTeacher->id], $class->teachers()->pluck('users.id')->all());
    }

    public function test_certificates_and_audit_entries_are_limited_to_the_school(): void
    {
        $own = Certificate::create(['user_id' => $this->studentIn($this->mine)->id, 'certificate_reference' => 'OWN-1',
            'tier1_score' => 90, 'tier2_score' => 90, 'tier3_score' => 90, 'tier4_score' => 90, 'issued_on' => now()]);
        $foreign = Certificate::create(['user_id' => $this->studentIn($this->other)->id, 'certificate_reference' => 'FOREIGN-1',
            'tier1_score' => 90, 'tier2_score' => 90, 'tier3_score' => 90, 'tier4_score' => 90, 'issued_on' => now()]);
        $this->actingAs($this->admin);

        Livewire::test(ManageCertificates::class)
            ->assertCanSeeTableRecords([$own])
            ->assertCanNotSeeTableRecords([$foreign]);

        $foreignAdmin = User::factory()->administrator()->create(['school_id' => $this->other->id]);
        $ownEntry = AuditLog::record($this->admin->id, 'user.updated', 'user:1');
        $foreignEntry = AuditLog::record($foreignAdmin->id, 'user.updated', 'user:2');

        Livewire::test(ManageAuditLogs::class)
            ->assertCanSeeTableRecords([$ownEntry])
            ->assertCanNotSeeTableRecords([$foreignEntry]);
    }

    /** FR-10.5, FR-10.7, FR-10.8: the register exports as CSV, its own school only, and the export is recorded. */
    public function test_the_certificate_register_exports_as_csv(): void
    {
        Certificate::create(['user_id' => $this->studentIn($this->mine, null, ['display_name' => '=HYPERLINK("x")'])->id,
            'certificate_reference' => 'OWN-1', 'tier1_score' => 81, 'tier2_score' => 82, 'tier3_score' => 83, 'tier4_score' => 84, 'issued_on' => now()]);
        Certificate::create(['user_id' => $this->studentIn($this->other)->id, 'certificate_reference' => 'FOREIGN-1',
            'tier1_score' => 90, 'tier2_score' => 90, 'tier3_score' => 90, 'tier4_score' => 90, 'issued_on' => now()]);
        $this->actingAs($this->admin);

        $started = microtime(true);
        $response = (new ManageCertificates)->exportCsv();
        ob_start();
        $response->sendContent();
        $csv = (string) ob_get_clean();

        $this->assertLessThan(10, microtime(true) - $started);       // FR-10.7
        $this->assertStringContainsString('OWN-1', $csv);
        $this->assertStringContainsString('81,82,83,84', $csv);
        $this->assertStringNotContainsString('FOREIGN-1', $csv);
        // A name that looks like a formula stays text in a spreadsheet.
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertDatabaseHas('audit_logs', ['action_type' => 'certificates.exported']);
    }

    public function test_the_school_report_is_limited_to_the_school_and_exports(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(SchoolReports::class)
            ->set('schoolId', $this->other->id)          // forged
            ->assertSet('schoolId', $this->mine->id)
            ->assertSee('Mine')
            ->callAction('exportCsv')
            ->assertFileDownloaded();

        $this->assertDatabaseHas('audit_logs', ['action_type' => 'report.exported', 'target_reference' => "school:{$this->mine->id}"]);
    }

    /** Deleting a school would turn its administrators into team administrators. */
    public function test_a_school_with_accounts_cannot_be_deleted(): void
    {
        $this->expectException(\LogicException::class);

        $this->mine->delete();
    }
}
