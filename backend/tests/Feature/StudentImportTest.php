<?php

namespace Tests\Feature;

use App\Filament\Pages\ImportStudents;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use App\Services\StudentImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * UC-18, FR-10.2 and FR-10.3. M5 is done when "a bulk file with one bad row
 * creates zero accounts and names that row" (docs/build-order.md).
 */
class StudentImportTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        $this->school = School::create([
            'name' => 'Pilot School', 'district' => 'Gujrat', 'board' => 'punjab', 'licensed_grades' => [9, 10],
            'licence_start' => now(), 'licence_end' => now()->addYear(), 'student_cap' => 0,
        ]);
        SchoolClass::create(['school_id' => $this->school->id, 'title' => 'Class 9-A', 'grade_level' => 9, 'academic_year' => '2026-2027']);
        SchoolClass::create(['school_id' => $this->school->id, 'title' => 'Class 10-B', 'grade_level' => 10, 'academic_year' => '2026-2027']);
    }

    private function import(string $csv): array
    {
        return app(StudentImportService::class)->import($this->school, $csv, null);
    }

    private function students(): int
    {
        return User::where('role', 'student')->count();
    }

    public function test_a_good_file_creates_every_student_in_their_class_and_welcomes_them(): void
    {
        $result = $this->import("name,email,class\nAyesha Khan,Ayesha@Pilot.test,Class 9-A\nBilal Ahmed,bilal@pilot.test,class 10-b\n");

        $this->assertSame(['created' => 2, 'errors' => []], $result);
        $ayesha = User::where('email', 'ayesha@pilot.test')->firstOrFail();
        $this->assertSame($this->school->id, $ayesha->school_id);
        $this->assertSame('Class 9-A', $ayesha->profile->schoolClass->title);
        $this->assertSame('Class 10-B', User::where('email', 'bilal@pilot.test')->first()->profile->schoolClass->title);
        Notification::assertSentTo($ayesha, WelcomeNotification::class);
        // FR-10.8: one entry per account and one for the file.
        $this->assertDatabaseHas('audit_logs', ['action_type' => 'user.created', 'target_reference' => "user:{$ayesha->id}"]);
        $this->assertDatabaseHas('audit_logs', ['action_type' => 'users.imported', 'target_reference' => "school:{$this->school->id}"]);
    }

    /** FR-10.3, and M5's "done when". */
    public function test_one_bad_row_creates_no_account_and_names_that_row(): void
    {
        $result = $this->import("name,email,class\nAyesha Khan,ayesha@pilot.test,Class 9-A\nBilal Ahmed,not-an-email,Class 9-A\nHina,hina@pilot.test,Class 9-A\n");

        $this->assertSame(0, $result['created']);
        $this->assertSame(0, $this->students());
        $this->assertCount(1, $result['errors']);
        $this->assertSame(3, $result['errors'][0]['line']);
        $this->assertStringContainsString('not an email address', $result['errors'][0]['messages'][0]);
        Notification::assertNothingSent();
    }

    public function test_every_failing_row_is_reported_with_every_reason(): void
    {
        User::factory()->create(['email' => 'taken@pilot.test']);

        $result = $this->import(implode("\n", [
            'email,class,name',                                   // columns in any order
            'taken@pilot.test,Class 9-A,Taken',
            'dup@pilot.test,Class 9-A,First',
            'dup@pilot.test,Class 9-A,Second',
            ',Class 12-Z,',
        ]));

        $this->assertSame(0, $result['created']);
        $this->assertDatabaseMissing('users', ['email' => 'dup@pilot.test']);
        $byLine = collect($result['errors'])->keyBy('line');
        $this->assertSame([2, 4, 5], $byLine->keys()->all());
        $this->assertStringContainsString('already has an account', $byLine[2]['messages'][0]);
        $this->assertStringContainsString('also on line 3', $byLine[4]['messages'][0]);
        $this->assertCount(3, $byLine[5]['messages']);          // no name, no email, no such class
    }

    public function test_a_class_of_another_school_is_refused(): void
    {
        $other = School::create([
            'name' => 'Other', 'district' => 'Lahore', 'board' => 'punjab', 'licensed_grades' => [9],
            'licence_start' => now(), 'licence_end' => now()->addYear(), 'student_cap' => 0,
        ]);
        SchoolClass::create(['school_id' => $other->id, 'title' => 'Class 9-Z', 'grade_level' => 9, 'academic_year' => '2026-2027']);

        $result = $this->import("name,email,class\nAyesha,ayesha@pilot.test,Class 9-Z\n");

        $this->assertSame(0, $result['created']);
        $this->assertStringContainsString('no class called', $result['errors'][0]['messages'][0]);
    }

    public function test_a_file_without_the_right_header_or_rows_is_refused(): void
    {
        $this->assertStringContainsString('Missing: class', $this->import("name,email\nA,a@pilot.test\n")['errors'][0]['messages'][0]);
        $this->assertStringContainsString('no students', $this->import("name,email,class\n")['errors'][0]['messages'][0]);
        $this->assertStringContainsString('empty', $this->import('')['errors'][0]['messages'][0]);
    }

    /** FR-10.2: at most 500 rows. */
    public function test_more_than_500_rows_is_refused(): void
    {
        $rows = ['name,email,class'];
        for ($i = 1; $i <= 501; $i++) {
            $rows[] = "Student {$i},s{$i}@pilot.test,Class 9-A";
        }

        $result = $this->import(implode("\n", $rows));

        $this->assertSame(0, $this->students());
        $this->assertStringContainsString('at most 500', $result['errors'][0]['messages'][0]);
    }

    /** FR-10.2: 500 rows within 60 seconds. Measured here on SQLite; check again on the server. */
    public function test_500_rows_import_within_60_seconds(): void
    {
        $rows = ["\xEF\xBB\xBFname;email;class"];                 // Excel: byte order mark, semicolons
        for ($i = 1; $i <= 500; $i++) {
            $rows[] = "Student {$i};s{$i}@pilot.test;Class 9-A";
        }

        $started = microtime(true);
        $result = $this->import(implode("\r\n", $rows));
        $seconds = microtime(true) - $started;

        $this->assertSame(500, $result['created'], json_encode($result['errors']));
        $this->assertLessThan(60, $seconds);
        fwrite(STDERR, sprintf("\n  FR-10.2: 500 students imported in %.1f s\n", $seconds));
    }

    public function test_the_import_page_reports_failing_rows(): void
    {
        $admin = User::factory()->administrator()->create(['school_id' => $this->school->id]);
        $this->actingAs($admin);

        $file = UploadedFile::fake()->createWithContent('students.csv', "name,email,class\nAyesha,bad,Class 9-A\n");

        Livewire::test(ImportStudents::class)
            ->set('data.file', $file)
            ->call('import')
            ->assertSet('created', null)
            ->assertSee('not an email address');

        $this->assertSame(0, $this->students());
    }

    public function test_the_import_page_creates_students_in_the_administrators_school(): void
    {
        $admin = User::factory()->administrator()->create(['school_id' => $this->school->id]);
        $this->actingAs($admin);

        $file = UploadedFile::fake()->createWithContent('students.csv', "name,email,class\nAyesha,ayesha@pilot.test,Class 9-A\n");

        Livewire::test(ImportStudents::class)
            ->set('data.file', $file)
            ->call('import')
            ->assertSet('created', 1);

        $this->assertSame($this->school->id, User::where('email', 'ayesha@pilot.test')->first()->school_id);
    }
}
