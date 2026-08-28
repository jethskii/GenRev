<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * sales had zero indexes beyond its primary key. product_id and production_id are
     * filtered in nearly every balance/FIFO/availability query in the app (Sale::
     * availableForMode/availableKg, InventoryService::recomputeProductBalance,
     * allocateAndDeduct, every dashboard revenue/trend query) and date/order_date drive
     * every dashboard date-range filter - all were doing full table scans.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (!$this->indexExists('sales', 'sales_product_id_index')) {
                $table->index('product_id', 'sales_product_id_index');
            }
            if (Schema::hasColumn('sales', 'production_id') && !$this->indexExists('sales', 'sales_production_id_index')) {
                $table->index('production_id', 'sales_production_id_index');
            }
            if (Schema::hasColumn('sales', 'date') && !$this->indexExists('sales', 'sales_date_index')) {
                $table->index('date', 'sales_date_index');
            }
            if (Schema::hasColumn('sales', 'order_date') && !$this->indexExists('sales', 'sales_order_date_index')) {
                $table->index('order_date', 'sales_order_date_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            foreach (['sales_product_id_index', 'sales_production_id_index', 'sales_date_index', 'sales_order_date_index'] as $name) {
                if ($this->indexExists('sales', $name)) {
                    $table->dropIndex($name);
                }
            }
        });
    }

    private function indexExists(string $table, string $name): bool
    {
        return Schema::hasIndex($table, $name);
    }
};
