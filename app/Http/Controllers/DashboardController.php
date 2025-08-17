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
    public function index()
    {
        $start = Carbon::now()->startOfWeek(); // Mon 00:00
        $end   = Carbon::now()->endOfWeek();   // Sun 23:59

        // ===== KPI cards =====
        $totalProducts        = (int) Product::count();
        $totalMaterialsWeight = (float) (Material::sum('quantity_kg') ?? 0); // on-hand stock
        $totalRevenue         = (float) (Sale::selectRaw('SUM(quantity * price) as rev')->value('rev') ?? 0);
        $totalSales           = (int) Sale::count();

        // ===== Recent sales =====
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

        // ===== Labels Mon..Sun =====
        $labels = [];
        $p = $start->copy();
        while ($p->lte($end)) {
            $labels[] = $p->format('D');
            $p->addDay();
        }

        // ===== Weekly Production =====
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

        // ===== Weekly Sales (qty + revenue) =====
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

        // ===== Materials Used (This Week): qty + cost from productions × recipe =====
        // uses product_recipes.qty (per one unit of product) and unit_price_snapshot
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

        // Optional: recent materials (by created_at) if you still show this elsewhere
        $recentMaterials = Material::whereBetween('created_at', [$start, $end])
            ->orderByDesc('created_at')
            ->take(8)
            ->get();

        return view('dashboard', [
            'totalProducts'            => $totalProducts,
            'totalMaterialsWeight'     => $totalMaterialsWeight,
            'totalRevenue'             => $totalRevenue,
            'totalSales'               => $totalSales,
            'recentSales'              => $recentSales,
            'labels'                   => $labels,
            'weeklyProductionSeries'   => $weeklyProductionSeries,
            'weeklySalesQtySeries'     => $weeklySalesQtySeries,
            'weeklySalesRevenueSeries' => $weeklySalesRevenueSeries,
            'materialsUsage'           => $materialsUsage,
            'materialsUsageTotals'     => $materialsUsageTotals,
            'recentMaterials'          => $recentMaterials,
        ]);
    }
}
