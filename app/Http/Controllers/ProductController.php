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

    /** Products index with filters, sort, and pagination. */
    public function index(Request $request)
    {
        $perPage = max(1, (int) $request->integer('per_page', 10));

        $products = Product::query()
            ->search($request->get('search'))
            ->category($request->get('category'))
            ->status($request->get('status'))
            ->sorted($request->get('sort'))
            ->withCount(['recipes', 'children as variants_count'])
            ->paginate($perPage)
            ->appends($request->query());

        $categories = Product::query()
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('products.index', compact('products', 'categories'));
    }

    /** Single product page with batches, recipe, and quick sale. */
    public function show(Product $product)
    {
        $product->load([
            'productions' => fn ($q) => $q->orderByDesc('production_date')->orderByDesc('id'),
            'recipes.material' => function ($q) {
                $q->select('id', 'material_name', 'unit')
                  ->addSelect(\DB::raw('unit_price as default_unit_price'));
            },
            'parent:id,product_name',
            'children:id,product_name,parent_id',
        ]);

        $materials = Material::query()
            ->select('id', 'material_name', 'unit')
            ->addSelect(\DB::raw('unit_price as default_unit_price'))
            ->orderBy('material_name')
            ->get();

        $recipe = $product->recipes;
        $variants = $product->children;

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

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product = Product::create($data);

        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Product created.');
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

        // prevent setting parent to one of its descendants
        if (!empty($data['parent_id']) && (int)$data['parent_id'] === (int)$product->id) {
            unset($data['parent_id']);
        }

        if ($request->hasFile('image')) {
            if (!empty($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);

        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Product updated.');
    }

    /**
     * PERMANENTLY delete a product. If it's a parent, delete all variants and their dependents.
     */
    public function destroy(Request $request, Product $product)
    {
        try {
            DB::transaction(function () use ($product) {
                $targets = collect([$product])->merge($product->children()->get());

                foreach ($targets as $p) {
                    // 1) Delete dependent rows
                    Sale::where('product_id', $p->id)->delete();
                    Production::where('product_id', $p->id)->delete();
                    if (method_exists($p, 'recipes')) {
                        $p->recipes()->delete();
                    }

                    // 2) Delete image
                    if (!empty($p->image)) {
                        try { Storage::disk('public')->delete($p->image); } catch (\Throwable $e) {
                            Log::warning('Failed to delete product image', ['product_id' => $p->id, 'error' => $e->getMessage()]);
                        }
                    }

                    // 3) Force delete
                    if (in_array('Illuminate\\Database\\Eloquent\\SoftDeletes', class_uses_recursive($p))) {
                        $p->forceDelete();
                    } else {
                        $p->delete();
                    }
                }
            });

            // KPI snapshot for dashboard badges
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
     * QUICK STORE (AJAX) — used by your “+ New variant” button in the Orders modal.
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
            'parent_id'        => ['nullable','integer', Rule::exists('products','id')],
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

    public function materialsIndex(Product $product)
    {
        $product->load('recipes.material');

        $materials = Material::query()
            ->select('id', 'material_name', 'unit')
            ->addSelect(\DB::raw('unit_price as default_unit_price'))
            ->orderBy('material_name')
            ->get();

        $recipe = $product->recipes;

        return view('products.materials.index', compact('product', 'materials', 'recipe'));
    }

    /** Save (sync) recipe lines for a product. */
    public function recipeStore(Request $request, Product $product)
    {
        $validated = $request->validate([
            'rows'                   => ['required','array','min:1'],
            'rows.*.ingredient_id'   => ['required','integer', Rule::exists('materials','id')],
            'rows.*.qty'             => ['required','numeric','min:0'],
            'rows.*.unit_price'      => ['required','numeric','min:0'],
        ]);

        DB::transaction(function () use ($product, $validated) {
            $keepIds = [];

            foreach ($validated['rows'] as $row) {
                $matId = (int) $row['ingredient_id'];
                $keepIds[] = $matId;

                ProductRecipe::updateOrCreate(
                    [
                        'product_id'    => (int) $product->id,
                        'ingredient_id' => $matId,
                    ],
                    [
                        'qty'                 => (float) $row['qty'],
                        'unit_price_snapshot' => (float) $row['unit_price'],
                    ]
                );
            }

            ProductRecipe::where('product_id', $product->id)
                ->whereNotIn('ingredient_id', $keepIds)
                ->delete();
        });

        return back()->with('success', 'Recipe saved.');
    }

    /** Remove a single recipe line. */
    public function recipeDestroy(Product $product, ProductRecipe $line)
    {
        if ((int) $line->product_id !== (int) $product->id) {
            abort(404);
        }
        $line->delete();

        return back()->with('success', 'Recipe line removed.');
    }

    /* ============================== IMAGE ONLY ============================== */

    public function updateImage(Request $request, Product $product)
    {
        $request->validate(['image' => ['required', 'image', 'max:4096']]);

        if (!empty($product->image)) {
            Storage::disk('public')->delete($product->image);
        }
        $product->image = $request->file('image')->store('products', 'public');
        $product->save();

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
}
