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
    $ring  = $delta <= 0 ? 'ring-1 ring-rose-700/50' : ($delta <= 10 ? 'ring-1 ring-amber-600/40' : '');

    $badge = null; $badgeCls = '';
    if (isset($p->is_expired) || isset($p->days_to_expiry)) {
        if ($p->is_expired ?? false) { $badge = 'Expired'; $badgeCls = 'bg-rose-600/15 text-rose-300 border border-rose-700/40'; }
        elseif (($p->days_to_expiry ?? 99) <= 3) { $badge = ($p->days_to_expiry).'d left'; $badgeCls = 'bg-amber-500/15 text-amber-300 border border-amber-600/40'; }
    }

    $imgPrimary   = $p->card_image_url ?? $p->image_thumb_url ?? $p->image_url ?? asset('images/default-product.png');
    $srcset       = $p->card_image_srcset ?? null;
    $sku          = $p->sku ?? '—';
    $defaultPrice = (float)($p->default_price ?? $p->price ?? $p->unit_cost ?? 0);
  @endphp

  <div
    class="prod-card glass rounded-2xl border border-white/10 p-4 flex flex-col gap-3 hover:bg-white/5 transition {{ $ring }}"
    id="product-card-{{ $p->id }}"
    data-id="{{ $p->id }}"
    data-name="{{ e($p->product_name) }}"
    data-price="{{ $defaultPrice }}"
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

      {{-- NEW: Delete latest batch (AJAX) --}}
      <button
        type="button"
        class="js-delete-latest px-3 py-2 rounded-xl bg-rose-600/90 text-white hover:bg-rose-600"
        data-product-id="{{ (int)$p->id }}"
        title="Delete the latest batch for this product"
      >
        Delete Latest Batch
      </button>

      {{-- Quick Add to Sales --}}
      <button
        type="button"
        class="js-quick-add px-3 py-2 rounded-xl bg-[var(--sidebar-active,#EDD100)] text-[#1F1E1E] font-semibold hover:opacity-90"
        data-id="{{ (int)$p->id }}"
        data-name="{{ e($p->product_name) }}"
        data-price="{{ $defaultPrice }}"
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
(function () {
  if (window.__prodCardsBound) return;
  window.__prodCardsBound = true;

  function toast(message, type='info') {
    const el = document.createElement('div');
    el.className = `fixed bottom-6 left-1/2 -translate-x-1/2 px-4 py-2 rounded-xl text-sm shadow z-[9999] ${
      type==='success' ? 'bg-emerald-600' : type==='error' ? 'bg-red-600' : 'bg-black/80'
    } text-white`;
    el.textContent = message;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 2500);
  }

  async function fetchQuickAddPayload(productId) {
    const url = '{{ route('production.quickAdd', 0) }}'.replace('/0', '/' + productId);
    const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
    if (!res.ok) throw new Error('Failed to load product defaults');
    return res.json();
  }

  async function handleQuickAdd(btn) {
    const card = btn.closest('.prod-card') || btn;
    const fallback = {
      id: Number(card.dataset.id || btn.dataset.id || 0),
      name: card.dataset.name || btn.dataset.name || '',
      price: Number(card.dataset.price || btn.dataset.price || 0),
    };

    btn.classList.add('btn-busy');
    const original = btn.textContent;
    btn.textContent = 'Loading…';

    try {
      const payload = await fetchQuickAddPayload(fallback.id);
      const data = {
        id: payload.id ?? fallback.id,
        name: payload.name ?? fallback.name,
        price: (typeof payload.price === 'number' ? payload.price : fallback.price) || 0,
        production_id: payload.production_id ?? null,
        production_date: payload.production_date ?? '',
        expiration_date: payload.expiration_date ?? ''
      };

      if (typeof window.prefillSaleModal === 'function') {
        window.prefillSaleModal(data);
      } else {
        const sel = document.getElementById('sale_product_id');
        const pIn = document.getElementById('sale_price');
        const qIn = document.getElementById('sale_quantity');
        const dP  = document.getElementById('sale_production_date');
        const dE  = document.getElementById('sale_expiration_date');
        const bId = document.getElementById('sale_production_id');

        if (sel) { sel.value = String(data.id); sel.dispatchEvent(new Event('change', {bubbles:true})); }
        if (pIn) pIn.value = Number(data.price || 0).toFixed(2);
        if (qIn && !qIn.value) qIn.value = '1';
        if (dP) dP.value = data.production_date || '';
        if (dE) dE.value = data.expiration_date || '';
        if (bId) bId.value = data.production_id || '';

        const modal = document.getElementById('saleModal');
        if (modal) {
          try { modal.showModal && modal.showModal(); } catch(_) {}
          modal.classList.remove('hidden'); modal.removeAttribute('aria-hidden');
        } else {
          toast('Sales modal not found.', 'error');
        }
      }
    } catch (e) {
      console.warn(e);
      if (typeof window.prefillSaleModal === 'function') {
        window.prefillSaleModal({ id: fallback.id, name: fallback.name, price: fallback.price });
      } else {
        toast('Could not prefill from server. Using fallback.', 'error');
      }
    } finally {
      btn.classList.remove('btn-busy');
      btn.textContent = original;
    }
  }

  async function handleDeleteLatest(btn) {
    const productId = Number(btn.dataset.productId || 0);
    if (!productId) return;

    if (!confirm('Delete the latest batch for this product? This will adjust inventory accordingly.')) return;

    btn.classList.add('btn-busy');
    const original = btn.textContent;
    btn.textContent = 'Deleting…';
    btn.disabled = true;

    try {
      const res = await fetch('{{ route('production.batch.destroyLatest', 0) }}'.replace('/0', '/' + productId), {
        method: 'DELETE',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
      });

      const data = await res.json();
      if (!res.ok || !data.ok) throw new Error(data.message || 'Failed to delete.');

      // Replace the card with updated HTML
      const card = document.getElementById(`product-card-${productId}`);
      if (card && data.card_html) {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = data.card_html.trim();
        const newCard = wrapper.querySelector(`#product-card-${productId}`) || wrapper.firstElementChild;
        if (newCard) card.replaceWith(newCard);
      }

      toast('Latest batch deleted.', 'success');
    } catch (err) {
      toast(err.message || 'Could not delete batch.', 'error');
    } finally {
      btn.classList.remove('btn-busy');
      btn.textContent = original;
      btn.disabled = false;
    }
  }

  // Event delegation for dynamic cards
  document.addEventListener('click', (e) => {
    const quick = e.target.closest('.js-quick-add');
    if (quick) { handleQuickAdd(quick); return; }

    const del = e.target.closest('.js-delete-latest');
    if (del) { handleDeleteLatest(del); return; }
  }, true);
})();
</script>
@endonce
