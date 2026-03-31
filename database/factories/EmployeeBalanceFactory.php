<?php

namespace Database\Factories;

use App\Models\EmployeeBalance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeBalance>
 */
class EmployeeBalanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'current_balance' => $this->faker->numberBetween(0, 500),
            'daily_quota' => $this->faker->randomElement([50, 100, 150, 200]),
            'last_recharged_at' => now(),
        ];
    }
}
