{{-- resources/views/sales/partials/sale-modal.blade.php --}}
<dialog
  id="saleModal"
  class="rounded-2xl p-0 bg-[#111]/95 text-white border border-white/10 backdrop:bg-black/50 hidden"
  aria-labelledby="saleModalTitle"
>
  <form
    id="saleForm"
    method="POST"
    action="{{ route('sales.store') }}"
    class="w-[min(92vw,560px)]"
    novalidate
  >
    @csrf

    <div class="flex items-center justify-between px-5 py-4 border-b border-white/10">
      <h3 id="saleModalTitle" class="text-lg font-semibold">New Sale</h3>
      <button
        type="button"
        class="sale-close text-white/70 hover:text-white"
        aria-label="Close"
      >
        &times;
      </button>
    </div>

    <div class="px-5 py-4 space-y-4">
      {{-- Product --}}
      <div>
        <label for="sale_product_id" class="block text-sm text-white/70 mb-1">Product</label>
        <select
          id="sale_product_id"
          name="product_id"
          class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2"
          required
        >
          <option value="" disabled selected>Select a product</option>
          @foreach(($allProducts ?? []) as $ap)
            <option
              value="{{ $ap->id }}"
              data-price="{{ (float)($ap->price ?? 0) }}"
            >
              {{ $ap->product_name }}
            </option>
          @endforeach
        </select>
        <p class="mt-1 text-xs text-white/50">
          If available, the default price will auto-fill from this product.
        </p>
      </div>

      {{-- Quantity & Unit Price --}}
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label for="sale_quantity" class="block text-sm text-white/70 mb-1">
            Quantity
          </label>
          <input
            id="sale_quantity"
            name="quantity"
            type="number"
            step="0.001"
            min="0.001"
            class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2"
            required
          />
          <p class="mt-1 text-xs text-white/50">
            Use kilograms or the unit type your batch uses.
          </p>
        </div>
        <div>
          <label for="sale_price" class="block text-sm text-white/70 mb-1">
            Unit Price (₱)
          </label>
          <input
            id="sale_price"
            name="price"
            type="number"
            step="0.01"
            min="0"
            class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2"
            required
          />
        </div>
      </div>

      {{-- Optional: Unit Type (Auto / pack / bag) --}}
      <div>
        <label for="sale_unit_type" class="block text-sm text-white/70 mb-1">
          Unit Type (optional)
        </label>
        <select
          id="sale_unit_type"
          name="unit_type"
          class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2"
        >
          <option value="">Auto</option>
          <option value="pack">Per Pack</option>
          <option value="bag">Per Bag</option>
        </select>
        <p class="mt-1 text-xs text-white/50">
          Leave on Auto if you want the system to pick the best batch unit.
        </p>
      </div>

      {{-- Production / Expiration Dates --}}
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label for="sale_production_date" class="block text-sm text-white/70 mb-1">
            Production Date
          </label>
          <input
            id="sale_production_date"
            name="production_date"
            type="date"
            class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2"
          />
        </div>
        <div>
          <label for="sale_expiration_date" class="block text-sm text-white/70 mb-1">
            Expiration Date
          </label>
          <input
            id="sale_expiration_date"
            name="expiration_date"
            type="date"
            class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2"
          />
        </div>
      </div>

      {{-- Sale Date & Notes --}}
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label for="sale_date" class="block text-sm text-white/70 mb-1">
            Sale Date
          </label>
          <input
            id="sale_date"
            name="date"
            type="date"
            value="{{ now('Asia/Manila')->toDateString() }}"
            class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2"
            required
          />
        </div>
        <div>
          <label for="sale_notes" class="block text-sm text-white/70 mb-1">
            Notes (optional)
          </label>
          <input
            id="sale_notes"
            name="notes"
            type="text"
            class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2"
            placeholder="e.g., Walk-in customer"
          />
        </div>
      </div>

      {{-- Optional: tie to a specific batch --}}
      <input type="hidden" id="sale_production_id" name="production_id" />
    </div>

    <div class="flex items-center justify-end gap-2 px-5 py-4 border-t border-white/10">
      <button
        type="button"
        class="sale-close px-4 py-2 rounded-xl bg-white/10 border border-white/10 hover:bg-white/15"
      >
        Cancel
      </button>
      <button
        id="saleSubmitBtn"
        type="submit"
        class="px-4 py-2 rounded-xl bg-[var(--sidebar-active,#EDD100)] text-[#1F1E1E] font-semibold"
      >
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

  if (!modal || !form || !btn) return;

  function toast(message, type = 'info') {
    const el = document.createElement('div');
    el.className = `
      fixed bottom-6 left-1/2 -translate-x-1/2 px-4 py-2 rounded-xl text-sm shadow z-[9999]
      ${type === 'success'
        ? 'bg-emerald-600'
        : type === 'error'
        ? 'bg-red-600'
        : 'bg-black/80'}
    `.trim().replace(/\s+/g,' ');
    el.textContent = message;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 3000);
  }

  function setTodayIfEmpty() {
    const saleDate = document.getElementById('sale_date');
    if (!saleDate) return;
    if (!saleDate.value) {
      const now = new Date();
      const iso = new Date(now.getTime() - now.getTimezoneOffset() * 60000)
        .toISOString()
        .slice(0, 10);
      saleDate.value = iso;
    }
  }

  function openModal(){
    setTodayIfEmpty();
    try {
      if (typeof modal.showModal === 'function') {
        modal.showModal();
      }
    } catch (_) {
      // some browsers throw if already open; ignore
    }
    modal.classList.remove('hidden');
    modal.removeAttribute('aria-hidden');
  }

  function closeModal(){
    try {
      if (typeof modal.close === 'function') {
        modal.close();
      }
    } catch (_) {}
    modal.setAttribute('aria-hidden','true');
    modal.classList.add('hidden');
    // reset after close so prefill works cleanly next time
    form.reset();
    // keep production_id cleared unless explicitly set
    const pBatch = document.getElementById('sale_production_id');
    if (pBatch) pBatch.value = '';
  }

  // Close handlers (buttons + Esc + backdrop click on dialog)
  document.addEventListener('click', (e) => {
    if (e.target.closest('.sale-close')) {
      closeModal();
    }
  });

  modal.addEventListener('click', (e) => {
    // Click directly on backdrop area of dialog
    if (e.target === modal) {
      closeModal();
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeModal();
    }
  });

  // Auto-price on product change
  const prodSelect = document.getElementById('sale_product_id');
  const priceInput = document.getElementById('sale_price');
  if (prodSelect && priceInput) {
    prodSelect.addEventListener('change', () => {
      const opt = prodSelect.options[prodSelect.selectedIndex];
      if (!opt) return;
      const p = opt.getAttribute('data-price');
      if (p !== null && p !== '' && (!priceInput.value || +priceInput.value === 0)) {
        const num = Number(p);
        if (!Number.isNaN(num)) {
          priceInput.value = num.toFixed(2);
        }
      }
    });
  }

  // Expose explicit open for generic "Add Sale" button
  window.openSaleModal = function() {
    // Clear, but keep today’s date
    form.reset();
    const pBatch = document.getElementById('sale_production_id');
    if (pBatch) pBatch.value = '';
    setTodayIfEmpty();
    openModal();
  };

  // Expose to global: used by product card button
  window.prefillSaleModal = function({
    id,
    name,
    price,
    production_date,
    expiration_date,
    production_id = null
  }) {
    const sel    = document.getElementById('sale_product_id');
    const pIn    = document.getElementById('sale_price');
    const qIn    = document.getElementById('sale_quantity');
    const dProd  = document.getElementById('sale_production_date');
    const dExp   = document.getElementById('sale_expiration_date');
    const pBatch = document.getElementById('sale_production_id');

    // Reset first to avoid stale values
    form.reset();
    setTodayIfEmpty();

    if (sel && id) {
      sel.value = String(id);
      sel.dispatchEvent(new Event('change', { bubbles: true }));
    }

    if (pIn && typeof price === 'number' && isFinite(price)) {
      pIn.value = price.toFixed(2);
    }

    if (qIn && !qIn.value) {
      qIn.value = '1';
    }

    if (dProd) dProd.value = production_date || '';
    if (dExp)  dExp.value  = expiration_date || '';
    if (pBatch) pBatch.value = production_id || '';

    openModal();
  };

  // AJAX submit with graceful fallback
  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const fd = new FormData(form);
    if (!fd.get('product_id')) {
      toast('Please select a product.', 'error');
      return;
    }

    btn.disabled = true;
    btn.classList.add('opacity-70','cursor-not-allowed');

    try {
      const res = await fetch(form.getAttribute('action'), {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
      });

      if (!res.ok) {
        if (res.status === 422) {
          let msg = 'Validation error';
          try {
            const j = await res.json();
            if (j?.errors) {
              msg = Object.values(j.errors).flat().join('\n');
            }
          } catch(_) {}
          toast(msg, 'error');
        } else {
          toast('Failed to save sale.', 'error');
        }
        return;
      }

      const ct = res.headers.get('Content-Type') || '';

      // If backend returns JSON (recommended)
      if (ct.includes('application/json')) {
        const j = await res.json();
        if (j.ok) {
          toast(j.message || 'Sale saved.', 'success');
          closeModal();

          if (j.redirect) {
            window.location.href = j.redirect;
          } else if (j.reload) {
            window.location.reload();
          }
          // Otherwise, caller can handle updating UI via a custom hook if you add one
        } else {
          toast(j.message || 'Unable to save.', 'error');
        }
      } else {
        // Non-JSON (e.g. redirect HTML). Just treat as success and reload.
        toast('Sale saved.', 'success');
        closeModal();
        window.location.reload();
      }

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
