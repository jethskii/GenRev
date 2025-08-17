<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductRecipeController extends Controller
{
    // GET /products/{product}/materials
    public function index(Product $product)
    {
        $recipes = $product->recipes()->with('ingredient')->get();

        // map materials to fields the blade expects: id, name, unit, default_unit_price
        $ingredients = Material::orderBy('material_name')
            ->get(['id', 'material_name as name', 'unit', 'default_unit_price']);

        return view('materials.recipe', compact('product','recipes','ingredients'));
    }

    // GET /products/{product}/materials/defaults
    public function defaults(Product $product)
    {
        // If recipe already exists, return it
        $existing = $product->recipes()->with('ingredient')->get();
        if ($existing->isNotEmpty()) {
            return response()->json(
                $existing->map(fn($r) => [
                    'ingredient_id' => $r->ingredient_id,
                    'ingredient'    => $r->ingredient?->material_name ?? 'Unknown',
                    'unit'          => $r->ingredient?->unit ?? 'kg',
                    'qty'           => (float) $r->qty,
                    'unit_price'    => (float) $r->unit_price_snapshot,
                ])
            );
        }

        // Otherwise, propose defaults (adjust names to match your materials table!)
        $defaultNames = [
            ['name' => 'Ground Meat',   'qty' => 0.80],
            ['name' => 'Fat',           'qty' => 0.20],
            ['name' => 'Salt',          'qty' => 0.015],
            ['name' => 'Garlic',        'qty' => 0.010],
            ['name' => 'Paprika',       'qty' => 0.008],
            ['name' => 'Casing',        'qty' => 30.00],
        ];

        $rows = [];
        foreach ($defaultNames as $d) {
            $m = Material::where('material_name', $d['name'])->first();
            if ($m) {
                $rows[] = [
                    'ingredient_id' => $m->id,
                    'ingredient'    => $m->material_name,
                    'unit'          => $m->unit,
                    'qty'           => $d['qty'],
                    'unit_price'    => (float) $m->default_unit_price,
                ];
            }
        }
        return response()->json($rows);
    }

    // POST /products/{product}/materials  (bulk upsert)
    public function storeOrUpdate(Product $product, Request $request)
    {
        $data = $request->validate([
            'rows'                  => 'required|array|min:1',
            'rows.*.ingredient_id'  => 'required|exists:materials,id',
            'rows.*.qty'            => 'required|numeric|min:0',
            'rows.*.unit_price'     => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($product, $data) {
            foreach ($data['rows'] as $row) {
                ProductRecipe::updateOrCreate(
                    [
                        'product_id'    => $product->id,
                        'ingredient_id' => $row['ingredient_id'],
                    ],
                    [
                        'qty'                 => $row['qty'],
                        'unit_price_snapshot' => $row['unit_price'],
                    ]
                );
            }
            // sync behavior: remove items not submitted
            $keep = collect($data['rows'])->pluck('ingredient_id')->all();
            ProductRecipe::where('product_id', $product->id)
                ->whereNotIn('ingredient_id', $keep)->delete();
        });

        return back()->with('success', 'Recipe saved!');
    }
}
