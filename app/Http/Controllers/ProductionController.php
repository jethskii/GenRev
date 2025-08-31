<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use App\Models\Product;
use App\Models\Production;
use App\Models\Sale;
use App\Models\Material;

class ProductionController extends Controller
{
    /* ============================= INDEX / FILTER ============================= */
    public function index(Request $request)
    {
        $selectedCategory = $request->string('category')->toString() ?: null;
        $sort             = (string) $request->get('sort', 'urgency');

        $products = Product::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = trim($request->get('search'));
                $q->where('product_name', 'like', "%{$s}%");
            })
            ->when($selectedCategory, fn ($q) => $q->where('category', $selectedCategory))
            ->orderByDesc('production_date')
            ->get();

        $products = $this->enrichProductsForCards($products);
        $products = $this->sortProducts($products, $sort);

        [$forecastedDemand, $actualInventory, $shortfall, $recommendedProduction] = $this->totalsSnapshot();

        $categories       = Product::whereNotNull('category')->distinct()->orderBy('category')->pluck('category');
        $allProducts      = Product::orderBy('product_name')->get();
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
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->get('category')))
            ->when($request->filled('search'),   fn ($q) => $q->where('product_name', 'like', '%' . $request->get('search') . '%'))
            ->orderByDesc('production_date')
            ->get();

        $products = $this->enrichProductsForCards($products);
        $products = $this->sortProducts($products, $sort);

        return response()->json([
            'html' => view('production.partials.product-cards', compact('products'))->render(),
        ]);
    }

    /* =============================== CREATE (DASHBOARD) =============================== */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id'        => ['nullable','integer','exists:products,id'],
            'product_name'      => ['nullable','string','max:255'],
            'batch_number'      => ['nullable','string','max:255'],
            'forecasted_demand' => ['nullable','numeric','min:0'],
            'current_inventory' => ['required','numeric','min:0.001'],
            'unit_cost'         => ['nullable','numeric','min:0'],
            'production_date'   => ['required','date'],
            'expiration_date'   => ['nullable','date','after_or_equal:production_date'],
            'category'          => ['nullable','string','max:120'],
            'image'             => ['nullable','image','mimes:jpg,jpeg,png,webp','max:5120'],
        ]);

        try {
            // Resolve or create product
            if (empty($validated['product_id'])) {
                $name = isset($validated['product_name']) ? ucfirst(strtolower(trim($validated['product_name']))) : null;
                if (!$name) return $this->respondError($request, ['product_name' => 'Please select a product or enter a new name.']);

                $attrs = $this->filterProductColumns([
                    'forecasted_demand' => (float)($validated['forecasted_demand'] ?? 0),
                    'unit_cost'         => (float)($validated['unit_cost'] ?? 0),
                    'production_date'   => $validated['production_date'],
                    'stock_status'      => 'in_stock',
                    'category'          => $validated['category'] ?? null,
                    'status'            => 'active',
                    'unit'              => 'kg',
                ]);

                $product = Product::firstOrCreate(['product_name' => $name], $attrs);

                if ($request->hasFile('image') && method_exists($product, 'setImageFromUpload')) {
                    try { $product->setImageFromUpload($request->file('image')); }
                    catch (\Throwable $e) { Log::warning('Product image upload failed', ['error' => $e->getMessage()]); }
                }
            } else {
                $product = Product::findOrFail((int)$validated['product_id']);
                if (array_key_exists('forecasted_demand', $validated)) $product->forecasted_demand = (float)$validated['forecasted_demand'];
                if (array_key_exists('unit_cost', $validated))         $product->unit_cost         = (float)$validated['unit_cost'];
                if (array_key_exists('category', $validated))          $product->category          = $validated['category'];
                $product->production_date = $validated['production_date'];
                $product->stock_status    = 'in_stock';

                if ($request->hasFile('image') && method_exists($product, 'setImageFromUpload')) {
                    try { $product->setImageFromUpload($request->file('image')); }
                    catch (\Throwable $e) { Log::warning('Product image upload failed', ['error' => $e->getMessage()]); }
                }

                $product->save();
            }

            $prodDate = Carbon::parse($validated['production_date']);
            $expiry   = !empty($validated['expiration_date'])
                ? Carbon::parse($validated['expiration_date'])
                : $prodDate->copy()->addDays((int)($product->shelf_life_days ?? 7));

            $batchNumber = !empty($validated['batch_number'])
                ? $validated['batch_number']
                : $this->nextBatchNumber($product);

            if (config('app.consume_materials', false)) {
                DB::transaction(function () use ($product, $validated, $prodDate, $expiry, $batchNumber) {
                    $this->consumeMaterials($product, (float)$validated['current_inventory']);
                    $this->createBatchAndRecompute($product, $validated, $prodDate, $expiry, $batchNumber);
                });
            } else {
                $this->createBatchAndRecompute($product, $validated, $prodDate, $expiry, $batchNumber);
            }

            if ($request->ajax() || $request->wantsJson()) {
                $freshProduct = $product->fresh();
                $this->attachCardMedia($freshProduct);

                $cardHtml = View::exists('production.partials.product-card')
                    ? view('production.partials.product-card', ['p' => $freshProduct])->render()
                    : view('production.partials.product-cards', ['products' => collect([$freshProduct])])->render();

                [$forecastedDemand, $actualInventory, $shortfall, $recommendedProduction] = $this->totalsSnapshot();

                return response()->json([
                    'ok'        => true,
                    'message'   => 'Production record added.',
                    'product_id'=> $freshProduct->id,
                    'card_html' => $cardHtml,
                    'totals'    => [
                        'forecastedDemand'      => (float)$forecastedDemand,
                        'actualInventory'       => (float)$actualInventory,
                        'shortfall'             => (float)$shortfall,
                        'recommendedProduction' => (float)$recommendedProduction,
                    ],
                ]);
            }

            return redirect()->route('production.index')->with('success', 'Production record added.');
        } catch (\Throwable $e) {
            Log::error('Failed to save production', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $msg = config('app.debug')
                ? 'Server error: '.$e->getMessage()
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
        $validated = $request->validate([
            'product_id'       => ['required','integer','exists:products,id'],
            'batch_number'     => ['nullable','string','max:255'],
            'production_date'  => ['required','date'],
            'expiration_date'  => ['nullable','date','after_or_equal:production_date'],
            'quantity'         => ['required','numeric','min:0.001'],
            'unit_cost'        => ['nullable','numeric','min:0'],
        ]);

        $product  = Product::findOrFail((int)$validated['product_id']);
        $prodDate = Carbon::parse($validated['production_date']);
        $expiry   = !empty($validated['expiration_date'])
            ? Carbon::parse($validated['expiration_date'])
            : $prodDate->copy()->addDays((int)($product->shelf_life_days ?? 7));

        $batchNumber = !empty($validated['batch_number'])
            ? $validated['batch_number']
            : $this->nextBatchNumber($product);

        try {
            DB::transaction(function () use ($product, $validated, $prodDate, $expiry, $batchNumber) {
                if (config('app.consume_materials', false)) {
                    $this->consumeMaterials($product, (float)$validated['quantity']);
                }

                Production::create([
                    'product_id'        => $product->id,
                    'batch_number'      => $batchNumber,
                    'forecasted_demand' => (float)($product->forecasted_demand ?? 0),
                    'current_inventory' => (float)$validated['quantity'],
                    'quantity'          => (float)$validated['quantity'],
                    'unit_cost'         => (float)($validated['unit_cost'] ?? ($product->unit_cost ?? 0)),
                    'production_date'   => $prodDate->toDateString(),
                    'expiration_date'   => $expiry->toDateString(),
                ]);
            });

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['ok' => true, 'message' => 'Order added.']);
            }

            return redirect()->route('production.orders', $product->id)->with('success', 'Order added.');
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
        $orders  = Production::where('product_id', $id)->orderByDesc('production_date')->orderByDesc('id')->get();

        $nextBatchNumber  = $this->nextBatchNumber($product);
        $defaultProdDate  = now()->toDateString();
        $defaultExpiry    = Carbon::parse($defaultProdDate)->addDays((int)($product->shelf_life_days ?? 7))->toDateString();
        $defaultUnitPrice = $this->defaultUnitPriceFromSales($product);

        $allProducts      = Product::orderBy('product_name')->get();
        $consumeMaterials = (bool) config('app.consume_materials', false);
        $hasRecipe        = method_exists($product, 'recipes') ? $product->recipes()->exists() : false;

        return view('production.orders', compact(
            'product','orders','nextBatchNumber','defaultProdDate','defaultExpiry','defaultUnitPrice','allProducts','consumeMaterials','hasRecipe'
        ));
    }

    public function showOrders($id) { return $this->show($id); }

    public function edit($id)
    {
        $production = Production::findOrFail($id);
        return view('production.edit', compact('production'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'forecasted_demand'   => ['required','numeric','min:0'],
            'current_inventory'   => ['required','numeric','min:0'],
            'unit_cost'           => ['required','numeric','min:0'],
            'production_date'     => ['required','date'],
        ]);

        $production = Production::findOrFail($id);
        $production->update(array_merge($validated, [
            'quantity' => (float)$validated['current_inventory']
        ]));

        $this->recomputeProductBalance($production->product_id);

        return redirect()->route('production.index')->with('success', 'Production record updated.');
    }

    public function destroy(Production $production)
    {
        if (Sale::where('production_id', $production->id)->exists()) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['ok' => false, 'message' => 'Cannot delete this batch; it has linked sales.'], 409);
            }
            return redirect()->route('production.index')->with('error', 'Cannot delete this batch; it has linked sales.');
        }

        $productId = (int)$production->product_id;
        $production->delete();

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
                'ok'        => true,
                'message'   => 'Production deleted.',
                'product_id'=> $productId,
                'card_html' => $cardHtml,
                'totals'    => [
                    'forecastedDemand'      => (float)$forecastedDemand,
                    'actualInventory'       => (float)$actualInventory,
                    'shortfall'             => (float)$shortfall,
                    'recommendedProduction' => (float)$recommendedProduction,
                ]
            ]);
        }

        return redirect()->route('production.index')->with('success', 'Production deleted.');
    }

    /** NEW: Delete the latest batch for a product (no linked sales allowed) */
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
            return response()->json(['ok' => false, 'message' => 'Cannot delete; batch has linked sales.'], 409);
        }

        $latest->delete();

        $freshProduct = Product::find($product->id);
        $this->attachCardMedia($freshProduct);

        $cardHtml = View::exists('production.partials.product-card')
            ? view('production.partials.product-card', ['p' => $freshProduct])->render()
            : view('production.partials.product-cards', ['products' => collect([$freshProduct])])->render();

        [$forecastedDemand, $actualInventory, $shortfall, $recommendedProduction] = $this->totalsSnapshot();

        return response()->json([
            'ok'        => true,
            'message'   => 'Latest batch deleted.',
            'product_id'=> $product->id,
            'card_html' => $cardHtml,
            'totals'    => [
                'forecastedDemand'      => (float)$forecastedDemand,
                'actualInventory'       => (float)$actualInventory,
                'shortfall'             => (float)$shortfall,
                'recommendedProduction' => (float)$recommendedProduction,
            ]
        ]);
    }

    /* =============================== LIGHTWEIGHT APIS =============================== */
    public function getProductInfo($name): JsonResponse
    {
        $product = Product::where('product_name', $name)->latest('production_date')->first();
        if (!$product) return response()->json(['error' => 'Product not found'], 404);

        return response()->json([
            'forecasted_demand' => (float) $product->forecasted_demand,
            'current_inventory' => (float) ($product->quantity ?? 0),
            'unit_cost'         => (float) $product->unit_cost,
            'shelf_life_days'   => (int)  ($product->shelf_life_days ?? 7),
            'default_price'     => (float) ($product->default_price ?? 0),
        ]);
    }

    public function apiByProduct(Product $product): JsonResponse
    {
        $batches = Production::where('product_id', $product->id)
            ->orderByDesc('production_date')->orderByDesc('id')
            ->get(['id','batch_number','quantity','current_inventory','production_date','expiration_date']);

        return response()->json($batches);
    }

    public function quickAddPayload(Product $product): JsonResponse
    {
        $price = (float)($product->price ?? 0);

        $latestBatch = Production::where('product_id', $product->id)
            ->orderByDesc('production_date')->orderByDesc('id')->first();

        $productionDate = $latestBatch?->production_date
            ? Carbon::parse($latestBatch->production_date)->toDateString()
            : null;

        $expirationDate = $latestBatch?->expiration_date
            ? Carbon::parse($latestBatch->expiration_date)->toDateString()
            : ($productionDate
                ? Carbon::parse($productionDate)->addDays((int)($product->shelf_life_days ?? 7))->toDateString()
                : null
            );

        return response()->json([
            'id'               => $product->id,
            'name'             => $product->product_name,
            'price'            => $price,
            'production_id'    => $latestBatch?->id,
            'production_date'  => $productionDate,
            'expiration_date'  => $expirationDate,
        ]);
    }

    /* ================================= HELPERS ================================= */
    private function sortProducts($products, string $sort)
    {
        switch ($sort) {
            case 'expiry':
                return $products->sortBy(function ($p) {
                    $shelf  = (int)($p->shelf_life_days ?? 7);
                    $expiry = $p->expiration_date
                        ? Carbon::parse($p->expiration_date)
                        : ($p->production_date
                            ? Carbon::parse($p->production_date)->addDays($shelf)
                            : Carbon::now()->addYears(50));
                    return $expiry->timestamp;
                })->values();

            case 'name':
                return $products->sortBy(fn ($p) => mb_strtolower($p->product_name ?? ''))->values();

            case 'urgency':
            default:
                return $products->sortBy(function ($p) {
                    $qty = (float)($p->quantity ?? 0);
                    $fc  = (float)($p->forecasted_demand ?? 0);
                    return $qty - $fc;
                })->values();
        }
    }

    private function nextBatchNumber(Product $product): string
    {
        $prefix = $product->product_code ? strtoupper($product->product_code) : 'B';
        $last   = Production::where('product_id', $product->id)->orderByDesc('id')->value('batch_number');
        $n = 0;
        if ($last && preg_match('/(\d+)\s*$/', $last, $m)) $n = (int)$m[1];
        return $prefix . '-' . str_pad((string)($n + 1), 4, '0', STR_PAD_LEFT);
    }

    private function defaultUnitPriceFromSales(Product $product): float
    {
        $latest = Sale::where('product_id', $product->id)
            ->orderByDesc(DB::raw('COALESCE(date, created_at)'))
            ->value('price');

        return (float) ($latest ?? $product->price ?? 0);
    }

    private function recomputeProductBalance(int $productId): void
    {
        $produced = (float) Production::where('product_id', $productId)->sum('quantity');
        $sold     = (float) Sale::where('product_id', $productId)->sum('quantity');
        $balance  = max(0.0, $produced - $sold);
        $latestProdDate = Production::where('product_id', $productId)->max('production_date');

        Product::where('id', $productId)->update([
            'quantity'        => $balance,
            'stock_status'    => $balance > 0 ? 'in_stock' : 'out_of_stock',
            'production_date' => $latestProdDate,
        ]);
    }

    private function totalsSnapshot(): array
    {
        $products = Product::all();
        $forecastedDemand      = (float) $products->sum('forecasted_demand');
        $actualInventory       = (float) $products->sum('quantity');
        $shortfall             = max($forecastedDemand - $actualInventory, 0.0);
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
            Log::info('Product has no recipes() relation; skipping consumption', ['product_id' => $product->id]);
            return;
        }

        $rows = $product->recipes()->with('material')->get();
        if ($rows->isEmpty()) {
            Log::info('Skipping material consumption: no recipe for product', [
                'product_id'  => $product->id,
                'product'     => $product->product_name,
            ]);
            return;
        }

        $locked = [];
        foreach ($rows as $r) {
            $materialId = $r->material_id ?? $r->ingredient_id ?? $r->material?->id;
            if (!$materialId) continue;
            $mat = $locked[$materialId] ??= Material::whereKey($materialId)->lockForUpdate()->first();
            if (!$mat) continue;

            $needed = (float)$r->qty * $batchUnits;
            if ($mat->quantity_kg < $needed) {
                throw new \RuntimeException("Insufficient stock for {$mat->material_name}. Need {$needed} {$mat->unit}, available {$mat->quantity_kg}.");
            }
        }

        foreach ($rows as $r) {
            $materialId = $r->material_id ?? $r->ingredient_id ?? $r->material?->id;
            if (!$materialId || !isset($locked[$materialId])) continue;
            $mat = $locked[$materialId];
            $needed = (float)$r->qty * $batchUnits;
            $mat->quantity_kg = (float)$mat->quantity_kg - $needed;
            $mat->save();
        }
    }

    private function createBatchAndRecompute(Product $product, array $validated, Carbon $prodDate, Carbon $expiry, string $batchNumber): void
    {
        $qty = isset($validated['current_inventory'])
            ? (float)$validated['current_inventory']
            : (float)($validated['quantity'] ?? 0);

        Production::create([
            'product_id'        => $product->id,
            'batch_number'      => $batchNumber,
            'forecasted_demand' => (float)($validated['forecasted_demand'] ?? ($product->forecasted_demand ?? 0)),
            'current_inventory' => $qty,
            'quantity'          => $qty,
            'unit_cost'         => (float)($validated['unit_cost'] ?? ($product->unit_cost ?? 0)),
            'production_date'   => $prodDate->toDateString(),
            'expiration_date'   => $expiry->toDateString(),
        ]);

        $this->recomputeProductBalance($product->id);
    }

    private function enrichProductsForCards($products)
    {
        return $products->map(function ($p) { $this->attachCardMedia($p); return $p; });
    }

    private function attachCardMedia($p): void
    {
        $orig = $p->image_url ?? asset('images/default-product.png');
        $p->card_image_url     = $orig;
        $p->image_thumb_url    = null;
        $p->image_medium_url   = null;
        $p->image_original_url = $orig;
        $p->card_image_srcset  = null;
    }
}
