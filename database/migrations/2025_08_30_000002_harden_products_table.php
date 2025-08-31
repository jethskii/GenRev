<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'product_name')) $table->string('product_name')->nullable();
            if (!Schema::hasColumn('products', 'quantity')) $table->decimal('quantity', 12, 3)->default(0);
            if (!Schema::hasColumn('products', 'forecasted_demand')) $table->decimal('forecasted_demand', 12, 3)->default(0);
            if (!Schema::hasColumn('products', 'unit_cost')) $table->decimal('unit_cost', 12, 2)->default(0);
            if (!Schema::hasColumn('products', 'shelf_life_days')) $table->integer('shelf_life_days')->nullable();
            if (!Schema::hasColumn('products', 'production_date')) $table->date('production_date')->nullable();
            if (!Schema::hasColumn('products', 'category')) $table->string('category')->nullable();
            if (!Schema::hasColumn('products', 'status')) $table->string('status')->default('active');
            if (!Schema::hasColumn('products', 'unit')) $table->string('unit')->default('kg');
            if (!Schema::hasColumn('products', 'product_code')) $table->string('product_code')->nullable();
            if (!Schema::hasColumn('products', 'sku')) $table->string('sku')->nullable();
            if (!Schema::hasColumn('products', 'price')) $table->decimal('price', 12, 2)->nullable();
            if (!Schema::hasColumn('products', 'image')) $table->string('image')->nullable();
            if (!Schema::hasColumn('products', 'stock_status')) $table->string('stock_status')->nullable();
        });
    }

    public function down(): void
    {
        // Non-destructive on purpose
    }
};
