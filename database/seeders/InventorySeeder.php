<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Inventory;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Inventory::create([
            'product' => 'Longganisa',
            'batch' => 21,
            'date' => '2025-02-19',
            'quantity' => 84,
        ]);

        // Add more records here...
        Inventory::create([
            'product' => 'Ham',
            'batch' => 19,
            'date' => '2025-02-07',
            'quantity' => 182,
        ]);

        Inventory::create([
            'product' => 'Shanghai',
            'batch' => 16,
            'date' => '2025-02-05',
            'quantity' => 95,
        ]);
    }
}
