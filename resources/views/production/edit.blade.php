@extends('layout.mainlayout')

@section('content')
<div class="bg-dark-bg text-white p-6 rounded shadow-md w-full max-w-2xl mx-auto">
    <h2 class="text-xl font-semibold mb-4">Edit Production</h2>

    <form action="{{ route('production.update', $product->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label class="block text-sm text-gray-200">Product Name</label>
            <input name="product_name" value="{{ $product->product_name }}" required
                class="w-full px-3 py-2 bg-white text-black border rounded">
        </div>

        <div class="mb-4">
            <label class="block text-sm text-gray-200">Forecasted Demand (kg)</label>
            <input type="number" name="forecasted_demand" value="{{ $product->forecasted_demand }}" required
                class="w-full px-3 py-2 bg-white text-black border rounded">
        </div>

        <div class="mb-4">
            <label class="block text-sm text-gray-200">Current Inventory (kg)</label>
            <input type="number" name="current_inventory" value="{{ $product->current_inventory }}" required
                class="w-full px-3 py-2 bg-white text-black border rounded">
        </div>

        <div class="mb-4">
            <label class="block text-sm text-gray-200">Unit Cost</label>
            <input type="number" step="0.01" name="unit_cost" value="{{ $product->unit_cost }}" required
                class="w-full px-3 py-2 bg-white text-black border rounded">
        </div>

        <div class="mb-4">
            <label class="block text-sm text-gray-200">Production Date</label>
            <input type="date" name="production_date" value="{{ \Carbon\Carbon::parse($product->production_date)->format('Y-m-d') }}" required
                class="w-full px-3 py-2 bg-white text-black border rounded">
        </div>

        <button type="submit" class="btn-armygreen w-full">Update Production</button>
    </form>
</div>
@endsection
