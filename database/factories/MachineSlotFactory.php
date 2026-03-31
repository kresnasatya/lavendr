<?php

namespace Database\Factories;

use App\Models\Machine;
use App\Models\MachineSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MachineSlot>
 */
class MachineSlotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'machine_id' => Machine::factory(),
            'slot_number' => $this->faker->numberBetween(1, 12),
            'category' => $this->faker->randomElement(['Food', 'Beverage', 'Snack', 'Dessert']),
            'price' => $this->faker->numberBetween(5, 50),
            'quantity' => $this->faker->numberBetween(10, 100),
        ];
    }
}
