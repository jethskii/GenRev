<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductRecipeController extends Controller
{
    /**
     * GET /products/{product}/materials
     * Render the per-product BOM editor.
     */
    public function index(Product $product)
    {
        // Current recipe lines with their material loaded
        $recipe = $product->recipes()
            ->with(['material:id,material_name,unit'])
            ->orderBy('id')
            ->get();

        // Materials for the dropdown; expose unit_price as default_unit_price
        $materials = Material::query()
            ->select('id', 'material_name', 'unit')
            ->addSelect(DB::raw('unit_price as default_unit_price'))
            ->orderBy('material_name')
            ->get();

        return view('products.materials.index', compact('product', 'recipe', 'materials'));
    }

    /**
     * POST /products/{product}/materials
     * Accepts multiple rows: rows[n][material_id|qty|unit_price|unit]
     */
    public function store(Request $request, Product $product)
    {
        $validated = $request->validate([
            'rows'                      => ['required','array','min:1'],
            'rows.*.material_id'        => ['required','integer','exists:materials,id'],
            'rows.*.qty'                => ['required','numeric','min:0'],
            'rows.*.unit_price'         => ['required','numeric','min:0'],
            'rows.*.unit'               => ['nullable','string','max:20'],
        ]);

        $now = now();

        $payload = collect($validated['rows'])->map(function ($r) use ($product, $now) {
            return [
                'product_id'          => $product->id,
                'material_id'         => (int) $r['material_id'],
                'qty'                 => (float) $r['qty'],
                'unit'                => $r['unit'] ?? null,
                'unit_price_snapshot' => round((float) $r['unit_price'], 2),
                'created_at'          => $now,
                'updated_at'          => $now,
            ];
        })->values();

        if ($payload->isEmpty()) {
            return back()->withErrors(['rows' => 'Please add at least one complete line.'])->withInput();
        }

        DB::transaction(function () use ($payload) {
            ProductRecipe::insert($payload->all());
        });

        return back()->with('success', 'Saved '.$payload->count().' line(s).');
    }

    /**
     * DELETE /products/{product}/materials/{line}
     * (Use this if your route is pointed here; otherwise keep your existing ProductController@recipeDestroy.)
     */
    public function destroy(Product $product, ProductRecipe $line)
    {
        if ((int) $line->product_id !== (int) $product->id) {
            abort(404);
        }
        $line->delete();

        return back()->with('success', 'Line removed.');
    }

    /**
     * Optional defaults endpoint if you consume it elsewhere
     * GET /products/{product}/materials/defaults
     */
    public function defaults(Product $product)
    {
        return response()->json([
            'unit'  => $product->unit ?? 'kg',
            'price' => (float) ($product->default_price ?? 0),
        ]);
    }
}
