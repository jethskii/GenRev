<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Production;
use App\Models\Sale;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema; // <-- required

class InventoryService
{
    public function applySale(Sale $sale): void
    {
        DB::transaction(function () use ($sale) {
            $qty = (float) ($sale->quantity ?? $sale->quantity_kg ?? 0);
            if ($qty <= 0) return;

            $q = Production::where('product_id', $sale->product_id)
                ->orderBy('production_date')
                ->orderBy('id');

            if (!empty($sale->production_id)) {
                $q->where('id', $sale->production_id);
            }

            $batches = $q->lockForUpdate()->get();
            $left = $qty;

            foreach ($batches as $b) {
                $soldForBatch = (float) Sale::where('production_id', $b->id)
                    ->where('id', '!=', $sale->id)
                    ->sum('quantity');

                $available = is_null($b->current_inventory)
                    ? max(0.0, (float)$b->quantity - $soldForBatch)
                    : max(0.0, (float)$b->current_inventory);

                if ($available <= 0) continue;

                $take = min($left, $available);
                $left -= $take;

                $b->current_inventory = is_null($b->current_inventory)
                    ? max(0.0, (float)$b->quantity - $soldForBatch - $take)
                    : max(0.0, (float)$b->current_inventory - $take);

                $b->save();

                if ($left <= 0) break;
            }

            if ($left > 0) {
                throw new \RuntimeException('Not enough total inventory for product.');
            }

            $this->recomputeProductBalance($sale->product_id);
        });
    }

    public function undoSale(Sale $sale): void
    {
        DB::transaction(function () use ($sale) {
            $qty = (float) ($sale->quantity ?? $sale->quantity_kg ?? 0);
            if ($qty <= 0) return;

            $q = Production::where('product_id', $sale->product_id)
                ->orderByDesc('production_date')
                ->orderByDesc('id');

            if (!empty($sale->production_id)) {
                $q->where('id', $sale->production_id);
            }

            $batches = $q->lockForUpdate()->get();

            // credit back to the newest (simple LIFO) or the explicit batch
            foreach ($batches as $b) {
                $b->current_inventory = (float) ($b->current_inventory ?? 0) + $qty;
                $b->save();
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

    /**
     * Material usage between two dates inclusive.
     * Auto-detects:
     * - product_recipes.{material_id|ingredient_id}
     * - materials.{material_name|name}
     * - price from {unit_price_snapshot|unit_price|0}
     */
    public function materialUsage(string $startDate, string $endDate): Collection
    {
        // Detect FK on product_recipes
        $recipeFk = Schema::hasColumn('product_recipes', 'material_id')
            ? 'material_id'
            : (Schema::hasColumn('product_recipes', 'ingredient_id') ? 'ingredient_id' : null);

        if (!$recipeFk) {
            throw new \RuntimeException("product_recipes needs 'material_id' or 'ingredient_id'.");
        }

        // Detect label column on materials
        $materialNameCol = Schema::hasColumn('materials', 'material_name')
            ? 'material_name'
            : (Schema::hasColumn('materials', 'name') ? 'name' : null);

        if (!$materialNameCol) {
            throw new \RuntimeException("materials needs a label column: 'material_name' or 'name'.");
        }

        // Detect price column on recipe/materials
        $priceExpr = [];
        if (Schema::hasColumn('product_recipes', 'unit_price_snapshot')) {
            $priceExpr[] = 'r.unit_price_snapshot';
        }
        if (Schema::hasColumn('materials', 'unit_price')) {
            $priceExpr[] = 'm.unit_price';
        }
        $unitPriceExpr = $priceExpr ? 'COALESCE(' . implode(',', $priceExpr) . ',0)' : '0';

        return DB::table('productions as p')
            ->join('product_recipes as r', 'r.product_id', '=', 'p.product_id')
            ->join('materials as m', function ($join) use ($recipeFk) {
                $join->on('m.id', '=', DB::raw('r.' . $recipeFk));
            })
            ->whereBetween('p.production_date', [$startDate, $endDate])
            ->groupBy('m.id', 'm.' . $materialNameCol)
            ->select([
                'm.id',
                DB::raw('m.' . $materialNameCol . ' as material_name'),
                DB::raw('SUM(p.quantity * r.qty) as qty_used'),
                DB::raw('SUM(p.quantity * r.qty * ' . $unitPriceExpr . ') as cost_used'),
            ])
            ->orderByDesc('qty_used')
            ->get();
    }
}
