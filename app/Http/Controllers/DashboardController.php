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
        $start = $end->copy()->startOfWeek()->subWeeks($weeks - 1);
        $buckets = [];
        $cursor = $start->copy();
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

    /** ---------------------------- Pages ----------------------------- */

    /**
     * Dashboard view with inline data for charts (Mon..Sun of current week)
     * + 12-week production trend.
     */
    public function index()
    {
        $start = Carbon::now()->startOfWeek(); // Mon 00:00
        $end   = Carbon::now()->endOfWeek();   // Sun 23:59

        /* ======================== KPI cards ======================== */
        $totalProducts        = (int) Product::count();
        $totalMaterialsWeight = (float) (Material::sum('quantity_kg') ?? 0); // on-hand stock
        $totalRevenue         = (float) (Sale::selectRaw('SUM(quantity * price) as rev')->value('rev') ?? 0);
        $totalSales           = (int) Sale::count();

        /* ===================== Recent sales table ==================== */
        $recentSales = Sale::with('productRef:id,product_name')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->take(8)
            ->get()
            ->map(function ($s) {
                $s->product_name = $s->productRef->product_name ?? ($s->product ?? 'Product');
                $s->quantity     = (float) ($s->quantity ?? 0);
                $s->price        = (float) ($s->price ?? 0);
                return $s;
            });

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
            ->selectRaw('DATE(date) as d, SUM(quantity) as qty, SUM(quantity * price) as rev')
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

        // Biggest day by revenue (for the Sales Report widget)
        $biggestSalesDay = null;
        if ($salesDaily->isNotEmpty()) {
            $maxRow = $salesDaily->sortByDesc('rev')->first();
            if ($maxRow && (float)$maxRow->rev > 0) {
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
                SUM(p.quantity * r.qty)                         as qty_used,
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
        // Sum remaining qty expected to expire each day of the current week.
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
                $expDate = Carbon::parse($b->production_date)->copy()->addDays((int)$b->product->shelf_life_days);
            }
            if (!$expDate) continue;

            $expDate = Carbon::parse($expDate)->toDateString();
            if (isset($expiryBuckets[$expDate])) {
                // use remaining qty if tracked; else total qty
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

        /* ============== Top 5 products by revenue (this week) ============== */
        $topProducts = Sale::join('products as p', 'p.id', '=', 'sales.product_id')
            ->whereBetween(DB::raw('DATE(sales.date)'), [$start->toDateString(), $end->toDateString()])
            ->groupBy('sales.product_id', 'p.product_name')
            ->selectRaw('sales.product_id, p.product_name, SUM(sales.quantity) as quantity, SUM(sales.quantity * sales.price) as revenue')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->map(function ($row) use ($totalRevenue) {
                $row->revenue_share = $totalRevenue > 0 ? round(($row->revenue / $totalRevenue) * 100, 1) : 0.0;
                return $row;
            });

        /* =================== 12-week Production Trend =================== */
        $trendEnd      = Carbon::now();
        $weekBuckets   = $this->makeWeekBuckets($trendEnd, 12); // Monday keys
        $windowStart   = Carbon::parse(array_key_first($weekBuckets));

        // Pull daily totals across the whole 12-week window
        $dailyProd = Production::whereBetween('production_date', [
                $windowStart->toDateString(),
                $trendEnd->endOfWeek()->toDateString(),
            ])
            ->selectRaw('production_date as d, SUM(quantity) as qty')
            ->groupBy('d')
            ->pluck('qty', 'd')
            ->all();

        // Bucket each day into its week's Monday
        foreach ($dailyProd as $dayYmd => $qty) {
            $weekStart = Carbon::parse($dayYmd)->startOfWeek()->toDateString();
            if (array_key_exists($weekStart, $weekBuckets)) {
                $weekBuckets[$weekStart] += (float) $qty;
            }
        }

        // Build labels/series oldest → newest
        $productionTrendLabels = [];
        $productionTrendSeries = [];
        foreach ($weekBuckets as $weekStartYmd => $sumQty) {
            $productionTrendLabels[] = $this->humanWeekLabel($weekStartYmd);
            $productionTrendSeries[] = (float) $sumQty;
        }

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
        ]);
    }

    /**
     * Optional JSON endpoint your routes already expose: /dashboard/data
     * Returns the same series so you can fetch via AJAX if you want later.
     */
    public function data()
    {
        $start = Carbon::now()->startOfWeek();
        $end   = Carbon::now()->endOfWeek();

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
            ->selectRaw('DATE(date) as d, SUM(quantity) as qty, SUM(quantity * price) as rev')
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
                $expDate = Carbon::parse($b->production_date)->copy()->addDays((int)$b->product->shelf_life_days);
            }
            if (!$expDate) continue;

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
        $trendEnd      = Carbon::now();
        $weekBuckets   = $this->makeWeekBuckets($trendEnd, 12);
        $windowStart   = Carbon::parse(array_key_first($weekBuckets));

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

        return response()->json([
            'labels'                   => $labels,
            'weeklyProductionSeries'   => $weeklyProductionSeries,
            'weeklySalesQtySeries'     => $weeklySalesQtySeries,
            'weeklySalesRevenueSeries' => $weeklySalesRevenueSeries,
            'weeklyExpirySeries'       => $weeklyExpirySeries,
            'productionTrendLabels'    => $productionTrendLabels,
            'productionTrendSeries'    => $productionTrendSeries,
        ]);
    }
}
