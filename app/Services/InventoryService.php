<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class InventoryService
{
    /* =========================================================================
     |  PUBLIC API
     * ========================================================================= */

    /**
     * Product kg balance is:
     *   sum(Production.quantity) minus sum(Sale.quantity_kg or quantity)
     * Ignores soft deleted rows and updates product stock flags and last production date.
     */
    public function recomputeProductBalance(int $productId): void
    {
        $produced = (float) DB::table('productions')
            ->whereNull('deleted_at')
            ->where('product_id', $productId)
            ->sum(DB::raw('COALESCE(quantity, 0)'));

        $qtyCol = Schema::hasColumn('sales', 'quantity_kg')
            ? 'quantity_kg'
            : (Schema::hasColumn('sales', 'quantity') ? 'quantity' : null);

        $sold = 0.0;
        if ($qtyCol !== null) {
            $sold = (float) DB::table('sales')
                ->whereNull('deleted_at')
                ->where('product_id', $productId)
                ->sum(DB::raw("COALESCE($qtyCol, 0)"));
        }

        $balance = max(0.0, $produced - $sold);

        $latestProdDate = DB::table('productions')
            ->whereNull('deleted_at')
            ->where('product_id', $productId)
            ->max('production_date');

        DB::table('products')
            ->where('id', $productId)
            ->update([
                'quantity'        => $balance,
                'stock_status'    => $balance > 0 ? 'in_stock' : 'out_of_stock',
                'production_date' => $latestProdDate,
            ]);
    }

    /**
     * Weekly material usage stub for InventoryController.
     * Replace with your implementation when recipes are ready.
     */
    public function materialUsage(string $fromDate, string $toDate): Collection
    {
        return collect();
    }
}
