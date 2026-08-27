<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * users.role was defined as ENUM('admin','staff','manager','sales','inventory'), but
     * App\Models\User's actual canonical roles are 'masters admin' / 'production manager' /
     * 'sales' / 'inventory' - multi-word values that were never valid enum members. That
     * mismatch throws "Data truncated for column 'role'" on MySQL and a CHECK constraint
     * violation on Postgres (enum() there is implemented as a CHECK constraint over the same
     * narrow set) the moment the app tries to store either multi-word role. App\Models\User's
     * own normalizeRole() is already the single source of truth for valid values, so the
     * column just needs to be a plain string.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `users` MODIFY `role` VARCHAR(255) NOT NULL DEFAULT 'sales'");
            return;
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
            DB::statement('ALTER TABLE users ALTER COLUMN role TYPE VARCHAR(255)');
            DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'sales'");
        }
    }

    public function down(): void
    {
        // No-op: don't reintroduce the incompatible enum.
    }
};
