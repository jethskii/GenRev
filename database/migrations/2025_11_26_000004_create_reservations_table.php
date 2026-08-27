<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->date('reserved_date');
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('production_id')->nullable()->constrained('productions')->nullOnDelete();
            $table->integer('units');
            $table->string('unit_type', 20); // pack | bag
            $table->string('type_label')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('reference_code')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('reserved'); // reserved | converted | cancelled | expired
            $table->unsignedInteger('sale_id')->nullable(); // sales.id is INT UNSIGNED, not bigint
            $table->foreign('sale_id')->references('id')->on('sales')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();

            $table->index('reserved_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
