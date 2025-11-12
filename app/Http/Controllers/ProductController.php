<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Material;
use App\Models\ProductRecipe;
use App\Models\Production;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /* ============================== LIST / SHOW ============================== */

    /** Products index with filters, sort, and pagination (with latest production snapshot). */
    public function index(Request $request)
    {
        $perPage   = max(1, (int) $request->integer('per_page', 10));
        $search    = $request->get('search');
        $category  = $request->get('category');
        $status    = $request->get('status');
        $sort      = $request->get('sort');

        $products = Product::query()
            ->search($search)
            ->category($category)
            ->status($status)
            ->withLatestProductionSnapshot()               // provides latest_* scalar fields
            ->sorted($sort)
            ->withCount(['recipes', 'children as variants_count'])
            ->paginate($perPage)
            ->appends($request->query());

        $categories = Product::query()
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'ok'        => true,
                'data'      => $products->items(),
                'meta'      => [
                    'current_page' => $products->currentPage(),
                    'per_page'     => $products->perPage(),
                    'total'        => $products->total(),
                    'last_page'    => $products->lastPage(),
                ],
                'filters'   => compact('search','category','status','sort'),
                'categories'=> $categories,
            ]);
        }

        return view('products.index', compact('products', 'categories'));
    }

    /** Single product page with batches, recipe, variants. */
    public function show(Product $product, Request $request)
    {
        $product->load([
            'productions' => fn ($q) => $q->orderByDesc('production_date')->orderByDesc('id'),
            'recipes.material' => function ($q) {
                $q->select('id', 'material_name', 'unit')
                  ->addSelect(DB::raw('unit_price as default_unit_price'));
            },
            'parent:id,product_name',
            'children:id,product_name,parent_id',
        ]);

        $materials = Material::query()
            ->select('id', 'material_name', 'unit')
            ->addSelect(DB::raw('unit_price as default_unit_price'))
            ->orderBy('material_name')
            ->get();

        $recipe   = $product->recipes;
        $variants = $product->children;

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'ok'        => true,
                'product'   => $product,
                'materials' => $materials,
                'recipe'    => $recipe,
                'variants'  => $variants,
            ]);
        }

        return view('products.show', compact('product', 'materials', 'recipe', 'variants'));
    }

    /* ============================== CREATE / EDIT ============================== */

    public function create()
    {
        return view('products.create', [
            'parents'        => Product::roots()->orderBy('product_name')->get(['id', 'product_name']),
            'categories'     => Product::query()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
            'unitOptions'    => ['kg' => 'Kilograms', 'pcs' => 'Pieces', 'lt' => 'Liters'],
            'statusOptions'  => ['active' => 'Active', 'inactive' => 'Inactive', 'pending' => 'Pending', 'on_sale' => 'On Sale'],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateProduct($request);

        $product = Product::create($data);

        if ($request->hasFile('image')) {
            try {
                $product->setImageFromUpload($request->file('image'));
                $product->save();
            } catch (\Throwable $e) {
                Log::warning('Product image upload failed', ['product_id' => $product->id, 'error' => $e->getMessage()]);
            }
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['ok' => true, 'product' => $product], 201);
        }

        return redirect()->route('products.show', $product)->with('success', 'Product created.');
    }

    public function edit(Product $product)
    {
        return view('products.edit', [
            'product'       => $product,
            'parents'       => Product::roots()->where('id', '<>', $product->id)->orderBy('product_name')->get(['id','product_name']),
            'categories'    => Product::query()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
            'unitOptions'   => ['kg' => 'Kilograms', 'pcs' => 'Pieces', 'lt' => 'Liters'],
            'statusOptions' => ['active' => 'Active', 'inactive' => 'Inactive', 'pending' => 'Pending', 'on_sale' => 'On Sale'],
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validateProduct($request, $product->id);

        if (!empty($data['parent_id']) && (int)$data['parent_id'] === (int)$product->id) {
            unset($data['parent_id']);
        }

        $product->update($data);

        if ($request->hasFile('image')) {
            try {
                $product->setImageFromUpload($request->file('image'));
                $product->save();
            } catch (\Throwable $e) {
                Log::warning('Product image upload failed (update)', ['product_id' => $product->id, 'error' => $e->getMessage()]);
            }
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['ok' => true, 'product' => $product->fresh()]);
        }

        return redirect()->route('products.show', $product)->with('success', 'Product updated.');
    }

    /**
     * PERMANENTLY delete a product. If it's a parent, delete all variants and their dependents.
     * - Deletes Sales first, then Productions, then Recipes, then Product(s).
     * - Uses forceDelete where models use SoftDeletes to truly purge.
     */
    public function destroy(Request $request, Product $product)
    {
        try {
            DB::transaction(function () use ($product) {
                $targets = collect([$product])->merge($product->children()->get());

                foreach ($targets as $p) {
                    // Sales -> Productions -> Recipes
                    Sale::where('product_id', $p->id)->withTrashed()->get()->each(function ($row) {
                        method_exists($row, 'forceDelete') ? $row->forceDelete() : $row->delete();
                    });

                    Production::where('product_id', $p->id)->withTrashed()->get()->each(function ($row) {
                        method_exists($row, 'forceDelete') ? $row->forceDelete() : $row->delete();
                    });

                    if (method_exists($p, 'recipes')) {
                        $p->recipes()->delete();
                    }

                    // Delete main image if present (double-safety)
                    try {
                        if (!empty($p->image_path) && Storage::disk('public')->exists($p->image_path)) {
                            Storage::disk('public')->delete($p->image_path);
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Failed to delete product image', ['product_id' => $p->id, 'error' => $e->getMessage()]);
                    }

                    method_exists($p, 'forceDelete') ? $p->forceDelete() : $p->delete();
                }
            });

            [$forecastedDemand, $actualInventory, $shortfall, $recommendedProduction] = $this->totalsSnapshot();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'ok'         => true,
                    'message'    => 'Product permanently deleted.',
                    'product_id' => (int) $product->id,
                    'totals'     => [
                        'forecastedDemand'      => (float)$forecastedDemand,
                        'actualInventory'       => (float)$actualInventory,
                        'shortfall'             => (float)$shortfall,
                        'recommendedProduction' => (float)$recommendedProduction,
                    ],
                ]);
            }

            return redirect()->route('products.index')->with('success', 'Product permanently deleted.');
        } catch (\Throwable $e) {
            Log::error('Failed to permanently delete product', [
                'product_id' => $product->id ?? null,
                'error'      => $e->getMessage(),
            ]);

            $msg = config('app.debug') ? 'Delete failed: '.$e->getMessage() : 'Server error while deleting product.';

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => $msg], 500);
            }
            return redirect()->back()->with('error', $msg);
        }
    }

    /**
     * QUICK STORE (AJAX) — used by “+ New variant” button in Orders modal.
     * Accepts: product_name, parent_id (optional), unit_cost (optional), shelf_life_days (optional)
     * Returns JSON: { id, product_name, unit_cost }
     */
    public function quickStore(Request $request)
    {
        $name = trim((string) ($request->input('product_name') ?? $request->input('name')));
        if ($name === '') {
            return $request->wantsJson()
                ? response()->json(['ok' => false, 'message' => 'Product name is required'], 422)
                : back()->withErrors(['name' => 'Product name is required'])->withInput();
        }

        $validated = $request->validate([
            'parent_id'        => ['nullable','integer', Rule::exists('products','id')->whereNull('deleted_at')],
            'unit_cost'        => ['nullable','numeric','min:0'],
            'shelf_life_days'  => ['nullable','integer','min:0'],
        ]);

        if (Product::where('product_name', $name)->exists()) {
            $msg = 'Product name already exists.';
            return $request->wantsJson()
                ? response()->json(['ok' => false, 'message' => $msg], 422)
                : back()->withErrors(['name' => $msg])->withInput();
        }

        $product = Product::create([
            'product_name'     => $name,
            'parent_id'        => $validated['parent_id'] ?? null,
            'unit_cost'        => (float)($validated['unit_cost'] ?? 0),
            'shelf_life_days'  => (int)($validated['shelf_life_days'] ?? 0),
            'status'           => 'active',
            'unit'             => 'kg',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'ok'           => true,
                'id'           => $product->id,
                'product_name' => $product->product_name,
                'unit_cost'    => (float)($product->unit_cost ?? 0),
            ]);
        }

        return back()->with('success', 'Product added.');
    }

    /* ============================== MATERIALS / RECIPE ============================== */

    /** BOM editor landing (materials picker + current recipe lines). */
    public function materialsIndex(Product $product, Request $request)
    {
        $product->load('recipes.material');

        $materials = Material::query()
            ->select('id', 'material_name', 'unit')
            ->addSelect(DB::raw('unit_price as default_unit_price'))
            ->orderBy('material_name')
            ->get();

        $recipe = $product->recipes;

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'ok'        => true,
                'product'   => $product->only(['id','product_name']),
                'materials' => $materials,
                'recipe'    => $recipe,
            ]);
        }

        return view('products.materials.index', compact('product', 'materials', 'recipe'));
    }

    /**
     * Save (sync) recipe lines for a product.
     * Accepts either LEGACY rows:
     *   rows[*]: { ingredient_id, qty, unit_price }
     * Or MODERN rows:
     *   rows[*]: { material_id, quantity_per_unit, unit, wastage_pct, unit_price_snapshot }
     */
    public function recipeStore(Request $request, Product $product)
    {
        $validated = $request->validate([
            'rows'                           => ['required','array','min:1'],
            'rows.*.ingredient_id'           => ['nullable','integer', 'exists:materials,id'],
            'rows.*.material_id'             => ['nullable','integer', 'exists:materials,id'],
            'rows.*.qty'                     => ['nullable','numeric','min:0'],
            'rows.*.quantity_per_unit'       => ['nullable','numeric','min:0'],
            'rows.*.unit'                    => ['nullable','string','max:10'],
            'rows.*.wastage_pct'             => ['nullable','numeric','min:0','max:100'],
            'rows.*.unit_price'              => ['nullable','numeric','min:0'],
            'rows.*.unit_price_snapshot'     => ['nullable','numeric','min:0'],
        ]);

        DB::transaction(function () use ($product, $validated) {
            $keepMaterialIds = [];

            foreach ($validated['rows'] as $row) {
                // Resolve material id and quantity from either legacy or modern keys
                $matId = (int) (($row['material_id'] ?? 0) ?: ($row['ingredient_id'] ?? 0));
                if ($matId <= 0) continue;

                $qty = $this->normQty($row['quantity_per_unit'] ?? $row['qty'] ?? 0);
                $wst = $this->normPct($row['wastage_pct'] ?? 0);
                $unt = isset($row['unit']) ? trim((string) $row['unit']) : null;

                $snap = $this->normMoney(
                    $row['unit_price_snapshot'] ?? $row['unit_price'] ?? null
                );

                // If no snapshot provided, pull current Material price
                if ($snap === 0.0) {
                    $snap = (float) (Material::whereKey($matId)->value('unit_price') ?? 0);
                }

                $payload = [
                    'qty'                 => $qty,                 // legacy column stays in sync
                    'unit_price_snapshot' => $snap,
                ];

                // Write modern columns if they exist on your table (safe even if ignored)
                $payload['material_id']       = $matId;
                $payload['ingredient_id']     = $matId;          // keep legacy FK
                $payload['quantity_per_unit'] = $qty;
                if (!is_null($unt)) $payload['unit'] = $unt;
                $payload['wastage_pct']       = $wst;

                ProductRecipe::updateOrCreate(
                    ['product_id' => (int) $product->id, 'ingredient_id' => $matId],
                    $payload
                );

                $keepMaterialIds[] = $matId;
            }

            // Remove any rows not present in the submitted payload (by material/ingredient id)
            if (!empty($keepMaterialIds)) {
                ProductRecipe::where('product_id', $product->id)
                    ->whereNotIn('ingredient_id', $keepMaterialIds)
                    ->delete();
            } else {
                // if nothing valid submitted, clear all
                ProductRecipe::where('product_id', $product->id)->delete();
            }

            // Optional: recompute product.unit_cost from recipe lines (qty_effective * snapshot)
            $totalCost = ProductRecipe::with('material:id,unit_price')
                ->where('product_id', $product->id)
                ->get()
                ->sum(function ($r) {
                    $qty = method_exists($r, 'getQtyEffectiveAttribute')
                        ? $r->qty_effective
                        : (float) $r->qty;

                    $snap = $r->unit_price_snapshot ?: (float) ($r->material->unit_price ?? 0);
                    return round($qty * (float) $snap, 2);
                });

            $product->update(['unit_cost' => $totalCost]);
        });

        return $request->wantsJson()
            ? response()->json(['ok' => true, 'message' => 'Recipe saved.', 'product_id' => $product->id])
            : back()->with('success', 'Recipe saved.');
    }

    /** Remove a single recipe line. */
    public function recipeDestroy(Product $product, ProductRecipe $line, Request $request)
    {
        if ((int) $line->product_id !== (int) $product->id) {
            abort(404);
        }
        $line->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['ok' => true, 'message' => 'Recipe line removed.']);
        }

        return back()->with('success', 'Recipe line removed.');
    }

    /* ============================== IMAGE ONLY ============================== */

    public function updateImage(Request $request, Product $product)
    {
        $request->validate(['image' => ['required', 'image', 'max:4096']]);

        try {
            $product->setImageFromUpload($request->file('image'));
            $product->save();
        } catch (\Throwable $e) {
            Log::warning('Product image upload failed (updateImage)', ['product_id' => $product->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Image upload failed.');
        }

        return back()->with('success', 'Image updated.');
    }

    /* ============================== VALIDATION ============================== */

    protected function validateProduct(Request $request, ?int $productId = null): array
    {
        return $request->validate([
            'parent_id'           => ['nullable','integer', Rule::exists('products','id')->whereNull('deleted_at')],
            'product_code'        => ['nullable', 'string', 'max:100', Rule::unique('products', 'product_code')->ignore($productId)],
            'product_name'        => ['required', 'string', 'max:255', Rule::unique('products', 'product_name')->ignore($productId)],
            'category'            => ['nullable', 'string', 'max:100'],
            'unit'                => ['nullable', Rule::in(['kg','pcs','lt'])],
            'status'              => ['nullable', Rule::in(['active','inactive','pending','on_sale'])],
            'default_price'       => ['nullable', 'numeric', 'min:0'],
            'shelf_life_days'     => ['nullable', 'integer', 'min:0'],
            'yield_rate'          => ['nullable', 'numeric', 'min:0', 'max:100'],
            'standard_batch_size' => ['nullable', 'numeric', 'min:0'],
            'lead_time_days'      => ['nullable', 'integer', 'min:0'],
            'min_run_qty'         => ['nullable', 'numeric', 'min:0'],
            'max_run_qty'         => ['nullable', 'numeric', 'min:0'],
            'storage_zone'        => ['nullable', Rule::in(['chiller','freezer','ambient'])],
            'unit_cost'           => ['nullable', 'numeric', 'min:0'],
            'last_cost_date'      => ['nullable', 'date'],
            'temp_requirements'   => ['nullable', 'string', 'max:2000'],
            'line_constraints'    => ['nullable'],
            'image'               => ['nullable', 'image', 'max:4096'],
        ]);
    }

    /* ============================== HELPERS ============================== */

    private function totalsSnapshot(): array
    {
        $products = Product::all();
        $forecastedDemand      = (float) $products->sum('forecasted_demand');
        $actualInventory       = (float) $products->sum('quantity');
        $shortfall             = max($forecastedDemand - $actualInventory, 0.0);
        $recommendedProduction = $shortfall;

        return [$forecastedDemand, $actualInventory, $shortfall, $recommendedProduction];
    }

    private function normMoney($v): float
    {
        if (is_null($v)) return 0.00;
        if (is_numeric($v)) return round((float) $v, 2);

        $s = (string) $v;
        $s = preg_replace('/[₱\p{Sc}\s]+/u', '', $s);
        if (str_contains($s, ',') && str_contains($s, '.')) $s = str_replace(',', '', $s);
        elseif (str_contains($s, ',') && !str_contains($s, '.')) { $s = str_replace('.', '', $s); $s = str_replace(',', '.', $s); }
        else $s = str_replace(',', '', $s);

        return ($s === '' || !is_numeric($s)) ? 0.00 : round((float) $s, 2);
    }

    private function normQty($v): float
    {
        if (is_null($v)) return 0.000;
        if (is_numeric($v)) return round((float) $v, 3);

        $s = (string) $v;
        $s = preg_replace('/[\s,]+/u', '', $s);
        if ($s !== '' && str_contains($s, ',') && !str_contains($s, '.')) $s = str_replace(',', '.', $s);
        return ($s === '' || !is_numeric($s)) ? 0.000 : round((float) $s, 3);
    }

    private function normPct($v): float
    {
        if (is_null($v) || $v === '') return 0.00;
        $num = is_numeric($v) ? (float) $v : 0.00;
        return round(min(max($num, 0.00), 100.00), 2);
    }
}
