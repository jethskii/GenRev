<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 2025_11_27_000001 added a database-level guard against two active (non-soft-deleted)
     * products sharing a name, but only for Postgres - MySQL has no partial/filtered index
     * support, so this project's own local/dev database (MySQL) was left relying solely on
     * the app-level Rule::unique() check, with no real protection against a concurrent-request
     * race. MySQL 5.7+ generated columns give an equivalent: a stored column that is NULL
     * whenever the row is soft-deleted, with a plain unique index on that column (MySQL unique
     * indexes already treat multiple NULLs as non-conflicting, so soft-deleted rows never
     * collide with each other or with an active row).
     *
     * SQLite (the automated test suite's driver) actually supports the exact same partial-index
     * syntax Postgres uses, so this also closes the gap there - meaning the constraint finally
     * gets real automated test coverage, which it had none of before (the Postgres-only index
     * was invisible to every test in this suite).
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            if (! Schema::hasIndex('products', 'products_product_name_unique')) {
                DB::statement(
                    'CREATE UNIQUE INDEX products_product_name_unique ON products (product_name) WHERE deleted_at IS NULL'
                );
            }

            return;
        }

        if ($driver === 'mysql') {
            if (! Schema::hasColumn('products', 'product_name_active')) {
                DB::statement(
                    'ALTER TABLE products ADD COLUMN product_name_active VARCHAR(255) '.
                    'GENERATED ALWAYS AS (CASE WHEN deleted_at IS NULL THEN product_name ELSE NULL END) STORED'
                );
            }

            if (! Schema::hasIndex('products', 'products_product_name_active_unique')) {
                DB::statement(
                    'ALTER TABLE products ADD UNIQUE INDEX products_product_name_active_unique (product_name_active)'
                );
            }
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS products_product_name_unique');

            return;
        }

        if ($driver === 'mysql') {
            try {
                DB::statement('ALTER TABLE products DROP INDEX products_product_name_active_unique');
            } catch (\Throwable $e) {
                // already gone
            }

            try {
                DB::statement('ALTER TABLE products DROP COLUMN product_name_active');
            } catch (\Throwable $e) {
                // already gone
            }
        }
    }
};
