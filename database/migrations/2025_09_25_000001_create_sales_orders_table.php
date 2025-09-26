<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();

            // Human-readable order number (SO-YYYYMMDD-###)
            $table->string('order_number')->unique();

            // Customer info
            $table->string('customer_name')->nullable();
            $table->date('order_date')->nullable();

            // Status
            $table->string('status', 50)->default('Completed');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['order_date']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
