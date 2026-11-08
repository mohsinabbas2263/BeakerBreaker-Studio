<?php

namespace App\Support;

use App\Models\Experiment;

/**
 * FR-06.3: turns stored badge codes into what a student reads.
 *
 * RewardService awards three families of code: safety_tier_{n}, experiment_{slug}
 * and streak_{n}_days. The names live here, on the server, so every client shows
 * the same words and a new experiment needs no client release.
 */
class Badges
{
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
        if (preg_match('/^safety_tier_(\d+)$/', $code, $m)) {
            return ['code' => $code, 'name' => "Safety Tier {$m[1]}", 'icon' => '🛡️'];
        }

        if (preg_match('/^streak_(\d+)_days$/', $code, $m)) {
            return ['code' => $code, 'name' => "{$m[1]}-Day Streak", 'icon' => '🔥'];
        }

        if (str_starts_with($code, 'experiment_')) {
            $slug = substr($code, strlen('experiment_'));
            $title = $titles[$slug] ?? ucfirst(str_replace('-', ' ', $slug));

            return ['code' => $code, 'name' => $title, 'icon' => '🔬'];
        }

        return ['code' => $code, 'name' => ucfirst(str_replace('_', ' ', $code)), 'icon' => '🏅'];
    }
}
