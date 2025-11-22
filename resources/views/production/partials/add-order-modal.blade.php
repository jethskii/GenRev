{{-- Add Order Modal (parent-aware, animated, with image preview) --}}
@once
<style>
  /* ===== Modal backdrop & container animation ===== */
  #addOrderModal.flex {
    align-items: flex-start;
    justify-content: center;
  }

  .add-order-backdrop {
    animation: aoBackdropIn .24s ease-out forwards;
  }

  .add-order-card {
    animation: aoCardIn .26s ease-out forwards;
    transform-origin: top center;
  }

  @keyframes aoBackdropIn {
    from { opacity: 0; }
    to   { opacity: 1; }
  }

  @keyframes aoCardIn {
    0%   { opacity:0; transform:translateY(12px) scale(.96); }
    60%  { opacity:1; transform:translateY(-2px) scale(1.01); }
    100% { opacity:1; transform:translateY(0) scale(1); }
  }

  /* Fancy header accent */
  .add-order-header {
    position: relative;
    padding-bottom: .75rem;
    margin-bottom: .75rem;
  }
  .add-order-header::after {
    content:'';
    position:absolute;
    left:0; bottom:0;
    width:72px; height:3px;
    border-radius:999px;
    background:linear-gradient(90deg,#4f46e5,#22c55e,#f97316);
  }

  /* Animated image preview frame */
  .image-preview-wrap {
    border-radius: 14px;
    border: 1px dashed rgba(148,163,184,.7);
    background: radial-gradient(circle at top left, rgba(129,140,248,.08), transparent 60%),
                radial-gradient(circle at bottom right, rgba(45,212,191,.08), transparent 60%),
                #f9fafb;
    padding: .75rem;
    position: relative;
    overflow: hidden;
    transition: border-color .2s ease-out, box-shadow .2s ease-out, background .2s ease-out;
  }
  .image-preview-wrap::before {
    content:'';
    position:absolute;
    inset:-40%;
    background:conic-gradient(from 180deg,
      rgba(129,140,248,.18),
      rgba(45,212,191,.18),
      rgba(249,250,251,0),
      rgba(251,191,36,.25),
      rgba(129,140,248,.18)
    );
    opacity:0;
    mix-blend-mode:screen;
    transition:opacity .25s ease-out;
    pointer-events:none;
  }
  .image-preview-wrap.is-active {
    border-style: solid;
    border-color: rgba(129,140,248,.7);
    box-shadow: 0 12px 28px rgba(15,23,42,.18);
  }
  .image-preview-wrap.is-active::before {
    opacity:.65;
  }

  .image-preview-label {
    font-size: .74rem;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #64748b;
    display:flex;
    align-items:center;
    gap:.35rem;
    margin-bottom:.25rem;
  }

  .image-preview-label-dot {
    width:.5rem; height:.5rem;
    border-radius:999px;
    background:radial-gradient(circle at 30% 20%,#22c55e,#16a34a);
    box-shadow:0 0 0 4px rgba(34,197,94,.25);
    animation: ipDotPulse 1.6s ease-out infinite;
  }
  @keyframes ipDotPulse {
    0%   { transform:scale(.75); box-shadow:0 0 0 0 rgba(34,197,94,.35); }
    70%  { transform:scale(1);   box-shadow:0 0 0 10px rgba(34,197,94,0); }
    100% { transform:scale(1);   box-shadow:0 0 0 10px rgba(34,197,94,0); }
  }

  .image-preview-box {
    width:100%;
    aspect-ratio:4/3;
    border-radius:12px;
    background:#e5e7eb;
    display:flex;
    align-items:center;
    justify-content:center;
    overflow:hidden;
    position:relative;
  }

  .image-preview-box img {
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
    transform:scale(1.03);
    transition:transform .22s ease-out, filter .22s ease-out;
  }

  .image-preview-box img.is-loaded {
    filter:brightness(1.03) contrast(1.03);
  }

  .image-preview-box img.is-loaded:hover {
    transform:scale(1.06);
    filter:brightness(1.06) contrast(1.05);
  }

  /* Slightly animated primary button */
  .btn.btn-primary {
    transition: transform .12s ease-out, box-shadow .12s ease-out, filter .12s ease-out;
  }
  .btn.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 18px rgba(37,99,235,.30);
    filter:brightness(1.04);
  }
  .btn.btn-primary:active {
    transform: translateY(0);
    box-shadow: 0 4px 10px rgba(37,99,235,.25);
  }
</style>
@endonce

<div id="addOrderModal" class="fixed inset-0 z-[9999] hidden">
  <div class="absolute inset-0 bg-black/40 add-order-backdrop" onclick="closeOrderModal()" aria-hidden="true"></div>

  <div class="relative mx-auto my-10 max-w-md w-[92%] page-card add-order-card">
    <button type="button" onclick="closeOrderModal()" aria-label="Close"
            class="absolute top-2 right-4 text-2xl font-bold text-[color:var(--muted)] hover:text-[color:var(--red)]">&times;</button>

    <div class="add-order-header">
      <h3 id="modalTitle" class="text-xl font-semibold">
        Add Order ({{ $product->product_name }})
      </h3>
    </div>

    {{-- POST to production.orders.store --}}
    <form id="addOrderForm"
          action="{{ route('production.orders.store') }}"
          method="POST"
          enctype="multipart/form-data"   {{-- needed for image upload --}}
          class="space-y-3">
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

      {{-- Unit Cost / Prices --}}
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div>
          <label class="label">Unit Cost (₱)</label>
          <input id="po_cost" name="unit_cost" type="number" step="0.01" min="0" required class="input"
                 value="{{ old('unit_cost', (float)($product->unit_cost ?? 0)) }}">
        </div>
        <div>
          <label class="label">Price per Pack (₱)</label>
          <input id="po_price_pack" name="unit_price_pack" type="number" step="0.01" min="0" class="input"
                 value="{{ old('unit_price_pack') }}">
        </div>
        <div>
          <label class="label">Price per Bag (₱)</label>
          <input id="po_price_bag" name="unit_price_bag" type="number" step="0.01" min="0" class="input"
                 value="{{ old('unit_price_bag') }}">
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

      {{-- Product / Batch image upload + animated preview --}}
      <div>
        <label class="label">Product / Batch Image</label>
        <input id="po_image"
               name="image"
               type="file"
               accept="image/*"
               class="input">
        <p class="help mt-1">Optional. If saved, this image will appear on the product card (via the latest batch).</p>

        <div id="po_image_preview_wrap" class="image-preview-wrap mt-2 hidden">
          <div class="image-preview-label">
            <span class="image-preview-label-dot"></span>
            <span>Live preview · will show on dashboard after save</span>
          </div>
          <div class="image-preview-box">
            <img id="po_image_preview" src="" alt="Selected product image preview" class="hidden" />
          </div>
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
  function closeOrderModal(){
    const m=$$('#addOrderModal'); if(!m) return;
    m.classList.add('hidden'); m.classList.remove('flex');
  }

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

    // ===== Image live preview (ties into product cards via latest batch) =====
    const fileInput   = document.getElementById('po_image');
    const previewWrap = document.getElementById('po_image_preview_wrap');
    const previewImg  = document.getElementById('po_image_preview');

    if (fileInput && previewWrap && previewImg) {
      fileInput.addEventListener('change', (e) => {
        const file = e.target.files && e.target.files[0];
        if (!file) {
          previewWrap.classList.add('hidden');
          previewWrap.classList.remove('is-active');
          previewImg.src = '';
          previewImg.classList.add('hidden');
          return;
        }

        const url = URL.createObjectURL(file);
        previewImg.onload = () => {
          previewImg.classList.add('is-loaded');
        };
        previewImg.src = url;
        previewImg.classList.remove('hidden');
        previewWrap.classList.remove('hidden');
        previewWrap.classList.add('is-active');
      });
    }
  });
</script>
