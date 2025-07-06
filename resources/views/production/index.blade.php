@extends('layout.mainlayout')

@section('content')
<div class="bg-dark-bg text-white p-6 rounded shadow-md border border-dark-line">
    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-semibold">Production Overview</h2>
        <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="btn-armygreen">+ Add Production</button>
    </div>

    {{-- Filter/Search Bar --}}
    <div class="mb-6">
        <form method="GET" action="{{ route('production.index') }}" class="flex flex-wrap gap-2">
            <input type="text" name="search" placeholder="Search product name..."
                   value="{{ request('search') }}"
                   class="bg-dark-field text-white px-3 py-2 rounded focus:outline-none w-full sm:w-1/3">
            <button type="submit" class="btn-armygreen">Search</button>
        </form>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 mb-6">
        @foreach ([
            ['label' => 'Forecasted Demand', 'value' => $forecastedDemand . ' kg'],
            ['label' => 'Current Inventory', 'value' => $actualInventory . ' kg'],
            ['label' => 'Shortfall', 'value' => $shortfall . ' kg', 'class' => 'text-red-400'],
            ['label' => 'Recommended Production', 'value' => $recommendedProduction . ' kg', 'class' => 'text-green-400'],
        ] as $card)
            <div class="bg-sidebar text-white p-4 rounded shadow">
                <p class="text-sm text-gray-300">{{ $card['label'] }}</p>
                <h3 class="text-lg font-bold {{ $card['class'] ?? '' }}">{{ $card['value'] }}</h3>
            </div>
        @endforeach
    </div>

    {{-- Product Table --}}
    <div class="overflow-x-auto rounded-lg">
        <table class="min-w-full text-sm text-left bg-dark-bg rounded-lg overflow-hidden">
            <thead class="bg-sidebar text-white uppercase text-xs">
                <tr>
                    <th class="py-3 px-4">Product Name</th>
                    <th class="py-3 px-4">Forecasted Demand</th>
                    <th class="py-3 px-4">Current Inventory</th>
                    <th class="py-3 px-4">Unit Cost</th>
                    <th class="py-3 px-4">Production Date</th>
                    <th class="py-3 px-4 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="text-gray-100 divide-y divide-dark-line">
                @forelse ($products as $product)
                    <tr class="hover:bg-sidebar-hover transition">
                        <td class="py-3 px-4">{{ $product->product_name }}</td>
                        <td class="py-3 px-4">{{ $product->forecasted_demand }} kg</td>
                        <td class="py-3 px-4">{{ $product->current_inventory }} kg</td>
                        <td class="py-3 px-4">₱{{ number_format($product->unit_cost, 2) }}</td>
                        <td class="py-3 px-4">{{ \Carbon\Carbon::parse($product->production_date)->format('M d, Y') }}</td>
                        <td class="py-3 px-4 text-center space-x-2">
                            <a href="{{ route('production.edit', $product->id) }}" class="text-yellow-400 hover:underline">Edit</a>
                            <form action="{{ route('production.destroy', $product->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-400 hover:underline">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-4 text-center text-gray-400">No production records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Add Production Modal --}}
<div id="addModal" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
    <div class="bg-dark-bg p-6 rounded-lg shadow-lg w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">Add Production</h3>
            <button onclick="document.getElementById('addModal').classList.add('hidden')" class="text-gray-400 hover:text-white">&times;</button>
        </div>
       <form action="{{ route('production.store') }}" method="POST">
    @csrf

    {{-- Product Name --}}
    <div class="mb-4">
        <label class="block text-sm text-gray-200">Product Name</label>
        <input name="product_name" required
            class="w-full px-3 py-2 bg-white text-black placeholder-gray-500 border border-gray-400 rounded focus:outline-none focus:ring-2 focus:ring-armygreen"
            placeholder="e.g. Hotdog ni Arjay">
    </div>

    {{-- Forecasted Demand --}}
    <div class="mb-4">
        <label class="block text-sm text-gray-200">Forecasted Demand (kg)</label>
        <input type="number" name="forecasted_demand" required
            class="w-full px-3 py-2 bg-white text-black placeholder-gray-500 border border-gray-400 rounded focus:outline-none focus:ring-2 focus:ring-armygreen"
            placeholder="e.g. 100">
    </div>

    {{-- Current Inventory --}}
    <div class="mb-4">
        <label class="block text-sm text-gray-200">Current Inventory (kg)</label>
        <input type="number" name="current_inventory" required
            class="w-full px-3 py-2 bg-white text-black placeholder-gray-500 border border-gray-400 rounded focus:outline-none focus:ring-2 focus:ring-armygreen"
            placeholder="e.g. 50">
    </div>

    {{-- Unit Cost --}}
    <div class="mb-4">
        <label class="block text-sm text-gray-200">Unit Cost</label>
        <input type="number" step="0.01" name="unit_cost" required
            class="w-full px-3 py-2 bg-white text-black placeholder-gray-500 border border-gray-400 rounded focus:outline-none focus:ring-2 focus:ring-armygreen"
            placeholder="e.g. 120.50">
    </div>

    {{-- Production Date --}}
    <div class="mb-4">
        <label class="block text-sm text-gray-200">Production Date</label>
        <input type="date" name="production_date" required
            class="w-full px-3 py-2 bg-white text-black border border-gray-400 rounded focus:outline-none focus:ring-2 focus:ring-armygreen">
    </div>

    <button type="submit" class="btn-armygreen w-full mt-2">Add Production</button>
</form>


    </div>
</div>
@endsection
