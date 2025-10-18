<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed a guaranteed Master Admin user and a linked Employee record.
     */
    public function run(): void
    {
        // .env overrides (safe defaults)
        $email    = Str::lower(env('ADMIN_EMAIL', 'admin@genrev.test'));
        $username = Str::lower(env('ADMIN_USERNAME', 'admin'));
        $plainPwd = env('ADMIN_PASSWORD', 'password123');

        // Normalize role string; if your app uses constants you can swap here.
        $adminRole = defined(User::class.'::ROLE_ADMIN')
            ? constant(User::class.'::ROLE_ADMIN')
            : 'Admin';

        DB::transaction(function () use ($email, $username, $plainPwd, $adminRole) {

            // --- Create or update the User (restore if soft-deleted)
            $query = User::query();

            // If the model uses SoftDeletes, allow restoring seamlessly
            if (in_array('Illuminate\\Database\\Eloquent\\SoftDeletes', class_uses_recursive(User::class))) {
                $query->withTrashed();
            }

            /** @var \App\Models\User|null $user */
            $user = $query->whereRaw('LOWER(email) = ?', [$email])->first();

            if ($user) {
                if (method_exists($user, 'trashed') && $user->trashed()) {
                    $user->restore();
                }

                $user->name              = 'Master Admin';
                $user->email             = $email;
                $user->password          = Hash::make($plainPwd); // never rely on casts here
                $user->role              = $adminRole;
                $user->email_verified_at = now();
                $user->remember_token    = $user->remember_token ?: Str::random(60);
                $user->save();
            } else {
                $user = User::create([
                    'name'              => 'Master Admin',
                    'email'             => $email,
                    'password'          => Hash::make($plainPwd),
                    'role'              => $adminRole,
                    'email_verified_at' => now(),
                    'remember_token'    => Str::random(60),
                ]);
            }

            // --- Link or create the Employee (match by email or username, case-insensitive)
            $employee = Employee::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->orWhereRaw('LOWER(username) = ?', [$username])
                ->first();

            if ($employee) {
                $employee->user_id    = $user->id;
                $employee->first_name = $employee->first_name ?: 'Master';
                $employee->last_name  = $employee->last_name  ?: 'Admin';
                $employee->username   = $employee->username   ?: $username;
                $employee->email      = $employee->email      ?: $email;
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
