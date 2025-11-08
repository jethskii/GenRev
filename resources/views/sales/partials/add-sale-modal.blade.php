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

<div id="addSaleModal" class="fixed inset-0 z-50 hidden items-center justify-center">
  {{-- Backdrop --}}
  <div class="absolute inset-0 bg-black/30" onclick="toggleAddSaleModal(false)"></div>

  {{-- Modal Card - LIGHT --}}
  <div class="relative w-full max-w-xl mx-4 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl">
    {{-- Title bar --}}
    <div class="px-6 py-5 border-b border-gray-200 bg-white">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900">Add New Sale</h2>
        <button type="button" onclick="toggleAddSaleModal(false)"
                class="grid h-9 w-9 place-items-center rounded-full border border-gray-200 text-gray-600 hover:bg-gray-50"
                aria-label="Close">✖</button>
      </div>
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

      <form action="{{ route('sales.store') }}" method="POST" class="space-y-4" novalidate>
        @csrf

        {{-- Product --}}
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

          {{-- Availability pill --}}
          <div id="availabilityPill" class="mt-2 hidden">
            <span class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-xs border border-emerald-200 bg-emerald-50 text-emerald-800">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M20 7l-9 9-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span><span id="availQty">0</span> available</span>
            </span>
          </div>
        </div>

        {{-- Batch --}}
        <div>
          <label for="production_id" class="block text-sm text-gray-700 mb-1">Batch</label>
          <select
            name="production_id" id="production_id" disabled
            class="w-full rounded-xl border border-gray-300 bg-white text-gray-900 px-3 py-2.5 outline-none disabled:opacity-60 disabled:cursor-not-allowed focus:ring-2 focus:ring-blue-300">
            <option value="">— Select batch —</option>
          </select>
          <p id="batchInfo" class="text-xs text-gray-500 mt-1 hidden"></p>
        </div>

        {{-- Date --}}
        <div>
          <label class="block text-sm text-gray-700 mb-1">Date</label>
          <input
            type="date" name="date" value="{{ old('date', now()->format('Y-m-d')) }}" required
            class="w-full rounded-xl border border-gray-300 bg-white text-gray-900 px-3 py-2.5 outline-none focus:ring-2 focus:ring-blue-300">
        </div>

        {{-- Type (auto-suggest + auto-increment when blank) --}}
        <div>
          <label class="block text-sm text-gray-700 mb-1">Type</label>
          <input
            type="text" name="type_label" id="type_label" list="typeList"
            placeholder="Leave blank for Auto"
            class="w-full rounded-xl border border-gray-300 bg-white text-gray-900 px-3 py-2.5 outline-none focus:ring-2 focus:ring-blue-300">
          <datalist id="typeList"></datalist>
          <p id="typeAutoHint" class="text-xs text-gray-500 mt-1">Auto will become: <span class="font-medium" id="nextTypeText">Type 1</span></p>
        </div>

        {{-- Unit Type --}}
        <div>
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

        {{-- Qty & Price --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-sm text-gray-700 mb-1">Quantity</label>
            <input
              type="number" name="quantity" min="0.001" step="0.001" value="{{ old('quantity') }}" required inputmode="decimal"
              class="w-full rounded-xl border border-gray-300 bg-white text-gray-900 px-3 py-2.5 outline-none focus:ring-2 focus:ring-blue-300">
          </div>
          <div>
            <label class="block text-sm text-gray-700 mb-1">Unit Price (₱)</label>
            <input
              type="number" name="price" step="0.01" min="0" value="{{ old('price') }}" placeholder="Leave blank for auto"
              class="w-full rounded-xl border border-gray-300 bg-white text-gray-900 px-3 py-2.5 outline-none focus:ring-2 focus:ring-blue-300">
            <p class="text-xs text-gray-500 mt-1">If blank, server uses the batch’s per-{unit_type} price.</p>
          </div>
        </div>

        {{-- Total preview --}}
        <div class="flex items-center justify-between rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5">
          <span class="text-gray-700">Total</span>
          <span id="totalPreview" class="text-gray-900 font-semibold">₱ 0.00</span>
        </div>

        {{-- Invoice (preview) --}}
        <div>
          <label class="block text-sm text-gray-700 mb-1">Invoice Number</label>
          <input
            type="text" value="{{ $nextInvoice }}" readonly
            class="w-full rounded-xl border border-gray-200 bg-gray-50 text-gray-700 px-3 py-2.5" aria-readonly="true">
        </div>

        {{-- Status --}}
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

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-2 pt-2">
          <button type="button" onclick="toggleAddSaleModal(false)"
                  class="rounded-xl px-4 py-2 text-gray-700 border border-gray-300 bg-white hover:bg-gray-50">
            Cancel
          </button>
          <button type="submit"
                  class="inline-flex items-center gap-2 rounded-xl px-4 py-2 bg-blue-600 text-white hover:bg-blue-700">
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
  const pill       = document.getElementById('availabilityPill');
  const availQtyEl = document.getElementById('availQty');
  const batchInfo  = document.getElementById('batchInfo');

  const typeInput   = document.getElementById('type_label');
  const typeList    = document.getElementById('typeList');
  const nextTypeTxt = document.getElementById('nextTypeText');

  const batchesUrlBase  = "{{ url('/production/api/by-product') }}/"; // returns minimal batch list (now includes prices)
  const productAvailUrl = "{{ route('sales.available') }}";           // returns { available, price }
  const typesApiUrl     = "{{ route('sales.api.types') }}";           // returns { ok, list:[], next:"Type N" }

  function updateTotal() {
    const q = parseFloat(qtyInput.value || 0);
    const p = parseFloat(priceInput.value || NaN);
    const t = (isNaN(q) || q<=0) ? 0 : (isNaN(p) ? NaN : q * p);
    totalEl.textContent = isNaN(t) ? '₱ auto' : ('₱ ' + t.toFixed(2));
  }

  function resetBatchUI() {
    batchSel.innerHTML = '<option value="">— Select batch —</option>';
    batchSel.disabled = true;
    batchInfo.classList.add('hidden');
    qtyInput.removeAttribute('max');
  }

  // --- NEW: helpers for automatic unit price --------------------------------
  function currentUnitType() {
    return (unitType?.value || '').toLowerCase().trim();
  }

  function selectedBatchOption() {
    return batchSel?.options?.[batchSel.selectedIndex] || null;
  }

  function selectedProductOption() {
    return productSel?.options?.[productSel.selectedIndex] || null;
  }

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
    // Fallback to product default price
    const pOpt = selectedProductOption();
    if (pOpt) {
      const prodPrice = parseFloat(pOpt.getAttribute('data-price') || 'NaN');
      if (!isNaN(prodPrice)) return prodPrice;
    }
    return null;
  }

  // Set price from the currently selected unit/batch.
  // If force=true, overwrite whatever is there; else only fill if blank/zero.
  function applyUnitPriceFromSelection(force = false) {
    const unit = currentUnitType();
    if (!unit) { updateTotal(); return; } // 'Auto' → do nothing
    const suggested = getSuggestedPriceFor(unit);
    if (suggested !== null) {
      if (force || priceInput.value === '' || +priceInput.value === 0) {
        priceInput.value = suggested.toFixed(2);
      }
    }
    updateTotal();
  }
  // ---------------------------------------------------------------------------

  async function fetchProductAvailability(productId) {
    try {
      const url = productAvailUrl + "?product_id=" + encodeURIComponent(productId);
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      if (!res.ok) return;
      const data = await res.json();

      if (typeof data.available !== 'undefined') {
        availQtyEl.textContent = data.available;
        pill.classList.remove('hidden');
        qtyInput.setAttribute('max', data.available);
      } else {
        pill.classList.add('hidden');
        qtyInput.removeAttribute('max');
      }

      // Only set a base price if price is empty and unit type is not Pack/Bag, the specific
      // per-unit setting will happen via applyUnitPriceFromSelection when user picks unit.
      if (priceInput.value === '') {
        const opt = selectedProductOption();
        priceInput.value = (opt?.getAttribute('data-price') || (typeof data.price !== 'undefined' ? data.price : '') );
      }

      updateTotal();
    } catch {}
  }

  async function loadBatches(productId) {
    resetBatchUI();
    try {
      const res = await fetch(batchesUrlBase + encodeURIComponent(productId), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      if (!res.ok) return;

      const batches = await res.json();
      batches.forEach(b => {
        const opt = document.createElement('option');

        // NEW: persist per-pack and per-bag prices on the option
        // so we can read them when user chooses Pack/Bag.
        opt.dataset.pack = (b.unit_price_pack ?? '') === '' ? '' : String(b.unit_price_pack);
        opt.dataset.bag  = (b.unit_price_bag  ?? '') === '' ? '' : String(b.unit_price_bag);

        const date = b.production_date ?? '';
        const qty  = (b.quantity ?? 0);
        const inv  = (b.current_inventory ?? 0);

        opt.value = b.id;
        opt.textContent = `${b.batch_number ?? 'Batch'} — Qty ${qty} — Inv ${inv}${date ? ' — ' + date : ''}`;
        opt.dataset.inv = inv;
        batchSel.appendChild(opt);
      });
      batchSel.disabled = false;

      // If a unit type is already chosen, try to auto-apply its price now.
      applyUnitPriceFromSelection(/*force*/ false);
    } catch {}
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

  productSel?.addEventListener('change', function () {
    const hasValue = !!this.value;

    resetBatchUI();
    pill.classList.add('hidden');
    qtyInput.value = '';
    qtyInput.removeAttribute('max');

    // Base price from product; per-unit override happens on unit/batch change
    const opt = selectedProductOption();
    if (priceInput.value === '') {
      priceInput.value = opt?.getAttribute('data-price') || '';
    }
    updateTotal();

    if (hasValue) {
      fetchProductAvailability(this.value);
      loadBatches(this.value);
      loadTypes(this.value);
    } else {
      typeList.innerHTML = '';
      nextTypeTxt.textContent = 'Type 1';
    }
  });

  batchSel?.addEventListener('change', function () {
    const selOpt = selectedBatchOption();
    const inv = selOpt?.dataset?.inv ?? '';
    if (inv !== '') {
      batchInfo.textContent = `Batch available inventory: ${inv}`;
      batchInfo.classList.remove('hidden');
      qtyInput.setAttribute('max', inv);
      if (qtyInput.value && parseFloat(qtyInput.value) > parseFloat(inv)) {
        qtyInput.value = inv;
      }
    } else {
      batchInfo.classList.add('hidden');
      qtyInput.removeAttribute('max');
    }

    // NEW: when batch changes, re-apply price for current Pack/Bag selection
    applyUnitPriceFromSelection(/*force*/ true);
  });

  qtyInput?.addEventListener('input', function () {
    const max = parseFloat(qtyInput.getAttribute('max') || '0');
    const val = parseFloat(this.value || '0');
    if (max && val > max) this.value = max;
    updateTotal();
  });

  priceInput?.addEventListener('input', updateTotal);

  // NEW: when user picks Pack/Bag, set the correct price from the selected batch
  unitType?.addEventListener('change', function () {
    applyUnitPriceFromSelection(/*force*/ true);
  });

  // Initial compute
  updateTotal();
});
</script>
