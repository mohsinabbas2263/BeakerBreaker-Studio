<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SafetyTierQuestion>
 */
class SafetyTierQuestionFactory extends Factory
{
    protected $model = \App\Models\SafetyTierQuestion::class;

    public function definition(): array
    {
        return [
            'tier_number' => 1,
            'prompt' => fake()->sentence().'?',
            'options' => ['Correct answer', 'Wrong one', 'Wrong two', 'Wrong three'],
            'correct_option' => 0,
            'explanation' => fake()->sentence(),
        ];
    }

    public function tier(int $tier): static
    {
        return $this->state(fn () => ['tier_number' => $tier]);
    }
}
