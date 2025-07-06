<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('productions', function (Blueprint $table) {
            $table->id();
            $table->string('product_name');
            $table->string('batch_number');
            $table->date('production_date');
            $table->unsignedInteger('quantity_produced');
            $table->string('status')->default('Pending'); // e.g. Pending, Completed
            $table->timestamps(); // created_at, updated_at
        });
    }

    public function down(): void {
        Schema::dropIfExists('productions');
    }
};
