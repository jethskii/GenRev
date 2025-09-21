<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('materials')) {
            Schema::create('materials', function (Blueprint $table) {
                $table->bigIncrements('id');

                $table->string('material_name', 255)->unique();
                $table->string('category', 100)->nullable();     // Blade expects it
                $table->enum('unit', [
                    'kg','g','lbs','pcs','pkg','box','bag','roll','tray','lt','ml','m3'
                ])->default('kg');

                $table->string('sku', 120)->nullable()->unique();
                $table->decimal('unit_price', 12, 2)->default(0.00);
                $table->decimal('quantity_kg', 14, 3)->default(0.000);
                $table->decimal('min_stock_kg', 14, 3)->nullable();

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
