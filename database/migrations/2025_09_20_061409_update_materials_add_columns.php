<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('materials', function (Blueprint $table) {
            if (!Schema::hasColumn('materials','category')) {
                $table->string('category',100)->nullable();
            }
            if (!Schema::hasColumn('materials','unit')) {
                $table->string('unit',20)->default('kg');
            }
            if (!Schema::hasColumn('materials','unit_price')) {
                $table->decimal('unit_price',12,2)->default(0);
            }
            if (!Schema::hasColumn('materials','quantity_kg')) {
                $table->decimal('quantity_kg',14,3)->default(0);
            }
            if (!Schema::hasColumn('materials','min_stock_kg')) {
                $table->decimal('min_stock_kg',14,3)->nullable();
            }
            if (!Schema::hasColumn('materials','sku')) {
                $table->string('sku',120)->nullable();
            }
            if (!Schema::hasColumn('materials','created_at')) {
                $table->timestamp('created_at')->useCurrent();
            }
            if (!Schema::hasColumn('materials','updated_at')) {
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            }
        });
    }

    public function down(): void {
        // optional: drop columns if needed
    }
};
