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
        // Slot ranges per BRD: 1–10 = juice, 11–30 = meal, 31–40 = snack
        Machine::create([
            'code' => 'VM-001',
            'location' => 'Floor 1 - Break Room',
            'is_active' => true,
        ])->slots()->createMany([
            ['slot_number' => 1, 'category' => 'juice', 'price' => 10],
            ['slot_number' => 2, 'category' => 'juice', 'price' => 12],
            ['slot_number' => 11, 'category' => 'meal', 'price' => 25],
            ['slot_number' => 12, 'category' => 'meal', 'price' => 30],
            ['slot_number' => 31, 'category' => 'snack', 'price' => 8],
            ['slot_number' => 32, 'category' => 'snack', 'price' => 10],
        ]);

        Machine::create([
            'code' => 'VM-002',
            'location' => 'Floor 2 - Pantry',
            'is_active' => true,
        ])->slots()->createMany([
            ['slot_number' => 1, 'category' => 'juice', 'price' => 8],
            ['slot_number' => 2, 'category' => 'juice', 'price' => 10],
            ['slot_number' => 31, 'category' => 'snack', 'price' => 7],
            ['slot_number' => 32, 'category' => 'snack', 'price' => 8],
        ]);

        Machine::create([
            'code' => 'VM-003',
            'location' => 'Ground Floor - Lobby',
            'is_active' => true,
        ])->slots()->createMany([
            ['slot_number' => 11, 'category' => 'meal', 'price' => 35],
            ['slot_number' => 12, 'category' => 'meal', 'price' => 40],
            ['slot_number' => 31, 'category' => 'snack', 'price' => 12],
        ]);
    }
}
