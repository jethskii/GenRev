<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Ensure at least one test user
        \App\Models\User::factory()->create([
            'name'  => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Optional admin seed (only if you have it)
        if (class_exists(\Database\Seeders\AdminUserSeeder::class)) {
            $this->call([ AdminUserSeeder::class ]);
        }

        // Prefer the new Sales Orders flow if tables exist
        $hasOrders = Schema::hasTable('sales_orders') && Schema::hasTable('sales_order_items');

        if ($hasOrders && class_exists(\Database\Seeders\SalesOrderSeeder::class)) {
            $this->call([ SalesOrderSeeder::class ]);
        } else {
            // Fallback to legacy sales table seeding
            if (Schema::hasTable('sales')) {
                $this->call([ SalesSeeder::class ]);
            } else {
                $this->command?->warn('No sales-related tables detected; skipping sales seeders.');
            }
        }
    }
}
