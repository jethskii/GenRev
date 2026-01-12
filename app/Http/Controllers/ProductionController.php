<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Intervention\Image\Laravel\Facades\Image;
use App\Models\Product;
use App\Models\Production;
use App\Models\Sale;
use App\Models\Material;

class ProductionController extends Controller
{
    /* ============================= INDEX / FILTER ============================= */

    // /production/{parent}/types  (route-model bound)
    public function suggestTypes(Product $parent): JsonResponse
    {
        $fromOrders = Production::query()
            ->where(function ($q) use ($parent) {
                $q->where('parent_product_id', $parent->id)
                    ->orWhere(function ($q2) use ($parent) {
                        $q2->whereNull('parent_product_id')
                            ->where('product_id', $parent->id);
                    });
            })
            ->whereNotNull('product_name_snapshot')
            ->pluck('product_name_snapshot');

        $fromVariants = Product::where('parent_id', $parent->id)->pluck('product_name');
        $maybeCat = collect($parent->category ? [$parent->category] : []);

        $types = $fromOrders
            ->merge($fromVariants)
            ->merge($maybeCat)
            ->map(fn($s) => trim((string) $s))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return response()->json(['types' => $types]);
    }

    private function computeNextTypeLabel(Product $parent, \Illuminate\Support\Collection $existing): string
    {
        $maxN = 0;
        foreach ($existing as $label) {
            if (preg_match('/\bType\s+(\d+)\b/i', (string) $label, $m)) {
                $n = (int) $m[1];
                if ($n > $maxN) {
                    $maxN = $n;
                }
            }
        }

        $candidate = $maxN > 0 ? $maxN + 1 : ($existing->count() + 1);

        $proposed = "Type {$candidate}";
        $i = 0;
        while ($existing->contains(fn($v) => 0 === strcasecmp(trim((string) $v), $proposed))) {
            $i++;
            $proposed = "Type " . ($candidate + $i);
            if ($i > 999) {
                break;
            }
        }

        return $proposed;
    }

    // Lightweight sales types API: /production/sales-types?product_id=123
    public function salesTypes(Request $request): JsonResponse
    {
        $productId = (int) $request->get('product_id');
        if ($productId <= 0) {
            return response()->json(['ok' => false, 'list' => [], 'next' => 'Type 1'], 422);
        }

        $parent = Product::find($productId);
        if (!$parent) {
            return response()->json(['ok' => false, 'list' => [], 'next' => 'Type 1'], 404);
        }

        $fromOrders = Production::query()
            ->where(function ($q) use ($parent) {
                $q->where('parent_product_id', $parent->id)
                    ->orWhere(function ($q2) use ($parent) {
                        $q2->whereNull('parent_product_id')
                            ->where('product_id', $parent->id);
                    });
            })
            ->whereNotNull('product_name_snapshot')
            ->pluck('product_name_snapshot');

        $fromVariants = Product::where('parent_id', $parent->id)->pluck('product_name');
        $maybeCat = collect($parent->category ? [$parent->category] : []);

        $list = $fromOrders
            ->merge($fromVariants)
            ->merge($maybeCat)
            ->map(fn($s) => trim((string) $s))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $next = $this->computeNextTypeLabel($parent, collect($list));

        return response()->json([
            'ok' => true,
            'list' => array_values($list),
            'next' => $next,
        ]);
    }

    public function index(Request $request)
    {
        $selectedCategory = $request->string('category')->toString() ?: null;
        $sort = (string) $request->get('sort', 'urgency');

        $products = Product::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = trim((string) $request->get('search'));
                $q->where('product_name', 'like', "%{$s}%");
            })
            ->when($selectedCategory, fn($q) => $q->where('category', $selectedCategory))
            ->orderByDesc('production_date')
            ->get();

        $products = $this->enrichProductsForCards($products);
        $products = $this->sortProducts($products, $sort);

        [$forecastedDemand, $actualInventory, $shortfall, $recommendedProduction] = $this->totalsSnapshot();

        $categories = Product::whereNotNull('category')->distinct()->orderBy('category')->pluck('category');
        $allProducts = Product::orderBy('product_name')->get();
        $consumeMaterials = (bool) config('app.consume_materials', false);

        return view('production.index', compact(
            'products',
            'forecastedDemand',
            'actualInventory',
            'shortfall',
            'recommendedProduction',
            'categories',
            'selectedCategory',
            'allProducts',
            'sort',
            'consumeMaterials'
        ));
    }

    public function filter(Request $request): JsonResponse
    {
        $sort = (string) $request->get('sort', 'urgency');

        $products = Product::query()
            ->when($request->filled('category'), fn($q) => $q->where('category', $request->get('category')))
            ->when($request->filled('search'), fn($q) => $q->where('product_name', 'like', '%' . $request->get('search') . '%'))
            ->orderByDesc('production_date')
            ->get();

        $products = $this->enrichProductsForCards($products);
        $products = $this->sortProducts($products, $sort);

        return response()->json([
            'html' => view('production.partials.product-cards', compact('products'))->render(),
        ]);
    }

    /* =============================== CREATE (DASHBOARD CARD) =============================== */

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'product_name' => ['nullable', 'string', 'max:255'],
            'batch_number' => ['nullable', 'string', 'max:255'],
            'forecasted_demand' => ['nullable', 'numeric', 'min:0'],
            'unit_price_pack' => ['nullable', 'numeric', 'min:0'],
            'unit_price_bag' => ['nullable', 'numeric', 'min:0'],
            'available_pack' => ['nullable', 'integer', 'min:0'],
            'available_bag' => ['nullable', 'integer', 'min:0'],
            'production_date' => ['required', 'date'],
            'expiration_date' => ['nullable', 'date', 'after_or_equal:production_date'],
            'category' => ['nullable', 'string', 'max:120'],
            'remarks' => ['nullable', 'string', 'max:500'],

            // IMAGE
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'dimensions:min_width=300,min_height=300'],
            'product_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'dimensions:min_width=300,min_height=300'],

            // legacy accepted
            'current_inventory' => ['nullable', 'numeric', 'min:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            // Resolve or create product
            if (empty($validated['product_id'])) {
                $name = isset($validated['product_name'])
                    ? ucfirst(strtolower(trim($validated['product_name'])))
                    : null;

                if (!$name) {
                    return $this->respondError($request, ['product_name' => 'Please select a product or enter a new name.']);
                }

                $attrs = $this->filterProductColumns([
                    'forecasted_demand' => (float) ($validated['forecasted_demand'] ?? 0),
                    'production_date' => $validated['production_date'],
                    'stock_status' => 'in_stock',
                    'category' => $validated['category'] ?? null,
                ]);

                $product = Product::firstOrCreate(['product_name' => $name], $attrs);

                // IMAGE FROM ADD PRODUCTION MODAL
                $file = $request->file('image') ?? $request->file('product_image');
                if ($file) {
                    if (method_exists($product, 'setImageFromUpload')) {
                        try {
                            $product->setImageFromUpload($file);
                        } catch (\Throwable $e) {
                            Log::warning('Product image upload failed', ['error' => $e->getMessage()]);
                            $this->applyImageToProduct($product, $file);
                        }
                    } else {
                        $this->applyImageToProduct($product, $file);
                    }
                }

                $product->save();
            } else {
                $product = Product::findOrFail((int) $validated['product_id']);

                $updates = $this->filterProductColumns([
                    'forecasted_demand' => array_key_exists('forecasted_demand', $validated)
                        ? (float) $validated['forecasted_demand']
                        : $product->forecasted_demand,
                    'category' => $validated['category'] ?? $product->category,
                    'production_date' => $validated['production_date'],
                    'stock_status' => 'in_stock',
                ]);

                $product->fill($updates);

                $file = $request->file('image') ?? $request->file('product_image');
                if ($file) {
                    if (method_exists($product, 'setImageFromUpload')) {
                        try {
                            $product->setImageFromUpload($file);
                        } catch (\Throwable $e) {
                            Log::warning('Product image upload failed', ['error' => $e->getMessage()]);
                            $this->applyImageToProduct($product, $file);
                        }
                    } else {
                        $this->applyImageToProduct($product, $file);
                    }
                }

                $product->save();
            }

            $prodDate = Carbon::parse($validated['production_date']);
            $expiry = !empty($validated['expiration_date'])
                ? Carbon::parse($validated['expiration_date'])
                : $prodDate->copy()->addDays((int) ($product->shelf_life_days ?? 7));

            // === Sequential Batch #: honor user numeric if unique, else next ===
            $providedInt = $this->normalizeBatchInt($validated['batch_number'] ?? null);
            if ($providedInt !== null) {
                $candidate = (string) $providedInt;
                if ($this->batchExists($product->id, $candidate)) {
                    $providedInt = null;
                }
            }
            $batchInt = $providedInt ?? $this->nextBatchNumberInt($product);
            $batchNumber = (string) $batchInt;

            // infer qty
            $qty = $this->inferQuantity($validated);

            if (config('app.consume_materials', false)) {
                if (!$this->productHasRecipe($product)) {
                    return $this->respondNeedsRecipe($request, $product);
                }

                try {
                    DB::transaction(function () use ($product, $validated, $prodDate, $expiry, &$batchNumber, $qty) {
                        $this->consumeMaterials($product, (float) $qty);
                        $this->createBatchAndRecompute(
                            $product,
                            $validated + ['__inferred_qty' => $qty],
                            $prodDate,
                            $expiry,
                            $batchNumber
                        );
                    });
                } catch (\RuntimeException $re) {
                    return $this->respondError($request, ['materials' => $re->getMessage()]);
                }
            } else {
                $this->createBatchAndRecompute(
                    $product,
                    $validated + ['__inferred_qty' => $qty],
                    $prodDate,
                    $expiry,
                    $batchNumber
                );
            }

            if ($request->ajax() || $request->wantsJson()) {
                $freshProduct = $product->fresh();
                $this->attachCardMedia($freshProduct);

                $cardHtml = View::exists('production.partials.product-card')
                    ? view('production.partials.product-card', ['p' => $freshProduct])->render()
                    : view('production.partials.product-cards', ['products' => collect([$freshProduct])])->render();

                [$forecastedDemand, $actualInventory, $shortfall, $recommendedProduction] = $this->totalsSnapshot();

                return response()->json([
                    'ok' => true,
                    'message' => 'Production record added.',
                    'product_id' => $freshProduct->id,
                    'card_html' => $cardHtml,
                    'totals' => [
                        'forecastedDemand' => (float) $forecastedDemand,
                        'actualInventory' => (float) $actualInventory,
                        'shortfall' => (float) $shortfall,
                        'recommendedProduction' => (float) $recommendedProduction,
                    ],
                ]);
            }

            return redirect()->route('production.index')->with('success', 'Production record added.');
        } catch (\Throwable $e) {
            Log::error('Failed to save production', ['error' => $e->getMessage()]);
            $msg = config('app.debug')
                ? 'Server error: ' . $e->getMessage()
                : 'Server error while saving production.';

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => $msg], 500);
            }

            return back()->with('error', $msg)->withInput();
        }
    }

    /* =============================== CREATE (ORDERS PAGE) =============================== */

    public function storeOrder(Request $request)
    {
        $rules = [
            'parent_product_id' => ['nullable', 'integer', 'exists:products,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'product_name_snapshot' => ['nullable', 'string', 'max:255'],
            'type_label' => ['nullable', 'string', 'max:255'],
            'batch_number' => ['nullable', 'string', 'max:255'],
            'production_date' => ['required', 'date'],
            'expiration_date' => ['nullable', 'date', 'after_or_equal:production_date'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'produced_qty_kg' => ['nullable', 'numeric', 'min:0'],
            'unit_price_pack' => ['nullable', 'numeric', 'min:0'],
            'unit_price_bag' => ['nullable', 'numeric', 'min:0'],
            'available_pack' => ['nullable', 'integer', 'min:0'],
            'available_bag' => ['nullable', 'integer', 'min:0'],
            'remarks' => ['nullable', 'string', 'max:500'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'], // legacy

            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'dimensions:min_width=300,min_height=300'],
            'product_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'dimensions:min_width=300,min_height=300'],
        ];

        $validated = $request->validate($rules);

        $product = Product::findOrFail((int) $validated['product_id']);
        $parentProductId = (int) ($validated['parent_product_id'] ?? $product->parent_id ?? $product->id);

        // IMAGE FROM ADD ORDER MODAL:
        $file = $request->file('image') ?? $request->file('product_image');
        if ($file) {
            if (method_exists($product, 'setImageFromUpload')) {
                try {
                    $product->setImageFromUpload($file);
                } catch (\Throwable $e) {
                    Log::warning('Product image upload failed in storeOrder via setImageFromUpload', [
                        'product_id' => $product->id,
                        'error' => $e->getMessage(),
                    ]);
                    $this->applyImageToProduct($product, $file);
                }
            } else {
                $this->applyImageToProduct($product, $file);
            }
        }

        $prodDate = Carbon::parse($validated['production_date']);
        $expiry = !empty($validated['expiration_date'])
            ? Carbon::parse($validated['expiration_date'])
            : $prodDate->copy()->addDays((int) ($product->shelf_life_days ?? 7));

        // Sequential batch number: honor user numeric if unique, else next
        $providedInt = $this->normalizeBatchInt($validated['batch_number'] ?? null);
        if ($providedInt !== null) {
            $candidate = (string) $providedInt;
            if ($this->batchExists($product->id, $candidate)) {
                $providedInt = null;
            }
        }
        $batchInt = $providedInt ?? $this->nextBatchNumberInt($product);
        $batchNumber = (string) $batchInt;

        $typeLabel = trim((string) ($validated['type_label'] ?? ''));
        $snapshotName = trim((string) ($validated['product_name_snapshot'] ?? ''));
        if ($typeLabel !== '') {
            $snapshotName = $typeLabel;
        }
        if ($snapshotName === '') {
            $snapshotName = $product->category ?: $product->product_name;
        }

        $qty = $this->inferQuantity($validated);

        try {
            DB::transaction(function () use ($product, $parentProductId, $snapshotName, $validated, $prodDate, $expiry, &$batchNumber, $qty) {
                if (config('app.consume_materials', false)) {
                    if (!$this->productHasRecipe($product)) {
                        throw new \LogicException('__NEEDS_RECIPE__');
                    }
                    $this->consumeMaterials($product, (float) $qty);
                }

                $payload = [
                    'parent_product_id' => $parentProductId,
                    'product_id' => $product->id,
                    'product_name_snapshot' => $snapshotName,
                    'batch_number' => $batchNumber,
                    'forecasted_demand' => (float) ($product->forecasted_demand ?? 0),
                    'current_inventory' => (int) $qty,
                    'quantity' => (int) $qty,
                    'unit_price_pack' => (float) ($validated['unit_price_pack'] ?? 0),
                    'unit_price_bag' => (float) ($validated['unit_price_bag'] ?? 0),
                    'production_date' => $prodDate->toDateString(),
                    'expiration_date' => $expiry->toDateString(),
                ];

                if (Schema::hasColumn('productions', 'available_pack')) {
                    $payload['available_pack'] = (int) ($validated['available_pack'] ?? 0);
                }
                if (Schema::hasColumn('productions', 'available_bag')) {
                    $payload['available_bag'] = (int) ($validated['available_bag'] ?? 0);
                }
                if (Schema::hasColumn('productions', 'remarks')) {
                    $payload['remarks'] = (string) ($validated['remarks'] ?? '');
                }

                for ($attempt = 1; $attempt <= 2; $attempt++) {
                    try {
                        Production::create($payload);
                        break;
                    } catch (QueryException $e) {
                        if ($e->getCode() === '23000' && $attempt < 2) {
                            $batchNumber = $this->uniqueBatchNumber($product);
                            $payload['batch_number'] = $batchNumber;
                            continue;
                        }
                        throw $e;
                    }
                }
            });

            $this->recomputeProductBalance($product->id);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['ok' => true, 'message' => 'Order added.']);
            }

            return redirect()->route('production.orders', $parentProductId)->with('success', 'Order added.');
        } catch (\LogicException $lex) {
            if ($lex->getMessage() === '__NEEDS_RECIPE__') {
                return $this->respondNeedsRecipe($request, $product);
            }
            return $this->respondError($request, ['materials' => 'Recipe check failed.']);
        } catch (\RuntimeException $re) {
            return $this->respondError($request, ['materials' => $re->getMessage()]);
        } catch (\Throwable $e) {
            Log::error('Failed to save production order', ['error' => $e->getMessage()]);
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => 'Server error while saving order.'], 500);
            }
            return back()->with('error', 'Server error while saving order.')->withInput();
        }
    }

    /* ================================== READ/EDIT ================================== */

    public function show($id)
    {
        $product = Product::findOrFail($id);

        // ✅ AUTO-ARCHIVE expired batches for this parent product on page load
        // (expiration_date is today or already passed)
        $this->autoArchiveExpiredBatchesForParent((int) $id);

        $orders = Production::query()
            ->where(function ($q) use ($id) {
                $q->where('parent_product_id', $id)
                    ->orWhere(function ($q2) use ($id) {
                        $q2->whereNull('parent_product_id')
                            ->where('product_id', $id);
                    });
            })
            ->with(['product', 'parentProduct'])
            ->orderByDesc('production_date')->orderByDesc('id')
            ->get();

        $nextBatchInt = $this->nextBatchNumberInt($product);
        $nextBatchNumber = (string) $nextBatchInt;
        $nextBatchLabel = $this->formatBatchLabel($nextBatchInt);

        $defaultProdDate = now()->toDateString();
        $defaultExpiry = Carbon::parse($defaultProdDate)->addDays((int) ($product->shelf_life_days ?? 7))->toDateString();
        $defaultUnitPrice = $this->defaultUnitPriceFromSales($product);

        $allProducts = Product::orderBy('product_name')->get();
        $variantProducts = Product::where('parent_id', $product->id)->orderBy('product_name')->get();
        $consumeMaterials = (bool) config('app.consume_materials', false);
        $hasRecipe = method_exists($product, 'recipes') ? $product->recipes()->exists() : false;

        return view('production.orders', compact(
            'product',
            'orders',
            'nextBatchNumber',
            'nextBatchLabel',
            'defaultProdDate',
            'defaultExpiry',
            'defaultUnitPrice',
            'allProducts',
            'variantProducts',
            'consumeMaterials',
            'hasRecipe'
        ));
    }

    public function showOrders($id)
    {
        return $this->show($id);
    }

    public function edit($id)
    {
        $product = Production::findOrFail($id);
        return view('production.edit', compact('product'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'forecasted_demand' => ['nullable', 'numeric', 'min:0'],
            'current_inventory' => ['nullable', 'integer', 'min:0'],
            'unit_price_pack' => ['nullable', 'numeric', 'min:0'],
            'unit_price_bag' => ['nullable', 'numeric', 'min:0'],
            'available_pack' => ['nullable', 'integer', 'min:0'],
            'available_bag' => ['nullable', 'integer', 'min:0'],
            'production_date' => ['required', 'date'],
            'expiration_date' => ['nullable', 'date', 'after_or_equal:production_date'],
            'remarks' => ['nullable', 'string', 'max:500'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'], // legacy
        ]);

        $production = Production::findOrFail($id);

        $payload = [
            'forecasted_demand' => $validated['forecasted_demand'] ?? $production->forecasted_demand,
            'current_inventory' => array_key_exists('current_inventory', $validated)
                ? (int) $validated['current_inventory']
                : $production->current_inventory,
            'quantity' => array_key_exists('current_inventory', $validated)
                ? (int) $validated['current_inventory']
                : $production->quantity,
            'unit_price_pack' => $validated['unit_price_pack'] ?? $production->unit_price_pack,
            'unit_price_bag' => $validated['unit_price_bag'] ?? $production->unit_price_bag,
            'production_date' => $validated['production_date'],
            'expiration_date' => $validated['expiration_date'] ?? $production->expiration_date,
        ];

        if (Schema::hasColumn('productions', 'available_pack') && array_key_exists('available_pack', $validated)) {
            $payload['available_pack'] = (int) ($validated['available_pack'] ?? 0);
        }
        if (Schema::hasColumn('productions', 'available_bag') && array_key_exists('available_bag', $validated)) {
            $payload['available_bag'] = (int) ($validated['available_bag'] ?? 0);
        }
        if (Schema::hasColumn('productions', 'remarks') && array_key_exists('remarks', $validated)) {
            $payload['remarks'] = (string) ($validated['remarks'] ?? '');
        }

        $production->update($payload);
        $this->recomputeProductBalance((int) $production->product_id);

        return redirect()->route('production.index')->with('success', 'Production record updated.');
    }

    public function destroy(Production $production)
    {
        if (Sale::where('production_id', $production->id)->exists()) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Cannot delete this batch; it has linked sales.',
                ], 409);
            }

            return redirect()->route('production.index')
                ->with('error', 'Cannot delete this batch; it has linked sales.');
        }

        $productId = (int) $production->product_id;
        $production->delete();

        $this->recomputeProductBalance($productId);

        if (request()->ajax() || request()->wantsJson()) {
            $product = Product::find($productId);
            $cardHtml = null;

            if ($product) {
                $this->attachCardMedia($product);

                $cardHtml = View::exists('production.partials.product-card')
                    ? view('production.partials.product-card', ['p' => $product])->render()
                    : view('production.partials.product-cards', ['products' => collect([$product])])->render();
            }

            [$forecastedDemand, $actualInventory, $shortfall, $recommendedProduction] = $this->totalsSnapshot();

            return response()->json([
                'ok' => true,
                'message' => 'Production deleted.',
                'product_id' => $productId,
                'card_html' => $cardHtml,
                'totals' => [
                    'forecastedDemand' => (float) $forecastedDemand,
                    'actualInventory' => (float) $actualInventory,
                    'shortfall' => (float) $shortfall,
                    'recommendedProduction' => (float) $recommendedProduction,
                ],
            ]);
        }

        return redirect()->route('production.index')->with('success', 'Production deleted.');
    }

    public function destroyLatest(Product $product)
    {
        $latest = Production::where('product_id', $product->id)
            ->orderByDesc('production_date')
            ->orderByDesc('id')
            ->first();

        if (!$latest) {
            return response()->json(['ok' => false, 'message' => 'No batch to delete.'], 404);
        }

        if (Sale::where('production_id', $latest->id)->exists()) {
            return response()->json([
                'ok' => false,
                'message' => 'Cannot delete; batch has linked sales.',
            ], 409);
        }

        $latest->delete();

        $this->recomputeProductBalance((int) $product->id);

        $freshProduct = Product::find($product->id);
        $this->attachCardMedia($freshProduct);

        $cardHtml = View::exists('production.partials.product-card')
            ? view('production.partials.product-card', ['p' => $freshProduct])->render()
            : view('production.partials.product-cards', ['products' => collect([$freshProduct])])->render();

        [$forecastedDemand, $actualInventory, $shortfall, $recommendedProduction] = $this->totalsSnapshot();

        return response()->json([
            'ok' => true,
            'message' => 'Latest batch deleted.',
            'product_id' => $product->id,
            'card_html' => $cardHtml,
            'totals' => [
                'forecastedDemand' => (float) $forecastedDemand,
                'actualInventory' => (float) $actualInventory,
                'shortfall' => (float) $shortfall,
                'recommendedProduction' => (float) $recommendedProduction,
            ],
        ]);
    }

    /* =============================== ARCHIVE PAGES & ACTIONS =============================== */

    /**
     * Simple entry point if any old route still hits archivedIndex.
     * It just proxies to archived() so we only maintain one logic.
     */
    public function archivedIndex(Request $request)
    {
        return $this->archived($request);
    }

    /**
     * Main Archived listing page:
     * - Auto-purges records soft-deleted more than 30 days ago
     * - Supports search, sort, and source filters
     * - Adds `purge_at` (deleted_at + 30 days) for display.
     */
    public function archived(Request $request)
    {
        $q      = trim((string) $request->get('q', ''));
        $sort   = (string) $request->get('sort', 'deleted_at'); // deleted_at|batch|product|date|qty
        $source = $request->get('source');

        $now = Carbon::now();
        $purgedCount = 0;

        // ---------------------------------------------
        // AUTO-PURGE: Delete anything older than 30 days
        // ---------------------------------------------
        try {
            $purgeCutoff = $now->copy()->subDays(30);

            $purgeQuery = Production::onlyTrashed()
                ->where('deleted_at', '<=', $purgeCutoff);

            $purgedCount = (clone $purgeQuery)->count();
            if ($purgedCount > 0) {
                $purgeQuery->forceDelete();
            }
        } catch (\Throwable $e) {
            Log::warning('Production auto-purge failed', [
                'error' => $e->getMessage()
            ]);
        }

        // ---------------------------------------------
        // MAP sort values → real DB columns
        // ---------------------------------------------
        $sortMap = [
            'deleted_at' => ['deleted_at', 'desc'],
            'batch'      => ['batch_number', 'asc'],
            'product'    => ['product_id', 'asc'],
            'date'       => ['production_date', 'desc'],
            'qty'        => ['quantity', 'desc'],
        ];

        [$sortCol, $sortDir] = $sortMap[$sort] ?? ['deleted_at', 'desc'];

        // ---------------------------------------------
        // FETCH archived items (within 30-day window)
        // ---------------------------------------------
        $items = Production::onlyTrashed()
            ->where('deleted_at', '>', $now->copy()->subDays(30))

            // ----- SOURCE FILTER -----
            ->when($source, function ($qBuilder) use ($source) {
                if ($source === 'sales') {
                    $qBuilder->where('archived_reason', 'like', '%sale%');
                } elseif ($source === 'production') {
                    $qBuilder->where('archived_reason', 'like', '%production%');
                } elseif ($source === 'other') {
                    $qBuilder->whereNotNull('archived_reason')
                        ->where('archived_reason', 'not like', '%sale%')
                        ->where('archived_reason', 'not like', '%production%');
                }
            })

            // ----- SEARCH -----
            ->when($q !== '', function ($qry) use ($q) {
                $qry->where(function ($w) use ($q) {
                    $w->where('batch_number', 'like', "%{$q}%")
                        ->orWhereHas('product', function ($p) use ($q) {
                            $p->where('product_name', 'like', "%{$q}%")
                                ->orWhere('product_code', 'like', "%{$q}%")
                                ->orWhere('category', 'like', "%{$q}%")
                                ->orWhereHas('parent', function ($pp) use ($q) {
                                    $pp->where('product_name', 'like', "%{$q}%");
                                });
                        });
                });
            })

            // ----- RELATIONS -----
            ->with([
                'product' => fn($p) => $p->with('parent')
            ])

            // ----- SORTING -----
            ->orderBy($sortCol, $sortDir)

            // ----- PAGINATION -----
            ->paginate(15)

            // ----- ADD purge_at (deleted_at + 30 days) -----
            ->through(function ($p) {
                $p->purge_at = $p->deleted_at
                    ? Carbon::parse($p->deleted_at)->addDays(30)
                    : null;
                return $p;
            });

        // ---------------------------------------------
        // LOG FOR DEBUGGING (optional)
        // ---------------------------------------------
        Log::info('Archived Debug', [
            'sort' => $sort,
            'sort_col' => $sortCol,
            'direction' => $sortDir,
            'q' => $q,
            'source' => $source,
            'purgedCount' => $purgedCount,
            'items_returned' => $items->total(),
        ]);

        // ---------------------------------------------
        // RETURN TO VIEW (your archived.blade.php)
        // ---------------------------------------------
        return view('production.archived', compact(
            'items',
            'q',
            'sort',
            'source',
            'purgedCount'
        ));
    }

    /**
     * Archive a production record (soft delete with reason).
     * Route: POST /production/{id}/archive  -> name('production.archive')
     */
    public function archive($id, Request $request)
    {
        $production = Production::findOrFail((int) $id);

        if (Sale::where('production_id', $production->id)->exists()) {
            return $this->archiveJsonOrBack(
                $request,
                false,
                'Cannot archive this batch; it has linked sales.'
            );
        }

        // Set archive meta if columns exist
        if (Schema::hasColumn('productions', 'archived_at')) {
            $production->archived_at = now();
        }
        if (Schema::hasColumn('productions', 'archived_reason')) {
            $production->archived_reason = $request->input('reason', 'manual');
        }
        $production->save();

        $productId = (int) $production->product_id;
        $production->delete(); // soft delete

        $this->recomputeProductBalance($productId);

        return $this->archiveJsonOrBack($request, true, 'Production archived.');
    }

    public function restore($id, Request $request)
    {
        $p = Production::withTrashed()->findOrFail((int) $id);
        if (!$p->trashed()) {
            return $this->archiveJsonOrBack($request, true, 'Record is already active.');
        }

        $p->restore();

        $dirty = false;
        if (Schema::hasColumn('productions', 'archived_at')) {
            $p->archived_at = null;
            $dirty = true;
        }
        if (Schema::hasColumn('productions', 'archived_reason')) {
            $p->archived_reason = null;
            $dirty = true;
        }
        if ($dirty) {
            $p->save();
        }

        $this->recomputeProductBalance((int) $p->product_id);

        return $this->archiveJsonOrBack($request, true, 'Production restored.');
    }

    public function destroyForever($id, Request $request)
    {
        $p = Production::withTrashed()->findOrFail((int) $id);

        if (Sale::where('production_id', $p->id)->exists()) {
            return $this->archiveJsonOrBack(
                $request,
                false,
                'Cannot delete forever. This batch has linked sales.'
            );
        }

        $productId = (int) $p->product_id;
        $p->forceDelete();

        $this->recomputeProductBalance($productId);

        return $this->archiveJsonOrBack($request, true, 'Production permanently deleted.');
    }

    private function archiveJsonOrBack(Request $request, bool $ok, string $msg)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['ok' => $ok, 'message' => $msg]);
        }

        return back()->with($ok ? 'success' : 'error', $msg);
    }

    /* =============================== LIGHTWEIGHT APIS =============================== */

    public function getProductInfo($name): JsonResponse
    {
        $product = Product::where('product_name', $name)->latest('production_date')->first();
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        return response()->json([
            'forecasted_demand' => (float) $product->forecasted_demand,
            'current_inventory' => (float) ($product->quantity ?? 0),
            'unit_cost' => (float) ($product->unit_cost ?? 0),
            'shelf_life_days' => (int) ($product->shelf_life_days ?? 7),
            'default_price' => (float) ($product->default_price ?? 0),
        ]);
    }

    // Used by: /production/api/by-product/{product}
    public function apiByProduct(Product $product): JsonResponse
    {
        $cols = [
            'id',
            'batch_number',
            'quantity',
            'current_inventory',
            'production_date',
            'expiration_date',
            'unit_price_pack',
            'unit_price_bag',
            'product_name_snapshot',
        ];

        if (Schema::hasColumn('productions', 'available_pack')) {
            $cols[] = 'available_pack';
        }
        if (Schema::hasColumn('productions', 'available_bag')) {
            $cols[] = 'available_bag';
        }
        if (Schema::hasColumn('productions', 'remarks')) {
            $cols[] = 'remarks';
        }

        $batches = Production::where('product_id', $product->id)
            ->orderByDesc('production_date')
            ->orderByDesc('id')
            ->get($cols);

        $batches = $batches->map(function ($b) {
            $b->unit_price_pack = isset($b->unit_price_pack) ? (float) $b->unit_price_pack : null;
            $b->unit_price_bag = isset($b->unit_price_bag) ? (float) $b->unit_price_bag : null;
            $b->quantity = isset($b->quantity) ? (float) $b->quantity : 0.0;
            $b->current_inventory = isset($b->current_inventory) ? (float) $b->current_inventory : 0.0;

            if (property_exists($b, 'available_pack') && isset($b->available_pack)) {
                $b->available_pack = (int) $b->available_pack;
            }
            if (property_exists($b, 'available_bag') && isset($b->available_bag)) {
                $b->available_bag = (int) $b->available_bag;
            }

            return $b;
        });

        return response()->json($batches);
    }

    /* ================================= HELPERS ================================= */

    /**
     * ✅ NEW: Auto-archive expired production batches for a "parent" product context:
     * - expiry date is today or already passed (<= today)
     * - uses same soft-delete behavior so it appears in Archived page
     * - sets archived_at / archived_reason when those columns exist
     */
    private function autoArchiveExpiredBatchesForParent(int $parentId): int
    {
        $today = Carbon::today()->toDateString();

        $hasArchivedAt = Schema::hasColumn('productions', 'archived_at');
        $hasArchivedReason = Schema::hasColumn('productions', 'archived_reason');

        $expired = Production::query()
            ->where(function ($q) use ($parentId) {
                $q->where('parent_product_id', $parentId)
                    ->orWhere(function ($q2) use ($parentId) {
                        $q2->whereNull('parent_product_id')
                            ->where('product_id', $parentId);
                    });
            })
            ->whereNotNull('expiration_date')
            ->whereDate('expiration_date', '<=', $today)
            ->get();

        if ($expired->isEmpty()) {
            return 0;
        }

        DB::transaction(function () use ($expired, $hasArchivedAt, $hasArchivedReason) {
            foreach ($expired as $p) {
                if ($hasArchivedAt) {
                    $p->archived_at = now();
                }
                if ($hasArchivedReason) {
                    $p->archived_reason = $p->archived_reason ?: 'production expiry (auto)';
                }

                $p->save();
                $p->delete(); // soft delete -> goes to Archived page
            }
        });

        // Recompute balances for affected product_ids (could include variants)
        $expired->pluck('product_id')->unique()->each(function ($pid) {
            $this->recomputeProductBalance((int) $pid);
        });

        Log::info('Auto-archived expired batches', [
            'parent_product_id' => $parentId,
            'count' => $expired->count(),
            'today' => $today,
        ]);

        return $expired->count();
    }

    private function respondNeedsRecipe(Request $request, Product $product)
    {
        $msg = "No recipe set for {$product->product_name}. Add materials first.";
        $redirect = route('products.materials.index', $product->id);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'ok' => false,
                'message' => $msg,
                'needs_recipe' => true,
                'redirect' => $redirect,
            ], 422);
        }

        return redirect()->to($redirect)->with('error', $msg);
    }

    private function sortProducts($products, string $sort)
    {
        switch ($sort) {
            case 'expiry':
                return $products->sortBy(function ($p) {
                    $shelf = (int) ($p->shelf_life_days ?? 7);
                    $expiry = $p->expiration_date
                        ? Carbon::parse($p->expiration_date)
                        : ($p->production_date
                            ? Carbon::parse($p->production_date)->addDays($shelf)
                            : Carbon::now()->addYears(50));
                    return $expiry->timestamp;
                })->values();

            case 'name':
                return $products->sortBy(
                    fn($p) => mb_strtolower($p->product_name ?? '')
                )->values();

            case 'urgency':
            default:
                return $products->sortBy(function ($p) {
                    $qty = (float) ($p->quantity ?? 0);
                    $fc = (float) ($p->forecasted_demand ?? 0);
                    return $qty - $fc;
                })->values();
        }
    }

    private function uniqueBatchNumber(Product $product, ?string $preferred = null): string
    {
        $providedInt = $this->normalizeBatchInt($preferred);
        if ($providedInt !== null) {
            $candidate = (string) $providedInt;
            if (!$this->batchExists($product->id, $candidate)) {
                return $candidate;
            }
        }

        return (string) $this->nextBatchNumberInt($product);
    }

    private function batchExists(int $productId, string $batch): bool
    {
        return Production::where('product_id', $productId)
            ->where('batch_number', $batch)
            ->exists();
    }

    private function defaultUnitPriceFromSales(Product $product): float
    {
        $latest = Sale::where('product_id', $product->id)
            ->orderByDesc(DB::raw('COALESCE(date, created_at)'))
            ->select([DB::raw('COALESCE(unit_price, price) as p')])
            ->value('p');

        return (float) ($latest ?? $product->price ?? 0);
    }

    /**
     * Recalculate product balance and auto-update forecasted demand
     * based on recent sales.
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

        $autoForecast = $this->computeForecastForProduct($product);

        $product->quantity = $balance;
        $product->stock_status = $balance > 0 ? 'in_stock' : 'out_of_stock';
        $product->production_date = $latestProdDate;
        $product->forecasted_demand = $autoForecast;
        $product->save();
    }

    /**
     * Compute an automatic forecast for a product based on its recent sales.
     */
    private function computeForecastForProduct(Product $product): float
    {
        $windowDays = (int) (config('app.production_forecast_window_days', 30));
        $horizonDays = (int) (config('app.production_forecast_horizon_days', 14));
        $windowDays = $windowDays > 0 ? $windowDays : 30;
        $horizonDays = $horizonDays > 0 ? $horizonDays : 14;

        $today = Carbon::today();
        $from = $today->copy()->subDays($windowDays);

        $totalSold = Sale::where('product_id', $product->id)
            ->where(function ($q) use ($from, $today) {
                $q->whereBetween('date', [$from->toDateString(), $today->toDateString()])
                    ->orWhereBetween('created_at', [$from, $today->copy()->endOfDay()]);
            })
            ->select(DB::raw('COALESCE(SUM(quantity_kg), 0) + COALESCE(SUM(quantity), 0) as s'))
            ->value('s');

        $totalSold = (float) $totalSold;

        if ($totalSold <= 0) {
            return (float) ($product->forecasted_demand ?? 0);
        }

        $avgPerDay = $totalSold / max($windowDays, 1);

        return round($avgPerDay * $horizonDays, 3);
    }

    private function totalsSnapshot(): array
    {
        $products = Product::all();

        $forecastedDemand = (float) $products->sum('forecasted_demand');
        $actualInventory = (float) $products->sum('quantity');
        $shortfall = max($forecastedDemand - $actualInventory, 0.0);
        $recommendedProduction = $shortfall;

        return [$forecastedDemand, $actualInventory, $shortfall, $recommendedProduction];
    }

    private function respondError(Request $request, array $errors)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['ok' => false, 'errors' => $errors], 422);
        }

        return back()->withErrors($errors)->withInput();
    }

    private function filterProductColumns(array $attrs): array
    {
        $columns = Schema::getColumnListing('products');
        return array_intersect_key($attrs, array_flip($columns));
    }

    private function consumeMaterials(Product $product, float $batchUnits): void
    {
        if (!method_exists($product, 'recipes')) {
            Log::info('Product has no recipes() relation; skipping consumption', [
                'product_id' => $product->id,
            ]);
            return;
        }

        $rows = $product->recipes()->with('material')->get();
        if ($rows->isEmpty()) {
            Log::info('Skipping material consumption: no recipe for product', [
                'product_id' => $product->id,
                'product' => $product->product_name,
            ]);
            return;
        }

        $locked = [];

        // Check availability
        foreach ($rows as $r) {
            $materialId = $r->material_id ?? $r->ingredient_id ?? $r->material?->id;
            if (!$materialId) {
                continue;
            }

            $mat = $locked[$materialId] ??= Material::whereKey($materialId)
                ->lockForUpdate()
                ->first();

            if (!$mat) {
                continue;
            }

            $needed = (float) $r->qty * $batchUnits;
            if ($mat->quantity_kg < $needed) {
                throw new \RuntimeException(
                    "Insufficient stock for {$mat->material_name}. " .
                    "Need {$needed} {$mat->unit}, available {$mat->quantity_kg}."
                );
            }
        }

        // Deduct
        foreach ($rows as $r) {
            $materialId = $r->material_id ?? $r->ingredient_id ?? $r->material?->id;
            if (!$materialId || !isset($locked[$materialId])) {
                continue;
            }

            $mat = $locked[$materialId];
            $needed = (float) $r->qty * $batchUnits;

            $mat->quantity_kg = (float) $mat->quantity_kg - $needed;
            $mat->save();
        }
    }

    private function createBatchAndRecompute(
        Product $product,
        array $validated,
        Carbon $prodDate,
        Carbon $expiry,
        string $batchNumber
    ): void {
        $qty = isset($validated['current_inventory'])
            ? (int) $validated['current_inventory']
            : (isset($validated['quantity'])
                ? (int) $validated['quantity']
                : (int) ($validated['__inferred_qty'] ?? 0));

        $payload = [
            'product_id' => $product->id,
            'batch_number' => $batchNumber,
            'forecasted_demand' => (float) ($validated['forecasted_demand'] ?? ($product->forecasted_demand ?? 0)),
            'current_inventory' => $qty,
            'quantity' => $qty,
            'unit_price_pack' => (float) ($validated['unit_price_pack'] ?? 0),
            'unit_price_bag' => (float) ($validated['unit_price_bag'] ?? 0),
            'production_date' => $prodDate->toDateString(),
            'expiration_date' => $expiry->toDateString(),
        ];

        if (Schema::hasColumn('productions', 'available_pack')) {
            $payload['available_pack'] = (int) ($validated['available_pack'] ?? 0);
        }
        if (Schema::hasColumn('productions', 'available_bag')) {
            $payload['available_bag'] = (int) ($validated['available_bag'] ?? 0);
        }
        if (Schema::hasColumn('productions', 'remarks')) {
            $payload['remarks'] = (string) ($validated['remarks'] ?? '');
        }

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                Production::create($payload);
                break;
            } catch (QueryException $e) {
                if ($e->getCode() === '23000' && $attempt < 2) {
                    $batchNumber = $this->uniqueBatchNumber($product);
                    $payload['batch_number'] = $batchNumber;
                    continue;
                }
                throw $e;
            }
        }

        $this->recomputeProductBalance((int) $product->id);
    }

    private function enrichProductsForCards($products)
    {
        return $products->map(function ($p) {
            $this->attachCardMedia($p);
            return $p;
        });
    }

    /**
     * Attach media + expiry snapshot fields used by the dashboard
     * product cards (product-cards.blade.php).
     */
    private function attachCardMedia($p): void
    {
        if (!$p) {
            return;
        }

        // === Latest expiry snapshot for "Next Expiry" stat chip ===
        try {
            $latestExp = Production::where('product_id', $p->id)
                ->whereNotNull('expiration_date')
                ->orderByDesc('expiration_date')
                ->value('expiration_date');

            if ($latestExp) {
                $p->latest_expiration_date = $latestExp;
            }
        } catch (\Throwable $e) {
            Log::debug('attachCardMedia: could not compute latest_expiration_date', [
                'product_id' => $p->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }

        // Derive URL from legacy image_path if present
        $fromPath = null;

        try {
            $disk = $p->image_disk ?: config('filesystems.default');
            if (!empty($p->image_path) && Storage::disk($disk)->exists($p->image_path)) {
                $fromPath = Storage::disk($disk)->url($p->image_path);
            }
        } catch (\Throwable $e) {
            Log::debug('attachCardMedia: image_path lookup failed', [
                'product_id' => $p->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }

        $primary = $p->card_image_url
            ?? $p->image_thumb_url
            ?? $p->image_url
            ?? $fromPath
            ?? asset('images/default-product.png');

        $p->card_image_url = $primary;
        $p->card_image_srcset = $p->card_image_srcset ?? null;

        $p->image_thumb_url = $p->image_thumb_url ?? null;
        $p->image_medium_url = $p->image_medium_url ?? null;
        $p->image_original_url = $p->image_original_url
            ?? $p->image_url
            ?? $fromPath
            ?? $primary;
    }

    private function productHasRecipe(Product $product): bool
    {
        if (!method_exists($product, 'recipes')) {
            return false;
        }

        return $product->recipes()->exists();
    }

    /* =============================== ROUTE STUBS TO MATCH YOUR ROUTES =============================== */

    public function create()
    {
        if (View::exists('production.create')) {
            return view('production.create');
        }

        return redirect()->route('production.index')
            ->with('info', 'Create view not found. Using dashboard instead.');
    }

    public function pdf($id)
    {
        try {
            $production = Production::with(['product', 'parentProduct'])->findOrFail((int) $id);

            if (View::exists('production.pdf')) {
                if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('production.pdf', ['production' => $production]);
                    $filename = 'production-' . $production->id . '.pdf';
                    return $pdf->download($filename);
                }

                return view('production.pdf', ['production' => $production]);
            }

            return response()->json([
                'ok' => true,
                'message' => 'PDF view not found; returning JSON payload.',
                'data' => $production,
            ]);
        } catch (\Throwable $e) {
            Log::error('PDF generation failed', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'ok' => false,
                'message' => 'PDF generation not available on this environment.',
            ], 501);
        }
    }

    public function quickAddPayload(Product $product): JsonResponse
    {
        $price = (float) ($product->price ?? $product->default_price ?? 0);

        $latestBatch = Production::where('product_id', $product->id)
            ->orderByDesc('production_date')->orderByDesc('id')
            ->first();

        $productionDate = $latestBatch?->production_date
            ? Carbon::parse($latestBatch->production_date)->toDateString()
            : null;

        $expirationDate = $latestBatch?->expiration_date
            ? Carbon::parse($latestBatch->expiration_date)->toDateString()
            : ($productionDate
                ? Carbon::parse($productionDate)->addDays((int) ($product->shelf_life_days ?? 7))->toDateString()
                : null);

        $nextInt = $this->nextBatchNumberInt($product);

        return response()->json([
            'id' => $product->id,
            'name' => $product->product_name,
            'price' => $price,
            'production_id' => $latestBatch?->id,
            'production_date' => $productionDate,
            'expiration_date' => $expirationDate,
            'next_batch_number' => $nextInt,
            'batch_label' => $this->formatBatchLabel($nextInt),
        ]);
    }

    /* =============== NEW: central qty inference =============== */

    private function inferQuantity(array $data): int
    {
        if (isset($data['current_inventory'])) {
            return (int) max(0, (int) $data['current_inventory']);
        }
        if (isset($data['quantity'])) {
            return (int) max(0, (int) $data['quantity']);
        }
        if (isset($data['produced_qty_kg'])) {
            return (int) max(0, (float) $data['produced_qty_kg']);
        }

        $packs = (int) ($data['available_pack'] ?? 0);
        $bags = (int) ($data['available_bag'] ?? 0);

        return max(0, $packs + $bags);
    }

    /* =============================== BATCH HELPERS (NEW) =============================== */

    private function nextBatchNumberInt(Product $product): int
    {
        $nums = Production::where('product_id', $product->id)
            ->pluck('batch_number')
            ->map(function ($v) {
                if ($v === null) {
                    return 0;
                }
                $v = (string) $v;

                if (ctype_digit($v)) {
                    return (int) $v;
                }

                if (preg_match('/(\d+)\s*$/', $v, $m)) {
                    return (int) $m[1];
                }

                return 0;
            });

        $max = $nums->max() ?? 0;
        return max(0, (int) $max) + 1;
    }

    private function normalizeBatchInt(?string $raw): ?int
    {
        if ($raw === null) {
            return null;
        }

        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        if (ctype_digit($raw)) {
            return (int) $raw;
        }

        if (preg_match('/(\d+)/', $raw, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    private function formatBatchLabel(int $n): string
    {
        return 'BATCH #' . $n;
    }

    /* =============================== IMAGE PROCESSOR (UPDATED) =============================== */

    private function applyImageToProduct(Product $product, \Illuminate\Http\UploadedFile $file): void
    {
        Log::info('Starting applyImageToProduct()', [
            'product_id' => $product->id,
            'original_filename' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size_kb' => round($file->getSize() / 1024, 2),
        ]);

        try {
            if (!class_exists(Image::class)) {
                throw new \RuntimeException('Intervention Image not installed/configured');
            }

            $disk = 'public';

            Log::info('Intervention Image detected, processing begins', [
                'product_id' => $product->id,
                'disk' => $disk,
            ]);

            $uuid = (string) Str::uuid();
            $base = "products/{$product->id}/{$uuid}";

            Log::info('Generated base image path + UUID', [
                'product_id' => $product->id,
                'uuid' => $uuid,
                'base_path' => $base,
            ]);

            $img = Image::read($file->getRealPath())->orient();
            Log::info('Image loaded + oriented successfully', [
                'product_id' => $product->id,
            ]);

            $master = (clone $img)->scaleDown(1600, 1600);
            Log::info('Master image created (1600px max)', [
                'product_id' => $product->id,
                'width' => $master->width(),
                'height' => $master->height(),
            ]);

            $w1200 = (clone $master)->scaleDown(1200, 1200);
            $w800 = (clone $master)->scaleDown(800, 800);
            $w400 = (clone $master)->scaleDown(400, 400);

            Log::info('Generated responsive image sizes', [
                'product_id' => $product->id,
                'sizes' => [
                    '1200' => [$w1200->width(), $w1200->height()],
                    '800' => [$w800->width(), $w800->height()],
                    '400' => [$w400->width(), $w400->height()],
                ],
            ]);

            $path1200 = "{$base}-1200.webp";
            $path800 = "{$base}-800.webp";
            $path400 = "{$base}-400.webp";

            Log::info('Saving images to storage...', [
                'product_id' => $product->id,
                'paths' => [
                    '1200' => $path1200,
                    '800' => $path800,
                    '400' => $path400,
                ],
            ]);

            Storage::disk($disk)->put($path1200, (string) $w1200->toWebp(80));
            Storage::disk($disk)->put($path800, (string) $w800->toWebp(80));
            Storage::disk($disk)->put($path400, (string) $w400->toWebp(80));

            Log::info('Images saved successfully', [
                'product_id' => $product->id,
            ]);

            $url1200 = Storage::disk($disk)->url($path1200);
            $url800 = Storage::disk($disk)->url($path800);
            $url400 = Storage::disk($disk)->url($path400);

            $srcset = "{$url400} 400w, {$url800} 800w, {$url1200} 1200w";

            Log::info('Updating product record with image paths', [
                'product_id' => $product->id,
                'url' => $url1200,
            ]);

            $product->image_disk = $disk;
            $product->image_path = $path1200;
            $product->image_medium_path = $path800;
            $product->image_thumb_path = $path400;
            $product->image_url = $url1200;
            $product->card_image_url = $url800;
            $product->card_image_srcset = $srcset;

            $product->save();

            Log::info('applyImageToProduct completed successfully', [
                'product_id' => $product->id,
            ]);

        } catch (\Throwable $e) {
            Log::warning('applyImageToProduct failed, using simple store()', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);

            try {
                $path = $file->store('products', 'public');
                $url = Storage::disk('public')->url($path);

                Log::info('Fallback upload used successfully', [
                    'product_id' => $product->id,
                    'path' => $path,
                    'url' => $url,
                ]);

                $product->image_disk = 'public';
                $product->image_path = $path;
                $product->image_medium_path = null;
                $product->image_thumb_path = null;
                $product->image_url = $url;
                $product->card_image_url = $url;
                $product->card_image_srcset = null;
                $product->save();

            } catch (\Throwable $e2) {
                Log::error('Fallback store() for product image failed', [
                    'product_id' => $product->id,
                    'error' => $e2->getMessage(),
                ]);
            }
        }
    }
}
