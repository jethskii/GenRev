<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\ProductRecipe;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema;

class MaterialController extends Controller
{
    /** Keep UI + backend in sync */
    private const ALLOWED_UNITS = [
        'kg','g','lbs','pcs','pkg','box','bag','roll','tray','lt','ml','m3'
    ];

    /* ------------------------- LIST / INDEX ------------------------- */

    /**
     * List materials with optional search/sort/category and pagination.
     * Use ?per_page=all to return a plain collection.
     */
    public function index(Request $request)
    {
        $search   = trim((string) $request->get('search', ''));
        $sort     = (string) $request->get('sort', 'name_asc'); // name_asc|name_desc|qty_desc|qty_asc|price_desc|price_asc|updated_desc|updated_asc
        $perPage  = $request->get('per_page', 50);              // "all" or number
        $category = $request->get('category');                  // optional filter

        $q = Material::query()
            ->when($search !== '', function ($qq) use ($search) {
                $qq->where(function ($w) use ($search) {
                    $w->where('material_name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            // Safe if column not present during DB switches
            ->when($category && Schema::hasColumn('materials', 'category'), function ($qq) use ($category) {
                $qq->where('category', $category);
            });

        $map = [
            'name_asc'     => ['material_name', 'asc'],
            'name_desc'    => ['material_name', 'desc'],
            'qty_desc'     => ['quantity_kg', 'desc'],
            'qty_asc'      => ['quantity_kg', 'asc'],
            'price_desc'   => ['unit_price', 'desc'],
            'price_asc'    => ['unit_price', 'asc'],
            'updated_desc' => ['updated_at', 'desc'],
            'updated_asc'  => ['updated_at', 'asc'],
        ];
        if (!array_key_exists($sort, $map)) $sort = 'name_asc';
        [$col, $dir] = $map[$sort];

        // Only orderBy if column exists (guards SQLite/MySQL flips)
        if ($col === 'updated_at' || Schema::hasColumn('materials', $col)) {
            $q->orderBy($col, $dir);
        } else {
            $q->orderBy('material_name', 'asc');
        }

        if (is_string($perPage) && strtolower($perPage) === 'all') {
            $materials = $q->get();
        } else {
            $perPage   = max(1, (int) $perPage);
            $materials = $q->paginate($perPage)->appends($request->query());
        }

        return view('materials.index', compact('materials'));
    }

    /* ------------------------- CREATE / STORE ------------------------- */

    public function create()
    {
        return view('materials.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateMaterial($request);

        // Mutators on the model will normalize values (₱, commas, etc.)
        $mat = Material::create($data);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'ok'       => true,
                'message'  => 'Material added!',
                'material' => $this->toApi($mat),
            ]);
        }

        return redirect()->route('materials.index')->with('success', 'Material added!');
    }

    /* ------------------------- EDIT / UPDATE ------------------------- */

    public function edit(Request $request, $id)
    {
        $material = Material::query()->findOrFail($id);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($this->toApi($material));
        }

        return view('materials.edit', compact('material'));
    }

    public function update(Request $request, $id)
    {
        $material = Material::findOrFail($id);

        $data = $this->validateMaterial($request, $material->id);

        // Mutators on the model will normalize values
        $material->fill($data)->save();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'ok'       => true,
                'message'  => 'Material updated!',
                'material' => $this->toApi($material),
            ]);
        }

        return redirect()->route('materials.index')->with('success', 'Material updated!');
    }

    /* ------------------------- DELETE ------------------------- */

    public function destroy(Request $request, $id)
    {
        $material = Material::findOrFail($id);

        $inUse = \App\Models\ProductRecipe::where('ingredient_id', $material->id)
            ->orWhere('material_id', $material->id)
            ->exists();

        if ($inUse) {
            $msg = 'Cannot delete: this material is used in one or more product recipes.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => $msg], 409);
            }
            return redirect()->route('materials.index')->with('error', $msg);
        }

        $material->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['ok' => true, 'message' => 'Material deleted!']);
        }

        return redirect()->route('materials.index')->with('success', 'Material deleted!');
    }

    /* ------------------------- VALIDATION ------------------------- */

    /**
     * Keep validation permissive for currency-like strings.
     * Model mutators handle the actual normalization/clamping.
     */
    protected function validateMaterial(Request $request, ?int $materialId = null): array
    {
        return $request->validate([
            'material_name' => [
                'required', 'string', 'max:255',
                Rule::unique('materials', 'material_name')->ignore($materialId),
            ],
            // Optional; don't fail if column not present
            'category'      => [Schema::hasColumn('materials', 'category') ? 'nullable' : 'sometimes', 'string', 'max:100'],
            'unit'          => ['required', Rule::in(self::ALLOWED_UNITS)],

            // Accept any non-empty string; model mutator will parse ₱1,234.56 etc.
            'unit_price'    => ['required'],

            // Accept numeric-like strings; model mutator will parse/clamp
            'quantity_kg'   => ['required'],
            'min_stock_kg'  => ['nullable'],

            'sku'           => [
                'nullable', 'string', 'max:120',
                Rule::unique('materials', 'sku')->ignore($materialId),
            ],
        ]);
    }

    /* ------------------------- HELPERS ------------------------- */

    /** Standardize JSON shape for modals / AJAX */
    private function toApi(Material $m): array
    {
        return [
            'id'            => $m->id,
            'material_name' => $m->material_name,
            'category'      => Schema::hasColumn('materials', 'category') ? $m->category : null,
            'unit'          => $m->unit,
            'unit_price'    => (float)$m->unit_price,
            'quantity_kg'   => (float)$m->quantity_kg,
            'min_stock_kg'  => $m->min_stock_kg !== null ? (float)$m->min_stock_kg : null,
            'sku'           => $m->sku,
            'updated_at'    => optional($m->updated_at)->toDateTimeString(),
        ];
    }
}
