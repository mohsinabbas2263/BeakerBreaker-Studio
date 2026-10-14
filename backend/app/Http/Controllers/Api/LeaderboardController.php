<?php

namespace App\Http\Controllers\Api;

use App\Models\StudentProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    /**
     * FR-07.1: display name, avatar, rank level and points. Nothing else.
     * SR-08: no score, safety history or readiness classification of another
     * student appears here, which is why the select list is explicit.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $classId = $user->profile?->class_id;

        if (! $classId) {
            return response()->json(['scope' => 'class', 'rows' => [], 'me' => null]);
        }

        $rows = StudentProfile::query()
            ->where('class_id', $classId)
            ->with(['user:id,display_name,avatar_key,status'])
            ->orderByDesc('total_points')
            ->orderBy('user_id')
            ->limit(50)
            ->get()
            ->filter(fn (StudentProfile $p) => $p->user && $p->user->status === 'active')
            ->values()
            ->map(fn (StudentProfile $p, int $i) => [
                'position' => $i + 1,
                'display_name' => $p->user->display_name,
                'avatar_key' => $p->user->avatar_key,
                'rank_level' => $p->rank_level,
                'rank_name' => $p->rankName(),
                'total_points' => $p->total_points,
                'is_me' => $p->user_id === $user->id,
            ]);

        return response()->json([
            'scope' => 'class',
            'rows' => $rows,
            'me' => $rows->firstWhere('is_me', true),
        ]);
    }
}
