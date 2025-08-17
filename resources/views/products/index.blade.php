@extends('layout.mainlayout')

@section('content')
<div class="bg-dark-bg text-white border border-dark-line p-6 rounded shadow-md">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold">Products</h2>

        <div class="flex items-center gap-2">
            <a href="{{ route('materials.index') }}" class="text-sm text-armygreen underline">Global Materials</a>
            {{-- Quick add product --}}
            <form action="{{ route('products.quick-store') }}" method="POST" class="flex gap-2">
                @csrf
                <input type="text" name="name" class="input-dark w-56" placeholder="Quick add product…" required>
                <button class="btn-armygreen">Add</button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 text-green-400 text-sm">{{ session('success') }}</div>
    @endif

    <div class="overflow-x-auto rounded-lg">
        <table class="w-full text-sm text-left bg-dark-bg rounded-lg overflow-hidden border-collapse">
            <thead class="bg-sidebar text-white text-xs uppercase">
                <tr>
                    <th class="py-3 px-4 border-b border-dark-line">Product</th>
                    <th class="py-3 px-4 border-b border-dark-line w-40 text-right">Unit Material Cost</th>
                    <th class="py-3 px-4 border-b border-dark-line w-72 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="text-gray-100 divide-y divide-dark-line">
                @forelse($products as $product)
                    <tr class="hover:bg-sidebar-hover transition">
                        <td class="py-3 px-4">{{ $product->product_name }}</td>

                        <td class="py-3 px-4 text-right">
                            ₱{{ number_format($product->unit_material_cost, 2) }}
                        </td>

                        <td class="py-3 px-4 text-right space-x-4">
                            {{-- Manage product’s recipe/BOM --}}
                            <a href="{{ route('products.materials.index', $product) }}"
                               class="text-armygreen hover:underline">Manage Materials</a>

                            {{-- Go to Production (batches/orders) for this product --}}
                            <a href="{{ route('production.orders', $product->id) }}"
                               class="text-armygreen hover:underline">Go to Production</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="py-4 text-center text-gray-400">No products yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
