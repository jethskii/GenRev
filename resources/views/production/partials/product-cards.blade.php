{{-- resources/views/production/partials/product-cards.blade.php --}}
@php
/** @var \Illuminate\Support\Collection|\App\Models\Product[] $products */
@endphp

@once
<style>
  /* ===== Card container (soft bubble look) ===== */
  .prod-card{
    position:relative;
    display:flex; flex-direction:column;
    gap:1rem;
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:20px;
    padding:1rem 1rem 1.1rem;
    box-shadow:0 8px 18px rgba(17,24,39,.06);
    overflow:hidden;
  }

  /* ===== Image: auto-adjust (responsive 4:3) ===== */
  .prod-card-img{
    width:100%;
    height:auto;                 /* let aspect-ratio drive height */
    aspect-ratio:4 / 3;          /* consistent frame without distortion */
    object-fit:cover;            /* crop gracefully when needed */
    object-position:center;
    border-radius:14px;
    border:1px solid rgba(0,0,0,.08);
    background:#f3f4f6;
    image-rendering:auto;
  }

  /* ===== Skeleton while image loads (prevents layout shift) ===== */
  .img-skeleton{
    width:100%;
    aspect-ratio:4 / 3;
    border-radius:14px;
    border:1px solid rgba(0,0,0,.08);
    background: linear-gradient(90deg,#f3f4f6 0%,#f9fafb 40%,#f3f4f6 80%);
    background-size:200% 100%;
    animation: shimmer 1.2s infinite linear;
  }
  @keyframes shimmer{0%{background-position:-200% 0}100%{background-position:200% 0}}
  @media (prefers-reduced-motion: reduce){
    .img-skeleton{animation:none}
  }

  .prod-card:hover .prod-card-img{ filter:brightness(1.02); }

  /* ===== Stat chip ===== */
  .stat-chip{
    background:#f8fafc;
    border:1px solid #e5e7eb;
    border-radius:14px;
    padding:.9rem .9rem .8rem;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.6);
  }
  .stat-label{ color:#6b7280; font-size:.82rem; }
  .stat-val{ color:#0f172a; font-weight:700; }

  /* ===== Action buttons (neumorphic pills) ===== */
  .neo-btn{
    --padx:14px; --pady:10px;
    display:inline-flex; align-items:center; justify-content:center; gap:8px;
    padding:var(--pady) var(--padx);
    border-radius:14px; font-weight:700; font-size:.92rem; line-height:1;
    background:#fff; color:#111827; border:1px solid #e6e8f0;
    box-shadow:0 10px 18px rgba(17,24,39,.10),
               inset 0 2px 0 rgba(255,255,255,.9),
               inset 0 -2px 0 rgba(0,0,0,.06);
    transition:transform .12s, box-shadow .12s, background .12s, filter .12s;
    white-space:nowrap; flex:0 0 auto;
  }
  .neo-btn:hover{ transform:translateY(-1px); filter:brightness(1.03); }
  .neo-btn:active{ transform:translateY(0);
    box-shadow:0 3px 10px rgba(0,0,0,.10),
               inset 0 -2px 0 rgba(255,255,255,.6),
               inset 0 2px 0 rgba(0,0,0,.08); }
  .neo-btn--indigo{ color:#4338ca; border-color:#dfe2ff; }
  .neo-btn--indigo:hover{ background:#eef0ff; }
  .neo-btn--red{ color:#b91c1c; border-color:#ffe0e0; }
  .neo-btn--red:hover{ background:#fff4f4; }
  .neo-btn--green{ color:#047857; border-color:#d6f5e9; }
  .neo-btn--green:hover{ background:#e8fbf4; }

  /* ===== Actions row ===== */
  .card-actions{
    margin-top:.2rem;
    display:flex; align-items:center; justify-content:center;
    gap:.75rem; flex-wrap:wrap;
  }
  @media (max-width: 420px){
    .card-actions .neo-btn{ width: calc(50% - .38rem); justify-content:center; }
  }
  @media (max-width: 360px){
    .card-actions .neo-btn{ width:100%; }
  }

  .btn-busy{ opacity:.7; pointer-events:none; }
</style>
@endonce

@forelse($products as $p)
  @php
    $qty     = number_format((float)($p->quantity ?? 0), 3);
    $demand  = number_format((float)($p->forecasted_demand ?? 0), 3);
    $unit    = number_format((float)($p->unit_cost ?? 0), 2);
    $status  = $p->stock_status ?? ((float)($p->quantity ?? 0) > 0 ? 'in_stock' : 'out_of_stock');

    // ring state
    $delta = (float)($p->quantity ?? 0) - (float)($p->forecasted_demand ?? 0);
    $ring  = $delta <= 0 ? 'ring-1 ring-red-200' : ($delta <= 10 ? 'ring-1 ring-amber-200' : '');

    $badge = null; $badgeCls = '';
    if (isset($p->is_expired) || isset($p->days_to_expiry)) {
      if ($p->is_expired ?? false) { $badge='Expired'; $badgeCls='bg-red-100 text-red-700 border border-red-200'; }
      elseif (($p->days_to_expiry ?? 99) <= 3) { $badge=($p->days_to_expiry).'d left'; $badgeCls='bg-amber-100 text-amber-700 border border-amber-200'; }
    }

    $imgPrimary   = $p->card_image_url ?? $p->image_thumb_url ?? $p->image_url ?? asset('images/default-product.png');
    $srcset       = $p->card_image_srcset ?? null;
    $sku          = $p->sku ?? '—';
    $defaultPrice = (float)($p->default_price ?? $p->price ?? $p->unit_cost ?? 0);
  @endphp

  <div id="product-card-{{ $p->id }}"
       class="prod-card {{ $ring }}"
       data-id="{{ $p->id }}"
       data-name="{{ e($p->product_name) }}"
       data-price="{{ $defaultPrice }}">

    {{-- Image (skeleton + responsive) --}}
    <div class="relative">
      <div class="img-skeleton" aria-hidden="true"></div>
      <img
        @if($srcset)
          srcset="{{ $srcset }}"
          sizes="(min-width:1280px) 25vw, (min-width:1024px) 33vw, (min-width:640px) 50vw, 100vw"
        @endif
        src="{{ $imgPrimary }}"
        alt="{{ $p->product_name }} image"
        class="prod-card-img"
        loading="lazy" decoding="async"
        onload="this.previousElementSibling?.remove()"
        onerror="this.onerror=null;this.src='{{ asset('images/default-product.png') }}';this.previousElementSibling?.remove();"
      >
      @if($badge)
        <span class="absolute top-2 left-2 px-2 py-0.5 rounded-full text-xs {{ $badgeCls }}">{{ $badge }}</span>
      @endif
      @if(!empty($p->category))
        <span class="absolute top-2 right-2 px-2 py-0.5 rounded-full text-xs bg-white border border-gray-200 text-gray-700">
          {{ $p->category }}
        </span>
      @endif
    </div>

    {{-- Title --}}
    <div>
      <h3 class="text-lg font-semibold text-gray-900 truncate" title="{{ $p->product_name }}">{{ $p->product_name }}</h3>
      <p class="text-xs text-gray-500">SKU: {{ $sku }}</p>
    </div>

    {{-- Stats grid --}}
    <div class="grid grid-cols-2 gap-3 text-sm">
      <div class="stat-chip">
        <p class="stat-label">Inventory</p>
        <p class="stat-val">{{ $qty }} kg</p>
      </div>
      <div class="stat-chip">
        <p class="stat-label">Forecasted Demand</p>
        <p class="stat-val">{{ $demand }} kg</p>
      </div>
      <div class="stat-chip">
        <p class="stat-label">Unit Cost</p>
        <p class="stat-val">₱ {{ $unit }}</p>
      </div>
      <div class="stat-chip">
        <p class="stat-label">Status</p>
        @php
          $cls = $status === 'in_stock'
            ? 'bg-green-100 text-green-700 border-green-200'
            : 'bg-yellow-100 text-yellow-700 border-yellow-200';
          $label = $status === 'in_stock' ? 'In Stock' : 'Low Stock';
        @endphp
        <span class="px-2 py-0.5 rounded-full text-xs border {{ $cls }}">{{ $label }}</span>
      </div>
    </div>

    {{-- Actions --}}
    <div class="card-actions">
      @if (Route::has('production.show'))
        <a href="{{ route('production.show', $p->id) }}"
           class="neo-btn neo-btn--indigo"
           title="Manage production orders for this product">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"/>
          </svg>
          Manage Orders
        </a>
      @endif

      <button type="button"
              class="js-open-delete-product neo-btn neo-btn--red text-sm"
              data-product-id="{{ (int)$p->id }}"
              data-product-name="{{ e($p->product_name) }}"
              aria-haspopup="dialog"
              title="Permanently delete this product and all related data">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2h.293l.853 10.24A2 2 0 007.139 18h5.722a2 2 0 001.993-1.76L15.707 6H16a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9z" clip-rule="evenodd" />
        </svg>
        Delete Product
      </button>

      <button type="button"
              class="js-quick-add neo-btn neo-btn--green text-sm"
              data-id="{{ (int)$p->id }}"
              data-name="{{ e($p->product_name) }}"
              data-price="{{ $defaultPrice }}"
              title="Quick add this product to Sales">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
        </svg>
        Quick Add
      </button>
    </div>
  </div>
@empty
  <div class="col-span-full text-center text-gray-500 py-10">No products yet.</div>
@endforelse
