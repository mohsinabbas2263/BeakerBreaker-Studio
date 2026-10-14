<?php

namespace Database\Factories;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = \App\Models\User::class;

    public function definition(): array
    {
        return [
            'school_id' => null,
            'role' => UserRole::Student,
            'display_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'status' => 'active',
        ];
    }

    public function teacher(): static
    {
        return $this->state(fn () => ['role' => UserRole::Teacher]);
    }

    public function administrator(): static
    {
        return $this->state(fn () => ['role' => UserRole::Administrator]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => 'suspended']);
    }
}
