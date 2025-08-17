<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Product;
use App\Models\Production;
use App\Models\Sale;
use App\Models\Material;

class ProductionController extends Controller
{
    /** Production dashboard (cards + product list) */
    public function index(Request $request)
    {
        $selectedCategory = $request->category;
        $sort = (string) $request->get('sort', 'urgency'); // urgency | expiry | name

        $products = Product::when($request->search, function ($q) use ($request) {
                $q->where('product_name', 'like', '%' . $request->search . '%');
            })
            ->when($selectedCategory, fn($q) => $q->where('category', $selectedCategory))
            ->orderByDesc('production_date')
            ->get();

        $forecastedDemand      = (float) $products->sum('forecasted_demand');
        $actualInventory       = (float) $products->sum('quantity');
        $shortfall             = max($forecastedDemand - $actualInventory, 0.0);
        $recommendedProduction = $shortfall;

        $products   = $this->sortProducts($products, $sort);
        $categories = Product::whereNotNull('category')->distinct()->pluck('category')->sort()->values();
        $allProducts= Product::orderBy('product_name')->get();

        return view('production.index', compact(
            'products','forecastedDemand','actualInventory','shortfall','recommendedProduction',
            'categories','selectedCategory','allProducts','sort'
        ));
    }

    /** AJAX: filter product cards */
    public function filter(Request $request): JsonResponse
    {
        $sort = (string) $request->get('sort', 'urgency');

        $products = Product::when($request->category, fn($q) => $q->where('category', $request->category))
            ->when($request->search, fn($q) => $q->where('product_name', 'like', '%' . $request->search . '%'))
            ->orderByDesc('production_date')
            ->get();

        $products = $this->sortProducts($products, $sort);

        return response()->json([
            'html' => view('production.partials.product-cards', compact('products'))->render()
        ]);
    }

    /** Manual stock-in baseline (no linked sale) */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_name'        => ['required','string','max:255'],
            'batch_number'        => ['required','string','max:255'],
            'forecasted_demand'   => ['required','numeric','min:0'],
            'current_inventory'   => ['required','numeric','min:0'],
            'unit_cost'           => ['required','numeric','min:0'],
            'production_date'     => ['required','date'],
        ]);

        $name    = ucfirst(strtolower(trim($validated['product_name'])));
        $product = Product::firstOrNew(['product_name' => $name]);

        $product->forecasted_demand = (float)$validated['forecasted_demand'];
        $product->unit_cost         = (float)$validated['unit_cost'];
        $product->production_date   = $validated['production_date'];
        $product->stock_status      = 'in_stock';
        $product->save();

        Production::create([
            'product_id'        => $product->id,
            'batch_number'      => $validated['batch_number'],
            'forecasted_demand' => (float)$validated['forecasted_demand'],
            'current_inventory' => (float)$validated['current_inventory'],
            'unit_cost'         => (float)$validated['unit_cost'],
            'production_date'   => $validated['production_date'],
            'quantity'          => (float)$validated['current_inventory'],
        ]);

        $this->recomputeProductBalance($product->id);

        return redirect()->route('production.index')->with('success', 'Production record added.');
    }

    /** Product page with batches */
    public function show($id)
    {
        $product = Product::findOrFail($id);
        $orders  = Production::where('product_id', $id)
            ->orderByDesc('production_date')
            ->orderByDesc('id')
            ->get();

        $nextBatchNumber  = $this->nextBatchNumber($product);
        $defaultProdDate  = now()->toDateString();
        $defaultExpiry    = Carbon::parse($defaultProdDate)
                                ->addDays((int)($product->shelf_life_days ?? 7))
                                ->toDateString();
        $defaultUnitPrice = $this->defaultUnitPriceFromSales($product);

        $allProducts = Product::orderBy('product_name')->get();

        return view('production.orders', compact(
            'product','orders','nextBatchNumber','defaultProdDate','defaultExpiry','defaultUnitPrice','allProducts'
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
        $production->update(array_merge(
            $validated,
            ['quantity' => (float)$validated['current_inventory']]
        ));

        $this->recomputeProductBalance($production->product_id);

        return redirect()->route('production.index')->with('success', 'Production record updated.');
    }

    /** Block delete if batch has sales; keep product balance in sync */
    public function destroy(Production $production)
    {
        if (Sale::where('production_id', $production->id)->exists()) {
            return redirect()->route('production.index')
                ->with('error', 'Cannot delete this batch; it has linked sales.');
        }

        $productId = $production->product_id;
        $production->delete();
        $this->recomputeProductBalance($productId);

        return redirect()->route('production.index')->with('success', 'Production deleted.');
    }

    /** Modal autofill for a product name */
    public function getProductInfo($name): JsonResponse
    {
        $product = Product::where('product_name', $name)
            ->latest('production_date')
            ->first();

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        return response()->json([
            'forecasted_demand' => (float) $product->forecasted_demand,
            'current_inventory' => (float) ($product->quantity ?? 0),
            'unit_cost'         => (float) $product->unit_cost,
            'shelf_life_days'   => (int)  ($product->shelf_life_days ?? 7),
            'default_price'     => (float) ($product->default_price ?? 0),
        ]);
    }

    /** Batches for Add-Sale modal */
    public function apiByProduct(Product $product): JsonResponse
    {
        $batches = Production::where('product_id', $product->id)
            ->orderByDesc('production_date')
            ->orderByDesc('id')
            ->get(['id','batch_number','quantity','current_inventory','production_date','expiration_date']);

        return response()->json($batches);
    }

    /**
     * Add Order: create batch + linked sale with atomic invoice number.
     * Also consumes materials based on the product's recipe.
     */
    public function storeOrder(Product $product, Request $request)
    {
        $data = $request->validate([
            // Production (IN)
            'forecasted_demand'   => ['nullable','numeric','min:0'],
            'produced_qty_kg'     => ['required','numeric','min:0.001'],
            'unit_cost'           => ['nullable','numeric','min:0'], // ← allow null; default from recipe
            'production_date'     => ['required','date'],
            'expiration_date'     => ['nullable','date'],
            // Sale (OUT)
            'order_date'          => ['nullable','date'],
            'order_quantity_kg'   => ['required','numeric','min:0.001'],
            'unit_price'          => ['nullable','numeric','min:0'],
            // Optional
            'customer_name'       => ['nullable','string','max:120'],
            'notes'               => ['nullable','string','max:500'],
        ]);

        $prodDate = Carbon::parse($data['production_date']);
        $expiry   = !empty($data['expiration_date'])
            ? Carbon::parse($data['expiration_date'])
            : $prodDate->copy()->addDays((int)($product->shelf_life_days ?? 7));

        $saleDate  = !empty($data['order_date'])
            ? Carbon::parse($data['order_date'])->toDateString()
            : now()->toDateString();

        // Default unit_price for the sale
        $unitPrice = isset($data['unit_price']) && $data['unit_price'] !== null
            ? (float)$data['unit_price']
            : $this->defaultUnitPriceFromSales($product);

        // Default production unit_cost from recipe if not provided
        if (!isset($data['unit_cost']) || $data['unit_cost'] === null || $data['unit_cost'] === '') {
            $data['unit_cost'] = $product->unit_material_cost; // accessor in Product model
        }

        if ($data['order_quantity_kg'] > $data['produced_qty_kg']) {
            return back()->withErrors(['order_quantity_kg' => 'Order qty cannot exceed produced qty for this batch.'])->withInput();
        }

        return DB::transaction(function () use ($product, $data, $expiry, $prodDate, $saleDate, $unitPrice) {
            // 0) Consume materials for this batch (throws if insufficient)
            $this->consumeMaterials($product, (float)$data['produced_qty_kg']);

            // 1) Create production batch
            $production = Production::create([
                'product_id'        => $product->id,
                'batch_number'      => $this->nextBatchNumber($product),
                'forecasted_demand' => (float)($data['forecasted_demand'] ?? 0),
                'current_inventory' => (float)$data['produced_qty_kg'],
                'quantity'          => (float)$data['produced_qty_kg'],
                'unit_cost'         => (float)$data['unit_cost'], // per unit cost (from recipe or user)
                'production_date'   => $prodDate->toDateString(),
                'expiration_date'   => $expiry->toDateString(),
            ]);

            // 2) Create Sale with atomic invoice number
            $qty   = (float)$data['order_quantity_kg'];
            $total = round($qty * (float)$unitPrice, 2);
            $invoice = $this->nextInvoiceNumber();

            try {
                Sale::create([
                    'invoice_number' => $invoice,
                    'product_id'     => $product->id,
                    'production_id'  => $production->id,
                    'product'        => $product->product_name,
                    'date'           => $saleDate,
                    'quantity'       => $qty,
                    'price'          => (float)$unitPrice,
                    'total'          => $total,
                    'status'         => 'Pending',
                ]);
            } catch (\Throwable $ex) {
                Log::error('Failed creating Sale for production order', [
                    'product_id'    => $product->id,
                    'production_id' => $production->id,
                    'invoice'       => $invoice,
                    'error'         => $ex->getMessage(),
                ]);
                throw $ex;
            }

            // 3) Refresh product balance
            $this->recomputeProductBalance($product->id);

            return redirect()
                ->route('production.show', $product->id)
                ->with('success', 'Order added, materials consumed, sale recorded, and inventory updated.');
        });
    }

    /* ---------------- Helpers ---------------- */

    private function sortProducts($products, string $sort)
    {
        switch ($sort) {
            case 'expiry':
                return $products->sortBy(function ($p) {
                    $shelf = (int)($p->shelf_life_days ?? 7);
                    $expiry = $p->expiration_date
                        ? Carbon::parse($p->expiration_date)
                        : ($p->production_date
                            ? Carbon::parse($p->production_date)->addDays($shelf)
                            : Carbon::now()->addYears(50));
                    return $expiry->timestamp;
                })->values();
            case 'name':
                return $products->sortBy(fn($p) => mb_strtolower($p->product_name ?? ''))->values();
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
        $last = Production::where('product_id', $product->id)
            ->orderByDesc('id')->value('batch_number');

        $n = 0;
        if ($last && preg_match('/(\d+)\s*$/', $last, $m)) {
            $n = (int) $m[1];
        }
        return 'B-' . str_pad((string)($n + 1), 4, '0', STR_PAD_LEFT);
    }

    private function defaultUnitPriceFromSales(Product $product): float
    {
        $latest = Sale::where('product_id', $product->id)
            ->orderByDesc(DB::raw('COALESCE(date, created_at)'))
            ->value('price');

        return (float) ($latest ?? $product->unit_cost ?? $product->price ?? 0);
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

    /** Atomic daily invoice number (shared with Sales), with fallback if table missing. */
    protected function nextInvoiceNumber(): string
    {
        $todayDate = now()->toDateString();
        $ymd       = now()->format('Ymd');

        try {
            return DB::transaction(function () use ($todayDate, $ymd) {
                $row = DB::table('invoice_sequences')
                    ->where('date_key', $todayDate)
                    ->lockForUpdate()
                    ->first();

                if (!$row) {
                    DB::table('invoice_sequences')->insert([
                        'date_key'   => $todayDate,
                        'last_seq'   => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $seq = 1;
                } else {
                    $seq = (int)$row->last_seq + 1;
                    DB::table('invoice_sequences')
                        ->where('date_key', $todayDate)
                        ->update(['last_seq' => $seq, 'updated_at' => now()]);
                }

                return 'INV-' . $ymd . '-' . str_pad((string)$seq, 3, '0', STR_PAD_LEFT);
            });
        } catch (\Throwable $e) {
            Log::warning('invoice_sequences unavailable; using MAX()-based fallback', ['error' => $e->getMessage()]);
            $prefix = 'INV-' . $ymd . '-';
            $max = Sale::where('invoice_number', 'like', $prefix.'%')->max('invoice_number');
            $seq = $max ? ((int) substr($max, strlen($prefix)) + 1) : 1;
            return $prefix . str_pad((string)$seq, 3, '0', STR_PAD_LEFT);
        }
    }

    /** Quick-add product for the modal */
    public function quickStoreProduct(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_name'    => ['required','string','max:255','unique:products,product_name'],
            'category'        => ['nullable','string','max:120'],
            'unit_cost'       => ['nullable','numeric','min:0'],
            'shelf_life_days' => ['nullable','integer','min:0'],
        ]);

        $p = Product::create([
            'product_name'    => trim($data['product_name']),
            'category'        => $data['category'] ?? null,
            'unit_cost'       => $data['unit_cost'] ?? 0,
            'shelf_life_days' => $data['shelf_life_days'] ?? 7,
            'stock_status'    => 'in_stock',
            'quantity'        => 0,
        ]);

        return response()->json([
            'id'              => $p->id,
            'product_name'    => $p->product_name,
            'category'        => $p->category,
            'unit_cost'       => (float) $p->unit_cost,
            'shelf_life_days' => (int) ($p->shelf_life_days ?? 7),
        ], 201);
    }

    /**
     * Consume materials according to the product's recipe for the specified batch size.
     * Throws an exception on insufficient stock (transaction will roll back).
     */
    private function consumeMaterials(Product $product, float $batchUnits): void
    {
        // Load recipe with ingredient materials
        $rows = $product->recipes()->with('ingredient')->get();

        if ($rows->isEmpty()) {
            throw new \RuntimeException("No recipe set for {$product->product_name}. Add materials first.");
        }

        // 1) Validate stock under row locks
        foreach ($rows as $r) {
            /** @var Material $mat */
            $mat = Material::whereKey($r->ingredient_id)->lockForUpdate()->first();
            $needed = (float)$r->qty * $batchUnits; // qty defined per ONE unit of product

            if ($mat->quantity_kg < $needed) {
                throw new \RuntimeException(
                    "Insufficient stock for {$mat->material_name}. Need {$needed} {$mat->unit}, available {$mat->quantity_kg}."
                );
            }
        }

        // 2) Deduct stock
        foreach ($rows as $r) {
            /** @var Material $mat */
            $mat = Material::whereKey($r->ingredient_id)->lockForUpdate()->first();
            $needed = (float)$r->qty * $batchUnits;
            $mat->quantity_kg = (float)$mat->quantity_kg - $needed;
            $mat->save();
        }
    }
}
