<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->constrained('products')->nullOnDelete();
            }
            if (!Schema::hasColumn('products', 'default_price')) {
                $table->decimal('default_price', 12, 2)->nullable();
            }
            if (!Schema::hasColumn('products', 'last_cost_date')) {
                $table->date('last_cost_date')->nullable();
            }
            if (!Schema::hasColumn('products', 'current_inventory')) {
                $table->decimal('current_inventory', 14, 3)->default(0);
            }
            if (!Schema::hasColumn('products', 'temp_requirements')) {
                $table->string('temp_requirements')->nullable();
            }
            if (!Schema::hasColumn('products', 'storage_zone')) {
                $table->string('storage_zone')->nullable();
            }
            if (!Schema::hasColumn('products', 'yield_rate')) {
                $table->decimal('yield_rate', 8, 4)->nullable();
            }
            if (!Schema::hasColumn('products', 'standard_batch_size')) {
                $table->decimal('standard_batch_size', 14, 3)->nullable();
            }
            if (!Schema::hasColumn('products', 'lead_time_days')) {
                $table->integer('lead_time_days')->nullable();
            }
            if (!Schema::hasColumn('products', 'min_run_qty')) {
                $table->decimal('min_run_qty', 14, 3)->nullable();
            }
            if (!Schema::hasColumn('products', 'max_run_qty')) {
                $table->decimal('max_run_qty', 14, 3)->nullable();
            }
            if (!Schema::hasColumn('products', 'line_constraints')) {
                $table->json('line_constraints')->nullable();
            }
            if (!Schema::hasColumn('products', 'image_disk')) {
                $table->string('image_disk')->nullable();
            }
            if (!Schema::hasColumn('products', 'image_path')) {
                $table->string('image_path')->nullable();
            }
            if (!Schema::hasColumn('products', 'image_medium_path')) {
                $table->string('image_medium_path')->nullable();
            }
            if (!Schema::hasColumn('products', 'image_thumb_path')) {
                $table->string('image_thumb_path')->nullable();
            }
            if (!Schema::hasColumn('products', 'card_image_url')) {
                $table->string('card_image_url')->nullable();
            }
            if (!Schema::hasColumn('products', 'card_image_srcset')) {
                $table->text('card_image_srcset')->nullable();
            }
            if (!Schema::hasColumn('products', 'image_original_url')) {
                $table->string('image_original_url')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            foreach ([
                'default_price', 'last_cost_date', 'current_inventory', 'temp_requirements',
                'storage_zone', 'yield_rate', 'standard_batch_size', 'lead_time_days',
                'min_run_qty', 'max_run_qty', 'line_constraints', 'image_disk', 'image_path',
                'image_medium_path', 'image_thumb_path', 'card_image_url', 'card_image_srcset',
                'image_original_url',
            ] as $col) {
                if (Schema::hasColumn('products', $col)) {
                    $table->dropColumn($col);
                }
            }
            if (Schema::hasColumn('products', 'parent_id')) {
                $table->dropConstrainedForeignId('parent_id');
            }
        });
    }
};
