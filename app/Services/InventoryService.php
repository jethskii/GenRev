<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Production;
use App\Models\Sale;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InventoryService
{
    /* =========================================================================
     |  PUBLIC API (called by Sale model events you already wired)
     * ========================================================================= */

    /** Apply the effect of a sale (create/restore or after update). */
    public function applySale(Sale $sale): void
    {
        $productId = (int) ($sale->product_id ?? 0);
        if ($productId <= 0) return;

        DB::transaction(function () use ($sale, $productId) {

            // 1) Batch-level adjustments (if a batch is specified OR kg FIFO)
            $unitType = $this->readUnitType($sale);  // 'kg' | 'pack' | 'bag'
            $qty      = $this->readQty($sale);       // numeric (int for pack/bag)

            if ($qty > 0) {
                if ($sale->production_id) {
                    // Explicit batch → adjust that single batch
                    $this->decrementBatch((int) $sale->production_id, $unitType, $qty);
                } else {
                    // No batch selected
                    if ($unitType === 'kg') {
                        // FIFO across batches for kg only
                        $this->decrementKgFifo($productId, $qty);
                    }
                    // For pack/bag we skip batch-level changes without an explicit batch,
                    // because we cannot know which batch’s packs/bags to consume.
                }
            }

            // 2) Product-level recompute (kg)
            $this->recomputeProductBalance($productId);
        });
    }

    /** Undo the effect of a sale (delete, or before update). */
    public function undoSale(Sale $sale): void
    {
        $productId = (int) ($sale->product_id ?? 0);
        if ($productId <= 0) return;

        DB::transaction(function () use ($sale, $productId) {

            $unitType = $this->readUnitType($sale);  // 'kg' | 'pack' | 'bag'
            $qty      = $this->readQty($sale);

            if ($qty > 0) {
                if ($sale->production_id) {
                    // Credit back to the exact batch previously used
                    $this->incrementBatch((int) $sale->production_id, $unitType, $qty);
                } else {
                    // No batch recorded
                    if ($unitType === 'kg') {
                        // Simple LIFO credit for kg (newest-first)
                        $this->incrementKgLifo($productId, $qty);
                    }
                    // For pack/bag we cannot safely credit without a specific batch
                }
            }

            $this->recomputeProductBalance($productId);
        });
    }

    /**
     * Product kg balance = Σ(Production.quantity) - Σ(Sale.quantity_kg or quantity).
     * Ignores soft-deleted rows and updates product stock flags + last production date.
     */
    public function recomputeProductBalance(int $productId): void
    {
        $produced = (float) DB::table('productions')
            ->whereNull('deleted_at')
            ->where('product_id', $productId)
            ->sum(DB::raw('COALESCE(quantity,0)'));

        $qtyCol = Schema::hasColumn('sales', 'quantity_kg') ? 'quantity_kg'
               : (Schema::hasColumn('sales', 'quantity')    ? 'quantity'    : null);

        $sold = 0.0;
        if ($qtyCol) {
            $sold = (float) DB::table('sales')
                ->whereNull('deleted_at')
                ->where('product_id', $productId)
                ->sum(DB::raw("COALESCE($qtyCol,0)"));
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

    /** Weekly material usage stub for InventoryController. */
    public function materialUsage(string $fromDate, string $toDate): Collection
    {
        // Keep your previous implementation or return empty until you wire recipes.
        return collect();
    }

    /* =========================================================================
     |  INTERNALS — batch adjustment helpers
     * ========================================================================= */

    /** Decrement a specific batch by unit type. */
    protected function decrementBatch(int $productionId, string $unitType, float $qty): void
    {
        /** @var Production|null $batch */
        $batch = Production::whereKey($productionId)->lockForUpdate()->first();
        if (!$batch) return;

        switch ($unitType) {
            case 'pack':
                if ($this->hasColumn('productions', 'available_pack')) {
                    $batch->available_pack = max(0, (int) ($batch->available_pack ?? 0) - (int) round($qty));
                }
                break;

            case 'bag':
                if ($this->hasColumn('productions', 'available_bag')) {
                    $batch->available_bag = max(0, (int) ($batch->available_bag ?? 0) - (int) round($qty));
                }
                break;

            default: // 'kg'
                if ($this->hasColumn('productions', 'current_inventory')) {
                    $batch->current_inventory = max(0.0, (float) ($batch->current_inventory ?? 0) - round($qty, 3));
                } else {
                    // Fallback: derive from total - other sales on this batch
                    $soldOnBatch = (float) DB::table('sales')
                        ->whereNull('deleted_at')
                        ->where('production_id', $productionId)
                        ->sum(DB::raw('COALESCE(quantity_kg, quantity, 0)'));
                    $remaining = max(0.0, (float) ($batch->quantity ?? 0) - $soldOnBatch - round($qty, 3));
                    $batch->current_inventory = $remaining;
                }
                break;
        }

        $batch->save();
    }

    /** Increment a specific batch by unit type (reverse of decrement). */
    protected function incrementBatch(int $productionId, string $unitType, float $qty): void
    {
        /** @var Production|null $batch */
        $batch = Production::whereKey($productionId)->lockForUpdate()->first();
        if (!$batch) return;

        switch ($unitType) {
            case 'pack':
                if ($this->hasColumn('productions', 'available_pack')) {
                    $batch->available_pack = (int) ($batch->available_pack ?? 0) + (int) round($qty);
                }
                break;

            case 'bag':
                if ($this->hasColumn('productions', 'available_bag')) {
                    $batch->available_bag = (int) ($batch->available_bag ?? 0) + (int) round($qty);
                }
                break;

            default: // 'kg'
                if ($this->hasColumn('productions', 'current_inventory')) {
                    $batch->current_inventory = (float) ($batch->current_inventory ?? 0) + round($qty, 3);
                } else {
                    $batch->current_inventory = (float) ($batch->quantity ?? 0); // best-effort
                }
                break;
        }

        $batch->save();
    }

    /** FIFO consume kg across batches when no batch is chosen. */
    protected function decrementKgFifo(int $productId, float $qty): void
    {
        $left = round(max(0.0, $qty), 3);
        if ($left <= 0) return;

        $batches = Production::whereNull('deleted_at')
            ->where('product_id', $productId)
            ->orderBy('production_date') // oldest first
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id','quantity','current_inventory']);

        foreach ($batches as $b) {
            $available = (float) ($b->current_inventory ?? 0);

            // If current_inventory is NULL, derive from total - sold on that batch
            if (!$available && is_null($b->current_inventory)) {
                $soldOnBatch = (float) DB::table('sales')
                    ->whereNull('deleted_at')
                    ->where('production_id', $b->id)
                    ->sum(DB::raw('COALESCE(quantity_kg, quantity, 0)'));
                $available = max(0.0, (float) ($b->quantity ?? 0) - $soldOnBatch);
            }

            if ($available <= 0) continue;

            $take = (float) min($left, $available);
            $left = round($left - $take, 3);

            $b->current_inventory = round(max(0.0, $available - $take), 3);
            $b->save();

            if ($left <= 0) break;
        }

        if ($left > 0.0005) {
            throw new \RuntimeException('Not enough total kg inventory for this product.');
        }
    }

    /** LIFO credit kg back when no batch is known (best-effort for deletes/undo). */
    protected function incrementKgLifo(int $productId, float $qty): void
    {
        $add = round(max(0.0, $qty), 3);
        if ($add <= 0) return;

        $batch = Production::whereNull('deleted_at')
            ->where('product_id', $productId)
            ->orderByDesc('production_date') // newest first
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        if ($batch) {
            $cur = (float) ($batch->current_inventory ?? 0.0);
            $batch->current_inventory = round($cur + $add, 3);
            $batch->save();
        }
    }

    /* =========================================================================
     |  UTILITIES
     * ========================================================================= */

    protected function readQty(Sale $sale): float
    {
        $unit = $this->readUnitType($sale);
        $q    = (float) ($sale->quantity_kg ?? $sale->quantity ?? 0);

        // integerize for pack/bag
        if ($unit === 'pack' || $unit === 'bag') {
            return (float) (int) round($q);
        }
        return round($q, 3);
    }

    protected function readUnitType(Sale $sale): string
    {
        $col = Schema::hasColumn('sales','unit_type') ? 'unit_type'
             : (Schema::hasColumn('sales','unit')     ? 'unit'      : null);

        $val = $col ? strtolower((string) ($sale->{$col} ?? '')) : '';
        return in_array($val, ['kg','pack','bag'], true) ? $val : 'kg';
    }

    protected function hasColumn(string $table, string $column): bool
    {
        static $cache = [];
        $key = "$table.$column";
        if (!array_key_exists($key, $cache)) {
            $cache[$key] = Schema::hasColumn($table, $column);
        }
        return $cache[$key];
    }
}
