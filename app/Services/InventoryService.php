<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Production;
use App\Models\Sale;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class InventoryService
{
    public const UNIT_KG   = 'kg';
    public const UNIT_PACK = 'pack';
    public const UNIT_BAG  = 'bag';

    /* =========================================================================
     |  PUBLIC API
     * ========================================================================= */

    /**
     * Apply the effect of a sale (create or restore or after update).
     */
    public function applySale(Sale $sale): void
    {
        $productId = (int) ($sale->product_id ?? 0);
        if ($productId <= 0) {
            return;
        }

        DB::transaction(function () use ($sale, $productId): void {
            $unitType = $this->readUnitType($sale);  // kg | pack | bag
            $qty      = $this->readQty($sale);       // numeric (int for pack/bag)

            if ($qty > 0) {
                if ($sale->production_id) {
                    // Explicit batch. Adjust that single batch.
                    $this->decrementBatch((int) $sale->production_id, $unitType, $qty);
                } else {
                    // No batch selected.
                    if ($unitType === self::UNIT_KG) {
                        // FIFO across batches for kg only.
                        $this->decrementKgFifo($productId, $qty);
                    }
                    // For pack/bag we skip batch level changes without an explicit batch,
                    // because we cannot know which batch packs or bags to consume.
                }
            }

            // Product level recompute (kg).
            $this->recomputeProductBalance($productId);
        });
    }

    /**
     * Undo the effect of a sale (delete or before update).
     */
    public function undoSale(Sale $sale): void
    {
        $productId = (int) ($sale->product_id ?? 0);
        if ($productId <= 0) {
            return;
        }

        DB::transaction(function () use ($sale, $productId): void {
            $unitType = $this->readUnitType($sale);  // kg | pack | bag
            $qty      = $this->readQty($sale);

            if ($qty > 0) {
                if ($sale->production_id) {
                    // Credit back to the exact batch previously used.
                    $this->incrementBatch((int) $sale->production_id, $unitType, $qty);
                } else {
                    // No batch recorded.
                    if ($unitType === self::UNIT_KG) {
                        // Simple LIFO credit for kg (newest first).
                        $this->incrementKgLifo($productId, $qty);
                    }
                    // For pack/bag we cannot safely credit without a specific batch.
                }
            }

            $this->recomputeProductBalance($productId);
        });
    }

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

    /* =========================================================================
     |  INTERNALS. Batch adjustment helpers
     * ========================================================================= */

    /**
     * Decrement a specific batch by unit type.
     */
    protected function decrementBatch(int $productionId, string $unitType, float $qty): void
    {
        /** @var Production|null $batch */
        $batch = Production::whereKey($productionId)->lockForUpdate()->first();
        if ($batch === null) {
            return;
        }

        switch ($unitType) {
            case self::UNIT_PACK:
                if ($this->hasColumn('productions', 'available_pack')) {
                    $batch->available_pack = max(
                        0,
                        (int) ($batch->available_pack ?? 0) - (int) round($qty)
                    );
                }
                break;

            case self::UNIT_BAG:
                if ($this->hasColumn('productions', 'available_bag')) {
                    $batch->available_bag = max(
                        0,
                        (int) ($batch->available_bag ?? 0) - (int) round($qty)
                    );
                }
                break;

            default: // kg
                if ($this->hasColumn('productions', 'current_inventory')) {
                    $batch->current_inventory = max(
                        0.0,
                        (float) ($batch->current_inventory ?? 0) - round($qty, 3)
                    );
                } else {
                    // Fallback. Derive from total minus other sales on this batch.
                    $soldOnBatch = (float) DB::table('sales')
                        ->whereNull('deleted_at')
                        ->where('production_id', $productionId)
                        ->sum(DB::raw('COALESCE(quantity_kg, quantity, 0)'));

                    $remaining = max(
                        0.0,
                        (float) ($batch->quantity ?? 0) - $soldOnBatch - round($qty, 3)
                    );

                    $batch->current_inventory = $remaining;
                }
                break;
        }

        $batch->save();
    }

    /**
     * Increment a specific batch by unit type. Reverse of decrement.
     */
    protected function incrementBatch(int $productionId, string $unitType, float $qty): void
    {
        /** @var Production|null $batch */
        $batch = Production::whereKey($productionId)->lockForUpdate()->first();
        if ($batch === null) {
            return;
        }

        switch ($unitType) {
            case self::UNIT_PACK:
                if ($this->hasColumn('productions', 'available_pack')) {
                    $batch->available_pack = (int) ($batch->available_pack ?? 0)
                        + (int) round($qty);
                }
                break;

            case self::UNIT_BAG:
                if ($this->hasColumn('productions', 'available_bag')) {
                    $batch->available_bag = (int) ($batch->available_bag ?? 0)
                        + (int) round($qty);
                }
                break;

            default: // kg
                if ($this->hasColumn('productions', 'current_inventory')) {
                    $batch->current_inventory = (float) ($batch->current_inventory ?? 0)
                        + round($qty, 3);
                } else {
                    // Best effort if the column does not exist.
                    $batch->current_inventory = (float) ($batch->quantity ?? 0);
                }
                break;
        }

        $batch->save();
    }

    /**
     * FIFO consume kg across batches when no batch is chosen.
     */
    protected function decrementKgFifo(int $productId, float $qty): void
    {
        $left = round(max(0.0, $qty), 3);
        if ($left <= 0) {
            return;
        }

        $batches = Production::whereNull('deleted_at')
            ->where('product_id', $productId)
            ->orderBy('production_date') // oldest first
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id', 'quantity', 'current_inventory']);

        foreach ($batches as $batch) {
            $available = (float) ($batch->current_inventory ?? 0.0);

            // If current_inventory is null, derive from total minus sold on this batch.
            if ($batch->current_inventory === null) {
                $soldOnBatch = (float) DB::table('sales')
                    ->whereNull('deleted_at')
                    ->where('production_id', $batch->id)
                    ->sum(DB::raw('COALESCE(quantity_kg, quantity, 0)'));

                $available = max(
                    0.0,
                    (float) ($batch->quantity ?? 0) - $soldOnBatch
                );
            }

            if ($available <= 0) {
                continue;
            }

            $take = (float) min($left, $available);
            $left = round($left - $take, 3);

            $batch->current_inventory = round(max(0.0, $available - $take), 3);
            $batch->save();

            if ($left <= 0) {
                break;
            }
        }

        if ($left > 0.0005) {
            throw new \RuntimeException('Not enough total kg inventory for this product.');
        }
    }

    /**
     * LIFO credit kg back when no batch is known.
     * Best effort for deletes or undo operations.
     */
    protected function incrementKgLifo(int $productId, float $qty): void
    {
        $add = round(max(0.0, $qty), 3);
        if ($add <= 0) {
            return;
        }

        $batch = Production::whereNull('deleted_at')
            ->where('product_id', $productId)
            ->orderByDesc('production_date') // newest first
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        if ($batch) {
            $current = (float) ($batch->current_inventory ?? 0.0);
            $batch->current_inventory = round($current + $add, 3);
            $batch->save();
        }
    }

    /* =========================================================================
     |  UTILITIES
     * ========================================================================= */

    /**
     * Normalizes sale quantity based on unit type.
     * For pack and bag it always returns an integer value.
     */
    protected function readQty(Sale $sale): float
    {
        $unit = $this->readUnitType($sale);

        $rawQty = (float) ($sale->quantity_kg ?? $sale->quantity ?? 0);

        if ($unit === self::UNIT_PACK || $unit === self::UNIT_BAG) {
            return (float) (int) round($rawQty);
        }

        return round($rawQty, 3);
    }

    /**
     * Detects unit type column and normalizes value.
     */
    protected function readUnitType(Sale $sale): string
    {
        $column = Schema::hasColumn('sales', 'unit_type')
            ? 'unit_type'
            : (Schema::hasColumn('sales', 'unit') ? 'unit' : null);

        $val = $column
            ? strtolower((string) ($sale->{$column} ?? ''))
            : '';

        return in_array($val, [self::UNIT_KG, self::UNIT_PACK, self::UNIT_BAG], true)
            ? $val
            : self::UNIT_KG;
    }

    /**
     * Cached Schema::hasColumn lookup.
     */
    private function hasColumn(string $table, string $column): bool
    {
        static $cache = [];

        $key = $table . '.' . $column;

        if (!array_key_exists($key, $cache)) {
            $cache[$key] = Schema::hasColumn($table, $column);
        }

        return $cache[$key];
    }
}
