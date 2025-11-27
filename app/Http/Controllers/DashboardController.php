<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Material;
use App\Models\Production;
use App\Models\Sale;
use App\Models\DemandEvent;
use App\Services\DemandForecastService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /* ================================================================
     |  Unit + date helpers
     * ================================================================ */

    private function convertKgToPacks(float $kgOrUnits, ?int $productId = null): float
    {
        // TODO: implement per-product conversion from kg -> packs.
        return $kgOrUnits;
    }

    private function resolveDateRange(Request $request): array
    {
        $startInput = $request->query('start');
        $endInput   = $request->query('end');

        if ($startInput && $endInput) {
            $start = Carbon::parse($startInput)->startOfDay();
            $end   = Carbon::parse($endInput)->endOfDay();
        } else {
            $start = Carbon::now()->startOfWeek();
            $end   = Carbon::now()->endOfWeek();
        }

        return [$start, $end];
    }

    private function buildDayLabels(Carbon $start, Carbon $end): array
    {
        $labels = [];
        $p = $start->copy();

        while ($p->lte($end)) {
            $labels[] = $p->format('D');
            $p->addDay();
        }

        return $labels;
    }

    private function makeWeekBuckets(Carbon $end, int $weeks = 12): array
    {
        $start   = $end->copy()->startOfWeek()->subWeeks($weeks - 1);
        $buckets = [];
        $cursor  = $start->copy();

        while ($cursor->lte($end->copy()->endOfWeek())) {
            $buckets[$cursor->toDateString()] = 0.0; // Monday YYYY-MM-DD
            $cursor->addWeek();
        }

        return $buckets;
    }

    private function humanWeekLabel(string $weekStartYmd): string
    {
        return Carbon::parse($weekStartYmd)->format('M j');
    }

    private function resolveBatchVariantLabel(Production $b): ?string
    {
        if (!empty($b->type))           return $b->type;
        if (!empty($b->variant))        return $b->variant;
        if (!empty($b->packaging_type)) return $b->packaging_type;
        if (!empty($b->size_label))     return $b->size_label;
        if (!empty($b->packaging_size)) return $b->packaging_size;

        if ($b->relationLoaded('product') && !empty($b->product->variant_label)) {
            return $b->product->variant_label;
        }

        return null;
    }

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

        return 'Monitor and rotate stock so near expiry goes out first.';
    }

    /* ================================================================
     |  Shared blocks: expiry snapshot + trends + forecast
     * ================================================================ */

    private function buildExpirySnapshot(
        Carbon $today,
        Carbon $expiryStart,
        Carbon $expiryEnd
    ): array {
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
                } else {
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

        return [
            'labels'   => $expiryLabels,
            'series'   => $weeklyExpirySeries,
            'stats'    => $expiryStats,
            'priority' => $expiryPriority,
        ];
    }

    private function buildProductionTrend(): array
    {
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

        $labels = [];
        $series = [];
        foreach ($weekBuckets as $weekStartYmd => $sumQty) {
            $labels[] = $this->humanWeekLabel($weekStartYmd);
            $series[] = $this->convertKgToPacks((float) $sumQty);
        }

        return [$labels, $series];
    }

    private function buildForecast(int $lookbackDays = 60, int $horizonDays = 30): array
    {
        $today = Carbon::today();

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

        $productStats = [];
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

        $unitTypeMap = Sale::selectRaw("
                product_id,
                COALESCE(NULLIF(TRIM(unit_type), ''), 'pack') as unit_type
            ")
            ->whereIn('product_id', array_keys($productStats))
            ->groupBy('product_id', 'unit_type')
            ->pluck('unit_type', 'product_id');

        $avgDailyPerProduct = [];
        $riskProductsRaw    = [];
        $safetyHorizonDays  = 7;

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
            $unitType       = $unitTypeMap[$pid] ?? 'pack';

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

        $riskCollection = collect($riskProductsRaw)
            ->sortBy(fn ($p) => $p['days_to_stockout'] ?? PHP_INT_MAX)
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

    /* ================================================================
     |  Pages
     * ================================================================ */

    public function index(Request $request, DemandForecastService $demandForecastService)
    {
        [$start, $end] = $this->resolveDateRange($request);

        $today       = Carbon::today();
        $expiryStart = $today->copy();
        $expiryEnd   = $today->copy()->addDays(6);

        $QTY   = 'COALESCE(sales.quantity_kg, sales.quantity, 0)';
        $UNIT  = 'COALESCE(sales.unit_price, sales.price, 0)';
        $REVEX = "$QTY * $UNIT";
        $TYPEX = "NULLIF(TRIM(sales.type_label), '')";

        /* ======================== KPI cards ======================== */

        $totalProducts        = (int) Product::count();
        $totalMaterialsWeight = (float) (Material::sum('quantity_kg') ?? 0);
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
                DB::raw("COALESCE(NULLIF(TRIM(sales.unit_type), ''), 'pack') as unit_type"),
                DB::raw("DATE(sales.date) as date"),
            ]);

        /* ===================== Weekly labels ==================== */

        $labels = $this->buildDayLabels($start, $end);

        /* =================== Weekly Production (units) =================== */

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

        /* =================== Weekly Sales (qty + revenue) =================== */

        $salesDaily = Sale::whereBetween(DB::raw('DATE(date)'), [$start->toDateString(), $end->toDateString()])
            ->selectRaw("DATE(date) as d, SUM($QTY) as qty, SUM($REVEX) as rev")
            ->groupBy('d')
            ->get()
            ->keyBy('d');

        $weeklySalesQtySeries     = [];
        $weeklySalesRevenueSeries = [];

        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key   = $cursor->toDateString();
            $raw   = (float) ($salesDaily[$key]->qty ?? 0);
            $weeklySalesQtySeries[]     = $this->convertKgToPacks($raw);
            $weeklySalesRevenueSeries[] = (float) ($salesDaily[$key]->rev ?? 0);
            $cursor->addDay();
        }

        $weekRevenue = array_sum($weeklySalesRevenueSeries);

        $biggestSalesDay = null;
        if ($salesDaily->isNotEmpty()) {
            $maxRow = $salesDaily->sortByDesc('rev')->first();
            if ($maxRow && (float) $maxRow->rev > 0) {
                $biggestSalesDay = Carbon::parse($maxRow->d)->format('M d');
            }
        }

        /* ---------- Demand / event calendar (with reservations) ---------- */

        $demandCalendar        = [];
        $calendarEvents        = [];
        $calendarEventsByDate  = [];

        // 1) Aggregate events / reservations overlapping this range
        $eventRows = DemandEvent::with('product')
            ->where('status', '!=', 'cancelled')
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->get();

        $eventCalendarByDate = [];

        foreach ($eventRows as $e) {
            $eventStart = Carbon::parse($e->start_date)->max($start);
            $eventEnd   = Carbon::parse($e->end_date)->min($end);

            $cursor = $eventStart->copy();
            while ($cursor->lte($eventEnd)) {
                $key = $cursor->toDateString();

                if (!isset($eventCalendarByDate[$key])) {
                    $eventCalendarByDate[$key] = [
                        'reserved_units'   => 0.0,
                        'reservations_cnt' => 0,
                        'has_holiday'      => false,
                        'has_promo'        => false,
                        'event_badges'     => [],
                    ];
                }

                // reserved_qty counts toward reservations
                if (!is_null($e->reserved_qty)) {
                    $eventCalendarByDate[$key]['reserved_units'] += (float) $e->reserved_qty;
                    $eventCalendarByDate[$key]['reservations_cnt']++;
                    $eventCalendarByDate[$key]['event_badges']['reservation'] = 'Reserved units scheduled';
                }

                if ($e->event_type === 'holiday') {
                    $eventCalendarByDate[$key]['has_holiday'] = true;
                    $eventCalendarByDate[$key]['event_badges']['holiday'] = 'Holiday / closure';
                } elseif ($e->event_type === 'promo') {
                    $eventCalendarByDate[$key]['has_promo'] = true;
                    $eventCalendarByDate[$key]['event_badges']['promo'] = 'Promo / high traffic period';
                }

                $cursor->addDay();
            }
        }

        // 2) Build calendar entries for days with sales (and attach reservations)
        if ($salesDaily->isNotEmpty()) {
            $maxRev = max($salesDaily->pluck('rev')->all());

            if ($maxRev > 0) {
                foreach ($salesDaily as $date => $row) {
                    $ratio = $row->rev / $maxRev;

                    if ($ratio >= 0.7) {
                        $level = 'high';
                        $badge = 'High sales day';
                        $note  = 'Very strong performance. Expect higher demand around this date.';
                    } elseif ($ratio >= 0.4) {
                        $level = 'medium';
                        $badge = 'Medium sales day';
                        $note  = 'Healthy sales volume. Good benchmark for normal demand.';
                    } else {
                        $level = 'normal';
                        $badge = 'Normal sales day';
                        $note  = 'Regular demand pattern based on recorded sales.';
                    }

                    $eventMeta = $eventCalendarByDate[$date] ?? [
                        'reserved_units'   => 0.0,
                        'reservations_cnt' => 0,
                        'event_badges'     => [],
                    ];

                    $reservedUnits   = (float) ($eventMeta['reserved_units'] ?? 0.0);
                    $reservationsCnt = (int) ($eventMeta['reservations_cnt'] ?? 0);
                    $badges          = array_merge([$badge], array_values($eventMeta['event_badges'] ?? []));

                    $demandCalendar[$date] = $level;

                    $calendarEventsByDate[$date] = [
                        'date'            => $date,
                        'demand_level'    => $level === 'normal' ? null : $level,
                        'forecast_units'  => null,
                        'remaining_units' => null,
                        'reserved_units'  => $reservedUnits,
                        'total_revenue'   => (float) $row->rev,
                        'total_qty'       => $this->convertKgToPacks((float) $row->qty),
                        'badges'          => array_values(array_unique($badges)),
                        'note'            => $note,
                        'reservations'    => $reservedUnits,
                        'reservations_cnt'=> $reservationsCnt,
                    ];
                }
            }
        }

        // 3) Add calendar entries for days that have events/reservations but no sales
        foreach ($eventCalendarByDate as $date => $meta) {
            if (isset($calendarEventsByDate[$date])) {
                continue;
            }

            $level = 'normal';
            if (!empty($meta['has_holiday'])) {
                $level = 'high';
            } elseif (!empty($meta['has_promo'])) {
                $level = 'medium';
            }

            $demandCalendar[$date] = $level;

            $note = 'Planned events / reservations only. Align production and staffing to this day.';

            $calendarEventsByDate[$date] = [
                'date'            => $date,
                'demand_level'    => $level === 'normal' ? null : $level,
                'forecast_units'  => null,
                'remaining_units' => null,
                'reserved_units'  => (float) ($meta['reserved_units'] ?? 0.0),
                'total_revenue'   => 0.0,
                'total_qty'       => 0.0,
                'badges'          => array_values($meta['event_badges'] ?? []),
                'note'            => $note,
                'reservations'    => (float) ($meta['reserved_units'] ?? 0.0),
                'reservations_cnt'=> (int) ($meta['reservations_cnt'] ?? 0),
            ];
        }

        // Final flat array for Blade / JS
        $calendarEvents = array_values($calendarEventsByDate);

        /* ================= Materials Used (kg) ================= */

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

        /* ---------- Estimated weekly profit from materials ---------- */

        $estimatedWeekProfit      = 0.0;
        $estimatedGrossMarginPct  = null;
        $weeklySalesProfitSeries  = [];

        $estimatedWeekCost = (float) ($materialsUsageTotals['cost'] ?? 0.0);

        if ($weekRevenue > 0 && $estimatedWeekCost > 0) {
            $estimatedWeekProfit     = max(0.0, $weekRevenue - $estimatedWeekCost);
            $estimatedGrossMarginPct = round(($estimatedWeekProfit / $weekRevenue) * 100, 1);
            $profitFactor            = $estimatedWeekProfit / $weekRevenue;

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

        /* =================== Expiration Trend =================== */

        $expiryData         = $this->buildExpirySnapshot($today, $expiryStart, $expiryEnd);
        $expiryLabels       = $expiryData['labels'];
        $weeklyExpirySeries = $expiryData['series'];
        $expiryStats        = $expiryData['stats'];
        $expiryPriority     = $expiryData['priority'];

        /* ============== Most Sold Products & Variants ============== */

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

                $row->quantity = $this->convertKgToPacks((float) $row->quantity, (int) $row->product_id);

                $unit    = strtolower($row->unit_type ?? 'pack');
                $variant = trim($row->sale_type ?? '');
                $base    = trim($row->product_name . ' ' . $variant);

                $row->display_label = $base . ' (' . $unit . ')';

                return $row;
            });

        /* =================== 12-week Production Trend =================== */

        [$productionTrendLabels, $productionTrendSeries] = $this->buildProductionTrend();

        /* =================== Predictive Analytics (global forecast) =================== */

        $forecast        = $this->buildForecast(60, 30);
        $productForecast = $demandForecastService->perProductForecast(7, 60);

        $forecastDemandSeriesBase    = $forecast['demandSeries']    ?? [];
        $forecastInventorySeriesBase = $forecast['inventorySeries'] ?? [];

        $forecastDemandSeries = array_map(
            fn ($v) => $this->convertKgToPacks((float) $v),
            $forecastDemandSeriesBase
        );
        $forecastInventorySeries = array_map(
            fn ($v) => $this->convertKgToPacks((float) $v),
            $forecastInventorySeriesBase
        );

        $forecastSummary = $forecast['summary'] ?? [];
        if (!empty($forecastSummary['total_recommended_production'])) {
            $forecastSummary['total_recommended_production'] = $this->convertKgToPacks(
                (float) $forecastSummary['total_recommended_production']
            );
        }

        $forecastTopProducts = $forecast['topProducts'] ?? collect();
        if ($forecastTopProducts instanceof \Illuminate\Support\Collection) {
            $forecastTopProducts = $forecastTopProducts->map(function (array $row) {
                $productId = $row['product_id'] ?? null;

                $row['daily_demand']           = $this->convertKgToPacks((float) ($row['daily_demand'] ?? 0.0), $productId);
                $row['recommended_production'] = $this->convertKgToPacks((float) ($row['recommended_production'] ?? 0.0), $productId);

                $unit      = strtolower($row['unit_type'] ?? 'pack');
                $name      = $row['name'] ?? 'Product';
                $row['label'] = $name . ' (' . $unit . ')';

                return $row;
            });
        }

        /* ---------- Weekly AI sales forecast (next 7 days) ---------- */

        $globalDemandSeriesBase = $forecast['demandSeries'] ?? [];

        $avgUnitPriceGlobal = $totalSales > 0
            ? ($totalRevenue / max($totalSales, 1))
            : 0.0;

        $marginRatio = ($weekRevenue > 0 && $estimatedWeekProfit > 0)
            ? ($estimatedWeekProfit / $weekRevenue)
            : 0.0;

        $weeklySalesForecastQtySeries     = [];
        $weeklySalesForecastRevenueSeries = [];
        $weeklySalesForecastProfitSeries  = [];

        for ($i = 0; $i < 7; $i++) {
            $demandBase  = (float) ($globalDemandSeriesBase[$i] ?? 0.0);
            $demandUnits = $this->convertKgToPacks($demandBase);

            $weeklySalesForecastQtySeries[] = $demandUnits;

            $forecastRev = $demandUnits * $avgUnitPriceGlobal;
            $weeklySalesForecastRevenueSeries[] = $forecastRev;
            $weeklySalesForecastProfitSeries[]  = $marginRatio > 0
                ? $forecastRev * $marginRatio
                : 0.0;
        }

        /* ---------- Per-product weekly AI plan ---------- */

        if ($productForecast instanceof \Illuminate\Support\Collection) {
            $productForecast = $productForecast->map(function ($row) {
                if (is_array($row)) {
                    $productId = $row['product_id'] ?? null;
                    $row['avg_daily_demand']     = $this->convertKgToPacks((float) ($row['avg_daily_demand'] ?? 0.0), $productId);
                    $row['forecast_total']       = $this->convertKgToPacks((float) ($row['forecast_total'] ?? 0.0), $productId);
                    $row['current_inventory']    = $this->convertKgToPacks((float) ($row['current_inventory'] ?? 0.0), $productId);
                    $row['suggested_production'] = $this->convertKgToPacks((float) ($row['suggested_production'] ?? 0.0), $productId);
                    return $row;
                }

                if (is_object($row)) {
                    $productId = $row->product_id ?? null;
                    $row->avg_daily_demand     = $this->convertKgToPacks((float) ($row->avg_daily_demand ?? 0.0), $productId);
                    $row->forecast_total       = $this->convertKgToPacks((float) ($row->forecast_total ?? 0.0), $productId);
                    $row->current_inventory    = $this->convertKgToPacks((float) ($row->current_inventory ?? 0.0), $productId);
                    $row->suggested_production = $this->convertKgToPacks((float) ($row->suggested_production ?? 0.0), $productId);
                }

                return $row;
            });
        }

        $products = Product::orderBy('product_name')->get(['id', 'product_name']);

        return view('dashboard', [
            'totalProducts'                        => $totalProducts,
            'totalMaterialsWeight'                 => $totalMaterialsWeight,
            'totalRevenue'                         => $totalRevenue,
            'totalSales'                           => $totalSales,

            'recentSales'                          => $recentSales,
            'recentMaterials'                      => $recentMaterials,
            'materialsUsage'                       => $materialsUsage,
            'materialsUsageTotals'                 => $materialsUsageTotals,
            'topProducts'                          => $topProducts,
            'biggestSalesDay'                      => $biggestSalesDay,

            'labels'                               => $labels,
            'weeklyProductionSeries'               => $weeklyProductionSeries,
            'weeklySalesQtySeries'                 => $weeklySalesQtySeries,
            'weeklySalesRevenueSeries'             => $weeklySalesRevenueSeries,
            'weeklySalesProfitSeries'              => $weeklySalesProfitSeries,

            'weekRevenue'                          => $weekRevenue,
            'estimatedWeekProfit'                  => $estimatedWeekProfit,
            'estimatedGrossMarginPct'              => $estimatedGrossMarginPct,

            'weeklySalesForecastQtySeries'         => $weeklySalesForecastQtySeries,
            'weeklySalesForecastRevenueSeries'     => $weeklySalesForecastRevenueSeries,
            'weeklySalesForecastProfitSeries'      => $weeklySalesForecastProfitSeries,

            'expiryLabels'                         => $expiryLabels,
            'weeklyExpirySeries'                   => $weeklyExpirySeries,
            'expiryStats'                          => $expiryStats,
            'expiryPriority'                       => $expiryPriority,

            'productionTrendLabels'                => $productionTrendLabels,
            'productionTrendSeries'                => $productionTrendSeries,

            'forecastLabels'                       => $forecast['labels'],
            'forecastDemandSeries'                 => $forecastDemandSeries,
            'forecastInventorySeries'              => $forecastInventorySeries,
            'forecastSummary'                      => $forecastSummary,
            'forecastTopProducts'                  => $forecastTopProducts,

            'productForecast'                      => $productForecast,

            'filterStart'                          => $start->toDateString(),
            'filterEnd'                            => $end->toDateString(),
            'demandCalendar'                       => $demandCalendar,
            'calendarEvents'                       => $calendarEvents,
            'products'                             => $products,
        ]);
    }

    /**
     * Lightweight JSON endpoint for async dashboard refresh.
     */
    public function data(Request $request)
    {
        [$start, $end] = $this->resolveDateRange($request);

        $today       = Carbon::today();
        $expiryStart = $today->copy();
        $expiryEnd   = $today->copy()->addDays(6);

        $QTY   = 'COALESCE(sales.quantity_kg, sales.quantity, 0)';
        $UNIT  = 'COALESCE(sales.unit_price, sales.price, 0)';
        $REVEX = "$QTY * $UNIT";

        $labels = $this->buildDayLabels($start, $end);

        /* ---------- Production ---------- */

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

        /* ---------- Sales + simple profit ---------- */

        $salesDaily = Sale::whereBetween(DB::raw('DATE(date)'), [$start->toDateString(), $end->toDateString()])
            ->selectRaw("DATE(date) as d, SUM($QTY) as qty, SUM($REVEX) as rev")
            ->groupBy('d')
            ->get()
            ->keyBy('d');

        $weeklySalesQtySeries     = [];
        $weeklySalesRevenueSeries = [];

        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key   = $cursor->toDateString();
            $raw   = (float) ($salesDaily[$key]->qty ?? 0);
            $weeklySalesQtySeries[]     = $this->convertKgToPacks($raw);
            $weeklySalesRevenueSeries[] = (float) ($salesDaily[$key]->rev ?? 0);
            $cursor->addDay();
        }

        $weekRevenue  = array_sum($weeklySalesRevenueSeries);
        $marginRatio  = 0.30;
        $weeklySalesProfitSeries = [];

        foreach ($weeklySalesRevenueSeries as $revDay) {
            $weeklySalesProfitSeries[] = (float) $revDay * $marginRatio;
        }

        /* ---------- Expiry snapshot ---------- */

        $expiryData         = $this->buildExpirySnapshot($today, $expiryStart, $expiryEnd);
        $expiryLabels       = $expiryData['labels'];
        $weeklyExpirySeries = $expiryData['series'];
        $expiryStats        = $expiryData['stats'];
        $expiryPriority     = $expiryData['priority'];

        /* ---------- 12-week production trend ---------- */

        [$productionTrendLabels, $productionTrendSeries] = $this->buildProductionTrend();

        /* ---------- Global forecast ---------- */

        $forecast = $this->buildForecast(60, 30);

        $forecastDemandSeriesBase    = $forecast['demandSeries']    ?? [];
        $forecastInventorySeriesBase = $forecast['inventorySeries'] ?? [];

        $forecastDemandSeries = array_map(
            fn ($v) => $this->convertKgToPacks((float) $v),
            $forecastDemandSeriesBase
        );
        $forecastInventorySeries = array_map(
            fn ($v) => $this->convertKgToPacks((float) $v),
            $forecastInventorySeriesBase
        );

        $forecastSummary = $forecast['summary'] ?? [];
        if (!empty($forecastSummary['total_recommended_production'])) {
            $forecastSummary['total_recommended_production'] = $this->convertKgToPacks(
                (float) $forecastSummary['total_recommended_production']
            );
        }

        $forecastTopProducts = $forecast['topProducts'] ?? collect();
        if ($forecastTopProducts instanceof \Illuminate\Support\Collection) {
            $forecastTopProducts = $forecastTopProducts->map(function (array $row) {
                $productId = $row['product_id'] ?? null;

                $row['daily_demand']           = $this->convertKgToPacks((float) ($row['daily_demand'] ?? 0.0), $productId);
                $row['recommended_production'] = $this->convertKgToPacks((float) ($row['recommended_production'] ?? 0.0), $productId);

                $unit      = strtolower($row['unit_type'] ?? 'pack');
                $name      = $row['name'] ?? 'Product';
                $row['label'] = $name . ' (' . $unit . ')';

                return $row;
            });
        }

        /* ---------- Weekly AI forecast (fixed margin) ---------- */

        $globalDemandSeriesBase = $forecast['demandSeries'] ?? [];

        $totalRevenue = (float) (Sale::selectRaw("SUM($REVEX) as rev")->value('rev') ?? 0);
        $totalSales   = (int) Sale::count();

        $avgUnitPrice = $totalSales > 0
            ? ($totalRevenue / max($totalSales, 1))
            : 0.0;

        $weeklySalesForecastQtySeries     = [];
        $weeklySalesForecastRevenueSeries = [];
        $weeklySalesForecastProfitSeries  = [];

        for ($i = 0; $i < 7; $i++) {
            $demandBase  = (float) ($globalDemandSeriesBase[$i] ?? 0.0);
            $demandUnits = $this->convertKgToPacks($demandBase);

            $weeklySalesForecastQtySeries[]     = $demandUnits;
            $forecastRev                        = $demandUnits * $avgUnitPrice;
            $weeklySalesForecastRevenueSeries[] = $forecastRev;
            $weeklySalesForecastProfitSeries[]  = $forecastRev * $marginRatio;
        }

        return response()->json([
            'labels'                           => $labels,
            'weeklyProductionSeries'           => $weeklyProductionSeries,
            'weeklySalesQtySeries'             => $weeklySalesQtySeries,
            'weeklySalesRevenueSeries'         => $weeklySalesRevenueSeries,
            'weeklySalesProfitSeries'          => $weeklySalesProfitSeries,

            'weeklySalesForecastQtySeries'     => $weeklySalesForecastQtySeries,
            'weeklySalesForecastRevenueSeries' => $weeklySalesForecastRevenueSeries,
            'weeklySalesForecastProfitSeries'  => $weeklySalesForecastProfitSeries,

            'expiryLabels'                     => $expiryLabels,
            'weeklyExpirySeries'               => $weeklyExpirySeries,
            'expiryStats'                      => $expiryStats,
            'expiryPriority'                   => $expiryPriority,

            'productionTrendLabels'            => $productionTrendLabels,
            'productionTrendSeries'            => $productionTrendSeries,

            'forecastLabels'                   => $forecast['labels'],
            'forecastDemandSeries'             => $forecastDemandSeries,
            'forecastInventorySeries'          => $forecastInventorySeries,
            'forecastSummary'                  => $forecastSummary,
            'forecastTopProducts'              => $forecastTopProducts,

            'filterStart'                      => $start->toDateString(),
            'filterEnd'                        => $end->toDateString(),
        ]);
    }

    /**
     * Daily snapshot endpoint for the calendar.
     */
    public function daySnapshot(Request $request)
    {
        $dateParam = $request->query('date');
        $day       = $dateParam ? Carbon::parse($dateParam) : Carbon::today();
        $dayYmd    = $day->toDateString();
        $today     = Carbon::today();

        $QTY   = 'COALESCE(sales.quantity_kg, sales.quantity, 0)';
        $UNIT  = 'COALESCE(sales.unit_price, sales.price, 0)';
        $REVEX = "$QTY * $UNIT";

        // Summary for this day
        $summaryRow = Sale::whereDate('date', $dayYmd)
            ->selectRaw("
                SUM($QTY) as qty,
                SUM($REVEX) as revenue,
                COUNT(*) as order_count,
                COUNT(DISTINCT product_id) as product_count
            ")
            ->first();

        $dayRevenue = (float) ($summaryRow->revenue ?? 0.0);
        $dayQtyRaw  = (float) ($summaryRow->qty ?? 0.0);

        // Top products
        $topProducts = Sale::leftJoin('products as p', 'p.id', '=', 'sales.product_id')
            ->whereDate('sales.date', $dayYmd)
            ->selectRaw("
                sales.product_id,
                COALESCE(p.product_name, sales.product, 'Product') as product_name,
                NULLIF(TRIM(sales.type_label), '') as sale_variant,
                COALESCE(NULLIF(TRIM(sales.unit_type), ''), 'pack') as unit_type,
                SUM($QTY) as quantity,
                SUM($REVEX) as revenue
            ")
            ->groupBy(
                'sales.product_id',
                'p.product_name',
                'sales.product',
                'sales.type_label',
                'sales.unit_type'
            )
            ->orderByDesc('revenue')
            ->limit(20)
            ->get()
            ->map(function ($row) {
                $row->quantity_units = $this->convertKgToPacks(
                    (float) $row->quantity,
                    (int) $row->product_id
                );

                $variant = trim($row->sale_variant ?? '');
                $unit    = strtolower($row->unit_type ?? 'pack');

                $baseName = trim($row->product_name . ' ' . $variant);
                $row->display_label = $baseName . ' (' . $unit . ')';

                return $row;
            });

        // Baseline for demand classification
        $historyStart = $day->copy()->subDays(30)->toDateString();
        $historyEnd   = $day->copy()->subDay()->toDateString();

        $history = Sale::whereBetween(DB::raw('DATE(date)'), [$historyStart, $historyEnd])
            ->selectRaw("DATE(date) as d, SUM($REVEX) as revenue")
            ->groupBy('d')
            ->get();

        $avgRev = 0.0;
        if ($history->isNotEmpty()) {
            $avgRev = $history->sum('revenue') / max(1, $history->count());
        }

        $demandLevel = 'no_data';
        if ($dayRevenue > 0 && $avgRev > 0) {
            if ($dayRevenue >= $avgRev * 1.3) {
                $demandLevel = 'high';
            } elseif ($dayRevenue <= $avgRev * 0.7) {
                $demandLevel = 'low';
            } else {
                $demandLevel = 'normal';
            }
        } elseif ($dayRevenue > 0) {
            $demandLevel = 'normal';
        }

        // Events (holiday / promo / reservations)
        $events = DemandEvent::with('product')
            ->whereDate('start_date', '<=', $dayYmd)
            ->whereDate('end_date', '>=', $dayYmd)
            ->where('status', '!=', 'cancelled')
            ->get();

        $isHoliday = $events->contains(fn ($e) => $e->event_type === 'holiday');
        $hasPromo  = $events->contains(fn ($e) => $e->event_type === 'promo');

        // 🔹 Reservations: sum reserved_qty for this day
        $reservedUnits = (float) $events->sum('reserved_qty');

        // Holiday season window: ±2 days of any holiday event
        $holidaySeason = $isHoliday;
        if (!$holidaySeason) {
            $holidaySeason = DemandEvent::where('event_type', 'holiday')
                ->where('status', '!=', 'cancelled')
                ->whereDate('start_date', '<=', $day->copy()->addDays(2)->toDateString())
                ->whereDate('end_date', '>=', $day->copy()->subDays(2)->toDateString())
                ->exists();
        }

        // If date is in future, treat as forecast variants
        if ($day->gt($today)) {
            if ($isHoliday) {
                $demandLevel = 'forecast_high';
            } elseif ($holidaySeason) {
                $demandLevel = 'forecast_medium';
            } elseif ($hasPromo) {
                $demandLevel = 'forecast_medium';
            } else {
                $demandLevel = $demandLevel === 'no_data' ? 'forecast_normal' : $demandLevel;
            }
        } else {
            if ($holidaySeason && $demandLevel === 'normal') {
                $demandLevel = 'high';
            } elseif ($holidaySeason && $demandLevel === 'low') {
                $demandLevel = 'normal';
            }
        }

        // 🔹 Map global forecast onto this specific date for the sidebar
        $forecastDemandUnits    = null;
        $forecastRemainingUnits = null;
        $netAvailable           = null;

        $forecast = $this->buildForecast(60, 30);
        $demandBaseSeries    = $forecast['demandSeries']    ?? [];
        $inventoryBaseSeries = $forecast['inventorySeries'] ?? [];

        $horizon = min(count($demandBaseSeries), count($inventoryBaseSeries));
        $offset  = $today->diffInDays($day, false);

        if ($offset >= 0 && $offset < $horizon) {
            $forecastDemandUnits    = $this->convertKgToPacks((float) $demandBaseSeries[$offset]);
            $forecastRemainingUnits = $this->convertKgToPacks((float) $inventoryBaseSeries[$offset]);
            $netAvailable           = $forecastRemainingUnits - $reservedUnits;
        }

        $eventPayload = $events->map(function (DemandEvent $e) {
            return [
                'id'           => $e->id,
                'title'        => $e->title,
                'event_type'   => $e->event_type,
                'status'       => $e->status,
                'product_id'   => $e->product_id,
                'product_name' => optional($e->product)->product_name,
                'start_date'   => $e->start_date,
                'end_date'     => $e->end_date,
                'reserved_qty' => (float) $e->reserved_qty,
                'unit_type'    => $e->unit_type ?? 'pack',
                'notes'        => $e->notes,
            ];
        });

        return response()->json([
            'date'    => $dayYmd,
            'summary' => [
                'total_revenue'           => $dayRevenue,
                'total_qty'               => $this->convertKgToPacks($dayQtyRaw),
                'order_count'             => (int) ($summaryRow->order_count ?? 0),
                'product_count'           => (int) ($summaryRow->product_count ?? 0),
                'demand_level'            => $demandLevel,
                'is_holiday'              => $isHoliday,
                'is_holiday_season'       => $holidaySeason,
                // 👇 for Day Insights sidebar
                'reserved_units'          => $reservedUnits,
                'forecast_demand_units'   => $forecastDemandUnits,
                'forecast_remaining_units'=> $forecastRemainingUnits,
                'net_available_units'     => $netAvailable,
            ],
            'products' => $topProducts,
            'events'   => $eventPayload,
        ]);
    }
}
