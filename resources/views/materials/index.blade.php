{{-- resources/views/materials/index.blade.php --}}
@extends('layout.mainlayout')

@section('content')
@php
  $isPaginated = $materials instanceof \Illuminate\Contracts\Pagination\Paginator;
  $rows        = $isPaginated ? $materials->items() : $materials;

  $currentQuery = request()->query();
  $sort    = request('sort', 'name_asc');
  $perPage = request('per_page', 50);
  $search  = trim((string) request('search', ''));
  $cat     = request('category');

  $sortOptions = [
    'name_asc'=>'Name ↑','name_desc'=>'Name ↓','qty_desc'=>'Quantity ↓','qty_asc'=>'Quantity ↑',
    'price_desc'=>'Unit Price ↓','price_asc'=>'Unit Price ↑',
    'updated_desc'=>'Updated ↓','updated_asc'=>'Updated ↑',
  ];

  $unitOptions = ['kg'=>'Kilograms','g'=>'Grams','lbs'=>'Pounds','pcs'=>'Pieces','pkg'=>'Package','box'=>'Box','bag'=>'Bag','roll'=>'Roll','tray'=>'Tray','lt'=>'Liters','ml'=>'Milliliters','m3'=>'Cubic Meter'];

  $categoryCatalog = [
    'Primary Raw Materials','Meat Cuts & Trimmings','Fats / Skins','Salt','Curing Agents (Nitrite/Nitrate)',
    'Phosphates','Spices & Seasonings','Fillers & Binders','Sugars','Water / Ice','Smoke Materials',
    'Casings','Packaging Films & Bags','Labels & Cartons','Cleaning & Sanitation (Non-food)',
  ];

  function infer_category($m){
    $name = strtolower(($m->material_name ?? ''));
    $map = [
      'Primary Raw Materials'=>['carcass','beef','pork','chicken','lamb','whole'],
      'Meat Cuts & Trimmings'=>['loin','belly','ham','shoulder','trim','cut'],
      'Fats / Skins'=>['fat','tallow','skin','backfat'],
      'Salt'=>['salt','sodium chloride'],
      'Curing Agents (Nitrite/Nitrate)'=>['nitrite','nitrate','prague powder','curing'],
      'Phosphates'=>['phosphate'],
      'Spices & Seasonings'=>['pepper','garlic','spice','season','paprika','herb'],
      'Fillers & Binders'=>['starch','soy','binder','breadcrumb','flour','tvp'],
      'Sugars'=>['sugar','dextrose','sucrose'],
      'Water / Ice'=>['water','ice'],
      'Smoke Materials'=>['smoke','wood chip','hickory','liquid smoke'],
      'Casings'=>['casing','collagen','hog casing','sheep casing'],
      'Packaging Films & Bags'=>['vacuum','film','bag','tray','shrink','roll'],
      'Labels & Cartons'=>['label','carton','box','sticker'],
      'Cleaning & Sanitation (Non-food)'=>['sanitizer','detergent','glove','hairnet','apron'],
    ];
    foreach($map as $cat => $needles){
      foreach($needles as $n){ if(str_contains($name,$n)) return $cat; }
    }
    return 'Primary Raw Materials';
  }

  // valuation uses unit_price * quantity_kg
  $stats = (function($items){
    $sum=0;$cnt=0;$low=0;
    foreach($items as $m){
      $qty=(float)($m->quantity_kg??0);
      $price=(float)($m->unit_price??0);
      $sum += $qty*$price; $cnt++;
      $min=(float)($m->min_stock_kg??-1); if($min>=0 && $qty<$min) $low++;
    }
    return ['count'=>$cnt,'valuation'=>$sum,'low'=>$low];
  })($rows);
@endphp

{{-- Page-scoped light theme helpers (consistent with Sales/Products/Production) --}}
<style>
  .page-wrap { background:#f7f8fb; }
  .light-card{ background:#fff; border:1px solid #e5e7eb; border-radius:16px; box-shadow:0 8px 18px rgba(17,24,39,.04); }
  .input-light{
    width:100%; padding:.7rem .9rem; border-radius:12px;
    background:#fff; border:1px solid #e5e7eb; color:#111827;
    transition: box-shadow .16s, border-color .16s, transform .12s; outline:none;
  }
  .input-light::placeholder{ color:#9ca3af; }
  .input-light:hover{ border-color:#e2e8f0; }
  .input-light:focus{ box-shadow:0 0 0 2px rgba(59,130,246,.2); border-color:#93c5fd; transform:translateY(-1px); }

  .btn{ display:inline-flex; align-items:center; justify-content:center; gap:.5rem; padding:.72rem 1.05rem; border-radius:12px; font-weight:700; border:1px solid transparent; }
  /* Primary = RED */
  .btn-primary{ background:#ef4444; color:#fff; border-color:#ef4444; }
  .btn-primary:hover{ filter:brightness(.96); }
  /* Ghost/secondary (neutral) */
  .btn-ghost{ background:#fff; border:1px solid #e5e7eb; color:#111827; }
  .btn-ghost:hover{ background:#f3f4f6; }

  /* Links as secondary accents */
  .link-blue{ color:#1d4ed8; font-weight:600; }
  .link-blue:hover{ text-decoration:underline; }
  .link-green{ color:#047857; font-weight:600; }
  .link-green:hover{ text-decoration:underline; }

  /* Chips / badges */
  .chip{ display:inline-block; padding:.42rem .75rem; border-radius:999px; font-weight:700; font-size:.72rem; border:1px solid #e5e7eb; background:#fff; color:#111827; }
  .badge{ padding:.28rem .65rem; font-size:.72rem; border-radius:999px; border:1px solid #e5e7eb; display:inline-block; background:#fff; color:#111827; }
  .b-green{ background:#ecfdf5; border-color:#a7f3d0; color:#065f46; }
  .b-amber{ background:#fffbeb; border-color:#fde68a; color:#92400e; }
  .b-blue{ background:#eff6ff; border-color:#bfdbfe; color:#1d4ed8; }
  .b-gray{ background:#f9fafb; border-color:#e5e7eb; color:#374151; }

  /* KPIs */
  .kpi { background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:1rem; box-shadow:0 4px 12px rgba(17,24,39,.04); }

  /* Table */
  table{ border-collapse:separate; border-spacing:0; }
  thead th{ background:#f9fafb; color:#374151; font-weight:800; }
  tbody td{ color:#111827; }
  tbody tr:nth-child(even){ background:#fafafa; }
  tbody tr:hover{ background:#f3f4f6; }
  th, td{ border-color:#e5e7eb !important; }

  /* Modals (dialog) */
  dialog.modal{ border:0; padding:0; background:transparent; z-index:60; }
  dialog::backdrop{ background:rgba(0,0,0,.55); -webkit-backdrop-filter: blur(3px); backdrop-filter: blur(3px); }
  dialog[open]{ display:block; }
  .modal-box{ transform:translateY(8px) scale(.985); opacity:0; transition:.18s ease;
              background:#fff; border:1px solid #e5e7eb; border-radius:16px; box-shadow:0 20px 48px rgba(0,0,0,.12); padding:1.25rem; }
  dialog[open] .modal-box{ transform:translateY(0) scale(1); opacity:1; }
</style>

<div class="page-wrap rounded-2xl p-6 text-gray-900">
  <div class="light-card p-6">

    {{-- Header & CTA --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6">
      <div>
        <h2 class="text-2xl font-bold">Raw Materials</h2>
        <p class="text-sm text-gray-600">Unit price is recorded correctly and used in valuation.</p>
      </div>

      <button type="button" class="btn btn-primary" data-open="modalCreate" aria-haspopup="dialog" aria-controls="modalCreate" title="Add a new material">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M11 11V5h2v6h6v2h-6v6h-2v-6H5v-2z"/></svg>
        Add Material
      </button>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
      <div class="kpi">
        <div class="text-xs text-gray-600">Total Items</div>
        <div class="text-2xl font-semibold">{{ number_format($stats['count']) }}</div>
      </div>
      <div class="kpi">
        <div class="text-xs text-gray-600">Inventory Valuation</div>
        <div class="text-2xl font-semibold">₱ {{ number_format($stats['valuation'], 2) }}</div>
      </div>
      <div class="kpi">
        <div class="text-xs text-gray-600">Low Stock</div>
        <div class="text-2xl font-semibold">{{ number_format($stats['low']) }}</div>
      </div>
    </div>

    {{-- Filters --}}
    <form id="filterForm" action="{{ route('materials.index') }}" method="GET" class="mb-4" role="search">
      <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
        <div class="md:col-span-2">
          <label class="text-xs text-gray-600 mb-1 block" for="searchBox">Search</label>
          <div class="flex gap-2">
            <input id="searchBox" type="text" name="search" value="{{ $search }}" placeholder="Search name or SKU…" class="input-light" />
            <button type="submit" class="btn btn-ghost">Go</button>
            <a href="{{ route('materials.index') }}" class="btn btn-ghost">Reset</a>
          </div>
        </div>
        <div>
          <label class="text-xs text-gray-600 mb-1 block">Category</label>
          <select name="category" class="input-light" onchange="this.form.submit()">
            <option value="">All</option>
            @foreach($categoryCatalog as $_c)
              <option value="{{ $_c }}" @selected($cat === $_c)>{{ $_c }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="text-xs text-gray-600 mb-1 block">Sort</label>
          <select name="sort" class="input-light" onchange="this.form.submit()">
            @foreach($sortOptions as $k => $label)
              <option value="{{ $k }}" @selected($sort === $k)>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="text-xs text-gray-600 mb-1 block">Rows</label>
          <select name="per_page" class="input-light" onchange="this.form.submit()">
            @foreach([25,50,100,200,'all'] as $_pp)
              <option value="{{ $_pp }}" @selected((string)$perPage === (string)$_pp)>{{ is_numeric($_pp) ? $_pp : 'All' }}</option>
            @endforeach
          </select>
        </div>
      </div>

      {{-- quick chips --}}
      <div class="mt-3 flex flex-wrap gap-2">
        @php $chipBase = 'chip'; @endphp
        <a href="{{ route('materials.index', array_filter(array_merge($currentQuery, ['category'=>null]))) }}"
           class="{{ $chipBase }} {{ $cat ? '' : 'b-blue' }}">All</a>
        @foreach($categoryCatalog as $_c)
          @php $active = $cat === $_c ? 'b-green' : 'b-gray'; @endphp
          <a href="{{ route('materials.index', array_merge($currentQuery, ['category'=>$_c])) }}"
             class="badge {{ $active }}">{{ $_c }}</a>
        @endforeach
      </div>
    </form>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-2xl border border-gray-200 bg-white">
      <table class="w-full text-sm text-left rounded-2xl overflow-hidden">
        <thead class="text-xs uppercase">
          <tr>
            <th class="py-3 px-4 border-b">Name</th>
            <th class="py-3 px-4 border-b w-52">Category</th>
            <th class="py-3 px-4 border-b w-24">Unit</th>
            <th class="py-3 px-4 border-b w-32 text-right">Unit Price</th>
            <th class="py-3 px-4 border-b w-32 text-right">Quantity</th>
            <th class="py-3 px-4 border-b w-36 text-right">Line Value</th>
            <th class="py-3 px-4 border-b w-36">SKU</th>
            <th class="py-3 px-4 border-b w-40">Updated</th>
            <th class="py-3 px-4 border-b w-40 text-center">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
        @forelse($rows as $m)
          @php
            $category = $m->category ?? infer_category($m);
            $qty      = (float)($m->quantity_kg ?? 0);
            $price    = (float)($m->unit_price ?? 0);
            $lineVal  = $qty * $price;
            $badgeClass = (str_contains($category,'Spices') || str_contains($category,'Fillers')) ? 'b-amber' : 'b-green';
          @endphp
          @if(!$cat || $cat === $category)
            <tr class="hover:bg-gray-50 transition-colors">
              <td class="py-3 px-4">
                <div class="font-semibold flex items-center gap-2">
                  <span class="inline-block h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                  {{ $m->material_name }}
                </div>
              </td>
              <td class="py-3 px-4"><span class="badge {{ $badgeClass }}">{{ $category }}</span></td>
              <td class="py-3 px-4"><span class="badge b-gray">{{ $m->unit }}</span></td>
              <td class="py-3 px-4 text-right">₱ {{ number_format($price, 2) }}</td>
              <td class="py-3 px-4 text-right">{{ number_format($qty, 3) }}</td>
              <td class="py-3 px-4 text-right">₱ {{ number_format($lineVal, 2) }}</td>
              <td class="py-3 px-4 text-gray-700">{{ $m->sku ?? '—' }}</td>
              <td class="py-3 px-4 text-gray-600">{{ optional($m->updated_at)->format('Y-m-d H:i') }}</td>
              <td class="py-3 px-4">
                <div class="flex items-center justify-center gap-2">
                  <button type="button"
                          class="btn btn-ghost text-[.85rem]"
                          data-fetch-url="{{ route('materials.edit',$m->id) }}"
                          data-update-url="{{ route('materials.update',$m->id) }}"
                          title="Edit {{ $m->material_name }}">
                    Edit
                  </button>
                  <form action="{{ route('materials.destroy',$m->id) }}" method="POST" onsubmit="return confirm('Delete this material?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-primary" title="Delete {{ $m->material_name }}">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
          @endif
        @empty
          <tr><td colspan="9" class="py-6 px-4 text-center text-gray-600">No materials found.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>

    @if($isPaginated)
      <div class="mt-4">{{ $materials->withQueryString()->links() }}</div>
    @endif
  </div>
</div>

{{-- CREATE MODAL (Light) --}}
<dialog id="modalCreate" class="modal" aria-label="Add Material">
  <form method="POST" action="{{ route('materials.store') }}" class="modal-box w-full max-w-2xl">
    @csrf
    <h3 class="font-bold text-lg mb-4 text-gray-900">Add Material</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="text-xs text-gray-600 mb-1 block">Name</label>
        <input name="material_name" required class="input-light" placeholder="e.g., Pork Lean"/>
      </div>
      <div>
        <label class="text-xs text-gray-600 mb-1 block">Category</label>
        <select name="category" class="input-light">
          <option value="">— Select —</option>
          @foreach($categoryCatalog as $_c)<option value="{{ $_c }}">{{ $_c }}</option>@endforeach
        </select>
      </div>
      <div>
        <label class="text-xs text-gray-600 mb-1 block">Unit</label>
        <select name="unit" class="input-light" required>
          @foreach($unitOptions as $v=>$label)<option value="{{ $v }}">{{ $label }}</option>@endforeach
        </select>
      </div>
      <div>
        <label class="text-xs text-gray-600 mb-1 block">Unit Price (₱)</label>
        <input name="unit_price" type="number" min="0" step="0.01" class="input-light" value="0" />
      </div>
      <div>
        <label class="text-xs text-gray-600 mb-1 block">Quantity (kg)</label>
        <input name="quantity_kg" type="number" min="0" step="0.001" class="input-light" value="0"/>
      </div>
      <div>
        <label class="text-xs text-gray-600 mb-1 block">Min Stock (kg)</label>
        <input name="min_stock_kg" type="number" min="0" step="0.001" class="input-light"/>
      </div>
      <div class="md:col-span-2">
        <label class="text-xs text-gray-600 mb-1 block">SKU (optional)</label>
        <input name="sku" class="input-light" placeholder="e.g., MT-PORK-LEAN"/>
      </div>
    </div>
    <div class="flex justify-end gap-2 mt-6">
      <button type="button" class="btn btn-ghost" data-close>Cancel</button>
      <button type="submit" class="btn btn-primary">Save</button>
    </div>
  </form>
</dialog>

{{-- EDIT MODAL (Light) --}}
<dialog id="modalEdit" class="modal" aria-label="Edit Material">
  <form id="editForm" method="POST" class="modal-box w-full max-w-2xl">
    @csrf @method('PUT')
    <h3 class="font-bold text-lg mb-4 text-gray-900">Edit Material</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="text-xs text-gray-600 mb-1 block">Name</label>
        <input name="material_name" required class="input-light"/>
      </div>
      <div>
        <label class="text-xs text-gray-600 mb-1 block">Category</label>
        <select name="category" class="input-light">
          <option value="">— Select —</option>
          @foreach($categoryCatalog as $_c)<option value="{{ $_c }}">{{ $_c }}</option>@endforeach
        </select>
      </div>
      <div>
        <label class="text-xs text-gray-600 mb-1 block">Unit</label>
        <select name="unit" class="input-light" required>
          @foreach($unitOptions as $v=>$label)<option value="{{ $v }}">{{ $label }}</option>@endforeach
        </select>
      </div>
      <div>
        <label class="text-xs text-gray-600 mb-1 block">Unit Price (₱)</label>
        <input name="unit_price" type="number" min="0" step="0.01" class="input-light"/>
      </div>
      <div>
        <label class="text-xs text-gray-600 mb-1 block">Quantity (kg)</label>
        <input name="quantity_kg" type="number" min="0" step="0.001" class="input-light"/>
      </div>
      <div>
        <label class="text-xs text-gray-600 mb-1 block">Min Stock (kg)</label>
        <input name="min_stock_kg" type="number" min="0" step="0.001" class="input-light"/>
      </div>
      <div class="md:col-span-2">
        <label class="text-xs text-gray-600 mb-1 block">SKU (optional)</label>
        <input name="sku" class="input-light"/>
      </div>
    </div>
    <div class="flex justify-end gap-2 mt-6">
      <button type="button" class="btn btn-ghost" data-close>Cancel</button>
      <button type="submit" class="btn btn-primary">Save Changes</button>
    </div>
  </form>
</dialog>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const $ = (sel, root=document) => root.querySelector(sel);
  const $$ = (sel, root=document) => Array.from(root.querySelectorAll(sel));
  const byId = (id) => document.getElementById(id);

  // Open/close dialogs via global handlers (layout already wired)
  document.addEventListener('click', (e) => {
    if (e.target?.hasAttribute('data-close')) {
      const dlg = e.target.closest('dialog');
      try { dlg?.close(); } catch { dlg?.removeAttribute('open'); }
    }
  }, true);

  // EDIT: fetch JSON & populate then open modal
  $$('.btn[data-fetch-url]').forEach(btn => {
    btn.addEventListener('click', async () => {
      const fetchUrl  = btn.dataset.fetchUrl;
      const updateUrl = btn.dataset.updateUrl;
      const form      = byId('editForm');
      if (!fetchUrl || !updateUrl || !form) return;

      try {
        const res = await fetch(fetchUrl, {
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
          }
        });
        if (!res.ok) throw new Error('Load failed');

        const mat = await res.json();
        form.setAttribute('action', updateUrl);
        form.querySelector('[name="material_name"]').value = mat.material_name ?? '';
        form.querySelector('[name="category"]').value      = mat.category ?? '';
        form.querySelector('[name="unit"]').value          = mat.unit ?? 'kg';
        form.querySelector('[name="unit_price"]').value    = (mat.unit_price ?? 0);
        form.querySelector('[name="quantity_kg"]').value   = (mat.quantity_kg ?? 0);
        const minEl = form.querySelector('[name="min_stock_kg"]');
        if (minEl) minEl.value = (mat.min_stock_kg ?? '');
        form.querySelector('[name="sku"]').value = mat.sku ?? '';

        const modal = byId('modalEdit');
        if (modal?.showModal) modal.showModal();
        else modal?.setAttribute('open','open');
      } catch (e) {
        alert('Could not load material details.');
        console.warn(e);
      }
    });
  });
});
</script>
@endpush
