<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    public function index()
    {
    $materials = Material::latest()->get();
    return view('materials.index', compact('materials'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'quantity_kg' => 'required|numeric|min:0.01',
        ]);

        Material::create($request->only('name', 'quantity_kg'));

        return redirect()->route('materials.index')->with('success', 'Material added!');
    }

    public function edit($id)
    {
        $material = Material::findOrFail($id);
        return response()->json($material);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'quantity_kg' => 'required|numeric|min:0.01',
        ]);

        $material = Material::findOrFail($id);
        $material->update($request->only('name', 'quantity_kg'));

        return redirect()->route('materials.index')->with('success', 'Material updated!');
    }

    public function destroy($id)
    {
        $material = Material::findOrFail($id);
        $material->delete();

        return redirect()->route('materials.index')->with('success', 'Material deleted!');
    }
}
