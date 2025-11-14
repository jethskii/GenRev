<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Material;
use App\Models\Production;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /** ---------------------------- Helpers ---------------------------- */

    /** Build week-start (Monday) buckets from oldest → newest */
    private function makeWeekBuckets(Carbon $end, int $weeks = 12): array
    {
        $start   = $end->copy()->startOfWeek()->subWeeks($weeks - 1);
        $buckets = [];
        $cursor  = $start->copy();

        while ($cursor->lte($end->copy()->endOfWeek())) {
            $buckets[$cursor->toDateString()] = 0.0; // key = Monday YYYY-MM-DD
            $cursor->addWeek();
        }

        return $buckets;
    }

    /** Human label for a week bucket (e.g., "Aug 18") */
    private function humanWeekLabel(string $weekStartYmd): string
    {
        return Carbon::parse($weekStartYmd)->format('M j');
    }

    /**
     * Build simple global + per-product forecast series from historical sales
     * and current inventory.
     *
     * Returns:
     *  - labels              => array of "M d" strings (forecast horizon)
     *  - demandSeries        => array of floats (kg/day) global demand
     *  - inventorySeries     => array of floats (kg) global inventory
     *  - summary             => [
     *        horizon_days,
     *        global_stockout_date (Y-m-d|null),
     *        total_recommended_production (float|null),
     *    ]
     *  - topProducts         => Collection of arrays:
     *        [name, daily_demand, days_to_stockout, recommended_production]
     */
    private function buildForecast(int $lookbackDays = 60, int $horizonDays = 30): array
    {
        $today = Carbon::today();

        // ---------- 1) Historical demand per product ----------
        $qtyExpr = 'COALESCE(quantity_kg, quantity, 0)';

        $windowStart = $today->copy()->subDays($lookbackDays - 1)->toDateString();
        $windowEnd   = $today->toDateString();

        $salesHistory = Sale::whereBetween(DB::raw('DATE(date)'), [$windowStart, $windowEnd])
            ->selectRaw("product_id, DATE(date) as d, SUM($qtyExpr) as qty")
            ->groupBy('product_id', 'd')
            ->get();

        if ($salesHistory->isEmpty()) {
            return [
                'labels'          => [],
                'demandSeries'    => [],
                'inventorySeries' => [],
                'summary'         => [
                    'horizon_days'                 => $horizonDays,
                    'global_stockout_date'         => null,
                    'total_recommended_production' => null,
                ],
                'topProducts'     => collect(),
            ];
        }

        // Aggregate totals & span per product
        $productStats = []; // product_id => [sumQty, firstDate, lastDate]
        foreach ($salesHistory as $row) {
            $pid = (int) $row->product_id;

            if (!isset($productStats[$pid])) {
                $productStats[$pid] = [
                    'sumQty'    => 0.0,
                    'firstDate' => $row->d,
                    'lastDate'  => $row->d,
                ];
            }

            $productStats[$pid]['sumQty']    += (float) $row->qty;
            $productStats[$pid]['firstDate']  = min($productStats[$pid]['firstDate'], $row->d);
            $productStats[$pid]['lastDate']   = max($productStats[$pid]['lastDate'],  $row->d);
        }

        // ---------- 2) Current inventory per product ----------
        $inventoryPerProduct = Production::selectRaw(
                'product_id, SUM(COALESCE(current_inventory, quantity, 0)) as stock'
            )
            ->groupBy('product_id')
            ->pluck('stock', 'product_id');

        if ($inventoryPerProduct->isEmpty()) {
            return [
                'labels'          => [],
                'demandSeries'    => [],
                'inventorySeries' => [],
                'summary'         => [
                    'horizon_days'                 => $horizonDays,
                    'global_stockout_date'         => null,
                    'total_recommended_production' => null,
                ],
                'topProducts'     => collect(),
            ];
        }

        $productNames = Product::pluck('product_name', 'id');

        // ---------- 3) Average daily demand + risk metrics ----------
        $avgDailyPerProduct   = []; // product_id => avg daily demand
        $riskProductsRaw      = [];
        $safetyHorizonDays    = 7;

        foreach ($productStats as $pid => $stat) {
            $sumQty    = (float) $stat['sumQty'];
            $firstDate = Carbon::parse($stat['firstDate']);
            $lastDate  = Carbon::parse($stat['lastDate']);

            $spanDays  = max(1, $firstDate->diffInDays($lastDate) + 1);
            $window    = min($spanDays, $lookbackDays);
            $avgDaily  = $sumQty / max(1, $window);

            $stock     = (float) ($inventoryPerProduct[$pid] ?? 0.0);

            $avgDailyPerProduct[$pid] = $avgDaily;

            if ($avgDaily <= 0 || $stock <= 0) {
                continue;
            }

            $daysToStockout = $stock / max(0.0001, $avgDaily);
            $recProduction  = max(0.0, $avgDaily * $safetyHorizonDays - $stock);

            $riskProductsRaw[] = [
                'product_id'             => $pid,
                'name'                   => (string) ($productNames[$pid] ?? 'Product #' . $pid),
                'daily_demand'           => $avgDaily,
                'days_to_stockout'       => (int) floor($daysToStockout),
                'recommended_production' => $recProduction,
            ];
        }

        $globalInitialInventory = array_sum($inventoryPerProduct->toArray());
        $globalDailyDemand      = array_sum($avgDailyPerProduct);

        // ---------- 4) Global forecast series ----------
        $forecastLabels          = [];
        $forecastDemandSeries    = [];
        $forecastInventorySeries = [];
        $stockoutDate            = null;

        if ($globalInitialInventory > 0 && $globalDailyDemand > 0) {
            $inv = $globalInitialInventory;

            for ($i = 0; $i < $horizonDays; $i++) {
                $day = $today->copy()->addDays($i);

                // If inventory already depleted, mark stockout
                if ($inv <= 0 && $stockoutDate === null) {
                    $stockoutDate = $day->toDateString();
                }

                $forecastLabels[]          = $day->format('M d');
                $forecastInventorySeries[] = max(0.0, $inv);
                $forecastDemandSeries[]    = (float) $globalDailyDemand;

                $inv -= $globalDailyDemand;
            }

            if ($stockoutDate === null && $inv <= 0) {
                $stockoutDate = $today->copy()->addDays($horizonDays - 1)->toDateString();
            }
        }

        // ---------- 5) Rank “products to watch” ----------
        $riskCollection = collect($riskProductsRaw)
            ->sortBy(function ($p) {
                return $p['days_to_stockout'] ?? PHP_INT_MAX;
            })
            ->values()
            ->take(5);

        $totalRecommendedProduction = $riskCollection->sum('recommended_production');

        return [
            'labels'          => $forecastLabels,
            'demandSeries'    => $forecastDemandSeries,
            'inventorySeries' => $forecastInventorySeries,
            'summary'         => [
                'horizon_days'                 => $horizonDays,
                'global_stockout_date'         => $stockoutDate,
                'total_recommended_production' =>
                    $totalRecommendedProduction > 0 ? $totalRecommendedProduction : null,
            ],
            'topProducts'     => $riskCollection,
        ];
    }

    /** ---------------------------- Pages ----------------------------- */

    /**
     * Dashboard view with inline data for charts (Mon..Sun of current week)
     * + 12-week production trend + predictive analytics.
     */
    public function index()
    {
        $start = Carbon::now()->startOfWeek(); // Mon 00:00
        $end   = Carbon::now()->endOfWeek();   // Sun 23:59

        // ==== SAFE SQL pieces that match your table ====
        $QTY   = 'COALESCE(sales.quantity_kg, sales.quantity, 0)';
        $UNIT  = 'COALESCE(sales.unit_price, sales.price, 0)';
        $REVEX = "$QTY * $UNIT";
        $TYPEX = "NULLIF(TRIM(sales.type_label), '')";

        /* ======================== KPI cards ======================== */
        $totalProducts        = (int) Product::count();
        $totalMaterialsWeight = (float) (Material::sum('quantity_kg') ?? 0); // on-hand stock
        $totalRevenue         = (float) (Sale::selectRaw("SUM($REVEX) as rev")->value('rev') ?? 0);
        $totalSales           = (int) Sale::count();

        /* ===================== Recent sales table ==================== */
        $recentSales = Sale::leftJoin('products as p', 'p.id', '=', 'sales.product_id')
            ->orderByDesc('sales.date')
            ->orderByDesc('sales.id')
            ->take(8)
            ->get([
                DB::raw("COALESCE(p.product_name, sales.product, 'Product') as product_name"),
                DB::raw("$TYPEX as sale_type"),
                DB::raw("$QTY  as quantity"),
                DB::raw("$UNIT as unit_price"),
                DB::raw("DATE(sales.date) as date"),
            ]);

        /* ===================== Labels Mon..Sun ======================= */
        $labels = [];
        $p = $start->copy();
        while ($p->lte($end)) {
            $labels[] = $p->format('D'); // Mon, Tue, ...
            $p->addDay();
        }

        /* =================== Weekly Production (current week) ====================== */
        $prodDaily = Production::whereBetween('production_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('production_date as d, SUM(quantity) as qty')
            ->groupBy('d')
            ->pluck('qty', 'd')
            ->all();

        $weeklyProductionSeries = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $weeklyProductionSeries[] = (float) ($prodDaily[$cursor->toDateString()] ?? 0);
            $cursor->addDay();
        }

        /* =================== Weekly Sales (qty/rev) ================= */
        $salesDaily = Sale::whereBetween(DB::raw('DATE(date)'), [$start->toDateString(), $end->toDateString()])
            ->selectRaw("DATE(date) as d, SUM($QTY) as qty, SUM($REVEX) as rev")
            ->groupBy('d')
            ->get()
            ->keyBy('d');

        $weeklySalesQtySeries = [];
        $weeklySalesRevenueSeries = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            $weeklySalesQtySeries[]     = (float) ($salesDaily[$key]->qty ?? 0);
            $weeklySalesRevenueSeries[] = (float) ($salesDaily[$key]->rev ?? 0);
            $cursor->addDay();
        }

        // Biggest day by revenue (for potential use in Sales Report widget)
        $biggestSalesDay = null;
        if ($salesDaily->isNotEmpty()) {
            $maxRow = $salesDaily->sortByDesc('rev')->first();
            if ($maxRow && (float) $maxRow->rev > 0) {
                $biggestSalesDay = Carbon::parse($maxRow->d)->format('M d');
            }
        }

        /* ================= Materials Used (This Week) ================ */
        $materialsUsage = DB::table('productions as p')
            ->join('products as pr', 'pr.id', '=', 'p.product_id')
            ->join('product_recipes as r', 'r.product_id', '=', 'pr.id')
            ->join('materials as m', 'm.id', '=', 'r.ingredient_id')
            ->whereBetween('p.production_date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('m.id', 'm.material_name')
            ->selectRaw('
                m.id,
                m.material_name,
                SUM(p.quantity * r.qty)                          as qty_used,
                SUM(p.quantity * r.qty * r.unit_price_snapshot) as cost_used
            ')
            ->orderByDesc('qty_used')
            ->limit(8)
            ->get();

        $materialsUsageTotals = [
            'qty'  => (float) ($materialsUsage->sum('qty_used') ?? 0),
            'cost' => (float) ($materialsUsage->sum('cost_used') ?? 0),
        ];

        // Recent materials (created this week)
        $recentMaterials = Material::whereBetween('created_at', [$start, $end])
            ->orderByDesc('created_at')
            ->take(8)
            ->get();

        /* =================== Expiration Trend (current week) =================== */
        $expiryBuckets = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $expiryBuckets[$cursor->toDateString()] = 0.0;
            $cursor->addDay();
        }

        // Consider batches that could expire on/before end of this week
        $batches = Production::with('product:id,shelf_life_days')
            ->whereDate('production_date', '<=', $end->toDateString())
            ->get();

        foreach ($batches as $b) {
            // Determine expiration date robustly
            $expDate = $b->expiration_date;
            if (empty($expDate) && $b->product && !empty($b->product->shelf_life_days)) {
                $expDate = Carbon::parse($b->production_date)
                    ->copy()
                    ->addDays((int) $b->product->shelf_life_days);
            }
            if (!$expDate) {
                continue;
            }

            $expDate = Carbon::parse($expDate)->toDateString();
            if (isset($expiryBuckets[$expDate])) {
                $remaining = (float) ($b->current_inventory ?? $b->quantity ?? 0);
                $expiryBuckets[$expDate] += max(0.0, $remaining);
            }
        }

        $weeklyExpirySeries = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $weeklyExpirySeries[] = (float) ($expiryBuckets[$cursor->toDateString()] ?? 0);
            $cursor->addDay();
        }

        /* ============== Most Sold Products & Types (this week) ============== */
        $weekRevenue = (float) (Sale::whereBetween(DB::raw('DATE(sales.date)'), [$start->toDateString(), $end->toDateString()])
            ->selectRaw("SUM($REVEX) as rev")
            ->value('rev') ?? 0);

        $topProducts = Sale::join('products as p', 'p.id', '=', 'sales.product_id')
            ->whereBetween(DB::raw('DATE(sales.date)'), [$start->toDateString(), $end->toDateString()])
            ->selectRaw("
                sales.product_id,
                p.product_name,
                $TYPEX as sale_type,
                SUM($QTY)   as quantity,
                SUM($REVEX) as revenue
            ")
            ->groupByRaw('sales.product_id, p.product_name, sale_type')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->map(function ($row) use ($weekRevenue) {
                $row->revenue_share = $weekRevenue > 0
                    ? round(($row->revenue / $weekRevenue) * 100, 1)
                    : 0.0;
                return $row;
            });

        /* =================== 12-week Production Trend =================== */
        $trendEnd    = Carbon::now();
        $weekBuckets = $this->makeWeekBuckets($trendEnd, 12); // Monday keys
        $windowStart = Carbon::parse(array_key_first($weekBuckets));

        $dailyProd = Production::whereBetween('production_date', [
                $windowStart->toDateString(),
                $trendEnd->endOfWeek()->toDateString(),
            ])
            ->selectRaw('production_date as d, SUM(quantity) as qty')
            ->groupBy('d')
            ->pluck('qty', 'd')
            ->all();

        foreach ($dailyProd as $dayYmd => $qty) {
            $weekStart = Carbon::parse($dayYmd)->startOfWeek()->toDateString();
            if (array_key_exists($weekStart, $weekBuckets)) {
                $weekBuckets[$weekStart] += (float) $qty;
            }
        }

        $productionTrendLabels = [];
        $productionTrendSeries = [];
        foreach ($weekBuckets as $weekStartYmd => $sumQty) {
            $productionTrendLabels[] = $this->humanWeekLabel($weekStartYmd);
            $productionTrendSeries[] = (float) $sumQty;
        }

        /* =================== Predictive Analytics =================== */
        // Positional args for max compatibility
        $forecast = $this->buildForecast(60, 30);

        return view('dashboard', [
            // Cards
            'totalProducts'            => $totalProducts,
            'totalMaterialsWeight'     => $totalMaterialsWeight,
            'totalRevenue'             => $totalRevenue,
            'totalSales'               => $totalSales,

            // Tables/widgets
            'recentSales'              => $recentSales,
            'recentMaterials'          => $recentMaterials,
            'materialsUsage'           => $materialsUsage,
            'materialsUsageTotals'     => $materialsUsageTotals,
            'topProducts'              => $topProducts,
            'biggestSalesDay'          => $biggestSalesDay,

            // Charts (current week)
            'labels'                   => $labels,
            'weeklyProductionSeries'   => $weeklyProductionSeries,
            'weeklySalesQtySeries'     => $weeklySalesQtySeries,
            'weeklySalesRevenueSeries' => $weeklySalesRevenueSeries,
            'weeklyExpirySeries'       => $weeklyExpirySeries,

            // Charts (12-week trend)
            'productionTrendLabels'    => $productionTrendLabels,
            'productionTrendSeries'    => $productionTrendSeries,

            // Predictive Analytics
            'forecastLabels'           => $forecast['labels'],
            'forecastDemandSeries'     => $forecast['demandSeries'],
            'forecastInventorySeries'  => $forecast['inventorySeries'],
            'forecastSummary'          => $forecast['summary'],
            'forecastTopProducts'      => $forecast['topProducts'],
        ]);
    }

    /**
     * Optional JSON endpoint: /dashboard/data
     * Returns the same series so you can fetch via AJAX if you want later.
     */
    public function data()
    {
        $start = Carbon::now()->startOfWeek();
        $end   = Carbon::now()->endOfWeek();

        $QTY   = 'COALESCE(sales.quantity_kg, sales.quantity, 0)';
        $UNIT  = 'COALESCE(sales.unit_price, sales.price, 0)';
        $REVEX = "$QTY * $UNIT";

        // Labels
        $labels = [];
        $p = $start->copy();
        while ($p->lte($end)) {
            $labels[] = $p->format('D');
            $p->addDay();
        }

        // Production (current week)
        $prodDaily = Production::whereBetween('production_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('production_date as d, SUM(quantity) as qty')
            ->groupBy('d')
            ->pluck('qty', 'd')
            ->all();

        $weeklyProductionSeries = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $weeklyProductionSeries[] = (float) ($prodDaily[$cursor->toDateString()] ?? 0);
            $cursor->addDay();
        }

        // Sales (current week)
        $salesDaily = Sale::whereBetween(DB::raw('DATE(date)'), [$start->toDateString(), $end->toDateString()])
            ->selectRaw("DATE(date) as d, SUM($QTY) as qty, SUM($REVEX) as rev")
            ->groupBy('d')
            ->get()
            ->keyBy('d');

        $weeklySalesQtySeries = [];
        $weeklySalesRevenueSeries = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            $weeklySalesQtySeries[]     = (float) ($salesDaily[$key]->qty ?? 0);
            $weeklySalesRevenueSeries[] = (float) ($salesDaily[$key]->rev ?? 0);
            $cursor->addDay();
        }

        // Expiry (current week)
        $expiryBuckets = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $expiryBuckets[$cursor->toDateString()] = 0.0;
            $cursor->addDay();
        }

        $batches = Production::with('product:id,shelf_life_days')
            ->whereDate('production_date', '<=', $end->toDateString())
            ->get();

        foreach ($batches as $b) {
            $expDate = $b->expiration_date;
            if (empty($expDate) && $b->product && !empty($b->product->shelf_life_days)) {
                $expDate = Carbon::parse($b->production_date)
                    ->copy()
                    ->addDays((int) $b->product->shelf_life_days);
            }
            if (!$expDate) {
                continue;
            }

            $expDate = Carbon::parse($expDate)->toDateString();
            if (isset($expiryBuckets[$expDate])) {
                $remaining = (float) ($b->current_inventory ?? $b->quantity ?? 0);
                $expiryBuckets[$expDate] += max(0.0, $remaining);
            }
        }

        $weeklyExpirySeries = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $weeklyExpirySeries[] = (float) ($expiryBuckets[$cursor->toDateString()] ?? 0);
            $cursor->addDay();
        }

        /* 12-week production trend */
        $trendEnd    = Carbon::now();
        $weekBuckets = $this->makeWeekBuckets($trendEnd, 12);
        $windowStart = Carbon::parse(array_key_first($weekBuckets));

        $dailyProd = Production::whereBetween('production_date', [
                $windowStart->toDateString(),
                $trendEnd->endOfWeek()->toDateString(),
            ])
            ->selectRaw('production_date as d, SUM(quantity) as qty')
            ->groupBy('d')
            ->pluck('qty', 'd')
            ->all();

        foreach ($dailyProd as $dayYmd => $qty) {
            $weekStart = Carbon::parse($dayYmd)->startOfWeek()->toDateString();
            if (array_key_exists($weekStart, $weekBuckets)) {
                $weekBuckets[$weekStart] += (float) $qty;
            }
        }

        $productionTrendLabels = [];
        $productionTrendSeries = [];
        foreach ($weekBuckets as $weekStartYmd => $sumQty) {
            $productionTrendLabels[] = $this->humanWeekLabel($weekStartYmd);
            $productionTrendSeries[] = (float) $sumQty;
        }

        // Predictive Analytics (for AJAX usage)
        $forecast = $this->buildForecast(60, 30);

        return response()->json([
            'labels'                   => $labels,
            'weeklyProductionSeries'   => $weeklyProductionSeries,
            'weeklySalesQtySeries'     => $weeklySalesQtySeries,
            'weeklySalesRevenueSeries' => $weeklySalesRevenueSeries,
            'weeklyExpirySeries'       => $weeklyExpirySeries,
            'productionTrendLabels'    => $productionTrendLabels,
            'productionTrendSeries'    => $productionTrendSeries,

            'forecastLabels'           => $forecast['labels'],
            'forecastDemandSeries'     => $forecast['demandSeries'],
            'forecastInventorySeries'  => $forecast['inventorySeries'],
            'forecastSummary'          => $forecast['summary'],
            'forecastTopProducts'      => $forecast['topProducts'],
        ]);
    }
}
