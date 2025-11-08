@php
    use App\Models\Product;

    $products = $products ?? Product::select('id','product_name','selling_price','unit_cost')
        ->orderBy('product_name')
        ->get()
        ->map(function ($p) {
            $p->name  = $p->product_name;
            $p->price = $p->selling_price ?? $p->unit_cost ?? 0;
            return $p;
        });

    $statusOptions    = $statusOptions ?? ['Pending','Completed','Cancelled','Paid'];
    $unitTypeOptions  = ['pack','bag'];
    $nextInvoice      = $nextInvoice ?? '';
@endphp

<style>
  /* full-width, clean cards */
  #addSaleModal .sheet { width:100%; max-width:1280px; }
  .insight { border-radius:14px; padding:16px; border:1px solid; }
  .insight h4 { font-size:.9rem; margin:0 0 .25rem; }
  .insight .big { font-size:2rem; line-height:1.1; font-weight:800; letter-spacing:.5px; }
  .insight .hint { font-size:.78rem; opacity:.8 }
  .insight .right { margin-left:auto; text-align:right }
  .insight-pack { background:#FFFCF0; border-color:#F6E9A8; color:#6B4C00; }
  .insight-bag  { background:#FFF5F6; border-color:#F5C5CA; color:#831843; }
  .muted { color:#64748b }
</style>

<div id="addSaleModal" class="fixed inset-0 z-50 hidden items-start justify-center p-4 sm:p-6 md:p-8">
  {{-- Backdrop --}}
  <div class="absolute inset-0 bg-black/30" onclick="toggleAddSaleModal(false)"></div>

  {{-- Modal Sheet --}}
  <div class="relative sheet mx-auto overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl">
    {{-- Title bar --}}
    <div class="px-6 py-5 border-b border-gray-200 bg-white flex items-center justify-between">
      <h2 class="text-lg font-semibold text-gray-900">Add New Sale</h2>
      <button type="button" onclick="toggleAddSaleModal(false)"
              class="grid h-9 w-9 place-items-center rounded-full border border-gray-200 text-gray-600 hover:bg-gray-50"
              aria-label="Close">✖</button>
    </div>

    {{-- Body --}}
    <div class="px-6 pb-6 pt-4 space-y-4">
      @if ($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 text-rose-700 p-3 text-sm">
          <ul class="list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
          </ul>
        </div>
      @endif

      {{-- No-stock banner --}}
      <div id="noStockBanner"
           class="hidden rounded-xl border border-amber-300 bg-amber-50 text-amber-800 p-3 text-sm font-semibold">
        ⚠️ There’s no stock for the current selection<span id="bannerBatchSuffix" class="hidden"> / batch</span>.
      </div>

      <form action="{{ route('sales.store') }}" method="POST" class="space-y-4" novalidate>
        @csrf

        {{-- Row: Product / Batch / Date --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <div>
            <label for="product_id" class="block text-sm text-gray-700 mb-1">Product</label>
            <select
              name="product_id" id="product_id" required
              class="w-full rounded-xl border border-gray-300 bg-white text-gray-900 px-3 py-2.5 outline-none focus:ring-2 focus:ring-blue-300">
              <option value="">— Select product —</option>
              @foreach ($products as $p)
                <option value="{{ $p->id }}" data-price="{{ (float) ($p->price ?? 0) }}">
                  {{ $p->name ?? $p->product_name }}
                </option>
              @endforeach
            </select>
          </div>

          <div>
            <label for="production_id" class="block text-sm text-gray-700 mb-1">Batch</label>
            <select
              name="production_id" id="production_id" disabled
              class="w-full rounded-xl border border-gray-300 bg-white text-gray-900 px-3 py-2.5 outline-none disabled:opacity-60 disabled:cursor-not-allowed focus:ring-2 focus:ring-blue-300">
              <option value="">— Select batch —</option>
            </select>
            <p id="batchInfo" class="text-xs text-gray-500 mt-1 hidden"></p>
          </div>

          <div>
            <label class="block text-sm text-gray-700 mb-1">Date</label>
            <input
              type="date" name="date" value="{{ old('date', now()->format('Y-m-d')) }}" required
              class="w-full rounded-xl border border-gray-300 bg-white text-gray-900 px-3 py-2.5 outline-none focus:ring-2 focus:ring-blue-300">
          </div>
        </div>

        {{-- Insight cards (Pack / Bag) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div class="insight insight-pack flex items-start gap-4">
            <div>
              <h4>Pack Availability</h4>
              <div class="big"><span id="packsAvail">0</span> <span class="text-base font-semibold">packs</span></div>
              <div id="packHint" class="hint">Based on latest batches with price.</div>
            </div>
            <div class="right">
              <div class="text-sm font-medium opacity-80">Suggested Price</div>
              <div class="text-xl font-extrabold">₱<span id="packPrice">0.00</span></div>
            </div>
          </div>

          <div class="insight insight-bag flex items-start gap-4">
            <div>
              <h4>Bag Availability</h4>
              <div class="big"><span id="bagsAvail">0</span> <span class="text-base font-semibold">bags</span></div>
              <div id="bagHint" class="hint">Based on latest batches with price.</div>
            </div>
            <div class="right">
              <div class="text-sm font-medium opacity-80">Suggested Price</div>
              <div class="text-xl font-extrabold">₱<span id="bagPrice">0.00</span></div>
            </div>
          </div>
        </div>

        {{-- Unit Type / Qty / Unit Price --}}
          <div class="md:col-span-1">
            <label class="block text-sm text-gray-700 mb-1">Unit Type</label>
            <select
              name="unit_type" id="unit_type"
              class="w-full rounded-xl border border-gray-300 bg-white text-gray-900 px-3 py-2.5 outline-none focus:ring-2 focus:ring-blue-300">
              <option value="">Auto</option>
              @foreach ($unitTypeOptions as $opt)
                <option value="{{ $opt }}">{{ ucfirst($opt) }}</option>
              @endforeach
            </select>
            <p class="text-xs text-gray-500 mt-1">Leave on Auto to let the server choose based on the batch.</p>
          </div>

          <div class="md:col-span-1">
            <label class="block text-sm text-gray-700 mb-1">Quantity</label>
            <input
              type="number" name="quantity" min="0.001" step="0.001" value="{{ old('quantity') }}" required inputmode="decimal"
              class="w-full rounded-xl border border-gray-300 bg-white text-gray-900 px-3 py-2.5 outline-none focus:ring-2 focus:ring-blue-300">
          </div>

          <div class="md:col-span-1">
            <label class="block text-sm text-gray-700 mb-1">Unit Price (₱)</label>
            <input
              type="number" name="price" step="0.01" min="0" value="{{ old('price') }}" placeholder="Leave blank for auto"
              class="w-full rounded-xl border border-gray-300 bg-white text-gray-900 px-3 py-2.5 outline-none focus:ring-2 focus:ring-blue-300">
            <p class="text-xs text-gray-500 mt-1">If blank, server uses the batch’s per-{unit_type} price.</p>
          </div>
        </div>

        {{-- Total --}}
        <div class="flex items-center justify-between rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5">
          <span class="text-gray-700">Total</span>
          <span id="totalPreview" class="text-gray-900 font-semibold">₱ 0.00</span>
        </div>

        {{-- Invoice / Status --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="block text-sm text-gray-700 mb-1">Invoice Number</label>
            <input type="text" value="{{ $nextInvoice }}" readonly
                   class="w-full rounded-xl border border-gray-200 bg-gray-50 text-gray-700 px-3 py-2.5" aria-readonly="true">
          </div>
          <div>
            <label class="block text-sm text-gray-700 mb-1">Status</label>
            <select
              name="status" required
              class="w-full rounded-xl border border-gray-300 bg-white text-gray-900 px-3 py-2.5 outline-none focus:ring-2 focus:ring-blue-300">
              @foreach ($statusOptions as $opt)
                <option value="{{ $opt }}" @selected(old('status')===$opt)>{{ $opt }}</option>
              @endforeach
            </select>
          </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-2 pt-2">
          <button type="button" onclick="toggleAddSaleModal(false)"
                  class="rounded-xl px-4 py-2 text-gray-700 border border-gray-300 bg-white hover:bg-gray-50">
            Cancel
          </button>
          <button id="btnSaveSale" type="submit"
                  class="inline-flex items-center gap-2 rounded-xl px-4 py-2 bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-60 disabled:cursor-not-allowed"
                  disabled>
            Save Sale
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function toggleAddSaleModal(show) {
  const el = document.getElementById('addSaleModal');
  if (!el) return;
  el.classList.toggle('hidden', !show);
  if (show) el.classList.add('flex'); else el.classList.remove('flex');
}

document.addEventListener('DOMContentLoaded', function () {
  const productSel = document.getElementById('product_id');
  const batchSel   = document.getElementById('production_id');
  const unitType   = document.getElementById('unit_type');
  const priceInput = document.querySelector('input[name="price"]');
  const qtyInput   = document.querySelector('input[name="quantity"]');
  const totalEl    = document.getElementById('totalPreview');

  const packsAvail = document.getElementById('packsAvail');
  const bagsAvail  = document.getElementById('bagsAvail');
  const packPrice  = document.getElementById('packPrice');
  const bagPrice   = document.getElementById('bagPrice');
  const packHint   = document.getElementById('packHint');
  const bagHint    = document.getElementById('bagHint');

  const btnSave    = document.getElementById('btnSaveSale');
  const banner     = document.getElementById('noStockBanner');
  const bannerBatchSuffix = document.getElementById('bannerBatchSuffix');
  const batchInfo  = document.getElementById('batchInfo');

  const typeInput   = document.getElementById('type_label');
  const typeList    = document.getElementById('typeList');
  const nextTypeTxt = document.getElementById('nextTypeText');

  const batchesUrlBase  = "{{ url('/production/api/by-product') }}/"; // returns batch list including: available_pack, available_bag, unit_price_pack/bag, current_inventory, production_date, product_name_snapshot, batch_number
  const productAvailUrl = "{{ route('sales.available') }}";           // returns { available, price }
  const typesApiUrl     = "{{ route('sales.api.types') }}";           // returns { ok, list:[], next:"Type N" }

  let productAvailableKg = 0;
  let batchAvailableKg   = null;
  let currentBatches     = []; // cache list for current product

  /** UI helpers **/
  function setSaveEnabled(enabled){ btnSave.disabled = !enabled; }
  function showNoStockBanner(show, isBatch){
    banner.classList.toggle('hidden', !show);
    bannerBatchSuffix.classList.toggle('hidden', !isBatch);
  }
  function currentUnitType(){ return (unitType?.value || '').toLowerCase().trim(); }
  function selectedBatchOption(){ return batchSel?.options?.[batchSel.selectedIndex] || null; }
  function selectedProductOption(){ return productSel?.options?.[productSel.selectedIndex] || null; }

  function peso(n){ const v = Number(n||0); return isFinite(v)? v.toFixed(2):'0.00'; }
  function int(n){ const v = parseInt(n,10); return isFinite(v)? v:0; }

  /** recompute banner + qty clamp based on active availability (batch > product) */
  function recomputeNoStockUI(){
    const usingBatch = (batchAvailableKg !== null);
    const availKg = usingBatch ? (parseFloat(batchAvailableKg)||0) : (parseFloat(productAvailableKg)||0);
    const noStock = !isFinite(availKg) || availKg <= 0;
    showNoStockBanner(noStock, usingBatch);
    setSaveEnabled(!noStock);
    if (noStock){
      qtyInput.setAttribute('max', 0);
      if (qtyInput.value) qtyInput.value = '';
    } else {
      qtyInput.setAttribute('max', String(availKg));
      const q = parseFloat(qtyInput.value||'0');
      if (q > availKg) qtyInput.value = availKg;
    }
    updateTotal();
  }

  /** total preview */
  function updateTotal() {
    const q = parseFloat(qtyInput.value || 0);
    const p = parseFloat(priceInput.value || NaN);
    const t = (isNaN(q) || q<=0) ? 0 : (isNaN(p) ? NaN : q * p);
    totalEl.textContent = isNaN(t) ? '₱ auto' : ('₱ ' + t.toFixed(2));
  }

  /** get per-unit suggested price from selected batch (or product fallback) */
  function getSuggestedPriceFor(unit) {
    const opt = selectedBatchOption();
    if (opt) {
      if (unit === 'pack' && opt.dataset.pack && !isNaN(parseFloat(opt.dataset.pack))) {
        return parseFloat(opt.dataset.pack);
      }
      if (unit === 'bag' && opt.dataset.bag && !isNaN(parseFloat(opt.dataset.bag))) {
        return parseFloat(opt.dataset.bag);
      }
    }
    const pOpt = selectedProductOption();
    if (pOpt) {
      const prodPrice = parseFloat(pOpt.getAttribute('data-price') || 'NaN');
      if (!isNaN(prodPrice)) return prodPrice;
    }
    return null;
  }

  function applyUnitPriceFromSelection(force=false){
    const unit = currentUnitType();
    if (!unit){ updateTotal(); return; }
    const suggested = getSuggestedPriceFor(unit);
    if (suggested !== null && (force || priceInput.value === '' || +priceInput.value === 0)) {
      priceInput.value = Number(suggested).toFixed(2);
    }
    updateTotal();
  }

  /** ----- INSIGHT CARDS LOGIC ----- **/
  function updateCardsForAggregate() {
    // aggregate across all batches that have price for each unit
    const withPackPrice = currentBatches.filter(b => b.unit_price_pack && Number(b.unit_price_pack) > 0);
    const withBagPrice  = currentBatches.filter(b => b.unit_price_bag && Number(b.unit_price_bag) > 0);

    const packs = withPackPrice.reduce((s,b)=> s + int(b.available_pack ?? 0), 0);
    const bags  = withBagPrice.reduce((s,b)=> s + int(b.available_bag  ?? 0), 0);

    // choose latest priced batch for suggested price
    const latestPack = withPackPrice[0];
    const latestBag  = withBagPrice[0];

    packsAvail.textContent = packs;
    bagsAvail.textContent  = bags;
    packPrice.textContent  = peso(latestPack ? latestPack.unit_price_pack : 0);
    bagPrice.textContent   = peso(latestBag  ? latestBag.unit_price_bag  : 0);

    packHint.textContent = 'Based on latest batches with price.';
    bagHint .textContent = 'Based on latest batches with price.';
  }

  function updateCardsForBatch(opt) {
    const ap = int(opt?.dataset.apack ?? opt?.dataset.availablePack ?? 0);
    const ab = int(opt?.dataset.abag  ?? opt?.dataset.availableBag  ?? 0);
    const pp = parseFloat(opt?.dataset.pack ?? '0');
    const bp = parseFloat(opt?.dataset.bag  ?? '0');

    packsAvail.textContent = ap;
    bagsAvail.textContent  = ab;
    packPrice.textContent  = peso(pp);
    bagPrice.textContent   = peso(bp);

    packHint.textContent = 'Based on selected batch.';
    bagHint .textContent = 'Based on selected batch.';
  }

  /** API calls **/
  async function fetchProductAvailability(productId) {
    try {
      const url = productAvailUrl + "?product_id=" + encodeURIComponent(productId);
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      if (!res.ok) return;
      const data = await res.json();
      productAvailableKg = parseFloat(data.available ?? 0) || 0;
      if (priceInput.value === '') {
        const opt = selectedProductOption();
        priceInput.value = (opt?.getAttribute('data-price') || (typeof data.price !== 'undefined' ? data.price : '') );
      }
      updateTotal();
      recomputeNoStockUI();
    } catch {
      productAvailableKg = 0;
      recomputeNoStockUI();
    }
  }

  function resetBatchUI() {
    batchSel.innerHTML = '<option value="">— Select batch —</option>';
    batchSel.disabled = true;
    batchInfo.classList.add('hidden');
    batchAvailableKg = null;
    currentBatches = [];
    updateCardsForAggregate(); // clears to 0
  }

  async function loadBatches(productId) {
    resetBatchUI();
    try {
      const res = await fetch(batchesUrlBase + encodeURIComponent(productId), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      if (!res.ok) { recomputeNoStockUI(); return; }

      const list = await res.json();

      // sort: newest first already, but ensure
      currentBatches = Array.isArray(list) ? list : [];

      currentBatches.forEach(b => {
        const opt = document.createElement('option');

        // datasets we use later
        opt.dataset.pack = (b.unit_price_pack ?? '') === '' ? '' : String(b.unit_price_pack);
        opt.dataset.bag  = (b.unit_price_bag  ?? '') === '' ? '' : String(b.unit_price_bag);
        opt.dataset.inv  = String(b.current_inventory ?? 0);
        opt.dataset.apack = String(b.available_pack ?? 0);
        opt.dataset.abag  = String(b.available_bag  ?? 0);
        opt.dataset.type  = (b.product_name_snapshot ?? '').toString();

        // label like: Type: Garlic Skinless • Pack 33 • Bag 22 • B-2
        const type = b.product_name_snapshot ?? 'Base';
        const pN   = int(b.available_pack ?? 0);
        const bN   = int(b.available_bag ?? 0);
        const code = b.batch_number ?? 'Batch';

        opt.value = b.id;
        opt.textContent = `Type: ${type} • Pack ${pN} • Bag ${bN} • ${code}`;
        batchSel.appendChild(opt);
      });

      batchSel.disabled = false;
      updateCardsForAggregate();           // fill aggregate cards
      applyUnitPriceFromSelection(false);  // set any default price if unit chosen
      recomputeNoStockUI();
    } catch {
      recomputeNoStockUI();
    }
  }

  async function loadTypes(productId) {
    typeList.innerHTML = '';
    nextTypeTxt.textContent = 'Type 1';
    if (!productId) return;

    try {
      const url = typesApiUrl + "?product_id=" + encodeURIComponent(productId);
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      if (!res.ok) return;

      const data = await res.json();
      if (Array.isArray(data.list)) {
        data.list.forEach(lbl => {
          const opt = document.createElement('option');
          opt.value = lbl;
          typeList.appendChild(opt);
        });
      }
      if (data.next) nextTypeTxt.textContent = data.next;
    } catch {}
  }

  /** Event wiring **/
  productSel?.addEventListener('change', function () {
    const hasValue = !!this.value;
    resetBatchUI();
    qtyInput.value = '';
    qtyInput.removeAttribute('max');

    const opt = selectedProductOption();
    if (priceInput.value === '') priceInput.value = opt?.getAttribute('data-price') || '';
    updateTotal();

    if (hasValue) {
      fetchProductAvailability(this.value);
      loadBatches(this.value);
      loadTypes(this.value);
    } else {
      productAvailableKg = 0;
      typeList.innerHTML = '';
      nextTypeTxt.textContent = 'Type 1';
      updateCardsForAggregate();
      recomputeNoStockUI();
    }
  });

  batchSel?.addEventListener('change', function () {
    const selOpt = selectedBatchOption();
    const invStr = selOpt?.dataset?.inv ?? '';
    const inv = invStr === '' ? null : parseFloat(invStr);

    // info line like your screenshot
    if (selOpt) {
      const type = selOpt.dataset.type || 'Base';
      const pN   = int(selOpt.dataset.apack);
      const bN   = int(selOpt.dataset.abag);
      batchInfo.textContent = `Type: ${type} — Pack: ${pN} • Bag: ${bN} — Batch inventory (kg): ${inv ?? 0}`;
      batchInfo.classList.remove('hidden');
      updateCardsForBatch(selOpt);
    } else {
      batchInfo.classList.add('hidden');
      updateCardsForAggregate();
    }

    if (inv !== null) {
      batchAvailableKg = inv;
      qtyInput.setAttribute('max', String(inv));
      const v = parseFloat(qtyInput.value||'0');
      if (v > inv) qtyInput.value = inv;
    } else {
      batchAvailableKg = null;
      qtyInput.removeAttribute('max');
    }

    applyUnitPriceFromSelection(true);
    recomputeNoStockUI();
  });

  qtyInput?.addEventListener('input', function () {
    const max = parseFloat(qtyInput.getAttribute('max') || '0');
    const val = parseFloat(this.value || '0');
    if (max && val > max) this.value = max;
    updateTotal();
  });

  priceInput?.addEventListener('input', updateTotal);
  unitType?.addEventListener('change', () => applyUnitPriceFromSelection(true));

  // init
  setSaveEnabled(false);
  updateTotal();
});
</script>
