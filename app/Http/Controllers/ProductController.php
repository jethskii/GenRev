<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Material;
use App\Models\ProductRecipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Intervention\Image\Laravel\Facades\Image;

class ProductController extends Controller
{
    /* ============================== LIST / SHOW ============================== */

    /** Products index with filters, sort, and pagination (with latest production snapshot). */
    public function index(Request $request)
    {
        $perPage  = max(1, (int) $request->integer('per_page', 10));
        $search   = $request->get('search');
        $category = $request->get('category');
        $status   = $request->get('status');
        $sort     = $request->get('sort');

        $products = Product::query()
            ->search($search)
            ->category($category)
            ->status($status)
            ->withLatestProductionSnapshot()
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
                'ok'   => true,
                'data' => $products->items(),
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'per_page'     => $products->perPage(),
                    'total'        => $products->total(),
                    'last_page'    => $products->lastPage(),
                ],
                'filters'    => compact('search', 'category', 'status', 'sort'),
                'categories' => $categories,
            ]);
        }

        return view('products.index', compact('products', 'categories'));
    }

    /**
     * Archived (soft-deleted) products list.
     * Route: GET /products/archived -> products.archived
     */
    public function archived(Request $request)
    {
        $perPage = max(1, (int) $request->integer('per_page', 15));
        $search  = $request->get('search');

        $products = Product::onlyTrashed()
            ->when($search, function ($q) use ($search) {
                $q->where('product_name', 'like', '%' . $search . '%');
            })
            ->with(['parent:id,product_name'])
            ->orderByDesc('deleted_at')
            ->paginate($perPage)
            ->appends($request->query());

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'ok'   => true,
                'data' => $products->items(),
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'per_page'     => $products->perPage(),
                    'total'        => $products->total(),
                    'last_page'    => $products->lastPage(),
                ],
                'filters' => compact('search'),
            ]);
        }

        return view('production', [
            'products' => $products,
            'search'   => $search,
        ]);
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
            'parents'       => Product::roots()->orderBy('product_name')->get(['id', 'product_name']),
            'categories'    => Product::query()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
            'unitOptions'   => ['kg' => 'Kilograms', 'pcs' => 'Pieces', 'lt' => 'Liters'],
            'statusOptions' => ['active' => 'Active', 'inactive' => 'Inactive', 'pending' => 'Pending', 'on_sale' => 'On Sale'],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateProduct($request);

        $product = Product::create($data);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $this->syncProductImage($product, $file);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['ok' => true, 'product' => $product->fresh()], 201);
        }

        return redirect()->route('products.show', $product)->with('success', 'Product created.');
    }

    public function edit(Product $product)
    {
        return view('products.edit', [
            'product'       => $product,
            'parents'       => Product::roots()->where('id', '<>', $product->id)->orderBy('product_name')->get(['id', 'product_name']),
            'categories'    => Product::query()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
            'unitOptions'   => ['kg' => 'Kilograms', 'pcs' => 'Pieces', 'lt' => 'Liters'],
            'statusOptions' => ['active' => 'Active', 'inactive' => 'Inactive', 'pending' => 'Pending', 'on_sale' => 'On Sale'],
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validateProduct($request, $product->id);

        if (!empty($data['parent_id']) && (int) $data['parent_id'] === (int) $product->id) {
            unset($data['parent_id']);
        }

        $product->update($data);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $this->syncProductImage($product, $file);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['ok' => true, 'product' => $product->fresh()]);
        }

        return redirect()->route('products.show', $product)->with('success', 'Product updated.');
    }

    /**
     * Archive (soft delete) a product and its variants.
     * - Soft-deletes parent + children.
     * - For AJAX: returns JSON with redirect URL to archived list.
     * - For normal requests: redirects to products.archived.
     */
    public function archiveProduct(Request $request, Product $product)
    {
        Log::info('ARCHIVE PRODUCT ROUTE HIT', ['id' => $product->id]);

        try {
            DB::transaction(function () use ($product) {
                $targets = collect([$product])->merge($product->children()->get());

                foreach ($targets as $p) {
                    $p->delete();
                }
            });

            $redirectUrl = route('production');

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'ok'       => true,
                    'message'  => 'Product archived.',
                    'id'       => $product->id,
                    'redirect' => $redirectUrl,
                ]);
            }

            return redirect($redirectUrl)->with('success', 'Product archived.');
        } catch (\Throwable $e) {
            Log::error('ARCHIVE FAILED', ['id' => $product->id, 'error' => $e->getMessage()]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['ok' => false, 'error' => 'Archive failed'], 500);
            }

            return back()->with('error', 'Archive failed.');
        }
    }

    /**
     * QUICK STORE (AJAX) — used by “+ New variant” button in Orders modal.
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
            'parent_id'       => ['nullable', 'integer', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'unit_cost'       => ['nullable', 'numeric', 'min:0'],
            'shelf_life_days' => ['nullable', 'integer', 'min:0'],
        ]);

        if (Product::where('product_name', $name)->exists()) {
            $msg = 'Product name already exists.';
            return $request->wantsJson()
                ? response()->json(['ok' => false, 'message' => $msg], 422)
                : back()->withErrors(['name' => $msg])->withInput();
        }

        $product = Product::create([
            'product_name'    => $name,
            'parent_id'       => $validated['parent_id'] ?? null,
            'unit_cost'       => (float) ($validated['unit_cost'] ?? 0),
            'shelf_life_days' => (int) ($validated['shelf_life_days'] ?? 0),
            'status'          => 'active',
            'unit'            => 'kg',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'ok'           => true,
                'id'           => $product->id,
                'product_name' => $product->product_name,
                'unit_cost'    => (float) ($product->unit_cost ?? 0),
            ]);
        }

        return back()->with('success', 'Product added.');
    }

    /* ============================== LEGACY RECIPE ENDPOINTS ============================== */

    public function recipeStore(Request $request, Product $product)
    {
        $validated = $request->validate([
            'rows'                       => ['required', 'array', 'min:1'],
            'rows.*.ingredient_id'       => ['nullable', 'integer', 'exists:materials,id'],
            'rows.*.material_id'         => ['nullable', 'integer', 'exists:materials,id'],
            'rows.*.qty'                 => ['nullable', 'numeric', 'min:0'],
            'rows.*.quantity_per_unit'   => ['nullable', 'numeric', 'min:0'],
            'rows.*.unit'                => ['nullable', 'string', 'max:10'],
            'rows.*.wastage_pct'         => ['nullable', 'numeric', 'min:0', 'max:100'],
            'rows.*.unit_price'          => ['nullable', 'numeric', 'min:0'],
            'rows.*.unit_price_snapshot' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($product, $validated) {
            $keepMaterialIds = [];

            foreach ($validated['rows'] as $row) {
                $matId = (int) (($row['material_id'] ?? 0) ?: ($row['ingredient_id'] ?? 0));
                if ($matId <= 0) {
                    continue;
                }

                $qty = $this->normQty($row['quantity_per_unit'] ?? $row['qty'] ?? 0);
                $wst = $this->normPct($row['wastage_pct'] ?? 0);
                $unt = isset($row['unit']) ? trim((string) $row['unit']) : null;

                $snap = $this->normMoney(
                    $row['unit_price_snapshot'] ?? $row['unit_price'] ?? null
                );

                if ($snap === 0.0) {
                    $snap = (float) (Material::whereKey($matId)->value('unit_price') ?? 0);
                }

                $payload = [
                    'qty'                 => $qty,
                    'unit_price_snapshot' => $snap,
                    'material_id'         => $matId,
                    'ingredient_id'       => $matId,
                    'quantity_per_unit'   => $qty,
                    'wastage_pct'         => $wst,
                ];
                if (!is_null($unt)) {
                    $payload['unit'] = $unt;
                }

                ProductRecipe::updateOrCreate(
                    ['product_id' => (int) $product->id, 'ingredient_id' => $matId],
                    $payload
                );

                $keepMaterialIds[] = $matId;
            }

            if (!empty($keepMaterialIds)) {
                ProductRecipe::where('product_id', $product->id)
                    ->whereNotIn('ingredient_id', $keepMaterialIds)
                    ->delete();
            } else {
                ProductRecipe::where('product_id', $product->id)->delete();
            }

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
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'dimensions:min_width=300,min_height=300'],
        ]);

        try {
            $file = $request->file('image');
            $this->syncProductImage($product, $file);
        } catch (\Throwable $e) {
            Log::warning('Product image upload failed (updateImage)', [
                'product_id' => $product->id,
                'error'      => $e->getMessage(),
            ]);

            return back()->with('error', 'Image upload failed.');
        }

        return back()->with('success', 'Image updated.');
    }

    /* ============================== VALIDATION ============================== */

    protected function validateProduct(Request $request, ?int $productId = null): array
    {
        $rules = [
            'parent_id'          => ['nullable', 'integer', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'product_name'       => ['required', 'string', 'max:255', Rule::unique('products', 'product_name')->ignore($productId)],
            'category'           => ['nullable', 'string', 'max:100'],
            'unit'               => ['nullable', Rule::in(['kg', 'pcs', 'lt'])],
            'status'             => ['nullable', Rule::in(['active', 'inactive', 'pending', 'on_sale'])],
            'default_price'      => ['nullable', 'numeric', 'min:0'],
            'shelf_life_days'    => ['nullable', 'integer', 'min:0'],
            'yield_rate'         => ['nullable', 'numeric', 'min:0', 'max:100'],
            'standard_batch_size'=> ['nullable', 'numeric', 'min:0'],
            'lead_time_days'     => ['nullable', 'integer', 'min:0'],
            'min_run_qty'        => ['nullable', 'numeric', 'min:0'],
            'max_run_qty'        => ['nullable', 'numeric', 'min:0'],
            'storage_zone'       => ['nullable', Rule::in(['chiller', 'freezer', 'ambient'])],
            'unit_cost'          => ['nullable', 'numeric', 'min:0'],
            'last_cost_date'     => ['nullable', 'date'],
            'temp_requirements'  => ['nullable', 'string', 'max:2000'],
            'line_constraints'   => ['nullable'],
            'image'              => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'dimensions:min_width=300,min_height=300'],
        ];

        if (Schema::hasColumn('products', 'product_code')) {
            $rules['product_code'] = [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'product_code')->ignore($productId),
            ];
        }

        return $request->validate($rules);
    }

    /* ============================== HELPERS ============================== */

    private function totalsSnapshot(): array
    {
        $products        = Product::all();
        $forecasted      = (float) $products->sum('forecasted_demand');
        $actualInventory = (float) $products->sum('quantity');
        $shortfall       = max($forecasted - $actualInventory, 0.0);
        $recommendedProd = $shortfall;

        return [$forecasted, $actualInventory, $shortfall, $recommendedProd];
    }

    private function normMoney($v): float
    {
        if (is_null($v)) {
            return 0.00;
        }
        if (is_numeric($v)) {
            return round((float) $v, 2);
        }

        $s = (string) $v;
        $s = preg_replace('/[₱\p{Sc}\s]+/u', '', $s);

        if (str_contains($s, ',') && str_contains($s, '.')) {
            $s = str_replace(',', '', $s);
        } elseif (str_contains($s, ',') && !str_contains($s, '.')) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } else {
            $s = str_replace(',', '', $s);
        }

        return ($s === '' || !is_numeric($s)) ? 0.00 : round((float) $s, 2);
    }

    private function normQty($v): float
    {
        if (is_null($v)) {
            return 0.000;
        }
        if (is_numeric($v)) {
            return round((float) $v, 3);
        }

        $s = (string) $v;
        $s = preg_replace('/[\s,]+/u', '', $s);
        if ($s !== '' && str_contains($s, ',') && !str_contains($s, '.')) {
            $s = str_replace(',', '.', $s);
        }

        return ($s === '' || !is_numeric($s)) ? 0.000 : round((float) $s, 3);
    }

    private function normPct($v): float
    {
        if (is_null($v) || $v === '') {
            return 0.00;
        }
        $num = is_numeric($v) ? (float) $v : 0.00;
        return round(min(max($num, 0.00), 100.00), 2);
    }

    /**
     * Central image handler.
     */
    private function syncProductImage(Product $product, \Illuminate\Http\UploadedFile $file): void
    {
        $usedCustom = false;

        if (method_exists($product, 'setImageFromUpload')) {
            try {
                $product->setImageFromUpload($file);
                $usedCustom = true;
            } catch (\Throwable $e) {
                Log::warning('setImageFromUpload failed, using controller pipeline instead', [
                    'product_id' => $product->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        if ($usedCustom && (!empty($product->card_image_url) || !empty($product->card_image_srcset))) {
            $product->save();
            return;
        }

        $this->applyImageToProduct($product, $file);
    }

    /**
     * Intervention-based pipeline with safe fallback store().
     */
    private function applyImageToProduct(Product $product, \Illuminate\Http\UploadedFile $file): void
    {
        try {
            if (!class_exists(Image::class)) {
                throw new \RuntimeException('Intervention Image not installed/configured');
            }

            $disk     = 'public';
            $baseName = \Illuminate\Support\Str::slug($product->product_name ?? 'product');
            $base     = "products/{$product->id}/{$baseName}";

            $img    = Image::read($file->getRealPath())->orient();
            $master = (clone $img)->scaleDown(1600, 1600);

            $w1200 = (clone $master)->scaleDown(1200, 1200);
            $w800  = (clone $master)->scaleDown(800, 800);
            $w400  = (clone $master)->scaleDown(400, 400);

            $path1200 = "{$base}-1200.webp";
            $path800  = "{$base}-800.webp";
            $path400  = "{$base}-400.webp";

            Storage::disk($disk)->put($path1200, (string) $w1200->toWebp(quality: 80));
            Storage::disk($disk)->put($path800, (string) $w800->toWebp(quality: 80));
            Storage::disk($disk)->put($path400, (string) $w400->toWebp(quality: 80));

            $url1200 = Storage::disk($disk)->url($path1200);
            $url800  = Storage::disk($disk)->url($path800);
            $url400  = Storage::disk($disk)->url($path400);

            $srcset = "{$url400} 400w, {$url800} 800w, {$url1200} 1200w";

            $product->image_disk        = $disk;
            $product->image_path        = $path1200;
            $product->image_medium_path = $path800;
            $product->image_thumb_path  = $path400;

            $product->image_url         = $url1200;
            $product->card_image_url    = $url800;
            $product->card_image_srcset = $srcset;

            $product->save();
        } catch (\Throwable $e) {
            Log::warning('applyImageToProduct failed, using simple store()', [
                'product_id' => $product->id,
                'error'      => $e->getMessage(),
            ]);

            try {
                $disk     = 'public';
                $baseName = \Illuminate\Support\Str::slug($product->product_name ?? 'product');
                $dir      = "products/{$product->id}";
                $ext      = $file->getClientOriginalExtension() ?: 'jpg';
                $filename = "{$baseName}.{$ext}";
                $path     = "{$dir}/{$filename}";

                Storage::disk($disk)->putFileAs($dir, $file, $filename);
                $url = Storage::disk($disk)->url($path);

                $product->image_disk        = $disk;
                $product->image_path        = $path;
                $product->image_medium_path = null;
                $product->image_thumb_path  = null;

                $product->image_url         = $url;
                $product->card_image_url    = $url;
                $product->card_image_srcset = null;

                $product->save();
            } catch (\Throwable $e2) {
                Log::error('Fallback store() for product image failed', [
                    'product_id' => $product->id,
                    'error'      => $e2->getMessage(),
                ]);
            }
        }
    }
}
