<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Employee;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create or update admin user
        $user = User::updateOrCreate(
            ['email' => 'admin@example.com'], // lookup by email
            [
                'name'     => 'Master Admin',
                'password' => 'password123', // auto-hashed by User model cast
                'role'     => 'admin',
            ]
        );

        // Ensure there’s a linked Employee record for username login
        Employee::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'user_id'    => $user->id,
                'first_name' => 'Master',
                'last_name'  => 'Admin',
                'username'   => 'admin',
                'status'     => 'active',
            ]
        );
    }
}
