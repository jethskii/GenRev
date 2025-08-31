<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\ProductRecipe;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MaterialController extends Controller
{
    /**
     * List materials with optional search/sort and pagination.
     * Keeps compatibility if your view expects a collection (use ?per_page=all).
     */
    public function index(Request $request)
    {
        $search   = trim((string) $request->get('search', ''));
        $sort     = (string) $request->get('sort', 'name_asc'); // name_asc|name_desc|qty_desc|qty_asc|price_desc|price_asc|updated_desc|updated_asc
        $perPage  = $request->get('per_page', 50);              // "all" or number

        $q = Material::query()
            ->when($search !== '', function ($qq) use ($search) {
                $qq->where(function ($w) use ($search) {
                    $w->where('material_name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            });

        // Sorting map
        $map = [
            'name_asc'     => ['material_name', 'asc'],
            'name_desc'    => ['material_name', 'desc'],
            'qty_desc'     => ['quantity_kg', 'desc'],
            'qty_asc'      => ['quantity_kg', 'asc'],
            'price_desc'   => ['default_unit_price', 'desc'],
            'price_asc'    => ['default_unit_price', 'asc'],
            'updated_desc' => ['updated_at', 'desc'],
            'updated_asc'  => ['updated_at', 'asc'],
        ];
        if (!isset($map[$sort])) $sort = 'name_asc';
        [$col, $dir] = $map[$sort];
        $q->orderBy($col, $dir);

        // Pagination (optional)
        if ($perPage === 'all') {
            $materials = $q->get();
        } else {
            $perPage   = max(1, (int) $perPage);
            $materials = $q->paginate($perPage)->appends($request->query());
        }

        return view('materials.index', compact('materials'));
    }

    /**
     * Create a new material.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'material_name'      => ['required', 'string', 'max:255', Rule::unique('materials', 'material_name')],
            'unit'               => ['required', Rule::in(['kg','g','pcs','lt'])],
            'default_unit_price' => ['nullable', 'numeric', 'min:0'],
            'quantity_kg'        => ['required', 'numeric', 'min:0'],
            'sku'                => ['nullable', 'string', 'max:120', Rule::unique('materials', 'sku')],
        ]);

        // Normalize numbers to float to play nice with decimal casts
        $data['default_unit_price'] = isset($data['default_unit_price']) ? (float) $data['default_unit_price'] : 0.0;
        $data['quantity_kg']        = (float) $data['quantity_kg'];

        Material::create($data);

        return redirect()->route('materials.index')->with('success', 'Material added!');
    }

    /**
     * Return a single material for inline edit (JSON).
     */
    public function edit($id)
    {
        $material = Material::query()->findOrFail($id);

        // Only expose fields needed by the editor
        return response()->json([
            'id'                 => $material->id,
            'material_name'      => $material->material_name,
            'unit'               => $material->unit,
            'default_unit_price' => (float) $material->default_unit_price,
            'quantity_kg'        => (float) $material->quantity_kg,
            'sku'                => $material->sku,
        ]);
    }

    /**
     * Update a material.
     */
    public function update(Request $request, $id)
    {
        $material = Material::findOrFail($id);

        $data = $request->validate([
            'material_name'      => ['required', 'string', 'max:255', Rule::unique('materials', 'material_name')->ignore($material->id)],
            'unit'               => ['required', Rule::in(['kg','g','pcs','lt'])],
            'default_unit_price' => ['nullable', 'numeric', 'min:0'],
            'quantity_kg'        => ['required', 'numeric', 'min:0'],
            'sku'                => ['nullable', 'string', 'max:120', Rule::unique('materials', 'sku')->ignore($material->id)],
        ]);

        $data['default_unit_price'] = isset($data['default_unit_price']) ? (float) $data['default_unit_price'] : 0.0;
        $data['quantity_kg']        = (float) $data['quantity_kg'];

        $material->update($data);

        return redirect()->route('materials.index')->with('success', 'Material updated!');
    }

    /**
     * Delete a material (blocked if it is referenced by any product recipe).
     */
    public function destroy($id)
    {
        $material = Material::findOrFail($id);

        // Prevent deleting materials that are in use (either material_id or legacy ingredient_id)
        $inUse = ProductRecipe::where('ingredient_id', $material->id)
                ->orWhere('material_id', $material->id)
                ->exists();

        if ($inUse) {
            return redirect()
                ->route('materials.index')
                ->with('error', 'Cannot delete: this material is used in one or more product recipes.');
        }

        $material->delete();

        return redirect()->route('materials.index')->with('success', 'Material deleted!');
    }
}
