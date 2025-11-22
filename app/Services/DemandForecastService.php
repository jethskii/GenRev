<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Production;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DemandForecastService
{
    /**
     * Per-product forecast for the next N days.
     *
     * Uses simple exponential smoothing over the last X days of sales
     * + current inventory to recommend how much to produce.
     *
     * Returns a collection of:
     *  [
     *      'product_id'           => int,
     *      'product_name'         => string,
     *      'avg_daily_demand'     => float,   // smoothed / forecasted daily demand
     *      'forecast_total'       => float,   // demand for the next $horizonDays
     *      'current_inventory'    => float,   // current stock (kg or units)
     *      'days_to_stockout'     => int|null,
     *      'suggested_production' => float,   // how much to produce to cover horizon
     *  ]
     */
    public function perProductForecast(int $horizonDays = 7, int $lookbackDays = 60): Collection
    {
        $today       = Carbon::today();
        $windowStart = $today->copy()->subDays($lookbackDays - 1)->toDateString();
        $windowEnd   = $today->toDateString();

        // Quantity expression consistent with DashboardController
        $qtyExpr = 'COALESCE(sales.quantity_kg, sales.quantity, 0)';

        // 1) Aggregate daily sales per product within the lookback window
        $salesHistory = Sale::whereBetween(DB::raw('DATE(date)'), [$windowStart, $windowEnd])
            ->selectRaw("product_id, DATE(date) as d, SUM($qtyExpr) as qty")
            ->groupBy('product_id', 'd')
            ->orderBy('product_id')
            ->orderBy('d')
            ->get();

        if ($salesHistory->isEmpty()) {
            return collect();
        }

        // 2) Current inventory per product (same idea as dashboard buildForecast)
        $inventoryPerProduct = Production::selectRaw(
                'product_id, SUM(COALESCE(current_inventory, quantity, 0)) as stock'
            )
            ->groupBy('product_id')
            ->pluck('stock', 'product_id');

        if ($inventoryPerProduct->isEmpty()) {
            return collect();
        }

        $products = Product::whereIn('id', $salesHistory->pluck('product_id')->unique())
            ->get()
            ->keyBy('id');

        // Group history by product
        $byProduct = $salesHistory->groupBy('product_id');

        // Exponential smoothing factor: you can tweak (0.1–0.3 is common)
        $alpha = 0.3;

        $result = collect();

        foreach ($byProduct as $productId => $rows) {
            $productId = (int) $productId;
            $product   = $products->get($productId);

            if (!$product) {
                continue;
            }

            // If no inventory record, treat as 0 (no need to forecast urgent production)
            $stock = (float) ($inventoryPerProduct[$productId] ?? 0.0);

            // ---------- Build complete daily series ----------
            $map     = $rows->keyBy('d');
            $cursor  = Carbon::parse($windowStart);
            $endDate = Carbon::parse($windowEnd);

            $series = [];
            while ($cursor->lte($endDate)) {
                $day   = $cursor->toDateString();
                $series[] = (float) ($map[$day]->qty ?? 0.0);
                $cursor->addDay();
            }

            if (empty($series)) {
                continue;
            }

            // ---------- Exponential smoothing ----------
            // S_t = α * y_t + (1 - α) * S_{t-1}
            $s = $series[0]; // initial S_0 = first observation
            foreach ($series as $y) {
                $s = $alpha * $y + (1 - $alpha) * $s;
            }

            $avgPerDay = max(0.0, $s); // smoothed daily demand

            // If zero demand, still include but everything will be 0 suggested
            $forecastTotal = $avgPerDay * $horizonDays;

            // Days to stockout
            $daysToStockout = ($avgPerDay > 0 && $stock > 0)
                ? (int) floor($stock / $avgPerDay)
                : null;

            // How much we *should* have for the horizon
            $targetStock      = $avgPerDay * $horizonDays;
            $needed           = max(0.0, $targetStock - $stock);
            $suggestedProd    = $needed;

            $result->push([
                'product_id'           => $productId,
                'product_name'         => $product->product_name,
                'avg_daily_demand'     => round($avgPerDay, 3),
                'forecast_total'       => round($forecastTotal, 3),
                'current_inventory'    => round($stock, 3),
                'days_to_stockout'     => $daysToStockout,
                'suggested_production' => round($suggestedProd, 3),
            ]);
        }

        // Sort: highest suggested production first (most urgent products on top)
        return $result
            ->sortByDesc('suggested_production')
            ->values();
    }
}
