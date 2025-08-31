@php
/** @var \Illuminate\Support\Collection|\App\Models\Product[] $products */
use Illuminate\Support\Str;
@endphp

@once
<style>
  .prod-card-img{
    width: 100%;
    height: 10rem;
    object-fit: cover;
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,.12);
    background: linear-gradient(135deg, #657423 0%, #2a2a2a 100%);
  }
  .prod-card:hover .prod-card-img{ filter: brightness(1.02); }

  /* Safety */
  .prod-card { pointer-events: auto; }
  .btn-busy { opacity: .7; pointer-events: none; }
</style>
@endonce

@forelse($products as $p)
  @php
    $qty     = number_format((float)($p->quantity ?? 0), 3);
    $demand  = number_format((float)($p->forecasted_demand ?? 0), 3);
    $unit    = number_format((float)($p->unit_cost ?? 0), 2);
    $status  = $p->stock_status ?? ((float)($p->quantity ?? 0) > 0 ? 'in_stock' : 'out_of_stock');

    $delta = (float)($p->quantity ?? 0) - (float)($p->forecasted_demand ?? 0);
    $ring  = $delta <= 0 ? 'ring-1 ring-rose-700/50'
          : ($delta <= 10 ? 'ring-1 ring-amber-600/40' : '');

    $badge = null; $badgeCls = '';
    if (isset($p->is_expired) || isset($p->days_to_expiry)) {
        if ($p->is_expired ?? false) { $badge = 'Expired'; $badgeCls = 'bg-rose-600/15 text-rose-300 border border-rose-700/40'; }
        elseif (($p->days_to_expiry ?? 99) <= 3) { $badge = ($p->days_to_expiry).'d left'; $badgeCls = 'bg-amber-500/15 text-amber-300 border border-amber-600/40'; }
    }

    $imgPrimary  = $p->card_image_url ?? $p->image_thumb_url ?? $p->image_url ?? asset('images/default-product.png');
    $srcset      = $p->card_image_srcset ?? null;
    $sku         = $p->sku ?? '—';
  @endphp

  <div
    class="prod-card glass rounded-2xl border border-white/10 p-4 flex flex-col gap-3 hover:bg-white/5 transition {{ $ring }}"
    id="product-card-{{ $p->id }}"
    data-id="{{ $p->id }}"
    data-name="{{ e($p->product_name) }}"
    data-unit-cost="{{ (float)($p->unit_cost ?? 0) }}"
    data-forecasted="{{ (float)($p->forecasted_demand ?? 0) }}"
  >
    {{-- Image --}}
    <div class="relative">
      <img
        @if($srcset) srcset="{{ $srcset }}" sizes="(min-width:1280px) 25vw, (min-width:1024px) 33vw, (min-width:640px) 50vw, 100vw" @endif
        src="{{ $imgPrimary }}"
        alt="{{ $p->product_name }} image"
        class="prod-card-img"
        width="400" height="300"
        loading="lazy" decoding="async"
        onerror="this.onerror=null;this.src='{{ asset('images/default-product.png') }}';"
      >
      @if($badge)
        <span class="absolute top-2 left-2 px-2 py-0.5 rounded-full text-xs {{ $badgeCls }} backdrop-blur">
          {{ $badge }}
        </span>
      @endif
      @if(!empty($p->category))
        <span class="absolute top-2 right-2 px-2 py-0.5 rounded-full text-xs bg-white/10 text-white/80 border border-white/20 backdrop-blur">
          {{ $p->category }}
        </span>
      @endif
    </div>

    {{-- Info --}}
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0">
        <h3 class="text-lg font-semibold text-white truncate" title="{{ $p->product_name }}">{{ $p->product_name }}</h3>
        <p class="text-xs text-white/60">SKU: {{ $sku }}</p>
      </div>
    </div>

    <div class="grid grid-cols-2 gap-3 text-sm">
      <div class="bg-white/5 rounded-xl p-3 border border-white/10">
        <p class="text-white/60">Inventory</p>
        <p class="text-white font-semibold">{{ $qty }} kg</p>
      </div>
      <div class="bg-white/5 rounded-xl p-3 border border-white/10">
        <p class="text-white/60">Forecasted Demand</p>
        <p class="text-white font-semibold">{{ $demand }} kg</p>
      </div>
      <div class="bg-white/5 rounded-xl p-3 border border-white/10">
        <p class="text-white/60">Unit Cost</p>
        <p class="text-white font-semibold">₱ {{ $unit }}</p>
      </div>
      <div class="bg-white/5 rounded-xl p-3 border border-white/10">
        <p class="text-white/60">Status</p>
        @php
          $cls = $status === 'in_stock'
            ? 'bg-emerald-600/15 text-emerald-300 border-emerald-700/40'
            : 'bg-rose-600/15 text-rose-300 border-rose-700/40';
        @endphp
        <span class="px-2 py-0.5 rounded-full text-xs border {{ $cls }}">{{ Str::of($status)->replace('_',' ')->title() }}</span>
      </div>
    </div>

    {{-- Actions --}}
    <div class="flex items-center justify-end gap-2 pt-2">
      @if (Route::has('production.show'))
        <a href="{{ route('production.show', $p->id) }}"
           class="px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-white/90 hover:bg-white/10">
          Manage Orders
        </a>
      @endif

      {{-- Dynamic Quick Add (no inline handlers) --}}
      <button
        type="button"
        class="js-quick-add px-3 py-2 rounded-xl bg-[var(--sidebar-active,#EDD100)] text-[#1F1E1E] font-semibold hover:opacity-90 relative z-10"
        data-id="{{ (int)$p->id }}"
        aria-label="Quick add to Sales"
      >
        + Quick Add
      </button>
    </div>
  </div>
@empty
  <div class="col-span-full text-center text-white/70 py-10">No products yet.</div>
@endforelse

@once
<script>
(function() {
  if (window.__quickAddBound) return;
  window.__quickAddBound = true;

  const openSalesModal = (payload) => {
    const { id, name, price } = payload || {};
    const saleModal      = document.querySelector('#saleModal');
    const fldProductSel  = document.querySelector('#sale_product_id');   // <select>
    const fldProductName = document.querySelector('#sale_product_name'); // optional text
    const fldPrice       = document.querySelector('#sale_price');
    const fldQty         = document.querySelector('#sale_quantity');

    if (fldProductSel && id) {
      fldProductSel.value = String(id);
      fldProductSel.dispatchEvent(new Event('change', { bubbles: true }));
    }
    if (fldProductName && name) fldProductName.value = name;
    if (fldPrice && typeof price === 'number' && isFinite(price)) fldPrice.value = price.toFixed(2);
    if (fldQty && !fldQty.value) fldQty.value = '1';

    if (typeof window.prefillSaleModal === 'function') {
      try { window.prefillSaleModal({ id, name, price }); } catch(_) {}
    }

    if (saleModal) {
      if (window.bootstrap && window.bootstrap.Modal) {
        try { window.bootstrap.Modal.getOrCreateInstance(saleModal).show(); return; } catch(_) {}
      }
      try { saleModal.showModal && saleModal.showModal(); } catch(_) {}
      try { saleModal.classList.remove('hidden'); saleModal.removeAttribute('aria-hidden'); } catch(_) {}
    } else {
      console.warn('Sales modal (#saleModal) not found.');
    }
  };

  const endpointFor = (id) => `/production/quick-add/${id}`;

  // Delegate clicks so it works on dynamically injected cards
  document.addEventListener('click', async function(e) {
    const btn = e.target.closest('.js-quick-add');
    if (!btn) return;

    const id = Number(btn.dataset.id || 0);
    if (!id) return;

    const originalHTML = btn.innerHTML;
    btn.classList.add('btn-busy');
    btn.innerHTML = 'Loading…';

    try {
      // Fetch live quick-add payload (name + price). Keeps things dynamic.
      const res = await fetch(endpointFor(id), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const data = await res.json();

      const payload = {
        id:  data.id ?? id,
        name: (data.name || '').toString(),
        price: Number(data.price ?? 0)
      };

      openSalesModal(payload);
    } catch (err) {
      console.warn('Quick Add payload fetch failed, falling back.', err);
      // Minimal fallback if endpoint isn’t available
      openSalesModal({ id, name: '', price: 0 });
    } finally {
      btn.classList.remove('btn-busy');
      btn.innerHTML = originalHTML;
    }
  }, true);
})();
</script>
@endonce
