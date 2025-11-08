<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Production;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;   // column checks
use Illuminate\Support\Facades\View;     // render card html
use Illuminate\Support\Str;              // debug UUIDs

class SalesController extends Controller
{
    /** List sales + feed Add-Sale modal + dashboard KPIs & charts */
    public function index()
    {
        [$dateExprForWhere, $ymExpr, $orderExpr] = $this->dateExpressions();

        $sales = Sale::with([
                'productRef:id,product_name,shelf_life_days',
                'production:id,product_id,batch_number,quantity,current_inventory,production_date,expiration_date,unit_price_pack,unit_price_bag,product_name_snapshot'
            ])
            ->orderByDesc(DB::raw($orderExpr))
            ->orderByDesc('id')
            ->get();

        $products = Product::select('id','product_name','unit_cost','shelf_life_days')
            ->orderBy('product_name')
            ->get()
            ->map(function ($p) {
                $p->name  = $p->product_name;
                $p->price = $p->unit_cost ?? 0;
                return $p;
            });

        $statusOptions    = ['Pending','Completed','Cancelled','Paid'];
        $unitTypeOptions  = ['kg','pack','bag']; // include kg
        $nextInvoice      = $this->peekNextInvoiceNumber();

        // ----- Dashboard data -----
        [$chartMonths, $chartTotals] = $this->getMonthlyRevenueSeries(12);

        $annualRevenue  = $this->sumRevenueBetween(
            Carbon::now()->startOfYear()->toDateString(),
            Carbon::now()->endOfYear()->toDateString()
        );

        $monthlyRevenue = $this->sumRevenueBetween(
            Carbon::now()->startOfMonth()->toDateString(),
            Carbon::now()->endOfMonth()->toDateString()
        );

        $orderCount = Sale::count();

        [$donutLabels, $donutValues] = $this->getTopProductsRevenue(90);

        if (empty($donutLabels) || array_sum($donutValues) <= 0) {
            $donutLabels = ['No Data'];
            $donutValues = [0];
        }
        if (empty($chartMonths) || empty($chartTotals)) {
            $chartMonths = ['Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec','Jan'];
            $chartTotals = array_fill(0, count($chartMonths), 0);
        }

        return view('sales.index', compact(
            'sales','nextInvoice','products','statusOptions','unitTypeOptions',
            'chartMonths','chartTotals','annualRevenue','monthlyRevenue','orderCount',
            'donutLabels','donutValues'
        ));
    }

    /* ========================== Product-level Orders ========================== */

    public function byProduct(Product $product, Request $request)
    {
        [$sales, $filters] = $this->filteredSalesQueryForProduct($product->id, $request);

        return view('sales.by-product', [
            'product'         => $product,
            'sales'           => $sales,
            'filters'         => $filters,
            'statusOptions'   => ['Pending','Completed','Cancelled','Paid'],
            'unitTypeOptions' => ['kg','pack','bag'],
        ]);
    }

    public function byProductTable(Product $product, Request $request)
    {
        [$sales, $filters] = $this->filteredSalesQueryForProduct($product->id, $request);

        $html = view('sales.partials.orders-table', [
            'sales'   => $sales,
            'product' => $product,
            'filters' => $filters,
        ])->render();

        return response()->json(['ok' => true, 'html' => $html]);
    }

    protected function filteredSalesQueryForProduct(int $productId, Request $request): array
    {
        [$dateExpr] = [$this->dateExpressions()[0]];

        $q = Sale::with([
                'production:id,product_id,batch_number,production_date,expiration_date,unit_price_pack,unit_price_bag'
            ])
            ->where('product_id', $productId);

        $filters = [
            'status'         => $request->string('status')->toString() ?: null,
            'unit_type'      => $request->string('unit_type')->toString() ?: null, // 'kg' | 'pack' | 'bag'
            'batch_number'   => $request->string('batch_number')->toString() ?: null,
            'has_production' => $request->filled('has_production') ? $request->boolean('has_production') : null,
            'date_from'      => $request->string('date_from')->toString() ?: null,
            'date_to'        => $request->string('date_to')->toString() ?: null,
            'q'              => $request->string('q')->toString() ?: null,
        ];

        if ($filters['status']) {
            $q->where('status', $filters['status']);
        }

        $unitTypeColumn = $this->unitTypeColumn();
        if ($filters['unit_type'] && $unitTypeColumn) {
            $q->where($unitTypeColumn, $filters['unit_type']);
        }

        if ($filters['batch_number']) {
            $q->whereHas('production', function ($sub) use ($filters) {
                $sub->where('batch_number', 'like', '%' . $filters['batch_number'] . '%');
            });
        }

        if ($filters['has_production'] !== null) {
            $filters['has_production']
                ? $q->whereNotNull('production_id')
                : $q->whereNull('production_id');
        }

        if ($filters['date_from']) {
            $q->whereRaw("$dateExpr >= ?", [Carbon::parse($filters['date_from'])->toDateString()]);
        }
        if ($filters['date_to']) {
            $q->whereRaw("$dateExpr <= ?", [Carbon::parse($filters['date_to'])->toDateString()]);
        }

        if ($filters['q']) {
            $q->where(function ($sub) use ($filters) {
                foreach (['invoice_number','order_number','customer_name'] as $col) {
                    if (Schema::hasColumn('sales', $col)) {
                        $sub->orWhere($col, 'like', '%' . $filters['q'] . '%');
                    }
                }
            });
        }

        $sales = $q->orderByDesc('id')->get();

        return [$sales, $filters];
    }

    /* ---------------- Invoice number helpers ---------------- */

    protected function nextInvoiceNumber()
    {
        $todayDate = now()->toDateString();
        $ymd       = now()->format('Ymd');

        try {
            return DB::transaction(function () use ($todayDate, $ymd) {
                $row = DB::table('invoice_sequences')->where('date_key', $todayDate)->lockForUpdate()->first();

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
                    DB::table('invoice_sequences')->where('date_key', $todayDate)->update([
                        'last_seq' => $seq,
                        'updated_at' => now(),
                    ]);
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

    protected function peekNextInvoiceNumber()
    {
        $todayDate = now()->toDateString();
        $ymd       = now()->format('Ymd');

        try {
            $row  = DB::table('invoice_sequences')->where('date_key', $todayDate)->first();
            $next = ($row ? (int)$row->last_seq : 0) + 1;

            if (!$row) {
                $prefix = 'INV-' . $ymd . '-';
                $max = Sale::where('invoice_number', 'like', $prefix.'%')->max('invoice_number');
                if ($max) $next = ((int) substr($max, strlen($prefix))) + 1;
            }

            return 'INV-' . $ymd . '-' . str_pad((string)$next, 3, '0', STR_PAD_LEFT);
        } catch (\Throwable $e) {
            $prefix = 'INV-' . $ymd . '-';
            $max = Sale::where('invoice_number', 'like', $prefix.'%')->max('invoice_number');
            $next = $max ? ((int) substr($max, strlen($prefix))) + 1 : 1;
            return $prefix . str_pad((string)$next, 3, '0', STR_PAD_LEFT);
        }
    }

    /* --------------------------------- CRUD --------------------------------- */

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id'      => ['required','integer','exists:products,id'],
            'production_id'   => ['nullable','integer','exists:productions,id'],
            'unit_type'       => ['nullable','in:kg,pack,bag'], // allow kg
            'type_label'      => ['nullable','string','max:255'], // NEW
            'date'            => ['nullable','date'],
            'order_date'      => ['nullable','date'],
            'quantity'        => ['required','numeric','min:0.001'],
            'price'           => ['nullable','numeric','min:0'],
            'status'          => ['nullable','string','in:Pending,Completed,Cancelled,Paid'],
            'product'         => ['sometimes','nullable','string','max:150'],
            'production_date' => ['nullable','date'],
            'expiration_date' => ['nullable','date','after_or_equal:production_date'],
            'notes'           => ['nullable','string','max:2000'],
            'customer_name'   => ['nullable','string','max:255'],
        ]);

        $inputDate = $validated['date'] ?? $validated['order_date'] ?? $request->input('order_date');
        if (!$inputDate) {
            return $this->respondValidationError($request, ['date' => 'Please provide a sale date.']);
        }
        $validated['date'] = Carbon::parse($inputDate)->toDateString();

        $resolvedProductionId = $validated['production_id'] ?? null;
        if (!empty($resolvedProductionId)) {
            $batch = Production::select('id','product_id')->findOrFail($resolvedProductionId);
            if ((int)$batch->product_id !== (int)$validated['product_id']) {
                return $this->respondValidationError($request, ['production_id' => 'Selected batch does not belong to the chosen product.']);
            }
        }

        $product     = Product::select('id','product_name','shelf_life_days')->findOrFail($validated['product_id']);
        $displayName = $validated['product'] ?? $product->product_name;
        $invoice     = $this->nextInvoiceNumber();
        $qty         = (float) $validated['quantity'];
        $unitType    = $validated['unit_type'] ?? null;

        // Soft UX guards
        if ($qty > 5000) session()->flash('info', 'Heads up: quantity above 5000 kg. Please double-check.');
        if (isset($validated['price']) && (float)$validated['price'] === 0.0) {
            session()->flash('info', 'Unit price is zero. If this is intentional, you can ignore this note.');
        }

        // Resolve batch if not provided
        $orderDateStr = $validated['date'];
        if (empty($resolvedProductionId)) {
            $resolved = $this->resolveProductionByProductAndDate((int)$product->id, $orderDateStr);
            $resolvedProductionId = $resolved ? $resolved->id : null;
        }
        $batch = $resolvedProductionId ? Production::find($resolvedProductionId) : null;

        // Compute unit price
        $unit  = $this->determineUnitPrice($product, $batch, $unitType, $validated['price'] ?? null);
        $total = round($qty * $unit, 2);
        $status = $validated['status'] ?? 'Completed';

        // Final type label
        $typeLabel = trim((string)($validated['type_label'] ?? ''));
        if ($typeLabel === '' && $batch) $typeLabel = (string)($batch->product_name_snapshot ?? '');
        if ($typeLabel === '') $typeLabel = null;

        $debugUuid = (string) Str::uuid();
        Log::info("[sales.store] START {$debugUuid}", ['request' => $request->all()]);

        $createdSale = null;

        try {
            DB::transaction(function () use ($validated, $displayName, $invoice, $qty, $unit, $total, $status, $product, $resolvedProductionId, $unitType, $typeLabel, $debugUuid, &$createdSale) {
                $payload = [
                    'product_id'    => (int) $validated['product_id'],
                    'production_id' => $resolvedProductionId,
                    'status'        => $status,
                ];

                $map = [
                    'order_number'    => $invoice,
                    'invoice_number'  => $invoice,
                    'order_date'      => $validated['date'],
                    'date'            => $validated['date'],
                    'product'         => $displayName,
                    'type_label'      => $typeLabel,
                    'quantity_kg'     => $qty,
                    'quantity'        => $qty,
                    'unit_price'      => $unit,
                    'price'           => $unit,
                    'total_price'     => $total,
                    'total'           => $total,
                    $this->unitTypeColumn() => $unitType,
                    'customer_name'   => $validated['customer_name'] ?? null,
                    'notes'           => $validated['notes'] ?? null,
                    'production_date' => $validated['production_date'] ?? null,
                    'expiration_date' => $validated['expiration_date'] ?? null,
                ];

                foreach ($map as $col => $val) {
                    if ($col && Schema::hasColumn('sales', $col)) {
                        $payload[$col] = $val;
                    }
                }

                Log::info("[sales.store] PAYLOAD {$debugUuid}", $payload);
                $createdSale = Sale::create($payload);
                Log::info("[sales.store] CREATED {$debugUuid}", ['id' => $createdSale->id]);

                $this->recomputeProductBalance((int)$product->id);
            });

            Log::info("[sales.store] COMMIT {$debugUuid}");

            // Optional audit trail
            $rawChanges = $request->input('change_log');
            if ($createdSale && $rawChanges && class_exists(\App\Models\SaleChange::class)) {
                try {
                    \App\Models\SaleChange::create([
                        'sale_id'      => $createdSale->id,
                        'user_id'      => optional(auth()->user())->id,
                        'changes_json' => $rawChanges,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('[sales.store] SaleChange save failed', ['error' => $e->getMessage()]);
                }
            }

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['ok' => true, 'message' => 'Sale saved.']);
            }
            return redirect()->route('sales')->with('success', 'Sale recorded.');
        } catch (\Throwable $e) {
            Log::error("[sales.store] FAIL {$debugUuid}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function edit(Sale $sale)
    {
        $sale->load([
            'productRef:id,product_name,shelf_life_days,unit_cost',
            'production:id,product_id,batch_number,production_date,expiration_date,unit_price_pack,unit_price_bag,product_name_snapshot',
        ]);

        $products = Product::orderBy('product_name')->get(['id','product_name','unit_cost','shelf_life_days']);

        $batches = Production::where('product_id', $sale->product_id)
            ->orderByDesc('production_date')->orderByDesc('id')
            ->get(['id','batch_number','production_date','expiration_date','unit_price_pack','unit_price_bag','product_name_snapshot']);

        $productionDate = $sale->production_date ?: ($sale->production->production_date ?? null);
        $expirationDate = $sale->expiration_date ?: ($sale->production->expiration_date ?? null);

        if (!$expirationDate && $productionDate && $sale->productRef && (int)$sale->productRef->shelf_life_days > 0) {
            $expirationDate = Carbon::parse($productionDate)
                ->addDays((int)$sale->productRef->shelf_life_days)
                ->toDateString();
        }

        $statusOptions   = ['Pending','Completed','Cancelled','Paid'];
        $unitTypeOptions = ['kg','pack','bag'];

        return view('sales.edit', compact(
            'sale','products','batches','productionDate','expirationDate','statusOptions','unitTypeOptions'
        ));
    }

    public function update(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'product_id'      => ['required','integer','exists:products,id'],
            'production_id'   => ['nullable','integer','exists:productions,id'],
            'unit_type'       => ['nullable','in:kg,pack,bag'],
            'type_label'      => ['nullable','string','max:255'],
            'date'            => ['nullable','date'],
            'order_date'      => ['nullable','date'],
            'quantity'        => ['required','numeric','min:0.001'],
            'price'           => ['nullable','numeric','min:0'],
            'status'          => ['nullable','string','in:Pending,Completed,Cancelled,Paid'],
            'product'         => ['sometimes','nullable','string','max:150'],
            'production_date' => ['nullable','date'],
            'expiration_date' => ['nullable','date','after_or_equal:production_date'],
            'notes'           => ['nullable','string','max:2000'],
            'customer_name'   => ['nullable','string','max:255'],
        ]);

        $inputDate = $validated['date'] ?? $validated['order_date'] ?? $request->input('order_date');
        if (!$inputDate) {
            return $this->respondValidationError($request, ['date' => 'Please provide a sale date.']);
        }
        $validated['date'] = Carbon::parse($inputDate)->toDateString();

        $resolvedProductionId = $validated['production_id'] ?? null;
        if (!empty($resolvedProductionId)) {
            $batch = Production::select('id','product_id')->findOrFail($resolvedProductionId);
            if ((int)$batch->product_id !== (int)$validated['product_id']) {
                return $this->respondValidationError($request, ['production_id' => 'Selected batch does not belong to the chosen product.']);
            }
        }

        $oldProductId = (int)$sale->product_id;
        $product      = Product::select('id','product_name','shelf_life_days')->findOrFail($validated['product_id']);
        $displayName  = $validated['product'] ?? $product->product_name;
        $qty          = (float) $validated['quantity'];
        $unitType     = $validated['unit_type'] ?? $this->readUnitTypeFromSale($sale);

        if ($qty > 5000) session()->flash('info', 'Heads up: quantity above 5000 kg. Please double-check.');
        if (isset($validated['price']) && (float)$validated['price'] === 0.0) {
            session()->flash('info', 'Unit price is zero. If this is intentional, you can ignore this note.');
        }

        $orderDateStr = $validated['date'];
        if (empty($resolvedProductionId)) {
            $resolved = $this->resolveProductionByProductAndDate((int)$product->id, $orderDateStr);
            $resolvedProductionId = $resolved ? $resolved->id : null;
        }
        $batch = $resolvedProductionId ? Production::find($resolvedProductionId) : null;

        $unit  = $this->determineUnitPrice($product, $batch, $unitType, $validated['price'] ?? null, $fallbackCurrent = $this->inferCurrentUnitPrice($sale));
        $total = round($qty * $unit, 2);
        $status = $validated['status'] ?? ($sale->status ?: 'Completed');

        $typeLabel = trim((string)($validated['type_label'] ?? ''));
        if ($typeLabel === '' && $batch) $typeLabel = (string)($batch->product_name_snapshot ?? '');
        if ($typeLabel === '') $typeLabel = null;

        $debugUuid = (string) Str::uuid();
        Log::info("[sales.update] START {$debugUuid}", ['request' => $request->all(), 'sale_id' => $sale->id]);

        try {
            DB::transaction(function () use ($sale, $validated, $displayName, $qty, $unit, $total, $status, $resolvedProductionId, $unitType, $typeLabel, $debugUuid) {
                $payload = [
                    'product_id'    => (int) $validated['product_id'],
                    'production_id' => $resolvedProductionId,
                    'status'        => $status,
                ];

                $map = [
                    'order_date'      => $validated['date'],
                    'date'            => $validated['date'],
                    'product'         => $displayName,
                    'type_label'      => $typeLabel,
                    'quantity_kg'     => $qty,
                    'quantity'        => $qty,
                    'unit_price'      => $unit,
                    'price'           => $unit,
                    'total_price'     => $total,
                    'total'           => $total,
                    $this->unitTypeColumn() => $unitType,
                    'customer_name'   => $validated['customer_name'] ?? null,
                    'notes'           => $validated['notes'] ?? null,
                    'production_date' => $validated['production_date'] ?? null,
                    'expiration_date' => $validated['expiration_date'] ?? null,
                ];

                foreach ($map as $col => $val) {
                    if ($col && Schema::hasColumn('sales', $col)) {
                        $payload[$col] = $val;
                    }
                }

                Log::info("[sales.update] PAYLOAD {$debugUuid}", $payload);
                $sale->update($payload);
            });

            $this->recomputeProductBalance($oldProductId);
            $this->recomputeProductBalance((int)$product->id);

            Log::info("[sales.update] COMMIT {$debugUuid}");

            // Optional audit trail
            $rawChanges = $request->input('change_log');
            if ($rawChanges && class_exists(\App\Models\SaleChange::class)) {
                try {
                    \App\Models\SaleChange::create([
                        'sale_id'      => $sale->id,
                        'user_id'      => optional(auth()->user())->id,
                        'changes_json' => $rawChanges,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('[sales.update] SaleChange save failed', ['error' => $e->getMessage()]);
                }
            }

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['ok' => true, 'message' => 'Sale updated.']);
            }
            return redirect()->route('sales')->with('success', 'Sale updated.');
        } catch (\Throwable $e) {
            Log::error("[sales.update] FAIL {$debugUuid}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Soft-delete the sale AND also archive the linked Production batch (soft delete),
     * then redirect straight to the Production Archive page so both are visible there.
     */
    public function destroy(Sale $sale)
    {
        $productId     = (int) $sale->product_id;
        $productionId  = (int) ($sale->production_id ?? 0);

        DB::transaction(function () use ($sale, $productionId) {
            // Soft delete sale
            $sale->delete();

            // Also archive the linked production batch (soft delete), if any
            if ($productionId > 0) {
                $batch = Production::withTrashed()->find($productionId);
                if ($batch && is_null($batch->deleted_at)) {
                    $batch->delete();
                }
            }
        });

        $this->recomputeProductBalance($productId);

        // Redirect to Production Archive so user sees both archived entities together
        return redirect()->route('production.archived')->with('success', 'Sale archived and related batch moved to Production archive.');
    }

    /* ============================== Sales Archive (Trash) ============================== */

    /** List soft-deleted sales */
    public function archivedIndex(Request $request)
    {
        $sales = Sale::onlyTrashed()
            ->with(['productRef:id,product_name', 'production' => function($q) {
                $q->withTrashed()->select('id','product_id','batch_number','deleted_at');
            }])
            ->orderByDesc('deleted_at')
            ->paginate(30);

        return view('sales.archived', compact('sales'));
    }

    /** Restore a soft-deleted sale */
    public function restore($id)
    {
        $sale = Sale::onlyTrashed()->findOrFail($id);
        $sale->restore();

        $this->recomputeProductBalance((int)$sale->product_id);

        return redirect()->route('sales.archived')->with('success', 'Sale restored.');
    }

    /** Permanently delete a soft-deleted sale */
    public function forceDelete($id)
    {
        $sale = Sale::onlyTrashed()->findOrFail($id);
        $productId = (int) $sale->product_id;

        $sale->forceDelete();

        $this->recomputeProductBalance($productId);

        return redirect()->route('sales.archived')->with('success', 'Sale permanently deleted.');
    }

    /** Bulk restore */
    public function restoreMany(Request $request)
    {
        $ids = array_filter((array) $request->input('ids', []), 'is_numeric');
        if (empty($ids)) return back()->with('info', 'No items selected.');

        $productIds = [];
        DB::transaction(function () use ($ids, &$productIds) {
            $toRestore = Sale::onlyTrashed()->whereIn('id', $ids)->get();
            foreach ($toRestore as $s) {
                $productIds[] = (int)$s->product_id;
                $s->restore();
            }
        });

        foreach (array_unique($productIds) as $pid) $this->recomputeProductBalance($pid);

        return back()->with('success', 'Selected sales restored.');
    }

    /** Bulk force delete */
    public function forceDeleteMany(Request $request)
    {
        $ids = array_filter((array) $request->input('ids', []), 'is_numeric');
        if (empty($ids)) return back()->with('info', 'No items selected.');

        $productIds = [];
        DB::transaction(function () use ($ids, &$productIds) {
            $toDelete = Sale::onlyTrashed()->whereIn('id', $ids)->get();
            foreach ($toDelete as $s) {
                $productIds[] = (int)$s->product_id;
                $s->forceDelete();
            }
        });

        foreach (array_unique($productIds) as $pid) $this->recomputeProductBalance($pid);

        return back()->with('success', 'Selected sales permanently deleted.');
    }

    /* ============================== Lightweight APIs / Utility ============================== */

    public function available(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required','integer','exists:products,id'],
        ]);

        $product = Product::findOrFail($validated['product_id']);

        return response()->json([
            'available' => (float) ($product->available_stock_kg ?? 0),
            'price'     => (float) ($product->unit_cost ?? 0),
        ]);
    }

    public function quickStore(Request $request)
    {
        $validated = $request->validate([
            'product_id'    => ['required','integer','exists:products,id'],
            'quantity'      => ['nullable','numeric','min:0.001'],
            'price'         => ['nullable','numeric','min:0'],
            'unit_type'     => ['nullable','in:kg,pack,bag'],
            'type_label'    => ['nullable','string','max:255'],
            'production_id' => ['nullable','integer','exists:productions,id'],
            'date'          => ['nullable','date'],
            'order_date'    => ['nullable','date'],
        ]);

        $product   = Product::findOrFail((int)$validated['product_id']);
        $quantity  = (float)($validated['quantity'] ?? 1);
        $dateInput = $validated['date'] ?? $validated['order_date'] ?? now()->toDateString();
        $date      = Carbon::parse($dateInput)->toDateString();
        $unitType  = $validated['unit_type'] ?? null;

        // Prefer provided batch; fallback to latest
        $batch = null;
        if (!empty($validated['production_id'])) {
            $b = Production::select('id','product_id','unit_price_pack','unit_price_bag','product_name_snapshot')->findOrFail($validated['production_id']);
            if ((int)$b->product_id === (int)$product->id) $batch = $b;
        }
        if (!$batch) {
            $batch = Production::where('product_id', $product->id)
                ->orderByDesc('production_date')->orderByDesc('id')
                ->first(['id','product_id','unit_price_pack','unit_price_bag','production_date','product_name_snapshot']);
        }

        // Compute unit price according to unit_type & batch
        $unit  = $this->determineUnitPrice($product, $batch, $unitType, $validated['price'] ?? null);
        $total = round($quantity * $unit, 2);

        // Final type label
        $typeLabel = trim((string)($validated['type_label'] ?? ''));
        if ($typeLabel === '' && $batch) $typeLabel = (string)($batch->product_name_snapshot ?? '');
        if ($typeLabel === '') $typeLabel = null;

        $invoice   = $this->nextInvoiceNumber();
        $debugUuid = (string) Str::uuid();
        Log::info("[sales.quickStore] START {$debugUuid}", ['request' => $request->all()]);

        DB::transaction(function () use ($product, $quantity, $unit, $total, $date, $batch, $unitType, $typeLabel, $invoice, $debugUuid) {
            $payload = [
                'product_id'    => $product->id,
                'production_id' => $batch ? $batch->id : null,
                'status'        => 'Completed',
            ];

            $map = [
                'order_number'    => $invoice,
                'invoice_number'  => $invoice,
                'order_date'      => $date,
                'date'            => $date,
                'product'         => $product->product_name,
                'type_label'      => $typeLabel,
                'quantity_kg'     => $quantity,
                'quantity'        => $quantity,
                'unit_price'      => $unit,
                'price'           => $unit,
                'total_price'     => $total,
                'total'           => $total,
                'production_date' => $date,
                $this->unitTypeColumn() => $unitType,
            ];

            foreach ($map as $col => $val) {
                if ($col && Schema::hasColumn('sales', $col)) {
                    $payload[$col] = $val;
                }
            }

            Log::info("[sales.quickStore] PAYLOAD {$debugUuid}", $payload);

            $sale = Sale::create($payload);

            Log::info("[sales.quickStore] CREATED {$debugUuid}", ['id' => $sale->id]);

            $this->recomputeProductBalance($product->id);
        });

        Log::info("[sales.quickStore] COMMIT {$debugUuid}");

        [$cardHtml, $pid] = $this->buildProductCardHtml($product->id);

        $all = Product::all();
        $forecastedDemand      = (float) $all->sum('forecasted_demand');
        $actualInventory       = (float) $all->sum('quantity');
        $shortfall             = max($forecastedDemand - $actualInventory, 0.0);
        $recommendedProduction = $shortfall;

        return response()->json([
            'ok'        => true,
            'message'   => 'Sale recorded.',
            'product_id'=> $pid,
            'card_html' => $cardHtml,
            'totals'    => [
                'forecastedDemand'      => $forecastedDemand,
                'actualInventory'       => $actualInventory,
                'shortfall'             => $shortfall,
                'recommendedProduction' => $recommendedProduction,
            ],
        ]);
    }

    public function receipt(Sale $sale)
    {
        $sale->load([
            'productRef:id,product_name,shelf_life_days',
            'production:id,product_id,batch_number,production_date,expiration_date,unit_price_pack,unit_price_bag',
        ]);

        $production = $this->resolveProductionFor($sale);
        $meta = $this->buildReceiptMeta($sale, $production);

        return view('sales.receipt', [
            'sale' => $sale,
            'meta' => $meta,
        ]);
    }

    public function download(Sale $sale)
    {
        $sale->load([
            'productRef:id,product_name,shelf_life_days',
            'production:id,product_id,batch_number,production_date,expiration_date,unit_price_pack,unit_price_bag',
        ]);

        $production = $this->resolveProductionFor($sale);
        $meta = $this->buildReceiptMeta($sale, $production);

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('sales.receipt', [
                'sale'  => $sale,
                'meta'  => $meta,
                'isPdf' => true,
            ]);
            $file = ($sale->invoice_number ?? $sale->order_number ?? 'receipt') . '.pdf';
            return $pdf->download($file);
        }

        return redirect()->route('sales.receipt', $sale)->with('info', 'PDF package not installed; opened printable receipt instead.');
    }

    /* ----------------------------- Dashboard helpers ----------------------------- */

    protected function getMonthlyRevenueSeries(int $n = 12): array
    {
        $n = max(1, $n);
        $end   = Carbon::now()->startOfMonth();
        $start = (clone $end)->subMonths($n - 1);

        $months = [];
        $cursor = $start->copy();
        while ($cursor <= $end) {
            $months[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        [$dateExpr, $ymExpr] = $this->dateExpressions();
        $sumExpr = $this->buildRevenueSumExpr();

        $rows = Sale::query()
            ->selectRaw("$ymExpr AS ym, $sumExpr AS total")
            ->whereRaw("$dateExpr BETWEEN ? AND ?", [$start->toDateString(), $end->copy()->endOfMonth()->toDateString()])
            ->groupBy('ym')
            ->orderBy('ym')
            ->get();

        $byYm = $rows->keyBy('ym');
        $labels = [];
        $totals = [];
        foreach ($months as $ym) {
            $labels[] = Carbon::parse($ym . '-01')->format('M');
            $totals[] = (float) ($byYm[$ym]->total ?? 0);
        }

        return [$labels, $totals];
    }

    protected function sumRevenueBetween(string $from, string $to): float
    {
        [$dateExpr] = [$this->dateExpressions()[0]];
        $sumExpr = $this->buildRevenueSumExpr();

        return (float) (Sale::query()
            ->whereRaw("$dateExpr BETWEEN ? AND ?", [$from, $to])
            ->selectRaw("$sumExpr AS total")
            ->value('total') ?? 0);
    }

    protected function getTopProductsRevenue(int $days = 90): array
    {
        $cutoff = Carbon::now()->subDays(max(1, $days))->startOfDay();
        [$dateExpr] = [$this->dateExpressions()[0]];

        $selects = ['id','product_id','product','type_label'];
        foreach (['quantity_kg','quantity','unit_price','price','total_price','total','order_date','date'] as $col) {
            if (Schema::hasColumn('sales', $col)) $selects[] = $col;
        }

        $recent = Sale::with('productRef:id,product_name')
            ->whereRaw("$dateExpr >= ?", [$cutoff->toDateString()])
            ->get($selects);

        $cols   = $this->salesNumericColumns();
        $qtyCol = $cols['qty'];
        $unitCol= $cols['unit'];
        $totCol = $cols['total'];

        $bucket = [];
        foreach ($recent as $s) {
            $name = $s->display_product
                ?? ($s->product ?: optional($s->productRef)->product_name ?: 'Unknown');

            $qty  = $qtyCol  ? (float) ($s->{$qtyCol}  ?? 0) : 0.0;
            $unit = $unitCol ? (float) ($s->{$unitCol} ?? 0) : 0.0;
            $tot  = $totCol  ? (float) ($s->{$totCol}  ?? ($qty * $unit)) : ($qty * $unit);

            $bucket[$name] = ($bucket[$name] ?? 0) + $tot;
        }

        if (empty($bucket)) return [[], []];

        arsort($bucket);
        $top = array_slice($bucket, 0, 6, true);

        return [array_keys($top), array_map('floatval', array_values($top))];
    }

    /* ----------------------------- Unit / Pricing helpers ----------------------------- */

    protected function unitTypeColumn(): ?string
    {
        if (Schema::hasColumn('sales','unit_type')) return 'unit_type';
        if (Schema::hasColumn('sales','unit')) return 'unit';
        return null;
    }

    protected function readUnitTypeFromSale(Sale $sale): ?string
    {
        $col = $this->unitTypeColumn();
        return $col ? ($sale->{$col} ?? null) : null;
    }

    /**
     * Decide unit price from (unit_type + batch), with safe fallbacks:
     * 1) If override given, use override.
     * 2) If unit_type == 'pack' and batch has unit_price_pack, use it.
     * 3) If unit_type == 'bag'  and batch has unit_price_bag,  use it.
     * 4) If unit_type omitted: prefer batch->unit_price_pack (if exists), else batch->unit_price_bag.
     * 5) Else fallback to product->price/defaults or 0.
     */
    protected function determineUnitPrice(Product $product, ?Production $batch, ?string $unitType, ?float $override, ?float $fallbackCurrent = null): float
    {
        if ($override !== null) return (float)$override;

        if ($batch) {
            if ($unitType === 'pack' && isset($batch->unit_price_pack) && is_numeric($batch->unit_price_pack)) {
                return (float)$batch->unit_price_pack;
            }
            if ($unitType === 'bag' && isset($batch->unit_price_bag) && is_numeric($batch->unit_price_bag)) {
                return (float)$batch->unit_price_bag;
            }
            if (isset($batch->unit_price_pack) && is_numeric($batch->unit_price_pack)) {
                return (float)$batch->unit_price_pack;
            }
            if (isset($batch->unit_price_bag) && is_numeric($batch->unit_price_bag)) {
                return (float)$batch->unit_price_bag;
            }
        }

        if ($fallbackCurrent !== null) return (float)$fallbackCurrent;

        $candidates = ['price','default_price','unit_cost'];
        foreach ($candidates as $c) {
            if (isset($product->{$c}) && is_numeric($product->{$c})) {
                return (float)$product->{$c};
            }
        }
        return 0.0;
    }

    protected function inferCurrentUnitPrice(Sale $sale): ?float
    {
        $unitCol = Schema::hasColumn('sales','unit_price') ? 'unit_price'
                : (Schema::hasColumn('sales','price') ? 'price' : null);
        if (!$unitCol) return null;
        $val = $sale->{$unitCol};
        return is_numeric($val) ? (float)$val : null;
    }

    /* ----------------------------- Misc helpers ----------------------------- */

    protected function respondValidationError(Request $request, array $errors)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['ok' => false, 'errors' => $errors], 422);
        }
        return back()->withErrors($errors)->withInput();
    }

    protected function recomputeProductBalance($productId)
    {
        $produced = (float) Production::where('product_id', $productId)->sum('quantity');

        $qtyCol = Schema::hasColumn('sales','quantity_kg') ? 'quantity_kg'
               : (Schema::hasColumn('sales','quantity') ? 'quantity' : null);

        if ($qtyCol) {
            $sold = (float) Sale::where('product_id', $productId)
                ->selectRaw("SUM(COALESCE($qtyCol,0)) as total_sold")
                ->value('total_sold');
        } else {
            $sold = 0.0;
        }

        $balance  = max(0.0, $produced - $sold);
        $latestProdDate = Production::where('product_id', $productId)->max('production_date');

        Product::where('id', $productId)->update([
            'quantity'        => $balance,
            'stock_status'    => $balance > 0 ? 'in_stock' : 'out_of_stock',
            'production_date' => $latestProdDate,
        ]);
    }

    protected function buildProductCardHtml($productId)
    {
        $fresh = Product::find($productId);
        if (!$fresh) return [null, $productId];

        $orig = $fresh->image_url ?? asset('images/default-product.png');
        $fresh->card_image_url     = $orig;
        $fresh->image_thumb_url    = null;
        $fresh->image_medium_url   = null;
        $fresh->image_original_url = $orig;
        $fresh->card_image_srcset  = null;

        $cardHtml = View::exists('production.partials.product-card')
            ? view('production.partials.product-card', ['p' => $fresh])->render()
            : view('production.partials.product-cards', ['products' => collect([$fresh])])->render();

        return [$cardHtml, $fresh->id];
    }

    protected function resolveProductionFor(Sale $sale)
    {
        if ($sale->relationLoaded('production') && $sale->production) {
            return $sale->production;
        }
        $productId = (int) ($sale->product_id ?: 0);
        if (!$productId) return null;

        $orderDate = $sale->order_date ?: ($sale->date ?: null);

        return $this->resolveProductionByProductAndDate($productId, $orderDate);
    }

    protected function resolveProductionByProductAndDate($productId, $orderDate)
    {
        $query = Production::where('product_id', (int)$productId);

        if ($orderDate) {
            $nearest = (clone $query)
                ->whereDate('production_date', '<=', Carbon::parse($orderDate)->toDateString())
                ->orderByDesc('production_date')
                ->orderByDesc('id')
                ->first();
            if ($nearest) return $nearest;
        }

        return (clone $query)->orderByDesc('production_date')->orderByDesc('id')->first();
    }

    protected function buildReceiptMeta(Sale $sale, $production = null)
    {
        $invoiceNo = $sale->invoice_number ?? $sale->order_number ?? null;

        $orderDate = $sale->order_date ?? $sale->date ?? null;

        $cols   = $this->salesNumericColumns();
        $qtyCol = $cols['qty'];
        $unitCol= $cols['unit'];
        $totCol = $cols['total'];

        $qty   = $qtyCol  ? (float) ($sale->{$qtyCol}  ?? 0) : 0.0;
        $unit  = $unitCol ? (float) ($sale->{$unitCol} ?? 0) : 0.0;
        $total = $totCol  ? (float) ($sale->{$totCol}  ?? ($qty * $unit)) : ($qty * $unit);

        $productionDate = $sale->production_date
            ?? ($production->production_date ?? null);

        $explicitExpiry = $sale->expiration_date
            ?? ($production->expiration_date ?? null);

        $shelfLifeDays  = (int) (optional($sale->productRef)->shelf_life_days ?? 0);
        $expiry = $explicitExpiry ?: $this->computeExpirationDate($productionDate, $shelfLifeDays);

        $daysLeft = null;
        if ($expiry) {
            $daysLeft = Carbon::parse($expiry)->diffInDays(now(), false) * -1;
        }

        // Human label for unit type
        $unitTypeCol = $this->unitTypeColumn();
        $unitTypeVal = $unitTypeCol ? ($sale->{$unitTypeCol} ?? null) : null;
        $unitTypeLabel = $unitTypeVal === 'bag' ? 'bag'
                         : ($unitTypeVal === 'pack' ? 'pack' : null);

        return [
            'invoice'         => $invoiceNo,
            'display_product' => $sale->display_product
                ?? ($sale->product ?? optional($sale->productRef)->product_name ?? 'N/A'),
            'order_date'      => $orderDate,
            'quantity'        => $qty,
            'unit_price'      => $unit,
            'unit_type_label' => $unitTypeLabel,
            'total'           => $total,
            'status'          => $sale->status ?? 'Completed',
            'customer_name'   => $sale->customer_name ?? null,

            'batch_number'    => $production->batch_number ?? null,
            'production_date' => $productionDate,
            'expiration_date' => $expiry,
            'days_left'       => $daysLeft,
        ];
    }

    protected function computeExpirationDate($productionDate, $shelfLifeDays)
    {
        if (!$productionDate) return null;
        $shelfLifeDays = (int)$shelfLifeDays;
        if ($shelfLifeDays <= 0) return null;
        return Carbon::parse($productionDate)->addDays($shelfLifeDays)->toDateString();
    }

    /* ----------------------------- SQL helpers ----------------------------- */

    protected function dateExpressions(): array
    {
        $hasOrder = Schema::hasColumn('sales','order_date');
        $hasDate  = Schema::hasColumn('sales','date');

        if ($hasOrder && $hasDate) {
            $coalesce = "COALESCE(order_date, `date`)";
        } elseif ($hasOrder) {
            $coalesce = "order_date";
        } elseif ($hasDate) {
            $coalesce = "`date`";
        } else {
            $coalesce = "created_at";
        }

        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            $dateExpr = "DATE($coalesce)";
            $ymExpr   = "strftime('%Y-%m', $coalesce)";
        } else {
            $dateExpr = "DATE($coalesce)";
            $ymExpr   = "DATE_FORMAT($coalesce, '%Y-%m')";
        }

        $orderExpr = $coalesce;

        return [$dateExpr, $ymExpr, $orderExpr];
    }

    protected function buildRevenueSumExpr(): string
    {
        $cols   = $this->salesNumericColumns();
        $qtyCol = $cols['qty'];
        $unitCol= $cols['unit'];
        $totCol = $cols['total'];

        if ($totCol) {
            $qtyPart  = $qtyCol  ? "COALESCE($qtyCol,0)"  : "0";
            $unitPart = $unitCol ? "COALESCE($unitCol,0)" : "0";
            return "SUM(COALESCE($totCol, ($qtyPart * $unitPart), 0))";
        }

        if ($qtyCol && $unitCol) {
            return "SUM(COALESCE(COALESCE($qtyCol,0) * COALESCE($unitCol,0), 0))";
        }

        return "SUM(0)";
    }

    protected function salesNumericColumns(): array
    {
        $total = Schema::hasColumn('sales','total_price') ? 'total_price'
               : (Schema::hasColumn('sales','total') ? 'total' : null);

        $qty   = Schema::hasColumn('sales','quantity_kg') ? 'quantity_kg'
               : (Schema::hasColumn('sales','quantity') ? 'quantity' : null);

        $unit  = Schema::hasColumn('sales','unit_price') ? 'unit_price'
               : (Schema::hasColumn('sales','price') ? 'price' : null);

        return ['total' => $total, 'qty' => $qty, 'unit' => $unit];
    }

    /* ----------------------------- Types API for Add Sale modal ----------------------------- */
    public function apiTypes(Request $request)
    {
        $productId = (int) $request->query('product_id');
        if ($productId <= 0) {
            return response()->json(['ok' => false, 'list' => [], 'next' => 'Type 1'], 422);
        }

        $parent = Product::find($productId);
        if (!$parent) {
            return response()->json(['ok' => false, 'list' => [], 'next' => 'Type 1'], 404);
        }

        // Gather distinct type labels from production orders (snapshot) + child variants + maybe category
        $fromOrders = Production::query()
            ->where(function($q) use ($parent){
                $q->where('parent_product_id', $parent->id)
                  ->orWhere(function($q2) use ($parent){
                      $q2->whereNull('parent_product_id')
                         ->where('product_id', $parent->id);
                  });
            })
            ->whereNotNull('product_name_snapshot')
            ->pluck('product_name_snapshot');

        $fromVariants = Product::where('parent_id', $parent->id)->pluck('product_name');
        $maybeCat     = collect($parent->category ? [$parent->category] : []);

        $list = $fromOrders
            ->merge($fromVariants)
            ->merge($maybeCat)
            ->map(fn($s)=>trim((string)$s))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        // Compute a suggested next label like “Type N+1”
        $existing = $list->values();
        $maxN = 0;
        foreach ($existing as $label) {
            if (preg_match('/\bType\s+(\d+)\b/i', (string)$label, $m)) {
                $n = (int)$m[1];
                if ($n > $maxN) $maxN = $n;
            }
        }
        $candidate = $maxN > 0 ? $maxN + 1 : ($existing->count() + 1);
        $next = "Type {$candidate}";

        return response()->json([
            'ok'   => true,
            'list' => $existing->all(),
            'next' => $next,
        ]);
    }
}
