{{-- resources/views/production/partials/product-cards.blade.php --}}
@php
  /** @var \Illuminate\Support\Collection|\App\Models\Product[] $products */
@endphp

@once
  <style>
    /* ===== Card container (soft neon bubble) ===== */
    .prod-card {
      position: relative;
      display: flex;
      flex-direction: column;
      gap: 1rem;
      background:
        radial-gradient(circle at top left, rgba(129, 140, 248, .18), transparent 55%),
        radial-gradient(circle at bottom right, rgba(45, 212, 191, .16), transparent 55%),
        #ffffff;
      border-radius: 20px;
      padding: 1rem 1rem 1.1rem;
      border: 1px solid rgba(148, 163, 184, .35);
      box-shadow:
        0 16px 40px rgba(15, 23, 42, .20),
        0 0 0 1px rgba(148, 163, 184, .35);
      overflow: hidden;
      transition:
        transform .18s ease-out,
        box-shadow .18s ease-out,
        border-color .18s ease-out,
        background .18s ease-out,
        filter .18s ease-out;
    }

    .prod-card::before {
      content: '';
      position: absolute;
      inset: -2px;
      border-radius: 22px;
      background: conic-gradient(from 140deg,
          rgba(129, 140, 248, .9),
          rgba(45, 212, 191, .9),
          rgba(248, 250, 252, 0),
          rgba(251, 191, 36, .85),
          rgba(129, 140, 248, .9));
      opacity: 0;
      transition: opacity .22s ease-out;
      z-index: -1;
    }

    .prod-card:hover {
      transform: translateY(-4px) scale(1.01);
      box-shadow:
        0 20px 55px rgba(15, 23, 42, .30),
        0 0 0 1px rgba(129, 140, 248, .45);
      border-color: rgba(129, 140, 248, .35);
      filter: drop-shadow(0 0 12px rgba(129, 140, 248, .45));
    }

    .prod-card:hover::before {
      opacity: .7;
    }

    /* subtle entry animation */
    .prod-card {
      opacity: 0;
      transform: translateY(6px) scale(.99);
      animation: prodCardIn .3s ease-out forwards;
    }

    @keyframes prodCardIn {
      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    /* ===== Media wrapper: fixed 4:3 frame with overlay ===== */
    .prod-card-media {
      position: relative;
      width: 100%;
      aspect-ratio: 4 / 3;
      border-radius: 16px;
      border: 1px solid rgba(15, 23, 42, .08);
      overflow: hidden;
      background: linear-gradient(135deg, #e5e7eb, #f9fafb);
      box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .5);
    }

    .prod-card-media::after {
      content: '';
      position: absolute;
      inset: 0;
      background: radial-gradient(circle at top, rgba(15, 23, 42, .12), transparent 60%);
      mix-blend-mode: multiply;
      opacity: .0;
      transition: opacity .2s ease-out;
      pointer-events: none;
    }

    .prod-card:hover .prod-card-media::after {
      opacity: .35;
    }

    /* ===== Skeleton while image loads ===== */
    .img-skeleton {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, #f3f4f6 0%, #f9fafb 40%, #f3f4f6 80%);
      background-size: 200% 100%;
      animation: shimmer 1.2s infinite linear;
    }

    @keyframes shimmer {
      0% {
        background-position: -200% 0
      }

      100% {
        background-position: 200% 0
      }
    }

    @media (prefers-reduced-motion: reduce) {
      .img-skeleton {
        animation: none
      }
    }

    /* ===== Image: zoom + slight tilt on hover ===== */
    .prod-card-img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      object-position: center;
      image-rendering: auto;
      display: block;
      transform: scale(1.03);
      transition: transform .25s ease-out, filter .25s ease-out;
    }

    .prod-card:hover .prod-card-img {
      transform: scale(1.08) translateY(-1px);
      filter: brightness(1.05) contrast(1.03);
    }

    /* ===== Stat chip ===== */
    .stat-chip {
      background: linear-gradient(135deg, #f9fafb, #eff6ff);
      border: 1px solid rgba(148, 163, 184, .35);
      border-radius: 14px;
      padding: .9rem .9rem .8rem;
      box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, .9),
        0 6px 12px rgba(15, 23, 42, .08);
      position: relative;
      overflow: hidden;
    }

    .stat-chip::before {
      content: '';
      position: absolute;
      inset: -40%;
      background: radial-gradient(circle at 0 0, rgba(129, 140, 248, .18), transparent 55%);
      opacity: 0;
      transition: opacity .2s ease-out;
      pointer-events: none;
    }

    .prod-card:hover .stat-chip::before {
      opacity: .8;
    }

    .stat-label {
      color: #6b7280;
      font-size: .8rem;
      letter-spacing: .01em;
      text-transform: uppercase;
    }

    .stat-val {
      color: #0f172a;
      font-weight: 800;
      font-size: .98rem;
    }

    /* ===== Pulsing status dot ===== */
    .status-dot {
      width: .55rem;
      height: .55rem;
      border-radius: 999px;
      display: inline-block;
      margin-right: .3rem;
      position: relative;
    }

    .status-dot::after {
      content: '';
      position: absolute;
      inset: -3px;
      border-radius: 999px;
      border: 2px solid currentColor;
      opacity: .4;
      animation: statusPulse 1.6s ease-out infinite;
    }

    @keyframes statusPulse {
      0% {
        transform: scale(.6);
        opacity: .5
      }

      80% {
        transform: scale(1.35);
        opacity: 0
      }

      100% {
        transform: scale(1.35);
        opacity: 0
      }
    }

    /* ===== Action buttons (neumorphic pills) ===== */
    .neo-btn {
      --padx: 14px;
      --pady: 10px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: var(--pady) var(--padx);
      border-radius: 999px;
      font-weight: 700;
      font-size: .92rem;
      line-height: 1;
      background: radial-gradient(circle at top, #ffffff, #f3f4ff);
      color: #111827;
      border: 1px solid #e6e8f0;
      box-shadow:
        0 10px 18px rgba(17, 24, 39, .10),
        inset 0 2px 0 rgba(255, 255, 255, .9),
        inset 0 -2px 0 rgba(15, 23, 42, .08);
      transition:
        transform .12s,
        box-shadow .12s,
        background .12s,
        filter .12s,
        border-color .12s;
      white-space: nowrap;
      flex: 0 0 auto;
    }

    .neo-btn svg {
      flex-shrink: 0;
    }

    .neo-btn:hover {
      transform: translateY(-1px);
      filter: brightness(1.04);
      box-shadow:
        0 14px 24px rgba(15, 23, 42, .18),
        inset 0 2px 0 rgba(255, 255, 255, .95);
    }

    .neo-btn:active {
      transform: translateY(0);
      box-shadow:
        0 3px 10px rgba(0, 0, 0, .10),
        inset 0 -2px 0 rgba(255, 255, 255, .6),
        inset 0 2px 0 rgba(0, 0, 0, .08);
    }

    .neo-btn--indigo {
      color: #4338ca;
      border-color: #dfe2ff;
      background: linear-gradient(135deg, #eef2ff, #e0f2fe);
    }

    .neo-btn--indigo:hover {
      background: linear-gradient(135deg, #e0e7ff, #bfdbfe);
      border-color: #c7d2fe;
    }

    .neo-btn--red {
      color: #b91c1c;
      border-color: #ffe0e0;
      background: linear-gradient(135deg, #fee2e2, #fef2f2);
    }

    .neo-btn--red:hover {
      background: linear-gradient(135deg, #fecaca, #fee2e2);
      border-color: #fecaca;
    }

    .neo-btn--green {
      color: #047857;
      border-color: #d6f5e9;
      background: linear-gradient(135deg, #dcfce7, #ecfdf5);
    }

    .neo-btn--green:hover {
      background: linear-gradient(135deg, #bbf7d0, #d1fae5);
      border-color: #a7f3d0;
    }

    /* ===== Actions row ===== */
    .card-actions {
      margin-top: .3rem;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: .75rem;
      flex-wrap: wrap;
    }

    @media (max-width: 420px) {
      .card-actions .neo-btn {
        width: calc(50% - .38rem);
        justify-content: center;
      }
    }

    @media (max-width: 360px) {
      .card-actions .neo-btn {
        width: 100%;
      }
    }

    .btn-busy {
      opacity: .7;
      pointer-events: none;
    }
  </style>
@endonce

@forelse($products as $p)
  @php
    $qty = number_format((float) ($p->quantity ?? 0), 3);
    $demand = number_format((float) ($p->forecasted_demand ?? 0), 3);
    $status = $p->stock_status ?? ((float) ($p->quantity ?? 0) > 0 ? 'in_stock' : 'out_of_stock');

    // ring state
    $delta = (float) ($p->quantity ?? 0) - (float) ($p->forecasted_demand ?? 0);
    $ring = $delta <= 0 ? 'ring-1 ring-red-200' : ($delta <= 10 ? 'ring-1 ring-amber-200' : '');

    $badge = null;
    $badgeCls = '';
    if (isset($p->is_expired) || isset($p->days_to_expiry)) {
      if ($p->is_expired ?? false) {
        $badge = 'Expired';
        $badgeCls = 'bg-red-100 text-red-700 border border-red-200';
      } elseif (($p->days_to_expiry ?? 99) <= 3) {
        $badge = ($p->days_to_expiry) . 'd left';
        $badgeCls = 'bg-amber-100 text-amber-700 border border-amber-200';
      }
    }

    // next expiry from latest production snapshot
    $nextExpiryRaw = $p->latest_expiration_date ?? null;
    $nextExpiry = $nextExpiryRaw
      ? \Carbon\Carbon::parse($nextExpiryRaw)->format('M d, Y')
      : '—';

    // Image pipeline:
    // 1) Product card image
    // 2) Product thumb / image_url
    // 3) Latest production batch image (so Add Order upload can show here)
    // 4) Default placeholder
    $latestProduction = $p->latestProduction ?? null;
    $batchImg = $latestProduction ? $latestProduction->image_url : null;
    $batchSet = $latestProduction ? $latestProduction->image_srcset : null;

    $imgPrimary = $p->card_image_url
      ?? $p->image_thumb_url
      ?? $p->image_url
      ?? $batchImg
      ?? asset('images/default-product.png');

    $srcset = $p->card_image_srcset
      ?? $batchSet
      ?? null;

    $sku = $p->sku ?? '—';
    $defaultPrice = (float) ($p->default_price ?? $p->price ?? $p->unit_cost ?? 0);
  @endphp

  <div id="product-card-{{ $p->id }}" class="prod-card {{ $ring }}" data-id="{{ $p->id }}"
    data-name="{{ e($p->product_name) }}" data-price="{{ $defaultPrice }}">

    {{-- Image block: fixed frame + skeleton + glow --}}
    <div class="prod-card-media">
      <div class="img-skeleton" aria-hidden="true"></div>

      <img @if($srcset) srcset="{{ $srcset }}" sizes="(min-width:1280px) 25vw,
         (min-width:1024px) 33vw,
         (min-width:640px) 50vw,
       100vw" @endif src="{{ $imgPrimary }}" alt="{{ $p->product_name }} image" class="prod-card-img" loading="lazy"
        decoding="async" onload="this.previousElementSibling?.remove()"
        onerror="this.onerror=null;this.src='{{ asset('images/default-product.png') }}';this.previousElementSibling?.remove();">

      @if($badge)
        <span class="absolute top-2 left-2 px-2 py-0.5 rounded-full text-xs {{ $badgeCls }}">
          {{ $badge }}
        </span>
      @endif

      @if(!empty($p->category))
        <span
          class="absolute top-2 right-2 px-2.5 py-0.5 rounded-full text-[0.65rem] uppercase tracking-wide bg-white/90 border border-indigo-100 text-indigo-700 shadow-sm">
          {{ $p->category }}
        </span>
      @endif
    </div>

    {{-- Title --}}
    <div>
      <h3 class="text-lg font-semibold text-gray-900 truncate flex items-center gap-1.5" title="{{ $p->product_name }}">
        <span class="inline-block w-1.5 h-5 rounded-full bg-gradient-to-b from-indigo-400 to-cyan-400"></span>
        {{ $p->product_name }}
      </h3>
      <p class="text-xs text-gray-500">SKU: {{ $sku }}</p>
    </div>

    {{-- Stats grid (Unit Cost removed, Next Expiry added) --}}
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
        <p class="stat-label">Next Expiry</p>
        <p class="stat-val">{{ $nextExpiry }}</p>
      </div>
      <div class="stat-chip">
        <p class="stat-label">Status</p>
        @php
          $cls = $status === 'in_stock'
            ? 'bg-green-100 text-green-700 border-green-200'
            : 'bg-yellow-100 text-yellow-700 border-yellow-200';
          $label = $status === 'in_stock' ? 'In Stock' : 'Low Stock';
          $dotColor = $status === 'in_stock' ? 'bg-green-500 text-green-500' : 'bg-yellow-400 text-yellow-400';
        @endphp
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs border {{ $cls }}">
          <span class="status-dot {{ $dotColor }}"></span>
          {{ $label }}
        </span>
      </div>
    </div>

    {{-- Actions --}}
    <div class="card-actions">
      @if (Route::has('production.show'))
        <a href="{{ route('production.show', $p->id) }}" class="neo-btn neo-btn--indigo"
          title="Manage production orders for this product">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
            aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18" />
          </svg>
          Manage Orders
        </a>
      @endif

      <button type="button" class="js-open-archive-product neo-btn neo-btn--red text-sm"
        data-product-id="{{ (int) $p->id }}" data-product-name="{{ e($p->product_name) }}" aria-haspopup="dialog"
        title="Archive this product">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd"
            d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2h.293l.853 10.24A2 2 0 007.139 18h5.722a2 2 0 001.993-1.76L15.707 6H16a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9z"
            clip-rule="evenodd" />
        </svg>
        Archive Product
      </button>


      <script>
        $(document).on('click', '.js-open-archive-product', function () {
          let productId = $(this).data('product-id');

          $.post(`/products/${productId}/archive-product`, {
            _token: $('meta[name="csrf-token"]').attr('content')
          })
            .done(function (res) {
              console.log("ARCHIVED:", res);
              location.reload();
            })
            .fail(function (err) {
              console.error(err);
              alert("Archive failed.");
            });
        });




      </script>



      <button type="button" class="js-quick-add neo-btn neo-btn--green text-sm" data-id="{{ (int) $p->id }}"
        data-name="{{ e($p->product_name) }}" data-price="{{ $defaultPrice }}" title="Quick add this product to Sales">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"
          aria-hidden="true">
          <path fill-rule="evenodd"
            d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
            clip-rule="evenodd" />
        </svg>
        Quick Add
      </button>
    </div>
  </div>
@empty
  <div class="col-span-full text-center text-gray-500 py-10">
    No products yet.
  </div>
@endforelse