<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class MaterialController extends Controller
{
    /**
     * Route: GET /materials
     */
    public function index(Request $request)
    {
        // -------------------------------------------------------------
        // Basic filters
        // -------------------------------------------------------------
        $search   = trim((string) $request->query('search', ''));
        $sort     = trim((string) $request->query('sort', 'name_asc'));
        $onlyLow  = (bool) $request->boolean('low_stock', false);
        $category = $request->query('category');

        $expiry  = $request->query('expiry');   // null | expired | near | fresh
        $storage = $request->query('storage');  // chiller | freezer | ambient | dry

        // -------------------------------------------------------------
        // Per-page handling (supports "all")
        // -------------------------------------------------------------
        $perPageParam = $request->query('per_page', 50);
        $perPage = ($perPageParam === 'all') ? 100000 : max((int) $perPageParam, 1);

        // -------------------------------------------------------------
        // Detect schema capabilities safely
        // -------------------------------------------------------------
        $hasProd = Schema::hasTable('productions')
            && Schema::hasColumn('productions', 'production_date')
            && Schema::hasColumn('productions', 'output_qty_kg')
            && Schema::hasColumn('productions', 'product_id');

        $hasRecipe = Schema::hasTable('product_recipes')
            && Schema::hasColumn('product_recipes', 'product_id')
            && Schema::hasColumn('product_recipes', 'qty');

        // Recipes may reference materials via material_id OR ingredient_id
        $recipeMatCol = null;
        if ($hasRecipe && Schema::hasColumn('product_recipes', 'material_id')) {
            $recipeMatCol = 'material_id';
        } elseif ($hasRecipe && Schema::hasColumn('product_recipes', 'ingredient_id')) {
            $recipeMatCol = 'ingredient_id';
        }

        $hasQtyPerUnit = $hasRecipe && Schema::hasColumn('product_recipes', 'quantity_per_unit');
        $multExpr      = $hasQtyPerUnit ? 'COALESCE(r.quantity_per_unit, r.qty)' : 'r.qty';

        // -------------------------------------------------------------
        // Main query + include used_in_products (for Blade "Used In" column)
        // -------------------------------------------------------------
        $query = Material::query()
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($onlyLow, fn ($q) => $q->lowStock())
            ->when($expiry === 'expired', fn ($q) => $q->expired())
            ->when($expiry === 'near', fn ($q) => $q->expiringSoon())
            ->when($expiry === 'fresh', fn ($q) => $q->fresh())
            ->when($storage, fn ($q) => $q->where('storage_type', $storage))
            ->when($category, fn ($q) => $q->where('category', $category))
            ->sortBy($sort);

        if ($recipeMatCol) {
            $query->addSelect([
                'used_in_products' => DB::table('product_recipes as pr')
                    ->selectRaw('COUNT(DISTINCT pr.product_id)')
                    ->whereColumn("pr.{$recipeMatCol}", 'materials.id'),
            ]);
        }

        $materials = $query->paginate($perPage)->withQueryString();

        // -------------------------------------------------------------
        // Usage / prediction metrics (global)
        // -------------------------------------------------------------
        $avg30          = 0.0;
        $avg7           = 0.0;
        $sparkData      = [0, 0, 0, 0, 0, 0];

        // Chart arrays expected by your Blade
        $usageLabels    = [];
        $usageValues    = [];
        $forecastLabels = [];
        $forecastValues = [];

        // Per-material prediction map expected by Blade
        $predictions = []; // [id => ['burn_per_day'=>float, 'days_to_min'=>float|null, 'reorder_date'=>string|null]]

        // -------------------------------------------------------------
        // Global usage series (30 days) + forecast (next 14 days)
        // -------------------------------------------------------------
        if ($hasProd && $hasRecipe) {
            $today  = now()->startOfDay();
            $days30 = 30;
            $days7  = 7;
            $from30 = $today->copy()->subDays($days30 - 1);

            // Sparse rows keyed by date
            $usageRows = DB::table('productions as p')
                ->join('product_recipes as r', 'r.product_id', '=', 'p.product_id')
                ->selectRaw('DATE(p.production_date) as d')
                ->selectRaw("SUM(p.output_qty_kg * {$multExpr}) as total_kg")
                ->whereBetween('p.production_date', [$from30->toDateString(), $today->toDateString()])
                ->groupBy(DB::raw('DATE(p.production_date)'))
                ->orderBy('d')
                ->pluck('total_kg', 'd');

            // Dense 30-day series (fill gaps with 0)
            $series30 = [];
            $cursor   = $from30->copy();

            for ($i = 0; $i < $days30; $i++) {
                $key = $cursor->toDateString();
                $val = (float) ($usageRows[$key] ?? 0.0);

                $series30[]   = $val;
                $usageLabels[] = $cursor->format('M d');

                $cursor->addDay();
            }

            $usageValues = $series30;

            // avg30 + avg7 + spark
            $avg30 = array_sum($series30) / max($days30, 1);

            $last7 = array_slice($series30, -$days7);
            $avg7  = array_sum($last7) / max(count($last7), 1);

            $sparkData = array_slice($series30, -6);
            if (count($sparkData) < 6) {
                $sparkData = array_pad($sparkData, 6, 0.0);
            }

            // Forecast next 14 days using avg7 baseline
            $forecastDays = 14;
            $futureCursor = $today->copy()->addDay();
            $baseDaily    = (float) $avg7;

            for ($i = 0; $i < $forecastDays; $i++) {
                $forecastLabels[] = $futureCursor->format('M d');
                $forecastValues[] = round($baseDaily, 3);
                $futureCursor->addDay();
            }
        }

        // -------------------------------------------------------------
        // Per-material burn rate + reorder (last 7 days)
        // Needs productions + recipes + material reference column
        // -------------------------------------------------------------
        if ($hasProd && $hasRecipe && $recipeMatCol) {
            $today = now()->startOfDay();
            $from7 = $today->copy()->subDays(6);

            $pageMaterialIds = $materials->getCollection()->pluck('id')->all();

            if (!empty($pageMaterialIds)) {
                $used7dByMaterial = DB::table('productions as p')
                    ->join('product_recipes as r', 'r.product_id', '=', 'p.product_id')
                    ->whereIn("r.{$recipeMatCol}", $pageMaterialIds)
                    ->whereBetween('p.production_date', [$from7->toDateString(), $today->toDateString()])
                    ->groupBy("r.{$recipeMatCol}")
                    ->selectRaw("r.{$recipeMatCol} as material_id")
                    ->selectRaw("SUM(p.output_qty_kg * {$multExpr}) as used_kg_7d")
                    ->pluck('used_kg_7d', 'material_id');

                foreach ($materials->getCollection() as $m) {
                    $mid  = (int) $m->id;
                    $qty  = (float) ($m->quantity_kg ?? 0.0);
                    $min  = (float) ($m->min_stock_kg ?? 0.0);
                    $used = (float) ($used7dByMaterial[$mid] ?? 0.0);

                    $burn = $used / 7.0; // kg/day

                    $daysToMin = null;
                    $reorderDate = null;

                    if ($burn > 0.0 && $qty > $min) {
                        $daysToMin   = ($qty - $min) / $burn;
                        $reorderDate = now()->addDays((int) ceil($daysToMin))->toDateString();
                    }

                    $predictions[$mid] = [
                        'burn_per_day' => round($burn, 3),
                        'days_to_min'  => $daysToMin !== null ? round($daysToMin, 1) : null,
                        'reorder_date' => $reorderDate,
                    ];
                }
            }
        }

        // -------------------------------------------------------------
        // JSON response
        // -------------------------------------------------------------
        if ($request->wantsJson()) {
            return response()->json([
                'ok'   => true,
                'data' => $materials->items(),
                'meta' => [
                    'current_page' => $materials->currentPage(),
                    'per_page'     => $materials->perPage(),
                    'total'        => $materials->total(),
                    'last_page'    => $materials->lastPage(),
                ],
                'filters' => [
                    'search'   => $search,
                    'sort'     => $sort,
                    'onlyLow'  => $onlyLow,
                    'expiry'   => $expiry,
                    'storage'  => $storage,
                    'category' => $category,
                    'perPage'  => $perPageParam,
                ],
                'usage' => [
                    'avg30'     => round($avg30, 3),
                    'avg7'      => round($avg7, 3),
                    'sparkData' => $sparkData,
                ],
                'chart' => [
                    'usageLabels'    => $usageLabels,
                    'usageValues'    => $usageValues,
                    'forecastLabels' => $forecastLabels,
                    'forecastValues' => $forecastValues,
                ],
                'predictions' => $predictions,
            ]);
        }

        // -------------------------------------------------------------
        // Blade view
        // -------------------------------------------------------------
        return view('materials.index', [
            'materials'       => $materials,

            'avg30'           => $avg30,
            'avg7'            => $avg7,
            'sparkData'       => $sparkData,

            'usageLabels'     => $usageLabels,
            'usageValues'     => $usageValues,
            'forecastLabels'  => $forecastLabels,
            'forecastValues'  => $forecastValues,

            'predictions'     => $predictions,
        ]);
    }

    // باقي الدوال خليك عليها زي ما هي عندك (store/update/delete/adjustQuantity...)

    private function rules(?int $materialId = null): array
    {
        return [
            'material_name'   => ['required', 'string', 'max:255'],
            'category'        => ['nullable', 'string', 'max:255'],
            'unit'            => ['required', 'string', Rule::in(Material::ALLOWED_UNITS)],
            'sku'             => ['nullable', 'string', 'max:64'],

            'unit_price'      => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'quantity_kg'     => ['nullable', 'numeric', 'min:0', 'max:999999999999.999'],
            'min_stock_kg'    => ['nullable', 'numeric', 'min:0', 'max:999999999999.999'],
            'stock_status'    => ['nullable', 'in:low,in_stock'],

            'supplier_name'   => ['nullable', 'string', 'max:255'],
            'batch_code'      => ['nullable', 'string', 'max:64'],

            'storage_type'    => ['nullable', 'string', Rule::in(Material::STORAGE_TYPES)],
            'manufactured_at' => ['nullable', 'date', 'before_or_equal:today'],
            'received_at'     => ['nullable', 'date', 'before_or_equal:today'],
            'expires_at'      => ['nullable', 'date', 'after_or_equal:manufactured_at'],

            'notes'           => ['nullable', 'string', 'max:2000'],
        ];
    }
}
