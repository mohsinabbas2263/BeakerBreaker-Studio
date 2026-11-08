<?php

namespace Tests\Feature;

use App\Enums\AssignmentState;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\Experiment;
use App\Models\ExperimentStep;
use App\Models\Notification;
use App\Models\SafetyAttempt;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * M3: FR-09.1 to FR-09.7, SR-04, and the teacher's view of certificates (FR-02.9).
 *
 * Done when (docs/build-order.md): a teacher request touching a class they are not
 * assigned to is rejected on the server, and an assignment moves to overdue with no
 * teacher action.
 */
class TeacherFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $teacher;
    private SchoolClass $class;
    private SchoolClass $otherClass;
    private User $amna;
    private User $bilal;
    private User $outsider;
    private Experiment $experiment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'name' => 'Test School', 'district' => 'Gujrat', 'board' => 'punjab',
            'licensed_grades' => [9, 10], 'licence_start' => now()->subMonth(),
            'licence_end' => now()->addYear(), 'student_cap' => 100,
        ]);

        $this->class = $this->makeClass('Class 9-A');
        $this->otherClass = $this->makeClass('Class 9-B');

        $this->teacher = User::factory()->create(['role' => UserRole::Teacher, 'school_id' => $this->school->id]);
        $this->class->teachers()->attach($this->teacher->id);

        $this->amna = $this->student('Amna', $this->class);
        $this->bilal = $this->student('Bilal', $this->class);
        $this->outsider = $this->student('Zara', $this->otherClass);

        $this->experiment = Experiment::factory()->create(['grade_level' => 9, 'board' => 'both', 'title' => 'Litmus Test']);
        ExperimentStep::create([
            'experiment_id' => $this->experiment->id, 'step_order' => 1, 'title' => 'Only step',
            'instruction_text' => 'Do it.', 'preauthored_guidance' => 'Like this.',
        ]);
    }

    private function makeClass(string $title): SchoolClass
    {
        return SchoolClass::create([
            'school_id' => $this->school->id, 'title' => $title, 'grade_level' => 9, 'academic_year' => '2026-2027',
        ]);
    }

    private function student(string $name, SchoolClass $class): User
    {
        $user = User::factory()->create(['display_name' => $name, 'school_id' => $this->school->id]);
        StudentProfile::create(['user_id' => $user->id, 'class_id' => $class->id, 'last_active_on' => now()->toDateString()]);

        return $user;
    }

    private function passTier(User $student, int $tier, int $score = 90): void
    {
        SafetyAttempt::create([
            'user_id' => $student->id, 'tier_number' => $tier, 'score' => $score, 'passed' => $score >= 80,
            'question_ids' => [], 'submitted_answers' => [], 'attempted_at' => now(),
        ]);
    }

    private function assignToClass(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        Sanctum::actingAs($this->teacher);

        return $this->postJson('/api/teacher/tasks', array_merge([
            'class_id' => $this->class->id,
            'experiment_id' => $this->experiment->id,
            'due_at' => now()->addDays(3)->toIso8601String(),
            'target' => 'class',
        ], $overrides));
    }

    // ---- FR-09.1 / SR-04: only assigned classes --------------------------------

    public function test_a_teacher_sees_only_the_classes_they_teach(): void
    {
        Sanctum::actingAs($this->teacher);

        $this->getJson('/api/teacher/classes')->assertOk()
            ->assertJsonCount(1, 'classes')
            ->assertJsonPath('classes.0.title', 'Class 9-A')
            ->assertJsonPath('classes.0.student_count', 2);
    }

    public function test_every_class_route_refuses_a_class_the_teacher_does_not_teach_and_logs_it(): void
    {
        Sanctum::actingAs($this->teacher);
        $id = $this->otherClass->id;

        foreach (["/api/teacher/classes/{$id}/students", "/api/teacher/classes/{$id}/safety", "/api/teacher/classes/{$id}/experiments"] as $url) {
            $this->getJson($url)->assertForbidden()->assertJsonPath('code', 'not_assigned');
        }

        $this->assertSame(3, AuditLog::where('action_type', 'teacher.refused')->where('actor_user_id', $this->teacher->id)->count());
    }

    public function test_a_student_cannot_reach_teacher_routes(): void
    {
        Sanctum::actingAs($this->amna);

        $this->getJson('/api/teacher/classes')->assertForbidden();
        $this->getJson("/api/teacher/classes/{$this->class->id}/students")->assertForbidden();
        $this->postJson('/api/teacher/tasks', [])->assertForbidden();
    }

    // ---- FR-09.2: class monitor ----------------------------------------------

    public function test_the_class_monitor_shows_points_experiments_certification_and_last_activity(): void
    {
        $this->amna->profile->update(['total_points' => 150]);
        $this->amna->experimentSessions()->create([
            'experiment_id' => $this->experiment->id, 'presentation_mode' => 'standard',
            'started_at' => now(), 'completed_at' => now(), 'steps_completed' => 1, 'completed_step_ids' => [],
        ]);
        foreach ([1, 2, 3, 4] as $t) {
            $this->passTier($this->amna, $t);
        }
        Certificate::create([
            'user_id' => $this->amna->id, 'certificate_reference' => 'CERT-TEST-1',
            'tier1_score' => 90, 'tier2_score' => 90, 'tier3_score' => 90, 'tier4_score' => 90, 'issued_on' => now(),
        ]);

        Sanctum::actingAs($this->teacher);
        $rows = collect($this->getJson("/api/teacher/classes/{$this->class->id}/students")->assertOk()->json('students'))
            ->keyBy('display_name');

        $this->assertSame(150, $rows['Amna']['total_points']);
        $this->assertSame(1, $rows['Amna']['experiments_completed']);
        $this->assertSame(4, $rows['Amna']['safety_tier_passed']);
        $this->assertTrue($rows['Amna']['has_certificate']);
        $this->assertSame(now()->toDateString(), $rows['Amna']['last_active_on']);
        $this->assertFalse($rows['Bilal']['has_certificate']);
        $this->assertArrayNotHasKey('Zara', $rows->all());
    }

    // ---- FR-02.9 / UC-26: safety review ---------------------------------------

    public function test_the_safety_review_shows_each_tier_and_the_certificate(): void
    {
        $this->passTier($this->bilal, 1, 70);
        $this->passTier($this->bilal, 1, 85);
        $this->passTier($this->bilal, 2, 60);

        Sanctum::actingAs($this->teacher);
        $res = $this->getJson("/api/teacher/classes/{$this->class->id}/safety")->assertOk();
        $bilal = collect($res->json('students'))->firstWhere('display_name', 'Bilal');

        $this->assertTrue($bilal['tier1_passed']);
        $this->assertSame(85, $bilal['tier1_score']);
        $this->assertFalse($bilal['tier2_passed']);
        $this->assertSame(60, $bilal['tier2_score']);
        $this->assertNull($bilal['tier3_score']);
        $this->assertNull($bilal['certificate']);
        $res->assertJsonPath('summary.total', 2)->assertJsonPath('summary.certified', 0);
    }

    // ---- FR-09.4 and FR-09.6: assigning a task --------------------------------

    public function test_assigning_to_the_class_creates_one_assignment_and_one_notification_per_student(): void
    {
        $this->assignToClass(['instructions' => '<b>Read page 12 first</b>'])->assertCreated()
            ->assertJsonPath('task.assigned', 2);

        $task = Task::sole();
        $this->assertSame('Read page 12 first', $task->instructions);
        $this->assertEqualsCanonicalizing(
            [$this->amna->id, $this->bilal->id],
            $task->assignments()->pluck('user_id')->all(),
        );
        $this->assertTrue($task->assignments->every(fn ($a) => $a->state === AssignmentState::Assigned));

        $this->assertSame(1, Notification::where('user_id', $this->amna->id)->where('notification_type', 'task')->count());
        $this->assertSame(1, Notification::where('user_id', $this->bilal->id)->where('notification_type', 'task')->count());
        $this->assertSame(0, Notification::where('user_id', $this->outsider->id)->count());
        $this->assertSame(1, AuditLog::where('action_type', 'task.created')->count());
    }

    public function test_the_notification_states_the_due_time_in_the_school_time_zone(): void
    {
        // 04:00 UTC is 09:00 in Pakistan.
        $this->assignToClass(['due_at' => now()->addDays(2)->setTime(4, 0)->toIso8601String()])->assertCreated();

        $body = Notification::where('user_id', $this->amna->id)->value('body');
        $this->assertStringContainsString('9:00 am', $body);
    }

    public function test_a_task_can_target_one_student_or_a_group(): void
    {
        $this->assignToClass(['target' => 'students', 'student_ids' => [$this->bilal->id]])->assertCreated();

        $this->assertSame([$this->bilal->id], TaskAssignment::pluck('user_id')->all());
    }

    public function test_a_due_date_that_is_not_in_the_future_is_rejected(): void
    {
        $this->assignToClass(['due_at' => now()->subMinute()->toIso8601String()])
            ->assertStatus(422)->assertJsonValidationErrors('due_at');

        $this->assertSame(0, Task::count());
    }

    public function test_a_student_outside_the_class_cannot_be_targeted(): void
    {
        $this->assignToClass(['target' => 'students', 'student_ids' => [$this->amna->id, $this->outsider->id]])
            ->assertForbidden()->assertJsonPath('code', 'not_assigned');

        $this->assertSame(0, Task::count());
        $this->assertSame(0, Notification::count());
        $this->assertSame(1, AuditLog::where('action_type', 'teacher.refused')->count());
    }

    public function test_a_task_cannot_be_set_for_a_class_the_teacher_does_not_teach(): void
    {
        $this->assignToClass(['class_id' => $this->otherClass->id])->assertForbidden();

        $this->assertSame(0, Task::count());
    }

    public function test_an_experiment_outside_the_class_licence_cannot_be_assigned(): void
    {
        $class11 = Experiment::factory()->create(['grade_level' => 11, 'board' => 'both']);
        $federal = Experiment::factory()->create(['grade_level' => 9, 'board' => 'federal']);

        $this->assignToClass(['experiment_id' => $class11->id])->assertStatus(422)->assertJsonValidationErrors('experiment_id');
        $this->assignToClass(['experiment_id' => $federal->id])->assertStatus(422)->assertJsonValidationErrors('experiment_id');

        $titles = collect($this->getJson("/api/teacher/classes/{$this->class->id}/experiments")->assertOk()->json('experiments'))
            ->pluck('id');
        $this->assertTrue($titles->contains($this->experiment->id));
        $this->assertFalse($titles->contains($class11->id));
        $this->assertFalse($titles->contains($federal->id));
    }

    // ---- FR-09.5: states change with no teacher action -------------------------

    public function test_starting_and_finishing_the_experiment_moves_the_task_on_by_itself(): void
    {
        $this->assignToClass()->assertCreated();
        $this->passTier($this->amna, 1);

        Sanctum::actingAs($this->amna);
        $session = $this->postJson("/api/experiments/{$this->experiment->id}/start", ['presentation_mode' => 'standard'])
            ->assertCreated()->json('session');

        $this->assertSame(AssignmentState::InProgress, TaskAssignment::where('user_id', $this->amna->id)->sole()->state);
        $this->assertSame(AssignmentState::Assigned, TaskAssignment::where('user_id', $this->bilal->id)->sole()->state);

        $this->postJson("/api/sessions/{$session['id']}/steps", ['step_id' => $this->experiment->steps()->value('id')])
            ->assertOk()->assertJsonPath('quiz_unlocked', true);

        $this->assertSame(AssignmentState::Completed, TaskAssignment::where('user_id', $this->amna->id)->sole()->state);
    }

    public function test_an_assignment_becomes_overdue_with_no_teacher_action_and_can_still_be_done(): void
    {
        $this->assignToClass()->assertCreated();
        Task::query()->update(['due_at' => now()->subHour()]);

        $this->artisan('assignments:mark-overdue')->assertSuccessful();

        $this->assertTrue(TaskAssignment::all()->every(fn ($a) => $a->state === AssignmentState::Overdue));

        $this->passTier($this->bilal, 1);
        Sanctum::actingAs($this->bilal);
        $this->postJson("/api/experiments/{$this->experiment->id}/start", ['presentation_mode' => 'standard'])->assertCreated();

        $this->assertSame(AssignmentState::InProgress, TaskAssignment::where('user_id', $this->bilal->id)->sole()->state);
    }

    public function test_the_task_list_and_detail_show_each_state(): void
    {
        $this->assignToClass()->assertCreated();
        TaskAssignment::where('user_id', $this->amna->id)->sole()->transitionTo(AssignmentState::InProgress);
        $task = Task::sole();

        Sanctum::actingAs($this->teacher);
        $this->getJson('/api/teacher/tasks')->assertOk()
            ->assertJsonPath('tasks.0.total', 2)
            ->assertJsonPath('tasks.0.assigned', 1)
            ->assertJsonPath('tasks.0.in_progress', 1)
            ->assertJsonPath('tasks.0.experiment_title', 'Litmus Test');

        $states = collect($this->getJson("/api/teacher/tasks/{$task->id}")->assertOk()->json('students'))
            ->pluck('state', 'display_name');
        $this->assertSame(['Amna' => 'in_progress', 'Bilal' => 'assigned'], $states->all());
    }

    public function test_another_teacher_cannot_open_the_task(): void
    {
        $this->assignToClass()->assertCreated();
        $stranger = User::factory()->create(['role' => UserRole::Teacher]);

        Sanctum::actingAs($stranger);
        $this->getJson('/api/teacher/tasks/'.Task::sole()->id)->assertForbidden();
        $this->getJson('/api/teacher/tasks')->assertOk()->assertJsonCount(0, 'tasks');
    }

    // ---- FR-09.7: broadcast ----------------------------------------------------

    public function test_a_safety_alert_can_be_broadcast_and_appears_in_the_sent_history(): void
    {
        Sanctum::actingAs($this->teacher);

        $this->postJson('/api/teacher/broadcast', [
            'class_id' => $this->class->id, 'notification_type' => 'safety_alert',
            'subject' => 'Goggles', 'body' => 'Wear goggles in every experiment.',
        ])->assertOk()->assertJsonPath('sent', 2);

        $this->getJson('/api/teacher/notifications')->assertOk()
            ->assertJsonPath('notifications.0.notification_type', 'safety_alert')
            ->assertJsonPath('notifications.0.class_title', 'Class 9-A')
            ->assertJsonPath('notifications.0.recipients', 2);
    }

    public function test_the_500_character_limit_counts_the_text_after_markup_is_removed(): void
    {
        Sanctum::actingAs($this->teacher);
        $text = str_repeat('a', 490);

        $this->postJson('/api/teacher/broadcast', [
            'class_id' => $this->class->id, 'notification_type' => 'announcement',
            'subject' => 'Note', 'body' => "<p><strong>{$text}</strong></p>",
        ])->assertOk();

        $this->assertSame($text, Notification::where('user_id', $this->amna->id)->value('body'));
    }

    // ---- wf_S10: dashboard -----------------------------------------------------

    public function test_the_dashboard_totals_the_classes_and_flags_students_needing_attention(): void
    {
        $this->amna->profile->update(['total_points' => 100, 'last_active_on' => now()->subDays(10)->toDateString()]);
        foreach ([1, 2, 3, 4] as $t) {
            $this->passTier($this->bilal, $t);
        }
        Certificate::create([
            'user_id' => $this->bilal->id, 'certificate_reference' => 'CERT-TEST-2',
            'tier1_score' => 90, 'tier2_score' => 90, 'tier3_score' => 90, 'tier4_score' => 90, 'issued_on' => now(),
        ]);

        Sanctum::actingAs($this->teacher);
        $res = $this->getJson('/api/teacher/dashboard')->assertOk()
            ->assertJsonPath('stats.students', 2)
            ->assertJsonPath('stats.avg_xp', 50)
            ->assertJsonPath('stats.certified', 1);

        $attention = collect($res->json('attention'))->keyBy('display_name');
        $this->assertContains('Inactive for 10 days', $attention['Amna']['reasons']);
        $this->assertContains('Safety Tier 1 not passed', $attention['Amna']['reasons']);
        $this->assertArrayNotHasKey('Bilal', $attention->all());
        $this->assertArrayNotHasKey('Zara', $attention->all());
    }
}
