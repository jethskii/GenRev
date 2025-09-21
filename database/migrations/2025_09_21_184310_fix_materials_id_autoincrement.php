<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Ensure id is unsigned big integer and primary + auto_increment
        DB::statement('ALTER TABLE materials MODIFY COLUMN id BIGINT UNSIGNED NOT NULL');
        // Drop PK if not on id (ignore error if already on id)
        try { DB::statement('ALTER TABLE materials DROP PRIMARY KEY'); } catch (\Throwable $e) {}
        DB::statement('ALTER TABLE materials ADD PRIMARY KEY (id)');
        DB::statement('ALTER TABLE materials MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
    }

    public function down(): void
    {
        // No-op: we won’t revert primary/AI changes
    }
};
