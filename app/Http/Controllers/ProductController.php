<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Material;
use App\Models\ProductRecipe; // pivot table: product_id, material_id, quantity, unit, cost
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Show all products (with recipes count).
     */
    public function index()
    {
        $products = Product::withCount('recipes')->get();
        return view('products.index', compact('products'));
    }

    /**
     * Show a single product with its recipe (ingredients list).
     */
    public function show(Product $product)
    {
        // eager load ingredients with pivot data
        $product->load(['recipes.material']);

        $allMaterials = Material::orderBy('name')->get();
        return view('products.show', compact('product', 'allMaterials'));
    }

    /**
     * Store a new product.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:255|unique:products,name',
            'image' => 'nullable|image|max:2048',
        ]);

        $product = new Product();
        $product->name = $validated['name'];

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $product->image_path = $path;
        }

        $product->save();

        return redirect()->route('products.index')->with('success', 'Product added.');
    }

    /**
     * Archive a product (soft deactivate).
     */
    public function archive($id)
    {
        $product = Product::findOrFail($id);
        $product->archived = true;
        $product->save();

        return back()->with('success', 'Product archived.');
    }

    /**
     * Update product image.
     */
    public function updateImage(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate(['image' => 'required|image|max:2048']);

        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $path = $request->file('image')->store('products', 'public');
        $product->image_path = $path;
        $product->save();

        return back()->with('success', 'Image updated.');
    }

    /**
     * Delete product permanently.
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }
        $product->delete();

        return redirect()->route('products.index')->with('success', 'Product deleted.');
    }

    /**
     * Quick-store (used by modals).
     */
    public function quickStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:products,name',
        ]);

        $product = Product::create(['name' => $request->name]);

        return response()->json(['id' => $product->id, 'name' => $product->name]);
    }

    /* ==================== RECIPE / BOM FUNCTIONS ==================== */

    /**
     * Add or update product recipe (ingredients).
     */
    public function updateRecipe(Request $request, Product $product)
    {
        $request->validate([
            'materials'   => 'required|array',
            'materials.*.id'     => 'required|exists:materials,id',
            'materials.*.qty'    => 'required|numeric|min:0.01',
            'materials.*.unit'   => 'required|string|max:50',
            'materials.*.cost'   => 'required|numeric|min:0',
        ]);

        // Clear existing recipe
        $product->recipes()->delete();

        foreach ($request->materials as $mat) {
            ProductRecipe::create([
                'product_id'  => $product->id,
                'material_id' => $mat['id'],
                'quantity'    => $mat['qty'],
                'unit'        => $mat['unit'],
                'cost'        => $mat['cost'],
            ]);
        }

        return back()->with('success', 'Recipe updated.');
    }
}
