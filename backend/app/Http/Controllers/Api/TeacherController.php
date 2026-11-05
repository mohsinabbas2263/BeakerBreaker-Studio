<?php

namespace App\Http\Controllers\Api;

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
    /**
     * FR-09.7: broadcast a notification to every student in a class.
     *
     * The body is stripped of HTML markup and capped at 500 characters.
     * Only the teacher formally assigned to the class may call this.
     */
    public function broadcast(Request $request): JsonResponse
    {
        $data = $request->validate([
            'class_id'          => ['required', 'integer', 'exists:classes,id'],
            'notification_type' => ['required', 'string', 'in:announcement,reminder,task'],
            'subject'           => ['required', 'string', 'max:200'],
            'body'              => ['required', 'string', 'max:500'],
        ]);

        $class = SchoolClass::findOrFail($data['class_id']);

        // SR-04: teacher must be formally assigned to this class.
        if (! $class->teachers()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['code' => 'not_assigned'], 403);
        }

        $body = strip_tags($data['body']);

        $students = $class->studentProfiles()->with('user')->get();
        $count = 0;

        foreach ($students as $profile) {
            Notification::create([
                'user_id'           => $profile->user_id,
                'notification_type' => $data['notification_type'],
                'subject'           => $data['subject'],
                'body'              => $body,
                'created_at'        => now(),
            ]);
            $count++;
        }

        return response()->json(['sent' => $count]);
    }
}
