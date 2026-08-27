<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // MySQL-only: native ALTER ... MODIFY ENUM syntax. On Postgres (and others), create_users_table's
        // enum() already permits 'sales'/'inventory' via its CHECK constraint, so there's nothing to widen.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Ensure column exists before altering
        if (! Schema::hasColumn('users', 'role')) return;

        // Expand enum to include 'sales' and 'inventory'
        DB::statement("
            ALTER TABLE `users`
            MODIFY `role` ENUM('admin','staff','manager','sales','inventory')
            NOT NULL DEFAULT 'staff'
        ");

        // Optional but recommended: index role for quick filters
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasIndex('users', 'users_role_index')) {
                $table->index('role');
            }
        });
    }

    public function down(): void
    {
        // Revert to original enum if needed (drop newer roles)
        DB::statement("
            ALTER TABLE `users`
            MODIFY `role` ENUM('admin','staff','manager')
            NOT NULL DEFAULT 'staff'
        ");

        Schema::table('users', function (Blueprint $table) {
            // keep index; it's harmless even if enum shrinks
        });
    }
};
