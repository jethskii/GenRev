<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // MySQL-only repair migration; Postgres/other drivers get correct types from create_materials_table.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::hasTable('materials')) {
            // Ensure primary key/AI id
            if (!Schema::hasColumn('materials', 'id')) {
                Schema::table('materials', function (Blueprint $table) {
                    $table->bigIncrements('id');
                });
            } else {
                // MySQL/MariaDB only: ensure it's AI PK
                try {
                    DB::statement('ALTER TABLE materials MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
                } catch (\Throwable $e) {
                    // SQLite or already OK — ignore
                }
            }

            Schema::table('materials', function (Blueprint $table) {
                if (!Schema::hasColumn('materials', 'material_name')) {
                    $table->string('material_name', 255)->unique()->after('id');
                }
                if (!Schema::hasColumn('materials', 'category')) {
                    $table->string('category', 100)->nullable()->after('material_name');
                }
                if (!Schema::hasColumn('materials', 'unit')) {
                    $table->enum('unit', [
                        'kg','g','lbs','pcs','pkg','box','bag','roll','tray','lt','ml','m3'
                    ])->default('kg')->after('category');
                }
                if (!Schema::hasColumn('materials', 'sku')) {
                    $table->string('sku', 120)->nullable()->after('unit');
                }
                if (!Schema::hasColumn('materials', 'unit_price')) {
                    $table->decimal('unit_price', 12, 2)->default(0.00)->after('sku');
                }
                if (!Schema::hasColumn('materials', 'quantity_kg')) {
                    $table->decimal('quantity_kg', 14, 3)->default(0.000)->after('unit_price');
                }
                if (!Schema::hasColumn('materials', 'min_stock_kg')) {
                    $table->decimal('min_stock_kg', 14, 3)->nullable()->after('quantity_kg');
                }
                if (!Schema::hasColumn('materials', 'created_at')) {
                    $table->timestamps();
                }
            });

            // Add uniques safely (ignore if duplicates exist)
            try { DB::statement('ALTER TABLE materials ADD UNIQUE `materials_material_name_unique` (`material_name`)'); } catch (\Throwable $e) {}
            try { DB::statement('ALTER TABLE materials ADD UNIQUE `materials_sku_unique` (`sku`)'); } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        // No-op: keep repairs
    }
};
