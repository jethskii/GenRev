@extends('layout.mainlayout')

@section('content')
@if(session('success'))
    <div id="successAlert" class="bg-green-600 text-white text-sm px-4 py-2 rounded mb-4 flex justify-between items-center">
        <span>{{ session('success') }}</span>
        <button onclick="document.getElementById('successAlert').style.display='none'" class="text-white text-lg leading-none hover:text-gray-300">&times;</button>
    </div>
@endif

<div class="bg-dark-bg text-white border border-dark-line shadow-md p-6 rounded-lg">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-white">Sales</h2>
        <button onclick="openModal()" class="btn-armygreen text-sm px-4 py-2 rounded-md shadow transition">
            + Add Sale
        </button>
    </div>

    <!-- Search & Filter Controls -->
    <div class="flex justify-between items-center gap-4 mb-4">
        <input
            type="text"
            id="searchInput"
            placeholder="Search product..."
            class="bg-[#2d3b2e] text-white px-4 py-2 rounded-md w-1/3 placeholder-gray-400 border border-dark-line focus:outline-none focus:ring focus:border-armygreen"
        >

        <select
            id="statusFilter"
            class="bg-[#2d3b2e] text-white px-4 py-2 rounded-md border border-dark-line focus:outline-none focus:ring focus:border-armygreen"
        >
            <option value="">Filter by Status</option>
            <option value="Paid">Paid</option>
            <option value="Pending">Pending</option>
            <option value="Cancelled">Cancelled</option>
        </select>
    </div>

    <!-- Sales Table -->
    <div class="overflow-x-auto rounded-lg">
        <table class="min-w-full text-sm text-left bg-dark-bg rounded-lg overflow-hidden">
            <thead class="bg-sidebar text-white uppercase text-xs">
                <tr class="rounded-t-lg">
                    <th class="py-3 px-4 border-b border-dark-line">Invoice #</th>
                    <th class="py-3 px-4 border-b border-dark-line">Product</th>
                    <th class="py-3 px-4 border-b border-dark-line">Date</th>
                    <th class="py-3 px-4 border-b border-dark-line">Qty</th>
                    <th class="py-3 px-4 border-b border-dark-line">Price</th>
                    <th class="py-3 px-4 border-b border-dark-line">Total</th>
                    <th class="py-3 px-4 border-b border-dark-line">Status</th>
                    <th class="py-3 px-4 border-b border-dark-line text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="text-gray-100 divide-y divide-dark-line">
                @forelse ($sales as $sale)
                <tr class="hover:bg-sidebar-hover transition">
                    <td class="py-3 px-4 text-green-400 cursor-pointer hover:underline" onclick="openInvoiceModal({{ $sale->id }})">
                        {{ $sale->invoice_number ?? ('INV-' . $sale->id) }}
                    </td>
                    <td class="py-3 px-4">{{ $sale->product_name }}</td>
                    <td class="py-3 px-4">{{ \Carbon\Carbon::parse($sale->date)->format('m-d-Y') }}</td>
                    <td class="py-3 px-4">{{ $sale->quantity }}</td>
                    <td class="py-3 px-4">₱{{ number_format($sale->price, 2) }}</td>
                    <td class="py-3 px-4">₱{{ number_format($sale->quantity * $sale->price, 2) }}</td>
                    <td class="py-3 px-4">{{ $sale->status }}</td>
                    <td class="py-3 px-4 text-center">
                        <div class="flex items-center justify-center gap-3">
                            <button onclick="openEditModal({{ $sale->id }})" class="hover:text-blue-400 transition" title="Edit">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white hover:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H7v-3a2 2 0 01.586-1.414z" />
                                </svg>
                            </button>
                            <a href="{{ route('sales.receipt', $sale->id) }}" title="View Receipt" target="_blank">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white hover:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-3-3v6m6 4H6a2 2 0 01-2-2V6a2 2 0 012-2h6l6 6v10a2 2 0 01-2 2z" />
                                </svg>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="py-4 text-center text-gray-400">No sales records found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@include('modals.modals')
@endsection

@section('scripts')
<script>
    const modal = document.getElementById('addSaleModal');

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

    function openEditModal(id) {
        fetch(`/sales/${id}/edit`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('edit-sale-id').value = data.id;
                document.getElementById('edit-product-name').value = data.product_name;
                document.getElementById('edit-date').value = data.date;
                document.getElementById('edit-quantity').value = data.quantity;
                document.getElementById('edit-price').value = data.price;
                document.getElementById('edit-status').value = data.status;

                document.getElementById('editSaleForm').action = `/sales/${data.id}`;
                document.getElementById('editSaleModal').classList.remove('hidden');
                document.getElementById('editSaleModal').classList.add('flex');
            });
    }

    function closeEditModal() {
        const modal = document.getElementById('editSaleModal');
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }

    function openInvoiceModal(id) {
        window.open(`/sales/${id}/receipt`, '_blank');
    }

    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const tableRows = document.querySelectorAll('table tbody tr');

        function filterTable() {
            const searchText = searchInput.value.toLowerCase();
            const selectedStatus = statusFilter.value;

            tableRows.forEach(row => {
                const productName = row.children[1].textContent.toLowerCase();
                const saleStatus = row.children[6].textContent.trim();
                const matchesSearch = productName.includes(searchText);
                const matchesFilter = !selectedStatus || saleStatus === selectedStatus;

                row.style.display = (matchesSearch && matchesFilter) ? '' : 'none';
            });
        }

        searchInput.addEventListener('keyup', filterTable);
        statusFilter.addEventListener('change', filterTable);
    });
</script>
@endsection
