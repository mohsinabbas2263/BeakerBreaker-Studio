<?php

namespace App\Support;

use App\Models\Experiment;

/**
 * FR-06.3: the badge catalogue, and the names a student reads.
 *
 * Four families of code:
 *   safety_tier_{n}      passing each safety tier (SafetyController)
 *   experiment_{slug}    passing the quiz of each published experiment (QuizController)
 *   streak_{n}_days      consecutive active days (RewardService, FR-06.5)
 *   the ACHIEVEMENTS     below, checked by AchievementService
 *
 * The achievements follow the survey (CLAUDE.md): they reward thorough, safe and
 * curious practice. None is about speed (FR-06.2), and none is ever taken away.
 * The names live here, on the server, so every client shows the same words.
 */
class Badges
{
    public const STREAKS = [3, 7, 30, 100];

    /**
     * code => [name, icon, the AchievementService stat it reads, threshold, what earns it]
     *
     * @var array<string, array{0: string, 1: string, 2: string, 3: int, 4: string}>
     */
    public const ACHIEVEMENTS = [
        'experiments_1' => ['First Experiment', '🧪', 'experiments_passed', 1, 'Pass the quiz of your first experiment.'],
        'experiments_5' => ['Five in the Book', '📗', 'experiments_passed', 5, 'Pass 5 different experiments.'],
        'experiments_10' => ['Ten Down', '📘', 'experiments_passed', 10, 'Pass 10 different experiments.'],
        'experiments_20' => ['Lab Regular', '📙', 'experiments_passed', 20, 'Pass 20 different experiments.'],
        'experiments_30' => ['Whole Syllabus', '📚', 'experiments_passed', 30, 'Pass 30 different experiments.'],

        'perfect_quiz_1' => ['Full Marks', '💯', 'perfect_quizzes', 1, 'Score 100% on a quiz.'],
        'perfect_quiz_5' => ['Five Perfect Quizzes', '🌟', 'perfect_quizzes', 5, 'Score 100% on 5 quizzes.'],
        'perfect_quiz_10' => ['Quiz Master', '🏆', 'perfect_quizzes', 10, 'Score 100% on 10 quizzes.'],
        'quiz_comeback' => ['Never Give Up', '💪', 'comebacks', 1, 'Pass a quiz you did not pass the first time.'],

        'safety_champion' => ['Safety Champion', '🦺', 'safety_tiers', 4, 'Pass all four safety tiers.'],
        'clean_run_1' => ['Safe Hands', '🧤', 'clean_runs', 1, 'Finish an experiment without a single safety slip.'],
        'clean_run_5' => ['Careful Chemist', '🥽', 'clean_runs', 5, 'Finish 5 experiments without a safety slip.'],
        'clean_run_10' => ['Safety First', '🛡️', 'clean_runs', 10, 'Finish 10 experiments without a safety slip.'],

        'recovery_1' => ['Put It Right', '🔁', 'recoveries', 1, 'Correct a mistake yourself, before the answer is shown.'],
        'recovery_10' => ['Learns from Mistakes', '🧠', 'recoveries', 10, 'Correct 10 mistakes yourself.'],
        'recovery_25' => ['Resilient Scientist', '🌱', 'recoveries', 25, 'Correct 25 mistakes yourself.'],

        'curious_1' => ['Curious Mind', '❓', 'questions', 1, 'Ask your guide about a step.'],
        'curious_10' => ['Question Everything', '🔍', 'questions', 10, 'Ask your guide about 10 steps.'],
        'curious_50' => ['Deep Thinker', '💡', 'questions', 50, 'Ask your guide about 50 steps.'],

        'rank_2' => ['Junior Chemist', '🎖️', 'rank', 2, 'Reach the Junior Chemist rank.'],
        'rank_3' => ['Apprentice Chemist', '🎖️', 'rank', 3, 'Reach the Apprentice Chemist rank.'],
        'rank_4' => ['Lab Technician', '🎖️', 'rank', 4, 'Reach the Lab Technician rank.'],
        'rank_5' => ['Senior Chemist', '🎖️', 'rank', 5, 'Reach the Senior Chemist rank.'],
        'rank_6' => ['Research Chemist', '🎖️', 'rank', 6, 'Reach the Research Chemist rank.'],
        'rank_7' => ['Lab Master', '🎖️', 'rank', 7, 'Reach the Lab Master rank.'],
        'rank_8' => ['Beaker Breaker', '👑', 'rank', 8, 'Reach the top rank.'],

        'points_500' => ['500 XP', '⭐', 'points', 500, 'Earn 500 experience points.'],
        'points_1000' => ['1,000 XP', '⭐', 'points', 1000, 'Earn 1,000 experience points.'],
        'points_2500' => ['2,500 XP', '🌠', 'points', 2500, 'Earn 2,500 experience points.'],
        'points_5000' => ['5,000 XP', '🌌', 'points', 5000, 'Earn 5,000 experience points.'],

        'missions_1' => ['On a Mission', '🎯', 'missions', 1, 'Complete a daily mission.'],
        'missions_10' => ['Mission Regular', '🎯', 'missions', 10, 'Complete 10 daily missions.'],
        'missions_50' => ['Mission Expert', '🎯', 'missions', 50, 'Complete 50 daily missions.'],
        'mission_day_1' => ['Full House', '🗓️', 'mission_days', 1, 'Complete all three missions in one day.'],
        'mission_day_7' => ['Seven Full Days', '📅', 'mission_days', 7, 'Complete all three missions on 7 days.'],

        'ar_1' => ['AR Explorer', '🥽', 'ar_experiments', 1, 'Finish an experiment in AR mode.'],
        'ar_3' => ['AR Adventurer', '🛸', 'ar_experiments', 3, 'Finish 3 experiments in AR mode.'],
    ];

    /**
     * Every badge a student could earn in the current content: the number FR-06.3
     * asks to be at least 60.
     *
     * @return list<string>
     */
    public static function catalogue(): array
    {
        $experiments = Experiment::where('is_published', true)->orderBy('id')->pluck('slug')
            ->map(fn (string $slug) => "experiment_{$slug}");

        return collect([1, 2, 3, 4])->map(fn ($n) => "safety_tier_{$n}")
            ->concat($experiments)
            ->concat(array_map(fn ($n) => "streak_{$n}_days", self::STREAKS))
            ->concat(array_keys(self::ACHIEVEMENTS))
            ->values()
            ->all();
    }

    /**
     * @param  iterable<string>  $codes
     * @return list<array{code: string, name: string, icon: string}>
     */
    public static function describe(iterable $codes): array
    {
        $codes = collect($codes)->values();

        $slugs = $codes
            ->filter(fn (string $c) => str_starts_with($c, 'experiment_'))
            ->map(fn (string $c) => substr($c, strlen('experiment_')))
            ->all();

        $titles = $slugs === []
            ? []
            : Experiment::whereIn('slug', $slugs)->pluck('title', 'slug')->all();

        return $codes->map(fn (string $code) => self::one($code, $titles))->all();
    }

    /** @param  array<string, string>  $titles  experiment titles by slug */
    private static function one(string $code, array $titles): array
    {
        if (isset(self::ACHIEVEMENTS[$code])) {
            [$name, $icon, , , $how] = self::ACHIEVEMENTS[$code];

            return ['code' => $code, 'name' => $name, 'icon' => $icon, 'description' => $how];
        }

        if (preg_match('/^safety_tier_(\d+)$/', $code, $m)) {
            return ['code' => $code, 'name' => "Safety Tier {$m[1]}", 'icon' => '🛡️', 'description' => "Pass safety tier {$m[1]}."];
        }

        if (preg_match('/^streak_(\d+)_days$/', $code, $m)) {
            return ['code' => $code, 'name' => "{$m[1]}-Day Streak", 'icon' => '🔥', 'description' => "Practise on {$m[1]} days in a row."];
        }

        if (str_starts_with($code, 'experiment_')) {
            $slug = substr($code, strlen('experiment_'));
            $title = $titles[$slug] ?? ucfirst(str_replace('-', ' ', $slug));

            return ['code' => $code, 'name' => $title, 'icon' => '🔬', 'description' => "Pass the quiz of {$title}."];
        }

        return ['code' => $code, 'name' => ucfirst(str_replace('_', ' ', $code)), 'icon' => '🏅', 'description' => ''];
    }
}
