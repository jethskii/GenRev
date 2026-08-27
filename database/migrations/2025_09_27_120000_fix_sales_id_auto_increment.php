<?php

// database/migrations/2025_09_27_120000_fix_sales_id_auto_increment.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('sales')) return;

        // Skip entirely if id is already a proper auto-increment primary key (e.g. fresh install)
        $idCol = DB::selectOne("
            SELECT EXTRA, COLUMN_KEY FROM information_schema.columns
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sales' AND COLUMN_NAME = 'id'
        ");
        if ($idCol && str_contains($idCol->EXTRA, 'auto_increment') && $idCol->COLUMN_KEY === 'PRI') {
            return;
        }

        // If some other column is auto_increment, drop that first
        $auto = DB::table('information_schema.columns')
            ->select('COLUMN_NAME')
            ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
            ->where('TABLE_NAME', 'sales')
            ->where('EXTRA', 'like', '%auto_increment%')
            ->value('COLUMN_NAME');

        if ($auto && $auto !== 'id') {
            DB::statement("ALTER TABLE sales MODIFY COLUMN {$auto} INT UNSIGNED NOT NULL");
        }

        // Ensure id is NOT NULL and integer
        DB::statement('ALTER TABLE sales MODIFY COLUMN id INT UNSIGNED NOT NULL');

        // Add PK on id if not present or on another column
        $pkCol = DB::table('information_schema.statistics')
            ->select('COLUMN_NAME')
            ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
            ->where('TABLE_NAME', 'sales')
            ->where('INDEX_NAME', 'PRIMARY')
            ->value('COLUMN_NAME');

        if (!$pkCol) {
            DB::statement('ALTER TABLE sales ADD PRIMARY KEY (id)');
        } elseif ($pkCol !== 'id') {
            DB::statement('ALTER TABLE sales DROP PRIMARY KEY');
            DB::statement('ALTER TABLE sales ADD PRIMARY KEY (id)');
        }

        // Finally set AUTO_INCREMENT on id
        DB::statement('ALTER TABLE sales MODIFY COLUMN id INT UNSIGNED NOT NULL AUTO_INCREMENT');
    }

    public function down(): void
    {
        // no-op
    }
};
