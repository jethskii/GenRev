<?php
// SalesController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;

class SalesController extends Controller
{
    /**
     * Display a listing of the sales.
     */
    public function index()
    {
        $sales = Sale::orderBy('date', 'desc')->get();
        return view('sales.index', compact('sales'));
    }

    /**
     * Show the form for creating a new sale.
     */
    public function create()
    {
        return view('sales.create');
    }

    /**
     * Store a newly created sale in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_name' => 'required|string|max:255',
            'quantity'     => 'required|numeric',
            'price'        => 'required|numeric',
            'date'         => 'required|date',
        ]);

        Sale::create($validated);

        return redirect()->route('sales.index')->with('success', 'Sale record added successfully!');
    }

    /**
     * Show the form for editing a sale.
     */
    public function edit(Sale $sale)
    {
        return view('sales.edit', compact('sale'));
    }

    /**
     * Update the specified sale in storage.
     */
    public function update(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'product_name' => 'required|string|max:255',
            'quantity'     => 'required|numeric',
            'price'        => 'required|numeric',
            'date'         => 'required|date',
        ]);

        $sale->update($validated);

        return redirect()->route('sales.index')->with('success', 'Sale record updated successfully!');
    }

    /**
     * Remove the specified sale from storage.
     */
    public function destroy(Sale $sale)
    {
        $sale->delete();
        return redirect()->route('sales.index')->with('success', 'Sale record deleted successfully!');
    }
}
