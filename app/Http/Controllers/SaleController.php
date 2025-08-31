<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Production;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SalesController extends Controller
{
    /** List sales + feed Add-Sale modal */
    public function index(): View
    {
        $sales = Sale::with([
                'productRef:id,product_name',
                'production:id,product_id,batch_number,quantity,current_inventory'
            ])
            ->orderByDesc(DB::raw('COALESCE(order_date, date)'))
            ->orderByDesc('id')
            ->get();

        $products = Product::select('id','product_name','unit_cost')
            ->orderBy('product_name')
            ->get()
            ->map(function ($p) {
                $p->name  = $p->product_name;
                $p->price = $p->unit_cost ?? 0;
                return $p;
            });

        $statusOptions = ['Pending','Completed','Cancelled','Paid'];
        $nextInvoice   = $this->peekNextInvoiceNumber(); // UI preview (does not reserve)

        return view('sales.index', compact('sales','nextInvoice','products','statusOptions'));
    }

    /* ---------------- Invoice number helpers (atomic + fallback) ---------------- */

    /** Reserve & return next invoice number for today (INV-YYYYMMDD-###). */
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

    /** Peek next invoice (no reservation) for UI display. */
    protected function peekNextInvoiceNumber(): string
    {
        $todayDate = now()->toDateString();
        $ymd       = now()->format('Ymd');

        try {
            $row  = DB::table('invoice_sequences')->where('date_key', $todayDate)->first();
            $next = ($row?->last_seq ?? 0) + 1;

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
            'product_id'     => ['required','integer','exists:products,id'],
            'production_id'  => ['nullable','integer','exists:productions,id'],
            'date'           => ['required','date'],
            'quantity'       => ['required','numeric','min:0.001'],
            'price'          => ['required','numeric','min:0'],
            // status now optional; default applied below
            'status'         => ['nullable','string','in:Pending,Completed,Cancelled,Paid'],
            'product'        => ['sometimes','nullable','string','max:150'],
            // Optional extra fields (if you store them; otherwise ignore)
            'production_date' => ['nullable','date'],
            'expiration_date' => ['nullable','date','after_or_equal:production_date'],
            'notes'           => ['nullable','string','max:2000'],
        ]);

        if (!empty($validated['production_id'])) {
            $batch = Production::select('id','product_id')->findOrFail($validated['production_id']);
            if ((int)$batch->product_id !== (int)$validated['product_id']) {
                return $this->respondValidationError($request, ['production_id' => 'Selected batch does not belong to the chosen product.']);
            }
        }

        $product     = Product::select('id','product_name')->findOrFail($validated['product_id']);
        $displayName = $validated['product'] ?? $product->product_name;
        $invoice     = $this->nextInvoiceNumber(); // atomic
        $total       = round(((float)$validated['quantity']) * ((float)$validated['price']), 2);
        $status      = $validated['status'] ?? 'Completed'; // ✅ default to Completed

        try {
            DB::transaction(function () use ($validated, $displayName, $invoice, $total, $status, $product) {
                $payload = [
                    'invoice_number' => $invoice,
                    'product_id'     => (int) $validated['product_id'],
                    'production_id'  => $validated['production_id'] ?? null,
                    'product'        => $displayName,
                    'date'           => $validated['date'],
                    'quantity'       => (float) $validated['quantity'],
                    'price'          => (float) $validated['price'],
                    'total'          => $total,
                    'status'         => $status,
                ];

                // Store optional fields only if columns exist
                if (Schema()->hasColumn('sales','production_date') && !empty($validated['production_date'])) {
                    $payload['production_date'] = $validated['production_date'];
                }
                if (Schema()->hasColumn('sales','expiration_date') && !empty($validated['expiration_date'])) {
                    $payload['expiration_date'] = $validated['expiration_date'];
                }
                if (Schema()->hasColumn('sales','notes') && !empty($validated['notes'])) {
                    $payload['notes'] = trim($validated['notes']);
                }

                Sale::create($payload);

                // Recompute product balance after sale
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

    public function edit(Sale $sale): JsonResponse
    {
        $sale->load(['productRef:id,product_name','production:id,batch_number,product_id,quantity,current_inventory']);
        return response()->json($sale);
    }

    public function update(Request $request, Sale $sale): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'product_id'     => ['required','integer','exists:products,id'],
            'production_id'  => ['nullable','integer','exists:productions,id'],
            'date'           => ['required','date'],
            'quantity'       => ['required','numeric','min:0.001'],
            'price'          => ['required','numeric','min:0'],
            'status'         => ['nullable','string','in:Pending,Completed,Cancelled,Paid'], // now optional
            'product'        => ['sometimes','nullable','string','max:150'],
            'production_date' => ['nullable','date'],
            'expiration_date' => ['nullable','date','after_or_equal:production_date'],
            'notes'           => ['nullable','string','max:2000'],
        ]);

        if (!empty($validated['production_id'])) {
            $batch = Production::select('id','product_id')->findOrFail($validated['production_id']);
            if ((int)$batch->product_id !== (int)$validated['product_id']) {
                return $this->respondValidationError($request, ['production_id' => 'Selected batch does not belong to the chosen product.']);
            }
        }

        $oldProductId = (int)$sale->product_id;

        $product     = Product::select('id','product_name')->findOrFail($validated['product_id']);
        $displayName = $validated['product'] ?? $product->product_name;
        $total       = round(((float)$validated['quantity']) * ((float)$validated['price']), 2);
        $status      = $validated['status'] ?? $sale->status ?? 'Completed';

        try {
            DB::transaction(function () use ($sale, $validated, $displayName, $total, $status) {
                $payload = [
                    'product_id'    => (int) $validated['product_id'],
                    'production_id' => $validated['production_id'] ?? null,
                    'product'       => $displayName,
                    'date'          => $validated['date'],
                    'quantity'      => (float) $validated['quantity'],
                    'price'         => (float) $validated['price'],
                    'total'         => $total,
                    'status'        => $status,
                ];

                if (Schema()->hasColumn('sales','production_date')) {
                    $payload['production_date'] = $validated['production_date'] ?? null;
                }
                if (Schema()->hasColumn('sales','expiration_date')) {
                    $payload['expiration_date'] = $validated['expiration_date'] ?? null;
                }
                if (Schema()->hasColumn('sales','notes')) {
                    $payload['notes'] = $validated['notes'] ?? null;
                }

                $sale->update($payload);
            });

            // Recompute balances for both old and (possibly) new product
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

    public function destroy(Sale $sale): RedirectResponse
    {
        $productId = (int)$sale->product_id;
        $sale->delete();
        $this->recomputeProductBalance($productId);
        return redirect()->route('sales')->with('success', 'Sale deleted.');
    }

    public function available(Request $request): JsonResponse
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

    /* ----------------------------- Helpers ----------------------------- */

    protected function respondValidationError(Request $request, array $errors)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['ok' => false, 'errors' => $errors], 422);
        }
        return back()->withErrors($errors)->withInput();
    }

    protected function recomputeProductBalance(int $productId): void
    {
        // Produced - Sold, never negative
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
}
