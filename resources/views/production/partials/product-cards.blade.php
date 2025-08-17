@php
/** @var \Illuminate\Support\Collection|\App\Models\Product[] $products */
@endphp

<style>
/* Keep card images crisp, same height, subtle hover */
.prod-card-img{
  width: 100%;
  height: 10rem; /* ~160px */
  object-fit: cover;
  border-radius: 12px;
  border: 1px solid rgba(255,255,255,.12);
  background: linear-gradient(135deg, #657423 0%, #2a2a2a 100%);
}
.prod-card:hover .prod-card-img{ filter: brightness(1.02); }
</style>

@forelse($products as $p)
  @php
    $qty     = number_format((float)($p->quantity ?? 0), 3);
    $demand  = number_format((float)($p->forecasted_demand ?? 0), 3);
    $unit    = number_format((float)($p->unit_cost ?? 0), 2);
    $status  = $p->stock_status ?? ((float)($p->quantity ?? 0) > 0 ? 'in_stock' : 'out_of_stock');

    // Urgency ring by delta
    $delta = (float)($p->quantity ?? 0) - (float)($p->forecasted_demand ?? 0);
    $ring  = $delta <= 0 ? 'ring-1 ring-rose-700/50'
          : ($delta <= 10 ? 'ring-1 ring-amber-600/40' : '');

    // Optional expiry badge support if controller augmented these fields
    $badge = null; $badgeCls = '';
    if (isset($p->is_expired) || isset($p->days_to_expiry)) {
        if ($p->is_expired ?? false) { $badge = 'Expired'; $badgeCls = 'bg-rose-600/15 text-rose-300 border border-rose-700/40'; }
        elseif (($p->days_to_expiry ?? 99) <= 3) { $badge = ($p->days_to_expiry).'d left'; $badgeCls = 'bg-amber-500/15 text-amber-300 border border-amber-600/40'; }
    }
  @endphp

  <div class="prod-card glass rounded-2xl border border-white/10 p-4 flex flex-col gap-3 hover:bg-white/5 transition {{ $ring }}">
    {{-- Image --}}
    <div class="relative">
      <img
        src="{{ $p->image_url }}"
        alt="{{ $p->product_name }} image"
        class="prod-card-img"
        loading="lazy"
        onerror="this.onerror=null;this.src='{{ asset('images/default-product.png') }}';"
      >
      @if($badge)
        <span class="absolute top-2 left-2 px-2 py-0.5 rounded-full text-xs {{$badgeCls}} backdrop-blur">
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
      <div>
        <h3 class="text-lg font-semibold text-white">{{ $p->product_name }}</h3>
        <p class="text-xs text-white/60">SKU: {{ $p->sku ?? '—' }}</p>
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
        <span class="px-2 py-0.5 rounded-full text-xs border {{ $cls }}">{{ str_replace('_',' ', $status) }}</span>
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

      {{-- Quick add: prefill modal and focus qty --}}
      <button
        type="button"
        onclick="openAddModal(); setModalProduct({{ (int)$p->id }}, '{{ e($p->product_name) }}', {{ (float)($p->unit_cost ?? 0) }}, {{ (float)($p->forecasted_demand ?? 0) }});"
        class="px-3 py-2 rounded-xl bg-[var(--sidebar-active,#EDD100)] text-[#1F1E1E] font-semibold hover:opacity-90">
        + Quick Add
      </button>
    </div>
  </div>
@empty
  <div class="col-span-full text-center text-white/70 py-10">No products yet.</div>
@endforelse

{{-- Helper to prefill modal fields when Quick Add is clicked --}}
<script>
window.setModalProduct = function(id, name, unitCost, forecasted) {
  const idEl   = document.getElementById('product_id');
  const nameEl = document.getElementById('product_name');
  const ucEl   = document.getElementById('unit_cost');
  const fdEl   = document.getElementById('forecasted_demand');

  if (idEl)   idEl.value   = id;
  if (nameEl) nameEl.value = name;
  if (ucEl)   ucEl.value   = (unitCost ?? 0);
  if (fdEl)   fdEl.value   = (forecasted ?? 0);

  setTimeout(() => { document.getElementById('produced_qty_kg')?.focus(); }, 50);
};
</script>
