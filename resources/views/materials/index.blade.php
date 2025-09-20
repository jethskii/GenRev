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
    foreach($map as $cat => $needles){ foreach($needles as $n){ if(str_contains($name,$n)) return $cat; } }
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

<style>
  :root{
    --sun:#FFD122;         /* sunflower yellow */
    --orange:#FF9E2C;      /* warm orange accent */
    --leaf:#2F7A00;        /* deep leaf green */
    --leaf-2:#3F8E08;
    --cream:#F1E1C8;
    --ink:#0B0F0B;

    --panel:rgba(18,22,16,.94);
    --panel-2:rgba(20,24,18,.84);
    --line:rgba(255,255,255,.10);
  }
  .theme-wrap{
    background:
      radial-gradient(1200px 600px at 100% -10%, rgba(255,158,44,.10), transparent 60%),
      radial-gradient(900px 700px at -10% 110%, rgba(47,122,0,.09), transparent 60%),
      linear-gradient(180deg, #0f130f, #0a0d0a);
  }
  .panel{ background:var(--panel); border:1px solid var(--line); border-radius:1.2rem; }
  .thead-grad{ background:linear-gradient(180deg,rgba(255,255,255,.08),rgba(255,255,255,.04)); }

  .input{
    width:100%; padding:.7rem .9rem; border-radius:1rem; color:#F7F7F5;
    background:rgba(255,255,255,.07); border:1px solid var(--line);
    transition: box-shadow .16s, border-color .16s, transform .12s;
    outline:none;
  }
  .input:hover{ border-color:rgba(255,255,255,.16); }
  .input:focus{ box-shadow:0 0 0 4px rgba(47,122,0,.28); border-color:rgba(47,122,0,.7); transform:translateY(-1px); }
  select.input, .input select{ color:#F7F7F5; background-color:rgba(24,28,22,.95); }
  select.input option{ color:#F7F7F5!important; background-color:#121812!important; }
  select.input option:checked{ background-color:rgba(47,122,0,.72)!important; color:#fff!important; }

  .btn{
    display:inline-flex; align-items:center; justify-content:center; gap:.5rem;
    padding:.72rem 1.05rem; border-radius:1rem; border:1px solid var(--line);
    background:rgba(255,255,255,.07); color:#fff; font-weight:700; transition:.18s;
  }
  .btn:hover{ background:rgba(255,255,255,.12); }
  .btn-primary{
    background:linear-gradient(135deg, var(--sun), var(--orange));
    color:#2b2b00; border-color:rgba(0,0,0,.08);
    box-shadow:0 10px 28px rgba(255,170,46,.35);
  }
  .btn-primary:hover{ transform:translateY(-1px); box-shadow:0 14px 36px rgba(255,170,46,.45); }
  .btn-danger{ background:rgba(255,60,60,.16); color:#ffd7d4; border-color:rgba(255,60,60,.35); }

  .chip{ border:1px solid var(--line); background:rgba(255,255,255,.06); border-radius:999px; padding:.48rem .95rem; font-size:.76rem; }
  .chip.active{ box-shadow:0 0 0 2px rgba(255,209,34,.35); }

  .kpi{ background:linear-gradient(180deg, rgba(255,255,255,.08), rgba(255,255,255,.04)); border:1px solid var(--line); border-radius:1.1rem; padding:1rem; }

  .badge{ padding:.2rem .6rem; font-size:.72rem; border-radius:999px; border:1px solid transparent; }
  .b-primary{ background:rgba(47,122,0,.18); color:#d7ffb2; border-color:rgba(47,122,0,.45); }
  .b-spice,.b-fill{ background:rgba(255,170,46,.20); color:#312400; border-color:rgba(255,170,46,.45); }
  .b-default{ background:rgba(255,255,255,.12); color:#eee; border-color:rgba(255,255,255,.2); }

  dialog.modal{ border:0; padding:0; background:transparent; }
  dialog::backdrop{ background:rgba(0,0,0,.65); backdrop-filter:blur(3px); }
  .modal-box{ transform:translateY(8px) scale(.985); opacity:0; transition:.18s ease; }
  dialog[open] .modal-box{ transform:translateY(0) scale(1); opacity:1; }
</style>

<div class="theme-wrap rounded-2xl p-6 text-white">
  <div class="panel p-6">

    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6">
      <div>
        <h2 class="text-2xl font-bold">Raw Materials</h2>
        <p class="text-sm text-white/60">Unit price is now recorded correctly and used in valuation.</p>
      </div>
      <button id="btnAdd" type="button" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M11 11V5h2v6h6v2h-6v6h-2v-6H5v-2z"/></svg>
        Add Material
      </button>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
      <div class="kpi">
        <div class="text-xs text-white/60">Total Items</div>
        <div class="text-2xl font-semibold">{{ number_format($stats['count']) }}</div>
      </div>
      <div class="kpi">
        <div class="text-xs text-white/60">Inventory Valuation</div>
        <div class="text-2xl font-semibold">₱ {{ number_format($stats['valuation'], 2) }}</div>
      </div>
      <div class="kpi">
        <div class="text-xs text-white/60">Low Stock</div>
        <div class="text-2xl font-semibold">{{ number_format($stats['low']) }}</div>
      </div>
    </div>

    {{-- filters --}}
    <form id="filterForm" action="{{ route('materials.index') }}" method="GET" class="mb-4">
      <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
        <div class="md:col-span-2">
          <label class="text-xs text-white/60 mb-1 block">Search</label>
          <div class="flex gap-2">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search name or SKU…" class="input">
            <button type="submit" class="btn">Go</button>
            <a href="{{ route('materials.index') }}" class="btn">Reset</a>
          </div>
        </div>
        <div>
          <label class="text-xs text-white/60 mb-1 block">Category</label>
          <select name="category" class="input" onchange="this.form.submit()">
            <option value="">All</option>
            @foreach($categoryCatalog as $_c)
              <option value="{{ $_c }}" @selected($cat === $_c)>{{ $_c }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="text-xs text-white/60 mb-1 block">Sort</label>
          <select name="sort" class="input" onchange="this.form.submit()">
            @foreach($sortOptions as $k => $label)
              <option value="{{ $k }}" @selected($sort === $k)>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="text-xs text-white/60 mb-1 block">Rows</label>
          <select name="per_page" class="input" onchange="this.form.submit()">
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
           class="{{ $chipBase }} {{ $cat ? '' : 'active' }}">All</a>
        @foreach($categoryCatalog as $_c)
          @php $active = $cat === $_c ? 'active' : ''; @endphp
          <a href="{{ route('materials.index', array_merge($currentQuery, ['category'=>$_c])) }}"
             class="{{ $chipBase }} {{ $active }}">{{ $_c }}</a>
        @endforeach
      </div>
    </form>

    {{-- table --}}
    <div class="overflow-x-auto rounded-xl border border-[color:var(--line)]">
      <table class="w-full text-sm text-left bg-[color:var(--panel-2)] rounded-xl border-collapse">
        <thead class="thead-grad sticky top-0 z-10 text-white/90 text-xs uppercase">
          <tr>
            <th class="py-3 px-4 border-b border-[color:var(--line)]">Name</th>
            <th class="py-3 px-4 border-b border-[color:var(--line)] w-52">Category</th>
            <th class="py-3 px-4 border-b border-[color:var(--line)] w-24">Unit</th>
            <th class="py-3 px-4 border-b border-[color:var(--line)] w-32 text-right">Unit Price</th>
            <th class="py-3 px-4 border-b border-[color:var(--line)] w-32 text-right">Quantity</th>
            <th class="py-3 px-4 border-b border-[color:var(--line)] w-36 text-right">Line Value</th>
            <th class="py-3 px-4 border-b border-[color:var(--line)] w-36">SKU</th>
            <th class="py-3 px-4 border-b border-[color:var(--line)] w-40">Updated</th>
            <th class="py-3 px-4 border-b border-[color:var(--line)] w-40 text-center">Actions</th>
          </tr>
        </thead>
        <tbody class="text-gray-100 divide-y divide-[color:var(--line)]">
        @forelse($rows as $m)
          @php
            $category = $m->category ?? infer_category($m);
            $qty      = (float)($m->quantity_kg ?? 0);
            $price    = (float)($m->unit_price ?? 0);
            $lineVal  = $qty * $price;
            $badge = str_contains($category,'Spices') || str_contains($category,'Fillers') ? 'b-spice' : 'b-primary';
          @endphp
          @if(!$cat || $cat === $category)
            <tr class="hover:bg-white/6 transition-colors">
              <td class="py-3 px-4">
                <div class="font-medium flex items-center gap-2">
                  <span class="inline-block h-2.5 w-2.5 rounded-full" style="background:var(--leaf)"></span>
                  {{ $m->material_name }}
                </div>
              </td>
              <td class="py-3 px-4"><span class="badge {{ $badge }}">{{ $category }}</span></td>
              <td class="py-3 px-4"><span class="badge b-default">{{ $m->unit }}</span></td>
              <td class="py-3 px-4 text-right">₱ {{ number_format($price, 2) }}</td>
              <td class="py-3 px-4 text-right">{{ number_format($qty, 3) }}</td>
              <td class="py-3 px-4 text-right">₱ {{ number_format($lineVal, 2) }}</td>
              <td class="py-3 px-4 text-white/80">{{ $m->sku ?? '—' }}</td>
              <td class="py-3 px-4 text-white/60">{{ optional($m->updated_at)->format('Y-m-d H:i') }}</td>
              <td class="py-3 px-4">
                <div class="flex items-center justify-center gap-2">
                  <button type="button" class="btn text-[.8rem]"
                          data-fetch-url="{{ route('materials.edit',$m->id) }}"
                          data-update-url="{{ route('materials.update',$m->id) }}">Edit</button>
                  <form action="{{ route('materials.destroy',$m->id) }}" method="POST" onsubmit="return confirm('Delete this material?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
          @endif
        @empty
          <tr><td colspan="9" class="py-6 px-4 text-center text-white/70">No materials found.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>

    @if($isPaginated)
      <div class="mt-4">{{ $materials->withQueryString()->links() }}</div>
    @endif
  </div>
</div>

{{-- CREATE MODAL --}}
<dialog id="modalCreate" class="modal">
  <form method="POST" action="{{ route('materials.store') }}" class="modal-box panel w-full max-w-2xl">
    @csrf
    <h3 class="font-bold text-lg mb-4">Add Material</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div><label class="text-xs text-white/60 mb-1 block">Name</label><input name="material_name" required class="input" placeholder="e.g., Pork Lean"/></div>
      <div><label class="text-xs text-white/60 mb-1 block">Category</label>
        <select name="category" class="input"><option value="">— Select —</option>@foreach($categoryCatalog as $_c)<option value="{{ $_c }}">{{ $_c }}</option>@endforeach</select>
      </div>
      <div><label class="text-xs text-white/60 mb-1 block">Unit</label>
        <select name="unit" class="input" required>@foreach($unitOptions as $v=>$label)<option value="{{ $v }}">{{ $label }}</option>@endforeach</select>
      </div>
      <div><label class="text-xs text-white/60 mb-1 block">Unit Price (₱)</label>
        <input name="unit_price" type="number" min="0" step="0.01" class="input" value="0" />
      </div>
      <div><label class="text-xs text-white/60 mb-1 block">Quantity (kg)</label><input name="quantity_kg" type="number" min="0" step="0.001" class="input" value="0"/></div>
      <div><label class="text-xs text-white/60 mb-1 block">Min Stock (kg)</label><input name="min_stock_kg" type="number" min="0" step="0.001" class="input"/></div>
      <div class="md:col-span-2"><label class="text-xs text-white/60 mb-1 block">SKU (optional)</label><input name="sku" class="input" placeholder="e.g., MT-PORK-LEAN"/></div>
    </div>
    <div class="flex justify-end gap-2 mt-6">
      <button type="button" class="btn" data-close>Cancel</button>
      <button type="submit" class="btn btn-primary">Save</button>
    </div>
  </form>
</dialog>

{{-- EDIT MODAL --}}
<dialog id="modalEdit" class="modal">
  <form id="editForm" method="POST" class="modal-box panel w-full max-w-2xl">
    @csrf @method('PUT')
    <h3 class="font-bold text-lg mb-4">Edit Material</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div><label class="text-xs text-white/60 mb-1 block">Name</label><input name="material_name" required class="input"/></div>
      <div><label class="text-xs text-white/60 mb-1 block">Category</label>
        <select name="category" class="input"><option value="">— Select —</option>@foreach($categoryCatalog as $_c)<option value="{{ $_c }}">{{ $_c }}</option>@endforeach</select>
      </div>
      <div><label class="text-xs text-white/60 mb-1 block">Unit</label>
        <select name="unit" class="input" required>@foreach($unitOptions as $v=>$label)<option value="{{ $v }}">{{ $label }}</option>@endforeach</select>
      </div>
      <div><label class="text-xs text-white/60 mb-1 block">Unit Price (₱)</label>
        <input name="unit_price" type="number" min="0" step="0.01" class="input"/>
      </div>
      <div><label class="text-xs text-white/60 mb-1 block">Quantity (kg)</label><input name="quantity_kg" type="number" min="0" step="0.001" class="input"/></div>
      <div><label class="text-xs text-white/60 mb-1 block">Min Stock (kg)</label><input name="min_stock_kg" type="number" min="0" step="0.001" class="input"/></div>
      <div class="md:col-span-2"><label class="text-xs text-white/60 mb-1 block">SKU (optional)</label><input name="sku" class="input"/></div>
    </div>
    <div class="flex justify-end gap-2 mt-6">
      <button type="button" class="btn" data-close>Cancel</button>
      <button type="submit" class="btn btn-primary">Save Changes</button>
    </div>
  </form>
</dialog>
@endsection

@section('scripts')
<script>
  function openDialog(d){ try{ d.showModal?d.showModal():d.show(); }catch(_){ d.open=true; } }
  function closeDialog(d){ try{ d.close?d.close():d.removeAttribute('open'); }catch(_){ d.open=false; } }

  const modalCreate = document.getElementById('modalCreate');
  const modalEdit   = document.getElementById('modalEdit');

  document.getElementById('btnAdd')?.addEventListener('click',()=>openDialog(modalCreate));
  [modalCreate,modalEdit].forEach(m=>{
    m?.addEventListener('click',e=>{ if(e.target?.hasAttribute?.('data-close')) closeDialog(m); });
  });

  // Edit -> fetch JSON & populate
  document.querySelectorAll('.btn[data-fetch-url]').forEach(btn=>{
    btn.addEventListener('click',async()=>{
      const fetchUrl=btn.dataset.fetchUrl, updateUrl=btn.dataset.updateUrl, form=document.getElementById('editForm');
      if(!fetchUrl||!updateUrl||!form) return;
      try{
        const res=await fetch(fetchUrl,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}});
        if(!res.ok) throw new Error('Load failed');
        const mat=await res.json();
        form.setAttribute('action',updateUrl);
        form.querySelector('[name="material_name"]').value=mat.material_name??'';
        form.querySelector('[name="category"]').value=mat.category??'';
        form.querySelector('[name="unit"]').value=mat.unit??'kg';
        form.querySelector('[name="unit_price"]').value=(mat.unit_price??0);
        form.querySelector('[name="quantity_kg"]').value=(mat.quantity_kg??0);
        if(form.querySelector('[name="min_stock_kg"]')) form.querySelector('[name="min_stock_kg"]').value=(mat.min_stock_kg??'');
        form.querySelector('[name="sku"]').value=mat.sku??'';
        openDialog(modalEdit);
      }catch(e){ alert('Could not load material details.'); console.warn(e); }
    });
  });

  // ESC to close
  document.addEventListener('keydown',e=>{
    if(e.key==='Escape'){ if(modalEdit?.open) closeDialog(modalEdit); if(modalCreate?.open) closeDialog(modalCreate); }
  });

  // Ctrl/Cmd+Enter submit inside dialogs
  [modalCreate,modalEdit].forEach(m=>{
    m?.addEventListener('keydown',e=>{
      if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='enter'){ m.querySelector('form')?.submit(); }
    });
  });
</script>
@endsection
