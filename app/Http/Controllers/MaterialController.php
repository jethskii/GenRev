<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class MaterialController extends Controller
{
    /**
     * Display a listing of materials with optional search, sort, and low-stock filter.
     * Route: GET /materials
     */
    public function index(Request $request)
    {
        $search   = trim((string) $request->query('q', ''));
        $sort     = trim((string) $request->query('sort', 'name_asc'));
        $onlyLow  = (bool) $request->boolean('low', false);
        $perPage  = (int) $request->integer('per_page', 15);

        $query = Material::query()
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($onlyLow, fn ($q) => $q->lowStock())
            ->sortBy($sort);

        $materials = $query->paginate(max(1, $perPage))->withQueryString();

        // If you want JSON for API/AJAX calls, uncomment:
        // if ($request->wantsJson()) return response()->json($materials);

        return view('materials.index', compact('materials', 'search', 'sort', 'onlyLow'));
    }

    /**
     * Show create form.
     * Route: GET /materials/create
     */
    public function create()
    {
        $units = Material::ALLOWED_UNITS;
        return view('materials.create', compact('units'));
    }

    /**
     * Store a new material.
     * Route: POST /materials
     */
    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        // Create inside a transaction for safety
        $material = DB::transaction(function () use ($data) {
            return Material::create($data);
        });

        return redirect()
            ->route('materials.index')
            ->with('success', "Material “{$material->material_name}” created.");
    }

    /**
     * Show a single material.
     * Route: GET /materials/{material}
     */
    public function show(Material $material)
    {
        // If you need to eager load related recipes/products:
        // $material->load(['recipes', 'products']);
        return view('materials.show', compact('material'));
    }

    /**
     * Show edit form.
     * Route: GET /materials/{material}/edit
     */
    public function edit(Material $material)
    {
        $units = Material::ALLOWED_UNITS;
        return view('materials.edit', compact('material', 'units'));
    }

    /**
     * Update a material.
     * Route: PUT/PATCH /materials/{material}
     */
    public function update(Request $request, Material $material)
    {
        $data = $request->validate($this->rules($material->id));

        DB::transaction(function () use ($material, $data) {
            $material->update($data);
        });

        return redirect()
            ->route('materials.index')
            ->with('success', "Material “{$material->material_name}” updated.");
    }

    /**
     * Soft delete a material.
     * Route: DELETE /materials/{material}
     */
    public function destroy(Material $material)
    {
        DB::transaction(function () use ($material) {
            $material->delete();
        });

        return redirect()
            ->back()
            ->with('success', "Material “{$material->material_name}” deleted.");
    }

    /**
     * List soft-deleted materials (trash).
     * Route: GET /materials/trash
     */
    public function trash(Request $request)
    {
        $materials = Material::onlyTrashed()
            ->orderByDesc('deleted_at')
            ->paginate(15);

        return view('materials.trash', compact('materials'));
    }

    /**
     * Restore a soft-deleted material.
     * Route: PATCH /materials/{id}/restore
     */
    public function restore($id)
    {
        $material = Material::onlyTrashed()->findOrFail($id);

        DB::transaction(function () use ($material) {
            $material->restore();
        });

        return redirect()
            ->route('materials.trash')
            ->with('success', "Material “{$material->material_name}” restored.");
    }

    /**
     * Permanently delete a soft-deleted material.
     * Route: DELETE /materials/{id}/force
     */
    public function forceDelete($id)
    {
        $material = Material::onlyTrashed()->findOrFail($id);

        DB::transaction(function () use ($material) {
            $material->forceDelete();
        });

        return redirect()
            ->route('materials.trash')
            ->with('success', "Material permanently removed.");
    }

    /* -----------------------------------------------------------------
     | Optional lightweight JSON endpoints (AJAX helpers)
     * -----------------------------------------------------------------*/

    /**
     * Adjust quantity by a delta (e.g., deduct usage or add stock).
     * Route: PATCH /materials/{material}/adjust-quantity
     * Body: { "delta": -1.25 }
     */
    public function adjustQuantity(Request $request, Material $material)
    {
        $validated = $request->validate([
            'delta' => ['required','numeric','between:-999999999999.999,999999999999.999'],
        ]);

        DB::transaction(function () use ($material, $validated) {
            $newQty = (float) $material->quantity_kg + (float) $validated['delta'];
            $newQty = max(0.0, $newQty); // clamp to >= 0
            $material->update(['quantity_kg' => $newQty]);
        });

        return response()->json([
            'ok' => true,
            'material' => $material->fresh(),
        ]);
    }

    /**
     * Set quantity to an absolute value.
     * Route: PATCH /materials/{material}/set-quantity
     * Body: { "quantity_kg": 12.345 }
     */
    public function setQuantity(Request $request, Material $material)
    {
        $validated = $request->validate([
            'quantity_kg' => ['required','numeric','min:0','max:999999999999.999'],
        ]);

        DB::transaction(function () use ($material, $validated) {
            $material->update(['quantity_kg' => (float) $validated['quantity_kg']]);
        });

        return response()->json([
            'ok' => true,
            'material' => $material->fresh(),
        ]);
    }

    /* -----------------------------------------------------------------
     | Validation rules
     * -----------------------------------------------------------------*/
    private function rules(?int $materialId = null): array
    {
        return [
            'material_name' => ['required','string','max:255'],
            'category'      => ['nullable','string','max:255'],
            'unit'          => ['required','string', Rule::in(Material::ALLOWED_UNITS)],
            'sku'           => ['nullable','string','max:64'],
            'unit_price'    => ['nullable','numeric','min:0','max:9999999999.99'],
            'quantity_kg'   => ['nullable','numeric','min:0','max:999999999999.999'],
            'min_stock_kg'  => ['nullable','numeric','min:0','max:999999999999.999'],
            'stock_status'  => ['nullable','in:low,in_stock'],
        ];
    }
}
