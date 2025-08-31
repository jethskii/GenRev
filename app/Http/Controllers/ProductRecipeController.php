<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductRecipeController extends Controller
{
    /**
     * GET /products/{product}/materials
     * Render the per-product BOM editor.
     */
    public function index(Product $product)
{
    $recipe = $product->recipes()->with('ingredient')->get();

    $materials = Material::orderBy('material_name')
        ->get(['id', 'material_name', 'unit', 'default_unit_price']);

    // ✅ Point to the correct Blade
    return view('products.materials.index', compact('product','recipe','materials'));
}


    /**
     * GET /products/{product}/materials/defaults
     * Returns JSON seed for "Load Defaults" (either current recipe, or heuristic defaults).
     */
    public function defaults(Product $product)
    {
        $existing = $product->recipes()->with('material')->get();

        if ($existing->isNotEmpty()) {
            // Return the current recipe in the JSON shape your JS expects
            return response()->json(
                $existing->map(fn ($r) => [
                    'ingredient_id' => (int) ($r->material_id ?? $r->ingredient_id),
                    'unit'          => (string) ($r->material->unit ?? ''),
                    'qty'           => (float)  ($r->qty ?? 0),
                    'unit_price'    => (float)  ($r->unit_price_snapshot ?? 0),
                ])->values()
            );
        }

        // Otherwise propose defaults by matching names in materials table
        // Tweak these names/quantities to fit your products
        $defaultNames = [
            ['name' => 'Ground Meat', 'qty' => 0.80],
            ['name' => 'Fat',         'qty' => 0.20],
            ['name' => 'Salt',        'qty' => 0.015],
            ['name' => 'Garlic',      'qty' => 0.010],
            ['name' => 'Paprika',     'qty' => 0.008],
            ['name' => 'Casing',      'qty' => 30.00], // if pcs or g, make sure material.unit matches
        ];

        $rows = [];
        foreach ($defaultNames as $d) {
            $m = Material::where('material_name', $d['name'])->first();
            if ($m) {
                $rows[] = [
                    'ingredient_id' => (int) $m->id,
                    'unit'          => (string) $m->unit,
                    'qty'           => (float)  $d['qty'],
                    'unit_price'    => (float)  $m->default_unit_price,
                ];
            }
        }

        return response()->json($rows);
    }

    /**
     * POST /products/{product}/materials
     * Bulk upsert + sync (any line not submitted is removed).
     * Expected payload:
     * rows: [
     *   ['ingredient_id' => int, 'qty' => number, 'unit_price' => number],
     *   ...
     * ]
     *
     * NOTE: Your materials Blade currently posts to route('products.recipe.store', $product)
     * which points to ProductController@recipeStore. Keeping this here allows either route to be used:
     * - POST /products/{product}/recipe        (ProductController@recipeStore)
     * - POST /products/{product}/materials     (this method)
     */
    public function store(Request $request, Product $product)
    {
        $data = $request->validate([
            'rows'                  => ['required', 'array', 'min:1'],
            'rows.*.ingredient_id'  => ['required', 'integer', Rule::exists('materials', 'id')],
            'rows.*.qty'            => ['required', 'numeric', 'min:0'],
            'rows.*.unit_price'     => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($product, $data) {
            // Upsert each submitted line
            foreach ($data['rows'] as $row) {
                ProductRecipe::updateOrCreate(
                    [
                        'product_id'    => (int) $product->id,
                        'ingredient_id' => (int) $row['ingredient_id'], // legacy column; model maps material_id too
                    ],
                    [
                        'qty'                 => (float) $row['qty'],
                        'unit_price_snapshot' => (float) $row['unit_price'],
                    ]
                );
            }

            // Sync behavior: remove recipe lines that were not submitted
            $keep = collect($data['rows'])->pluck('ingredient_id')->map(fn ($v) => (int) $v)->all();

            ProductRecipe::where('product_id', $product->id)
                ->whereNotIn('ingredient_id', $keep)
                ->delete();
        });

        return back()->with('success', 'Recipe saved!');
    }
}
