<?php
// database/migrations/2025_09_25_000001_fix_productions_schema.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Ensure 'id' is BIGINT UNSIGNED AUTO_INCREMENT
        DB::statement("ALTER TABLE `productions`
            MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT");

        // Align product_id to unsignedBigInteger (to match products.id)
        if (Schema::hasColumn('productions', 'product_id')) {
            Schema::table('productions', function (Blueprint $table) {
                $table->unsignedBigInteger('product_id')->change();
            });
        }

        // Indexes / keys
        Schema::table('productions', function (Blueprint $table) {
            if (! $this->indexExists('productions', 'productions_product_id_index')) {
                $table->index('product_id', 'productions_product_id_index');
            }
            if (Schema::hasColumn('productions', 'batch_number') &&
                ! $this->indexExists('productions', 'productions_batch_number_index')) {
                $table->index('batch_number', 'productions_batch_number_index');
            }
        });
    }

    public function down(): void
    {
        // no-op; don’t downgrade PK/AI in down()
    }

    private function indexExists(string $table, string $name): bool
    {
        $schema = Schema::getConnection()->getDoctrineSchemaManager();
        foreach ($schema->listTableIndexes($table) as $idx) {
            if ($idx->getName() === $name) return true;
        }
        return false;
    }
};
