<!-- Edit Sale Modal -->
<div id="editSaleModal" class="hidden fixed inset-0 overflow-y-auto z-[1000]">
  <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
    <div class="fixed inset-0 transition-opacity" aria-hidden="true">
      <div class="absolute inset-0 bg-black opacity-70"></div>
    </div>
    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

    <div class="inline-block align-bottom liquid-modal text-left overflow-hidden transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
      <form method="POST" action="#" id="editSaleForm">
        @csrf
        @method('PUT')

        <div class="liquid-modal-header">
          <h3 class="liquid-modal-title">Edit Sale</h3>
          <button type="button" onclick="closeEditModal()" class="absolute top-2 right-4 text-white text-xl font-bold hover:text-red-500">&times;</button>
        </div>

        <div class="liquid-modal-body">
          <div class="grid grid-cols-1 gap-y-6 sm:grid-cols-6">
            <input type="hidden" id="edit-sale-id">

            <!-- Product Selector -->
            <div class="sm:col-span-6 form-group">
              <label class="liquid-label">Product</label>
              <select id="edit-product-id" class="liquid-input">
                @foreach($products as $p)
                  <option value="{{ $p->id }}" data-price="{{ $p->price }}">{{ $p->product_name }}</option>
                @endforeach
              </select>
              <small class="text-xs text-gray-400 block mt-1">
                Changing this will only update the default price shown below; it won’t move the sale to another product.
              </small>
            </div>

            <!-- Date -->
            <div class="sm:col-span-3 form-group">
              <label class="liquid-label">Date</label>
              <input type="date" name="date" id="edit-date" class="liquid-input" required>
            </div>

            <!-- Quantity -->
            <div class="sm:col-span-3 form-group">
              <label class="liquid-label">Quantity</label>
              <input type="number" name="quantity" id="edit-quantity" min="1" class="liquid-input" required>
            </div>

            <!-- Price -->
            <div class="sm:col-span-3 form-group">
              <label class="liquid-label">Unit Price (₱)</label>
              <input type="number" step="0.01" name="price" id="edit-price" min="0" class="liquid-input" required>
            </div>

            <!-- Status -->
            <div class="sm:col-span-3 form-group">
              <label class="liquid-label">Status</label>
              <select name="status" id="edit-status" class="liquid-input" required>
                <option value="Paid">Paid</option>
                <option value="Pending">Pending</option>
                <option value="Completed">Completed</option>
                <option value="Cancelled">Cancelled</option>
                <option value="Refunded">Refunded</option>
              </select>
            </div>
          </div>
        </div>

        <div class="liquid-modal-footer">
          <button type="button" onclick="closeEditModal()" class="modal-liquid-btn modal-liquid-btn-secondary">
            Cancel
          </button>
          <button type="submit" class="modal-liquid-btn modal-liquid-btn-primary">
            Update Sale
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Script --}}
<script>
  function setPriceFromProduct(selectEl, priceInputEl) {
    if (!selectEl || !priceInputEl) return;
    const opt = selectEl.options[selectEl.selectedIndex];
    priceInputEl.value = opt ? opt.getAttribute('data-price') : '';
  }

  async function openEditModal(id) {
    const res  = await fetch(`/sales/${id}/edit`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const sale = await res.json();

    const modal      = document.getElementById('editSaleModal');
    const idInput    = document.getElementById('edit-sale-id');
    const prodSel    = document.getElementById('edit-product-id');
    const dateInput  = document.getElementById('edit-date');
    const qtyInput   = document.getElementById('edit-quantity');
    const priceInput = document.getElementById('edit-price');
    const statusSel  = document.getElementById('edit-status');
    const form       = document.getElementById('editSaleForm');

    idInput.value = sale.id;
    prodSel.value = String(sale.product_id);
    const d = new Date(sale.date);
    dateInput.value = isNaN(d) ? '' : d.toISOString().slice(0,10);
    qtyInput.value   = sale.quantity ?? 1;
    priceInput.value = sale.price ?? '';
    if (!priceInput.value) setPriceFromProduct(prodSel, priceInput);
    statusSel.value  = sale.status ?? 'Paid';
    form.action      = `/sales/${sale.id}`;
    prodSel.addEventListener('change', () => setPriceFromProduct(prodSel, priceInput));
    modal.classList.remove('hidden');
  }

  function closeEditModal() {
    document.getElementById('editSaleModal').classList.add('hidden');
  }
</script>
