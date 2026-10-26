<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Notification;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M7 – FR-09.6 and FR-09.7: notification broadcast and student inbox.
 *
 * Done-when: broadcast creates one notification per student in the class,
 * markup is stripped, body is capped at 500 chars, and each student sees
 * only their own notifications.
 */
class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private User       $teacher;
    private SchoolClass $class;
    private User       $studentA;
    private User       $studentB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher  = User::factory()->create(['role' => UserRole::Teacher]);
        $this->class    = SchoolClass::factory()->create(['school_id' => null]);
        $this->class->teachers()->attach($this->teacher->id);

        $this->studentA = User::factory()->create(['role' => UserRole::Student]);
        $this->studentB = User::factory()->create(['role' => UserRole::Student]);

        StudentProfile::factory()->create([
            'user_id'  => $this->studentA->id,
            'class_id' => $this->class->id,
        ]);
        StudentProfile::factory()->create([
            'user_id'  => $this->studentB->id,
            'class_id' => $this->class->id,
        ]);
    }

    // ── FR-09.7 done-when: broadcast creates one notification per student ──────

    /**
     * THE done-when test for M7.
     *
     * A broadcast to a class of two creates exactly two notifications
     * (one per student), with markup stripped from the body.
     */
    public function test_broadcast_creates_one_notification_per_student_and_strips_markup(): void
    {
        $response = $this->actingAs($this->teacher)
            ->postJson('/api/teacher/broadcast', [
                'class_id'          => $this->class->id,
                'notification_type' => 'announcement',
                'subject'           => 'Lab tomorrow',
                'body'              => '<b>Bring your lab coat</b> and <em>goggles</em>!',
            ]);

        $response->assertOk()->assertJson(['sent' => 2]);

        // Both students received a notification.
        $this->assertDatabaseHas('notifications', [
            'user_id'           => $this->studentA->id,
            'notification_type' => 'announcement',
            'subject'           => 'Lab tomorrow',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id'           => $this->studentB->id,
            'notification_type' => 'announcement',
            'subject'           => 'Lab tomorrow',
        ]);

        // Markup is stripped from the body (FR-09.7).
        $notif = Notification::where('user_id', $this->studentA->id)->first();
        $this->assertStringNotContainsString('<b>', $notif->body);
        $this->assertStringNotContainsString('<em>', $notif->body);
        $this->assertStringContainsString('Bring your lab coat', $notif->body);
    }

    // ── Body cap at 500 chars ──────────────────────────────────────────────────

    public function test_broadcast_body_longer_than_500_chars_is_rejected(): void
    {
        $this->actingAs($this->teacher)
            ->postJson('/api/teacher/broadcast', [
                'class_id'          => $this->class->id,
                'notification_type' => 'announcement',
                'subject'           => 'Too long',
                'body'              => str_repeat('A', 501),
            ])
            ->assertUnprocessable();
    }

    // ── SR-04 guard: teacher not assigned to class ─────────────────────────────

    public function test_teacher_cannot_broadcast_to_unassigned_class(): void
    {
        $otherClass = SchoolClass::factory()->create(['school_id' => null]);

        $this->actingAs($this->teacher)
            ->postJson('/api/teacher/broadcast', [
                'class_id'          => $otherClass->id,
                'notification_type' => 'announcement',
                'subject'           => 'Nope',
                'body'              => 'Should not land.',
            ])
            ->assertForbidden();
    }

    // ── Student inbox (FR-09.6) ───────────────────────────────────────────────

    public function test_student_can_read_their_own_notifications(): void
    {
        Notification::create([
            'user_id'           => $this->studentA->id,
            'notification_type' => 'task',
            'subject'           => 'Lab due Friday',
            'body'              => 'Complete the titration experiment.',
            'created_at'        => now(),
        ]);

        $response = $this->actingAs($this->studentA)
            ->getJson('/api/notifications');

        $response->assertOk()
            ->assertJsonPath('notifications.0.subject', 'Lab due Friday')
            ->assertJsonPath('unread_count', 1);
    }

    // ── SR-08: student cannot read another student's notifications ────────────

    public function test_student_inbox_returns_only_own_notifications(): void
    {
        Notification::create([
            'user_id'           => $this->studentB->id,
            'notification_type' => 'reminder',
            'subject'           => 'Only for B',
            'body'              => 'Private to student B.',
            'created_at'        => now(),
        ]);

        $response = $this->actingAs($this->studentA)
            ->getJson('/api/notifications');

        $response->assertOk();

        // Student A must not see Student B's notification.
        $subjects = collect($response->json('notifications'))->pluck('subject');
        $this->assertNotContains('Only for B', $subjects->all());
        $this->assertSame(0, $response->json('unread_count'));
    }

    // ── Mark all read ─────────────────────────────────────────────────────────

    public function test_read_all_marks_all_notifications_read(): void
    {
        Notification::create([
            'user_id' => $this->studentA->id, 'notification_type' => 'task',
            'subject' => 'First',  'body' => '...', 'created_at' => now(),
        ]);
        Notification::create([
            'user_id' => $this->studentA->id, 'notification_type' => 'reminder',
            'subject' => 'Second', 'body' => '...', 'created_at' => now(),
        ]);

        $this->actingAs($this->studentA)
            ->patchJson('/api/notifications/read-all')
            ->assertOk();

        $this->assertSame(
            0,
            Notification::where('user_id', $this->studentA->id)->whereNull('read_at')->count()
        );
    }

    // ── Push token registration ────────────────────────────────────────────────

    public function test_student_can_register_push_token(): void
    {
        $this->actingAs($this->studentA)
            ->postJson('/api/push/register', [
                'device_token' => 'fake-fcm-token-abc123',
                'platform'     => 'web',
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('fcm_tokens', [
            'user_id'      => $this->studentA->id,
            'device_token' => 'fake-fcm-token-abc123',
            'platform'     => 'web',
        ]);
    }

    // ── Teacher role cannot access student inbox ───────────────────────────────

    public function test_teacher_cannot_access_student_notifications_endpoint(): void
    {
        $this->actingAs($this->teacher)
            ->getJson('/api/notifications')
            ->assertForbidden();
    }
}
