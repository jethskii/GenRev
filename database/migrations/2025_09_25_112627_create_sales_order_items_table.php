<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();

            // Parent reference
            $table->foreignId('sales_order_id')
                  ->constrained('sales_orders')
                  ->onDelete('cascade');

            // Product / production references
            $table->foreignId('product_id')
                  ->nullable()
                  ->constrained('products')
                  ->onDelete('set null');

            $table->foreignId('production_id')
                  ->nullable()
                  ->constrained('productions')
                  ->onDelete('set null');

            // Item details
            $table->string('description')->nullable();
            $table->decimal('quantity', 12, 3)->default(0);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total_price', 14, 2)->default(0);

            $table->date('delivery_date')->nullable();
            $table->string('status', 50)->default('Pending');

            $table->timestamps();
            $table->softDeletes();

            // Useful indexes
            $table->index(['sales_order_id']);
            $table->index(['product_id']);
            $table->index(['production_id']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_items');
    }
};
