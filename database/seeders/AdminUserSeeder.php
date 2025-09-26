<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Employee;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email    = Str::lower(env('ADMIN_EMAIL', 'admin@example.com'));
        $username = Str::lower(env('ADMIN_USERNAME', 'admin'));
        $password = env('ADMIN_PASSWORD', 'password123'); // hashed by User model cast

        DB::transaction(function () use ($email, $username, $password) {
            // Create or restore admin user
            /** @var \App\Models\User $user */
            $user = User::withTrashed()->where('email', $email)->first();

            if ($user) {
                if ($user->trashed()) {
                    $user->restore();
                }
                $user->fill([
                    'name'               => 'Master Admin',
                    'password'           => $password,        // 'hashed' cast -> bcrypt
                    'role'               => User::ROLE_ADMIN,
                    'email_verified_at'  => now(),
                ])->save();
            } else {
                $user = User::create([
                    'name'              => 'Master Admin',
                    'email'             => $email,
                    'password'          => $password,         // 'hashed' cast -> bcrypt
                    'role'              => User::ROLE_ADMIN,
                    'email_verified_at' => now(),
                ]);
            }

            // Link or create Employee (search by email OR username)
            $employee = Employee::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->orWhereRaw('LOWER(username) = ?', [$username])
                ->first();

            if ($employee) {
                $employee->user_id    = $user->id;
                $employee->first_name = $employee->first_name ?: 'Master';
                $employee->last_name  = $employee->last_name  ?: 'Admin';
                $employee->username   = $employee->username   ?: $username;
                $employee->status     = $employee->status     ?: 'active';
                $employee->position   = $employee->position   ?: 'Administrator';
                $employee->save();
            } else {
                Employee::create([
                    'user_id'    => $user->id,
                    'first_name' => 'Master',
                    'last_name'  => 'Admin',
                    'email'      => $email,
                    'username'   => $username,
                    'status'     => 'active',
                    'position'   => 'Administrator',
                ]);
            }
        });
    }
}
