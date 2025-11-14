@extends('layout.mainlayout')

@section('styles')
<style>
  :root{
    --bg-offwhite:#f7f7f5; --ink:#0f172a; --muted:#475569; --line:#e5e7eb;
    --red:#dc2626; --green:#16a34a; --blue:#2563eb;
  }
  .page-card{background:#fff;color:var(--ink);border:1px solid var(--line);border-radius:1rem;padding:1.25rem;box-shadow:0 1px 2px rgba(0,0,0,.04),0 10px 24px rgba(0,0,0,.05);}
  .soft-ring{border:1px solid var(--line);border-radius:1rem;}
  .input{width:100%;background:#fff;color:var(--ink);border:1px solid var(--line);border-radius:.75rem;padding:.6rem .8rem;transition:all .15s ease;}
  .input:focus{outline:0;border-color:var(--blue);box-shadow:0 0 0 3px rgba(37,99,235,.15)}
  .btn{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;border-radius:.75rem;padding:.55rem .85rem;font-weight:700;border:1px solid transparent;transition:filter .12s ease;}
  .btn-primary{background:var(--red);color:#fff;border-color:var(--red);}
  .btn-primary:hover{filter:brightness(.97);}
  .btn-outline{background:#fff;color:var(--ink);border:1px solid var(--line);}
  .btn-outline:hover{background:#f9fafb;}
  .toolbar{display:flex;flex-wrap:wrap;gap:.75rem;align-items:center;justify-content:space-between;margin-bottom:.75rem;}
  .chip{font-size:.75rem;padding:.3rem .7rem;border-radius:999px;border:1px solid var(--line);background:#f8fafc;color:#334155;cursor:pointer;transition:.15s;}
  .chip:hover{background:#f1f5f9;}
  .chip[data-active="true"]{background:#eef2ff;border-color:#e0e7ff;color:#3730a3;}
  .pill{display:inline-flex;align-items:center;gap:.4rem;padding:.15rem .5rem;border-radius:999px;font-size:.72rem;font-weight:700;border:1px solid;}
  .pill-ok{background:#ecfdf5;border-color:#a7f3d0;color:#065f46;}
  .pill-zero{background:#fef2f2;border-color:#fecaca;color:#7f1d1d;}
  table{border-collapse:separate;border-spacing:0;width:100%;}
  thead th{font-size:.72rem;text-transform:uppercase;color:#334155;background:#fafafa;border-bottom:1px solid var(--line);}
  tbody td{border-top:1px solid var(--line);}
  tbody tr{transition:opacity .18s ease, transform .18s ease;}
  tbody tr:hover{background:#fafafa;}
  .select-col{width:42px}
  .row-select{width:16px;height:16px;cursor:pointer}
  #bulkBar{position:sticky;top:0;z-index:20;display:none;}
  #bulkBar.active{display:block;}
  @media print{#bulkBar,[onclick],.btn{display:none!important}}
  
  /* --- Center Bubble Toast --- */
  .toast-center{
    position:fixed; inset:0; display:flex; align-items:center; justify-content:center;
    z-index:9999; pointer-events:none;
  }
  .toast-msg{
    min-width:280px; text-align:center;
    background:rgba(16,185,129,0.95);
    color:#fff; font-weight:700;
    border-radius:1rem;
    padding:1rem 1.5rem;
    box-shadow:0 0 40px rgba(16,185,129,0.4);
    transform:scale(0.8);
    opacity:0;
    animation:popIn .25s cubic-bezier(.25,1.25,.5,1) forwards;
  }
  .toast-msg.error{
    background:rgba(239,68,68,0.95);
    box-shadow:0 0 40px rgba(239,68,68,0.35);
  }
  @keyframes popIn{to{opacity:1;transform:scale(1);}}
  @keyframes popOut{to{opacity:0;transform:scale(.9);}}
</style>
@endsection

@section('content')
<div class="page-card">

  {{-- Header --}}
  <div class="flex items-center justify-between mb-6">
    <div>
      <h2 class="text-2xl font-bold tracking-wide">{{ $product->product_name }}</h2>
      <p class="text-sm text-[color:var(--muted)]">Types of Product: <span>{{ $product->category ?? 'Uncategorized' }}</span></p>
    </div>
    <img src="{{ $product->image_url ?? '/images/default-burger.png' }}" class="w-24 h-24 object-cover rounded-xl border border-[color:var(--line)]" alt="{{ $product->product_name }}">
  </div>

  {{-- Toolbar --}}
  <div class="toolbar">
    <div class="flex items-center gap-2">
      <a href="{{ route('production.index') }}" class="text-[color:var(--blue)] hover:underline">&larr; Back to Production</a>
      <span id="countBadge" class="chip" title="Visible batches">0 batches</span>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <div class="relative" style="max-width:320px">
        <input id="filterInput" type="text" class="input" placeholder="Search type, batch no., or remarks…">
        <span id="clearSearch" style="position:absolute;right:.7rem;top:50%;transform:translateY(-50%);cursor:pointer;color:#9ca3af;font-size:1rem;display:none">&times;</span>
      </div>
      <div class="hidden sm:flex items-center gap-1">
        @php $chips=['regular','special','garlic','chicken','beef','hamonado']; @endphp
        @foreach($chips as $c)
          <button type="button" class="chip js-chip" data-key="{{ $c }}">{{ ucfirst($c) }}</button>
        @endforeach
      </div>
    </div>
  </div>

  {{-- Bulk bar --}}
  <div id="bulkBar" class="px-4 py-3 bg-blue-50 border border-blue-200 rounded-lg mb-3">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div class="text-sm font-medium text-blue-900"><span id="bulkCount">0</span> selected</div>
      <div class="flex flex-wrap gap-2">
        <button type="button" class="btn btn-outline" id="bulkExportPdf">Export Selected PDF</button>
        <button type="button" class="btn btn-primary" id="bulkDelete">Delete Selected</button>
      </div>
    </div>
  </div>

  {{-- Orders Table --}}
  <div class="overflow-x-auto soft-ring">
    <table id="ordersTable">
      <thead>
        <tr>
          <th class="select-col text-center"><input type="checkbox" id="selectAll" class="row-select"></th>
          <th class="py-3 px-4">Batch #</th>
          <th class="py-3 px-4">Type</th>
          <th class="py-3 px-4 text-right">Avail Pack</th>
          <th class="py-3 px-4 text-right">Price/Pack</th>
          <th class="py-3 px-4 text-right">Avail Bag</th>
          <th class="py-3 px-4 text-right">Price/Bag</th>
          <th class="py-3 px-4">Prod. Date</th>
          <th class="py-3 px-4">Expiry</th>
          <th class="py-3 px-4 text-center">Actions</th>
        </tr>
      </thead>
      <tbody id="ordersBody">
        @foreach($orders as $o)
          @php
            $batch=$o->batch_number; $type=$o->type_name;
            $availP=(int)$o->available_pack; $availB=(int)$o->available_bag;
            $priceP=(float)$o->unit_price_pack; $priceB=(float)$o->unit_price_bag;
            $hay=\Illuminate\Support\Str::lower($batch.' '.$type.' '.($o->remarks??''));
          @endphp
          <tr data-id="{{ $o->id }}" data-hay="{{ $hay }}">
            <td class="text-center"><input type="checkbox" class="row-select" data-id="{{ $o->id }}"></td>
            <td class="font-mono text-xs py-2 px-4">{{ $batch }}</td>
            <td class="py-2 px-4">{{ $type }}</td>
            <td class="py-2 px-4 text-right"><span class="pill {{ $availP>0?'pill-ok':'pill-zero' }}">{{ $availP }}</span></td>
            <td class="py-2 px-4 text-right">₱{{ number_format($priceP,2) }}</td>
            <td class="py-2 px-4 text-right"><span class="pill {{ $availB>0?'pill-ok':'pill-zero' }}">{{ $availB }}</span></td>
            <td class="py-2 px-4 text-right">₱{{ number_format($priceB,2) }}</td>
            <td class="py-2 px-4">{{ \Carbon\Carbon::parse($o->production_date)->format('M d, Y') }}</td>
            <td class="py-2 px-4">{{ $o->expiration_date ? \Carbon\Carbon::parse($o->expiration_date)->format('M d, Y') : '—' }}</td>
            <td class="py-2 px-4 text-center">
              <div class="flex justify-center gap-2">
                <a href="{{ route('production.edit',$o->id) }}" class="btn btn-outline text-sm">Edit</a>
                <form action="{{ route('production.destroy',$o->id) }}" method="POST" class="delete-form" data-name="{{ $type }} • {{ $batch }}">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn btn-primary text-sm">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        @endforeach
      </tbody>
      <tfoot>
        <tr>
          <th colspan="3" class="py-3 px-4 text-right">Totals (visible)</th>
          <td class="py-3 px-4 text-right" id="tAvailPack">0</td>
          <td></td>
          <td class="py-3 px-4 text-right" id="tAvailBag">0</td>
          <td colspan="4"></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

{{-- Center Bubble Toast --}}
<div class="toast-center" id="toastCenter" style="display:none"></div>
@endsection

@section('scripts')
<script>
const $ = s=>document.querySelector(s), $$=s=>document.querySelectorAll(s);
const filterInput=$('#filterInput'),clearSearch=$('#clearSearch'),tbody=$('#ordersBody');
const countBadge=$('#countBadge'),tAvailPack=$('#tAvailPack'),tAvailBag=$('#tAvailBag');
const selectAll=$('#selectAll'),bulkBar=$('#bulkBar'),bulkCount=$('#bulkCount');
const selected=new Set(),bulkDel=$('#bulkDelete');

function num(s){return parseFloat((s||'').replace(/[^\d.-]/g,''))||0}
function fmt(n){return Number(n||0).toLocaleString()}

function applyFilter(){
  const q=(filterInput.value||'').trim().toLowerCase();
  let vis=0,pack=0,bag=0;
  tbody.querySelectorAll('tr[data-id]').forEach(tr=>{
    const show=!q||tr.dataset.hay.includes(q);
    tr.style.display=show?'':'none';
    if(show){vis++;const c=tr.children;pack+=num(c[3].innerText);bag+=num(c[5].innerText);}
  });
  countBadge.textContent=`${vis} ${vis==1?'batch':'batches'}`;
  tAvailPack.textContent=fmt(pack);tAvailBag.textContent=fmt(bag);
}
filterInput.addEventListener('input',applyFilter);
clearSearch.addEventListener('click',()=>{filterInput.value='';applyFilter();});
window.addEventListener('load',applyFilter);
$$('.js-chip').forEach(chip=>{
  chip.addEventListener('click',()=>{
    const key=chip.dataset.key,active=chip.dataset.active==='true';
    $$('.js-chip').forEach(c=>c.dataset.active='false');
    chip.dataset.active=active?'false':'true';
    filterInput.value=active?'':key;applyFilter();
  });
});

/* --- Toast Popup --- */
function toast(msg,type='ok'){
  const wrap=$('#toastCenter');
  wrap.innerHTML=`<div class="toast-msg ${type==='error'?'error':''}">${msg}</div>`;
  wrap.style.display='flex';
  setTimeout(()=>{
    const t=wrap.querySelector('.toast-msg');
    t.style.animation='popOut .25s ease forwards';
    setTimeout(()=>{wrap.style.display='none';},250);
  },1600);
}

/* --- Select & Bulk --- */
const rows=()=>[...tbody.querySelectorAll('.row-select')].filter(cb=>cb.closest('tr').style.display!=='none');
function updateUI(){
  bulkCount.textContent=selected.size;
  bulkBar.classList.toggle('active',selected.size>0);
  const all=rows();const allChecked=all.length&&all.every(cb=>cb.checked);
  selectAll.checked=allChecked;selectAll.indeterminate=selected.size>0&&!allChecked;
}
selectAll.addEventListener('change',()=>{
  rows().forEach(cb=>{cb.checked=selectAll.checked;cb.checked?selected.add(cb.dataset.id):selected.delete(cb.dataset.id)});
  updateUI();
});
tbody.addEventListener('change',e=>{
  if(!e.target.matches('.row-select'))return;
  const id=e.target.dataset.id;
  e.target.checked?selected.add(id):selected.delete(id);
  updateUI();
});

/* --- AJAX Delete + Bubble --- */
const CSRF='{{ csrf_token() }}';
tbody.addEventListener('submit',async e=>{
  const f=e.target.closest('.delete-form'); if(!f)return;
  e.preventDefault();
  const name=f.dataset.name||'Product';
  if(!confirm(`Delete ${name}?`))return;
  try{
    const fd=new FormData(f);
    const res=await fetch(f.action,{method:'POST',headers:{'X-CSRF-TOKEN':CSRF,'X-Requested-With':'XMLHttpRequest'},body:fd});
    if(!res.ok)throw new Error();
    const tr=f.closest('tr'); tr.style.opacity='.0'; tr.style.transform='scale(.98)';
    setTimeout(()=>tr.remove(),150); applyFilter(); toast('Product Deleted');
  }catch{toast('Delete Failed','error');}
});

/* --- Bulk Delete --- */
bulkDel.addEventListener('click',async()=>{
  if(!selected.size)return;
  if(!confirm(`Delete ${selected.size} selected batch(es)?`))return;
  let ok=0;
  for(const id of [...selected]){
    const tr=tbody.querySelector(`tr[data-id="${id}"]`);
    const f=tr?.querySelector('.delete-form'); if(!f)continue;
    try{
      const fd=new FormData(f);
      const res=await fetch(f.action,{method:'POST',headers:{'X-CSRF-TOKEN':CSRF,'X-Requested-With':'XMLHttpRequest'},body:fd});
      if(!res.ok)throw new Error();
      tr.style.opacity='.0'; tr.style.transform='scale(.98)';
      await new Promise(r=>setTimeout(r,150));
      tr.remove(); ok++; selected.delete(id);
    }catch{}
  }
  applyFilter(); updateUI();
  if(ok)toast(`${ok} Deleted`);
});
</script>
@endsection
