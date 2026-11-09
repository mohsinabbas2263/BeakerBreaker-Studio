<?php

namespace App\Services;

use App\Models\DailyMission;
use App\Models\ExperimentSession;
use App\Models\QuizAttempt;
use App\Models\StepAction;
use App\Models\User;
use App\Support\Badges;
use Illuminate\Support\Collection;

/**
 * FR-06.3: the achievement badges (Badges::ACHIEVEMENTS), checked after anything a
 * student does that could earn one. Every figure is counted on the server from the
 * student's own records; nothing here can be claimed by a client (FR-02.5), and a
 * badge once given stays (RewardService::awardBadge never removes).
 */
class AchievementService
{
    public function __construct(
        protected RewardService $rewards,
        protected MissionService $missions,
    ) {}

    /**
     * Brings missions and badges up to date after an event.
     *
     * @return list<string> the badge codes awarded just now
     */
    public function refresh(User $user): array
    {
        // A move may finish a mission, and finished missions count towards badges.
        $this->missions->forToday($user);

        $stats = $this->stats($user);
        $awarded = [];

        foreach (Badges::ACHIEVEMENTS as $code => [, , $stat, $threshold]) {
            if (($stats[$stat] ?? 0) >= $threshold && $this->rewards->awardBadge($user, $code)) {
                $awarded[] = $code;
            }
        }

        return $awarded;
    }

    /** @return array<string, int> */
    public function stats(User $user): array
    {
        $quizzes = QuizAttempt::where('user_id', $user->id)->orderBy('submitted_at')->orderBy('id')
            ->get(['experiment_id', 'score', 'passed']);

        $actions = StepAction::where('user_id', $user->id)->orderBy('id')
            ->get(['session_id', 'step_id', 'outcome', 'category', 'hinted']);

        $completed = ExperimentSession::where('user_id', $user->id)->whereNotNull('completed_at')
            ->get(['id', 'experiment_id', 'presentation_mode']);

        // A run is clean when no move in it was a safety mistake.
        $slips = $actions->where('outcome', 'mistake')->where('category', 'safety')->pluck('session_id')->unique();

        $missions = DailyMission::where('user_id', $user->id)->whereNotNull('completed_at')->get(['mission_date']);

        return [
            'experiments_passed' => $quizzes->where('passed', true)->pluck('experiment_id')->unique()->count(),
            'perfect_quizzes' => $quizzes->where('score', 100)->count(),
            'comebacks' => $quizzes->groupBy('experiment_id')
                ->filter(fn (Collection $tries) => ! $tries->first()->passed && $tries->contains('passed', true))
                ->count(),
            'safety_tiers' => $user->safetyAttempts()->where('passed', true)->distinct()->count('tier_number'),
            'clean_runs' => $completed->whereNotIn('id', $slips)->count(),
            'recoveries' => self::recoveries($actions),
            'questions' => $actions->where('hinted', true)->unique(fn ($a) => "{$a->session_id}:{$a->step_id}")->count(),
            'rank' => $user->profile?->rank_level ?? 1,
            'points' => $user->profile?->total_points ?? 0,
            'missions' => $missions->count(),
            'mission_days' => $missions->groupBy(fn ($m) => $m->mission_date->toDateString())->filter(fn ($d) => $d->count() >= 3)->count(),
            'ar_experiments' => $completed->where('presentation_mode', 'ar')->pluck('experiment_id')->unique()->count(),
        ];
    }

    /**
     * Steps where the student made a mistake and then got it right themselves,
     * before the answer was revealed (docs/experiment-engine.md, REVEAL_AFTER).
     *
     * @param  Collection<int, StepAction>  $actions  in the order they happened
     */
    public static function recoveries(Collection $actions): int
    {
        return $actions->groupBy(fn ($a) => "{$a->session_id}:{$a->step_id}")
            ->filter(function (Collection $moves) {
                $slipped = false;
                foreach ($moves as $move) {
                    if ($move->outcome === 'mistake') {
                        $slipped = true;
                    } elseif ($move->outcome === 'revealed') {
                        return false;
                    } elseif ($slipped) {
                        return true;
                    }
                }

                return false;
            })
            ->count();
    }
}
