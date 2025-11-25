<?php

declare(strict_types=1);

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
     * View: resources/views/products/materials/index.blade.php
     */
    public function index(Product $product, Request $request)
    {
        // Current recipe lines with their material loaded
        $recipe = $product->recipes()
            ->with(['material:id,material_name,unit,unit_price'])
            ->orderBy('id')
            ->get();

        // Materials for the dropdown; expose unit_price as default_unit_price
        $materials = Material::query()
            ->select('id', 'material_name', 'unit')
            ->addSelect(DB::raw('unit_price as default_unit_price'))
            ->orderBy('material_name')
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'ok'        => true,
                'product'   => $product->only(['id','product_name','unit','category']),
                'recipe'    => $recipe,
                'materials' => $materials,
            ]);
        }

        return view('products.materials.index', compact('product', 'recipe', 'materials'));
    }

    /**
     * POST /products/{product}/materials
     *
     * Accepts multiple rows from the BOM add-lines form:
     *   rows[n][material_id]
     *   rows[n][qty]
     *   rows[n][unit_price]
     *   rows[n][unit] (optional)
     *
     * We:
     * - upsert by (product_id, ingredient_id/material_id)
     * - keep legacy columns in sync (ingredient_id, qty)
     * - update product.unit_cost based on all recipe lines.
     */
    public function store(Request $request, Product $product)
    {
        $validated = $request->validate([
            'rows'               => ['required','array','min:1'],
            'rows.*.material_id' => ['required','integer','exists:materials,id'],
            'rows.*.qty'         => ['required','numeric','min:0'],
            'rows.*.unit_price'  => ['required','numeric','min:0'],
            'rows.*.unit'        => ['nullable','string','max:20'],
        ]);

        DB::transaction(function () use ($validated, $product) {
            foreach ($validated['rows'] as $row) {
                $matId = (int) $row['material_id'];
                $qty   = (float) $row['qty'];
                $snap  = round((float) $row['unit_price'], 2);
                $unit  = isset($row['unit']) ? trim((string) $row['unit']) : null;

                // Fallback snapshot from Material if 0
                if ($snap === 0.0) {
                    $snap = (float) (Material::whereKey($matId)->value('unit_price') ?? 0);
                }

                $payload = [
                    // legacy & modern columns kept in sync
                    'material_id'         => $matId,
                    'ingredient_id'       => $matId,
                    'qty'                 => $qty,
                    'quantity_per_unit'   => $qty,
                    'unit_price_snapshot' => $snap,
                ];

                if (!is_null($unit)) {
                    $payload['unit'] = $unit;
                }

                ProductRecipe::updateOrCreate(
                    [
                        'product_id'    => (int) $product->id,
                        'ingredient_id' => $matId,
                    ],
                    $payload
                );
            }

            // Recompute product.unit_cost from all recipe lines
            $totalCost = ProductRecipe::with('material:id,unit_price')
                ->where('product_id', $product->id)
                ->get()
                ->sum(function (ProductRecipe $r) {
                    $qty = method_exists($r, 'getQtyEffectiveAttribute')
                        ? $r->qty_effective
                        : (float) ($r->quantity_per_unit ?? $r->qty ?? 0);

                    $snap = $r->unit_price_snapshot ?: (float) ($r->material->unit_price ?? 0);
                    return round($qty * (float) $snap, 2);
                });

            $product->update(['unit_cost' => $totalCost]);
        });

        if ($request->wantsJson()) {
            return response()->json([
                'ok'         => true,
                'message'    => 'Recipe lines saved.',
                'product_id' => $product->id,
            ]);
        }

        return back()->with('success', 'Saved recipe lines.');
    }

    /**
     * DELETE /products/{product}/materials/{line}
     * Used by products.materials.destroy
     */
    public function destroy(Product $product, ProductRecipe $line, Request $request)
    {
        if ((int) $line->product_id !== (int) $product->id) {
            abort(404);
        }

        $line->delete();

        // Recalculate unit_cost after deletion
        $totalCost = ProductRecipe::with('material:id,unit_price')
            ->where('product_id', $product->id)
            ->get()
            ->sum(function (ProductRecipe $r) {
                $qty = method_exists($r, 'getQtyEffectiveAttribute')
                    ? $r->qty_effective
                    : (float) ($r->quantity_per_unit ?? $r->qty ?? 0);

                $snap = $r->unit_price_snapshot ?: (float) ($r->material->unit_price ?? 0);
                return round($qty * (float) $snap, 2);
            });

        $product->update(['unit_cost' => $totalCost]);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'message' => 'Line removed.']);
        }

        return back()->with('success', 'Line removed.');
    }

    /**
     * GET /products/{product}/materials/defaults
     *
     * Used by "Load Defaults" button in the BOM view.
     *
     * Logic:
     * 1. If this product already has a recipe → use that.
     * 2. Else try:
     *    - parent with recipe
     *    - siblings (variants) with recipe
     *    - children with recipe
     *    - another product in same category with recipe
     *    - similar-name product (same base name) with recipe
     *
     * Returns an array of rows compatible with your JS loader:
     *   [
     *     {
     *       ingredient_id,
     *       material_id,
     *       unit,
     *       qty,
     *       quantity_per_unit,
     *       wastage_pct,
     *       unit_price_snapshot,
     *       unit_price,
     *       default_unit_price
     *     },
     *     ...
     *   ]
     */
    public function defaults(Product $product)
{
    // Try to find a "base" product whose recipe we can reuse:
    // 1) Parent product (if this is a variant)
    // 2) A sibling variant that already has a recipe
    // 3) Any other product in the same category with a recipe
    $base = null;

    // 1) Parent
    if ($product->parent_id) {
        $base = Product::with(['recipes.material'])
            ->where('id', $product->parent_id)
            ->whereHas('recipes')
            ->first();
    }

    // 2) Sibling variant (same parent, different id)
    if (! $base && $product->parent_id) {
        $base = Product::with(['recipes.material'])
            ->where('parent_id', $product->parent_id)
            ->where('id', '<>', $product->id)
            ->whereHas('recipes')
            ->orderBy('id')
            ->first();
    }

    // 3) Same category
    if (! $base && $product->category) {
        $base = Product::with(['recipes.material'])
            ->where('category', $product->category)
            ->where('id', '<>', $product->id)
            ->whereHas('recipes')
            ->orderBy('id')
            ->first();
    }

    // If nothing found, just return empty rows
    if (! $base) {
        return response()->json([
            'from' => null,
            'rows' => [],
        ]);
    }

    // Build rows payload from base recipe
    $rows = $base->recipes->map(function (ProductRecipe $r) {
        return [
            'id'                  => $r->id,
            'material_id'         => $r->material_id ?? $r->ingredient_id,
            'ingredient_id'       => $r->ingredient_id ?? $r->material_id,
            'quantity_per_unit'   => (float) ($r->quantity_per_unit ?? $r->qty ?? 0),
            'qty'                 => (float) ($r->qty ?? $r->quantity_per_unit ?? 0),
            'unit'                => $r->unit,
            'wastage_pct'         => (float) ($r->wastage_pct ?? 0),
            'unit_price_snapshot' => (float) ($r->unit_price_snapshot ?? $r->unit_price ?? 0),
            'default_unit_price'  => (float) optional($r->material)->unit_price,
            'material_name'       => optional($r->material)->material_name,
        ];
    })->values();

    return response()->json([
        'from' => [
            'id'   => $base->id,
            'name' => $base->product_name,
        ],
        'rows' => $rows,
    ]);
}

    /* ======================================================================
     * INTERNAL HELPERS
     * ====================================================================*/

    /**
     * Find a "template" product whose recipe we should use as defaults for
     * the given product.
     *
     * This is where your "same type / same variant auto record" logic lives:
     * - Prefer this product's own recipe.
     * - Then parent.
     * - Then siblings (same parent_id).
     * - Then children.
     * - Then same category.
     * - Then similar-name product.
     */
    protected function findTemplateProductForDefaults(Product $product): ?Product
    {
        // 1) If this product already has a recipe, just use it.
        if ($product->recipes()->exists()) {
            return $product;
        }

        // 2) Parent with recipe
        if ($product->parent_id) {
            $parent = Product::withCount('recipes')->find($product->parent_id);
            if ($parent && $parent->recipes_count > 0) {
                return $parent;
            }

            // 3) Siblings (same parent_id) with recipe
            $sibling = Product::where('parent_id', $product->parent_id)
                ->where('id', '<>', $product->id)
                ->whereHas('recipes')
                ->orderBy('id')
                ->first();

            if ($sibling) {
                return $sibling;
            }
        }

        // 4) Children with recipe (if this is the parent / base product)
        $child = $product->children()
            ->whereHas('recipes')
            ->orderBy('id')
            ->first();

        if ($child) {
            return $child;
        }

        // 5) Another product in same category with recipe
        if (!empty($product->category)) {
            $catProduct = Product::where('category', $product->category)
                ->where('id', '<>', $product->id)
                ->whereHas('recipes')
                ->orderBy('id')
                ->first();

            if ($catProduct) {
                return $catProduct;
            }
        }

        // 6) Similar-name product with recipe
        $baseName = trim(preg_replace('/\s*\(.*\)$/', '', (string) $product->product_name));
        if ($baseName !== '') {
            $similar = Product::where('id', '<>', $product->id)
                ->whereHas('recipes')
                ->where(function ($q) use ($baseName) {
                    $q->where('product_name', 'LIKE', $baseName.'%')
                      ->orWhere('product_name', 'LIKE', '%'.$baseName.'%');
                })
                ->orderBy('id')
                ->first();

            if ($similar) {
                return $similar;
            }
        }

        // No suitable template found
        return null;
    }
}
