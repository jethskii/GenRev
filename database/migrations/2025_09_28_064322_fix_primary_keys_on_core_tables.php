<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // PRODUCTS
        Schema::table('products', function (Blueprint $table) {
            // Ensure 'id' is primary auto-increment BIGINT
            // 1) drop conflicting PK if needed
            try { DB::statement('ALTER TABLE products DROP PRIMARY KEY'); } catch (\Throwable $e) {}
            // 2) make id auto-increment
            DB::statement('ALTER TABLE products MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
            // 3) set id as PK
            DB::statement('ALTER TABLE products ADD PRIMARY KEY (id)');
        });

        // PRODUCTIONS (safety)
        Schema::table('productions', function (Blueprint $table) {
            try { DB::statement('ALTER TABLE productions DROP PRIMARY KEY'); } catch (\Throwable $e) {}
            DB::statement('ALTER TABLE productions MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
            DB::statement('ALTER TABLE productions ADD PRIMARY KEY (id)');
        });

        // PRODUCT_RECIPES (you had an error here too)
        // If this table should have a simple surrogate key:
        Schema::table('product_recipes', function (Blueprint $table) {
            // Remove any extra AUTO_INCREMENT columns first
            // If there is an existing 'id' that is NOT auto-inc, convert it.
            // If there is NO 'id', add one.
        });

        // Safer, explicit SQL for product_recipes:
        // 1) If there IS an id column but not auto-inc:
        try {
            DB::statement('ALTER TABLE product_recipes MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
            DB::statement('ALTER TABLE product_recipes ADD PRIMARY KEY (id)');
        } catch (\Throwable $e) {
            // 2) If there is NO id column, add one
            try {
                DB::statement('ALTER TABLE product_recipes ADD COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST');
            } catch (\Throwable $e2) {}
        }

        // Make sure NO OTHER column in those tables is AUTO_INCREMENT.
        // If you previously set auto-inc on a different column, remove it:
        // Example: DB::statement('ALTER TABLE product_recipes MODIFY COLUMN some_col INT NOT NULL');
    }

    public function down(): void
    {
        // Usually you can leave this empty or reverse changes if you want.
    }
};
