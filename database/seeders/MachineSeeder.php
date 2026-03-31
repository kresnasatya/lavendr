<?php

namespace Database\Seeders;

use App\Models\Machine;
use Illuminate\Database\Seeder;

class MachineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 3 sample machines
        Machine::create([
            'code' => 'VM-001',
            'location' => 'Floor 1 - Break Room',
            'is_active' => true,
        ])->slots()->createMany([
            ['slot_number' => 1, 'category' => 'Food', 'price' => 10],
            ['slot_number' => 2, 'category' => 'Food', 'price' => 12],
            ['slot_number' => 3, 'category' => 'Beverage', 'price' => 8],
            ['slot_number' => 4, 'category' => 'Beverage', 'price' => 10],
            ['slot_number' => 5, 'category' => 'Snack', 'price' => 5],
            ['slot_number' => 6, 'category' => 'Dessert', 'price' => 15],
        ]);

        Machine::create([
            'code' => 'VM-002',
            'location' => 'Floor 2 - Pantry',
            'is_active' => true,
        ])->slots()->createMany([
            ['slot_number' => 1, 'category' => 'Beverage', 'price' => 8],
            ['slot_number' => 2, 'category' => 'Beverage', 'price' => 10],
            ['slot_number' => 3, 'category' => 'Snack', 'price' => 7],
            ['slot_number' => 4, 'category' => 'Snack', 'price' => 8],
        ]);

        Machine::create([
            'code' => 'VM-003',
            'location' => 'Ground Floor - Lobby',
            'is_active' => true,
        ])->slots()->createMany([
            ['slot_number' => 1, 'category' => 'Food', 'price' => 15],
            ['slot_number' => 2, 'category' => 'Food', 'price' => 18],
            ['slot_number' => 3, 'category' => 'Dessert', 'price' => 20],
        ]);
    }
}
