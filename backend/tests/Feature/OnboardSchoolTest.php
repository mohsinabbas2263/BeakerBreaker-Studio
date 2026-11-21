<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** The team onboards a school and its first administrator from the server (FR-01.6). */
class OnboardSchoolTest extends TestCase
{
    use RefreshDatabase;

    private function onboard(array $extra = []): int
    {
        return $this->artisan('school:onboard', array_merge([
            'name' => 'Government High School, Gujrat',
            '--district' => 'Gujrat',
            '--board' => 'punjab',
            '--classes' => '9,10',
            '--from' => '2026-12-01',
            '--until' => '2027-11-30',
            '--cap' => 400,
            '--admin-name' => 'Ms Ayesha',
            '--admin-email' => 'Admin@GHS.test',
        ], $extra))->run();
    }

    public function test_it_makes_the_school_its_licence_and_a_school_administrator_and_welcomes_them(): void
    {
        Notification::fake();

        $this->assertSame(0, $this->onboard());

        $school = School::where('name', 'Government High School, Gujrat')->firstOrFail();
        $this->assertSame('punjab', $school->board);
        $this->assertSame([9, 10], $school->licensed_grades);
        $this->assertSame('2027-11-30', $school->licence_end->toDateString());

        $admin = User::where('email', 'admin@ghs.test')->firstOrFail();
        $this->assertTrue($admin->isSchoolAdministrator());
        $this->assertSame($school->id, $admin->school_id);
        Notification::assertSentTo($admin, WelcomeNotification::class);
        $this->assertDatabaseHas('audit_logs', ['action_type' => 'school.onboarded', 'target_reference' => "school:{$school->id}"]);
        $this->assertDatabaseHas('audit_logs', ['action_type' => 'user.created', 'target_reference' => "user:{$admin->id}"]);
    }

    public function test_bad_input_makes_nothing(): void
    {
        $this->assertSame(1, $this->onboard(['--board' => 'sindh']));
        $this->assertSame(1, $this->onboard(['--classes' => '8']));
        $this->assertSame(1, $this->onboard(['--until' => '2026-01-01']));
        $this->assertSame(1, $this->onboard(['--admin-email' => 'not-an-email']));

        $this->assertSame(0, School::count());
        $this->assertSame(0, User::count());
    }

    public function test_an_email_already_in_use_is_refused(): void
    {
        User::factory()->create(['email' => 'admin@ghs.test']);

        $this->assertSame(1, $this->onboard());
        $this->assertSame(0, School::count());
    }

    public function test_it_can_make_a_team_administrator(): void
    {
        Notification::fake();

        $this->artisan('school:onboard', ['--team' => true, '--admin-name' => 'Rabail', '--admin-email' => 'team@bb.test'])
            ->assertSuccessful();

        $this->assertTrue(User::where('email', 'team@bb.test')->firstOrFail()->isTeamAdministrator());
        $this->assertSame(0, School::count());
    }

    public function test_print_link_shows_the_link_instead_of_emailing(): void
    {
        Notification::fake();

        $this->onboard(['--print-link' => true]);

        Notification::assertNothingSent();
    }
}
