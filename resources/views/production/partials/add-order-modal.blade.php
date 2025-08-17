@php
/** @var \Illuminate\Support\Collection|\App\Models\Product[]|null $products */
$products = $products ?? collect();
$statusOptions = $statusOptions ?? ['Pending','Completed','Cancelled','Paid'];
@endphp

<div id="addSaleModal" class="fixed inset-0 z-40 hidden items-center justify-center bg-black/60">
  <div class="w-full max-w-2xl mx-4 rounded-2xl overflow-hidden border border-white/15 bg-gradient-to-br from-[#1F1E1E] to-[#001C00]">
    <div class="flex items-center justify-between px-5 py-4 border-b border-white/10">
      <h3 class="text-white font-semibold text-lg">Add New Sale</h3>
      <button type="button" class="text-white/60 hover:text-white" onclick="toggleAddSaleModal(false)">✕</button>
    </div>

    <form id="saleForm" action="{{ route('sales.store') }}" method="POST" class="px-5 py-4 space-y-4">
      @csrf

      {{-- Product --}}
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="block text-sm text-white/70 mb-1">Product</label>
          <select id="product_id" name="product_id"
                  class="w-full rounded-xl liquid-input px-3 py-2"
                  required>
            <option value="" disabled selected>— Select product —</option>
            @foreach ($products as $p)
              <option value="{{ $p->id }}"
                      data-name="{{ $p->name ?? $p->product_name }}"
                      data-price="{{ (float)($p->price ?? $p->unit_cost ?? 0) }}">
                {{ $p->name ?? $p->product_name }}
              </option>
            @endforeach
          </select>
        </div>

        {{-- Batch (optional) --}}
        <div>
          <label class="block text-sm text-white/70 mb-1">Batch (optional)</label>
          <select id="production_id" name="production_id" class="w-full rounded-xl liquid-input px-3 py-2">
            <option value="" selected>— Any batch —</option>
          </select>
        </div>
      </div>

      {{-- Date & Status --}}
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div>
          <label class="block text-sm text-white/70 mb-1">Date</label>
          <input type="date" id="date" name="date" class="w-full rounded-xl liquid-input px-3 py-2" required
                 value="{{ now()->toDateString() }}">
        </div>
        <div>
          <label class="block text-sm text-white/70 mb-1">Status</label>
          <select id="status" name="status" class="w-full rounded-xl liquid-input px-3 py-2" required>
            @foreach ($statusOptions as $s)
              <option value="{{ $s }}">{{ $s }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="block text-sm text-white/70 mb-1">Invoice (preview)</label>
          <input type="text" class="w-full rounded-xl liquid-input px-3 py-2" value="{{ $nextInvoice }}" disabled>
        </div>
      </div>

      {{-- Qty / Price --}}
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div>
          <label class="block text-sm text-white/70 mb-1">Quantity (kg)</label>
          <input type="number" step="0.001" min="0.001" id="quantity" name="quantity"
                 class="w-full rounded-xl liquid-input px-3 py-2" required>
        </div>
        <div>
          <label class="block text-sm text-white/70 mb-1">Unit Price</label>
          <input type="number" step="0.01" min="0" id="price" name="price"
                 class="w-full rounded-xl liquid-input px-3 py-2" required>
        </div>
        <div>
          <label class="block text-sm text-white/70 mb-1">Total</label>
          <input type="text" id="total" class="w-full rounded-xl liquid-input px-3 py-2" value="₱ 0.00" disabled>
        </div>
      </div>

      {{-- Display Name override (optional) --}}
      <div>
        <label class="block text-sm text-white/70 mb-1">Display Product Name (optional)</label>
        <input type="text" id="product" name="product" maxlength="150"
               class="w-full rounded-xl liquid-input px-3 py-2"
               placeholder="Override product name on receipt">
      </div>

      {{-- Availability peek --}}
      <div id="availRow" class="text-sm text-white/70">
        <span id="availText">Available: —</span>
        <span class="mx-2">•</span>
        <span id="priceText">Suggested price: —</span>
      </div>

      <div class="flex items-center justify-end gap-3 pt-2">
        <button type="button" class="btn-ghost px-4 py-2 rounded-xl" onclick="toggleAddSaleModal(false)">Cancel</button>
        <button id="saleSubmitBtn" type="submit" class="btn-primary px-4 py-2 rounded-xl">Save Sale</button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
(function(){
  const $ = sel => document.querySelector(sel);

  const productSelect = $('#product_id');
  const batchSelect   = $('#production_id');
  const qtyInput      = $('#quantity');
  const priceInput    = $('#price');
  const totalOutput   = $('#total');
  const submitBtn     = $('#saleSubmitBtn');
  const availText     = $('#availText');
  const priceText     = $('#priceText');

  function formatMoney(n){
    const v = isFinite(n) ? Number(n) : 0;
    return '₱ ' + v.toFixed(2);
  }

  function computeTotal(){
    const q = parseFloat(qtyInput?.value || '0') || 0;
    const p = parseFloat(priceInput?.value || '0') || 0;
    totalOutput.value = formatMoney(q * p);
  }

  qtyInput?.addEventListener('input', computeTotal);
  priceInput?.addEventListener('input', computeTotal);

  // On product change: load batches, peek availability and default price
  productSelect?.addEventListener('change', async (e) => {
    const pid = e.target.value;
    const opt = e.target.selectedOptions[0];
    if (!pid) return;

    // Reset batches
    batchSelect.innerHTML = `<option value="">— Any batch —</option>`;

    // Fetch batches for product
    try {
      const res = await fetch(`{{ url('/production') }}/${pid}/batches`, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
      if (res.ok) {
        const rows = await res.json();
        rows.forEach(b => {
          const o = document.createElement('option');
          o.value = b.id;
          o.textContent = `${b.batch_number} — ${Number(b.current_inventory ?? 0).toFixed(3)} kg`;
          batchSelect.appendChild(o);
        });
      }
    } catch(err){ console.error(err); }

    // Peek availability & suggested price
    try {
      const form = new FormData();
      form.append('product_id', pid);
      const res = await fetch(`{{ route('sales.available') }}`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('input[name=_token]')?.value || '{{ csrf_token() }}'
        },
        body: form
      });
      if (res.ok) {
        const j = await res.json();
        availText.textContent = `Available: ${Number(j.available ?? 0).toFixed(3)} kg`;
        priceText.textContent = `Suggested price: ${formatMoney(Number(j.price ?? 0))}`;
        if (!priceInput.value) priceInput.value = (j.price ?? 0);
        computeTotal();
      } else {
        availText.textContent = 'Available: —';
        priceText.textContent = 'Suggested price: —';
      }
    } catch(err){
      availText.textContent = 'Available: —';
      priceText.textContent = 'Suggested price: —';
    }

    // If option has data-price, use as default when user hasn’t typed yet
    const defaultPrice = parseFloat(opt?.dataset?.price || '0') || 0;
    if (!priceInput.value && defaultPrice > 0) {
      priceInput.value = defaultPrice.toFixed(2);
      computeTotal();
    }
  });

  // Prevent double submit
  document.getElementById('saleForm')?.addEventListener('submit', () => {
    submitBtn.disabled = true;
    submitBtn.classList.add('opacity-70','cursor-not-allowed');
  });
})();
</script>
@endpush
