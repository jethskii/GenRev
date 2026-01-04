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
     * View: resources/views/products/materials/index.blade.php
     */
    public function index(Product $product, Request $request)
    {
        // ✅ Keep legacy/material columns synced (only for this product)
        $this->syncLegacyColumnsForProduct($product->id);

        $recipe = $product->recipes()
            ->with(['material:id,material_name,unit,unit_price'])
            ->orderBy('id')
            ->get();

        // Materials for dropdown
        $materials = Material::query()
            ->select('id', 'material_name', 'unit')
            ->addSelect(DB::raw('unit_price as default_unit_price'))
            ->orderBy('material_name')
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'ok'        => true,
                'product'   => $product->only(['id', 'product_name', 'unit', 'category']),
                'recipe'    => $recipe,
                'materials' => $materials,
            ]);
        }

        return view('products.materials.index', compact('product', 'recipe', 'materials'));
    }

    /**
     * POST /products/{product}/materials
     *
     * Accepts your Blade payload:
     *  rows[][ingredient_id]           (material id)
     *  rows[][quantity_per_unit]
     *  rows[][wastage_pct]
     *  rows[][unit_price_snapshot]
     *
     * Behavior:
     * - UPSERT each posted line (unique per product + material_id)
     * - DELETE existing lines not included anymore (SYNC)
     * - Recompute product.unit_cost (uses wastage + snapshot)
     */
    public function store(Request $request, Product $product)
    {
        // Allow saving an empty recipe (if user removes all rows)
        $rows = $request->input('rows', []);
        if (!is_array($rows)) $rows = [];

        // Filter out completely empty rows (safety)
        $rows = array_values(array_filter($rows, function ($r) {
            if (!is_array($r)) return false;
            $id = $r['ingredient_id'] ?? $r['material_id'] ?? null;
            return !empty($id);
        }));

        $validated = $request->validate([
            'rows'                             => ['nullable', 'array'],
            'rows.*.ingredient_id'             => ['nullable', 'integer', 'exists:materials,id'],
            'rows.*.material_id'               => ['nullable', 'integer', 'exists:materials,id'],

            'rows.*.quantity_per_unit'         => ['nullable', 'numeric', 'min:0'],
            'rows.*.qty'                       => ['nullable', 'numeric', 'min:0'], // legacy fallback

            'rows.*.wastage_pct'               => ['nullable', 'numeric', 'min:0', 'max:100'],
            'rows.*.unit_price_snapshot'       => ['nullable', 'numeric', 'min:0'],
            'rows.*.unit_price'                => ['nullable', 'numeric', 'min:0'], // fallback
            'rows.*.unit'                      => ['nullable', 'string', 'max:50'],
        ]);

        DB::transaction(function () use ($product, $rows) {

            // ✅ Always sync old records first for this product
            $this->syncLegacyColumnsForProduct($product->id);

            $postedMatIds = [];

            foreach ($rows as $row) {
                $matId = (int) ($row['ingredient_id'] ?? $row['material_id'] ?? 0);
                if ($matId <= 0) continue;

                $postedMatIds[] = $matId;

                // qty base: quantity_per_unit preferred, else qty
                $qtyBase = (float) ($row['quantity_per_unit'] ?? $row['qty'] ?? 0);

                $wastage = (float) ($row['wastage_pct'] ?? 0);

                // snapshot price preferred
                $snap = (float) ($row['unit_price_snapshot'] ?? $row['unit_price'] ?? 0);
                $snap = round($snap, 2);

                // fallback snapshot from materials table
                if ($snap <= 0) {
                    $snap = (float) (Material::whereKey($matId)->value('unit_price') ?? 0);
                }

                $unit = isset($row['unit']) ? trim((string) $row['unit']) : null;
                if ($unit === '' || $unit === null) {
                    $unit = (string) (Material::whereKey($matId)->value('unit') ?? 'kg');
                }

                // ✅ UPSERT key MUST be product_id + material_id
                ProductRecipe::updateOrCreate(
                    [
                        'product_id'  => (int) $product->id,
                        'material_id' => $matId,
                    ],
                    [
                        // keep both columns synced forever
                        'material_id'         => $matId,
                        'ingredient_id'       => $matId,

                        // keep legacy + modern qty synced
                        'quantity_per_unit'   => $qtyBase,
                        'qty'                 => $qtyBase,

                        'wastage_pct'         => $wastage,
                        'unit'                => $unit,
                        'unit_price_snapshot' => $snap,
                    ]
                );
            }

            // ✅ SYNC DELETE: remove any lines not posted anymore
            $postedMatIds = array_values(array_unique($postedMatIds));

            ProductRecipe::where('product_id', $product->id)
                ->when(count($postedMatIds) > 0, fn ($q) => $q->whereNotIn('material_id', $postedMatIds))
                ->when(count($postedMatIds) === 0, fn ($q) => $q) // delete all if none posted
                ->delete();

            // ✅ Update product unit cost
            $product->update([
                'unit_cost' => $this->computeProductUnitCost($product->id),
            ]);
        });

        if ($request->wantsJson()) {
            return response()->json([
                'ok'         => true,
                'message'    => 'Recipe saved.',
                'product_id' => $product->id,
            ]);
        }

        return back()->with('success', 'Saved recipe lines.');
    }

    /**
     * DELETE /products/{product}/materials/{line}
     */
    public function destroy(Product $product, ProductRecipe $line, Request $request)
    {
        if ((int) $line->product_id !== (int) $product->id) {
            abort(404);
        }

        DB::transaction(function () use ($product, $line) {
            $line->delete();
            $product->update([
                'unit_cost' => $this->computeProductUnitCost($product->id),
            ]);
        });

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'message' => 'Line removed.']);
        }

        return back()->with('success', 'Line removed.');
    }

    /**
     * GET /products/{product}/materials/defaults
     * Returns: { ok, from, rows }
     */
    public function defaults(Product $product, Request $request)
    {
        $base = $this->findTemplateProductForDefaults($product);

        if (!$base) {
            return response()->json([
                'ok'   => true,
                'from' => null,
                'rows' => [],
            ]);
        }

        $base->loadMissing(['recipes.material:id,material_name,unit,unit_price']);

        $rows = $base->recipes->map(function (ProductRecipe $r) {
            $matId = (int) ($r->material_id ?? $r->ingredient_id ?? 0);

            return [
                'id'                  => (int) $r->id,
                'material_id'         => $matId,
                'ingredient_id'       => $matId,

                'quantity_per_unit'   => (float) ($r->quantity_per_unit ?? $r->qty ?? 0),
                'qty'                 => (float) ($r->qty ?? $r->quantity_per_unit ?? 0),

                'unit'                => (string) ($r->unit ?? optional($r->material)->unit ?? 'kg'),
                'wastage_pct'         => (float) ($r->wastage_pct ?? 0),

                'unit_price_snapshot' => (float) ($r->unit_price_snapshot ?? optional($r->material)->unit_price ?? 0),
                'default_unit_price'  => (float) (optional($r->material)->unit_price ?? 0),
                'material_name'       => (string) (optional($r->material)->material_name ?? ''),
            ];
        })->values();

        return response()->json([
            'ok'   => true,
            'from' => [
                'id'   => (int) $base->id,
                'name' => (string) $base->product_name,
            ],
            'rows' => $rows,
        ]);
    }

    /* =========================================================
     * INTERNAL HELPERS
     * ======================================================= */

    private function computeProductUnitCost(int $productId): float
    {
        $lines = ProductRecipe::with('material:id,unit_price')
            ->where('product_id', $productId)
            ->get();

        $total = 0.0;

        foreach ($lines as $r) {
            $baseQty = (float) ($r->quantity_per_unit ?? $r->qty ?? 0);
            $wastage = (float) ($r->wastage_pct ?? 0);
            $effQty  = $baseQty * (1 + ($wastage / 100));

            $snap = (float) ($r->unit_price_snapshot ?? 0);
            if ($snap <= 0) {
                $snap = (float) (optional($r->material)->unit_price ?? 0);
            }

            $total += ($effQty * $snap);
        }

        return round($total, 2);
    }

    /**
     * ✅ Ensures material_id and ingredient_id are always filled together.
     * Runs fast and only for the current product.
     */
    private function syncLegacyColumnsForProduct(int $productId): void
    {
        DB::table('product_recipes')
            ->where('product_id', $productId)
            ->whereNull('material_id')
            ->whereNotNull('ingredient_id')
            ->update(['material_id' => DB::raw('ingredient_id')]);

        DB::table('product_recipes')
            ->where('product_id', $productId)
            ->whereNull('ingredient_id')
            ->whereNotNull('material_id')
            ->update(['ingredient_id' => DB::raw('material_id')]);
    }

    protected function findTemplateProductForDefaults(Product $product): ?Product
    {
        if ($product->recipes()->exists()) return $product;

        if ($product->parent_id) {
            $parent = Product::whereKey($product->parent_id)->whereHas('recipes')->first();
            if ($parent) return $parent;

            $sibling = Product::where('parent_id', $product->parent_id)
                ->where('id', '<>', $product->id)
                ->whereHas('recipes')
                ->orderBy('id')
                ->first();

            if ($sibling) return $sibling;
        }

        if (method_exists($product, 'children')) {
            $child = $product->children()->whereHas('recipes')->orderBy('id')->first();
            if ($child) return $child;
        }

        if (!empty($product->category)) {
            $cat = Product::where('category', $product->category)
                ->where('id', '<>', $product->id)
                ->whereHas('recipes')
                ->orderBy('id')
                ->first();

            if ($cat) return $cat;
        }

        $baseName = trim(preg_replace('/\s*\(.*\)$/', '', (string) $product->product_name));
        if ($baseName !== '') {
            $similar = Product::where('id', '<>', $product->id)
                ->whereHas('recipes')
                ->where(function ($q) use ($baseName) {
                    $q->where('product_name', 'LIKE', $baseName . '%')
                      ->orWhere('product_name', 'LIKE', '%' . $baseName . '%');
                })
                ->orderBy('id')
                ->first();

            if ($similar) return $similar;
        }

        return null;
    }
}
