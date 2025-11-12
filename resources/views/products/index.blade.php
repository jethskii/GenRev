@extends('layout.mainlayout')

@section('styles')
<style>
  /* ===== Glassy Light Theme ===== */
  :root{
    --glass-bg: rgba(255,255,255,.65);
    --glass-brd: rgba(229,231,235,.65);
    --shadow: 0 10px 30px rgba(17,24,39,.10);
    --ink:#0f172a; --muted:#6b7280;

    /* accents */
    --chip-green-bg:#ecfdf5; --chip-green-brd:#a7f3d0; --chip-green-ink:#065f46;
    --chip-yellow-bg:#fffbeb; --chip-yellow-brd:#fde68a; --chip-yellow-ink:#92400e;
    --chip-red-bg:#fef2f2;    --chip-red-brd:#fecaca;   --chip-red-ink:#991b1b;
    --chip-blue-bg:#eff6ff;   --chip-blue-brd:#bfdbfe;  --chip-blue-ink:#1d4ed8;
    --chip-gray-bg:#f3f4f6;   --chip-gray-brd:#e5e7eb;  --chip-gray-ink:#374151;

    /* availability badges */
    --pack-bg: #fff7cc;      /* soft yellow */
    --pack-brd:#ffe066;
    --pack-glow: 0 0 22px rgba(255, 208, 0, .35);
    --pack-ink:#7a5b00;

    --bag-bg:#ffe2e2;        /* soft red */
    --bag-brd:#ffb3b3;
    --bag-glow: 0 0 22px rgba(255, 71, 71, .35);
    --bag-ink:#7a1b1b;
  }
  .page-wrap { background:#f7f8fb; }
  .light-card{
    background:var(--glass-bg);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border:1px solid var(--glass-brd);
    border-radius:16px;
    box-shadow:var(--shadow);
  }
  .input-light{
    background:#fff;border:1px solid #e5e7eb;color:#111827;border-radius:10px;
    padding:.625rem .75rem;line-height:1.4;
  }
  .input-light::placeholder{color:#9ca3af}
  .input-light:focus{outline:0;border-color:#93c5fd;box-shadow:0 0 0 2px rgba(59,130,246,.25)}

  /* chips (status) */
  .chip{display:inline-block;padding:.28rem .65rem;border-radius:999px;font-weight:700;font-size:.72rem;border:1px solid #e5e7eb}
  .chip--in{background:var(--chip-green-bg);border-color:var(--chip-green-brd);color:var(--chip-green-ink)}
  .chip--low{background:var(--chip-yellow-bg);border-color:var(--chip-yellow-brd);color:var(--chip-yellow-ink)}
  .chip--out{background:var(--chip-red-bg);border-color:var(--chip-red-brd);color:var(--chip-red-ink)}
  .chip--sale{background:var(--chip-blue-bg);border-color:var(--chip-blue-brd);color:var(--chip-blue-ink)}
  .chip--pending{background:#fff7ed;border-color:#fed7aa;color:#9a3412}
  .chip--inactive{background:var(--chip-gray-bg);border-color:var(--chip-gray-brd);color:var(--chip-gray-ink)}

  /* table */
  table{border-collapse:separate;border-spacing:0}
  thead th{background:rgba(249,250,251,.8);color:#374151;font-weight:800;backdrop-filter: blur(6px)}
  tbody td{color:#111827}
  tbody tr:nth-child(even){background:rgba(250,250,250,.65)}
  tbody tr:hover{background:rgba(243,244,246,.75)}
  th,td{border-color:#e5e7eb!important}

  /* links */
  .link-accent-blue{color:#1d4ed8;font-weight:600}
  .link-accent-blue:hover{text-decoration:underline}
  .link-accent-green{color:#047857;font-weight:600}
  .link-accent-green:hover{text-decoration:underline}

  /* availability badges */
  .badge{
    display:inline-flex;align-items:center;gap:.25rem;
    padding:.2rem .55rem;border-radius:999px;border:1px solid;
    font-size:.72rem;font-weight:700;white-space:nowrap;
  }
  .badge-pack{background:var(--pack-bg);border-color:var(--pack-brd);color:var(--pack-ink);box-shadow:var(--pack-glow)}
  .badge-bag{background:var(--bag-bg); border-color:var(--bag-brd); color:var(--bag-ink); box-shadow:var(--bag-glow)}
  .muted{color:#6b7280}
</style>
@endsection

@section('content')
<div class="page-wrap text-gray-900 p-6 rounded-2xl light-card">

  {{-- Header + actions --}}
  <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 mb-6">
    <div>
      <h2 class="text-2xl font-bold tracking-wide">Products</h2>
      <p class="text-sm text-gray-500">Master list connected to Recipes, Inventory, and Production</p>
    </div>

    <div class="flex items-center gap-2">
      <a href="{{ route('materials.index') }}" class="text-sm link-accent-green">Global Materials</a>

      {{-- Quick add (name only) --}}
      <form action="{{ route('products.quick-store') }}" method="POST" class="hidden md:flex gap-2">
        @csrf
        <input type="text" name="product_name" class="input-light w-56" placeholder="Quick add product…" required>
        <button class="btn btn-primary">Add</button>
      </form>

      @if(Route::has('products.create'))
        <a href="{{ route('products.create') }}" class="btn btn-primary whitespace-nowrap">+ Add New Product</a>
      @endif
    </div>
  </div>

  {{-- Flashes --}}
  @if(session('success'))
    <div class="mb-4 text-green-700 bg-green-50 border border-green-200 px-3 py-2 rounded-lg text-sm">{{ session('success') }}</div>
  @endif
  @if($errors->any())
    <div class="mb-4 text-rose-700 bg-rose-50 border border-rose-200 px-3 py-2 rounded-lg text-sm">Please fix the errors and try again.</div>
  @endif

  {{-- Filters --}}
  <form method="GET" action="{{ route('products.index') }}" class="light-card mb-4 px-4 py-3 rounded-xl">
    <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
      <div class="md:col-span-5">
        <input type="text" name="search" value="{{ request('search') }}" class="input-light w-full"
               placeholder="Search by name or code…">
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
      <button class="btn btn-primary">Apply</button>
    </div>
  </form>

  {{-- Table --}}
  <div class="overflow-x-auto rounded-2xl border border-gray-200 bg-white/70 backdrop-blur">
    <table class="w-full text-sm text-left rounded-2xl overflow-hidden">
      <thead class="text-xs uppercase">
        <tr>
          <th class="py-3 px-4 border-b">Product</th>
          <th class="py-3 px-4 border-b text-right">Unit Cost</th>
          <th class="py-3 px-4 border-b">Avail (Pack / Bag)</th>
          <th class="py-3 px-4 border-b">Latest Batch</th>
          <th class="py-3 px-4 border-b">Prices</th>
          <th class="py-3 px-4 border-b">Category</th>
          <th class="py-3 px-4 border-b w-[26rem] text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200">
        @forelse($products as $p)
          @php
            $img  = $p->image_url ?? '/images/default-product.png';
            $name = $p->product_name ?? $p->name ?? '—';

            $unitCost = is_null($p->unit_cost ?? null) ? null : number_format((float)$p->unit_cost, 2);

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
          @endphp

          <tr class="hover:bg-gray-50/70 transition-colors">
            {{-- Product --}}
            <td class="py-3 px-4">
              <div class="flex items-center gap-3">
                <img src="{{ $img }}" alt="{{ $name }}" class="w-9 h-9 rounded-lg object-cover border border-gray-200">
                <div class="flex flex-col">
                  <span class="font-semibold">{{ $name }}</span>
                  <span class="{{ $chipClass }} mt-1 w-max">{{ $statusLabel }}</span>
                </div>
              </div>
            </td>

            {{-- Unit Cost --}}
            <td class="py-3 px-4 text-right">
              {{ is_null($unitCost) ? '—' : '₱'.$unitCost }}
            </td>

            {{-- Avail (Pack/Bag) --}}
            <td class="py-3 px-4">
              <div class="flex flex-col gap-1 text-xs">
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="muted">Latest:</span>
                  <span class="badge badge-pack">
                    {{ !is_null($latestAvailPack) ? number_format((int)$latestAvailPack) : '—' }} pack
                  </span>
                  <span class="badge badge-bag">
                    {{ !is_null($latestAvailBag) ? number_format((int)$latestAvailBag) : '—' }} bag
                  </span>
                </div>
                <div class="flex items-center gap-2 flex-wrap muted">
                  <span>Total:</span>
                  <span class="badge badge-pack">{{ number_format((int)$totalAvailPack) }} pack</span>
                  <span class="badge badge-bag">{{ number_format((int)$totalAvailBag) }} bag</span>
                </div>
              </div>
            </td>

            {{-- Latest Batch --}}
            <td class="py-3 px-4">
              <div class="flex flex-col">
                <span class="font-medium">{{ $latestBatchNo ?: '—' }}</span>
                <span class="text-xs muted">
                  Prod: {{ $prodDateVis }} · Exp: {{ $expDateVis }}
                </span>
              </div>
            </td>

            {{-- Prices (from latest batch) --}}
            <td class="py-3 px-4">
              @if(!is_null($pricePack) || !is_null($priceBag))
                <div class="flex flex-col text-xs">
                  <span>Pack: {{ is_null($pricePack) ? '—' : '₱'.number_format((float)$pricePack,2) }}</span>
                  <span>Bag:  {{ is_null($priceBag)  ? '—' : '₱'.number_format((float)$priceBag,2)  }}</span>
                </div>
              @else
                <span class="text-xs text-gray-500">—</span>
              @endif
            </td>

            {{-- Category --}}
            <td class="py-3 px-4">
              <span class="text-sm">{{ $p->category ?? 'Uncategorized' }}</span>
            </td>

            {{-- Actions --}}
            <td class="py-3 px-4 text-right flex flex-wrap gap-3 justify-end">
              <a href="{{ route('products.materials.index', $p) }}" class="link-accent-blue">Manage Materials</a>
              <a href="{{ route('production.orders', $p->id) }}" class="link-accent-green">Go to Production</a>
              @if(Route::has('sales.index'))
                <a href="{{ route('sales.index', ['product_id' => $p->id]) }}" class="link-accent-blue">View Sales</a>
              @endif
              @if(Route::has('products.edit'))
                <a href="{{ route('products.edit', $p) }}" class="link-accent-blue">Edit</a>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="py-10 text-center text-gray-600">
              No products yet. Add one with the button above.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Pagination --}}
  @if(method_exists($products, 'links'))
    <div class="mt-4 flex items-center justify-between">
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
