<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class MaterialController extends Controller
{
    /** Keep UI + backend in sync */
    private const ALLOWED_UNITS = ['kg','g','lbs','pcs','pkg','box','bag','roll','tray','lt','ml','m3'];

    /* ------------------------- LIST / INDEX ------------------------- */

    public function index(Request $request)
    {
        $search   = trim((string) $request->get('search', ''));
        $sort     = (string) $request->get('sort', 'name_asc'); // name_asc|name_desc|qty_desc|qty_asc|price_desc|price_asc|updated_desc|updated_asc
        $perPage  = $request->get('per_page', 50);
        $category = $request->get('category');

        $q = Material::query()
            ->when($search !== '', function ($qq) use ($search) {
                $qq->where(function ($w) use ($search) {
                    $w->where('material_name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            })
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

        if ($col === 'updated_at' || Schema::hasColumn('materials', $col)) {
            $q->orderBy($col, $dir);
        } else {
            $q->orderBy('material_name', 'asc');
        }

        $materials = (is_string($perPage) && strtolower($perPage) === 'all')
            ? $q->get()
            : $q->paginate(max(1, (int) $perPage))->appends($request->query());

        return view('materials.index', compact('materials'));
    }

    /* ------------------------- CREATE / STORE ------------------------- */

    public function create()
    {
        return view('materials.create');
    }

    public function store(Request $request)
    {
        $validated  = $this->validateMaterial($request);
        $normalized = $this->normalizeForPersist($validated);

        try {
            $mat = DB::transaction(function () use ($normalized) {
                $mat = new Material();
                $payload = $this->onlyExistingColumns($normalized);
                $mat->forceFill($payload); // bypasses $fillable safely
                if (! $mat->save()) {
                    throw new \RuntimeException('Save returned false for materials.insert');
                }
                return $mat->fresh();
            });
        } catch (\Throwable $e) {
            Log::error('Material store failed', ['msg' => $e->getMessage()]);
            return $this->fail($request, 'Unable to add material.', $e);
        }

        return $this->ok($request, 'Material added!', $mat);
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
        $material   = Material::findOrFail($id);
        $validated  = $this->validateMaterial($request, $material->id);
        $normalized = $this->normalizeForPersist($validated);

        try {
            DB::transaction(function () use ($material, $normalized) {
                $payload = $this->onlyExistingColumns($normalized);
                $material->forceFill($payload);
                if (! $material->save()) {
                    throw new \RuntimeException('Save returned false for materials.update');
                }
            });
        } catch (\Throwable $e) {
            Log::error('Material update failed', ['id' => $id, 'msg' => $e->getMessage()]);
            return $this->fail($request, 'Unable to update material.', $e);
        }

        return $this->ok($request, 'Material updated!', $material->fresh());
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

    protected function validateMaterial(Request $request, ?int $materialId = null): array
    {
        $categoryPresenceRule = Schema::hasColumn('materials', 'category') ? 'nullable' : 'sometimes';

        return $request->validate([
            'material_name' => [
                'required', 'string', 'max:255',
                Rule::unique('materials', 'material_name')->ignore($materialId),
            ],
            'category'      => [$categoryPresenceRule, 'string', 'max:100'],
            'unit'          => ['required', Rule::in(self::ALLOWED_UNITS)],
            'unit_price'    => ['required'],
            'quantity_kg'   => ['required'],
            'min_stock_kg'  => ['nullable'],
            'sku'           => [
                'nullable', 'string', 'max:120',
                Rule::unique('materials', 'sku')->ignore($materialId),
            ],
        ]);
    }

    /* ------------------------- HELPERS ------------------------- */

    private function ok(Request $request, string $message, Material $mat)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'ok'       => true,
                'message'  => $message,
                'material' => $this->toApi($mat),
            ]);
        }
        return redirect()->route('materials.index')->with('success', $message);
    }

    private function fail(Request $request, string $message, \Throwable $e, int $code = 422)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'ok'      => false,
                'message' => $message,
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], $code);
        }
        return back()->withInput()->withErrors([
            'material' => config('app.debug') ? $e->getMessage() : $message
        ]);
    }

    private function toApi(Material $m): array
    {
        return [
            'id'            => $m->id,
            'material_name' => $m->material_name,
            'category'      => Schema::hasColumn('materials', 'category') ? $m->category : null,
            'unit'          => $m->unit,
            'unit_price'    => (float) $m->unit_price,
            'quantity_kg'   => (float) $m->quantity_kg,
            'min_stock_kg'  => $m->min_stock_kg !== null ? (float) $m->min_stock_kg : null,
            'sku'           => $m->sku,
            'stock_status'  => Schema::hasColumn('materials', 'stock_status') ? $m->stock_status : null,
            'updated_at'    => optional($m->updated_at)->toDateTimeString(),
        ];
    }

    /** Only persist keys that exist in the DB (prevents unknown column issues) */
    private function onlyExistingColumns(array $data): array
    {
        $cols = Schema::getColumnListing('materials');

        // Optionally auto-calc stock_status if column exists
        if (in_array('stock_status', $cols, true) && !isset($data['stock_status'])) {
            $q = (float) ($data['quantity_kg'] ?? 0);
            $min = (float) ($data['min_stock_kg'] ?? 0);
            $data['stock_status'] = $min > 0 && $q <= $min ? 'low' : 'in_stock';
        }

        return array_intersect_key($data, array_flip($cols));
    }

    /* ------------ Normalization (same as your version) ------------- */

    private function normalizeForPersist(array $data): array
    {
        $data['material_name'] = trim((string) ($data['material_name'] ?? ''));
        $data['sku']           = array_key_exists('sku', $data) && $data['sku'] !== null
            ? trim((string) $data['sku'])
            : null;

        if (!Schema::hasColumn('materials', 'category')) {
            unset($data['category']);
        } else {
            $data['category'] = isset($data['category']) && $data['category'] !== ''
                ? trim((string) $data['category'])
                : null;
        }

        $data['unit'] = in_array($data['unit'] ?? '', self::ALLOWED_UNITS, true)
            ? $data['unit']
            : 'kg';

        $data['unit_price']   = $this->toDecimal($data['unit_price'] ?? 0);
        $data['quantity_kg']  = $this->toDecimal($data['quantity_kg'] ?? 0, 3);
        $data['min_stock_kg'] = isset($data['min_stock_kg']) && $data['min_stock_kg'] !== ''
            ? $this->toDecimal($data['min_stock_kg'], 3)
            : null;

        return $data;
    }

    private function toDecimal($value, int $precision = 2): float
    {
        if (is_null($value)) return 0.0;
        if (is_numeric($value)) return round((float) $value, $precision);

        $s = (string) $value;
        $s = preg_replace('/[₱\p{Sc}\s]+/u', '', $s);

        if (strpos($s, ',') !== false && strpos($s, '.') !== false) {
            $s = str_replace(',', '', $s);
        } elseif (strpos($s, ',') !== false) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } else {
            $s = str_replace(',', '', $s);
        }

        $num = is_numeric($s) ? (float) $s : 0.0;
        return round($num, $precision);
    }
}
