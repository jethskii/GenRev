{{-- resources/views/sales/partials/add-sale-modal.blade.php --}}
@php
    use App\Models\Product;

    // Prefer controller-supplied $products; otherwise build a minimal fallback
    $products = $products ?? Product::select('id','product_name','selling_price','unit_cost')
        ->orderBy('product_name')
        ->get()
        ->map(function ($p) {
            $p->name  = $p->product_name;
            $p->price = $p->selling_price ?? $p->unit_cost ?? 0;
            return $p;
        });

    $statusOptions = $statusOptions ?? ['Pending','Completed','Cancelled','Paid'];
    $nextInvoice   = $nextInvoice ?? '';
@endphp

<div id="addSaleModal" class="fixed inset-0 z-50 hidden items-center justify-center">
  {{-- Backdrop --}}
  <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="toggleAddSaleModal(false)"></div>

  {{-- Modal Card --}}
  <div
    class="relative w-full max-w-xl mx-4 overflow-hidden rounded-2xl border border-white/10
           bg-gradient-to-br from-[#1F1E1E]/95 to-[#001C00]/80 shadow-2xl
           animate-[fadeIn_.18s_ease-out]">
    {{-- Top bar / title --}}
    <div class="relative px-6 py-5">
      <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-[#047705] via-[#71C862] to-[#EDD100]"></div>

      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-white" style="text-shadow:-1px 1px 0 #047705;">
          Add New Sale
        </h2>
        <button type="button" onclick="toggleAddSaleModal(false)"
                class="grid h-9 w-9 place-items-center rounded-full border border-white/10
                       text-white/80 hover:text-white hover:bg-white/10 transition">
          ✖
        </button>
      </div>
    </div>

    {{-- Body --}}
    <div class="px-6 pb-6 space-y-4">
      @if ($errors->any())
        <div class="rounded-xl border border-rose-700/40 bg-rose-900/20 text-rose-100 p-3 text-sm">
          <ul class="list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
          </ul>
        </div>
      @endif

      <form action="{{ route('sales.store') }}" method="POST" class="space-y-4" novalidate>
        @csrf

        {{-- Product --}}
        <div>
          <label for="product_id" class="block text-sm text-white/80 mb-1">Product</label>
          <select
            name="product_id" id="product_id" required
            class="w-full rounded-xl border border-white/10 bg-white/5 text-white
                   px-3 py-2.5 outline-none focus:border-[#047705] focus:ring-2 focus:ring-[#047705]/30">
            <option value="">-- Select Product --</option>
            @foreach ($products as $p)
              <option value="{{ $p->id }}" data-price="{{ (float) ($p->price ?? 0) }}">
                {{ $p->name ?? $p->product_name }}
              </option>
            @endforeach
          </select>

          {{-- availability pill --}}
          <div id="availabilityPill" class="mt-2 hidden">
            <span class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-xs
                         border border-white/10 bg-white/5 text-white/90">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M20 7l-9 9-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span><span id="availQty">0</span> available</span>
            </span>
          </div>
        </div>

        {{-- Batch --}}
        <div>
          <label for="production_id" class="block text-sm text-white/80 mb-1">Batch</label>
          <select
            name="production_id" id="production_id" disabled
            class="w-full rounded-xl border border-white/10 bg-white/5 text-white
                   px-3 py-2.5 outline-none disabled:opacity-60 disabled:cursor-not-allowed
                   focus:border-[#047705] focus:ring-2 focus:ring-[#047705]/30">
            <option value="">-- Select Batch --</option>
          </select>
          <p id="batchInfo" class="text-xs text-white/60 mt-1 hidden"></p>
        </div>

        {{-- Date --}}
        <div>
          <label class="block text-sm text-white/80 mb-1">Date</label>
          <input
            type="date" name="date" value="{{ old('date', now()->format('Y-m-d')) }}" required
            class="w-full rounded-xl border border-white/10 bg-white/5 text-white
                   px-3 py-2.5 outline-none focus:border-[#047705] focus:ring-2 focus:ring-[#047705]/30">
        </div>

        {{-- Qty & Price --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-sm text-white/80 mb-1">Quantity</label>
            <input
              type="number" name="quantity" min="1" step="1" value="{{ old('quantity') }}" required inputmode="numeric"
              class="w-full rounded-xl border border-white/10 bg-white/5 text-white
                     px-3 py-2.5 outline-none focus:border-[#047705] focus:ring-2 focus:ring-[#047705]/30">
          </div>
          <div>
            <label class="block text-sm text-white/80 mb-1">Unit Price</label>
            <input
              type="number" name="price" step="0.01" min="0" value="{{ old('price') }}" required
              class="w-full rounded-xl border border-white/10 bg-white/5 text-white
                     px-3 py-2.5 outline-none focus:border-[#047705] focus:ring-2 focus:ring-[#047705]/30">
          </div>
        </div>

        {{-- Total preview --}}
        <div class="flex items-center justify-between rounded-xl border border-white/10 bg-white/[.06] px-3 py-2.5">
          <span class="text-white/80">Total</span>
          <span id="totalPreview" class="text-white font-semibold">₱ 0.00</span>
        </div>

        {{-- Invoice (preview) --}}
        <div>
          <label class="block text-sm text-white/80 mb-1">Invoice Number</label>
          <input
            type="text" value="{{ $nextInvoice }}" readonly
            class="w-full rounded-xl border border-white/10 bg-white/5 text-white/90
                   px-3 py-2.5 opacity-90">
        </div>

        {{-- Status --}}
        <div>
          <label class="block text-sm text-white/80 mb-1">Status</label>
          <select
            name="status" required
            class="w-full rounded-xl border border-white/10 bg-white/5 text-white
                   px-3 py-2.5 outline-none focus:border-[#047705] focus:ring-2 focus:ring-[#047705]/30">
            @foreach ($statusOptions as $opt)
              <option value="{{ $opt }}" @selected(old('status')===$opt)>{{ $opt }}</option>
            @endforeach
          </select>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-2 pt-2">
          <button type="button" onclick="toggleAddSaleModal(false)"
                  class="rounded-xl px-4 py-2 text-white/90 border border-white/10 bg-white/5 hover:bg-white/10 transition">
            Cancel
          </button>
          <button type="submit"
                  class="inline-flex items-center gap-2 rounded-xl px-4 py-2
                         bg-gradient-to-r from-[#047705] to-[#0aad0a]
                         text-white shadow-[0_6px_18px_rgba(4,119,5,.35)]
                         hover:brightness-110 active:scale-[.99] transition">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
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
  const priceInput = document.querySelector('input[name="price"]');
  const qtyInput   = document.querySelector('input[name="quantity"]');
  const totalEl    = document.getElementById('totalPreview');
  const pill       = document.getElementById('availabilityPill');
  const availQtyEl = document.getElementById('availQty');
  const batchInfo  = document.getElementById('batchInfo');

  const batchesUrlBase  = "{{ url('/production/api/by-product') }}/";
  const productAvailUrl = "{{ route('sales.available') }}";

  function updateTotal() {
    const q = parseFloat(qtyInput.value || 0);
    const p = parseFloat(priceInput.value || 0);
    const t = (isNaN(q) || isNaN(p)) ? 0 : (q * p);
    totalEl.textContent = '₱ ' + t.toFixed(2);
  }

  function resetBatchUI() {
    batchSel.innerHTML = '<option value="">-- Select Batch --</option>';
    batchSel.disabled = true;
    batchInfo.classList.add('hidden');
  }

  async function fetchProductAvailability(productId) {
    try {
      const url = productAvailUrl + "?product_id=" + encodeURIComponent(productId);
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      if (!res.ok) return;
      const data = await res.json();

      // availability pill + qty cap
      if (typeof data.available !== 'undefined') {
        availQtyEl.textContent = data.available;
        pill.classList.remove('hidden');
        qtyInput.setAttribute('max', data.available);
      } else {
        pill.classList.add('hidden');
        qtyInput.removeAttribute('max');
      }

      // server price accessor (preferred), fallback to option data-price
      if (typeof data.price !== 'undefined') {
        priceInput.value = data.price;
      } else {
        const opt = productSel.options[productSel.selectedIndex];
        priceInput.value = opt?.getAttribute('data-price') || '';
      }

      updateTotal();
    } catch (e) { /* silent */ }
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
        const date = b.production_date ?? '';
        const qty  = (b.quantity ?? 0);
        const inv  = (b.current_inventory ?? 0);
        opt.value = b.id;
        opt.textContent = `${b.batch_number ?? 'Batch'} — Qty ${qty} — Inv ${inv}${date ? ' — ' + date : ''}`;
        opt.dataset.inv = inv;
        batchSel.appendChild(opt);
      });
      batchSel.disabled = false;
    } catch (e) { /* silent */ }
  }

  // Product change
  productSel?.addEventListener('change', function () {
    const hasValue = !!this.value;

    // UI resets
    resetBatchUI();
    pill.classList.add('hidden');
    qtyInput.value = '';
    qtyInput.removeAttribute('max');

    // Price fallback from option (will be overwritten by availability API)
    const opt = this.options[this.selectedIndex];
    priceInput.value = opt?.getAttribute('data-price') || '';
    updateTotal();

    if (hasValue) {
      fetchProductAvailability(this.value);
      loadBatches(this.value);
    }
  });

  // Batch change → cap by batch inventory
  batchSel?.addEventListener('change', function () {
    const selOpt = this.options[this.selectedIndex];
    const inv = selOpt?.dataset?.inv ?? '';
    if (inv !== '') {
      batchInfo.textContent = `Batch available inventory: ${inv}`;
      batchInfo.classList.remove('hidden');
      qtyInput.setAttribute('max', inv);
      if (qtyInput.value && parseFloat(qtyInput.value) > parseFloat(inv)) {
        qtyInput.value = inv;
        updateTotal();
      }
    } else {
      batchInfo.classList.add('hidden');
    }
  });

  qtyInput?.addEventListener('input', function () {
    const max = parseFloat(qtyInput.getAttribute('max') || '0');
    const val = parseFloat(this.value || '0');
    if (max && val > max) this.value = max;
    updateTotal();
  });

  priceInput?.addEventListener('input', updateTotal);
});
</script>

<style>
@keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
</style>
