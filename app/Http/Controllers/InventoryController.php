<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Material;
use App\Models\Production;
use App\Models\Sale;                 // legacy (ok if not used)
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;

class InventoryController extends Controller
{
    public function __construct(private InventoryService $inventory) {}

    public function index(Request $request)
    {
        $q         = trim((string) $request->get('q', ''));
        $cat       = $request->get('cat');
        $lowThresh = (float) $request->get('low_material_threshold', 5.0);

        $hasSalesOrderTbl     = Schema::hasTable('sales_orders');
        $hasSalesOrderItemTbl = Schema::hasTable('sales_order_items');
        $hasSaleTbl           = Schema::hasTable('sales');

        /* ========================= KPIs ========================= */

        $totalProducts        = (int) Product::count();
        $totalMaterialsWeight = (float) (Material::sum('quantity_kg') ?? 0.0);

        // Sales count (orders or rows)
        if ($hasSalesOrderTbl) {
            $totalSales = (int) DB::table('sales_orders')->whereNull('deleted_at')->count();
        } elseif ($hasSaleTbl) {
            $totalSales = (int) DB::table('sales')->whereNull('deleted_at')->count();
        } else {
            $totalSales = 0;
        }

        // Revenue (prefer items.total_price; fallback to qty*price)
        if ($hasSalesOrderItemTbl) {
            $totalRevenue = (float) (DB::table('sales_order_items')
                ->whereNull('deleted_at')
                ->selectRaw('SUM(COALESCE(total_price, (COALESCE(quantity,0) * COALESCE(unit_price,0)))) as rev')
                ->value('rev') ?? 0.0);
        } elseif ($hasSaleTbl) {
            // legacy: prefer total_price, then total, then qty*price
            $totalRevenue = (float) (DB::table('sales')
                ->whereNull('deleted_at')
                ->selectRaw('SUM(COALESCE(total_price, COALESCE(total, (COALESCE(quantity_kg, quantity, 0) * COALESCE(unit_price, price, 0))))) as rev')
                ->value('rev') ?? 0.0);
        } else {
            $totalRevenue = 0.0;
        }

        // Batch counts
        $batchesQuery         = Production::query()->whereNull('deleted_at');
        $batchesInProduction  = (int) (clone $batchesQuery)->count();
        $batchesReleased      = (int) (clone $batchesQuery)->where('current_inventory', '>', 0)->count();
        $batchesExpiringSoon  = (int) (clone $batchesQuery)
            ->whereDate('expiration_date', '<=', now()->addDays(7)->toDateString())
            ->count();

        /* ==================== Product listing ==================== */

        $productsBase = Product::query()
            ->when($q,   fn($qq) => $qq->where('product_name', 'like', "%{$q}%"))
            ->when($cat, fn($qq) => $qq->where('category', $cat))
            ->orderBy('product_name');

        $products   = $productsBase->paginate(18)->withQueryString();
        $productIds = $products->pluck('id');

        // Available stock (kg) = live batch balance sum per product
        $batchBalances = Production::query()
            ->whereNull('deleted_at')
            ->whereIn('product_id', $productIds)
            ->groupBy('product_id')
            ->select('product_id', DB::raw('SUM(COALESCE(current_inventory,0)) as bal'))
            ->pluck('bal', 'product_id');

        $products->getCollection()->transform(function (Product $p) use ($batchBalances) {
            $p->available_stock_kg = (float) ($batchBalances[$p->id] ?? 0.0);
            return $p;
        });

        /* ==================== Materials list ===================== */

        $hasNameCol = Schema::hasColumn('materials', 'name');
        $materials = Material::query()
            ->when($q, function ($qq) use ($q, $hasNameCol) {
                $qq->where(function ($w) use ($q, $hasNameCol) {
                    $w->where('material_name', 'like', "%{$q}%");
                    if ($hasNameCol) {
                        $w->orWhere('name', 'like', "%{$q}%");
                    }
                });
            })
            ->orderBy('quantity_kg')
            ->paginate(18)
            ->withQueryString();

        /* ===================== Expiry / recent ==================== */

        $expiringSoon = Production::whereNull('deleted_at')
            ->whereDate('expiration_date', '<=', now()->addDays(7)->toDateString())
            ->orderBy('expiration_date')
            ->with('product:id,product_name')
            ->limit(20)
            ->get();

        $recentBatches = Production::with('product:id,product_name')
            ->whereNull('deleted_at')
            ->orderByDesc('production_date')
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(function (Production $b) {
                $b->batch_code     = $b->batch_number;
                $b->produced_at    = $b->production_date;
                $b->expiry_date    = $b->expiration_date;
                $b->qty_total      = (float) ($b->quantity ?? 0);
                $b->qty_available  = (float) ($b->current_inventory ?? 0);
                $b->status         = $b->qty_available > 0 ? 'RELEASED' : 'CREATED';
                // optional: surface pack/bag
                $b->available_pack = (int) ($b->available_pack ?? 0);
                $b->available_bag  = (int) ($b->available_bag ?? 0);
                return $b;
            });

        /* ===================== Material usage ===================== */

        $start = Carbon::now()->startOfWeek()->toDateString();
        $end   = Carbon::now()->endOfWeek()->toDateString();
        $materialsUsage = $this->inventory->materialUsage($start, $end);
        $materialsUsageTotals = [
            'qty'  => (float) ($materialsUsage->sum('qty_used') ?? 0),
            'cost' => (float) ($materialsUsage->sum('cost_used') ?? 0),
        ];

        /* ===================== Forecast badges ==================== */

        $stockForecasting = $this->buildStockForecasting($products->getCollection());

        $categories = Product::whereNotNull('category')->distinct()->pluck('category')->sort()->values();

        $productionAlarms = [];
        foreach ($expiringSoon as $b) {
            $dte = property_exists($b, 'days_to_expiry') || isset($b->days_to_expiry)
                ? $b->days_to_expiry
                : (isset($b->expiration_date)
                    ? Carbon::now()->diffInDays($b->expiration_date, false)
                    : null);

            $sev  = ($dte !== null && $dte <= 3) ? 'critical' : 'warning';
            $left = $dte !== null ? $dte : 'N/A';

            $productionAlarms[] = [
                'severity' => $sev,
                'message'  => "{$b->product?->product_name} ({$b->batch_number}) expiring in {$left} day(s).",
            ];
        }

        if ($request->wantsJson()) {
            return response()->json([
                'kpi' => compact(
                    'totalProducts','totalMaterialsWeight','totalSales','totalRevenue',
                    'batchesInProduction','batchesReleased','batchesExpiringSoon'
                ),
                'expiringSoon'         => $expiringSoon,
                'recentBatches'        => $recentBatches,
                'materialsUsageTotals' => $materialsUsageTotals,
            ]);
        }

        return view('inventory.index', compact(
            'q','cat','categories','lowThresh',
            'products','materials',
            'totalProducts','totalMaterialsWeight','totalSales','totalRevenue',
            'batchesInProduction','batchesReleased','batchesExpiringSoon',
            'expiringSoon','recentBatches','materialsUsage','materialsUsageTotals',
            'stockForecasting','productionAlarms'
        ));
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'kind' => ['required', Rule::in(['material','product'])],
            'id'   => ['required','integer'],
            'delta_kg'              => ['nullable','numeric'],
            'set_forecasted_demand' => ['nullable','numeric','min:0'],
            'set_default_price'     => ['nullable','numeric','min:0'],
            'set_unit_cost'         => ['nullable','numeric','min:0'],
        ]);

        DB::transaction(function () use ($v) {
            if ($v['kind'] === 'material') {
                $m = Material::lockForUpdate()->findOrFail($v['id']);
                $m->quantity_kg = max(0.0, (float)$m->quantity_kg + (float)($v['delta_kg'] ?? 0));
                $m->save();
            } else {
                $p = Product::lockForUpdate()->findOrFail($v['id']);
                if (array_key_exists('set_forecasted_demand', $v) && $v['set_forecasted_demand'] !== null) {
                    $p->forecasted_demand = (float)$v['set_forecasted_demand'];
                }
                if (array_key_exists('set_default_price', $v) && $v['set_default_price'] !== null) {
                    $p->default_price = (float)$v['set_default_price'];
                }
                if (array_key_exists('set_unit_cost', $v) && $v['set_unit_cost'] !== null) {
                    $p->unit_cost = (float)$v['set_unit_cost'];
                }
                $p->save();
            }
        });

        return back()->with('success', 'Inventory updated.');
    }

    public function edit(Request $request, int $id)
    {
        $kind   = $request->get('kind', 'material');
        $record = $kind === 'product' ? Product::findOrFail($id) : Material::findOrFail($id);
        if ($kind !== 'product') {
            $kind = 'material';
        }
        return view('inventory.edit', compact('kind','record'));
    }

    public function update(Request $request, int $id)
    {
        $kind = $request->get('kind');
        if ($kind === 'product') {
            $data = $request->validate([
                'product_name'      => ['nullable','string','max:255'],
                'category'          => ['nullable','string','max:120'],
                'shelf_life_days'   => ['nullable','integer','min:1','max:3650'],
                'default_price'     => ['nullable','numeric','min:0'],
                'unit_cost'         => ['nullable','numeric','min:0'],
                'forecasted_demand' => ['nullable','numeric','min:0'],
            ]);
            $p = Product::findOrFail($id);
            $p->fill(array_filter($data, static fn($v)=>$v!==null))->save();
            return redirect()->route('inventory.index')->with('success','Product updated.');
        }

        $data = $request->validate([
            'material_name' => ['nullable','string','max:255'],
            'quantity_kg'   => ['nullable','numeric','min:0'],
            'unit_price'    => ['nullable','numeric','min:0'],
            'unit'          => ['nullable','string','max:10'],
        ]);
        $m = Material::findOrFail($id);
        $m->fill(array_filter($data, static fn($v)=>$v!==null))->save();
        return redirect()->route('inventory.index')->with('success','Material updated.');
    }

    /**
     * Export filtered products as CSV with forecasting info.
     */
    public function exportCsv(Request $request)
    {
        $q   = trim((string) $request->get('q', ''));
        $cat = $request->get('cat');

        // Base query with same filters as index
        $productsBase = Product::query()
            ->when($q,   fn($qq) => $qq->where('product_name', 'like', "%{$q}%"))
            ->when($cat, fn($qq) => $qq->where('category', $cat))
            ->orderBy('product_name');

        $products   = $productsBase->get();
        $productIds = $products->pluck('id');

        // Compute available stock per product (same logic as index)
        $batchBalances = Production::query()
            ->whereNull('deleted_at')
            ->whereIn('product_id', $productIds)
            ->groupBy('product_id')
            ->select('product_id', DB::raw('SUM(COALESCE(current_inventory,0)) as bal'))
            ->pluck('bal', 'product_id');

        $products->transform(function (Product $p) use ($batchBalances) {
            $p->available_stock_kg = (float) ($batchBalances[$p->id] ?? 0.0);
            return $p;
        });

        $stockForecasting = $this->buildStockForecasting($products);

        // Index by product_id for quick lookup
        $forecastMap = collect($stockForecasting)->keyBy('product_id');

        $fileName = 'inventory_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        $columns = [
            'Product ID',
            'Product Name',
            'Category',
            'Available (kg)',
            'Forecasted Demand (kg)',
            'Days Until Stockout',
            'Forecast Status',
        ];

        $callback = function () use ($products, $columns, $forecastMap) {
            $file = fopen('php://output', 'w');

            // header row
            fputcsv($file, $columns);

            foreach ($products as $p) {
                $forecast = $forecastMap->get($p->id);
                $days     = $forecast['days_until_stockout'] ?? null;
                $status   = $forecast['forecast_status']     ?? 'normal';

                fputcsv($file, [
                    'PROD-' . $p->id,
                    $p->product_name,
                    $p->category ?? '',
                    (float)($p->available_stock_kg ?? 0),
                    (float)($p->forecasted_demand ?? 0),
                    $days !== null ? round($days, 2) : null,
                    $status,
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export full inventory dashboard as PDF (KPIs + products + batches + materials).
     * View: resources/views/inventory/export_pdf.blade.php
     */
    public function exportPdf(Request $request)
    {
        $q         = trim((string) $request->get('q', ''));
        $cat       = $request->get('cat');
        $lowThresh = (float) $request->get('low_material_threshold', 5.0);

        $hasSalesOrderTbl     = Schema::hasTable('sales_orders');
        $hasSalesOrderItemTbl = Schema::hasTable('sales_order_items');
        $hasSaleTbl           = Schema::hasTable('sales');

        /* ========================= KPIs ========================= */

        $totalProducts        = (int) Product::count();
        $totalMaterialsWeight = (float) (Material::sum('quantity_kg') ?? 0.0);

        // Sales count (orders or rows)
        if ($hasSalesOrderTbl) {
            $totalSales = (int) DB::table('sales_orders')->whereNull('deleted_at')->count();
        } elseif ($hasSaleTbl) {
            $totalSales = (int) DB::table('sales')->whereNull('deleted_at')->count();
        } else {
            $totalSales = 0;
        }

        // Revenue (prefer items.total_price; fallback to qty*price)
        if ($hasSalesOrderItemTbl) {
            $totalRevenue = (float) (DB::table('sales_order_items')
                ->whereNull('deleted_at')
                ->selectRaw('SUM(COALESCE(total_price, (COALESCE(quantity,0) * COALESCE(unit_price,0)))) as rev')
                ->value('rev') ?? 0.0);
        } elseif ($hasSaleTbl) {
            // legacy: prefer total_price, then total, then qty*price
            $totalRevenue = (float) (DB::table('sales')
                ->whereNull('deleted_at')
                ->selectRaw('SUM(COALESCE(total_price, COALESCE(total, (COALESCE(quantity_kg, quantity, 0) * COALESCE(unit_price, price, 0))))) as rev')
                ->value('rev') ?? 0.0);
        } else {
            $totalRevenue = 0.0;
        }

        // Batch counts
        $batchesQuery         = Production::query()->whereNull('deleted_at');
        $batchesInProduction  = (int) (clone $batchesQuery)->count();
        $batchesReleased      = (int) (clone $batchesQuery)->where('current_inventory', '>', 0)->count();
        $batchesExpiringSoon  = (int) (clone $batchesQuery)
            ->whereDate('expiration_date', '<=', now()->addDays(7)->toDateString())
            ->count();

        /* ==================== Product listing ==================== */

        $productsBase = Product::query()
            ->when($q,   fn($qq) => $qq->where('product_name', 'like', "%{$q}%"))
            ->when($cat, fn($qq) => $qq->where('category', $cat))
            ->orderBy('product_name');

        // For PDF we want the full list, not paginated
        $products   = $productsBase->get();
        $productIds = $products->pluck('id');

        // Available stock (kg) = live batch balance sum per product
        $batchBalances = Production::query()
            ->whereNull('deleted_at')
            ->whereIn('product_id', $productIds)
            ->groupBy('product_id')
            ->select('product_id', DB::raw('SUM(COALESCE(current_inventory,0)) as bal'))
            ->pluck('bal', 'product_id');

        $products->transform(function (Product $p) use ($batchBalances) {
            $p->available_stock_kg = (float) ($batchBalances[$p->id] ?? 0.0);
            return $p;
        });

        /* ==================== Materials list ===================== */

        $hasNameCol = Schema::hasColumn('materials', 'name');
        $materials = Material::query()
            ->when($q, function ($qq) use ($q, $hasNameCol) {
                $qq->where(function ($w) use ($q, $hasNameCol) {
                    $w->where('material_name', 'like', "%{$q}%");
                    if ($hasNameCol) {
                        $w->orWhere('name', 'like', "%{$q}%");
                    }
                });
            })
            ->orderBy('quantity_kg')
            ->get();

        /* ===================== Expiry / recent ==================== */

        $expiringSoon = Production::whereNull('deleted_at')
            ->whereDate('expiration_date', '<=', now()->addDays(7)->toDateString())
            ->orderBy('expiration_date')
            ->with('product:id,product_name')
            ->limit(20)
            ->get();

        $recentBatches = Production::with('product:id,product_name')
            ->whereNull('deleted_at')
            ->orderByDesc('production_date')
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(function (Production $b) {
                $b->batch_code     = $b->batch_number;
                $b->produced_at    = $b->production_date;
                $b->expiry_date    = $b->expiration_date;
                $b->qty_total      = (float) ($b->quantity ?? 0);
                $b->qty_available  = (float) ($b->current_inventory ?? 0);
                $b->status         = $b->qty_available > 0 ? 'RELEASED' : 'CREATED';
                $b->available_pack = (int) ($b->available_pack ?? 0);
                $b->available_bag  = (int) ($b->available_bag ?? 0);
                return $b;
            });

        /* ===================== Material usage ===================== */

        $start = Carbon::now()->startOfWeek()->toDateString();
        $end   = Carbon::now()->endOfWeek()->toDateString();
        $materialsUsage = $this->inventory->materialUsage($start, $end);
        $materialsUsageTotals = [
            'qty'  => (float) ($materialsUsage->sum('qty_used') ?? 0),
            'cost' => (float) ($materialsUsage->sum('cost_used') ?? 0),
        ];

        /* ===================== Forecast badges ==================== */

        $stockForecasting = $this->buildStockForecasting($products);

        /* ===================== Production alarms ================== */

        $productionAlarms = [];
        foreach ($expiringSoon as $b) {
            $dte = property_exists($b, 'days_to_expiry') || isset($b->days_to_expiry)
                ? $b->days_to_expiry
                : (isset($b->expiration_date)
                    ? Carbon::now()->diffInDays($b->expiration_date, false)
                    : null);

            $sev  = ($dte !== null && $dte <= 3) ? 'critical' : 'warning';
            $left = $dte !== null ? $dte : 'N/A';

            $productionAlarms[] = [
                'severity' => $sev,
                'message'  => "{$b->product?->product_name} ({$b->batch_number}) expiring in {$left} day(s).",
            ];
        }

        $pdf = Pdf::loadView('inventory.export_pdf', [
            'search'               => $q,
            'lowThresh'            => $lowThresh,

            'products'             => $products,
            'materials'            => $materials,
            'recentBatches'        => $recentBatches,
            'expiringSoon'         => $expiringSoon,
            'materialsUsage'       => $materialsUsage,
            'materialsUsageTotals' => $materialsUsageTotals,
            'stockForecasting'     => $stockForecasting,
            'productionAlarms'     => $productionAlarms,

            'totalProducts'        => $totalProducts,
            'totalMaterialsWeight' => $totalMaterialsWeight,
            'totalSales'           => $totalSales,
            'totalRevenue'         => $totalRevenue,
            'batchesInProduction'  => $batchesInProduction,
            'batchesReleased'      => $batchesReleased,
            'batchesExpiringSoon'  => $batchesExpiringSoon,
        ])->setPaper('a4', 'landscape');

        $fileName = 'inventory_' . now()->format('Ymd_His') . '.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Shared stock-forecast helper so page + exports stay consistent.
     *
     * @param \Illuminate\Support\Collection|\Illuminate\Contracts\Support\Arrayable $products
     * @return array<int, array{product_id:int,days_until_stockout:float|null,forecast_status:string}>
     */
    protected function buildStockForecasting($products): array
    {
        // If paginator or arrayable, normalize to collection
        if (method_exists($products, 'getCollection')) {
            $products = $products->getCollection();
        }
        $collection = collect($products);

        $result = [];
        foreach ($collection as $p) {
            $forecast = (float) ($p->forecasted_demand ?? 0);
            $avail    = (float) ($p->available_stock_kg ?? 0);

            if ($forecast <= 0) {
                $result[] = [
                    'product_id'         => $p->id,
                    'days_until_stockout'=> null,
                    'forecast_status'    => 'normal',
                ];
                continue;
            }

            // Simple days estimate = available / forecast (you can swap with your own model)
            $days = $avail / max(0.001, $forecast);

            $result[] = [
                'product_id'         => $p->id,
                'days_until_stockout'=> $days,
                'forecast_status'    => $days <= 3 ? 'critical' : ($days <= 7 ? 'warning' : 'normal'),
            ];
        }

        return $result;
    }
}
