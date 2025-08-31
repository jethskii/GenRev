<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Material;
use App\Models\Production;
use App\Models\Sale;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    public function __construct(private InventoryService $inventory) {}

    public function index(Request $request)
    {
        $q         = trim((string) $request->get('q', ''));
        $cat       = $request->get('cat');
        $lowThresh = (float) $request->get('low_material_threshold', 5.0);

        // KPIs
        $totalProducts        = (int) Product::count();
        $totalMaterialsWeight = (float) (Material::sum('quantity_kg') ?? 0.0);
        $totalSales           = (int) Sale::count();
        $totalRevenue         = (float) (Sale::selectRaw('SUM(quantity * price) AS rev')->value('rev') ?? 0.0);

        // Batch counts (avoid ->clone(), use native clone)
        $batchesQuery = Production::query();
        $batchesInProduction = (int) (clone $batchesQuery)->count();
        $batchesReleased     = (int) (clone $batchesQuery)->where('current_inventory', '>', 0)->count();
        $batchesExpiringSoon = (int) (clone $batchesQuery)
            ->whereDate('expiration_date', '<=', now()->addDays(7)->toDateString())
            ->count();

        // Products + available stock
        $productsBase = Product::query()
            ->when($q,   fn($qq) => $qq->where('product_name', 'like', "%{$q}%"))
            ->when($cat, fn($qq) => $qq->where('category', $cat))
            ->orderBy('product_name');

        $products = $productsBase->paginate(18)->withQueryString();
        $productIds = $products->pluck('id');

        $producedPerProduct = Production::select('product_id', DB::raw('SUM(quantity) as qty'))
            ->whereIn('product_id', $productIds)->groupBy('product_id')->pluck('qty','product_id');
        $soldPerProduct = Sale::select('product_id', DB::raw('SUM(quantity) as qty'))
            ->whereIn('product_id', $productIds)->groupBy('product_id')->pluck('qty','product_id');

        $products->getCollection()->transform(function (Product $p) use ($producedPerProduct, $soldPerProduct) {
            $produced = (float) ($producedPerProduct[$p->id] ?? $p->quantity ?? 0);
            $sold     = (float) ($soldPerProduct[$p->id] ?? 0);
            $p->available_stock_kg = max(0.0, $produced - $sold);
            return $p;
        });

        // Materials search that adapts to your columns
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

        // Expiry risk
        $expiringSoon = Production::whereNull('deleted_at')
            ->whereDate('expiration_date', '<=', now()->addDays(7)->toDateString())
            ->orderBy('expiration_date')
            ->with('product:id,product_name')
            ->limit(20)
            ->get();

        // Recent batches (normalized for the view)
        $recentBatches = Production::with('product:id,product_name')
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
                return $b;
            });

        // Material usage (this week)
        $start = Carbon::now()->startOfWeek()->toDateString();
        $end   = Carbon::now()->endOfWeek()->toDateString();
        $materialsUsage = $this->inventory->materialUsage($start, $end);
        $materialsUsageTotals = [
            'qty'  => (float) ($materialsUsage->sum('qty_used') ?? 0),
            'cost' => (float) ($materialsUsage->sum('cost_used') ?? 0),
        ];

        // Stockout “badge” forecast
        $stockForecasting = [];
        foreach ($products as $p) {
            $forecast = (float) ($p->forecasted_demand ?? 0);
            $avail    = (float) ($p->available_stock_kg ?? 0);
            if ($forecast <= 0) {
                $stockForecasting[] = ['product_id'=>$p->id,'days_until_stockout'=>null,'forecast_status'=>'normal'];
                continue;
            }
            $days = $avail / max(0.001, $forecast);
            $stockForecasting[] = [
                'product_id' => $p->id,
                'days_until_stockout' => $days,
                'forecast_status' => $days <= 3 ? 'critical' : ($days <= 7 ? 'warning' : 'normal'),
            ];
        }

        $categories = Product::whereNotNull('category')->distinct()->pluck('category')->sort()->values();

        $productionAlarms = [];
        foreach ($expiringSoon as $b) {
            $productionAlarms[] = [
                'severity' => $b->days_to_expiry !== null && $b->days_to_expiry <= 3 ? 'critical' : 'warning',
                'message'  => "{$b->product?->product_name} ({$b->batch_number}) expiring in {$b->days_to_expiry} day(s).",
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
                if (array_key_exists('set_forecasted_demand', $v) && $v['set_forecasted_demand'] !== null) $p->forecasted_demand = (float)$v['set_forecasted_demand'];
                if (array_key_exists('set_default_price', $v)     && $v['set_default_price']     !== null) $p->default_price     = (float)$v['set_default_price'];
                if (array_key_exists('set_unit_cost', $v)         && $v['set_unit_cost']         !== null) $p->unit_cost         = (float)$v['set_unit_cost'];
                $p->save();
            }
        });

        return back()->with('success', 'Inventory updated.');
    }

    public function edit(Request $request, int $id)
    {
        $kind = $request->get('kind', 'material');
        $record = $kind === 'product' ? Product::findOrFail($id) : Material::findOrFail($id);
        if ($kind !== 'product') $kind = 'material';
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
}
