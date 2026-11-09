<?php

namespace App\Services;

use App\Models\DailyMission;
use App\Models\ExperimentSession;
use App\Models\QuizAttempt;
use App\Models\StepAction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * FR-07.3: three daily missions for each student, replaced at midnight Pakistan
 * Standard Time (config app.display_timezone).
 *
 * A mission counts what the student did that day; none of them asks for speed
 * (FR-06.2), and a completed mission is never taken back. Progress is computed on
 * the server from the student's own records, so a client cannot claim one.
 */
class MissionService
{
    /**
     * code => [title, icon, target, what it counts]
     *
     * @var array<string, array{0: string, 1: string, 2: int, 3: string}>
     */
    public const TEMPLATES = [
        // Need Safety Tier 1, because they happen inside experiments (FR-02.3).
        'right_moves' => ['Make 10 right moves in experiments', '✅', 10, 'right_moves'],
        'finish_experiment' => ['Finish every step of an experiment', '🧪', 1, 'finished_runs'],
        'pass_quiz' => ['Pass a knowledge quiz', '📝', 1, 'passed_quizzes'],
        'perfect_quiz' => ['Score 100% on a quiz', '💯', 1, 'perfect_quizzes'],
        'ask_guide' => ['Ask your guide about a step', '❓', 1, 'questions'],
        'fix_mistake' => ['Put a mistake right yourself', '🔁', 1, 'recoveries'],
        'try_ar' => ['Open an experiment in AR mode', '🥽', 1, 'ar_sessions'],
        // For a student still working through the safety tiers.
        'safety_quiz' => ['Take a safety tier quiz', '🛡️', 1, 'safety_attempts'],
        'safety_two' => ['Take two safety tier quizzes', '🦺', 2, 'safety_attempts'],
        'safety_pass' => ['Pass a safety tier', '🏅', 1, 'safety_passes'],
    ];

    private const EXPERIMENT_MISSIONS = ['right_moves', 'finish_experiment', 'pass_quiz', 'perfect_quiz', 'ask_guide', 'fix_mistake', 'try_ar'];
    private const SAFETY_MISSIONS = ['safety_quiz', 'safety_two', 'safety_pass'];

    /** The current day in Pakistan time, and its bounds in UTC for the queries. */
    public function today(): array
    {
        $tz = config('app.display_timezone', 'Asia/Karachi');
        $start = CarbonImmutable::now($tz)->startOfDay();

        return [$start->toDateString(), $start->utc(), $start->addDay()->utc()];
    }

    /**
     * Today's three missions with their progress; completes any that are done.
     *
     * @return array{date: string, resets_at: string, missions: list<array>}
     */
    public function forToday(User $user): array
    {
        [$date, $from, $to] = $this->today();

        $missions = DailyMission::where('user_id', $user->id)->whereDate('mission_date', $date)->get();
        if ($missions->isEmpty()) {
            $missions = $this->assign($user, $date);
        }

        $counts = $this->counts($user, $from, $to);

        $out = $missions->sortBy('id')->map(function (DailyMission $m) use ($counts) {
            [$title, $icon, , $metric] = self::TEMPLATES[$m->code] ?? [$m->code, '🎯', $m->target, ''];
            $progress = min($counts[$metric] ?? 0, $m->target);

            if ($progress >= $m->target && ! $m->completed_at) {
                $m->completed_at = now();
                $m->save();
            }

            return [
                'code' => $m->code,
                'title' => $title,
                'icon' => $icon,
                'target' => $m->target,
                // A finished mission stays finished, whatever happens later that day.
                'progress' => $m->completed_at ? $m->target : $progress,
                'completed' => (bool) $m->completed_at,
            ];
        })->values()->all();

        return ['date' => $date, 'resets_at' => $to->toIso8601String(), 'missions' => $out];
    }

    /** Three missions the student can actually do today, picked the same way on every request. */
    private function assign(User $user, string $date)
    {
        $tier = $user->highestSafetyTierPassed();

        $pool = $tier >= 1 ? self::EXPERIMENT_MISSIONS : self::SAFETY_MISSIONS;
        if ($tier >= 1 && $tier < 4) {
            $pool[] = 'safety_pass';   // the next tier is a fair mission too
        }

        $picked = collect($pool)
            ->sortBy(fn (string $code) => crc32("{$user->id}|{$date}|{$code}"))
            ->take(3);

        foreach ($picked as $code) {
            try {
                DailyMission::firstOrCreate(
                    ['user_id' => $user->id, 'mission_date' => $date, 'code' => $code],
                    ['target' => self::TEMPLATES[$code][2]],
                );
            } catch (UniqueConstraintViolationException) {
                // A second request assigned the same mission at the same moment.
            }
        }

        return DailyMission::where('user_id', $user->id)->whereDate('mission_date', $date)->get();
    }

    /** @return array<string, int> what the student did between $from and $to */
    private function counts(User $user, $from, $to): array
    {
        $actions = StepAction::where('user_id', $user->id)->whereBetween('created_at', [$from, $to])
            ->orderBy('id')->get(['session_id', 'step_id', 'outcome', 'hinted']);
        $quizzes = QuizAttempt::where('user_id', $user->id)->whereBetween('submitted_at', [$from, $to])->get(['score', 'passed']);
        $safety = $user->safetyAttempts()->whereBetween('attempted_at', [$from, $to])->get(['passed']);

        return [
            'right_moves' => $actions->whereIn('outcome', ['correct', 'partial'])->count(),
            'finished_runs' => ExperimentSession::where('user_id', $user->id)->whereBetween('completed_at', [$from, $to])->count(),
            'passed_quizzes' => $quizzes->where('passed', true)->count(),
            'perfect_quizzes' => $quizzes->where('score', 100)->count(),
            'questions' => $actions->where('hinted', true)->unique(fn ($a) => "{$a->session_id}:{$a->step_id}")->count(),
            'recoveries' => AchievementService::recoveries($actions),
            'ar_sessions' => ExperimentSession::where('user_id', $user->id)->where('presentation_mode', 'ar')
                ->where(fn ($q) => $q->whereBetween('started_at', [$from, $to])->orWhereBetween('updated_at', [$from, $to]))->count(),
            'safety_attempts' => $safety->count(),
            'safety_passes' => $safety->where('passed', true)->count(),
        ];
    }
}
