<!-- ===================== Edit Sale Modal (viewport-safe, scrollable) ===================== -->
<style>
  /* Keep tokens aligned with the light add-sale modal */
  :root{
    --bg-offwhite:#f7f7f5; --ink:#0f172a; --muted:#475569; --line:#e5e7eb;
    --red:#dc2626; --green:#16a34a; --blue:#2563eb;
    --green-50:#f0fdf4; --green-700:#15803d;
  }
  .modal-wrap{
    position:fixed; inset:0; z-index:1000;
    display:none; align-items:center; justify-content:center;
    padding:1rem;
  }
  .modal-wrap.flex{display:flex}
  .modal-backdrop{position:absolute; inset:0; background:rgba(15,23,42,.45); backdrop-filter:blur(2px)}
  .card{
    position:relative; width:100%; max-width:40rem;
    background:#fff; color:var(--ink);
    border:1px solid var(--line); border-radius:1rem;
    box-shadow:0 2px 8px rgba(0,0,0,.06), 0 24px 48px rgba(0,0,0,.08);
    display:flex; flex-direction:column;
    max-height:88svh; /* viewport-safe height */
    overflow:hidden;
    animation:fadeIn .18s ease-out both;
  }
  .head-bar{height:3px; background:linear-gradient(90deg,var(--red),var(--green),var(--blue))}
  .card-header{
    position:sticky; top:0; z-index:1;
    background:#fff; border-bottom:1px solid var(--line);
    padding:1rem 1.5rem;
  }
  .card-body{overflow-y:auto; padding:1rem 1.5rem 1.25rem}
  .label{font-size:.85rem;color:var(--muted);margin-bottom:.35rem;display:block}
  .input,.select{
    width:100%;background:#fff;color:var(--ink);
    border:1px solid var(--line);border-radius:.75rem;
    padding:.65rem .85rem;transition:box-shadow .15s,border-color .15s
  }
  .input:focus,.select:focus{outline:0;border-color:var(--blue);box-shadow:0 0 0 3px rgba(37,99,235,.15)}
  .hint{font-size:.75rem;color:var(--muted);margin-top:.35rem}
  .btn{display:inline-flex;align-items:center;gap:.5rem;border-radius:.75rem;padding:.6rem .9rem;font-weight:700;border:1px solid transparent;transition:filter .12s}
  .btn-outline{background:#fff;border-color:var(--line);color:var(--ink)}
  .btn-outline:hover{filter:brightness(.98)}
  .btn-primary{background:var(--green);color:#fff}
  .btn-primary:hover{filter:brightness(.97)}
  .footer{
    padding: .75rem 1.5rem 1.25rem;
    border-top:1px solid var(--line);
    background:#fff;
  }
  @keyframes fadeIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}
</style>

<div id="editSaleModal" class="modal-wrap" role="dialog" aria-modal="true" aria-labelledby="editSaleTitle">
  <!-- Backdrop -->
  <div class="modal-backdrop" onclick="closeEditModal()" aria-hidden="true"></div>

  <!-- Card -->
  <div class="card">
    <div class="head-bar" aria-hidden="true"></div>

    <!-- Header -->
    <div class="card-header">
      <div class="flex items-center justify-between">
        <h3 id="editSaleTitle" class="text-xl font-semibold">Edit Sale</h3>
        <button type="button" onclick="closeEditModal()" class="btn btn-outline" aria-label="Close">✖</button>
      </div>
    </div>

    <!-- Body -->
    <div class="card-body">
      <form method="POST" action="#" id="editSaleForm" novalidate>
        @csrf
        @method('PUT')

        <input type="hidden" id="edit-sale-id">

        <div class="grid grid-cols-1 gap-y-5 sm:grid-cols-6">
          <!-- Product -->
          <div class="sm:col-span-6">
            <label class="label" for="edit-product-id">Product</label>
            <select id="edit-product-id" class="select" required>
              @foreach($products as $p)
                <option value="{{ $p->id }}" data-price="{{ (float)($p->price ?? 0) }}">{{ $p->product_name }}</option>
              @endforeach
            </select>
            <div class="hint">Changing this updates the default unit price shown below.</div>
          </div>

          <!-- Order / Invoice No. (read-only) -->
          <div class="sm:col-span-6">
            <label class="label" for="edit-order-number">Order / Invoice No.</label>
            <input type="text" id="edit-order-number" class="input" readonly>
            <div class="hint">Auto-generated; cannot be edited.</div>
          </div>

          <!-- Date -->
          <div class="sm:col-span-3">
            <label class="label" for="edit-date">Date</label>
            <input type="date" name="date" id="edit-date" class="input" required>
          </div>

          <!-- Quantity (kg) -->
          <div class="sm:col-span-3">
            <label class="label" for="edit-quantity">Quantity (kg)</label>
            <input type="number" name="quantity" id="edit-quantity" min="0.001" step="0.001" class="input" required>
          </div>

          <!-- Unit Price -->
          <div class="sm:col-span-3">
            <label class="label" for="edit-price">Unit Price (₱)</label>
            <input type="number" step="0.01" name="price" id="edit-price" min="0" class="input" required>
          </div>

          <!-- Status -->
          <div class="sm:col-span-3">
            <label class="label" for="edit-status">Status</label>
            <select name="status" id="edit-status" class="select" required>
              <option value="Paid">Paid</option>
              <option value="Pending">Pending</option>
              <option value="Completed">Completed</option>
              <option value="Cancelled">Cancelled</option>
            </select>
          </div>
        </div>

        <!-- Footer (inside form so it's always visible while scrolling) -->
        <div class="footer flex items-center justify-end gap-2">
          <button type="button" onclick="closeEditModal()" class="btn btn-outline">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Sale</button>
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

  const $ = (id) => document.getElementById(id);

  function parseNum(val, def = null) {
    if (val === null || val === undefined || val === '') return def;
    const n = typeof val === 'number' ? val : parseFloat(val);
    return isNaN(n) ? def : n;
  }

  function normalizeDateString(val) {
    if (!val) return '';
    if (/^\d{4}-\d{2}-\d{2}$/.test(val)) return val;
    const d = new Date(val);
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

  // Open/Close helpers
  function openWrap() {
    const m = $('editSaleModal');
    if (!m) return;
    m.classList.add('flex');
    m.classList.remove('hidden');
    // Focus first field for speed
    setTimeout(() => { $('edit-product-id')?.focus(); }, 50);
  }
  function closeEditModal() {
    const m = $('editSaleModal');
    if (!m) return;
    m.classList.add('hidden');
    m.classList.remove('flex');
  }
  window.closeEditModal = closeEditModal;

  // Esc to close
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeEditModal();
  });

  // Public: call window.openEditModal(id) from your table "Edit" buttons
  async function openEditModal(id) {
    const sale = await safeFetch(`${UPDATE_URL_BASE}/${id}/edit`);
    if (!sale) return;

    const idInput    = $('edit-sale-id');
    const prodSel    = $('edit-product-id');
    const dateInput  = $('edit-date');
    const qtyInput   = $('edit-quantity');
    const priceInput = $('edit-price');
    const statusSel  = $('edit-status');
    const form       = $('editSaleForm');
    const orderNoEl  = $('edit-order-number');

    // Map across new or legacy schema payloads
    const saleDate = pick(sale.order_date, sale.date, sale.sale_date);
    const qty      = pick(parseNum(sale.quantity_kg), parseNum(sale.quantity), 1);
    const unit     = pick(parseNum(sale.unit_price), parseNum(sale.price), null);

    idInput.value     = sale.id;
    prodSel.value     = String(sale.product_id || '');
    dateInput.value   = normalizeDateString(saleDate);
    qtyInput.value    = qty;
    priceInput.value  = unit !== null ? unit : '';
    orderNoEl.value   = pick(sale.order_number, sale.invoice_number, '');

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

    openWrap();
  }

  // Expose to global scope so buttons can call it
  window.openEditModal = openEditModal;
})();
</script>
