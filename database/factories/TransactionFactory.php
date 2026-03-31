<?php

namespace Database\Factories;

use App\Models\Machine;
use App\Models\MachineSlot;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
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
            'machine_id' => Machine::factory(),
            'machine_slot_id' => MachineSlot::factory(),
            'points_deducted' => $this->faker->numberBetween(5, 50),
            'status' => $this->faker->randomElement(['success', 'failed', 'cancelled']),
            'notes' => $this->faker->optional()->word(),
        ];
    }
}
