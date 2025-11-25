<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class MaterialController extends Controller
{
    /* =======================================================================
     | INDEX / LIST
     * ======================================================================= */

    /**
     * Display a listing of materials with search, sort, low-stock, expiry,
     * storage filters + usage trend metrics for the dashboard graph.
     *
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

        // expiry: null | expired | near | fresh
        $expiry   = $request->query('expiry');
        // storage: e.g. chiller | freezer | ambient | dry
        $storage  = $request->query('storage');

        // -------------------------------------------------------------
        // Per-page handling (supports "all")
        // -------------------------------------------------------------
        $perPageParam = $request->query('per_page', 50);

        if ($perPageParam === 'all') {
            // Effectively "show everything" – keep a sane upper bound
            $perPage = 100000;
        } else {
            $perPage = (int) $perPageParam;
            if ($perPage < 1) {
                $perPage = 50; // default fallback
            }
        }

        // -------------------------------------------------------------
        // Main query
        // -------------------------------------------------------------
        $query = Material::query()
            ->when($search !== '', function ($q) use ($search) {
                return $q->search($search);        // assumes a local scope `search`
            })
            ->when($onlyLow, function ($q) {
                return $q->lowStock();            // assumes a local scope `lowStock`
            })
            ->when($expiry === 'expired', function ($q) {
                return $q->expired();             // scope `expired`
            })
            ->when($expiry === 'near', function ($q) {
                return $q->expiringSoon();        // scope `expiringSoon`
            })
            ->when($expiry === 'fresh', function ($q) {
                return $q->fresh();               // scope `fresh`
            })
            ->when($storage, function ($q) use ($storage) {
                return $q->where('storage_type', $storage);
            })
            ->when($category, function ($q) use ($category) {
                return $q->where('category', $category);
            })
            ->sortBy($sort);                       // assumes a local scope `sortBy`

        $materials = $query
            ->paginate($perPage)
            ->withQueryString();

        /* -----------------------------------------------------------------
         | Usage / prediction metrics for KPI graph
         | - total material usage across all materials
         | - based on production output * recipe quantity_per_unit (or qty)
         * -----------------------------------------------------------------*/
        $avg30     = 0.0;
        $avg7      = 0.0;
        $sparkData = [0, 0, 0, 0, 0, 0];

        // Only try to query if the required tables/columns exist
        if (
            Schema::hasTable('productions') &&
            Schema::hasTable('product_recipes') &&
            Schema::hasColumn('productions', 'output_qty_kg') &&
            Schema::hasColumn('product_recipes', 'qty')
        ) {
            $today  = now()->startOfDay();
            $days30 = 30;
            $days7  = 7;
            $from30 = $today->copy()->subDays($days30 - 1); // includes today

            $usageRows = DB::table('productions as p')
                ->join('product_recipes as r', 'r.product_id', '=', 'p.product_id')
                ->selectRaw('DATE(p.production_date) as d')
                // use quantity_per_unit when present, fall back to qty
                ->selectRaw('SUM(p.output_qty_kg * COALESCE(r.quantity_per_unit, r.qty)) as total_kg')
                ->whereBetween('p.production_date', [$from30->toDateString(), $today->toDateString()])
                ->groupBy(DB::raw('DATE(p.production_date)'))
                ->orderBy('d')
                ->pluck('total_kg', 'd'); // ['2025-11-01' => 123.45, ...]

            // Build a dense 30-day series (fill gaps with 0)
            $series30 = [];
            $cursor   = $from30->copy();
            for ($i = 0; $i < $days30; $i++) {
                $key        = $cursor->toDateString();
                $series30[] = (float) ($usageRows[$key] ?? 0.0);
                $cursor->addDay();
            }

            if (! empty($series30)) {
                $sum30 = array_sum($series30);
                $avg30 = $days30 > 0 ? $sum30 / $days30 : 0.0;

                $last7  = array_slice($series30, -$days7);
                $count7 = max(count($last7), 1);
                $sum7   = array_sum($last7);
                $avg7   = $sum7 / $count7;

                // last 6 points for the mini spark-bars
                $sparkData = array_slice($series30, -6);
                if (empty($sparkData)) {
                    $sparkData = [0, 0, 0, 0, 0, 0];
                }
            }
        }

        // -------------------------------------------------------------
        // JSON response (for future AJAX / API use)
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
            ]);
        }

        // -------------------------------------------------------------
        // Blade view
        // -------------------------------------------------------------
        return view('materials.index', [
            'materials' => $materials,
            'search'    => $search,
            'sort'      => $sort,
            'onlyLow'   => $onlyLow,
            'expiry'    => $expiry,
            'storage'   => $storage,
            'avg30'     => $avg30,
            'avg7'      => $avg7,
            'sparkData' => $sparkData,
        ]);
    }

    /* =======================================================================
     | CREATE / STORE
     * ======================================================================= */

    /**
     * Show create form.
     *
     * Route: GET /materials/create
     */
    public function create()
    {
        $units        = Material::ALLOWED_UNITS;
        $storageTypes = Material::STORAGE_TYPES;

        return view('materials.create', compact('units', 'storageTypes'));
    }

    /**
     * Store a new material.
     *
     * Route: POST /materials
     */
    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        // Auto-generate batch_code if not given
        if (empty($data['batch_code'] ?? null)) {
            $data['batch_code'] = Material::generateBatchCode($data);
        }

        // Auto-compute stock_status if not set
        $qty = (float) ($data['quantity_kg'] ?? 0);
        $min = (float) ($data['min_stock_kg'] ?? 0);

        if (! isset($data['stock_status'])) {
            $data['stock_status'] = Material::computeStockStatus($qty, $min);
        }

        $material = DB::transaction(function () use ($data) {
            return Material::create($data);
        });

        return redirect()
            ->route('materials.index')
            ->with('success', "Material “{$material->material_name}” created.");
    }

    /* =======================================================================
     | SHOW / EDIT / UPDATE
     * ======================================================================= */

    /**
     * Show a single material.
     *
     * Route: GET /materials/{material}
     */
    public function show(Material $material)
    {
        if (request()->wantsJson()) {
            return response()->json(['ok' => true, 'material' => $material]);
        }

        return view('materials.show', compact('material'));
    }

    /**
     * Show edit form.
     *
     * Route: GET /materials/{material}/edit
     */
    public function edit(Material $material)
    {
        $units        = Material::ALLOWED_UNITS;
        $storageTypes = Material::STORAGE_TYPES;

        return view('materials.edit', compact('material', 'units', 'storageTypes'));
    }

    /**
     * Update a material.
     *
     * Route: PUT/PATCH /materials/{material}
     */
    public function update(Request $request, Material $material)
    {
        $data = $request->validate($this->rules($material->id));

        // Keep existing batch_code if not given
        if (empty($data['batch_code'] ?? null)) {
            $data['batch_code'] = $material->batch_code ?: Material::generateBatchCode($data);
        }

        // Auto-compute stock_status if not given
        $qty = (float) ($data['quantity_kg'] ?? $material->quantity_kg ?? 0);
        $min = (float) ($data['min_stock_kg'] ?? $material->min_stock_kg ?? 0);

        if (! isset($data['stock_status'])) {
            $data['stock_status'] = Material::computeStockStatus($qty, $min);
        }

        DB::transaction(function () use ($material, $data) {
            $material->update($data);
        });

        return redirect()
            ->route('materials.index')
            ->with('success', "Material “{$material->material_name}” updated.");
    }

    /* =======================================================================
     | DELETE / TRASH / RESTORE
     * ======================================================================= */

    /**
     * Soft delete a material.
     *
     * Route: DELETE /materials/{material}
     */
    public function destroy(Material $material)
    {
        DB::transaction(function () use ($material) {
            $material->delete();
        });

        return redirect()
            ->back()
            ->with('success', "Material “{$material->material_name}” deleted.");
    }

    /**
     * List soft-deleted materials (trash).
     *
     * Route: GET /materials/trash
     */
    public function trash(Request $request)
    {
        $materials = Material::onlyTrashed()
            ->orderByDesc('deleted_at')
            ->paginate(15);

        return view('materials.trash', compact('materials'));
    }

    /**
     * Restore a soft-deleted material.
     *
     * Route: PATCH /materials/{id}/restore
     */
    public function restore(int $id)
    {
        $material = Material::onlyTrashed()->findOrFail($id);

        DB::transaction(function () use ($material) {
            $material->restore();
        });

        return redirect()
            ->route('materials.trash')
            ->with('success', "Material “{$material->material_name}” restored.");
    }

    /**
     * Permanently delete a soft-deleted material.
     *
     * Route: DELETE /materials/{id}/force
     */
    public function forceDelete(int $id)
    {
        $material = Material::onlyTrashed()->findOrFail($id);

        DB::transaction(function () use ($material) {
            $material->forceDelete();
        });

        return redirect()
            ->route('materials.trash')
            ->with('success', "Material permanently removed.");
    }

    /* =======================================================================
     | LIGHTWEIGHT JSON ENDPOINTS (AJAX HELPERS)
     * ======================================================================= */

    /**
     * Adjust quantity by a delta (e.g., deduct usage or add stock).
     *
     * Route: PATCH /materials/{material}/adjust-quantity
     * Body: { "delta": -1.25 } or { "delta_kg": -1.25 }
     */
    public function adjustQuantity(Request $request, Material $material)
    {
        // Accept either "delta" or "delta_kg" (your Blade uses delta_kg)
        $rawDelta = $request->input('delta', $request->input('delta_kg'));

        $request->validate([
            'delta'    => ['nullable', 'numeric', 'between:-999999999999.999,999999999999.999'],
            'delta_kg' => ['nullable', 'numeric', 'between:-999999999999.999,999999999999.999'],
        ]);

        $delta = (float) ($rawDelta ?? 0);

        DB::transaction(function () use ($material, $delta) {
            $newQty = max(0.0, (float) $material->quantity_kg + $delta);

            $material->update([
                'quantity_kg'  => $newQty,
                'stock_status' => Material::computeStockStatus($newQty, (float) $material->min_stock_kg),
            ]);
        });

        return response()->json([
            'ok'       => true,
            'material' => $material->fresh(),
        ]);
    }

    /**
     * Set quantity to an absolute value.
     *
     * Route: PATCH /materials/{material}/set-quantity
     * Body: { "quantity_kg": 12.345 }
     */
    public function setQuantity(Request $request, Material $material)
    {
        $validated = $request->validate([
            'quantity_kg' => ['required', 'numeric', 'min:0', 'max:999999999999.999'],
        ]);

        DB::transaction(function () use ($material, $validated) {
            $newQty = (float) $validated['quantity_kg'];

            $material->update([
                'quantity_kg'  => $newQty,
                'stock_status' => Material::computeStockStatus($newQty, (float) $material->min_stock_kg),
            ]);
        });

        return response()->json([
            'ok'       => true,
            'material' => $material->fresh(),
        ]);
    }

    /* =======================================================================
     | VALIDATION
     * ======================================================================= */

    /**
     * Central validation rules (with expiry, storage, supplier, batch).
     */
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

            // supplier + batch
            'supplier_name'   => ['nullable', 'string', 'max:255'],
            'batch_code'      => ['nullable', 'string', 'max:64'],

            // storage + dates
            'storage_type'    => ['nullable', 'string', Rule::in(Material::STORAGE_TYPES)],
            'manufactured_at' => ['nullable', 'date', 'before_or_equal:today'],
            'received_at'     => ['nullable', 'date', 'before_or_equal:today'],
            'expires_at'      => ['nullable', 'date', 'after_or_equal:manufactured_at'],

            // optional notes
            'notes'           => ['nullable', 'string', 'max:2000'],
        ];
    }
}
