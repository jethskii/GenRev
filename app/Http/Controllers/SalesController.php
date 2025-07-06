<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Barryvdh\DomPDF\Facade\Pdf;

class SalesController extends Controller
{
    /**
     * Display the sales list.
     */
    public function index(): View
    {
        $sales = Sale::orderByDesc('date')->get();

        $today = now()->format('Ymd');
        $dailyCount = Sale::whereDate('created_at', today())->count() + 1;
        $nextInvoice = 'INV-' . $today . '-' . str_pad($dailyCount, 3, '0', STR_PAD_LEFT);

        return view('sales', compact('sales', 'nextInvoice'));
    }

    /**
     * Store a newly created sale.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_name' => 'required|string|max:100',
            'date'         => 'required|date',
            'quantity'     => 'required|integer|min:1',
            'price'        => 'required|numeric|min:0',
            'status'       => 'required|string|in:Paid,Pending,Cancelled',
        ]);

        $invoiceNumber = 'INV-' . now()->format('Ymd') . '-' . str_pad(
            Sale::whereDate('created_at', today())->count() + 1,
            3,
            '0',
            STR_PAD_LEFT
        );

        $validated['invoice_number'] = $invoiceNumber;

        Sale::create($validated);

        return redirect()->route('sales')->with('success', 'Sale added successfully!');
    }

    /**
     * Return JSON data for editing.
     */
    public function edit($id): JsonResponse
    {
        $sale = Sale::findOrFail($id);
        return response()->json($sale);
    }

    /**
     * Update a specific sale.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'product_name' => 'required|string|max:100',
            'date'         => 'required|date',
            'quantity'     => 'required|integer|min:1',
            'price'        => 'required|numeric|min:0',
            'status'       => 'required|string|in:Paid,Pending,Cancelled',
        ]);

        $sale = Sale::findOrFail($id);
        $sale->update($validated);

        return redirect()->route('sales')->with('success', 'Sale updated successfully!');
    }

    /**
     * Display or download a receipt view.
     */
    public function receipt($id)
    {
        $sale = Sale::findOrFail($id);

        if (request()->has('download')) {
            $pdf = Pdf::loadView('sales.receipt', compact('sale'));
            return $pdf->download("receipt_{$sale->invoice_number}.pdf");
        }

        return view('sales.receipt', compact('sale'));
    }
}
