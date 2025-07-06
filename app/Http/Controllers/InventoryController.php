<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index()
    {
        $products = Inventory::orderBy('production_date', 'desc')->get();

        // Add static materials data
        $materials = [
            (object)[ 'name' => 'Pork', 'quantity' => 314, 'status' => 'In Stock' ],
            (object)[ 'name' => 'Chicken', 'quantity' => 283, 'status' => 'In Stock' ],
            (object)[ 'name' => 'Beef', 'quantity' => 192, 'status' => 'In Stock' ],
            (object)[ 'name' => 'Spices', 'quantity' => 275, 'status' => 'In Stock' ],
            (object)[ 'name' => 'Sugar', 'quantity' => 84, 'status' => 'In Stock' ],
            (object)[ 'name' => 'Starch', 'quantity' => 59, 'status' => 'In Stock' ],
            (object)[ 'name' => 'Phosphate', 'quantity' => 43, 'status' => 'In Stock' ],
            (object)[ 'name' => 'Sodium Nitrate', 'quantity' => 39, 'status' => 'In Stock' ],
            (object)[ 'name' => 'Sodium Erythorbate', 'quantity' => 47, 'status' => 'In Stock' ],
        ];

        return view('inventory', compact('products', 'materials'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_name' => 'required|string|max:255',
            'batch_number' => 'required|string|max:100',
            'production_date' => 'required|date',
            'quantity' => 'required|integer|min:0',
            'stock_status' => 'required|string|in:In Stock,Out of Stock,Low Stock',
        ]);

        Inventory::create($validated);

        return redirect()->route('inventory')->with('success', 'Product added successfully.');
    }

    public function edit($id)
    {
        $inventory = Inventory::findOrFail($id);
        return response()->json($inventory);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'product_name' => 'required|string|max:255',
            'batch_number' => 'required|string|max:100',
            'production_date' => 'required|date',
            'quantity' => 'required|integer|min:0',
            'stock_status' => 'required|string|in:In Stock,Out of Stock,Low Stock',
        ]);

        $inventory = Inventory::findOrFail($id);
        $inventory->update($validated);

        return redirect()->route('inventory')->with('success', 'Product updated successfully.');
    }
}
