<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Optionally seed multiple users using factory
        // User::factory(10)->create();

        // Create one test user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Call additional seeders here if needed
        // $this->call([
        //     InventorySeeder::class,
        // ]);
    }
}
