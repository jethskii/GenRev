@extends('layout.mainlayout')

@section('styles')
<style>
  /* --- Light theme utilities to match Sales/Production --- */
  .page-wrap { background:#f7f8fb; }
  .light-card{ background:#fff; border:1px solid #e5e7eb; border-radius:16px; box-shadow:0 8px 18px rgba(17,24,39,.04); }
  .input-light{
    background:#fff; border:1px solid #e5e7eb; color:#111827; border-radius:10px;
    padding:.625rem .75rem; line-height:1.4;
  }
  .input-light::placeholder{ color:#9ca3af; }
  .input-light:focus{ outline:0; border-color:#93c5fd; box-shadow:0 0 0 2px rgba(59,130,246,.25); }

  /* chips (status) */
  .chip{ display:inline-block; padding:.28rem .65rem; border-radius:999px; font-weight:700; font-size:.72rem; border:1px solid #e5e7eb; }
  .chip--active{ background:#ecfdf5; border-color:#a7f3d0; color:#065f46; }
  .chip--inactive{ background:#fef2f2; border-color:#fecaca; color:#991b1b; }
  .chip--sale{ background:#eff6ff; border-color:#bfdbfe; color:#1d4ed8; }
  .chip--pending{ background:#fffbeb; border-color:#fde68a; color:#92400e; }

  /* table */
  table{ border-collapse:separate; border-spacing:0; }
  thead th{ background:#f9fafb; color:#374151; font-weight:800; }
  tbody td{ color:#111827; }
  tbody tr:nth-child(even){ background:#fafafa; }
  tbody tr:hover{ background:#f3f4f6; }
  th, td{ border-color:#e5e7eb!important }

  /* links to sections (secondary accent) */
  .link-accent-blue{ color:#1d4ed8; font-weight:600; }
  .link-accent-blue:hover{ text-decoration:underline; }
  .link-accent-green{ color:#047857; font-weight:600; }
  .link-accent-green:hover{ text-decoration:underline; }
</style>
@endsection

@section('content')
<div class="page-wrap text-gray-900 p-6 rounded-2xl light-card">

  {{-- Header + actions --}}
  <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 mb-6">
    <div>
      <h2 class="text-2xl font-bold tracking-wide">Products</h2>
      <p class="text-sm text-gray-500">Admin view · master list connected to Recipes, Inventory, and Production</p>
    </div>

    <div class="flex items-center gap-2">
      <a href="{{ route('materials.index') }}" class="text-sm link-accent-green">Global Materials</a>

      {{-- Quick add (name only) --}}
      <form action="{{ route('products.quick-store') }}" method="POST" class="hidden md:flex gap-2">
        @csrf
        <input type="text" name="product_name" class="input-light w-56" placeholder="Quick add product…" required>
        {{-- Primary = RED as per theme --}}
        <button class="btn btn-primary">Add</button>
      </form>

      {{-- Full form --}}
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
      <div class="md:col-span-4">
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
          @php $statusOptions = ['active'=>'Active','inactive'=>'Inactive','on_sale'=>'On Sale','pending'=>'Pending']; @endphp
          @foreach($statusOptions as $val=>$label)
            <option value="{{ $val }}" @selected(request('status')===$val)>{{ $label }}</option>
          @endforeach
        </select>
      </div>

      <div class="md:col-span-2">
        <select name="sort" class="input-light w-full">
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
        <select name="per_page" class="input-light w-full">
          @foreach([10,25,50,100] as $pp)
            <option value="{{ $pp }}" @selected((int)request('per_page',10)===$pp)>{{ $pp }}/pg</option>
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
  <div class="overflow-x-auto rounded-2xl border border-gray-200 bg-white">
    <table class="w-full text-sm text-left rounded-2xl overflow-hidden">
      <thead class="text-xs uppercase">
        <tr>
          <th class="py-3 px-4 border-b">Product</th>
          <th class="py-3 px-4 border-b">Code</th>
          <th class="py-3 px-4 border-b text-right">Unit Cost</th>
          <th class="py-3 px-4 border-b text-right">Stock</th>
          <th class="py-3 px-4 border-b">Category</th>
          <th class="py-3 px-4 border-b">Status</th>
          <th class="py-3 px-4 border-b w-72 text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200">
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

          <tr class="hover:bg-gray-50 transition-colors">
            <td class="py-3 px-4">
              <div class="flex items-center gap-3">
                <img src="{{ $img }}" alt="{{ $name }}" class="w-9 h-9 rounded-lg object-cover border border-gray-200">
                <div class="flex flex-col">
                  <span class="font-semibold">{{ $name }}</span>
                  @if($p->product_code)
                    <span class="text-xs text-gray-500">#{{ $p->product_code }}</span>
                  @endif
                </div>
              </div>
            </td>

            <td class="py-3 px-4 text-gray-700">#{{ $code }}</td>

            <td class="py-3 px-4 text-right">
              {{ is_null($unitCost) ? '—' : '₱'.$unitCost }}
            </td>

            <td class="py-3 px-4 text-right">
              {{ $stock }} <span class="text-gray-500">{{ $unit }}</span>
            </td>

            <td class="py-3 px-4">
              <span class="text-sm">{{ $category }}</span>
            </td>

            <td class="py-3 px-4">
              <span class="{{ $chip }}">{{ ucfirst($status) }}</span>
            </td>

            <td class="py-3 px-4 text-right space-x-4">
              <a href="{{ route('products.materials.index', $p) }}" class="link-accent-blue">Manage Materials</a>
              <a href="{{ route('production.orders', $p->id) }}" class="link-accent-green">Go to Production</a>
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
