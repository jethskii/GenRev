@extends('layout.mainlayout')

@section('styles')
<style>
  /* ===== Glassy Meat-System Theme ===== */
  :root{
    --glass-bg: rgba(255,255,255,.8);
    --glass-brd: rgba(229,231,235,.8);
    --shadow: 0 14px 36px rgba(15,23,42,.12);

    --ink:#0f172a;
    --muted:#6b7280;

    /* chips */
    --chip-green-bg:#ecfdf5; --chip-green-brd:#a7f3d0; --chip-green-ink:#065f46;
    --chip-yellow-bg:#fffbeb; --chip-yellow-brd:#fde68a; --chip-yellow-ink:#92400e;
    --chip-red-bg:#fef2f2;    --chip-red-brd:#fecaca;   --chip-red-ink:#991b1b;
    --chip-blue-bg:#eff6ff;   --chip-blue-brd:#bfdbfe;  --chip-blue-ink:#1d4ed8;
    --chip-gray-bg:#f3f4f6;   --chip-gray-brd:#e5e7eb;  --chip-gray-ink:#374151;

    /* availability mini-cards */
    --pack-bg:#fff7ed;
    --pack-brd:#fed7aa;
    --pack-dot:#f97316;

    --bag-bg:#eff6ff;
    --bag-brd:#bfdbfe;
    --bag-dot:#2563eb;

    --accent-red:#b91c1c;
    --accent-red-soft:#fee2e2;
    --accent-green:#16a34a;
  }

  .page-wrap { background:#f5f5f6; }

  .light-card{
    background:var(--glass-bg);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border:1px solid var(--glass-brd);
    border-radius:16px;
    box-shadow:var(--shadow);
  }

  /* Inputs */
  .input-light{
    background:#fff;
    border:1px solid #e5e7eb;
    color:#111827;
    border-radius:10px;
    padding:.625rem .75rem;
    line-height:1.4;
    font-size:.875rem;
  }
  .input-light::placeholder{color:#9ca3af}
  .input-light:focus{
    outline:0;
    border-color:#f97373;
    box-shadow:0 0 0 2px rgba(248,113,113,.25);
    background:#fef2f2;
  }

  /* Search shell (glassy) */
  .search-shell{
    display:flex;
    align-items:stretch;
    gap:.4rem;
    background:rgba(255,255,255,.9);
    border-radius:999px;
    border:1px solid rgba(229,231,235,.9);
    padding:.15rem .2rem .15rem .4rem;
    box-shadow:0 6px 16px rgba(15,23,42,.12);
  }
  .search-shell .search-input{
    border-radius:999px;
    border:none;
    box-shadow:none;
    background:transparent;
    padding-left:0;
  }
  .search-shell .search-input:focus{
    border:none;
    box-shadow:none;
    background:transparent;
  }
  .search-shell .search-btn{
    border-radius:999px;
    padding:.4rem .95rem;
    font-size:.78rem;
    white-space:nowrap;
  }

  /* Buttons (shared style with other meat-system pages) */
  .btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:.35rem;
    padding:.45rem .9rem;
    border-radius:999px;
    border:1px solid rgba(15,23,42,.4);
    background:#f9fafb;
    color:#111827;
    font-size:.8rem;
    font-weight:600;
    line-height:1.2;
    cursor:pointer;
    box-shadow:0 2px 6px rgba(15,23,42,.12);
    transition:background .12s ease,transform .12s ease,box-shadow .12s ease;
    text-decoration:none;
  }
  .btn:hover{
    background:#e5e7eb;
    transform:translateY(-1px);
    box-shadow:0 4px 10px rgba(15,23,42,.18);
  }
  .btn:active{
    transform:translateY(0);
    box-shadow:0 1px 3px rgba(15,23,42,.18);
  }
  .btn-primary{
    background:var(--accent-red);
    color:#fef2f2;
    border-color:var(--accent-red);
    box-shadow:0 3px 10px rgba(185,28,28,.45);
  }
  .btn-primary:hover{
    background:#991b1b;
  }
  .btn-ghost{
    background:#ffffff;
  }
  .btn-ghost:hover{
    background:#f3f4f6;
  }

  /* Chips (status) */
  .chip{
    display:inline-block;
    padding:.28rem .65rem;
    border-radius:999px;
    font-weight:700;
    font-size:.72rem;
    border:1px solid #e5e7eb;
  }
  .chip--in{background:var(--chip-green-bg);border-color:var(--chip-green-brd);color:var(--chip-green-ink)}
  .chip--low{background:var(--chip-yellow-bg);border-color:var(--chip-yellow-brd);color:var(--chip-yellow-ink)}
  .chip--out{background:var(--chip-red-bg);border-color:var(--chip-red-brd);color:var(--chip-red-ink)}
  .chip--sale{background:var(--chip-blue-bg);border-color:var(--chip-blue-brd);color:var(--chip-blue-ink)}
  .chip--pending{background:#fff7ed;border-color:#fed7aa;color:#9a3412}
  .chip--inactive{background:var(--chip-gray-bg);border-color:var(--chip-gray-brd);color:var(--chip-gray-ink)}

  /* Table */
  table{border-collapse:separate;border-spacing:0}
  thead th{
    background:rgba(249,250,251,.9);
    color:#374151;
    font-weight:800;
    backdrop-filter: blur(6px);
    font-size:.72rem;
    text-transform:uppercase;
  }
  th,td{border-color:#e5e7eb!important}
  tbody td{color:#111827;font-size:.82rem;}
  tbody tr:nth-child(even){background:rgba(250,250,250,.65)}
  tbody tr:nth-child(odd){background:rgba(255,255,255,.8)}

  /* Row animation */
  tbody tr{
    transition:background .14s ease,transform .12s ease,box-shadow .12s ease;
  }
  tbody tr:hover{
    background:rgba(254,242,242,.96);
    transform:translateY(-1px);
    box-shadow:0 6px 14px rgba(15,23,42,.15);
  }

  /* Links */
  .link-accent-blue{color:#1d4ed8;font-weight:600}
  .link-accent-blue:hover{text-decoration:underline}
  .link-accent-green{color:#047857;font-weight:600}
  .link-accent-green:hover{text-decoration:underline}

  .muted{color:#6b7280}

  /* Availability cards (Pack / Bag) */
  .avail-grid{
    display:flex;
    flex-wrap:wrap;
    gap:.5rem;
  }
  .avail-card{
    flex:1 1 150px;
    border-radius:12px;
    border:1px solid rgba(148,163,184,.4);
    background:#f9fafb;
    padding:.45rem .55rem .5rem;
    box-shadow:0 2px 8px rgba(15,23,42,.06);
    animation:availFade .2s ease-out;
    transition:box-shadow .12s ease,transform .12s ease,background .12s ease;
  }
  .avail-card:hover{
    background:#ffffff;
    transform:translateY(-1px);
    box-shadow:0 5px 14px rgba(15,23,42,.16);
  }
  .avail-label{
    font-size:.7rem;
    text-transform:uppercase;
    letter-spacing:.05em;
    color:#6b7280;
    margin-bottom:.2rem;
    display:flex;
    justify-content:space-between;
    align-items:center;
  }
  .avail-label span:last-child{
    font-weight:600;
    font-size:.7rem;
    color:#9ca3af;
  }
  .avail-lines{
    display:flex;
    flex-direction:column;
    gap:.15rem;
    margin-top:.1rem;
  }
  .avail-line{
    display:flex;
    align-items:center;
    gap:.35rem;
    font-size:.78rem;
  }
  .avail-dot{
    width:7px;
    height:7px;
    border-radius:999px;
  }
  .avail-dot-pack{
    background:var(--pack-dot);
  }
  .avail-dot-bag{
    background:var(--bag-dot);
  }
  .avail-pill{
    padding:.08rem .5rem;
    border-radius:999px;
    font-weight:600;
    font-size:.75rem;
    white-space:nowrap;
  }
  .avail-pill-pack{
    background:var(--pack-bg);
    border:1px solid var(--pack-brd);
    color:#9a3412;
  }
  .avail-pill-bag{
    background:var(--bag-bg);
    border:1px solid var(--bag-brd);
    color:#1d4ed8;
  }

  /* Latest batch card */
  .batch-card{
    border-radius:12px;
    border:1px solid rgba(148,163,184,.4);
    background:linear-gradient(135deg,#fef3c7,#fef9c3);
    padding:.45rem .55rem .5rem;
    box-shadow:0 3px 10px rgba(250,204,21,.32);
    display:flex;
    flex-direction:column;
    gap:.18rem;
    font-size:.78rem;
    transition:transform .12s ease,box-shadow .12s ease;
  }
  .batch-card:hover{
    transform:translateY(-1px);
    box-shadow:0 6px 16px rgba(250,204,21,.45);
  }
  .batch-tag{
    display:inline-flex;
    align-items:center;
    gap:.3rem;
    border-radius:999px;
    padding:.12rem .55rem;
    background:#f59e0b;
    color:#fefce8;
    font-size:.72rem;
    font-weight:600;
  }
  .batch-dot{
    width:7px;
    height:7px;
    border-radius:999px;
    background:#fefce8;
  }
  .batch-dates{
    display:flex;
    flex-direction:column;
    gap:.05rem;
    color:#78350f;
  }

  /* Prices cards (Pack / Bag separate) */
  .price-grid{
    display:flex;
    flex-direction:column;
    gap:.35rem;
  }
  .price-card{
    border-radius:10px;
    padding:.35rem .55rem;
    font-size:.78rem;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:.5rem;
  }
  .price-label{
    text-transform:uppercase;
    letter-spacing:.05em;
    font-size:.7rem;
  }
  .price-card-pack{
    background:linear-gradient(135deg,#fef3c7,#ffedd5);
    border:1px solid #fed7aa;
    color:#92400e;
  }
  .price-card-bag{
    background:linear-gradient(135deg,#eff6ff,#e0f2fe);
    border:1px solid #bfdbfe;
    color:#1d4ed8;
  }

  /* Action pills (animated, colorful, vertical) */
  .actions-bar{
    display:flex;
    flex-direction:column;
    align-items:flex-end;
    gap:.35rem;
  }
  .action-pill{
    display:inline-flex;
    align-items:center;
    gap:.25rem;
    border-radius:999px;
    padding:.25rem .75rem;
    font-size:.72rem;
    font-weight:600;
    text-decoration:none;
    color:#111827;
    transition:transform .12s ease,box-shadow .12s ease,background .14s ease;
    box-shadow:0 2px 6px rgba(15,23,42,.12);
    min-width:120px;
    justify-content:flex-start;
  }
  .action-pill span.icon{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    width:18px;
    height:18px;
    border-radius:999px;
    background:rgba(255,255,255,.9);
    font-size:.75rem;
  }
  .action-pill--materials{
    background:linear-gradient(135deg,#f97316,#facc15);
    color:#fefce8;
  }
  .action-pill--production{
    background:linear-gradient(135deg,#22c55e,#16a34a);
    color:#ecfdf3;
  }
  .action-pill--sales{
    background:linear-gradient(135deg,#38bdf8,#1d4ed8);
    color:#eff6ff;
  }
  .action-pill--edit{
    background:linear-gradient(135deg,#e5e7eb,#9ca3af);
    color:#111827;
  }
  .action-pill:hover{
    transform:translateY(-1px) scale(1.02);
    box-shadow:0 4px 12px rgba(15,23,42,.2);
  }
  .action-pill:active{
    transform:translateY(0) scale(0.99);
    box-shadow:0 2px 6px rgba(15,23,42,.25);
  }

  @keyframes availFade{
    from{opacity:0;transform:translateY(4px);}
    to{opacity:1;transform:translateY(0);}
  }

  @media (max-width:768px){
    .light-card{border-radius:14px;}
    .actions-wrap{
      flex-direction:column;
      align-items:flex-start;
    }
  }
</style>
@endsection

@section('content')
<div class="page-wrap text-gray-900 p-6 rounded-2xl light-card">

  {{-- Header + actions --}}
  <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 mb-6">
    <div>
      <h2 class="text-2xl font-bold tracking-wide text-gray-900">Products</h2>
      <p class="text-sm text-gray-500">Master list connected to Recipes, Inventory, and Production</p>
    </div>

    <div class="flex items-center gap-2 actions-wrap">
      <a href="{{ route('materials.index') }}" class="text-sm link-accent-green">Global Materials</a>

      {{-- Quick add removed as requested --}}
      @if(Route::has('products.create'))
        <a href="{{ route('products.create') }}" class="btn btn-primary whitespace-nowrap">
          + Add New Product
        </a>
      @endif
    </div>
  </div>

  {{-- Flashes --}}
  @if(session('success'))
    <div class="mb-4 text-green-700 bg-green-50 border border-green-200 px-3 py-2 rounded-lg text-sm">
      {{ session('success') }}
    </div>
  @endif
  @if($errors->any())
    <div class="mb-4 text-rose-700 bg-rose-50 border border-rose-200 px-3 py-2 rounded-lg text-sm">
      Please fix the errors and try again.
    </div>
  @endif

  {{-- Filters --}}
  <form method="GET" action="{{ route('products.index') }}" class="light-card mb-4 px-4 py-3 rounded-xl">
    <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
      <div class="md:col-span-5">
        <div class="search-shell">
          <input type="text"
                 name="search"
                 value="{{ request('search') }}"
                 class="input-light w-full search-input"
                 placeholder="Search by name or code…">
          <button type="submit" class="btn btn-primary search-btn">
            Search
          </button>
        </div>
      </div>

      <div class="md:col-span-3">
        <select name="category" class="input-light w-full">
          <option value="">All categories</option>
          @foreach(($categories ?? []) as $cat)
            <option value="{{ $cat }}" @selected(request('category')===$cat)>{{ $cat }}</option>
          @endforeach
        </select>
      </div>

      <div class="md:col-span-2">
        <select name="status" class="input-light w-full">
          <option value="">All status</option>
          @php
            $statusOptions = [
              'in_stock'     => 'In Stock',
              'low_stock'    => 'Low Stock',
              'out_of_stock' => 'Out of Stock',
              'inactive'     => 'Inactive',
              'on_sale'      => 'On Sale',
              'pending'      => 'Pending',
            ];
          @endphp
          @foreach($statusOptions as $val=>$label)
            <option value="{{ $val }}" @selected(request('status')===$val)>{{ $label }}</option>
          @endforeach
        </select>
      </div>

      <div class="md:col-span-2">
        <select name="sort" class="input-light w-full">
          @php
            $sorts = [
              'name_asc'     => 'Name A–Z',
              'name_desc'    => 'Name Z–A',
              'cost_desc'    => 'Unit Cost High → Low',
              'cost_asc'     => 'Unit Cost Low → High',
              'updated_desc' => 'Recently Updated',
            ];
          @endphp
          @foreach($sorts as $val=>$label)
            <option value="{{ $val }}" @selected(request('sort')===$val)>{{ $label }}</option>
          @endforeach
        </select>
      </div>
    </div>
    <div class="mt-3 flex justify-end gap-2">
      <a href="{{ route('products.index') }}" class="btn btn-ghost text-sm">Reset</a>
      <button class="btn btn-primary text-sm">Apply</button>
    </div>
  </form>

  {{-- Table --}}
  <div class="overflow-x-auto rounded-2xl border border-gray-200 bg-white/80 backdrop-blur">
    <table class="w-full text-left rounded-2xl overflow-hidden">
      <thead class="text-xs uppercase">
        <tr>
          <th class="py-3 px-4 border-b">Product</th>
          <th class="py-3 px-4 border-b">Avail (Pack / Bag)</th>
          <th class="py-3 px-4 border-b">Latest Batch</th>
          <th class="py-3 px-4 border-b">Prices</th>
          <th class="py-3 px-4 border-b">Variant</th>
          <th class="py-3 px-4 border-b w-[20rem] text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200">
        @forelse($products as $p)
          @php
            $img  = $p->image_url ?? '/images/default-product.png';
            $name = $p->product_name ?? $p->name ?? '—';

            // latest production snapshot
            $latestBatchNo = $p->latest_batch_number ?? null;
            $prodDateStr   = $p->latest_production_date ?? null;
            $expDateStr    = $p->latest_expiration_date ?? null;

            $prodDateVis = $prodDateStr ? \Carbon\Carbon::parse($prodDateStr)->format('Y-m-d') : '—';
            $expDateVis  = $expDateStr  ? \Carbon\Carbon::parse($expDateStr)->format('Y-m-d')  : '—';

            // prices
            $pricePack = $p->latest_unit_price_pack ?? null;
            $priceBag  = $p->latest_unit_price_bag  ?? null;

            // availability
            $latestAvailPack = $p->latest_available_pack ?? null;
            $latestAvailBag  = $p->latest_available_bag  ?? null;

            $totalAvailPack = $p->total_available_pack
              ?? (int) \App\Models\Production::where('product_id', $p->id)->sum('available_pack');
            $totalAvailBag  = $p->total_available_bag
              ?? (int) \App\Models\Production::where('product_id', $p->id)->sum('available_bag');

            // status
            $rawStatus = strtolower($p->stock_status ?? $p->status ?? 'in_stock');
            $chipClass = match($rawStatus){
              'in_stock'     => 'chip chip--in',
              'low_stock'    => 'chip chip--low',
              'on_sale'      => 'chip chip--sale',
              'pending'      => 'chip chip--pending',
              'inactive'     => 'chip chip--inactive',
              'out_of_stock' => 'chip chip--out',
              default        => 'chip'
            };
            $statusLabel = ucwords(str_replace('_',' ', $rawStatus));

            // variant
            $variant = $p->variant ?? $p->category ?? 'Standard';
          @endphp

          <tr class="transition-all">
            {{-- Product --}}
            <td class="py-3 px-4">
              <div class="flex items-center gap-3">
                <img src="{{ $img }}" alt="{{ $name }}" class="w-9 h-9 rounded-lg object-cover border border-gray-200">
                <div class="flex flex-col">
                  <span class="font-semibold text-sm">{{ $name }}</span>
                  <span class="{{ $chipClass }} mt-1 w-max">{{ $statusLabel }}</span>
                </div>
              </div>
            </td>

            {{-- Avail (Pack/Bag) – redesigned --}}
            <td class="py-3 px-4">
              <div class="avail-grid">
                {{-- Latest --}}
                <div class="avail-card">
                  <div class="avail-label">
                    <span>Latest</span>
                    <span>#{{ $latestBatchNo ?: '—' }}</span>
                  </div>
                  <div class="avail-lines">
                    <div class="avail-line">
                      <span class="avail-dot avail-dot-pack"></span>
                      <span class="muted text-[0.7rem]">Pack</span>
                      <span class="avail-pill avail-pill-pack">
                        {{ !is_null($latestAvailPack) ? number_format((int)$latestAvailPack) : '—' }}
                      </span>
                    </div>
                    <div class="avail-line">
                      <span class="avail-dot avail-dot-bag"></span>
                      <span class="muted text-[0.7rem]">Bag</span>
                      <span class="avail-pill avail-pill-bag">
                        {{ !is_null($latestAvailBag) ? number_format((int)$latestAvailBag) : '—' }}
                      </span>
                    </div>
                  </div>
                </div>

                {{-- Total --}}
                <div class="avail-card">
                  <div class="avail-label">
                    <span>Total</span>
                    <span class="muted">All batches</span>
                  </div>
                  <div class="avail-lines">
                    <div class="avail-line">
                      <span class="avail-dot avail-dot-pack"></span>
                      <span class="muted text-[0.7rem]">Pack</span>
                      <span class="avail-pill avail-pill-pack">
                        {{ number_format((int)$totalAvailPack) }}
                      </span>
                    </div>
                    <div class="avail-line">
                      <span class="avail-dot avail-dot-bag"></span>
                      <span class="muted text-[0.7rem]">Bag</span>
                      <span class="avail-pill avail-pill-bag">
                        {{ number_format((int)$totalAvailBag) }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </td>

            {{-- Latest Batch – improved card --}}
            <td class="py-3 px-4">
              <div class="batch-card">
                <div class="batch-tag">
                  <span class="batch-dot"></span>
                  <span>Batch {{ $latestBatchNo ?: 'N/A' }}</span>
                </div>
                <div class="batch-dates text-[0.72rem]">
                  <span>Prod: <strong>{{ $prodDateVis }}</strong></span>
                  <span>Exp: <strong>{{ $expDateVis }}</strong></span>
                </div>
              </div>
            </td>

            {{-- Prices (Pack & Bag own rows/cards) --}}
            <td class="py-3 px-4">
              @if(!is_null($pricePack) || !is_null($priceBag))
                <div class="price-grid">
                  <div class="price-card price-card-pack">
                    <span class="price-label">Pack Price</span>
                    <span>
                      {{ is_null($pricePack) ? '—' : '₱'.number_format((float)$pricePack,2) }}
                    </span>
                  </div>
                  <div class="price-card price-card-bag">
                    <span class="price-label">Bag Price</span>
                    <span>
                      {{ is_null($priceBag) ? '—' : '₱'.number_format((float)$priceBag,2) }}
                    </span>
                  </div>
                </div>
              @else
                <span class="text-xs text-gray-500">—</span>
              @endif
            </td>

            {{-- Variant --}}
            <td class="py-3 px-4 text-sm">
              {{ $variant }}
            </td>

            {{-- Actions (vertical) --}}
            <td class="py-3 px-4 text-right">
              <div class="actions-bar">
                <a href="{{ route('products.materials.index', $p) }}" class="action-pill action-pill--materials">
                  <span class="icon">🧂</span>
                  <span>Materials</span>
                </a>
                <a href="{{ route('production.orders', $p->id) }}" class="action-pill action-pill--production">
                  <span class="icon">🏭</span>
                  <span>Production</span>
                </a>
                @if(Route::has('sales.index'))
                  <a href="{{ route('sales.index', ['product_id' => $p->id]) }}" class="action-pill action-pill--sales">
                    <span class="icon">💰</span>
                    <span>Sales</span>
                  </a>
                @endif
                @if(Route::has('products.edit'))
                  <a href="{{ route('products.edit', $p) }}" class="action-pill action-pill--edit">
                    <span class="icon">✏️</span>
                    <span>Edit</span>
                  </a>
                @endif
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="py-10 text-center text-gray-600">
              No products yet. Add one with the button above.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Pagination --}}
  @if(method_exists($products, 'links'))
    <div class="mt-4 flex items-center justify-between flex-wrap gap-3">
      <p class="text-xs text-gray-600">
        Showing
        <span class="font-semibold text-gray-900">
          {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? ($products->count() ?: 0) }}
        </span>
        of
        <span class="font-semibold text-gray-900">{{ $products->total() ?? $products->count() }}</span>
      </p>
      <div>{{ $products->appends(request()->query())->links() }}</div>
    </div>
  @endif
</div>
@endsection
