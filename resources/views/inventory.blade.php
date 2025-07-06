@extends('layout.mainlayout')

@section('content')
    @if(session('success'))
        <div id="successAlert" class="bg-green-600 text-white text-sm px-4 py-2 rounded mb-4 flex justify-between items-center">
            <span>{{ session('success') }}</span>
            <button onclick="document.getElementById('successAlert').style.display='none'" class="text-white text-lg leading-none hover:text-gray-300">&times;</button>
        </div>
    @endif

    <div class="bg-dark-bg text-white border border-dark-line shadow-md p-6 rounded-lg">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold">Inventory</h2>
            <button onclick="openModal()" class="btn-armygreen text-sm px-4 py-2 rounded-md shadow transition">
                + Add Product
            </button>
        </div>

        <!-- Search and Filter -->
        <div class="flex justify-between items-center gap-4 mb-4">
            <input
                type="text"
                id="searchInput"
                placeholder="Search product..."
                class="bg-[#2d3b2e] text-white px-4 py-2 rounded-md w-1/3 placeholder-gray-400 border border-dark-line focus:outline-none focus:ring focus:border-armygreen"
            >
            <select
                id="stockFilter"
                class="bg-[#2d3b2e] text-white px-4 py-2 rounded-md border border-dark-line focus:outline-none focus:ring focus:border-armygreen"
            >
                <option value="">Filter by Stock Status</option>
                <option value="High Stock">High Stock</option>
                <option value="In Stock">In Stock</option>
                <option value="Low Stock">Low Stock</option>
            </select>
        </div>

        <!-- Product Inventory Table -->
        <div class="overflow-x-auto rounded-lg">
            <table class="min-w-full text-sm text-left bg-dark-bg rounded-lg overflow-hidden">
                <thead class="bg-sidebar text-white uppercase text-xs">
                    <tr>
                        <th class="py-3 px-4 border-b border-dark-line">Product ID</th>
                        <th class="py-3 px-4 border-b border-dark-line">Product Name</th>
                        <th class="py-3 px-4 border-b border-dark-line">Batch #</th>
                        <th class="py-3 px-4 border-b border-dark-line">Production Date</th>
                        <th class="py-3 px-4 border-b border-dark-line">Quantity</th>
                        <th class="py-3 px-4 border-b border-dark-line">Stock Status</th>
                        <th class="py-3 px-4 border-b border-dark-line text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-gray-100 divide-y divide-dark-line">
                    @forelse ($products as $product)
                        <tr class="hover:bg-sidebar-hover transition">
                            <td class="py-3 px-4">PROD-{{ $product->id }}</td>
                            <td class="py-3 px-4">{{ $product->product_name }}</td>
                            <td class="py-3 px-4">{{ $product->batch_number }}</td>
                            <td class="py-3 px-4">{{ \Carbon\Carbon::parse($product->production_date)->format('m-d-Y') }}</td>
                            <td class="py-3 px-4">{{ $product->quantity }}</td>
                            <td class="py-3 px-4">{{ $product->stock_status }}</td>
                            <td class="py-3 px-4 text-center">
                                <div class="flex items-center justify-center gap-3">
                                    <button onclick="openEditModal({{ $product->id }})" class="hover:text-blue-400 transition" title="Edit">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white hover:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H7v-3a2 2 0 01.586-1.414z" />
                                        </svg>
                                    </button>
                                    <button class="hover:text-yellow-400 transition" title="Archive">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white hover:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4m16 0a8 8 0 11-16 0 8 8 0 0116 0zM9 12h6" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-4 text-center text-gray-400">No inventory records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('modals.inventory_modal')
@endsection

@section('scripts')
<script>
    const modal = document.getElementById('addProductModal');

    function openModal() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal() {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeModal();
    });

    function openEditModal(productId) {
        fetch(`/inventory/${productId}/edit`)
            .then(response => response.json())
            .then(product => {
                document.getElementById('edit-product-id').value = product.id;
                document.getElementById('edit-product-name').value = product.product_name;
                document.getElementById('edit-batch-number').value = product.batch_number;
                document.getElementById('edit-production-date').value = product.production_date;
                document.getElementById('edit-quantity').value = product.quantity;
                document.getElementById('edit-stock-status').value = product.stock_status;
                document.getElementById('editProductForm').action = `/inventory/${product.id}`;
                document.getElementById('editProductModal').classList.remove('hidden');
            });
    }

    function closeEditModal() {
        const modal = document.getElementById('editProductModal');
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }

    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('searchInput');
        const stockFilter = document.getElementById('stockFilter');
        const tableRows = document.querySelectorAll('table tbody tr');

        function filterTable() {
            const searchText = searchInput.value.toLowerCase();
            const selectedStock = stockFilter.value;

            tableRows.forEach(row => {
                const productName = row.children[1]?.textContent.toLowerCase();
                const stockStatus = row.children[5]?.textContent.trim();

                const matchesSearch = productName && productName.includes(searchText);
                const matchesFilter = !selectedStock || stockStatus === selectedStock;

                row.style.display = (matchesSearch && matchesFilter) ? '' : 'none';
            });
        }

        searchInput.addEventListener('keyup', filterTable);
        stockFilter.addEventListener('change', filterTable);
    });
</script>
@endsection
