{{-- Add Order Modal (parent-aware) --}}
<div id="addOrderModal" class="fixed inset-0 z-[9999] hidden">
  <div class="absolute inset-0 bg-black/40" onclick="closeOrderModal()" aria-hidden="true"></div>

  <div class="relative mx-auto my-10 max-w-md w-[92%] page-card animate-fadeIn">
    <button type="button" onclick="closeOrderModal()" aria-label="Close"
            class="absolute top-2 right-4 text-2xl font-bold text-[color:var(--muted)] hover:text-[color:var(--red)]">&times;</button>

    <h3 id="modalTitle" class="text-xl font-semibold mb-4">
      Add Order ({{ $product->product_name }})
    </h3>

    {{-- POST to production.orders.store --}}
    <form id="addOrderForm" action="{{ route('production.orders.store') }}" method="POST" class="space-y-3">
      @csrf

      {{-- parent product = current page --}}
      <input type="hidden" name="parent_product_id" id="po_parent_product_id" value="{{ (int) $product->id }}">

      {{-- selected variant (child) --}}
      <input type="hidden" id="po_product_id" name="product_id" value="{{ (int) $product->id }}">

      {{-- legacy snapshot (kept in sync with type_label) --}}
      <input type="hidden" id="po_product_name" name="product_name_snapshot" value="{{ $product->product_name }}">

      {{-- mirrors the computed expiration --}}
      <input type="hidden" id="po_expiration_date" name="expiration_date" value="{{ old('expiration_date', $defaultExpiry ?? '') }}">

      {{-- Variant selector --}}
      <div>
        <label class="label">Variant / Product</label>
        <select id="po_product_select" class="select">
          <option value="{{ $product->id }}">{{ $product->product_name }} (Base)</option>
          @foreach(($variantProducts ?? collect()) as $vp)
            <option value="{{ $vp->id }}">{{ $vp->product_name }}</option>
          @endforeach
        </select>

        <div class="mt-2 flex items-center gap-2 flex-wrap">
          <button type="button" id="po_new_toggle" class="btn btn-outline px-2 py-1 text-sm">+ New variant</button>
          @php $chips=['Regular skinless','Special skinless','Garlic skinless','Chicken skinless','Beef skinless','Hamonado']; @endphp
          @foreach($chips as $chip)
            <button type="button" class="chip js-type-chip" data-value="{{ $chip }}">{{ $chip }}</button>
          @endforeach
        </div>

        {{-- Type label saved per order (drives the Type column) --}}
        <div class="mt-3">
          <label class="label">Type / Variant label to record</label>
          <input id="po_type_label" name="type_label" type="text" class="input"
                 placeholder="e.g., Garlic skinless" value="{{ old('type_label') }}">
          <p class="help mt-1">This is saved with this order only and will appear in the Type column.</p>
        </div>

        {{-- inline quick-add variant --}}
        <div id="po_new_wrap" class="mt-3 hidden rounded-xl border border-[color:var(--line)] bg-white p-3">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="sm:col-span-2">
              <label class="label">New Variant Name</label>
              <input id="po_new_name" type="text" class="input" placeholder="e.g., Garlic skinless, Regular skinless" />
              <p class="help mt-1">New variant will be created under {{ $product->product_name }}.</p>
            </div>
            <div>
              <label class="label">Unit Cost (₱)</label>
              <input id="po_new_cost" type="number" step="0.01" min="0" class="input" />
            </div>
            <div>
              <label class="label">Shelf Life (days)</label>
              <input id="po_new_shelf" type="number" min="1" class="input" value="{{ (int)($product->shelf_life_days ?? 7) }}" />
            </div>
          </div>
          <div class="mt-3 flex items-center gap-2">
            <button type="button" id="po_new_save" class="btn btn-primary text-sm">Save variant</button>
            <button type="button" id="po_new_cancel" class="btn btn-outline text-sm">Cancel</button>
            <span id="po_new_err" class="text-xs text-[color:var(--red)] ml-2 hidden"></span>
          </div>
        </div>
      </div>

      {{-- Batch Number (preview only) --}}
      <div>
        <label class="label">Batch Number</label>
        <input id="po_batch_preview" class="input cursor-not-allowed bg-gray-50"
               value="{{ $nextBatchNumber ?? 'Auto' }}" readonly>
      </div>

      {{-- Forecasted / Produced --}}
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="label">Forecasted Demand (kg)</label>
          <input id="po_fc" name="forecasted_demand" type="number" step="0.01" min="0" class="input"
                 value="{{ old('forecasted_demand', (float)($product->forecasted_demand ?? 0)) }}">
        </div>
        <div>
          <label class="label">Produced Quantity (kg)</label>
          <input id="po_prod_qty" name="quantity" type="number" step="1" min="1" required class="input"
                 value="{{ old('quantity') }}">
        </div>
      </div>

      {{-- =======================
           Unit Cost / Prices
           + Availability
         ======================= --}}
      <style>
        .accent-pack{border:1px solid rgba(237,209,0,.45);border-radius:12px;padding:12px;box-shadow:0 0 0 1px rgba(237,209,0,.18) inset}
        .accent-bag{border:1px solid rgba(220,38,38,.45);border-radius:12px;padding:12px;box-shadow:0 0 0 1px rgba(220,38,38,.18) inset}
        .badge{display:inline-flex;align-items:center;font-weight:700;font-size:.75rem;border-radius:8px;padding:.15rem .5rem}
        .badge-pack{background:#EDD100;color:#1F1E1E}
        .badge-bag{background:#dc2626;color:#fff}
        .qty-input{text-align:center;font-weight:600}
      </style>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        {{-- Unit Cost --}}
        <div>
          <label class="label">Unit Cost (₱)</label>
          <input id="po_cost" name="unit_cost" type="number" step="0.01" min="0" required class="input"
                 value="{{ old('unit_cost', (float)($product->unit_cost ?? 0)) }}">
        </div>

        {{-- Per Pack (yellow) --}}
        <div class="accent-pack">
          <div class="flex items-center justify-between mb-2">
            <label class="label m-0">Per Pack</label>
            <span class="badge badge-pack">PACK</span>
          </div>
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="label">Price per Pack (₱)</label>
              <input id="po_price_pack" name="unit_price_pack" type="number" step="0.01" min="0"
                     class="input" value="{{ old('unit_price_pack') }}" placeholder="0.00">
            </div>
            <div>
              <label class="label">Available Packs</label>
              <input id="po_available_pack" name="available_pack" type="number" step="1" min="0"
                     class="input qty-input" value="{{ old('available_pack', 0) }}" placeholder="Qty">
            </div>
          </div>
        </div>

        {{-- Per Bag (red) --}}
        <div class="accent-bag">
          <div class="flex items-center justify-between mb-2">
            <label class="label m-0">Per Bag</label>
            <span class="badge badge-bag">BAG</span>
          </div>
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="label">Price per Bag (₱)</label>
              <input id="po_price_bag" name="unit_price_bag" type="number" step="0.01" min="0"
                     class="input" value="{{ old('unit_price_bag') }}" placeholder="0.00">
            </div>
            <div>
              <label class="label">Available Bags</label>
              <input id="po_available_bag" name="available_bag" type="number" step="1" min="0"
                     class="input qty-input" value="{{ old('available_bag', 0) }}" placeholder="Qty">
            </div>
          </div>
        </div>
      </div>

      {{-- Dates --}}
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="label">Production Date</label>
          <input id="po_prod_date" name="production_date" type="date" required class="input"
                 value="{{ old('production_date', $defaultProdDate ?? now()->toDateString()) }}">
        </div>
        <div>
          <label class="label">Expiration Date (auto)</label>
          <input id="po_exp_preview" type="date" readonly class="input cursor-not-allowed bg-gray-50"
                 value="{{ old('expiration_date', $defaultExpiry ?? '') }}">
          <p class="help mt-1">Computed from production date + {{ (int)($product->shelf_life_days ?? 7) }} days.</p>
        </div>
      </div>

      {{-- Optional fields --}}
      <div>
        <label class="label">Order Date</label>
        <input id="po_order_date" name="order_date" type="date" class="input"
               value="{{ old('order_date', now()->toDateString()) }}">
        <p class="help mt-1">Leave blank to use today.</p>
      </div>
      <div>
        <label class="label">Order Quantity (kg)</label>
        <input id="po_order_qty" name="order_quantity_kg" type="number" step="0.01" class="input"
               value="{{ old('order_quantity_kg') }}">
      </div>
      <div>
        <label class="label">Customer (optional)</label>
        <input name="customer_name" class="input" value="{{ old('customer_name') }}" placeholder="Walk-in, Distributor A, etc.">
      </div>
      <div>
        <label class="label">Notes</label>
        <textarea name="notes" rows="2" class="textarea" placeholder="Special handling, delivery date, etc.">{{ old('notes') }}</textarea>
      </div>

      <button id="submitAddOrder" class="btn btn-primary w-full">Add Order (Production + Sale)</button>
    </form>
  </div>
</div>

<script>
  const $$ = id => document.getElementById(id);

  function openOrderModal(){
    syncExpiryPreview();
    const sel = $$('#po_product_select');
    if (sel) {
      const opt = sel.options[sel.selectedIndex];
      const name = opt.textContent.replace(/\s*\(Base\)\s*$/,'').trim();
      updateFormForProduct(opt.value, name);
      autoFillCostPriceForName(name);
    }
    const m = $$('#addOrderModal'); if (!m) return;
    m.classList.remove('hidden'); m.classList.add('flex');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
  function closeOrderModal(){ const m=$$('#addOrderModal'); if(!m) return; m.classList.add('hidden'); m.classList.remove('flex'); }

  function syncExpiryPreview(){
    const shelf = {{ (int)($product->shelf_life_days ?? 7) }};
    const prod  = $$('#po_prod_date');
    const expPv = $$('#po_exp_preview');
    const expHidden = $$('#po_expiration_date');
    if(!prod || !expPv || !prod.value) return;
    const d = new Date(prod.value);
    d.setDate(d.getDate() + shelf);
    const iso = new Date(d.getTime() - d.getTimezoneOffset()*60000).toISOString().slice(0,10);
    expPv.value = iso; expPv.min = prod.value;
    if (expHidden) expHidden.value = iso;
  }
  document.addEventListener('change', e => { if (e.target && e.target.id === 'po_prod_date') syncExpiryPreview(); });

  function autoFillCostPriceForName(name){
    fetch(`{{ route('production.info', ':name') }}`.replace(':name', encodeURIComponent(name)))
      .then(r => r.ok ? r.json() : Promise.reject())
      .then(info => {
        if ($$('#po_cost') && !$$('#po_cost').value) $$('#po_cost').value = Number(info.unit_cost ?? 0).toFixed(2);
        if ($$('#po_fc')   && !$$('#po_fc').value)   $$('#po_fc').value   = Number(info.forecasted_demand ?? 0);
      })
      .catch(()=>{});
  }

  (function(){
    const form = $$('#addOrderForm'); const btn  = $$('#submitAddOrder');
    if (!form || !btn) return;
    form.addEventListener('submit', () => { btn.disabled = true; btn.textContent = 'Saving...'; });
  })();

  // Keep type label and legacy snapshot in sync
  function setTypeLabel(val) {
    const label = $$('#po_type_label');
    if (label) label.value = val;
    const snap  = $$('#po_product_name');
    if (snap)   snap.value = val;
  }

  function updateFormForProduct(productId, productName){
    const hid = $$('#po_product_id'); const hnm = $$('#po_product_name');
    if (hid) hid.value = String(productId);
    if (hnm && !$$('#po_type_label')?.value) hnm.value = productName;
    if (!$$('#po_type_label')?.value) setTypeLabel(productName);
    const title = document.getElementById('modalTitle');
    if (title) title.textContent = `Add Order ({{ $product->product_name }} → ${productName})`;
  }

  document.addEventListener('DOMContentLoaded', () => {
    const sel   = document.getElementById('po_product_select');
    const wrap  = document.getElementById('po_new_wrap');
    const tog   = document.getElementById('po_new_toggle');
    const save  = document.getElementById('po_new_save');
    const cancel= document.getElementById('po_new_cancel');
    const err   = document.getElementById('po_new_err');

    sel?.addEventListener('change', () => {
      const opt = sel.options[sel.selectedIndex];
      const name = opt.textContent.replace(/\s*\(Base\)\s*$/,'').trim();
      updateFormForProduct(opt.value, name);
      if (!$$('#po_type_label').value) setTypeLabel(name);
      autoFillCostPriceForName(name);
    });

    document.querySelectorAll('.js-type-chip').forEach(ch => {
      ch.addEventListener('click', () => setTypeLabel(ch.dataset.value || ch.textContent.trim()));
    });

    tog?.addEventListener('click', () => { wrap?.classList.toggle('hidden'); err?.classList.add('hidden'); err.textContent=''; });
    cancel?.addEventListener('click', () => { wrap?.classList.add('hidden'); err?.classList.add('hidden'); err.textContent=''; });

    // Save new variant under parent
    save?.addEventListener('click', async () => {
      const name  = document.getElementById('po_new_name')?.value?.trim();
      const cost  = parseFloat(document.getElementById('po_new_cost')?.value || '0') || 0;
      const shelf = parseInt(document.getElementById('po_new_shelf')?.value || '7', 10) || 7;
      const parentId = {{ (int)$product->id }};

      if (!name) { err.textContent = 'Variant name is required.'; err.classList.remove('hidden'); return; }

      try {
        const res = await fetch(@json(route('products.quick-store')), {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': (document.querySelector('#addOrderForm input[name=_token]')?.value || ''),
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({ product_name: name, unit_cost: cost, shelf_life_days: shelf, parent_id: parentId })
        });
        if (!res.ok) throw new Error('Failed to create variant');
        const data = await res.json();

        const newId   = data.id ?? data?.product_id ?? null;
        const newName = data.name ?? data?.product_name ?? name;
        if (!newId) throw new Error('Invalid response');

        const opt = new Option(newName, newId, true, true);
        sel.add(opt); sel.value = newId;

        updateFormForProduct(newId, newName);
        setTypeLabel(newName);
        if ($$('#po_cost') && !$$('#po_cost').value && typeof data.unit_cost !== 'undefined') {
          $$('#po_cost').value = Number(data.unit_cost).toFixed(2);
        }

        wrap?.classList.add('hidden'); err.classList.add('hidden'); err.textContent = '';
      } catch (e) {
        err.textContent = 'Could not save variant. Please try again.'; err.classList.remove('hidden');
      }
    });
  });
</script>
