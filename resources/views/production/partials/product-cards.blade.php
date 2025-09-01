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

  /* ===== 3D Buttons ===== */
  .btn-3d{
    position: relative;
    border-radius: 14px;
    font-weight: 700;
    padding: .55rem .9rem;
    border: 1px solid rgba(255,255,255,.12);
    transition: transform .12s ease, box-shadow .12s ease, filter .12s ease, background .12s ease;
    box-shadow:
      0 10px 18px rgba(0,0,0,.35),
      0 2px 0 rgba(255,255,255,.06) inset,
      0 -2px 0 rgba(0,0,0,.25) inset;
    backdrop-filter: blur(3px);
    line-height: 1;
  }
  .btn-3d:hover{ transform: translateY(-1px); filter: brightness(1.03); }
  .btn-3d:active{
    transform: translateY(0px);
    box-shadow:
      0 3px 10px rgba(0,0,0,.35),
      0 -2px 0 rgba(255,255,255,.03) inset,
      0 2px 0 rgba(0,0,0,.35) inset;
  }

  .btn-3d-danger{
    color:#fff;
    background: linear-gradient(180deg, #ff5353 0%, #c51e1e 100%);
    border-color: rgba(255,83,83,.3);
    text-shadow: 0 1px 0 rgba(0,0,0,.35);
  }
  .btn-3d-danger:hover{ background: linear-gradient(180deg, #ff5f5f 0%, #d22020 100%); }

  .btn-3d-neutral{
    color:#eaeaea;
    background: linear-gradient(180deg, rgba(255,255,255,.06) 0%, rgba(255,255,255,.03) 100%);
    border-color: rgba(255,255,255,.15);
  }

  /* ===== Modal (Glass + 3D confirm) ===== */
  .modal-overlay{
    position: fixed; inset: 0; z-index: 60;
    display: none; align-items: center; justify-content: center;
    background: rgba(0,0,0,.55);
  }
  .modal-overlay.show{ display:flex; }
  .modal-card{
    width: 100%; max-width: 460px;
    border-radius: 18px;
    background: linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.03));
    border: 1px solid rgba(255,255,255,.12);
    box-shadow: 0 20px 48px rgba(0,0,0,.45);
    color: #fff;
  }
  .modal-header{ padding: 14px 16px; border-bottom: 1px solid rgba(255,255,255,.1); }
  .modal-body{ padding: 16px; color: #eaeaea; }
  .modal-actions{ padding: 14px 16px; display:flex; gap:.5rem; justify-content:flex-end; border-top: 1px solid rgba(255,255,255,.08); }
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
    <div class="flex items-center justify-between gap-2 pt-2">
      <div class="flex items-center gap-2">
        @if (Route::has('production.show'))
          <a href="{{ route('production.show', $p->id) }}"
             class="px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-white/90 hover:bg-white/10">
            Manage Orders
          </a>
        @endif
      </div>

      <div class="flex items-center gap-2">
        {{-- Delete Product (3D) --}}
        <button
          type="button"
          class="js-open-delete-product btn-3d btn-3d-danger text-sm"
          data-product-id="{{ (int)$p->id }}"
          data-product-name="{{ e($p->product_name) }}"
          aria-haspopup="dialog"
          title="Permanently delete this product and all related data"
        >
          Delete Product
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
  </div>
@empty
  <div class="col-span-full text-center text-white/70 py-10">No products yet.</div>
@endforelse

{{-- ===== Global Delete Product Modal (single reusable instance) ===== --}}
@once
<div id="confirmDeleteProductModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="delTitle" aria-hidden="true">
  <div class="modal-card">
    <div class="modal-header">
      <h3 id="delTitle" class="text-lg font-semibold">Delete Product?</h3>
    </div>
    <div class="modal-body">
      <p class="text-sm leading-relaxed">
        This action will permanently remove <span id="delProductName" class="font-semibold text-white"></span> and
        all related data. Are you sure you want to continue?
      </p>
    </div>
    <div class="modal-actions">
      <button type="button" class="btn-3d btn-3d-neutral js-cancel-del">Cancel</button>
      <button type="button" class="btn-3d btn-3d-danger js-confirm-del">Confirm Delete</button>
    </div>
  </div>
</div>

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

  /* ===== Delete Product (modal flow) ===== */
  const delModal   = document.getElementById('confirmDeleteProductModal');
  const delNameEl  = document.getElementById('delProductName');
  const btnCancel  = delModal?.querySelector('.js-cancel-del');
  const btnConfirm = delModal?.querySelector('.js-confirm-del');
  let activeProductId = null;

  function openDelModal(id, name){
    activeProductId = id;
    if (delNameEl) delNameEl.textContent = name || 'this product';
    delModal?.classList.add('show');
    delModal?.setAttribute('aria-hidden', 'false');
  }
  function closeDelModal(){
    delModal?.classList.remove('show');
    delModal?.setAttribute('aria-hidden', 'true');
    activeProductId = null;
  }

  async function confirmDeleteProduct(){
    if (!activeProductId) return;
    btnConfirm?.classList.add('btn-busy');

    try {
      // RESTful route: products.destroy
      const url = '{{ route('products.destroy', 0) }}'.replace('/0', '/' + activeProductId);

      const res = await fetch(url, {
        method: 'DELETE',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Accept': 'application/json'
        }
      });

      if (!res.ok) {
        let msg = 'Failed to delete product.';
        try {
          const data = await res.json();
          if (data?.message) msg = data.message;
        } catch(_) {}
        throw new Error(msg);
      }

      // remove card from UI
      const card = document.getElementById(`product-card-${activeProductId}`);
      if (card) card.remove();
      toast('Product permanently deleted.', 'success');
      closeDelModal();
    } catch (err) {
      toast(err.message || 'Could not delete product.', 'error');
    } finally {
      btnConfirm?.classList.remove('btn-busy');
    }
  }

  document.addEventListener('click', (e) => {
    const quick = e.target.closest('.js-quick-add');
    if (quick) { handleQuickAdd(quick); return; }

    const delProd = e.target.closest('.js-open-delete-product');
    if (delProd) {
      const id   = Number(delProd.dataset.productId || 0);
      const name = delProd.dataset.productName || '';
      openDelModal(id, name);
      return;
    }

    if (e.target === delModal) closeDelModal(); // click backdrop to close
  }, true);

  btnCancel?.addEventListener('click', closeDelModal);
  btnConfirm?.addEventListener('click', confirmDeleteProduct);
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && delModal?.classList.contains('show')) closeDelModal();
  });
})();
</script>
@endonce
