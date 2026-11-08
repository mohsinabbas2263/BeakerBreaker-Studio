<?php

namespace App\Services;

use App\Models\Experiment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * One place that answers "may this student open this experiment, and why not".
 * FR-02.3 safety gate, FR-03.3 prerequisite gate, and the reason is returned
 * because FR-03.3 requires the reason to be displayed.
 */
class ExperimentAccessService
{
    /**
     * Board exams in Pakistan examine practicals in pairs: Class 10 covers 9 and 10,
     * Class 12 covers 11 and 12. A student therefore works with both classes of
     * their pair (decided 8 Nov 2026).
     */
    public const GRADE_PAIRS = [9 => [9, 10], 10 => [9, 10], 11 => [11, 12], 12 => [11, 12]];

    /**
     * Licensing (decided 8 Nov 2026): the experiments a student may see at all.
     * Their class's exam pair, limited to the grades their school is licensed for,
     * and the school's board or both boards. Applied to the list and, through
     * check(), to every single-experiment route, so a typed URL cannot get round it.
     */
    public function restrictToLicence(Builder $query, User $user): Builder
    {
        $grades = $this->gradesFor($user);

        if ($grades === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->whereIn('grade_level', $grades)
            ->whereIn('board', [$user->school->board, 'both']);
    }

    /** The grades this student may work on: their exam pair, as far as the school is licensed. */
    public function gradesFor(User $user): array
    {
        $grade = $this->gradeOf($user);
        $school = $user->school;

        if ($grade === null || ! $school) {
            return [];
        }

        return array_values(array_filter(
            self::GRADE_PAIRS[$grade] ?? [],
            fn (int $g) => $school->licensesGrade($g),
        ));
    }

    /** @return array{allowed:bool, reason:?string, code:?string} */
    public function licence(User $user, Experiment $experiment): array
    {
        $grade = $this->gradeOf($user);
        $school = $user->school;

        if ($grade === null) {
            return [
                'allowed' => false,
                'code' => 'no_class',
                'reason' => 'You are not in a class yet. Ask your school administrator to add you to one.',
            ];
        }

        $licensed = $school
            && in_array((int) $experiment->grade_level, $this->gradesFor($user), true)
            && in_array($experiment->board, [$school->board, 'both'], true);

        return $licensed
            ? ['allowed' => true, 'code' => null, 'reason' => null]
            : ['allowed' => false, 'code' => 'not_licensed', 'reason' => 'This experiment is not part of your class.'];
    }

    private function gradeOf(User $user): ?int
    {
        $grade = $user->profile?->schoolClass?->grade_level;

        return $grade === null ? null : (int) $grade;
    }

    /** @return array{allowed:bool, reason:?string, code:?string} */
    public function check(User $user, Experiment $experiment): array
    {
        // Licensing first: an experiment outside the student's class and school licence
        // does not exist for them, whatever their safety tier.
        $licence = $this->licence($user, $experiment);
        if (! $licence['allowed']) {
            return $licence;
        }

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
