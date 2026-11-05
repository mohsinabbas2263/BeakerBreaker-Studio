<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SchoolClass>
 */
class SchoolClassFactory extends Factory
{
    protected $model = \App\Models\SchoolClass::class;

    public function definition(): array
    {
        return [
            'school_id'     => null,
            'title'         => 'Class ' . fake()->randomElement(['9', '10']) . '-' . fake()->randomLetter(),
            'grade_level'   => fake()->randomElement([9, 10]),
            'academic_year' => '2026-2027',
        ];
    }
}
