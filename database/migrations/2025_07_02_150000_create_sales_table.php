<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->increments('id');

            $table->string('invoice_number')->nullable();
            $table->string('order_number')->nullable();
            $table->date('order_date')->nullable();

            $table->string('product')->nullable();
            $table->string('product_name')->nullable();
            $table->string('type_label')->nullable();
            $table->string('unit_type')->nullable();

            $table->decimal('quantity', 12, 3)->default(0);
            $table->decimal('quantity_kg', 14, 3)->nullable();
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('total', 14, 2)->nullable();
            $table->decimal('total_price', 14, 2)->nullable();

            $table->string('customer_name')->nullable();
            $table->text('notes')->nullable();

            $table->date('production_date')->nullable();
            $table->date('expiration_date')->nullable();
            $table->date('date')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
