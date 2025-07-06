<!-- Add Product Modal -->
<div id="addProductModal" class="fixed inset-0 z-50 bg-black bg-opacity-50 hidden items-center justify-center">
    <div class="bg-dark-bg text-white border border-dark-line rounded-lg shadow-lg w-full max-w-lg p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-white">Add New Product</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-white text-xl">&times;</button>
        </div>

        <form method="POST" action="{{ route('inventory.store') }}">

            @csrf
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="block text-sm mb-1">Product Name</label>
                    <input type="text" name="product_name" class="w-full bg-sidebar text-white rounded-md border border-dark-line px-3 py-2 text-sm" required>
                </div>

                <div>
                    <label class="block text-sm mb-1">Batch Number</label>
                    <input type="text" name="batch_number" class="w-full bg-sidebar text-white rounded-md border border-dark-line px-3 py-2 text-sm" required>
                </div>

                <div>
                    <label class="block text-sm mb-1">Production Date</label>
                    <input type="date" name="production_date" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" class="w-full bg-sidebar text-white rounded-md border border-dark-line px-3 py-2 text-sm" required>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm mb-1">Quantity</label>
                        <input type="number" name="quantity" class="w-full bg-sidebar text-white rounded-md border border-dark-line px-3 py-2 text-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Stock Status</label>
                        <select name="stock_status" class="w-full bg-sidebar text-white rounded-md border border-dark-line px-3 py-2 text-sm" required>
                            <option value="In Stock">In Stock</option>
                            <option value="Out of Stock">Out of Stock</option>
                            <option value="Low Stock">Low Stock</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-4">
                    <button type="button" onclick="closeModal()" class="text-sm px-4 py-2 rounded-md border border-dark-line text-gray-300 hover:text-white hover:bg-sidebar-hover transition">
                        Cancel
                    </button>
                    <button type="submit" class="btn-armygreen text-sm px-4 py-2 rounded-md shadow transition">
                        Save Product
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Product Modal -->
<div id="editProductModal" class="fixed inset-0 z-50 bg-black bg-opacity-50 hidden flex items-center justify-center">
    <div class="bg-dark-bg text-white border border-dark-line rounded-lg shadow-lg w-full max-w-lg p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-white">Edit Product</h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-white text-xl">&times;</button>
        </div>

        <form method="POST" action="" id="editProductForm">
            @csrf
            @method('PUT')
            <input type="hidden" name="product_id" id="edit-product-id">

            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="block text-sm mb-1">Product Name</label>
                    <input type="text" name="product_name" id="edit-product-name" class="w-full bg-sidebar text-white rounded-md border border-dark-line px-3 py-2 text-sm" required>
                </div>

                <div>
                    <label class="block text-sm mb-1">Batch Number</label>
                    <input type="text" name="batch_number" id="edit-batch-number" class="w-full bg-sidebar text-white rounded-md border border-dark-line px-3 py-2 text-sm" required>
                </div>

                <div>
                    <label class="block text-sm mb-1">Production Date</label>
                    <input type="date" name="production_date" id="edit-production-date" class="w-full bg-sidebar text-white rounded-md border border-dark-line px-3 py-2 text-sm" required>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm mb-1">Quantity</label>
                        <input type="number" name="quantity" id="edit-quantity" class="w-full bg-sidebar text-white rounded-md border border-dark-line px-3 py-2 text-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Stock Status</label>
                        <select name="stock_status" id="edit-stock-status" class="w-full bg-sidebar text-white rounded-md border border-dark-line px-3 py-2 text-sm" required>
                            <option value="In Stock">In Stock</option>
                            <option value="Out of Stock">Out of Stock</option>
                            <option value="Low Stock">Low Stock</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-4">
                    <button type="button" onclick="closeEditModal()" class="text-sm px-4 py-2 rounded-md border border-dark-line text-gray-300 hover:text-white hover:bg-sidebar-hover transition">
                        Cancel
                    </button>
                    <button type="submit" class="btn-armygreen text-sm px-4 py-2 rounded-md shadow transition">
                        Update Product
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
