<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ScopesToTaughtClasses;
use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\SchoolClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * M3/M7 – teacher-facing API actions.
 *
 * FR-09.1: every method here checks class assignment before acting.
 * SR-04:   the check lives on the server; a crafted token cannot bypass it.
 */
class TeacherController extends Controller
{
    use ScopesToTaughtClasses;

    /**
     * FR-09.7: broadcast a notification to every student in a class.
     *
     * The body is stripped of HTML markup and capped at 500 characters.
     * Only the teacher formally assigned to the class may call this.
     */
    public function broadcast(Request $request): JsonResponse
    {
        // FR-09.7: markup is removed first, so the 500 character limit is on the
        // text the students will actually read.
        $request->merge([
            'subject' => is_string($request->input('subject')) ? trim(strip_tags($request->input('subject'))) : $request->input('subject'),
            'body' => is_string($request->input('body')) ? trim(strip_tags($request->input('body'))) : $request->input('body'),
        ]);

        $data = $request->validate([
            'class_id'          => ['required', 'integer', 'exists:classes,id'],
            // FR-09.7 names four types: task, reminder, safety alert, announcement.
            'notification_type' => ['required', 'string', 'in:task,reminder,safety_alert,announcement'],
            'subject'           => ['required', 'string', 'max:120'],
            'body'              => ['required', 'string', 'max:500'],
        ], [
            'body.max' => 'The message can be at most 500 characters.',
        ]);

        $class = SchoolClass::findOrFail($data['class_id']);
        $this->authorizeClass($request, $class);

        $studentIds = $class->studentProfiles()->pluck('user_id');
        $now = now();

        foreach ($studentIds as $userId) {
            Notification::create([
                'user_id'           => $userId,
                'notification_type' => $data['notification_type'],
                'subject'           => $data['subject'],
                'body'              => $data['body'],
                'created_at'        => $now,
            ]);
        }

        // The teacher's sent history is read back from this entry (sent()).
        AuditLog::record($request->user()->id, 'broadcast.sent', "class:{$class->id}", [
            'class_title' => $class->title,
            'notification_type' => $data['notification_type'],
            'subject' => $data['subject'],
            'body' => $data['body'],
            'recipients' => $studentIds->count(),
        ], $request->ip());

        return response()->json(['sent' => $studentIds->count()]);
    }

    /** The teacher's own broadcasts, newest first. */
    public function sent(Request $request): JsonResponse
    {
        $entries = AuditLog::where('actor_user_id', $request->user()->id)
            ->where('action_type', 'broadcast.sent')
            ->orderByDesc('occurred_at')->orderByDesc('id')
            ->limit(50)
            ->get();

        return response()->json([
            'notifications' => $entries->map(fn (AuditLog $e) => [
                'notification_type' => $e->context['notification_type'] ?? 'announcement',
                'subject' => $e->context['subject'] ?? '',
                'body' => $e->context['body'] ?? '',
                'class_title' => $e->context['class_title'] ?? null,
                'recipients' => $e->context['recipients'] ?? 0,
                'created_at' => $e->occurred_at?->toIso8601String(),
            ])->values(),
        ]);
    }
}
