<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Production;
use Carbon\Carbon;

class ProductionController extends Controller
{
    /**
     * Display all production records and summary data.
     */
    public function index(Request $request)
    {
        // Optional product name search
        $products = Production::when($request->search, function ($query) use ($request) {
            $query->where('product_name', 'like', '%' . $request->search . '%');
        })->get();

        // Filter out invalid (negative) forecast values
        $validProducts = $products->filter(function ($product) {
            return $product->forecasted_demand >= 0;
        });

        // Summary calculations
        $forecastedDemand = $validProducts->sum('forecasted_demand');
        $actualInventory = $validProducts->sum('current_inventory');
        $shortfall = max($forecastedDemand - $actualInventory, 0);
        $recommendedProduction = $shortfall;

        return view('production.index', compact(
            'products',
            'forecastedDemand',
            'actualInventory',
            'shortfall',
            'recommendedProduction'
        ));
    }

    /**
     * Store a new production record.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_name'        => 'required|string|max:255',
            'forecasted_demand'   => 'required|numeric|min:0',
            'current_inventory'   => 'required|numeric|min:0',
            'unit_cost'           => 'required|numeric|min:0',
            'production_date'     => 'required|date',
        ]);

        Production::create($validated);

        return redirect()->route('production.index')->with('success', 'Production record added successfully.');
    }

    /**
     * Show the edit form for a production record.
     */
            public function edit($id)
        {
            $product = Production::findOrFail($id);
            return view('production.edit', compact('product'));
        }


    /**
     * Update an existing production record.
     */
        public function update(Request $request, $id)
    {
        $request->validate([
            'product_name' => 'required|string',
            'forecasted_demand' => 'required|numeric',
            'current_inventory' => 'required|numeric',
            'unit_cost' => 'required|numeric',
            'production_date' => 'required|date',
        ]);

        $production = Production::findOrFail($id);
        $production->update($request->all());

        return redirect()->route('production.index')->with('success', 'Production record updated.');
    }

    /**
     * Delete a production record.
     */
    public function destroy(Production $production)
    {
        $production->delete();

        return redirect()->route('production.index')->with('success', 'Production record deleted.');
    }

    /**
     * Export production data to PDF or Excel.
     */
    public function export($format)
    {
        // Placeholder for PDF/Excel export logic
        return back()->with('success', strtoupper($format) . ' export not yet implemented.');
    }
}
