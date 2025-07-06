<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SalesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('sales')->insert([
            [
                'invoice_number' => 'INV-2',
                'product_name' => 'Kimtarub',
                'date' => '2025-07-02',
                'quantity' => 5,
                'price' => 250.00,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'invoice_number' => 'INV-3',
                'product_name' => 'Arjay',
                'date' => '2025-07-02',
                'quantity' => 69,
                'price' => 420.00,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'invoice_number' => 'INV-4',
                'product_name' => 'Kimfy',
                'date' => '2025-07-02',
                'quantity' => 20,
                'price' => 10.00,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'invoice_number' => 'INV-5',
                'product_name' => 'Kimfyfyrahas',
                'date' => '2025-07-02',
                'quantity' => 21,
                'price' => 40.00,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'invoice_number' => 'INV-1',
                'product_name' => 'Longganisa',
                'date' => '2025-07-01',
                'quantity' => 12,
                'price' => 180.00,
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }
}
