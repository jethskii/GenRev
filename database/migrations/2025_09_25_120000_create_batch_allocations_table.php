<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('batch_allocations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('batch_id')->constrained('batches')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('order_item_id')->constrained('sales_order_items')->cascadeOnUpdate()->cascadeOnDelete();

            $table->unsignedInteger('allocated_qty');

            $table->boolean('locked_by_admin')->default(false);
            $table->string('override_reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->unique(['batch_id', 'order_item_id']); // prevent dup rows per item
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_allocations');
    }
};
