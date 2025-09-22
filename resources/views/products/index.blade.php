@extends('layout.mainlayout')

@section('styles')
<style>
  :root{
    /* Accessible dark palette */
    --bg:#0f1a12;                /* page background */
    --panel:#142017;             /* cards / panels */
    --panel-2:#17251b;           /* alt row / zebra */
    --line:rgba(255,255,255,.14);
    --line-subtle:rgba(255,255,255,.08);

    --text:#f7f9f8;              /* primary text */
    --text-muted:#c9d4ce;        /* body/subtitles */
    --text-dim:#9fb2a8;          /* helper text */
    --accent:#b7ff5f;            /* neon green */
    --accent-ink:#0a190f;        /* fg on accent */
    --accent-soft:rgba(183,255,95,.12);

    --danger:#ff6b6b;            --danger-soft:rgba(255,107,107,.15);
    --blue:#7cc7ff;              --blue-soft:rgba(124,199,255,.15);
    --amber:#ffd166;             --amber-soft:rgba(255,209,102,.18);
    --gray-chip:#a7b2ad;         --gray-soft:rgba(167,178,173,.16);
  }

  .page-wrap{background:var(--bg)}
  .glass{background:var(--panel); border:1px solid var(--line); box-shadow:0 8px 28px rgba(0,0,0,.35); border-radius:16px}
  .bg-dark-bg{background:var(--panel)}
  .text-white{color:var(--text)!important}
  .muted{color:var(--text-muted)}
  .dim{color:var(--text-dim)}
  .border-dark-line{border-color:var(--line)!important}
  .text-armygreen{color:var(--accent)}
  .text-armygreen:hover{text-decoration:underline}
  .input-dark{
    background:#0f1a12; color:var(--text);
    border:1px solid var(--line); border-radius:10px;
    padding:.625rem .75rem; line-height:1.4;
  }
  .input-dark::placeholder{color:var(--text-dim)}
  .input-dark:focus{
    outline:0; border-color:var(--accent);
    box-shadow:0 0 0 4px var(--accent-soft);
  }
  select.input-dark option{color:#111}
  .btn-armygreen{
    background:var(--accent); color:var(--accent-ink);
    padding:.55rem .9rem; border-radius:10px; font-weight:600;
    border:1px solid #c7ff8c;
  }
  .btn-armygreen:hover{filter:brightness(0.96)}
  .btn-outline{
    color:var(--text); border:1px solid var(--line); border-radius:10px; padding:.55rem .9rem;
  }
  .btn-outline:hover{background:var(--panel-2)}
  /* table */
  table{border-collapse:separate; border-spacing:0}
  thead.bg-sidebar{background:#0e1a12}
  tbody tr:nth-child(odd){background:transparent}
  tbody tr:nth-child(even){background:var(--panel-2)}
  tbody tr:hover{background:rgba(183,255,95,.05)}
  th, td{border-color:var(--line-subtle)!important}
  /* status chips */
  .chip{display:inline-block; padding:.25rem .65rem; border-radius:999px; font-weight:600; font-size:.72rem}
  .chip--active{background:rgba(80,200,120,.18); color:#a7f3c6; border:1px solid rgba(80,200,120,.28)}
  .chip--inactive{background:var(--danger-soft); color:#ffb3b3; border:1px solid rgba(255,107,107,.28)}
  .chip--sale{background:var(--blue-soft); color:#cfeaff; border:1px solid rgba(124,199,255,.28)}
  .chip--pending{background:var(--amber-soft); color:#ffe4ad; border:1px solid rgba(255,209,102,.28)}
</style>
@endsection

@section('content')
<div class="page-wrap text-white border border-dark-line p-6 rounded-2xl glass">

  {{-- Header + actions --}}
  <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 mb-6">
    <div>
      <h2 class="text-2xl font-semibold tracking-wide">Products</h2>
      <p class="text-sm dim">Admin view · master list connected to Recipes, Inventory, and Production</p>
    </div>

    <div class="flex items-center gap-2">
      <a href="{{ route('materials.index') }}" class="text-sm text-armygreen underline">Global Materials</a>

      {{-- Quick add (name only) --}}
      <form action="{{ route('products.quick-store') }}" method="POST" class="hidden md:flex gap-2">
        @csrf
        <input type="text" name="product_name" class="input-dark w-56" placeholder="Quick add product…" required>
        <button class="btn-armygreen">Add</button>
      </form>

      {{-- Full form --}}
      @if(Route::has('products.create'))
        <a href="{{ route('products.create') }}" class="btn-armygreen whitespace-nowrap">+ Add New Product</a>
      @endif
    </div>
  </div>

  {{-- Flashes --}}
  @if(session('success'))
    <div class="mb-4 text-green-300 text-sm">{{ session('success') }}</div>
  @endif
  @if($errors->any())
    <div class="mb-4 text-red-300 text-sm">Please fix the errors and try again.</div>
  @endif

  {{-- Filters --}}
  <form method="GET" action="{{ route('products.index') }}" class="glass mb-4 px-4 py-3 rounded-xl border border-dark-line">
    <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
      <div class="md:col-span-4">
        <input type="text" name="search" value="{{ request('search') }}" class="input-dark w-full"
               placeholder="Search by name or code…">
      </div>

      <div class="md:col-span-3">
        <select name="category" class="input-dark w-full">
          <option value="">All categories</option>
          @foreach(($categories ?? []) as $cat)
            <option value="{{ $cat }}" @selected(request('category')===$cat)>{{ $cat }}</option>
          @endforeach
        </select>
      </div>

      <div class="md:col-span-2">
        <select name="status" class="input-dark w-full">
          <option value="">All status</option>
          @php $statusOptions = ['active'=>'Active','inactive'=>'Inactive','on_sale'=>'On Sale','pending'=>'Pending']; @endphp
          @foreach($statusOptions as $val=>$label)
            <option value="{{ $val }}" @selected(request('status')===$val)>{{ $label }}</option>
          @endforeach
        </select>
      </div>

      <div class="md:col-span-2">
        <select name="sort" class="input-dark w-full">
          @php
            $sorts = [
              'name_asc'   => 'Name A–Z',
              'name_desc'  => 'Name Z–A',
              'stock_desc' => 'Stock High → Low',
              'stock_asc'  => 'Stock Low → High',
              'cost_desc'  => 'Unit Cost High → Low',
              'cost_asc'   => 'Unit Cost Low → High',
              'updated_desc' => 'Recently Updated',
            ];
          @endphp
          @foreach($sorts as $val=>$label)
            <option value="{{ $val }}" @selected(request('sort')===$val)>{{ $label }}</option>
          @endforeach
        </select>
      </div>

      <div class="md:col-span-1">
        <select name="per_page" class="input-dark w-full">
          @foreach([10,25,50,100] as $pp)
            <option value="{{ $pp }}" @selected((int)request('per_page',10)===$pp)>{{ $pp }}/pg</option>
          @endforeach
        </select>
      </div>
    </div>
    <div class="mt-3 flex justify-end gap-2">
      <a href="{{ route('products.index') }}" class="btn-outline text-sm">Reset</a>
      <button class="btn-armygreen">Apply</button>
    </div>
  </form>

  {{-- Table --}}
  <div class="overflow-x-auto rounded-2xl border border-dark-line">
    <table class="w-full text-sm text-left bg-dark-bg rounded-2xl overflow-hidden">
      <thead class="bg-sidebar text-white text-xs uppercase">
        <tr>
          <th class="py-3 px-4 border-b border-dark-line">Product</th>
          <th class="py-3 px-4 border-b border-dark-line">Code</th>
          <th class="py-3 px-4 border-b border-dark-line text-right">Unit Cost</th>
          <th class="py-3 px-4 border-b border-dark-line text-right">Stock</th>
          <th class="py-3 px-4 border-b border-dark-line">Category</th>
          <th class="py-3 px-4 border-b border-dark-line">Status</th>
          <th class="py-3 px-4 border-b border-dark-line w-72 text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y" style="border-color:var(--line-subtle)">
        @forelse($products as $p)
          @php
            $img  = $p->image_url ?? '/images/default-burger.png';
            $name = $p->product_name ?? $p->name ?? '—';
            $code = $p->product_code ?? $p->code ?? '—';
            $unitCost = is_null($p->unit_cost ?? null) ? null : number_format((float)$p->unit_cost, 2);
            $stock = number_format((float)($p->quantity ?? 0), 2);
            $unit = $p->unit ?? 'pcs';
            $category = $p->category ?? 'Uncategorized';
            $status = strtolower($p->status ?? ((float)($p->quantity ?? 0) > 0 ? 'active' : 'inactive'));
            $chip = match($status){
              'active'   => 'chip chip--active',
              'inactive' => 'chip chip--inactive',
              'on_sale'  => 'chip chip--sale',
              'pending'  => 'chip chip--pending',
              default    => 'chip',
            };
          @endphp

          <tr>
            <td class="py-3 px-4">
              <div class="flex items-center gap-3">
                <img src="{{ $img }}" alt="{{ $name }}" class="w-9 h-9 rounded-lg object-cover border" style="border-color:var(--line-subtle)">
                <div class="flex flex-col">
                  <span class="font-medium text-white">{{ $name }}</span>
                  @if($p->product_code)
                    <span class="text-xs dim">#{{ $p->product_code }}</span>
                  @endif
                </div>
              </div>
            </td>

            <td class="py-3 px-4 dim">#{{ $code }}</td>

            <td class="py-3 px-4 text-right">
              {{ is_null($unitCost) ? '—' : '₱'.$unitCost }}
            </td>

            <td class="py-3 px-4 text-right">
              {{ $stock }} <span class="dim">{{ $unit }}</span>
            </td>

            <td class="py-3 px-4">
              <span class="text-sm text-white">{{ $category }}</span>
            </td>

            <td class="py-3 px-4">
              <span class="{{ $chip }}">{{ ucfirst($status) }}</span>
            </td>

            <td class="py-3 px-4 text-right space-x-4">
              <a href="{{ route('products.materials.index', $p) }}" class="text-armygreen">Manage Materials</a>
              <a href="{{ route('production.orders', $p->id) }}" class="text-armygreen">Go to Production</a>
              @if(Route::has('products.edit'))
                <a href="{{ route('products.edit', $p) }}" class="text-armygreen">Edit</a>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="py-10 text-center dim">
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
      <p class="text-xs dim">
        Showing
        <span class="font-semibold text-white">
          {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? ($products->count() ?: 0) }}
        </span>
        of
        <span class="font-semibold text-white">{{ $products->total() ?? $products->count() }}</span>
      </p>
      <div>{{ $products->appends(request()->query())->links() }}</div>
    </div>
  @endif
</div>
@endsection
