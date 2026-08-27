<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            // align types if needed
            $table->unsignedBigInteger('product_id')->change();

            // unique batch per product (prevents race-condition duplicates)
            if (! $this->indexExists('productions', 'productions_product_batch_unique')) {
                $table->unique(['product_id', 'batch_number'], 'productions_product_batch_unique');
            }

            // helpful index for queries
            if (! $this->indexExists('productions', 'productions_production_date_index')) {
                $table->index('production_date', 'productions_production_date_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->dropIndex('productions_production_date_index');
            $table->dropUnique('productions_product_batch_unique');
        });
    }

    private function indexExists(string $table, string $name): bool
    {
        return Schema::hasIndex($table, $name);
    }
};
