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
     * Returns a collection of:
     *  [
     *      'product_id'           => int,
     *      'product_name'         => string,
     *      'avg_daily_demand'     => float,
     *      'forecast_total'       => float,
     *      'current_inventory'    => float,
     *      'days_to_stockout'     => int|null,
     *      'suggested_production' => float,
     *  ]
     */
    public function perProductForecast(int $horizonDays = 7, int $lookbackDays = 60): Collection
    {
        $today       = Carbon::today();
        $windowStart = $today->copy()->subDays($lookbackDays - 1)->toDateString();
        $windowEnd   = $today->toDateString();

        // Quantity expression consistent with your DashboardController
        $qtyExpr = 'COALESCE(sales.quantity_kg, sales.quantity, 0)';

        // 1) Aggregate daily sales per product
        $salesHistory = Sale::whereBetween(DB::raw('DATE(date)'), [$windowStart, $windowEnd])
            ->selectRaw("product_id, DATE(date) as d, SUM($qtyExpr) as qty")
            ->groupBy('product_id', 'd')
            ->get();

        if ($salesHistory->isEmpty()) {
            return collect();
        }

        // 2) Compute avg daily demand and forecast per product
        $stats = $salesHistory
            ->groupBy('product_id')
            ->map(function ($rows) use ($horizonDays) {
                $totalQty  = (float) $rows->sum('qty');
                $daysCount = max(1, $rows->pluck('d')->unique()->count());

                $avgPerDay      = $totalQty / $daysCount;
                $forecastTotal  = $avgPerDay * $horizonDays;

                return [
                    'avg_daily_demand' => $avgPerDay,
                    'forecast_total'   => $forecastTotal,
                ];
            });

        // 3) Current inventory per product (same idea as your buildForecast)
        $inventoryPerProduct = Production::selectRaw(
                'product_id, SUM(COALESCE(current_inventory, quantity, 0)) as stock'
            )
            ->groupBy('product_id')
            ->pluck('stock', 'product_id');

        $products = Product::whereIn('id', $stats->keys())->get()->keyBy('id');

        $result = collect();

        foreach ($stats as $productId => $stat) {
            $product = $products->get($productId);
            if (!$product) {
                continue;
            }

            $avg    = (float) $stat['avg_daily_demand'];
            $future = (float) $stat['forecast_total'];

            $stock  = (float) ($inventoryPerProduct[$productId] ?? 0);
            $needed = max(0.0, $future - $stock);
            $daysToStockout = ($avg > 0 && $stock > 0)
                ? (int) floor($stock / $avg)
                : null;

            $result->push([
                'product_id'           => $productId,
                'product_name'         => $product->product_name,
                'avg_daily_demand'     => round($avg, 3),
                'forecast_total'       => round($future, 3),
                'current_inventory'    => round($stock, 3),
                'days_to_stockout'     => $daysToStockout,
                'suggested_production' => round($needed, 3),
            ]);
        }

        // Sort: highest suggested production first (most urgent)
        return $result
            ->sortByDesc('suggested_production')
            ->values();
    }
}
