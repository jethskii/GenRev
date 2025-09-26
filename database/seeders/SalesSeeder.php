<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class SalesSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('sales')) {
            $this->command?->warn('sales table not found; skipping SalesSeeder.');
            return;
        }

        // Base dataset (legacy-style)
        $rows = [
            ['invoice_number' => 'INV-2', 'product' => 'Kimtarub',      'date' => '2025-07-02', 'quantity' => 5,  'price' => 250.00],
            ['invoice_number' => 'INV-3', 'product' => 'Arjay',         'date' => '2025-07-02', 'quantity' => 69, 'price' => 420.00],
            ['invoice_number' => 'INV-4', 'product' => 'Kimfy',         'date' => '2025-07-02', 'quantity' => 20, 'price' => 10.00],
            ['invoice_number' => 'INV-5', 'product' => 'Kimfyfyrahas',  'date' => '2025-07-02', 'quantity' => 21, 'price' => 40.00],
            ['invoice_number' => 'INV-1', 'product' => 'Longganisa',    'date' => '2025-07-01', 'quantity' => 12, 'price' => 180.00],
        ];

        $hasOrderDate  = Schema::hasColumn('sales', 'order_date');
        $hasDate       = Schema::hasColumn('sales', 'date');
        $hasQtyKg      = Schema::hasColumn('sales', 'quantity_kg');
        $hasQty        = Schema::hasColumn('sales', 'quantity');
        $hasUnitPrice  = Schema::hasColumn('sales', 'unit_price');
        $hasPrice      = Schema::hasColumn('sales', 'price');
        $hasTotalPrice = Schema::hasColumn('sales', 'total_price');
        $hasTotal      = Schema::hasColumn('sales', 'total');
        $hasProductStr = Schema::hasColumn('sales', 'product');
        $hasProductName= Schema::hasColumn('sales', 'product_name');
        $hasStatus     = Schema::hasColumn('sales', 'status');

        foreach ($rows as $r) {
            $qty   = (float)$r['quantity'];
            $unit  = (float)$r['price'];
            $total = round($qty * $unit, 2);
            $date  = Carbon::parse($r['date'])->toDateString();

            $payload = [
                'invoice_number' => $r['invoice_number'],
                'created_at'     => now(),
                'updated_at'     => now(),
            ];

            // Dates
            if ($hasOrderDate) $payload['order_date'] = $date;
            if ($hasDate)      $payload['date']       = $date;

            // Product display string
            if ($hasProductStr)  $payload['product']       = $r['product'];
            if ($hasProductName) $payload['product_name']  = $r['product'];

            // Quantity
            if ($hasQtyKg) $payload['quantity_kg'] = $qty;
            if ($hasQty)   $payload['quantity']    = $qty;

            // Unit price
            if ($hasUnitPrice) $payload['unit_price'] = $unit;
            if ($hasPrice)     $payload['price']      = $unit;

            // Totals
            if ($hasTotalPrice) $payload['total_price'] = $total;
            if ($hasTotal)      $payload['total']       = $total;

            // Status default
            if ($hasStatus) $payload['status'] = 'Completed';

            // Avoid duplicates by invoice_number
            DB::table('sales')->updateOrInsert(
                ['invoice_number' => $r['invoice_number']],
                $payload
            );
        }

        $this->command?->info('SalesSeeder: seeded legacy/mixed sales rows successfully.');
    }
}
