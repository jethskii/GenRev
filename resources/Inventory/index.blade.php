@extends('layout.mainlayout')

@section('content')
<div class="p-6 text-white">

    <div class="flex flex-col md:flex-row justify-between items-center mb-6">
        <h1 class="text-2xl font-bold mb-4 md:mb-0">Production Manager</h1>
        <div class="space-x-3">
            <button onclick="openAddModal()" class="btn-armygreen">+ Add Production</button>
            <a href="{{ route('production.export', ['format' => 'pdf']) }}" class="btn-armygreen">Export PDF</a>
            <a href="{{ route('production.export', ['format' => 'excel']) }}" class="btn-armygreen">Export Excel</a>
        </div>
    </div>

    {{-- Search Bar --}}
    <div class="mb-6">
        <input type="text" id="searchInput" placeholder="Search product..."
            class="w-full md:w-1/3 px-4 py-2 rounded bg-dark-field text-white focus:outline-none border border-dark-line">
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-sidebar p-4 rounded">
            <p class="text-gray-300 text-sm">Forecasted Demand</p>
            <h2 class="text-xl font-bold">{{ $forecastedDemand }} kg</h2>
        </div>
        <div class="bg-sidebar p-4 rounded">
            <p class="text-gray-300 text-sm">Current Inventory</p>
            <h2 class="text-xl font-bold">{{ $actualInventory }} kg</h2>
        </div>
        <div class="bg-sidebar p-4 rounded">
            <p class="text-gray-300 text-sm">Recommended Production</p>
            <h2 class="text-xl font-bold text-green-400">{{ $recommendedProduction }} kg</h2>
        </div>
        <div class="bg-sidebar p-4 rounded">
            <p class="text-gray-300 text-sm">Shortfall</p>
            <h2 class="text-xl font-bold text-red-400">{{ $shortfall }} kg</h2>
        </div>
    </div>

    {{-- Product Table --}}
    <div class="bg-dark-bg p-4 rounded shadow overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-gray-400 border-b border-dark-line uppercase">
                <tr>
                    <th class="py-3 px-4">Product Name</th>
                    <th class="py-3 px-4">Forecasted</th>
                    <th class="py-3 px-4">Inventory</th>
                    <th class="py-3 px-4">Unit Cost</th>
                    <th class="py-3 px-4">Production Date</th>
                </tr>
            </thead>
            <tbody id="productTable">
                @foreach($products as $product)
                <tr class="hover:bg-sidebar-hover border-b border-dark-line transition">
                    <td class="py-2 px-4">{{ $product->product_name }}</td>
                    <td class="py-2 px-4">{{ $product->forecasted_demand }} kg</td>
                    <td class="py-2 px-4">{{ $product->current_inventory }} kg</td>
                    <td class="py-2 px-4">₱{{ number_format($product->unit_cost, 2) }}</td>
                    <td class="py-2 px-4">{{ \Carbon\Carbon::parse($product->production_date)->format('M d, Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Add Modal --}}
    <div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden z-50">
        <div class="bg-dark-bg w-full max-w-lg p-6 rounded shadow-md">
            <h2 class="text-xl font-bold mb-4">Add Production Record</h2>
            <form method="POST" action="{{ route('production.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="block mb-1 text-sm">Product Name</label>
                    <input type="text" name="product_name" required
                        class="w-full px-4 py-2 rounded bg-dark-field text-white focus:outline-none">
                </div>
                <div class="mb-3">
                    <label class="block mb-1 text-sm">Forecasted Demand (kg)</label>
                    <input type="number" name="forecasted_demand" id="forecasted" required
                        class="w-full px-4 py-2 rounded bg-dark-field text-white focus:outline-none">
                </div>
                <div class="mb-3">
                    <label class="block mb-1 text-sm">Current Inventory (kg)</label>
                    <input type="number" name="current_inventory" id="inventory" required
                        class="w-full px-4 py-2 rounded bg-dark-field text-white focus:outline-none">
                </div>
                <div class="mb-3">
                    <label class="block mb-1 text-sm">Recommended Production (auto)</label>
                    <input type="number" id="recommended" readonly
                        class="w-full px-4 py-2 rounded bg-dark-field text-gray-400 cursor-not-allowed">
                </div>
                <div class="mb-3">
                    <label class="block mb-1 text-sm">Unit Cost (₱)</label>
                    <input type="number" name="unit_cost" step="0.01" required
                        class="w-full px-4 py-2 rounded bg-dark-field text-white focus:outline-none">
                </div>
                <div class="mb-4">
                    <label class="block mb-1 text-sm">Production Date</label>
                    <input type="date" name="production_date" required
                        class="w-full px-4 py-2 rounded bg-dark-field text-white focus:outline-none">
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeAddModal()" class="bg-gray-600 hover:bg-gray-500 px-4 py-2 rounded">Cancel</button>
                    <button type="submit" class="btn-armygreen">Save</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    // Search filtering
    document.getElementById('searchInput').addEventListener('input', function () {
        const query = this.value.toLowerCase();
        document.querySelectorAll('#productTable tr').forEach(row => {
            const product = row.querySelector('td')?.textContent?.toLowerCase() || '';
            row.style.display = product.includes(query) ? '' : 'none';
        });
    });

    // Auto-calculation logic
    document.getElementById('forecasted').addEventListener('input', updateRecommended);
    document.getElementById('inventory').addEventListener('input', updateRecommended);

    function updateRecommended() {
        const forecasted = parseInt(document.getElementById('forecasted').value) || 0;
        const inventory = parseInt(document.getElementById('inventory').value) || 0;
        const recommended = Math.max(forecasted - inventory, 0);
        document.getElementById('recommended').value = recommended;
    }

    // Modal functions
    function openAddModal() {
        document.getElementById('addModal').classList.remove('hidden');
    }
    function closeAddModal() {
        document.getElementById('addModal').classList.add('hidden');
    }
</script>
@endsection
