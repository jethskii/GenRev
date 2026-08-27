<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Material;
use App\Models\Production;
use App\Models\Sale;
use App\Models\DemandEvent;
use App\Models\Reservation;
use App\Services\DemandForecastService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /* ================================================================
     |  Unit + date helpers
     * ================================================================ */

    private function convertKgToPacks(float $kgOrUnits, ?int $productId = null): float
    {
        // For now this is identity. If you later add per-product conversion
        // (e.g. 1 pack = X kg for each product), you can use $productId here.
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

    private function salesDateExpr(): string
    {
        $hasOrder = Schema::hasColumn('sales', 'order_date');
        $hasDate  = Schema::hasColumn('sales', 'date');

        if ($hasOrder && $hasDate) {
            $coalesce = "COALESCE(order_date, date)";
        } elseif ($hasOrder) {
            $coalesce = "order_date";
        } elseif ($hasDate) {
            $coalesce = "date";
        } else {
            $coalesce = "created_at";
        }

        return "DATE($coalesce)";
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
            $buckets[$cursor->toDateString()] = 0.0;
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
     |  NEW: Auto-archive expired batches (expiry today or already passed)
     * ================================================================ */

    /**
     * Auto-archive (soft delete) production batches whose expiration date
     * is today or already passed (<= today).
     *
     * - Skips batches that have linked sales (to preserve production_id links).
     * - Sets archived_at / archived_reason if those columns exist.
     * - Updates product balance after archiving.
     */
    private function autoArchiveExpiredProductions(Carbon $today): int
    {
        $todayYmd = $today->toDateString();

        $hasArchivedAt     = Schema::hasColumn('productions', 'archived_at');
        $hasArchivedReason = Schema::hasColumn('productions', 'archived_reason');

        $expired = collect();

        // 1) Explicit expiration_date
        $expired = $expired->merge(
            Production::query()
                ->with('product:id,shelf_life_days')
                ->whereNotNull('expiration_date')
                ->whereDate('expiration_date', '<=', $todayYmd)
                ->get()
        );

        // 2) No expiration_date -> compute via shelf_life_days (default 7)
        $computed = Production::query()
            ->with('product:id,shelf_life_days')
            ->whereNull('expiration_date')
            ->whereNotNull('production_date')
            ->whereDate('production_date', '<=', $todayYmd)
            ->get();

        foreach ($computed as $b) {
            $shelf = (int) ($b->product->shelf_life_days ?? 7);
            $exp   = Carbon::parse($b->production_date)->addDays($shelf);

            if ($exp->toDateString() <= $todayYmd) {
                $expired->push($b);
            }
        }

        $expired = $expired->unique('id')->values();

        if ($expired->isEmpty()) {
            return 0;
        }

        $archivedCount = 0;
        $affectedProductIds = [];

        DB::transaction(function () use (
            $expired,
            $hasArchivedAt,
            $hasArchivedReason,
            &$archivedCount,
            &$affectedProductIds
        ) {
            foreach ($expired as $p) {
                // Skip if linked sales exist (keep existing rule behavior)
                if (Sale::where('production_id', $p->id)->exists()) {
                    continue;
                }

                if ($hasArchivedAt) {
                    $p->archived_at = now();
                }
                if ($hasArchivedReason) {
                    $p->archived_reason = $p->archived_reason ?: 'production expiry (auto)';
                }

                $p->save();
                $p->delete(); // soft delete -> archived listing

                $archivedCount++;
                $affectedProductIds[] = (int) $p->product_id;
            }
        });

        $affectedProductIds = array_values(array_unique($affectedProductIds));
        foreach ($affectedProductIds as $pid) {
            $this->recomputeProductBalance($pid);
        }

        if ($archivedCount > 0) {
            Log::info('Dashboard auto-archived expired batches', [
                'count' => $archivedCount,
                'today' => $todayYmd,
            ]);
        }

        return $archivedCount;
    }

    /**
     * Minimal balance recompute (matches your ProductionController logic)
     * so product.quantity stays accurate after auto-archiving.
     */
    private function recomputeProductBalance(int $productId): void
    {
        $produced = (float) Production::where('product_id', $productId)->sum('quantity');

        $sold = (float) Sale::where('product_id', $productId)
            ->select(DB::raw(
                'COALESCE(SUM(quantity_kg), 0) + COALESCE(SUM(quantity), 0) as s'
            ))
            ->value('s');

        $balance = max(0.0, $produced - $sold);
        $latestProdDate = Production::where('product_id', $productId)->max('production_date');

        $product = Product::find($productId);
        if (!$product) {
            Product::where('id', $productId)->update([
                'quantity' => $balance,
                'stock_status' => $balance > 0 ? 'in_stock' : 'out_of_stock',
                'production_date' => $latestProdDate,
            ]);
            return;
        }

        $product->quantity = $balance;
        $product->stock_status = $balance > 0 ? 'in_stock' : 'out_of_stock';
        $product->production_date = $latestProdDate;
        $product->save();
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

                // 🔗 Fully linked to real Production batch
                $batchNumber = $b->batch_number
                    ?? $b->batch_code
                    ?? $b->batch_no
                    ?? $b->id;

                $expiryPriorityRows[] = [
                    'product_id'         => $b->product_id,
                    'production_id'      => $b->id,
                    'product_name'       => $b->product->product_name ?? 'Product',
                    'batch_number'       => $batchNumber,
                    'batch_code'         => $batchNumber,
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

        // Sort by days_left (soonest expiry first), take top 10 and add display index
        $expiryPriority = collect($expiryPriorityRows)
            ->sortBy('days_left')
            ->values()
            ->take(10)
            ->map(function ($row, $index) {
                $row['batch_display_number'] = $index + 1;
                return $row;
            });

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

        $dateExpr = $this->salesDateExpr();

        $salesHistory = Sale::whereBetween(DB::raw($dateExpr), [$windowStart, $windowEnd])
            ->selectRaw("product_id, $dateExpr as d, SUM($qtyExpr) as qty")
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

        $today = Carbon::today();

        // ✅ AUTO-ARCHIVE expired batches globally (expiry today or already passed)
        $this->autoArchiveExpiredProductions($today);

        $expiryStart = $today->copy();
        $expiryEnd   = $today->copy()->addDays(6);

        $QTY   = 'COALESCE(sales.quantity_kg, sales.quantity, 0)';
        $UNIT  = 'COALESCE(sales.unit_price, sales.price, 0)';
        $REVEX = "$QTY * $UNIT";
        $TYPEX = "NULLIF(TRIM(sales.type_label), '')";
        $dateExpr = $this->salesDateExpr();

        /* ======================== KPI cards ======================== */

        $totalProducts        = (int) Product::count();
        $totalMaterialsWeight = (float) (Material::sum('quantity_kg') ?? 0);
        $totalRevenue         = (float) (Sale::selectRaw("SUM($REVEX) as rev")->value('rev') ?? 0);
        $totalSales           = (int) Sale::count();

        /* ===================== Recent sales table ==================== */

        $recentSales = Sale::leftJoin('products as p', 'p.id', '=', 'sales.product_id')
            ->orderByDesc(DB::raw($dateExpr))
            ->orderByDesc('sales.id')
            ->take(8)
            ->get([
                DB::raw("COALESCE(p.product_name, sales.product, 'Product') as product_name"),
                DB::raw("$TYPEX as sale_type"),
                DB::raw("$QTY  as quantity"),
                DB::raw("$UNIT as unit_price"),
                DB::raw("COALESCE(NULLIF(TRIM(sales.unit_type), ''), 'pack') as unit_type"),
                DB::raw("$dateExpr as date"),
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

        $salesDaily = Sale::whereBetween(DB::raw($dateExpr), [$start->toDateString(), $end->toDateString()])
            ->selectRaw("$dateExpr as d, SUM($QTY) as qty, SUM($REVEX) as rev")
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

        /* ---------- Global forecast (shared: charts + calendar + snapshot) ---------- */

        $forecast = $this->buildForecast(60, 30);

        $forecastDemandSeriesBase    = $forecast['demandSeries']    ?? [];
        $forecastInventorySeriesBase = $forecast['inventorySeries'] ?? [];

        $forecastByDate   = [];
        $forecastStart    = Carbon::today();
        $horizonForecast  = min(count($forecastDemandSeriesBase), count($forecastInventorySeriesBase));

        for ($i = 0; $i < $horizonForecast; $i++) {
            $date = $forecastStart->copy()->addDays($i)->toDateString();
            $forecastByDate[$date] = [
                'demand'    => (float) $forecastDemandSeriesBase[$i],
                'inventory' => (float) $forecastInventorySeriesBase[$i],
            ];
        }

        $maxForecastDemand = $horizonForecast > 0
            ? max($forecastDemandSeriesBase)
            : 0.0;

        /* ---------- Demand / event calendar (with reservations + forecast) ---------- */

        $demandCalendar        = [];
        $calendarEvents        = [];
        $calendarEventsByDate  = [];

        // 1) Aggregate events / reservations overlapping this range

        // 🔹 Demand events (holidays, promos, older reservation logic)
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
                        'reserved_units'     => 0.0,
                        'reservations_cnt'   => 0,
                        'has_holiday'        => false,
                        'has_promo'          => false,
                        'event_badges'       => [],
                        'reservation_items'  => [],
                    ];
                }

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

        // 🔹 Per-pack / per-bag Reservations (Reservation model)
        $reservationRows = Reservation::with('product')
            ->whereBetween('reserved_date', [$start->toDateString(), $end->toDateString()])
            ->where('status', Reservation::STATUS_RESERVED)
            ->get();

        foreach ($reservationRows as $r) {
            $key = Carbon::parse($r->reserved_date)->toDateString();

            if (!isset($eventCalendarByDate[$key])) {
                $eventCalendarByDate[$key] = [
                    'reserved_units'     => 0.0,
                    'reservations_cnt'   => 0,
                    'has_holiday'        => false,
                    'has_promo'          => false,
                    'event_badges'       => [],
                    'reservation_items'  => [],
                ];
            }

            $eventCalendarByDate[$key]['reserved_units']   += (float) $r->units;
            $eventCalendarByDate[$key]['reservations_cnt'] += 1;
            $eventCalendarByDate[$key]['event_badges']['reservation'] = 'Reserved units scheduled';

            $eventCalendarByDate[$key]['reservation_items'][] = [
                'id'           => $r->id,
                'product_id'   => $r->product_id,
                'product_name' => optional($r->product)->product_name,
                'units'        => (int) $r->units,
                'unit_type'    => $r->unit_type,
                'customer'     => $r->customer_name,
                'reference'    => $r->reference_code,
                'notes'        => $r->notes,
            ];
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
                        'reserved_units'     => 0.0,
                        'reservations_cnt'   => 0,
                        'event_badges'       => [],
                        'reservation_items'  => [],
                    ];

                    $reservedUnits    = (float) ($eventMeta['reserved_units'] ?? 0.0);
                    $reservationsCnt  = (int) ($eventMeta['reservations_cnt'] ?? 0);
                    $reservationItems = $eventMeta['reservation_items'] ?? [];
                    $badges           = array_merge([$badge], array_values($eventMeta['event_badges'] ?? []));

                    // Forecast + net available for this day (if present)
                    $demandUnits    = null;
                    $inventoryUnits = null;
                    $netAvailable   = null;

                    if (isset($forecastByDate[$date])) {
                        $rawDemand    = (float) ($forecastByDate[$date]['demand'] ?? 0.0);
                        $rawInventory = (float) ($forecastByDate[$date]['inventory'] ?? 0.0);

                        $demandUnits    = $this->convertKgToPacks($rawDemand);
                        $inventoryUnits = $this->convertKgToPacks($rawInventory);
                        $netAvailable   = $inventoryUnits - $reservedUnits;
                    }

                    // Primary reservation info for Product / Unit type / Reference
                    $primaryReservation = $reservationItems[0] ?? null;
                    $productName        = $primaryReservation['product_name'] ?? null;
                    $unitType           = $primaryReservation['unit_type'] ?? null;

                    $refPieces = [];
                    if (!empty($primaryReservation['customer'])) {
                        $refPieces[] = $primaryReservation['customer'];
                    }
                    if (!empty($primaryReservation['reference'])) {
                        $refPieces[] = $primaryReservation['reference'];
                    }
                    if (!empty($primaryReservation['notes'])) {
                        $refPieces[] = $primaryReservation['notes'];
                    }
                    $reservationSummary = count($refPieces) ? implode(' | ', $refPieces) : null;

                    $demandCalendar[$date] = $level;

                    $calendarEventsByDate[$date] = [
                        'date'                => $date,
                        'demand_level'        => $level === 'normal' ? null : $level,
                        'demand_units'        => $demandUnits,
                        'inventory_units'     => $inventoryUnits,
                        'net_available'       => $netAvailable,

                        // Extra aliases for front-end normalizer
                        'demand'              => $demandUnits,
                        'inventory'           => $inventoryUnits,

                        'reserved_units'      => $reservedUnits,
                        'total_revenue'       => (float) $row->rev,
                        'total_qty'           => $this->convertKgToPacks((float) $row->qty),

                        'badges'              => array_values(array_unique($badges)),
                        'note'                => $note,
                        'notes'               => $note,  // alias for JS normalizer
                        'event'               => $badge, // single main event label

                        'reservations'        => $reservedUnits,
                        'reservations_cnt'    => $reservationsCnt,
                        'reservation_items'   => $reservationItems,

                        'product_name'        => $productName,
                        'product_label'       => $productName,  // used by JS sidebar
                        'unit_type'           => $unitType,
                        'reservation_summary' => $reservationSummary,
                        'reference'           => $reservationSummary, // alias for JS
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

            $reservedUnits    = (float) ($meta['reserved_units'] ?? 0.0);
            $reservationItems = $meta['reservation_items'] ?? [];

            // attach forecast + net
            $demandUnits    = null;
            $inventoryUnits = null;
            $netAvailable   = null;

            if (isset($forecastByDate[$date])) {
                $rawDemand    = (float) ($forecastByDate[$date]['demand'] ?? 0.0);
                $rawInventory = (float) ($forecastByDate[$date]['inventory'] ?? 0.0);

                $demandUnits    = $this->convertKgToPacks($rawDemand);
                $inventoryUnits = $this->convertKgToPacks($rawInventory);
                $netAvailable   = $inventoryUnits - $reservedUnits;
            }

            // primary reservation for sidebar
            $primaryReservation = $reservationItems[0] ?? null;
            $productName        = $primaryReservation['product_name'] ?? null;
            $unitType           = $primaryReservation['unit_type'] ?? null;

            $refPieces = [];
            if (!empty($primaryReservation['customer'])) {
                $refPieces[] = $primaryReservation['customer'];
            }
            if (!empty($primaryReservation['reference'])) {
                $refPieces[] = $primaryReservation['reference'];
            }
            if (!empty($primaryReservation['notes'])) {
                $refPieces[] = $primaryReservation['notes'];
            }
            $reservationSummary = count($refPieces) ? implode(' | ', $refPieces) : null;

            $demandCalendar[$date] = $level;

            $note = 'Planned events / reservations only. Align production and staffing to this day.';

            $calendarEventsByDate[$date] = [
                'date'                => $date,
                'demand_level'        => $level === 'normal' ? null : $level,
                'demand_units'        => $demandUnits,
                'inventory_units'     => $inventoryUnits,
                'net_available'       => $netAvailable,

                // aliases
                'demand'              => $demandUnits,
                'inventory'           => $inventoryUnits,

                'reserved_units'      => $reservedUnits,
                'total_revenue'       => 0.0,
                'total_qty'           => 0.0,
                'badges'              => array_values($meta['event_badges'] ?? []),
                'note'                => $note,
                'notes'               => $note,
                'event'               => !empty($meta['event_badges'])
                    ? implode(', ', array_values($meta['event_badges']))
                    : null,

                'reservations'        => $reservedUnits,
                'reservations_cnt'    => (int) ($meta['reservations_cnt'] ?? 0),
                'reservation_items'   => $reservationItems,

                'product_name'        => $productName,
                'product_label'       => $productName,
                'unit_type'           => $unitType,
                'reservation_summary' => $reservationSummary,
                'reference'           => $reservationSummary,
            ];
        }

        // 4) Fill remaining *future* dates using forecast only (no sales, no events)
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $date = $cursor->toDateString();

            if (isset($calendarEventsByDate[$date])) {
                $cursor->addDay();
                continue;
            }

            if ($cursor->gt($today) && isset($forecastByDate[$date]) && $maxForecastDemand > 0) {
                $rawForecastDemand = (float) $forecastByDate[$date]['demand'] ?? 0.0;

                if ($rawForecastDemand > 0) {
                    $ratio = $rawForecastDemand / $maxForecastDemand;

                    if ($ratio >= 0.7) {
                        $level = 'forecast_high';
                        $badge = 'Forecast high demand day';
                        $note  = 'Projected strong demand. Plan additional production and staffing.';
                    } elseif ($ratio >= 0.4) {
                        $level = 'forecast_medium';
                        $badge = 'Forecast medium demand day';
                        $note  = 'Projected healthy demand. Use as baseline for planning.';
                    } else {
                        $level = 'forecast_normal';
                        $badge = 'Forecast normal demand day';
                        $note  = 'Projected regular demand based on recent sales.';
                    }

                    $forecastUnitsRaw   = (float) ($forecastByDate[$date]['demand'] ?? 0.0);
                    $remainingUnitsRaw  = (float) ($forecastByDate[$date]['inventory'] ?? 0.0);

                    $forecastUnits   = $this->convertKgToPacks($forecastUnitsRaw);
                    $remainingUnits  = $this->convertKgToPacks($remainingUnitsRaw);
                    $netAvailable    = $remainingUnits; // no reservations

                    $demandCalendar[$date] = $level;

                    $calendarEventsByDate[$date] = [
                        'date'              => $date,
                        'demand_level'      => $level,
                        'demand_units'      => $forecastUnits,
                        'inventory_units'   => $remainingUnits,
                        'net_available'     => $netAvailable,

                        // aliases
                        'demand'            => $forecastUnits,
                        'inventory'         => $remainingUnits,

                        'reserved_units'    => 0.0,
                        'total_revenue'     => 0.0,
                        'total_qty'         => 0.0,
                        'badges'            => [$badge],
                        'note'              => $note,
                        'notes'             => $note,
                        'event'             => $badge,

                        'reservations'      => 0.0,
                        'reservations_cnt'  => 0,
                        'reservation_items' => [],

                        'product_name'      => null,
                        'product_label'     => null,
                        'unit_type'         => null,
                        'reservation_summary' => null,
                        'reference'         => null,
                    ];
                }
            }

            $cursor->addDay();
        }

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
            ->whereBetween(DB::raw($dateExpr), [$start->toDateString(), $end->toDateString()])
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

        $productForecast = $demandForecastService->perProductForecast(7, 60);

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

                $unit          = strtolower($row['unit_type'] ?? 'pack');
                $name          = $row['name'] ?? 'Product';
                $row['label']  = $name . ' (' . $unit . ')';

                return $row;
            });
        }

        /* ---------- Weekly AI sales forecast (next 7 days) ---------- */

        $globalDemandSeriesBase = $forecastDemandSeriesBase;

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

        // 🔚 view return
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
     * (Your original implementation goes here.)
     */
    public function data(Request $request)
    {
        // ... your existing implementation ...
    }

    /**
     * Daily snapshot endpoint for the calendar.
     * (Your original implementation goes here.)
     */
    public function daySnapshot(Request $request)
    {
        // ... your existing implementation ...
    }

    /* ================================================================
     |  NEW: Sales Report range endpoint for dropdown
     * ================================================================ */

    public function salesRange(Request $request)
    {
        $range = $request->query('range', 'week');
        $today = Carbon::today();

        switch ($range) {
            case 'today':
                $start = $today->copy();
                $end   = $today->copy();
                $label = 'Today';
                break;

            case 'month':
                $start = $today->copy()->startOfMonth();
                $end   = $today->copy()->endOfMonth();
                $label = 'This Month';
                break;

            case '7days':
                $start = $today->copy()->subDays(6);
                $end   = $today->copy();
                $label = 'Last 7 Days';
                break;

            case '30days':
                $start = $today->copy()->subDays(29);
                $end   = $today->copy();
                $label = 'Last 30 Days';
                break;

            case 'week':
            default:
                $start = $today->copy()->startOfWeek();
                $end   = $today->copy()->endOfWeek();
                $label = 'This Week';
                break;
        }

        // mirror index() expressions, without table alias
        $QTY   = 'COALESCE(quantity_kg, quantity, 0)';
        $UNIT  = 'COALESCE(unit_price, price, 0)';
        $REVEX = "$QTY * $UNIT";
        $dateExpr = $this->salesDateExpr();

        // Aggregate per day for chart
        $daily = Sale::whereBetween(DB::raw($dateExpr), [$start->toDateString(), $end->toDateString()])
            ->selectRaw("$dateExpr as d, SUM($QTY) as qty, SUM($REVEX) as rev")
            ->groupBy('d')
            ->get()
            ->keyBy('d');

        $totalRevenue = (float) $daily->sum('rev');

        // "Sales Count" = number of sale rows in that range
        $totalSales   = (int) Sale::whereBetween(DB::raw($dateExpr), [$start->toDateString(), $end->toDateString()])
            ->count();

        $avgPrice     = $totalSales > 0
            ? $totalRevenue / max($totalSales, 1)
            : 0.0;

        $biggestSalesDay = null;
        if ($daily->isNotEmpty()) {
            $maxRow = $daily->sortByDesc('rev')->first();
            if ($maxRow && (float) $maxRow->rev > 0) {
                $biggestSalesDay = Carbon::parse($maxRow->d)->format('M d');
            }
        }

        // Chart labels + data (per day in range)
        $labels = [];
        $data   = [];

        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $ymd = $cursor->toDateString();
            $labels[] = $cursor->format('M d');
            $row   = $daily->get($ymd);
            $data[] = (float) ($row->rev ?? 0.0);
            $cursor->addDay();
        }

        return response()->json([
            'range' => $range,
            'label' => $label,
            'start' => $start->toDateString(),
            'end'   => $end->toDateString(),

            'stats' => [
                'total_revenue' => $totalRevenue,
                'total_sales'   => $totalSales,
                'avg_price'     => $avgPrice,
                'biggest_day'   => $biggestSalesDay,
            ],

            'chart' => [
                'labels' => $labels,
                'data'   => $data,
            ],
        ]);
    }
}
