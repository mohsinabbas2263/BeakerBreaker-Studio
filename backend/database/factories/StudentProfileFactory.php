<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StudentProfile>
 */
class StudentProfileFactory extends Factory
{
    protected $model = \App\Models\StudentProfile::class;

    public function definition(): array
    {
        return [
            'user_id'              => \App\Models\User::factory(),
            'class_id'             => null,
            'total_points'         => 0,
            'rank_level'           => 1,
            'current_streak_days'  => 0,
            'last_active_on'       => null,
        ];
    }
}
