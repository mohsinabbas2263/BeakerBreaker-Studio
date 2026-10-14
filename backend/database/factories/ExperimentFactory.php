<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Experiment>
 */
class ExperimentFactory extends Factory
{
    protected $model = \App\Models\Experiment::class;

    public function definition(): array
    {
        $title = ucfirst(fake()->unique()->words(2, true));

        return [
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 99999),
            'title' => $title,
            'topic' => fake()->randomElement(['Reactions', 'Solutions', 'Mixtures', 'Acids and Bases']),
            'difficulty' => 'easy',
            'grade_level' => 9,
            'chemical_equation' => 'A + B -> C',
            'explanatory_note' => fake()->sentence(),
            'ar_available' => false,
            'base_points' => 50,
            'estimated_minutes' => 15,
            'is_published' => true,
        ];
    }
}
