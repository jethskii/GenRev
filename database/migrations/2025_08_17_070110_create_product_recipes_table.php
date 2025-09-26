<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_recipes', function (Blueprint $table) {
            $table->bigIncrements('id'); // <-- AUTO INCREMENT PK
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('ingredient_id'); // points to materials.id
            $table->decimal('qty', 14, 3)->default(0);
            $table->decimal('unit_price_snapshot', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'ingredient_id']); // one line per material
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('ingredient_id')->references('id')->on('materials')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_recipes');
    }
};
