<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            if (!Schema::hasColumn('productions', 'parent_product_id')) {
                $table->foreignId('parent_product_id')->nullable()->constrained('products')->nullOnDelete();
            }
            if (!Schema::hasColumn('productions', 'product_name_snapshot')) {
                $table->string('product_name_snapshot')->nullable();
            }
            if (!Schema::hasColumn('productions', 'unit_price_pack')) {
                $table->decimal('unit_price_pack', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('productions', 'unit_price_bag')) {
                $table->decimal('unit_price_bag', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('productions', 'available_pack')) {
                $table->integer('available_pack')->default(0);
            }
            if (!Schema::hasColumn('productions', 'available_bag')) {
                $table->integer('available_bag')->default(0);
            }
            if (!Schema::hasColumn('productions', 'image_disk')) {
                $table->string('image_disk')->nullable();
            }
            if (!Schema::hasColumn('productions', 'image_medium_path')) {
                $table->string('image_medium_path')->nullable();
            }
            if (!Schema::hasColumn('productions', 'image_thumb_path')) {
                $table->string('image_thumb_path')->nullable();
            }
            if (!Schema::hasColumn('productions', 'remarks')) {
                $table->string('remarks', 500)->nullable();
            }
            if (!Schema::hasColumn('productions', 'archived_reason')) {
                $table->string('archived_reason')->nullable();
            }
            if (!Schema::hasColumn('productions', 'purge_at')) {
                $table->timestamp('purge_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            foreach ([
                'product_name_snapshot', 'unit_price_pack', 'unit_price_bag', 'available_pack',
                'available_bag', 'image_disk', 'image_medium_path', 'image_thumb_path',
                'remarks', 'archived_reason', 'purge_at',
            ] as $col) {
                if (Schema::hasColumn('productions', $col)) {
                    $table->dropColumn($col);
                }
            }
            if (Schema::hasColumn('productions', 'parent_product_id')) {
                $table->dropConstrainedForeignId('parent_product_id');
            }
        });
    }
};
