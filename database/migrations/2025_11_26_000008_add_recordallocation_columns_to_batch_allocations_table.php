<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * batch_allocations is used by two different features with incompatible column needs:
     *  - AllocationService/BatchAllocationController: batch_id, order_item_id, allocated_qty,
     *    locked_by_admin, override_reason, approved_by, approved_at (already present).
     *  - Sale::recordAllocation() / SalesOrderItem::recordAllocation(): a simpler FIFO deduction
     *    log using sale_id (or order_item_id via the SalesOrderItem relation), production_id,
     *    mode, quantity_value - none of which existed, and batch_id/allocated_qty were NOT NULL
     *    so those inserts would also fail on the missing value, not just the missing columns.
     */
    public function up(): void
    {
        Schema::table('batch_allocations', function (Blueprint $table) {
            $table->foreignId('batch_id')->nullable()->change();
            $table->foreignId('order_item_id')->nullable()->change();
            $table->unsignedInteger('allocated_qty')->nullable()->change();

            if (!Schema::hasColumn('batch_allocations', 'sale_id')) {
                // sales.id is INT UNSIGNED, not bigint
                $table->unsignedInteger('sale_id')->nullable()->after('id');
                $table->foreign('sale_id')->references('id')->on('sales')->nullOnDelete();
            }
            if (!Schema::hasColumn('batch_allocations', 'production_id')) {
                $table->foreignId('production_id')->nullable()->constrained('productions')->nullOnDelete();
            }
            if (!Schema::hasColumn('batch_allocations', 'mode')) {
                $table->string('mode', 20)->nullable();
            }
            if (!Schema::hasColumn('batch_allocations', 'quantity_value')) {
                $table->decimal('quantity_value', 14, 3)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('batch_allocations', function (Blueprint $table) {
            if (Schema::hasColumn('batch_allocations', 'mode')) {
                $table->dropColumn('mode');
            }
            if (Schema::hasColumn('batch_allocations', 'quantity_value')) {
                $table->dropColumn('quantity_value');
            }
            if (Schema::hasColumn('batch_allocations', 'production_id')) {
                $table->dropConstrainedForeignId('production_id');
            }
            if (Schema::hasColumn('batch_allocations', 'sale_id')) {
                $table->dropForeign(['sale_id']);
                $table->dropColumn('sale_id');
            }
        });
    }
};
