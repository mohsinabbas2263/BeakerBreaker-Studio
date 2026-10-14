<?php

namespace App\Services;

use App\Models\Experiment;
use App\Models\User;

/**
 * One place that answers "may this student open this experiment, and why not".
 * FR-02.3 safety gate, FR-03.3 prerequisite gate, and the reason is returned
 * because FR-03.3 requires the reason to be displayed.
 */
class ExperimentAccessService
{
    /** @return array{allowed:bool, reason:?string, code:?string} */
    public function check(User $user, Experiment $experiment): array
    {
        // FR-02.3: nothing opens before Tier 1.
        if (! $user->hasPassedSafetyTier(1)) {
            return [
                'allowed' => false,
                'code' => 'safety_tier_1_required',
                'reason' => 'Pass Safety Tier 1, Basic Lab Rules, before opening an experiment.',
            ];
        }

        $required = $experiment->requiredSafetyTier();

        if ($required > 1 && ! $user->hasPassedSafetyTier($required)) {
            return [
                'allowed' => false,
                'code' => 'safety_tier_required',
                'reason' => sprintf(
                    'This experiment needs Safety Tier %d, %s.',
                    $required,
                    \App\Models\SafetyTierQuestion::TIERS[$required] ?? ''
                ),
            ];
        }

        // FR-03.3: the prerequisite experiment must be complete, with the reason shown.
        if ($experiment->prerequisite_experiment_id) {
            $done = $user->experimentSessions()
                ->where('experiment_id', $experiment->prerequisite_experiment_id)
                ->whereNotNull('completed_at')
                ->exists();

            if (! $done) {
                return [
                    'allowed' => false,
                    'code' => 'prerequisite_incomplete',
                    'reason' => 'Complete '.$experiment->prerequisite->title.' first.',
                ];
            }
        }

        if (! $experiment->is_published) {
            return ['allowed' => false, 'code' => 'unpublished', 'reason' => 'This experiment is not available yet.'];
        }

        return ['allowed' => true, 'code' => null, 'reason' => null];
    }
}
