<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Skip entirely if id is already a proper auto-increment primary key (e.g. fresh install)
        $col = DB::selectOne("
            SELECT EXTRA, COLUMN_KEY FROM information_schema.columns
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'materials' AND COLUMN_NAME = 'id'
        ");
        if ($col && str_contains($col->EXTRA, 'auto_increment') && $col->COLUMN_KEY === 'PRI') {
            return;
        }

        // Disable FK checks: materials.id is referenced by product_recipes.ingredient_id,
        // and MySQL refuses to MODIFY/rebuild a referenced column while that FK is active.
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            // Ensure id is unsigned big integer and primary + auto_increment
            DB::statement('ALTER TABLE materials MODIFY COLUMN id BIGINT UNSIGNED NOT NULL');
            // Drop PK if not on id (ignore error if already on id)
            try { DB::statement('ALTER TABLE materials DROP PRIMARY KEY'); } catch (\Throwable $e) {}
            try { DB::statement('ALTER TABLE materials ADD PRIMARY KEY (id)'); } catch (\Throwable $e) {}
            DB::statement('ALTER TABLE materials MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        // No-op: we won’t revert primary/AI changes
    }
};
