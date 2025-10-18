<!-- ===================== Edit Sale Modal (viewport-safe, scrollable) ===================== -->
<style>
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
    max-height:88svh;
    overflow:hidden;
    animation:fadeIn .18s ease-out both;
  }
  .head-bar{height:3px; background:linear-gradient(90deg,var(--red),var(--green),var(--blue))}
  .card-header{position:sticky; top:0; z-index:1; background:#fff; border-bottom:1px solid var(--line); padding:1rem 1.5rem}
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
  .footer{padding:.75rem 1.5rem 1.25rem;border-top:1px solid var(--line);background:#fff}
  @keyframes fadeIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}
</style>

<div id="editSaleModal" class="modal-wrap" role="dialog" aria-modal="true" aria-labelledby="editSaleTitle">
  <div class="modal-backdrop" onclick="closeEditModal()" aria-hidden="true"></div>

  <div class="card">
    <div class="head-bar" aria-hidden="true"></div>

    <div class="card-header">
      <div class="flex items-center justify-between">
        <h3 id="editSaleTitle" class="text-xl font-semibold">Edit Sale</h3>
        <button type="button" onclick="closeEditModal()" class="btn btn-outline" aria-label="Close">✖</button>
      </div>
    </div>

    <div class="card-body">
      <form method="POST" action="#" id="editSaleForm" novalidate>
        @csrf
        @method('PUT')

        <input type="hidden" id="edit-sale-id">

        <div class="grid grid-cols-1 gap-y-5 sm:grid-cols-6">
          <!-- Product -->
          <div class="sm:col-span-6">
            <label class="label" for="edit-product-id">Product</label>
            <select id="edit-product-id" name="product_id" class="select" required>
              @foreach($products as $p)
                <option value="{{ $p->id }}" data-price="{{ (float)($p->price ?? 0) }}">{{ $p->product_name }}</option>
              @endforeach
            </select>
            <div class="hint">Changing this may update default unit price shown below.</div>
          </div>

          <!-- Order / Invoice No. -->
          <div class="sm:col-span-6">
            <label class="label" for="edit-order-number">Order / Invoice No.</label>
            <input type="text" id="edit-order-number" class="input" readonly aria-readonly="true">
            <div class="hint">Auto-generated; not editable.</div>
          </div>

          <!-- Date -->
          <div class="sm:col-span-3">
            <label class="label" for="edit-date">Date</label>
            <input type="date" name="date" id="edit-date" class="input" required>
          </div>

          <!-- Quantity -->
          <div class="sm:col-span-3">
            <label class="label" for="edit-quantity">Quantity</label>
            <input type="number" name="quantity" id="edit-quantity" min="0.001" step="0.001" class="input" required>
          </div>

          <!-- Unit Type -->
          <div class="sm:col-span-3">
            <label class="label" for="edit-unit-type">Unit Type</label>
            <select name="unit_type" id="edit-unit-type" class="select">
              <option value="">Auto</option>
              <option value="pack">Per Pack</option>
              <option value="bag">Per Bag</option>
            </select>
            <div class="hint">Leave on Auto to let the system pick from the batch.</div>
          </div>

          <!-- Unit Price -->
          <div class="sm:col-span-3">
            <label class="label" for="edit-price">Unit Price (₱)</label>
            <input type="number" step="0.01" name="price" id="edit-price" min="0" class="input" placeholder="Leave blank for auto">
            <div class="hint">If blank, the server uses the batch’s unit price for the chosen unit type.</div>
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

          <!-- Batch (optional) -->
          <div class="sm:col-span-3">
            <label class="label" for="edit-production-id">Batch</label>
            <select id="edit-production-id" name="production_id" class="select">
              <option value="">Auto (nearest/latest)</option>
              @if(isset($batches))
                @foreach($batches as $b)
                  <option
                    value="{{ $b->id }}"
                    data-inv="{{ (float)($b->current_inventory ?? 0) }}"
                  >
                    {{ $b->batch_number }} — Inv {{ (float)($b->current_inventory ?? 0) }}
                  </option>
                @endforeach
              @endif
            </select>
            <div id="edit-batch-info" class="hint hidden"></div>
          </div>
        </div>

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
  const API_READ_URL = (id) => (window.saleApiUrl ? `${window.saleApiUrl}/${id}` : `{{ url('/api/sales') }}/${id}`);
  const UPDATE_URL_BASE = "{{ url('/sales') }}";

  const $ = (id) => document.getElementById(id);
  const fmtDate = (v) => {
    if (!v) return '';
    if (/^\d{4}-\d{2}-\d{2}$/.test(v)) return v;
    const d = new Date(v);
    return isNaN(d) ? '' : new Date(d.getTime() - d.getTimezoneOffset()*60000).toISOString().slice(0,10);
  };
  const num = (v, d=null) => v===''||v==null?d:(isNaN(+v)?d:+v);

  function openWrap(){ const m=$('editSaleModal'); if(!m) return; m.classList.add('flex'); m.classList.remove('hidden'); setTimeout(()=>{$('edit-product-id')?.focus()},50) }
  function closeEditModal(){ const m=$('editSaleModal'); if(!m) return; m.classList.add('hidden'); m.classList.remove('flex'); }
  window.closeEditModal = closeEditModal;

  document.addEventListener('keydown', e => { if (e.key==='Escape') closeEditModal() });

  function applyBatchInfo(inv){
    const info = $('edit-batch-info');
    if (inv!=null && inv!=='') {
      info.textContent = `Batch available inventory: ${inv}`;
      info.classList.remove('hidden');
      const qty = $('edit-quantity');
      qty?.setAttribute('max', inv);
      if (qty && qty.value && +qty.value > +inv) qty.value = inv;
    } else {
      info.classList.add('hidden');
      $('edit-quantity')?.removeAttribute('max');
    }
  }

  function wireFieldEvents(){
    const prodSel = $('edit-product-id');
    const price   = $('edit-price');
    const batchSel= $('edit-production-id');

    if (batchSel && !batchSel._wired) {
      batchSel.addEventListener('change', () => {
        const inv = batchSel.options[batchSel.selectedIndex]?.dataset?.inv ?? '';
        applyBatchInfo(inv);
      });
      batchSel._wired = true;
    }

    if (prodSel && !prodSel._wired) {
      prodSel.addEventListener('change', () => {
        // If user hasn’t set price, let it stay blank (server will resolve)
        // But if product has a default price and price empty, we can show it.
        const p = prodSel.options[prodSel.selectedIndex]?.getAttribute('data-price');
        if ((price.value==='' || +price.value===0) && p!=null) price.value = p;
      });
      prodSel._wired = true;
    }
  }

  async function fetchSaleJson(id){
    try {
      const res = await fetch(API_READ_URL(id), { headers:{'X-Requested-With':'XMLHttpRequest'} });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      return await res.json();
    } catch (e) {
      console.error('Fetch sale failed', e);
      alert('Failed to load sale details.');
      return null;
    }
  }

  /**
   * Public opener:
   * - openEditModal(123) -> fetches /api/sales/123 (expects JSON)
   * - openEditModal({...}) -> uses provided object (must include id, product_id, etc.)
   */
  async function openEditModal(saleOrId){
    let sale = null;

    if (typeof saleOrId === 'number' || /^\d+$/.test(String(saleOrId))) {
      sale = await fetchSaleJson(saleOrId);
      if (!sale) return;
    } else if (typeof saleOrId === 'object' && saleOrId) {
      sale = saleOrId;
    } else {
      console.warn('openEditModal called with invalid param');
      return;
    }

    const idInput   = $('edit-sale-id');
    const prodSel   = $('edit-product-id');
    const dateInput = $('edit-date');
    const qtyInput  = $('edit-quantity');
    const unitSel   = $('edit-unit-type');
    const price     = $('edit-price');
    const statusSel = $('edit-status');
    const form      = $('editSaleForm');
    const orderNoEl = $('edit-order-number');
    const batchSel  = $('edit-production-id');

    const saleDate  = sale.order_date || sale.date || sale.sale_date || '';
    const qty       = num(sale.quantity_kg ?? sale.quantity, 1);
    const unitPrice = num(sale.unit_price ?? sale.price, null);

    idInput.value   = sale.id;
    prodSel.value   = String(sale.product_id || '');
    dateInput.value = fmtDate(saleDate);
    qtyInput.value  = qty;

    // unit type (if present on row)
    const unitType = sale.unit_type || sale.unit || '';
    unitSel.value = (unitType==='pack'||unitType==='bag') ? unitType : '';

    price.value = (unitPrice!=null ? unitPrice : ''); // blank means "auto" on server
    orderNoEl.value = sale.order_number || sale.invoice_number || '';

    const allowed = ['Paid','Pending','Completed','Cancelled'];
    statusSel.value = allowed.includes(sale.status) ? sale.status : 'Completed';

    // Setup batch (if list is present in DOM)
    if (batchSel) {
      if (sale.production_id) batchSel.value = String(sale.production_id);
      const inv = batchSel.options[batchSel.selectedIndex]?.dataset?.inv ?? '';
      applyBatchInfo(inv);
    }

    // PUT /sales/{id}
    form.action = `${UPDATE_URL_BASE}/${sale.id}`;

    wireFieldEvents();
    openWrap();
  }
  window.openEditModal = openEditModal;
})();
</script>
