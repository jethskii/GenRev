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

class SalesController extends Controller
{
    /** List sales + feed Add-Sale modal + dashboard KPIs & charts */
    public function index()
    {
        $sales = Sale::with([
                'productRef:id,product_name,shelf_life_days',
                'production:id,product_id,batch_number,quantity,current_inventory,production_date,expiration_date'
            ])
            ->orderByDesc(DB::raw('COALESCE(order_date, `date`)'))
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

        $statusOptions = ['Pending','Completed','Cancelled','Paid'];
        $nextInvoice   = $this->peekNextInvoiceNumber();

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

        // Safe fallbacks so charts render even with zero data
        if (empty($donutLabels) || array_sum($donutValues) <= 0) {
            $donutLabels = ['No Data'];
            $donutValues = [0];
        }
        if (empty($chartMonths) || empty($chartTotals)) {
            $chartMonths = ['Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec','Jan'];
            $chartTotals = array_fill(0, count($chartMonths), 0);
        }

        return view('sales.index', compact(
            'sales','nextInvoice','products','statusOptions',
            'chartMonths','chartTotals','annualRevenue','monthlyRevenue','orderCount',
            'donutLabels','donutValues'
        ));
    }

    /* ---------------- Invoice number helpers (atomic + fallback) ---------------- */

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
            'date'            => ['required','date'],
            'quantity'        => ['required','numeric','min:0.001'],
            'price'           => ['required','numeric','min:0'],
            'status'          => ['nullable','string','in:Pending,Completed,Cancelled,Paid'],
            'product'         => ['sometimes','nullable','string','max:150'],
            'production_date' => ['nullable','date'],
            'expiration_date' => ['nullable','date','after_or_equal:production_date'],
            'notes'           => ['nullable','string','max:2000'],
            'customer_name'   => ['nullable','string','max:255'],
        ]);

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
        $unit        = (float) $validated['price'];
        $total       = round($qty * $unit, 2);
        $status      = $validated['status'] ?? 'Completed';

        $hasNew = Schema::hasColumn('sales','order_date')
                && Schema::hasColumn('sales','quantity_kg')
                && Schema::hasColumn('sales','unit_price')
                && Schema::hasColumn('sales','total_price');

        $orderDateStr = $validated['date'];
        if (empty($resolvedProductionId)) {
            $resolved = $this->resolveProductionByProductAndDate((int)$product->id, $orderDateStr);
            $resolvedProductionId = $resolved ? $resolved->id : null;
        }

        try {
            DB::transaction(function () use ($validated, $displayName, $invoice, $qty, $unit, $total, $status, $product, $hasNew, $resolvedProductionId) {
                $payload = [
                    'product_id'    => (int) $validated['product_id'],
                    'production_id' => $resolvedProductionId,
                    'status'        => $status,
                ];

                if (Schema::hasColumn('sales','customer_name'))   $payload['customer_name'] = $validated['customer_name'] ?? null;
                if (Schema::hasColumn('sales','notes'))            $payload['notes'] = $validated['notes'] ?? null;
                if (Schema::hasColumn('sales','production_date'))  $payload['production_date'] = $validated['production_date'] ?? null;
                if (Schema::hasColumn('sales','expiration_date'))  $payload['expiration_date'] = $validated['expiration_date'] ?? null;

                if ($hasNew) {
                    if (Schema::hasColumn('sales','order_number')) $payload['order_number'] = $invoice;
                    $payload += [
                        'order_date'   => $validated['date'],
                        'quantity_kg'  => $qty,
                        'unit_price'   => $unit,
                        'total_price'  => $total,
                    ];
                } else {
                    $payload += [
                        'invoice_number' => $invoice,
                        'product'        => $displayName,
                        'date'           => $validated['date'],
                        'quantity'       => $qty,
                        'price'          => $unit,
                        'total'          => $total,
                    ];
                }

                foreach (array_keys($payload) as $k) {
                    if (!Schema::hasColumn('sales',$k)) unset($payload[$k]);
                }

                Sale::create($payload);

                $this->recomputeProductBalance((int)$product->id);
            });

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['ok' => true, 'message' => 'Sale saved.']);
            }
            return redirect()->route('sales')->with('success', 'Sale recorded.');
        } catch (\Throwable $e) {
            Log::error('Failed to save sale', ['error' => $e->getMessage()]);
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => 'Server error while saving sale.'], 500);
            }
            return back()->with('error', 'Server error while saving sale.')->withInput();
        }
    }

    public function edit(Sale $sale)
    {
        $sale->load([
            'productRef:id,product_name,shelf_life_days,unit_cost',
            'production:id,product_id,batch_number,production_date,expiration_date',
        ]);

        $products = Product::orderBy('product_name')->get(['id','product_name','unit_cost','shelf_life_days']);

        $batches = Production::where('product_id', $sale->product_id)
            ->orderByDesc('production_date')->orderByDesc('id')
            ->get(['id','batch_number','production_date','expiration_date']);

        $productionDate = $sale->production_date ?: ($sale->production->production_date ?? null);
        $expirationDate = $sale->expiration_date ?: ($sale->production->expiration_date ?? null);

        if (!$expirationDate && $productionDate && $sale->productRef && (int)$sale->productRef->shelf_life_days > 0) {
            $expirationDate = Carbon::parse($productionDate)
                ->addDays((int)$sale->productRef->shelf_life_days)
                ->toDateString();
        }

        $statusOptions = ['Pending','Completed','Cancelled','Paid'];

        return view('sales.edit', compact(
            'sale','products','batches','productionDate','expirationDate','statusOptions'
        ));
    }

    public function update(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'product_id'      => ['required','integer','exists:products,id'],
            'production_id'   => ['nullable','integer','exists:productions,id'],
            'date'            => ['required','date'],
            'quantity'        => ['required','numeric','min:0.001'],
            'price'           => ['required','numeric','min:0'],
            'status'          => ['nullable','string','in:Pending,Completed,Cancelled,Paid'],
            'product'         => ['sometimes','nullable','string','max:150'],
            'production_date' => ['nullable','date'],
            'expiration_date' => ['nullable','date','after_or_equal:production_date'],
            'notes'           => ['nullable','string','max:2000'],
            'customer_name'   => ['nullable','string','max:255'],
        ]);

        $resolvedProductionId = $validated['production_id'] ?? null;
        if (!empty($resolvedProductionId)) {
            $batch = Production::select('id','product_id')->findOrFail($resolvedProductionId);
            if ((int)$batch->product_id !== (int)$validated['product_id']) {
                return $this->respondValidationError($request, ['production_id' => 'Selected batch does not belong to the chosen product.']);
            }
        }

        $oldProductId = (int)$sale->product_id;
        $product     = Product::select('id','product_name','shelf_life_days')->findOrFail($validated['product_id']);
        $displayName = $validated['product'] ?? $product->product_name;
        $qty         = (float) $validated['quantity'];
        $unit        = (float) $validated['price'];
        $total       = round($qty * $unit, 2);
        $status      = $validated['status'] ?? ($sale->status ?: 'Completed');

        $hasNew = Schema::hasColumn('sales','order_date')
                && Schema::hasColumn('sales','quantity_kg')
                && Schema::hasColumn('sales','unit_price')
                && Schema::hasColumn('sales','total_price');

        $orderDateStr = $validated['date'];
        if (empty($resolvedProductionId)) {
            $resolved = $this->resolveProductionByProductAndDate((int)$product->id, $orderDateStr);
            $resolvedProductionId = $resolved ? $resolved->id : null;
        }

        try {
            DB::transaction(function () use ($sale, $validated, $displayName, $qty, $unit, $total, $status, $hasNew, $resolvedProductionId) {
                $payload = [
                    'product_id'    => (int) $validated['product_id'],
                    'production_id' => $resolvedProductionId,
                    'status'        => $status,
                ];

                if (Schema::hasColumn('sales','customer_name'))   $payload['customer_name'] = $validated['customer_name'] ?? null;
                if (Schema::hasColumn('sales','notes'))            $payload['notes'] = $validated['notes'] ?? null;
                if (Schema::hasColumn('sales','production_date'))  $payload['production_date'] = $validated['production_date'] ?? null;
                if (Schema::hasColumn('sales','expiration_date'))  $payload['expiration_date'] = $validated['expiration_date'] ?? null;

                if ($hasNew) {
                    $payload += [
                        'order_date'   => $validated['date'],
                        'quantity_kg'  => $qty,
                        'unit_price'   => $unit,
                        'total_price'  => $total,
                    ];
                } else {
                    $payload += [
                        'product'  => $displayName,
                        'date'     => $validated['date'],
                        'quantity' => $qty,
                        'price'    => $unit,
                        'total'    => $total,
                    ];
                }

                foreach (array_keys($payload) as $k) {
                    if (!Schema::hasColumn('sales',$k)) unset($payload[$k]);
                }

                $sale->update($payload);
            });

            $this->recomputeProductBalance($oldProductId);
            $this->recomputeProductBalance((int)$product->id);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['ok' => true, 'message' => 'Sale updated.']);
            }
            return redirect()->route('sales')->with('success', 'Sale updated.');
        } catch (\Throwable $e) {
            Log::error('Failed to update sale', ['error' => $e->getMessage()]);
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => 'Server error while updating sale.'], 500);
            }
            return back()->with('error', 'Server error while updating sale.')->withInput();
        }
    }

    public function destroy(Sale $sale)
    {
        $productId = (int)$sale->product_id;
        $sale->delete();
        $this->recomputeProductBalance($productId);
        return redirect()->route('sales')->with('success', 'Sale deleted.');
    }

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
            'production_id' => ['nullable','integer','exists:productions,id'],
            'date'          => ['nullable','date'],
        ]);

        $product   = Product::findOrFail((int)$validated['product_id']);
        $quantity  = (float)($validated['quantity'] ?? 1);
        $price     = (float)($validated['price'] ?? ($product->price ?? $product->default_price ?? $product->unit_cost ?? 0));
        $date      = !empty($validated['date']) ? Carbon::parse($validated['date'])->toDateString() : now()->toDateString();

        $batch = null;
        if (!empty($validated['production_id'])) {
            $b = Production::select('id','product_id')->findOrFail($validated['production_id']);
            if ((int)$b->product_id === (int)$product->id) $batch = $b;
        }
        if (!$batch) {
            $batch = Production::where('product_id', $product->id)
                ->orderByDesc('production_date')->orderByDesc('id')->first();
        }

        $invoice = $this->nextInvoiceNumber();
        $hasNew  = Schema::hasColumn('sales','order_date')
                && Schema::hasColumn('sales','quantity_kg')
                && Schema::hasColumn('sales','unit_price')
                && Schema::hasColumn('sales','total_price');

        DB::transaction(function () use ($product, $quantity, $price, $date, $batch, $invoice, $hasNew) {
            $payload = [
                'product_id'    => $product->id,
                'production_id' => $batch ? $batch->id : null,
                'status'        => 'Completed',
            ];

            if (Schema::hasColumn('sales','production_date')) $payload['production_date'] = $date;

            if ($hasNew) {
                if (Schema::hasColumn('sales','order_number')) $payload['order_number'] = $invoice;
                $payload += [
                    'order_date'   => $date,
                    'quantity_kg'  => $quantity,
                    'unit_price'   => $price,
                    'total_price'  => round($quantity * $price, 2),
                ];
            } else {
                $payload += [
                    'invoice_number' => $invoice,
                    'product'        => $product->product_name,
                    'date'           => $date,
                    'quantity'       => $quantity,
                    'price'          => $price,
                    'total'          => round($quantity * $price, 2),
                ];
            }

            foreach (array_keys($payload) as $k) {
                if (!Schema::hasColumn('sales',$k)) unset($payload[$k]);
            }

            Sale::create($payload);

            $this->recomputeProductBalance($product->id);
        });

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
            'production:id,product_id,batch_number,production_date,expiration_date',
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
            'production:id,product_id,batch_number,production_date,expiration_date',
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

    /** Return [labels(M), totals] for last N months; auto-detects columns. */
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
        $sumExpr = $this->buildRevenueSumExpr();  // <-- dynamic sum

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

    /** Sum revenue in range; auto-detects columns. */
    protected function sumRevenueBetween(string $from, string $to): float
    {
        [$dateExpr] = [$this->dateExpressions()[0]];
        $sumExpr = $this->buildRevenueSumExpr();

        return (float) (Sale::query()
            ->whereRaw("$dateExpr BETWEEN ? AND ?", [$from, $to])
            ->selectRaw("$sumExpr AS total")
            ->value('total') ?? 0);
    }

    /** Top product revenue; auto-detects columns and only selects what exists. */
    protected function getTopProductsRevenue(int $days = 90): array
    {
        $cutoff = Carbon::now()->subDays(max(1, $days))->startOfDay();
        [$dateExpr] = [$this->dateExpressions()[0]];

        // Build select list based on real columns
        $selects = ['id','product_id','product'];
        foreach (['quantity_kg','quantity','unit_price','price','total_price','total','order_date','date'] as $col) {
            if (Schema::hasColumn('sales', $col)) $selects[] = $col;
        }

        $recent = Sale::with('productRef:id,product_name')
            ->whereRaw("$dateExpr >= ?", [$cutoff->toDateString()])
            ->get($selects);

        // Resolve numeric columns
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

    /** Driver-safe date expressions */
    protected function dateExpressions(): array
    {
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            $dateExpr = "DATE(COALESCE(order_date, `date`))";
            $ymExpr   = "strftime('%Y-%m', COALESCE(order_date, `date`))";
        } else {
            $dateExpr = "DATE(COALESCE(order_date, `date`))";
            $ymExpr   = "DATE_FORMAT(COALESCE(order_date, `date`), '%Y-%m')";
        }
        return [$dateExpr, $ymExpr];
    }

    /** Build a SUM(...) expression that matches existing sales columns. */
    protected function buildRevenueSumExpr(): string
    {
        $cols   = $this->salesNumericColumns();
        $qtyCol = $cols['qty'];   // quantity_kg OR quantity OR null
        $unitCol= $cols['unit'];  // unit_price OR price OR null
        $totCol = $cols['total']; // total_price OR total OR null

        // If a total column exists, prefer it; else compute qty*unit if both exist; else 0.
        if ($totCol) {
            $qtyPart  = $qtyCol  ? "COALESCE($qtyCol,0)"  : "0";
            $unitPart = $unitCol ? "COALESCE($unitCol,0)" : "0";
            return "SUM(COALESCE($totCol, ($qtyPart * $unitPart), 0))";
        }

        if ($qtyCol && $unitCol) {
            return "SUM(COALESCE(COALESCE($qtyCol,0) * COALESCE($unitCol,0), 0))";
        }

        return "SUM(0)"; // ultra-safe fallback
    }

    /** Figure out which numeric columns exist in sales table. */
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

        // dynamic qty column
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

        return [
            'invoice'         => $invoiceNo,
            'display_product' => $sale->display_product
                ?? ($sale->product ?? optional($sale->productRef)->product_name ?? 'N/A'),
            'order_date'      => $orderDate,
            'quantity'        => $qty,
            'unit_price'      => $unit,
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
}
