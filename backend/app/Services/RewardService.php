<?php

namespace App\Services;

use App\Models\Reward;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * FR-06.2 points are never awarded for speed: no method here takes a duration.
 * FR-06.4 points and rank never decrease: the only arithmetic is addition.
 * FR-06.3 a badge reaches a student at most once: the unique index enforces it.
 */
class RewardService
{
    /** @return int the points actually added */
    public function awardPoints(
        User $user,
        int $points,
        string $sourceType,
        ?int $sourceId = null,
    ): int {
        if ($points <= 0) {
            return 0;                                   // FR-06.4: nothing may subtract
        }

        return DB::transaction(function () use ($user, $points, $sourceType, $sourceId) {
            Reward::create([
                'user_id' => $user->id,
                'reward_type' => 'points',
                'points_awarded' => $points,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'awarded_at' => now(),
            ]);

            $profile = StudentProfile::firstOrCreate(['user_id' => $user->id]);
            $profile->total_points += $points;
            $profile->rank_level = max(
                $profile->rank_level,                   // FR-06.4: rank never falls
                StudentProfile::rankFor($profile->total_points)
            );
            $profile->save();

            return $points;
        });
    }

    /** FR-04.3: the experiment's points, once only, on the first pass at 80 or above. */
    public function awardExperimentPointsOnce(User $user, int $experimentId, int $points): int
    {
        $already = Reward::where('user_id', $user->id)
            ->where('reward_type', 'points')
            ->where('source_type', 'experiment')
            ->where('source_id', $experimentId)
            ->exists();

        return $already ? 0 : $this->awardPoints($user, $points, 'experiment', $experimentId);
    }

    /** FR-06.3: returns false when the student already holds the badge. */
    public function awardBadge(User $user, string $badgeCode): bool
    {
        $exists = Reward::where('user_id', $user->id)->where('badge_code', $badgeCode)->exists();

        if ($exists) {
            return false;
        }

        Reward::create([
            'user_id' => $user->id,
            'reward_type' => 'badge',
            'badge_code' => $badgeCode,
            'points_awarded' => 0,
            'awarded_at' => now(),
        ]);

        return true;
    }

    /** FR-06.5: consecutive days of activity, with badges at 7, 30 and 100. */
    public function recordActivity(User $user): void
    {
        $profile = StudentProfile::firstOrCreate(['user_id' => $user->id]);
        $today = now()->toDateString();

        if ($profile->last_active_on?->toDateString() === $today) {
            return;
        }

        $wasYesterday = $profile->last_active_on?->toDateString() === now()->subDay()->toDateString();
        $profile->current_streak_days = $wasYesterday ? $profile->current_streak_days + 1 : 1;
        $profile->last_active_on = $today;
        $profile->save();

        foreach ([7, 30, 100] as $milestone) {
            if ($profile->current_streak_days === $milestone) {
                $this->awardBadge($user, "streak_{$milestone}_days");
            }
        }
    }
}
