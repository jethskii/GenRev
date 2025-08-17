<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Production;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /** Apply a sale by reserving/decrementing inventory (FIFO unless a batch is set). */
    public function applySale(Sale $sale): void
    {
        DB::transaction(function () use ($sale) {
            $qty  = (float) ($sale->quantity ?? $sale->quantity_kg ?? 0);
            if ($qty <= 0) return;

            // Choose candidate batches
            $q = Production::where('product_id', $sale->product_id)
                ->orderBy('production_date')->orderBy('id'); // FIFO

            if (!empty($sale->production_id)) {
                $q->where('id', $sale->production_id);
            }

            // Lock rows to prevent race conditions
            $batches = $q->lockForUpdate()->get();

            $left = $qty;

            foreach ($batches as $b) {
                // Compute available for this batch
                $soldForBatch = (float) Sale::where('production_id', $b->id)
                    ->where('id', '!=', $sale->id) // exclude this sale in case of update
                    ->sum('quantity');

                // If current_inventory is null, derive it from quantity - sold
                $available = is_null($b->current_inventory)
                    ? max(0.0, (float)$b->quantity - $soldForBatch)
                    : max(0.0, (float)$b->current_inventory);

                if ($available <= 0) continue;

                $take = min($left, $available);
                $left -= $take;

                // Update current_inventory
                if (is_null($b->current_inventory)) {
                    // initialize & reduce
                    $b->current_inventory = max(0.0, (float)$b->quantity - $soldForBatch - $take);
                } else {
                    $b->current_inventory = max(0.0, (float)$b->current_inventory - $take);
                }
                $b->save();

                if ($left <= 0) break;
            }

            if ($left > 0) {
                throw new \RuntimeException('Not enough total inventory for product.');
            }

            $this->recomputeProductBalance($sale->product_id);
        });
    }

    /** Reverse effect of a sale (used on soft delete / update rollbacks). */
    public function undoSale(Sale $sale): void
    {
        DB::transaction(function () use ($sale) {
            $qty = (float) ($sale->quantity ?? $sale->quantity_kg ?? 0);
            if ($qty <= 0) return;

            // Strategy: credit back to the specified batch if present; otherwise LIFO
            $q = Production::where('product_id', $sale->product_id)
                ->orderByDesc('production_date')->orderByDesc('id');

            if (!empty($sale->production_id)) {
                $q->where('id', $sale->production_id);
            }

            $batches = $q->lockForUpdate()->get();
            $left = $qty;

            foreach ($batches as $b) {
                $b->current_inventory = (float) ($b->current_inventory ?? 0) + $left;
                $b->save();
                $left = 0;
                break;
            }

            $this->recomputeProductBalance($sale->product_id);
        });
    }

    public function recomputeProductBalance(int $productId): void
    {
        $produced = (float) Production::where('product_id', $productId)->sum('quantity');
        $sold     = (float) Sale::where('product_id', $productId)->sum('quantity');
        $balance  = max(0.0, $produced - $sold);

        Product::where('id', $productId)->update([
            'quantity'     => $balance,
            'stock_status' => $balance > 0 ? 'in_stock' : 'out_of_stock',
        ]);
    }
}
