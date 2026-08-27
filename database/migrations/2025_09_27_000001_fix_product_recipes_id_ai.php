<?php

// database/migrations/2025_09_27_000001_fix_product_recipes_id_ai.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // MySQL-only repair migration; Postgres/other drivers get correct types from create_product_recipes_table.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::hasTable('product_recipes')) {
            // Ensure primary key & AUTO_INCREMENT on MySQL
            DB::statement('
                ALTER TABLE product_recipes
                MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT
            ');
        }
    }

    public function down(): void
    {
        // (no-op) – you can optionally remove AUTO_INCREMENT here if needed
    }
};
