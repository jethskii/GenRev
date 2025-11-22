@extends('layout.mainlayout')

@section('content')
@if(session('success'))
    <div id="successAlert" class="bg-green-600 text-white text-sm px-4 py-2 rounded mb-4 flex justify-between items-center shadow-lg transform transition-all duration-300">
        <span>{{ session('success') }}</span>
        <button onclick="document.getElementById('successAlert').style.display='none'" class="text-white text-lg leading-none hover:text-gray-300">&times;</button>
    </div>
@endif

<div class="glass border border-dark-line shadow-xl p-6 rounded-2xl text-[#1F4B2C] bg-gradient-to-br from-rose-50 via-red-50 to-amber-50">

    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-full bg-gradient-to-tr from-red-500 via-pink-500 to-amber-400 flex items-center justify-center shadow-md animate-pulse">
                <!-- Meat / production icon -->
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M5 3h4l2 5h8l-2 7H7L5 3z" />
                </svg>
            </div>
            <div>
                <h2 class="text-2xl font-extrabold tracking-tight text-[#1F2A1A]">
                    Inventory Overview
                </h2>
                <p class="text-xs text-[#335c3b] opacity-80">
                    Fresh meat stock, ready for production and sales.
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <!-- Export Buttons -->
            <a href="{{ route('inventory.export.csv') }}"
               class="inline-flex items-center gap-2 px-3 py-2 rounded-full text-xs font-semibold bg-white/80 hover:bg-green-100 text-green-700 border border-green-300 shadow-sm hover:shadow-md transform hover:-translate-y-0.5 transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 4h16v4H4zM4 9h16v11H4zM9 13h2v3H9zM13 13h2v3h-2z" />
                </svg>
                Export CSV
            </a>

            <a href="{{ route('inventory.export.pdf') }}"
               class="inline-flex items-center gap-2 px-3 py-2 rounded-full text-xs font-semibold bg-white/80 hover:bg-red-100 text-red-700 border border-red-300 shadow-sm hover:shadow-md transform hover:-translate-y-0.5 transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M7 2h8l5 5v13H7zM15 2v5h5" />
                </svg>
                Export PDF
            </a>

            <!-- Add Product -->
            <button onclick="openModal()"
                    class="btn-armygreen text-sm px-4 py-2 rounded-full shadow-md transform hover:-translate-y-0.5 hover:shadow-lg transition-all bg-gradient-to-r from-green-600 to-lime-500 text-white flex items-center gap-2">
                <span class="text-lg leading-none">+</span> Add Product
            </button>
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="flex flex-wrap justify-between items-center gap-4 mb-4">
        <div class="relative w-full sm:w-1/3">
            <input
                type="text"
                id="searchInput"
                placeholder="Search product..."
                class="input-dark w-full pl-9"
            >
            <span class="absolute inset-y-0 left-2 flex items-center text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-4.35-4.35M11 18a7 7 0 1 1 0-14 7 7 0 0 1 0 14z"/>
                </svg>
            </span>
        </div>

        <select
            id="stockFilter"
            class="input-dark w-full sm:w-1/4"
        >
            <option value="">Filter by Stock Status</option>
            <option value="High Stock">High Stock</option>
            <option value="In Stock">In Stock</option>
            <option value="Low Stock">Low Stock</option>
        </select>
    </div>

    <!-- Product Inventory Table -->
    <div class="overflow-x-auto rounded-xl shadow-inner bg-white/70 backdrop-blur">
        <table class="min-w-full text-sm text-left rounded-xl overflow-hidden">
            <thead class="bg-sidebar text-[#1F4B2C] uppercase text-xs">
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
            <tbody class="text-[#1F4B2C] divide-y divide-dark-line">
                @forelse ($products as $product)
                <tr class="hover:bg-amber-50/80 transition-all duration-200 transform hover:-translate-y-0.5"
                    data-row="inventory-row">
                    <td class="py-3 px-4 font-semibold text-[13px] text-gray-700">
                        <span class="inline-flex px-2 py-1 rounded-full bg-rose-100 text-rose-700 text-[11px] font-bold tracking-wide">
                            PROD-{{ $product->id }}
                        </span>
                    </td>
                    <td class="py-3 px-4 font-semibold">
                        {{ $product->product_name }}
                    </td>
                    <td class="py-3 px-4 text-xs">
                        <span class="inline-flex px-2 py-1 rounded-full bg-red-50 text-red-700 border border-red-200">
                            {{ $product->batch_number }}
                        </span>
                    </td>
                    <td class="py-3 px-4 text-xs">
                        {{ \Carbon\Carbon::parse($product->production_date)->format('m-d-Y') }}
                    </td>
                    <td class="py-3 px-4 font-bold text-sm">
                        {{ $product->quantity }}
                    </td>
                    <td class="py-3 px-4">
                        @php
                            $status = $product->stock_status;
                            $badgeClasses = match($status) {
                                'High Stock' => 'bg-green-100 text-green-700 border-green-300',
                                'In Stock'   => 'bg-amber-100 text-amber-700 border-amber-300',
                                'Low Stock'  => 'bg-red-100 text-red-700 border-red-300',
                                default      => 'bg-gray-100 text-gray-700 border-gray-300',
                            };
                        @endphp
                        <span class="inline-flex px-2 py-1 rounded-full border text-[11px] font-semibold {{ $badgeClasses }}">
                            {{ $status }}
                        </span>
                    </td>
                    <td class="py-3 px-4 text-center">
                        <div class="flex items-center justify-center gap-3">

                            {{-- Edit --}}
                            <button onclick="openEditModal({{ $product->id }})" title="Edit"
                                    class="transform hover:-translate-y-0.5 transition-all">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                     class="h-5 w-5 text-blue-500 hover:text-blue-700"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586"/>
                                </svg>
                            </button>

                            {{-- Archive --}}
                            <button class="transform hover:-translate-y-0.5 transition-all"
                                    title="Archive">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                     class="h-5 w-5 text-yellow-500 hover:text-yellow-700"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M20 12H4M9 12h6"/>
                                </svg>
                            </button>

                        </div>
                    </td>
                </tr>
                @empty
                <tr data-row="empty-row">
                    <td colspan="7" class="py-4 text-center text-gray-400 text-sm">
                        No inventory records found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($products, 'links'))
        <div class="mt-4">
            {{ $products->links() }}
        </div>
    @endif
</div>

@include('modals.inventory_modal')
@endsection

@section('scripts')
<script>
    // Add product modal
    const modal = document.getElementById('addProductModal');

    function openModal() {
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal() {
        if (!modal) return;
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeModal();
    });

    // Auto-hide success alert with fade-out
    const successAlert = document.getElementById('successAlert');
    if (successAlert) {
        setTimeout(() => {
            successAlert.style.opacity = '0';
            successAlert.style.transform = 'translateY(-4px)';
            setTimeout(() => successAlert.style.display = 'none', 300);
        }, 2500);
    }

    // Edit modal logic
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
                document.getElementById('editProductModal').classList.add('flex');
            });
    }

    function closeEditModal() {
        const editModal = document.getElementById('editProductModal');
        if (!editModal) return;
        editModal.classList.add('hidden');
        editModal.classList.remove('flex');
    }

    // Search + Filter
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('searchInput');
        const stockFilter = document.getElementById('stockFilter');
        const tableRows = document.querySelectorAll('tbody tr[data-row="inventory-row"]');
        const emptyRow   = document.querySelector('tbody tr[data-row="empty-row"]');

        function filterTable() {
            const searchText = searchInput.value.toLowerCase();
            const selectedStock = stockFilter.value;
            let visibleCount = 0;

            tableRows.forEach(row => {
                const productName = row.children[1]?.textContent.toLowerCase() || '';
                const stockStatus = row.children[5]?.textContent.trim() || '';
                const matchesSearch = productName.includes(searchText);
                const matchesFilter = !selectedStock || stockStatus === selectedStock;

                if (matchesSearch && matchesFilter) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (emptyRow) {
                emptyRow.style.display = visibleCount === 0 ? '' : 'none';
            }
        }

        if (searchInput) {
            searchInput.addEventListener('keyup', filterTable);
        }
        if (stockFilter) {
            stockFilter.addEventListener('change', filterTable);
        }
    });
</script>
@endsection
