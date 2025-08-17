<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_recipes', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->onDelete('cascade');

            $table->foreignId('ingredient_id')
                  ->constrained('materials') // ⚡ assuming "materials" table stores ingredients
                  ->onDelete('cascade');

            // Recipe details
            $table->decimal('qty', 10, 3)->default(0); // kg, grams, etc.
            $table->decimal('unit_price_snapshot', 12, 2)->default(0); // snapshot of cost/unit at time of saving

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_recipes');
    }
};
