@extends('layout.mainlayout')

@section('content')
<div class="bg-dark-bg text-white border border-dark-line p-6 rounded-2xl shadow-md">

  {{-- Header + actions --}}
  <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 mb-6">
    <div>
      <h2 class="text-2xl font-bold tracking-wide">Products</h2>
      <p class="text-sm text-[var(--muted,#A3B4A7)]">Admin view • master list connected to Recipes, Inventory, and Production</p>
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
    <div class="mb-4 text-green-400 text-sm">{{ session('success') }}</div>
  @endif
  @if($errors->any())
    <div class="mb-4 text-red-400 text-sm">Please fix the errors and try again.</div>
  @endif

  {{-- Filters / search / per-page / sort --}}
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
              'stock_desc' => 'Stock High→Low',
              'stock_asc'  => 'Stock Low→High',
              'cost_desc'  => 'Unit Cost High→Low',
              'cost_asc'   => 'Unit Cost Low→High',
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
      <a href="{{ route('products.index') }}" class="px-3 py-2 text-sm border border-dark-line rounded-lg hover:bg-sidebar-hover">Reset</a>
      <button class="btn-armygreen">Apply</button>
    </div>
  </form>

  {{-- Table --}}
  <div class="overflow-x-auto rounded-2xl border border-dark-line">
    <table class="w-full text-sm text-left bg-dark-bg rounded-2xl overflow-hidden border-collapse">
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
      <tbody class="text-gray-100 divide-y divide-dark-line">
        @forelse($products as $p)
          @php
            $img  = $p->image_url ?? '/images/default-burger.png';
            $name = $p->product_name ?? $p->name ?? '—';
            $code = $p->product_code ?? $p->code ?? '—';
            $unitCost = is_null($p->unit_cost ?? null) ? null : number_format((float)$p->unit_cost, 2);
            $stock = number_format((float)($p->quantity ?? 0), 2);
            $unit = $p->unit ?? 'pcs';
            $category = $p->category ?? 'Uncategorized';
            $status = $p->status ?? ((float)($p->quantity ?? 0) > 0 ? 'active' : 'inactive');
            $badgeMap = [
              'active'   => 'bg-green-500/20 text-green-300',
              'inactive' => 'bg-red-500/20 text-red-300',
              'on_sale'  => 'bg-blue-500/20 text-blue-300',
              'pending'  => 'bg-amber-500/20 text-amber-300',
              'bouncing' => 'bg-purple-500/20 text-purple-300',
            ];
            $badgeClass = $badgeMap[strtolower($status)] ?? 'bg-gray-500/20 text-gray-300';
          @endphp

          <tr class="hover:bg-sidebar-hover transition">
            <td class="py-3 px-4">
              <div class="flex items-center gap-3">
                <img src="{{ $img }}" alt="{{ $name }}" class="w-9 h-9 rounded-lg object-cover border border-white/10">
                <div class="flex flex-col">
                  <span class="font-medium">{{ $name }}</span>
                </div>
              </div>
            </td>

            <td class="py-3 px-4 text-[var(--muted,#A3B4A7)]">#{{ $code }}</td>

            <td class="py-3 px-4 text-right">
              {{ is_null($unitCost) ? '—' : '₱'.$unitCost }}
            </td>

            <td class="py-3 px-4 text-right">
              {{ $stock }} <span class="text-[var(--muted,#A3B4A7)]">{{ $unit }}</span>
            </td>

            <td class="py-3 px-4">
              <span class="text-sm">{{ $category }}</span>
            </td>

            <td class="py-3 px-4">
              <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $badgeClass }}">
                {{ ucfirst($status) }}
              </span>
            </td>

            <td class="py-3 px-4 text-right space-x-4">
              {{-- Manage recipe/BOM --}}
              <a href="{{ route('products.materials.index', $p) }}" class="text-armygreen hover:underline">Manage Materials</a>

              {{-- Go to Production (batches) for this product --}}
              <a href="{{ route('production.orders', $p->id) }}" class="text-armygreen hover:underline">Go to Production</a>

              {{-- Edit --}}
              @if(Route::has('products.edit'))
                <a href="{{ route('products.edit', $p) }}" class="text-armygreen hover:underline">Edit</a>
              @endif>

              {{-- More (optional dropdown placeholder) --}}
              {{-- <x-dropdown> … </x-dropdown> --}}
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="py-10 text-center text-gray-400">
              No products yet. Add one with the button above.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Pagination (only if using paginate() in controller) --}}
  @if(method_exists($products, 'links'))
    <div class="mt-4 flex items-center justify-between">
      <p class="text-xs text-[var(--muted,#A3B4A7)]">
        Showing
        <span class="font-semibold text-white">
          {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? ($products->count() ?: 0) }}
        </span>
        of
        <span class="font-semibold text-white">{{ $products->total() ?? $products->count() }}</span>
      </p>
      <div class="">{{ $products->appends(request()->query())->links() }}</div>
    </div>
  @endif
</div>
@endsection
