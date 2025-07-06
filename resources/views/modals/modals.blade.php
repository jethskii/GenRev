<!-- Add Sale Modal -->
<div id="addSaleModal" class="fixed inset-0 z-50 bg-black bg-opacity-50 hidden items-center justify-center">
    <div class="bg-dark-bg text-white border border-dark-line rounded-lg shadow-lg w-full max-w-lg p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-white">Add New Sale</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-white text-xl">&times;</button>
        </div>

        <form method="POST" action="{{ route('sales.store') }}">
            @csrf
            <div class="grid grid-cols-1 gap-4">

                <div>
                    <label class="block text-sm mb-1">Invoice Number</label>
                    <input type="text" name="id" value="{{ $nextInvoice }}"
                        readonly
                        class="w-full bg-sidebar text-gray-400 cursor-not-allowed rounded-md border border-dark-line px-3 py-2 text-sm" />
                </div>


                <div>
                    <label class="block text-sm mb-1">Product Name</label>
                    <input type="text" name="product_name" class="w-full bg-sidebar text-white rounded-md border border-dark-line px-3 py-2 text-sm" required>
                </div>

                <div>
                    <label class="block text-sm mb-1">Date</label>
                    <input
                        type="date"
                        name="date"
                        value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}"
                        class="w-full bg-sidebar text-white rounded-md border border-dark-line px-3 py-2 text-sm"
                        required
                    >
                </div>


                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm mb-1">Quantity</label>
                        <input type="number" name="quantity" class="w-full bg-sidebar text-white rounded-md border border-dark-line px-3 py-2 text-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Unit Price (₱)</label>
                        <input type="number" step="0.01" name="price" class="w-full bg-sidebar text-white rounded-md border border-dark-line px-3 py-2 text-sm" required>
                    </div>
                </div>

                <div>
                    <label class="block text-sm mb-1">Status</label>
                    <select name="status" class="w-full bg-sidebar text-white rounded-md border border-dark-line px-3 py-2 text-sm" required>
                        <option value="Paid">Paid</option>
                        <option value="Pending">Pending</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="flex justify-end gap-3 mt-4">
                    <button type="button" onclick="closeModal()" class="text-sm px-4 py-2 rounded-md border border-dark-line text-gray-300 hover:text-white hover:bg-sidebar-hover transition">
                        Cancel
                    </button>
                    <button type="submit" class="btn-armygreen text-sm px-4 py-2 rounded-md shadow transition">
                        Save Sale
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<!-- Edit Sale Modal -->
<div id="editSaleModal" class="fixed inset-0 z-50 bg-black bg-opacity-50 hidden items-center justify-center">
    <div class="bg-dark-bg text-white border border-dark-line rounded-lg shadow-lg w-full max-w-lg p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-white">Edit Sale</h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-white text-xl">&times;</button>
        </div>

        <form method="POST" action="#" id="editSaleForm">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 gap-4">
                <input type="hidden" name="sale_id" id="edit-sale-id">

                <div>
                    <label class="block text-sm mb-1">Product Name</label>
                    <input type="text" name="product_name" id="edit-product-name" class="w-full bg-sidebar text-white rounded-md border border-dark-line px-3 py-2 text-sm" required>
                </div>

                <div>
                    <label class="block text-sm mb-1">Date</label>
                    <input type="date" name="date" id="edit-date" class="w-full bg-sidebar text-white rounded-md border border-dark-line px-3 py-2 text-sm" required>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm mb-1">Quantity</label>
                        <input type="number" name="quantity" id="edit-quantity" class="w-full bg-sidebar text-white rounded-md border border-dark-line px-3 py-2 text-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Unit Price (₱)</label>
                        <input type="number" step="0.01" name="price" id="edit-price" class="w-full bg-sidebar text-white rounded-md border border-dark-line px-3 py-2 text-sm" required>
                    </div>
                </div>

                <div>
                    <label class="block text-sm mb-1">Status</label>
                    <select name="status" id="edit-status" class="w-full bg-sidebar text-white rounded-md border border-dark-line px-3 py-2 text-sm" required>
                        <option value="Paid">Paid</option>
                        <option value="Pending">Pending</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="flex justify-end gap-3 mt-4">
                    <button type="button" onclick="closeEditModal()" class="text-sm px-4 py-2 rounded-md border border-dark-line text-gray-300 hover:text-white hover:bg-sidebar-hover transition">
                        Cancel
                    </button>
                    <button type="submit" class="btn-armygreen text-sm px-4 py-2 rounded-md shadow transition">
                        Update Sale
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>


