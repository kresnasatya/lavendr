<?php

namespace Database\Factories;

use App\Enums\SlotCategory;
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
            'slot_number' => fake()->numberBetween(1, 10),
            'category' => SlotCategory::Juice,
            'price' => fake()->numberBetween(5, 50),
            'quantity' => fake()->numberBetween(10, 100),
        ];
    }

    public function juice(): static
    {
        return $this->state([
            'slot_number' => fake()->numberBetween(1, 10),
            'category' => SlotCategory::Juice,
        ]);
    }

    public function meal(): static
    {
        return $this->state([
            'slot_number' => fake()->numberBetween(11, 30),
            'category' => SlotCategory::Meal,
        ]);
    }

    public function snack(): static
    {
        return $this->state([
            'slot_number' => fake()->numberBetween(31, 40),
            'category' => SlotCategory::Snack,
        ]);
    }
}
