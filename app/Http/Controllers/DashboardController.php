<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Material;
use App\Models\Production;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\DemandForecastService;

class DashboardController extends Controller
{
    /** ---------------------------- Helpers ---------------------------- */

    /**
     * TODO: real conversion from kg to packs per product.
     *
     * IMPORTANT:
     * - All finished goods on the dashboard are expressed in units (packs/bags).
     * - Many legacy calculations still use kg or mixed units internally.
     * - To avoid dangerous unit confusion, every product-facing quantity that may
     *   be kg goes through this helper before it hits charts / cards.
     *
     * When you have per-product pack-size metadata, replace this with proper logic,
     * e.g. $kg / $packWeightForProduct.
     */
    private function convertKgToPacks(float $kgOrUnits, ?int $productId = null): float
    {
        // TODO: real conversion from kg to packs per product
        return $kgOrUnits;
    }

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
     * Try to resolve the variant / type label of a batch.
     * This is where you plug in the actual column you use
     * in your `productions` table for product variant.
     */
    private function resolveBatchVariantLabel(Production $b): ?string
    {
        if (!empty($b->type))              return $b->type;
        if (!empty($b->variant))           return $b->variant;
        if (!empty($b->packaging_type))    return $b->packaging_type;
        if (!empty($b->size_label))        return $b->size_label;
        if (!empty($b->packaging_size))    return $b->packaging_size;

        // Fallback: try product-level label if you have one
        if ($b->relationLoaded('product') && !empty($b->product->variant_label)) {
            return $b->product->variant_label;
        }

        return null;
    }

    /**
     * Simple helper to choose the recommended operational move
     * for a batch that is close to expiry.
     *
     * We treat quantity as packs/bags, not kg.
     */
    private function recommendedExpiryAction(int $daysLeft, float $unitsAtRisk): string
    {
        if ($daysLeft <= 0) {
            return 'Stop selling, check quality and adjust stock.';
        }

        if ($daysLeft <= 2) {
            return 'Move as priority dispatch and brief sales to push this first.';
        }

        if ($daysLeft <= 5) {
            return 'Suggest bundle or light promo and make sure store puts this in front.';
        }

        // 6 days and above inside our near window
        return 'Monitor and rotate stock so near expiry goes out first.';
    }

    /**
     * Build simple global + per-product forecast series from historical sales
     * and current inventory.
     *
     * NOTE:
     * - This function still works in the internal "base unit" (kg or units),
     *   and the mapping to packs happens in index()/data() via convertKgToPacks().
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

        // ---------- 2b) Default unit type per product from sales (pack / bag) ----------
        $unitTypeMap = Sale::selectRaw("
                product_id,
                COALESCE(NULLIF(TRIM(unit_type), ''), 'pack') as unit_type
            ")
            ->whereIn('product_id', array_keys($productStats))
            ->groupBy('product_id', 'unit_type')
            ->pluck('unit_type', 'product_id');

        // ---------- 3) Average daily demand + risk metrics ----------
        $avgDailyPerProduct   = [];
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

            $unitType = $unitTypeMap[$pid] ?? 'pack';

            $riskProductsRaw[] = [
                'product_id'             => $pid,
                'name'                   => (string) ($productNames[$pid] ?? 'Product #' . $pid),
                'unit_type'              => $unitType,
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

    public function index(DemandForecastService $demandForecastService)
    {
        $start = Carbon::now()->startOfWeek();
        $end   = Carbon::now()->endOfWeek();
        $today = Carbon::today();

        // Rolling 7 day window for expiry (today + next 6 days)
        $expiryStart = $today->copy();
        $expiryEnd   = $today->copy()->addDays(6);

        // For finished goods: QTY is still potentially kg internally.
        // We will convert to packs when mapping to charts / summaries.
        $QTY   = 'COALESCE(sales.quantity_kg, sales.quantity, 0)';
        $UNIT  = 'COALESCE(sales.unit_price, sales.price, 0)';
        $REVEX = "$QTY * $UNIT";
        $TYPEX = "NULLIF(TRIM(sales.type_label), '')";

        /* ======================== KPI cards ======================== */
        $totalProducts        = (int) Product::count();
        $totalMaterialsWeight = (float) (Material::sum('quantity_kg') ?? 0); // raw materials stay in kg
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
                DB::raw("$QTY  as quantity"),       // numeric quantity (kg or units)
                DB::raw("$UNIT as unit_price"),     // numeric price
                // 👇 pass unit_type so Blade can show Pack vs Bag
                DB::raw("COALESCE(NULLIF(TRIM(sales.unit_type), ''), 'pack') as unit_type"),
                DB::raw("DATE(sales.date) as date"),
            ]);

        /* ===================== Labels Mon..Sun (weekly charts) ======================= */
        $labels = [];
        $p = $start->copy();
        while ($p->lte($end)) {
            $labels[] = $p->format('D');
            $p->addDay();
        }

        /* =================== Weekly Production (current week, units) ====================== */
        $prodDaily = Production::whereBetween('production_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('production_date as d, SUM(quantity) as qty')
            ->groupBy('d')
            ->pluck('qty', 'd')
            ->all();

        $weeklyProductionSeries = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $rawQty = (float) ($prodDaily[$cursor->toDateString()] ?? 0);
            // Treat production quantity as finished-good units (packs/bags) on the dashboard.
            $weeklyProductionSeries[] = $this->convertKgToPacks($rawQty);
            $cursor->addDay();
        }

        /* =================== Weekly Sales (qty/rev) ================= */
        $salesDaily = Sale::whereBetween(DB::raw('DATE(date)'), [$start->toDateString(), $end->toDateString()])
            ->selectRaw("DATE(date) as d, SUM($QTY) as qty, SUM($REVEX) as rev")
            ->groupBy('d')
            ->get()
            ->keyBy('d');

        $weeklySalesQtySeries     = [];
        $weeklySalesRevenueSeries = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key          = $cursor->toDateString();
            $rawQtyUnits  = (float) ($salesDaily[$key]->qty ?? 0);
            $weeklySalesQtySeries[]     = $this->convertKgToPacks($rawQtyUnits); // units on the dashboard
            $weeklySalesRevenueSeries[] = (float) ($salesDaily[$key]->rev ?? 0);
            $cursor->addDay();
        }

        // Total revenue for this week (used in multiple places)
        $weekRevenue = array_sum($weeklySalesRevenueSeries);

        $biggestSalesDay = null;
        if ($salesDaily->isNotEmpty()) {
            $maxRow = $salesDaily->sortByDesc('rev')->first();
            if ($maxRow && (float) $maxRow->rev > 0) {
                $biggestSalesDay = Carbon::parse($maxRow->d)->format('M d');
            }
        }

        /* ================= Materials Used (This Week) ================ */
        // All of this remains kg-based because it is raw materials / ingredients.
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

        // ---------- Estimated weekly profit & daily profit series ----------
        $estimatedWeekProfit      = 0.0;
        $estimatedGrossMarginPct  = null;
        $weeklySalesProfitSeries  = [];

        $estimatedWeekCost = (float) ($materialsUsageTotals['cost'] ?? 0.0);

        if ($weekRevenue > 0 && $estimatedWeekCost > 0) {
            $estimatedWeekProfit     = max(0.0, $weekRevenue - $estimatedWeekCost);
            $estimatedGrossMarginPct = round(($estimatedWeekProfit / $weekRevenue) * 100, 1);

            $profitFactor = $estimatedWeekProfit / $weekRevenue;

            foreach ($weeklySalesRevenueSeries as $revDay) {
                $weeklySalesProfitSeries[] = (float) $revDay * $profitFactor;
            }
        } else {
            foreach ($weeklySalesRevenueSeries as $revDay) {
                $weeklySalesProfitSeries[] = 0.0;
            }
        }

        $recentMaterials = Material::whereBetween('created_at', [$start, $end])
            ->orderByDesc('created_at')
            ->take(8)
            ->get();

        /* =================== Expiration Trend (rolling 7 days, units) =================== */

        $expiryBuckets = [];
        $expiryLabels  = [];
        $cursor = $expiryStart->copy();
        while ($cursor->lte($expiryEnd)) {
            $expiryBuckets[$cursor->toDateString()] = 0.0;
            $expiryLabels[] = $cursor->format('D'); // Mon, Tue etc, starting today
            $cursor->addDay();
        }

        $expiryStats = [
            'total_expiring' => 0.0,
            'critical'       => 0.0,
            'high'           => 0.0,
            'medium'         => 0.0,
        ];

        $expiryPriorityRows = [];

        $batches = Production::with('product:id,product_name,shelf_life_days')
            ->whereDate('production_date', '<=', $expiryEnd->toDateString())
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

            $expCarbon = Carbon::parse($expDate);
            $expYmd    = $expCarbon->toDateString();

            // units (packs/bags)
            $unitsRemaining = (float) (
                $b->remaining_units
                ?? $b->current_inventory_units
                ?? $b->current_inventory
                ?? $b->quantity_units
                ?? $b->quantity
                ?? 0
            );

            if ($unitsRemaining <= 0) {
                continue;
            }

            $variantLabel = $this->resolveBatchVariantLabel($b);

            // add to bucket if expiry between today and today+6
            if ($expCarbon->betweenIncluded($expiryStart, $expiryEnd) && isset($expiryBuckets[$expYmd])) {
                $expiryBuckets[$expYmd] += max(0.0, $unitsRemaining);
            }

            $daysDiff = $today->diffInDays($expCarbon, false);

            // rolling 7 day window: today (0) to +6 days
            if ($daysDiff >= 0 && $daysDiff <= 6) {
                $daysLeft = max(0, $daysDiff);

                $expiryStats['total_expiring'] += $unitsRemaining;

                if ($daysLeft <= 2) {
                    $expiryStats['critical'] += $unitsRemaining;
                } elseif ($daysLeft <= 5) {
                    $expiryStats['high'] += $unitsRemaining;
                } elseif ($daysLeft <= 6) {
                    $expiryStats['medium'] += $unitsRemaining;
                }

                $expiryPriorityRows[] = [
                    'product_name'       => $b->product->product_name ?? 'Product',
                    'batch_code'         => $b->batch_code ?? $b->batch_no ?? $b->id,
                    'variant_label'      => $variantLabel,
                    'days_left'          => $daysLeft,
                    'units_at_risk'      => $unitsRemaining,
                    'recommended_action' => $this->recommendedExpiryAction($daysLeft, $unitsRemaining),
                ];
            }
        }

        $weeklyExpirySeries = [];
        $cursor = $expiryStart->copy();
        while ($cursor->lte($expiryEnd)) {
            $weeklyExpirySeries[] = (float) ($expiryBuckets[$cursor->toDateString()] ?? 0);
            $cursor->addDay();
        }

        $expiryPriority = collect($expiryPriorityRows)
            ->sortBy('days_left')
            ->values()
            ->take(10);

        /* ============== Most Sold Products & Variants (this week) ============== */
        $topProducts = Sale::join('products as p', 'p.id', '=', 'sales.product_id')
            ->whereBetween(DB::raw('DATE(sales.date)'), [$start->toDateString(), $end->toDateString()])
            ->selectRaw("
                sales.product_id,
                p.product_name,
                $TYPEX as sale_type,
                COALESCE(NULLIF(TRIM(sales.unit_type), ''), 'pack') as unit_type,
                SUM($QTY)   as quantity,
                SUM($REVEX) as revenue
            ")
            ->groupByRaw('sales.product_id, p.product_name, sale_type, unit_type')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->map(function ($row) use ($weekRevenue) {
                $row->revenue_share = $weekRevenue > 0
                    ? round(($row->revenue / $weekRevenue) * 100, 1)
                    : 0.0;

                // Convert quantity to units (packs/bags) for dashboard display
                $row->quantity = $this->convertKgToPacks((float) $row->quantity, (int) $row->product_id);

                // Human-friendly label for charts / tables
                $unit    = strtolower($row->unit_type ?? 'pack');       // "pack" or "bag"
                $variant = trim($row->sale_type ?? '');
                $base    = trim($row->product_name . ' ' . $variant);    // e.g. "Pork Tapa 250g"
                $row->display_label = $base . ' (' . $unit . ')';        // e.g. "Pork Tapa 250g (pack)"

                return $row;
            });

        /* =================== 12-week Production Trend (units) =================== */
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
            // Production trend is for finished goods → make sure it is units.
            $productionTrendSeries[] = $this->convertKgToPacks((float) $sumQty);
        }

        /* =================== Predictive Analytics =================== */
        $forecast        = $this->buildForecast(60, 30);
        $productForecast = $demandForecastService->perProductForecast(7, 60);

        // Map global forecast series (base units) into packs/units for the dashboard
        $forecastDemandSeriesBase    = $forecast['demandSeries']    ?? [];
        $forecastInventorySeriesBase = $forecast['inventorySeries'] ?? [];
        $forecastDemandSeries        = array_map(
            fn ($v) => $this->convertKgToPacks((float) $v),
            $forecastDemandSeriesBase
        );
        $forecastInventorySeries     = array_map(
            fn ($v) => $this->convertKgToPacks((float) $v),
            $forecastInventorySeriesBase
        );

        // Summary: convert recommended production to units for UI
        $forecastSummary = $forecast['summary'] ?? [];
        if (!empty($forecastSummary['total_recommended_production'])) {
            $forecastSummary['total_recommended_production'] = $this->convertKgToPacks(
                (float) $forecastSummary['total_recommended_production']
            );
        }

        // Per-product "what to produce next" (convert to units + label per pack/bag)
        $forecastTopProducts = $forecast['topProducts'] ?? collect();
        if ($forecastTopProducts instanceof \Illuminate\Support\Collection) {
            $forecastTopProducts = $forecastTopProducts->map(function (array $row) {
                $productId = $row['product_id'] ?? null;

                $row['daily_demand']           = $this->convertKgToPacks((float) ($row['daily_demand'] ?? 0.0), $productId);
                $row['recommended_production'] = $this->convertKgToPacks((float) ($row['recommended_production'] ?? 0.0), $productId);

                $unit = strtolower($row['unit_type'] ?? 'pack');
                $name = $row['name'] ?? 'Product';
                // label for Production Planning Assistant graph
                $row['label'] = $name . ' (' . $unit . ')';

                return $row;
            });
        }

        // ---------- AI-style Weekly Sales forecast (next 7 days, units) ----------
        $globalDemandSeriesBase = $forecast['demandSeries'] ?? [];

        // average unit price based on all-time totals
        $avgUnitPriceGlobal = $totalSales > 0
            ? ($totalRevenue / max($totalSales, 1))
            : 0.0;

        $marginRatio = ($weekRevenue > 0 && $estimatedWeekProfit > 0)
            ? ($estimatedWeekProfit / $weekRevenue)
            : 0.0;

        $weeklySalesForecastQtySeries       = [];
        $weeklySalesForecastRevenueSeries   = [];
        $weeklySalesForecastProfitSeries    = [];

        for ($i = 0; $i < 7; $i++) {
            $demandBase = (float) ($globalDemandSeriesBase[$i] ?? 0.0); // base unit
            $demandUnits = $this->convertKgToPacks($demandBase);        // UI uses units

            $weeklySalesForecastQtySeries[] = $demandUnits;

            $forecastRev = $demandUnits * $avgUnitPriceGlobal;
            $weeklySalesForecastRevenueSeries[] = $forecastRev;

            $weeklySalesForecastProfitSeries[] = $marginRatio > 0
                ? $forecastRev * $marginRatio
                : 0.0;
        }

        // ---------- AI per-product weekly plan (units) ----------
        if ($productForecast instanceof \Illuminate\Support\Collection) {
            $productForecast = $productForecast->map(function ($row) {
                // Support both array and stdClass
                if (is_array($row)) {
                    $row['avg_daily_demand']     = $this->convertKgToPacks((float) ($row['avg_daily_demand'] ?? 0.0), $row['product_id'] ?? null);
                    $row['forecast_total']       = $this->convertKgToPacks((float) ($row['forecast_total'] ?? 0.0), $row['product_id'] ?? null);
                    $row['current_inventory']    = $this->convertKgToPacks((float) ($row['current_inventory'] ?? 0.0), $row['product_id'] ?? null);
                    $row['suggested_production'] = $this->convertKgToPacks((float) ($row['suggested_production'] ?? 0.0), $row['product_id'] ?? null);
                    return $row;
                }

                if (is_object($row)) {
                    $productId = $row->product_id ?? null;
                    $row->avg_daily_demand     = $this->convertKgToPacks((float) ($row->avg_daily_demand ?? 0.0), $productId);
                    $row->forecast_total       = $this->convertKgToPacks((float) ($row->forecast_total ?? 0.0), $productId);
                    $row->current_inventory    = $this->convertKgToPacks((float) ($row->current_inventory ?? 0.0), $productId);
                    $row->suggested_production = $this->convertKgToPacks((float) ($row->suggested_production ?? 0.0), $productId);
                    return $row;
                }

                return $row;
            });
        }

        return view('dashboard', [
            'totalProducts'            => $totalProducts,
            'totalMaterialsWeight'     => $totalMaterialsWeight,
            'totalRevenue'             => $totalRevenue,
            'totalSales'               => $totalSales,

            'recentSales'              => $recentSales,
            'recentMaterials'          => $recentMaterials,
            'materialsUsage'           => $materialsUsage,
            'materialsUsageTotals'     => $materialsUsageTotals,
            'topProducts'              => $topProducts,
            'biggestSalesDay'          => $biggestSalesDay,

            'labels'                   => $labels,
            'weeklyProductionSeries'   => $weeklyProductionSeries,      // units
            'weeklySalesQtySeries'     => $weeklySalesQtySeries,        // units
            'weeklySalesRevenueSeries' => $weeklySalesRevenueSeries,
            'weeklySalesProfitSeries'  => $weeklySalesProfitSeries,

            'weekRevenue'              => $weekRevenue,
            'estimatedWeekProfit'      => $estimatedWeekProfit,
            'estimatedGrossMarginPct'  => $estimatedGrossMarginPct,

            'weeklySalesForecastQtySeries'       => $weeklySalesForecastQtySeries,     // units
            'weeklySalesForecastRevenueSeries'   => $weeklySalesForecastRevenueSeries,
            'weeklySalesForecastProfitSeries'    => $weeklySalesForecastProfitSeries,

            'expiryLabels'             => $expiryLabels,
            'weeklyExpirySeries'       => $weeklyExpirySeries,          // units at risk
            'expiryStats'              => $expiryStats,
            'expiryPriority'           => $expiryPriority,

            'productionTrendLabels'    => $productionTrendLabels,
            'productionTrendSeries'    => $productionTrendSeries,       // units

            'forecastLabels'           => $forecast['labels'],
            'forecastDemandSeries'     => $forecastDemandSeries,        // units
            'forecastInventorySeries'  => $forecastInventorySeries,     // units
            'forecastSummary'          => $forecastSummary,
            'forecastTopProducts'      => $forecastTopProducts,         // units + unit_type + label

            'productForecast'          => $productForecast,             // per-product AI plan in units
        ]);
    }

    public function data()
    {
        $start = Carbon::now()->startOfWeek();
        $end   = Carbon::now()->endOfWeek();
        $today = Carbon::today();

        // Rolling 7 day window for expiry (today + next 6 days)
        $expiryStart = $today->copy();
        $expiryEnd   = $today->copy()->addDays(6);

        // Finished goods: still potentially kg internally, converted to units for JSON.
        $QTY   = 'COALESCE(sales.quantity_kg, sales.quantity, 0)';
        $UNIT  = 'COALESCE(sales.unit_price, sales.price, 0)';
        $REVEX = "$QTY * $UNIT";

        // Labels for weekly charts
        $labels = [];
        $p = $start->copy();
        while ($p->lte($end)) {
            $labels[] = $p->format('D');
            $p->addDay();
        }

        // Production (current week → units)
        $prodDaily = Production::whereBetween('production_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('production_date as d, SUM(quantity) as qty')
            ->groupBy('d')
            ->pluck('qty', 'd')
            ->all();

        $weeklyProductionSeries = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $rawQty = (float) ($prodDaily[$cursor->toDateString()] ?? 0);
            $weeklyProductionSeries[] = $this->convertKgToPacks($rawQty);
            $cursor->addDay();
        }

        // Sales (current week → units + revenue)
        $salesDaily = Sale::whereBetween(DB::raw('DATE(date)'), [$start->toDateString(), $end->toDateString()])
            ->selectRaw("DATE(date) as d, SUM($QTY) as qty, SUM($REVEX) as rev")
            ->groupBy('d')
            ->get()
            ->keyBy('d');

        $weeklySalesQtySeries     = [];
        $weeklySalesRevenueSeries = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key                        = $cursor->toDateString();
            $rawQty                     = (float) ($salesDaily[$key]->qty ?? 0);
            $weeklySalesQtySeries[]     = $this->convertKgToPacks($rawQty);
            $weeklySalesRevenueSeries[] = (float) ($salesDaily[$key]->rev ?? 0);
            $cursor->addDay();
        }

        $weekRevenue = array_sum($weeklySalesRevenueSeries);

        // For the JSON endpoint, keep profit simple with a fixed margin (e.g., 30%),
        // so we don't repeat the heavy materials join.
        $marginRatio = 0.30;

        $weeklySalesProfitSeries = [];
        foreach ($weeklySalesRevenueSeries as $revDay) {
            $weeklySalesProfitSeries[] = (float) $revDay * $marginRatio;
        }

        /* Expiry predictive + type for AJAX (rolling 7 days) */
        $expiryBuckets = [];
        $expiryLabels  = [];
        $cursor = $expiryStart->copy();
        while ($cursor->lte($expiryEnd)) {
            $expiryBuckets[$cursor->toDateString()] = 0.0;
            $expiryLabels[] = $cursor->format('D');
            $cursor->addDay();
        }

        $expiryStats = [
            'total_expiring' => 0.0,
            'critical'       => 0.0,
            'high'           => 0.0,
            'medium'         => 0.0,
        ];

        $expiryPriorityRows = [];

        $batches = Production::with('product:id,product_name,shelf_life_days')
            ->whereDate('production_date', '<=', $expiryEnd->toDateString())
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

            $expCarbon = Carbon::parse($expDate);
            $expYmd    = $expCarbon->toDateString();

            $unitsRemaining = (float) (
                $b->remaining_units
                ?? $b->current_inventory_units
                ?? $b->current_inventory
                ?? $b->quantity_units
                ?? $b->quantity
                ?? 0
            );

            if ($unitsRemaining <= 0) {
                continue;
            }

            $variantLabel = $this->resolveBatchVariantLabel($b);

            if ($expCarbon->betweenIncluded($expiryStart, $expiryEnd) && isset($expiryBuckets[$expYmd])) {
                $expiryBuckets[$expYmd] += max(0.0, $unitsRemaining);
            }

            $daysDiff = $today->diffInDays($expCarbon, false);

            if ($daysDiff >= 0 && $daysDiff <= 6) {
                $daysLeft = max(0, $daysDiff);

                $expiryStats['total_expiring'] += $unitsRemaining;

                if ($daysLeft <= 2) {
                    $expiryStats['critical'] += $unitsRemaining;
                } elseif ($daysLeft <= 5) {
                    $expiryStats['high'] += $unitsRemaining;
                } elseif ($daysLeft <= 6) {
                    $expiryStats['medium'] += $unitsRemaining;
                }

                $expiryPriorityRows[] = [
                    'product_name'       => $b->product->product_name ?? 'Product',
                    'batch_code'         => $b->batch_code ?? $b->batch_no ?? $b->id,
                    'variant_label'      => $variantLabel,
                    'days_left'          => $daysLeft,
                    'units_at_risk'      => $unitsRemaining,
                    'recommended_action' => $this->recommendedExpiryAction($daysLeft, $unitsRemaining),
                ];
            }
        }

        $weeklyExpirySeries = [];
        $cursor = $expiryStart->copy();
        while ($cursor->lte($expiryEnd)) {
            $weeklyExpirySeries[] = (float) ($expiryBuckets[$cursor->toDateString()] ?? 0);
            $cursor->addDay();
        }

        $expiryPriority = collect($expiryPriorityRows)
            ->sortBy('days_left')
            ->values()
            ->take(10);

        /* 12-week production trend → units */
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
            $productionTrendSeries[] = $this->convertKgToPacks((float) $sumQty);
        }

        $forecast = $this->buildForecast(60, 30);

        // Map global forecast to units for JSON
        $forecastDemandSeriesBase    = $forecast['demandSeries']    ?? [];
        $forecastInventorySeriesBase = $forecast['inventorySeries'] ?? [];
        $forecastDemandSeries        = array_map(
            fn ($v) => $this->convertKgToPacks((float) $v),
            $forecastDemandSeriesBase
        );
        $forecastInventorySeries     = array_map(
            fn ($v) => $this->convertKgToPacks((float) $v),
            $forecastInventorySeriesBase
        );

        $forecastSummary = $forecast['summary'] ?? [];
        if (!empty($forecastSummary['total_recommended_production'])) {
            $forecastSummary['total_recommended_production'] = $this->convertKgToPacks(
                (float) $forecastSummary['total_recommended_production']
            );
        }

        // Per-product forecast "watch list" for JSON (with labels by unit_type)
        $forecastTopProducts = $forecast['topProducts'] ?? collect();
        if ($forecastTopProducts instanceof \Illuminate\Support\Collection) {
            $forecastTopProducts = $forecastTopProducts->map(function (array $row) {
                $productId = $row['product_id'] ?? null;

                $row['daily_demand']           = $this->convertKgToPacks((float) ($row['daily_demand'] ?? 0.0), $productId);
                $row['recommended_production'] = $this->convertKgToPacks((float) ($row['recommended_production'] ?? 0.0), $productId);

                $unit = strtolower($row['unit_type'] ?? 'pack');
                $name = $row['name'] ?? 'Product';
                $row['label'] = $name . ' (' . $unit . ')';

                return $row;
            });
        }

        // Simple AI-style weekly forecast for JSON (using avg price + fixed margin)
        $globalDemandSeriesBase = $forecast['demandSeries'] ?? [];

        $totalRevenue = (float) (Sale::selectRaw("SUM($REVEX) as rev")->value('rev') ?? 0);
        $totalSales   = (int) Sale::count();
        $avgUnitPrice = $totalSales > 0
            ? ($totalRevenue / max($totalSales, 1))
            : 0.0;

        $weeklySalesForecastQtySeries       = [];
        $weeklySalesForecastRevenueSeries   = [];
        $weeklySalesForecastProfitSeries    = [];

        for ($i = 0; $i < 7; $i++) {
            $demandBase  = (float) ($globalDemandSeriesBase[$i] ?? 0.0);
            $demandUnits = $this->convertKgToPacks($demandBase);

            $weeklySalesForecastQtySeries[]     = $demandUnits;
            $forecastRev                        = $demandUnits * $avgUnitPrice;
            $weeklySalesForecastRevenueSeries[] = $forecastRev;
            $weeklySalesForecastProfitSeries[]  = $forecastRev * $marginRatio;
        }

        return response()->json([
            'labels'                   => $labels,
            'weeklyProductionSeries'   => $weeklyProductionSeries,      // units
            'weeklySalesQtySeries'     => $weeklySalesQtySeries,        // units
            'weeklySalesRevenueSeries' => $weeklySalesRevenueSeries,
            'weeklySalesProfitSeries'  => $weeklySalesProfitSeries,

            'weeklySalesForecastQtySeries'       => $weeklySalesForecastQtySeries,     // units
            'weeklySalesForecastRevenueSeries'   => $weeklySalesForecastRevenueSeries,
            'weeklySalesForecastProfitSeries'    => $weeklySalesForecastProfitSeries,

            'expiryLabels'             => $expiryLabels,
            'weeklyExpirySeries'       => $weeklyExpirySeries,          // units at risk
            'expiryStats'              => $expiryStats,
            'expiryPriority'           => $expiryPriority,

            'productionTrendLabels'    => $productionTrendLabels,
            'productionTrendSeries'    => $productionTrendSeries,       // units

            'forecastLabels'           => $forecast['labels'],
            'forecastDemandSeries'     => $forecastDemandSeries,        // units
            'forecastInventorySeries'  => $forecastInventorySeries,     // units
            'forecastSummary'          => $forecastSummary,
            'forecastTopProducts'      => $forecastTopProducts,         // units + unit_type + label
        ]);
    }
}
