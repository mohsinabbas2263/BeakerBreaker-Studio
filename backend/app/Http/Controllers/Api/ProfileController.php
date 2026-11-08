<?php

namespace App\Http\Controllers\Api;

use App\Models\AuditLog;
use App\Models\Reward;
use App\Models\User;
use App\Support\Badges;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            // FR-06.3: each badge with the name a student reads, not its code.
            'badges' => Badges::describe($badges),
        ]);
    }

    /**
     * A student may change their display name and avatar, and nothing else.
     * Points, rank, class and role are not in this list by design (FR-06.4, SR-04).
     */
    public function update(Request $request): JsonResponse
    {
        // Squeeze runs of spaces so "  Ali   Khan " is stored as "Ali Khan".
        if (is_string($request->input('display_name'))) {
            $request->merge([
                'display_name' => trim(preg_replace('/\s+/u', ' ', $request->input('display_name'))),
            ]);
        }

        $validated = $request->validate([
            // The name classmates see on the leaderboard (FR-07.1): letters in any
            // script (Urdu too), spaces, dots, apostrophes and hyphens. No links,
            // digits or symbols, so it stays a name.
            'display_name' => ['sometimes', 'string', 'min:2', 'max:40', "regex:/^[\\pL\\pM][\\pL\\pM .'-]*$/u"],
            'avatar_key' => ['sometimes', 'string', Rule::in(array_keys(User::AVATARS))],
        ], [
            'display_name.regex' => 'Use letters only, with spaces between words.',
        ]);

        $user = $request->user();
        $oldName = $user->display_name;
        $user->update($validated);

        // FR-10.8: a name others see is changed on the record, so a teacher or
        // administrator can tell who a renamed student is.
        if (isset($validated['display_name']) && $validated['display_name'] !== $oldName) {
            AuditLog::record($user->id, 'profile.name_changed', "user:{$user->id}", [
                'from' => $oldName, 'to' => $validated['display_name'],
            ], $request->ip());
        }

        return response()->json([
            'message' => 'Profile updated.',
            'display_name' => $user->display_name,
            'avatar_key' => $user->avatar_key,
        ]);
    }
}
