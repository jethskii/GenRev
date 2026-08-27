<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            if (!Schema::hasColumn('productions', 'product_id')) {
                $table->foreignId('product_id')->after('id')->constrained('products')->restrictOnDelete()->cascadeOnUpdate();
            }
            if (!Schema::hasColumn('productions', 'batch_number')) {
                $table->string('batch_number')->nullable()->after('product_id');
            }
            if (!Schema::hasColumn('productions', 'forecasted_demand')) {
                $table->decimal('forecasted_demand', 12, 3)->default(0);
            }
            if (!Schema::hasColumn('productions', 'current_inventory')) {
                $table->decimal('current_inventory', 12, 3)->default(0);
            }
            if (!Schema::hasColumn('productions', 'quantity')) {
                $table->decimal('quantity', 12, 3)->default(0);
            }
            if (!Schema::hasColumn('productions', 'unit_cost')) {
                $table->decimal('unit_cost', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('productions', 'production_date')) {
                $table->date('production_date')->nullable();
            }
            if (!Schema::hasColumn('productions', 'expiration_date')) {
                $table->date('expiration_date')->nullable();
            }
            if (!Schema::hasColumn('productions', 'image_path')) {
                $table->string('image_path')->nullable();
            }
            if (!Schema::hasColumn('productions', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('productions', function (Blueprint $table) {
            try { $table->index(['product_id', 'production_date'], 'prod_product_date_idx'); } catch (\Throwable $e) {}
        });
    }

    public function down(): void
    {
        // Non-destructive on purpose
    }
};
