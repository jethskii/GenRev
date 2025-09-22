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
        // Load recipe lines + their material; include price alias for blades
        $recipe = $product->recipes()
            ->with(['material' => function ($q) {
                $q->select('id', 'material_name', 'unit')
                  ->addSelect(DB::raw('unit_price as default_unit_price'));
            }])
            ->get();

        // Materials for the "Add line" dropdown (alias price)
        $materials = Material::query()
            ->select('id', 'material_name', 'unit')
            ->addSelect(DB::raw('unit_price as default_unit_price'))
            ->orderBy('material_name')
            ->get();

        return view('products.materials.index', compact('product', 'recipe', 'materials'));
    }

    /**
     * GET /products/{product}/materials/defaults
     * Returns JSON seed for "Load Defaults".
     */
    public function defaults(Product $product)
    {
        $existing = $product->recipes()
            ->with(['material' => function ($q) {
                $q->select('id', 'unit')
                  ->addSelect(DB::raw('unit_price as default_unit_price'));
            }])
            ->get();

        if ($existing->isNotEmpty()) {
            return response()->json(
                $existing->map(fn ($r) => [
                    'ingredient_id' => (int) ($r->material_id ?? $r->ingredient_id),
                    'unit'          => (string) ($r->material->unit ?? ''),
                    'qty'           => (float)  ($r->qty ?? 0),
                    // Prefer stored snapshot; fallback to current material price
                    'unit_price'    => (float)  ($r->unit_price_snapshot ?? ($r->material->default_unit_price ?? 0)),
                ])->values()
            );
        }

        // Example heuristic defaults (adjust to your needs)
        $defaultNames = [
            ['name' => 'Ground Meat', 'qty' => 0.80],
            ['name' => 'Fat',         'qty' => 0.20],
            ['name' => 'Salt',        'qty' => 0.015],
            ['name' => 'Garlic',      'qty' => 0.010],
            ['name' => 'Paprika',     'qty' => 0.008],
            ['name' => 'Casing',      'qty' => 30.00],
        ];

        $rows = [];
        foreach ($defaultNames as $d) {
            $m = Material::where('material_name', $d['name'])->first();
            if ($m) {
                $rows[] = [
                    'ingredient_id' => (int) $m->id,
                    'unit'          => (string) $m->unit,
                    'qty'           => (float)  $d['qty'],
                    'unit_price'    => (float)  $m->unit_price, // current price
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
            foreach ($data['rows'] as $row) {
                $matId = (int) $row['ingredient_id'];

                // Upsert on (product_id, ingredient_id). Also populate material_id
                ProductRecipe::updateOrCreate(
                    [
                        'product_id'    => (int) $product->id,
                        'ingredient_id' => $matId,
                    ],
                    [
                        'material_id'         => $matId, // ✅ prevents 1364 error
                        'qty'                 => (float) $row['qty'],
                        'unit_price_snapshot' => (float) $row['unit_price'],
                    ]
                );
            }

            // Sync: delete lines not submitted
            $keep = collect($data['rows'])->pluck('ingredient_id')->map(fn ($v) => (int) $v)->all();

            ProductRecipe::where('product_id', $product->id)
                ->whereNotIn('ingredient_id', $keep)
                ->delete();
        });

        return back()->with('success', 'Recipe saved!');
    }
}
