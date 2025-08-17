<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_code')->unique();
            $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('production_id')->nullable()->constrained('productions')->cascadeOnUpdate()->nullOnDelete();

            $table->dateTime('produced_at');
            $table->dateTime('expiry_date');
            $table->unsignedInteger('shelf_life_days')->default(0);

            $table->unsignedInteger('qty_total');
            $table->unsignedInteger('qty_available');
            $table->unsignedInteger('qty_reserved')->default(0);

            $table->string('status', 40)->default('CREATED');
            $table->unsignedInteger('dispatch_sequence')->default(0);

            $table->softDeletes();
            $table->timestamps();

            $table->index(['product_id', 'status']);
            $table->index(['expiry_date']);
            $table->index(['dispatch_sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
