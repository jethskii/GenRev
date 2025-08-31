<!-- ===================== Edit Sale Modal ===================== -->
<div id="editSaleModal" class="hidden fixed inset-0 overflow-y-auto z-[1000]">
  <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
    <!-- Backdrop -->
    <div class="fixed inset-0 transition-opacity" aria-hidden="true">
      <div class="absolute inset-0 bg-black opacity-70"></div>
    </div>
    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

    <!-- Card -->
    <div class="inline-block align-bottom liquid-modal text-left overflow-hidden transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
      <form method="POST" action="#" id="editSaleForm" novalidate>
        @csrf
        @method('PUT')

        <!-- Header -->
        <div class="liquid-modal-header">
          <h3 class="liquid-modal-title">Edit Sale</h3>
          <button type="button" onclick="closeEditModal()" class="absolute top-2 right-4 text-white text-xl font-bold hover:text-red-500" aria-label="Close">&times;</button>
        </div>

        <!-- Body -->
        <div class="liquid-modal-body">
          <div class="grid grid-cols-1 gap-y-6 sm:grid-cols-6">
            <input type="hidden" id="edit-sale-id">

            <!-- Product -->
            <div class="sm:col-span-6 form-group">
              <label class="liquid-label" for="edit-product-id">Product</label>
              <select id="edit-product-id" class="liquid-input">
                @foreach($products as $p)
                  <option value="{{ $p->id }}" data-price="{{ (float)($p->price ?? 0) }}">{{ $p->product_name }}</option>
                @endforeach
              </select>
              <small class="text-xs text-gray-400 block mt-1">
                Changing this updates the default unit price shown below.
              </small>
            </div>

            <!-- Order / Invoice No. (read-only) -->
            <div class="sm:col-span-6 form-group">
              <label class="liquid-label" for="edit-order-number">Order / Invoice No.</label>
              <input type="text" id="edit-order-number" class="liquid-input" readonly>
              <small class="text-xs text-gray-400 block mt-1">Auto-generated; cannot be edited.</small>
            </div>

            <!-- Date -->
            <div class="sm:col-span-3 form-group">
              <label class="liquid-label" for="edit-date">Date</label>
              <input type="date" name="date" id="edit-date" class="liquid-input" required>
            </div>

            <!-- Quantity (kg) -->
            <div class="sm:col-span-3 form-group">
              <label class="liquid-label" for="edit-quantity">Quantity (kg)</label>
              <input type="number" name="quantity" id="edit-quantity" min="0.001" step="0.001" class="liquid-input" required>
            </div>

            <!-- Unit Price -->
            <div class="sm:col-span-3 form-group">
              <label class="liquid-label" for="edit-price">Unit Price (₱)</label>
              <input type="number" step="0.01" name="price" id="edit-price" min="0" class="liquid-input" required>
            </div>

            <!-- Status -->
            <div class="sm:col-span-3 form-group">
              <label class="liquid-label" for="edit-status">Status</label>
              <select name="status" id="edit-status" class="liquid-input" required>
                <option value="Paid">Paid</option>
                <option value="Pending">Pending</option>
                <option value="Completed">Completed</option>
                <option value="Cancelled">Cancelled</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Footer -->
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

{{-- ===================== Script ===================== --}}
<script>
(function () {
  const UPDATE_URL_BASE = "{{ url('/sales') }}"; // consistent base
  let productChangeHandlerBound = false;

  function parseNum(val, def = null) {
    if (val === null || val === undefined || val === '') return def;
    const n = typeof val === 'number' ? val : parseFloat(val);
    return isNaN(n) ? def : n;
  }

  function normalizeDateString(val) {
    // Accepts 'YYYY-MM-DD' or ISO strings with Z
    if (!val) return '';
    if (/^\d{4}-\d{2}-\d{2}$/.test(val)) return val;
    const d = new Date(val);
    // Normalize to local date to avoid off-by-one due to 'Z'
    return isNaN(d) ? '' : new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
  }

  function pick(value, ...fallbacks) {
    const all = [value, ...fallbacks];
    for (const v of all) if (v !== undefined && v !== null && v !== '') return v;
    return null;
  }

  function setPriceFromProduct(selectEl, priceInputEl) {
    const opt = selectEl?.options[selectEl.selectedIndex];
    const p = parseNum(opt?.getAttribute('data-price'), null);
    if ((priceInputEl.value === '' || parseNum(priceInputEl.value) === 0) && p !== null) {
      priceInputEl.value = p;
    }
  }

  async function safeFetch(url, opts = {}) {
    try {
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, ...opts });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      return await res.json();
    } catch (err) {
      console.error('Edit sale fetch failed:', err);
      alert('Failed to load sale details. Please try again.');
      return null;
    }
  }

  // Public: call window.openEditModal(id) from your table "Edit" buttons
  async function openEditModal(id) {
    const sale = await safeFetch(`${UPDATE_URL_BASE}/${id}/edit`);
    if (!sale) return;

    const modal      = document.getElementById('editSaleModal');
    const idInput    = document.getElementById('edit-sale-id');
    const prodSel    = document.getElementById('edit-product-id');
    const dateInput  = document.getElementById('edit-date');
    const qtyInput   = document.getElementById('edit-quantity');
    const priceInput = document.getElementById('edit-price');
    const statusSel  = document.getElementById('edit-status');
    const form       = document.getElementById('editSaleForm');
    const orderNoEl  = document.getElementById('edit-order-number');

    // Map across new or legacy schema payloads
    const saleDate = pick(sale.order_date, sale.date, sale.sale_date);
    const qty      = pick(parseNum(sale.quantity_kg), parseNum(sale.quantity), 1);
    const unit     = pick(parseNum(sale.unit_price), parseNum(sale.price), null);

    idInput.value     = sale.id;
    prodSel.value     = String(sale.product_id || '');
    dateInput.value   = normalizeDateString(saleDate);
    qtyInput.value    = qty;
    priceInput.value  = unit !== null ? unit : '';
    orderNoEl.value   = pick(sale.order_number, sale.invoice_number, ''); // view only

    if (!priceInput.value) setPriceFromProduct(prodSel, priceInput);

    const allowed = ['Paid','Pending','Completed','Cancelled'];
    statusSel.value = allowed.includes(sale.status) ? sale.status : 'Completed';

    // PUT /sales/{id}
    form.action = `${UPDATE_URL_BASE}/${sale.id}`;

    // Avoid double-binding if modal is opened multiple times
    if (!productChangeHandlerBound) {
      prodSel.addEventListener('change', () => setPriceFromProduct(prodSel, priceInput));
      productChangeHandlerBound = true;
    }

    modal.classList.remove('hidden');
  }

  function closeEditModal() {
    document.getElementById('editSaleModal').classList.add('hidden');
  }

  // Expose to global scope so buttons can call it
  window.openEditModal = openEditModal;
  window.closeEditModal = closeEditModal;
})();
</script>
