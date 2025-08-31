{{-- resources/views/sales/partials/sale-modal.blade.php --}}
<dialog id="saleModal" class="rounded-2xl p-0 bg-[#111]/95 text-white border border-white/10 backdrop:bg-black/50 hidden">
  <form id="saleForm" method="POST" action="{{ route('sales.store') }}" class="w-[min(92vw,560px)]">
    @csrf
    <div class="flex items-center justify-between px-5 py-4 border-b border-white/10">
      <h3 class="text-lg font-semibold">New Sale</h3>
      <button type="button" class="sale-close text-white/70 hover:text-white" aria-label="Close">&times;</button>
    </div>

    <div class="px-5 py-4 space-y-4">
      <div>
        <label for="sale_product_id" class="block text-sm text-white/70 mb-1">Product</label>
        <select id="sale_product_id" name="product_id"
                class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2" required>
          <option value="" disabled selected>Select a product</option>
          @foreach(($allProducts ?? []) as $ap)
            <option value="{{ $ap->id }}">{{ $ap->product_name }}</option>
          @endforeach
        </select>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label for="sale_quantity" class="block text-sm text-white/70 mb-1">Quantity (kg)</label>
          <input id="sale_quantity" name="quantity" type="number" step="0.001" min="0.001"
                 class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2" required />
        </div>
        <div>
          <label for="sale_price" class="block text-sm text-white/70 mb-1">Unit Price (₱)</label>
          <input id="sale_price" name="price" type="number" step="0.01" min="0"
                 class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2" required />
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label for="sale_production_date" class="block text-sm text-white/70 mb-1">Production Date</label>
          <input id="sale_production_date" name="production_date" type="date"
                 class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2" />
        </div>
        <div>
          <label for="sale_expiration_date" class="block text-sm text-white/70 mb-1">Expiration Date</label>
          <input id="sale_expiration_date" name="expiration_date" type="date"
                 class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2" />
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label for="sale_date" class="block text-sm text-white/70 mb-1">Sale Date</label>
          <input id="sale_date" name="date" type="date" value="{{ now()->toDateString() }}"
                 class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2" required />
        </div>
        <div>
          <label for="sale_notes" class="block text-sm text-white/70 mb-1">Notes (optional)</label>
          <input id="sale_notes" name="notes" type="text"
                 class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2" placeholder="e.g., Walk-in customer" />
        </div>
      </div>

      {{-- Optional: tie to a specific batch --}}
      <input type="hidden" id="sale_production_id" name="production_id" />
    </div>

    <div class="flex items-center justify-end gap-2 px-5 py-4 border-t border-white/10">
      <button type="button" class="sale-close px-4 py-2 rounded-xl bg-white/10 border border-white/10 hover:bg-white/15">
        Cancel
      </button>
      <button id="saleSubmitBtn" type="submit" class="px-4 py-2 rounded-xl bg-[var(--sidebar-active,#EDD100)] text-[#1F1E1E] font-semibold">
        Save Sale
      </button>
    </div>
  </form>
</dialog>

@once
<script>
(function(){
  const modal = document.getElementById('saleModal');
  const form  = document.getElementById('saleForm');
  const btn   = document.getElementById('saleSubmitBtn');

  function toast(message, type='info') {
    const el = document.createElement('div');
    el.className = `fixed bottom-6 left-1/2 -translate-x-1/2 px-4 py-2 rounded-xl text-sm shadow z-[9999]
                    ${type==='success' ? 'bg-emerald-600' : type==='error' ? 'bg-red-600' : 'bg-black/80'}`;
    el.textContent = message;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 3000);
  }

  function openModal(){
    try { modal.showModal && modal.showModal(); } catch(_) {}
    modal.classList.remove('hidden');
    modal.removeAttribute('aria-hidden');
  }
  function closeModal(){
    try { modal.close && modal.close(); } catch(_) {}
    modal.setAttribute('aria-hidden','true');
    modal.classList.add('hidden');
  }
  document.addEventListener('click', (e) => {
    if (e.target.closest('.sale-close')) closeModal();
  });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });

  // Expose to global: used by the product card button
  window.prefillSaleModal = function({ id, name, price, production_date, expiration_date, production_id=null }) {
    const sel   = document.getElementById('sale_product_id');
    const pIn   = document.getElementById('sale_price');
    const qIn   = document.getElementById('sale_quantity');
    const dProd = document.getElementById('sale_production_date');
    const dExp  = document.getElementById('sale_expiration_date');
    const pBatch= document.getElementById('sale_production_id');

    if (sel && id) {
      sel.value = String(id);
      sel.dispatchEvent(new Event('change', { bubbles: true }));
    }
    if (pIn && typeof price === 'number' && isFinite(price)) pIn.value = price.toFixed(2);
    if (qIn && !qIn.value) qIn.value = '1';

    if (dProd) dProd.value = production_date || '';
    if (dExp)  dExp.value  = expiration_date || '';
    if (pBatch) pBatch.value = production_id || '';

    openModal();
  };

  // AJAX submit
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    btn.disabled = true;
    btn.classList.add('opacity-70','cursor-not-allowed');

    const fd = new FormData(form);
    if (!fd.get('product_id')) {
      toast('Please select a product.', 'error');
      btn.disabled = false;
      btn.classList.remove('opacity-70','cursor-not-allowed');
      return;
    }

    try {
      const res = await fetch(form.getAttribute('action'), {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
      });

      if (!res.ok) {
        if (res.status === 422) {
          const j = await res.json();
          const msg = j?.errors ? Object.values(j.errors).flat().join('\n') : 'Validation error';
          toast(msg, 'error');
        } else {
          toast('Failed to save sale.', 'error');
        }
        return;
      }

      const j = await res.json();
      if (!j.ok) {
        toast(j.message || 'Unable to save.', 'error');
        return;
      }

      toast('Sale saved.', 'success');
      closeModal();

      // Optional: refresh any totals/UI sections if you expose endpoints
      // e.g., fetch updated product card or counters here.

    } catch (err) {
      console.error(err);
      toast('Network error. Please try again.', 'error');
    } finally {
      btn.disabled = false;
      btn.classList.remove('opacity-70','cursor-not-allowed');
    }
  });
})();
</script>
@endonce
