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
        $todayDate = now()->toDateString(); // 2025-08-16
        $ymd       = now()->format('Ymd');  // 20250816

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
            // Fallback if table missing / migration not run
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

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id'     => ['required','integer','exists:products,id'],
            'production_id'  => ['nullable','integer','exists:productions,id'],
            'date'           => ['required','date'],
            'quantity'       => ['required','numeric','min:0.001'],
            'price'          => ['required','numeric','min:0'],
            'status'         => ['required','string','in:Pending,Completed,Cancelled,Paid'],
            'product'        => ['sometimes','nullable','string','max:150'],
        ]);

        if (!empty($validated['production_id'])) {
            $batch = Production::select('id','product_id')->findOrFail($validated['production_id']);
            if ((int)$batch->product_id !== (int)$validated['product_id']) {
                return back()->withErrors(['production_id' => 'Selected batch does not belong to the chosen product.'])
                             ->withInput();
            }
        }

        $product     = Product::select('id','product_name')->findOrFail($validated['product_id']);
        $displayName = $validated['product'] ?? $product->product_name;

        $invoice = $this->nextInvoiceNumber(); // atomic
        $total   = round(((float)$validated['quantity']) * ((float)$validated['price']), 2);

        Sale::create([
            'invoice_number' => $invoice,
            'product_id'     => (int) $validated['product_id'],
            'production_id'  => $validated['production_id'] ?? null,
            'product'        => $displayName,
            'date'           => $validated['date'],
            'quantity'       => (float) $validated['quantity'],
            'price'          => (float) $validated['price'],
            'total'          => $total,
            'status'         => $validated['status'],
        ]);

        return redirect()->route('sales')->with('success', 'Sale recorded.');
    }

    public function edit(Sale $sale): JsonResponse
    {
        $sale->load(['productRef:id,product_name','production:id,batch_number,product_id,quantity,current_inventory']);
        return response()->json($sale);
    }

    public function update(Request $request, Sale $sale): RedirectResponse
    {
        $validated = $request->validate([
            'product_id'     => ['required','integer','exists:products,id'],
            'production_id'  => ['nullable','integer','exists:productions,id'],
            'date'           => ['required','date'],
            'quantity'       => ['required','numeric','min:0.001'],
            'price'          => ['required','numeric','min:0'],
            'status'         => ['required','string','in:Pending,Completed,Cancelled,Paid'],
            'product'        => ['sometimes','nullable','string','max:150'],
        ]);

        if (!empty($validated['production_id'])) {
            $batch = Production::select('id','product_id')->findOrFail($validated['production_id']);
            if ((int)$batch->product_id !== (int)$validated['product_id']) {
                return back()->withErrors(['production_id' => 'Selected batch does not belong to the chosen product.'])
                             ->withInput();
            }
        }

        $product     = Product::select('id','product_name')->findOrFail($validated['product_id']);
        $displayName = $validated['product'] ?? $product->product_name;
        $total       = round(((float)$validated['quantity']) * ((float)$validated['price']), 2);

        $sale->update([
            'product_id'    => (int) $validated['product_id'],
            'production_id' => $validated['production_id'] ?? null,
            'product'       => $displayName,
            'date'          => $validated['date'],
            'quantity'      => (float) $validated['quantity'],
            'price'         => (float) $validated['price'],
            'total'         => $total,
            'status'        => $validated['status'],
        ]);

        return redirect()->route('sales')->with('success', 'Sale updated.');
    }

    public function destroy(Sale $sale): RedirectResponse
    {
        $sale->delete();
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
}
