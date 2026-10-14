<?php

namespace App\Http\Controllers\Api;

use App\Models\Reward;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing('profile.schoolClass', 'certificate');

        $badges = Reward::where('user_id', $user->id)
            ->where('reward_type', 'badge')
            ->orderByDesc('awarded_at')
            ->pluck('badge_code');

        $completed = $user->experimentSessions()
            ->whereNotNull('completed_at')
            ->distinct()
            ->count('experiment_id');

        return response()->json([
            'display_name' => $user->display_name,
            'avatar_key' => $user->avatar_key,
            'class' => $user->profile?->schoolClass?->title,
            'total_points' => $user->profile?->total_points ?? 0,
            'rank_level' => $user->profile?->rank_level ?? 1,
            'rank_name' => $user->profile?->rankName(),
            'streak_days' => $user->profile?->current_streak_days ?? 0,
            'experiments_completed' => $completed,
            'safety_tier_passed' => $user->highestSafetyTierPassed(),
            'certificate' => $user->certificate,
            'badges' => $badges,
        ]);
    }

    /**
     * A student may change their display name and avatar, and nothing else.
     * Points, rank, class and role are not in this list by design (FR-06.4, SR-04).
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'display_name' => ['sometimes', 'string', 'min:2', 'max:60'],
            'avatar_key' => ['sometimes', 'string', 'max:60'],
        ]);

        $request->user()->update($validated);

        return response()->json(['message' => 'Profile updated.']);
    }
}
