<?php

namespace App\Http\Controllers\Api;

use App\Models\FcmToken;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * M7 – FR-09.6 and FR-09.7: student notification inbox.
 *
 * SR-08 structural enforcement: every route in this controller uses
 * $request->user()->id — there is no parameter a client can alter to
 * read another student's notifications.
 */
class NotificationController extends Controller
{
    // ── Student inbox ──────────────────────────────────────────────────────────

    /**
     * FR-09.6: return the authenticated student's notifications, unread first.
     *
     * Response shape:
     *   { notifications: [...], unread_count: int }
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $rows = Notification::where('user_id', $userId)
            ->orderByRaw('read_at IS NOT NULL')   // unread first
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'notification_type', 'subject', 'body', 'created_at', 'read_at']);

        $unread = Notification::where('user_id', $userId)->whereNull('read_at')->count();

        return response()->json([
            'notifications' => $rows,
            'unread_count'  => $unread,
        ]);
    }

    /**
     * Mark all of the authenticated student's notifications as read.
     * Idempotent — safe to call multiple times.
     */
    public function readAll(Request $request): JsonResponse
    {
        Notification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    // ── Push token registration ────────────────────────────────────────────────

    /**
     * Store or refresh an FCM web push token for this device (FR-09.6).
     *
     * The token is used by the push dispatch job when background delivery
     * is configured. Storing it here does not require a live FCM project —
     * the delivery job checks config('services.fcm.key') before calling
     * the FCM API and silently skips when the key is absent.
     */
    public function registerPushToken(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_token' => ['required', 'string', 'max:512'],
            'platform'     => ['required', 'in:android,ios,web'],
        ]);

        FcmToken::updateOrCreate(
            ['device_token' => $data['device_token']],
            ['user_id' => $request->user()->id, 'platform' => $data['platform']],
        );

        return response()->json(['ok' => true]);
    }
}
