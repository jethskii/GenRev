@extends('layout.mainlayout')

@section('styles')
<style>
  :root{ --bg-offwhite:#f7f7f5; --ink:#0f172a; --muted:#475569; --line:#e5e7eb; --red:#dc2626; --green:#16a34a; --blue:#2563eb; }
  .page-card{ background:#fff;color:var(--ink);border:1px solid var(--line);border-radius:1rem;padding:1.25rem;box-shadow:0 1px 2px rgba(0,0,0,.04),0 10px 24px rgba(0,0,0,.05); }
  .soft-ring{ border:1px solid var(--line); border-radius:1rem; }
  .label{font-size:.85rem;color:var(--muted);margin-bottom:.35rem;display:block}
  .input,.select,.textarea{width:100%;background:#fff;color:var(--ink);border:1px solid var(--line);border-radius:.75rem;padding:.6rem .8rem;line-height:1.35;transition:box-shadow .15s ease,border-color .15s ease}
  .input:focus,.select:focus,.textarea:focus{outline:0;border-color:var(--blue);box-shadow:0 0 0 3px rgba(37,99,235,.15)}
  .help{font-size:.75rem;color:#64748b}
  .btn{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;border-radius:.75rem;padding:.6rem .9rem;font-weight:700;border:1px solid transparent;transition:filter .12s ease}
  .btn:disabled{opacity:.6;cursor:not-allowed}
  .btn-primary{background:var(--red);color:#fff}
  .btn-primary:hover{filter:brightness(.97)}
  .btn-outline{background:#fff;color:var(--ink);border:1px solid var(--line)}
  .btn-outline:hover{filter:brightness(.98)}
  .toolbar{display:flex;flex-wrap:wrap;gap:.5rem;align-items:center;justify-content:space-between;margin:.25rem 0 1rem}
  .chip{font-size:.75rem;padding:.25rem .6rem;border-radius:999px;border:1px solid var(--line);background:#f8fafc;color:#334155;cursor:pointer}
  .chip[data-active="true"]{background:#eef2ff;border-color:#e0e7ff;color:#3730a3}
  .muted{color:var(--muted);}
  table{border-collapse:separate;border-spacing:0}
  thead th{font-size:.72rem;letter-spacing:.02em;text-transform:uppercase;color:#334155;background:#fafafa;border-bottom:1px solid var(--line)}
  tbody td{border-top:1px solid var(--line)}
  tbody tr:hover{background:#fafafa}
  tfoot th,tfoot td{border-top:2px solid var(--line);background:#fafafa}
  @keyframes fadeIn { from{opacity:0;transform:scale(.98)} to{opacity:1;transform:scale(1)} }
  .animate-fadeIn{ animation:fadeIn .18s ease-out }
</style>
@endsection

@section('content')
<div class="page-card">

  {{-- Header --}}
  <div class="flex items-center justify-between mb-6">
    <div>
      <h2 id="productTitle" class="text-2xl font-bold tracking-wide">{{ $product->product_name }}</h2>
      <p class="text-sm text-[color:var(--muted)]">
        Category: <span id="productCategory">{{ $product->category ?? 'Uncategorized' }}</span>
      </p>
    </div>
    <img id="productImage" src="{{ $product->image_url ?? '/images/default-burger.png' }}"
         class="w-24 h-24 object-cover rounded-xl border border-[color:var(--line)]"
         alt="{{ $product->product_name }}">
  </div>

  {{-- Toolbar --}}
  <div class="toolbar">
    <div class="flex items-center gap-2">
      <a href="{{ route('production.index') }}" class="text-[color:var(--blue)] hover:underline">&larr; Back to Production</a>
      <span id="countBadge" class="chip" title="Visible batches">0 batches</span>
    </div>
    <div class="flex items-center gap-2">
      <input id="filterInput" class="input" placeholder="Search type, product, batch no., notes…" style="max-width:320px">
      <div class="hidden sm:flex items-center gap-1">
        @php $chips=['regular','special','garlic','chicken','beef','hamonado']; @endphp
        @foreach($chips as $c)
          <button type="button" class="chip js-chip" data-key="{{ $c }}">{{ ucfirst($c) }}</button>
        @endforeach
      </div>
      <button id="addOrderBtn" type="button" class="btn btn-primary">+ Add Order</button>
    </div>
  </div>

  {{-- Flash + Errors --}}
  @if(session('success'))
    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 text-green-800 px-3 py-2">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 text-red-800 px-3 py-2">{{ session('error') }}</div>
  @endif
  @if ($errors->any())
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 text-red-800 px-3 py-2">
      <ul class="list-disc pl-6">
        @foreach ($errors->all() as $err)<li>{{ $err }}</li>@endforeach
      </ul>
    </div>
  @endif

  {{-- Orders Table (parent page shows all child variants) --}}
  <div class="overflow-x-auto soft-ring">
    <table class="min-w-full text-sm text-left rounded-2xl overflow-hidden" id="ordersTable">
      <thead>
        <tr>
          <th class="py-3 px-4">Batch #</th>
          <th class="py-3 px-4">Type</th>
          <th class="py-3 px-4">Forecasted</th>
          <th class="py-3 px-4">Produced</th>
          <th class="py-3 px-4">Unit Cost</th>
          <th class="py-3 px-4">Price/Pack</th>
          <th class="py-3 px-4">Price/Bag</th>
          <th class="py-3 px-4">Prod. Date</th>
          <th class="py-3 px-4">Expiry</th>
          <th class="py-3 px-4">Actions</th>
        </tr>
      </thead>
      <tbody id="ordersBody">
        @forelse ($orders as $o)
          @php
            $batch = (string)($o->batch_number ?? '');
            $type  = $o->type_name;  // from accessor -> uses saved snapshot
            $hay   = $o->type_keywords ?: \Illuminate\Support\Str::lower(
                        trim($batch.' '.($o->product_name_snapshot ?? $o->product->product_name ?? '').' '.($o->notes ?? ''))
                     );
          @endphp
          <tr id="order-row-{{ $o->id }}"
              data-type="{{ \Illuminate\Support\Str::lower($type) }}"
              data-hay="{{ $hay }}">
            <td class="py-3 px-4 font-mono text-xs">{{ $batch }}</td>
            <td class="py-3 px-4">{{ $type }}</td>
            <td class="py-3 px-4">{{ number_format((float)$o->forecasted_demand, 3) }} kg</td>
            <td class="py-3 px-4">{{ number_format((float)($o->quantity ?? $o->current_inventory), 3) }} kg</td>
            <td class="py-3 px-4">₱{{ number_format((float)$o->unit_cost, 2) }}</td>
            <td class="py-3 px-4">₱{{ number_format((float)($o->unit_price_pack ?? 0), 2) }}</td>
            <td class="py-3 px-4">₱{{ number_format((float)($o->unit_price_bag  ?? 0), 2) }}</td>
            <td class="py-3 px-4">{{ \Carbon\Carbon::parse($o->production_date)->format('M d, Y') }}</td>
            <td class="py-3 px-4">{{ $o->expiration_date ? \Carbon\Carbon::parse($o->expiration_date)->format('M d, Y') : '—' }}</td>
            <td class="py-3 px-4">
              <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('production.edit', $o->id) }}" class="btn btn-outline">Edit</a>
                <form action="{{ route('production.destroy', $o->id) }}" method="POST"
                      onsubmit="return confirm('Delete this batch? It will be soft-deleted and removed after 7 days.')">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn btn-primary" style="background:var(--red)">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="10" class="py-4 text-center text-[color:var(--muted)]">No production orders yet.</td></tr>
        @endforelse
      </tbody>
      <tfoot>
        <tr>
          <th class="py-3 px-4 text-right" colspan="2">Totals (visible)</th>
          <td class="py-3 px-4"><span id="tForecast">0</span> kg</td>
          <td class="py-3 px-4"><span id="tProduced">0</span> kg</td>
          <td class="py-3 px-4">—</td>
          <td class="py-3 px-4">₱<span id="tPack">0.00</span></td>
          <td class="py-3 px-4">₱<span id="tBag">0.00</span></td>
          <td class="py-3 px-4" colspan="3"></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

{{-- Modal --}}
@include('production.partials.add-order-modal')
@endsection

@section('scripts')
<script>
  const $$ = id => document.getElementById(id);

  const filterInput = $$('#filterInput');
  const body = $$('#ordersBody');
  const countBadge = $$('#countBadge');
  const tForecast = $$('#tForecast');
  const tProduced = $$('#tProduced');
  const tPack = $$('#tPack');
  const tBag = $$('#tBag');

  function num(s){ const n = parseFloat(String(s).replace(/[^\d.-]/g,'')); return isNaN(n)?0:n; }
  function kgFmt(n){ return Number(n||0).toLocaleString(undefined,{maximumFractionDigits:3}); }
  function pesoFmt(n){ return Number(n||0).toFixed(2); }

  function applyFilter(){
    const q = (filterInput?.value || '').trim().toLowerCase();
    let vis = 0, f=0, p=0, pack=0, bag=0;

    [...body.querySelectorAll('tr[id^="order-row-"]')].forEach(tr=>{
      const hay = tr.dataset.hay || '';
      const match = !q || hay.includes(q);
      tr.style.display = match ? '' : 'none';
      if(match){
        vis++;
        const cells = tr.children;
        f   += num(cells[2].innerText);
        p   += num(cells[3].innerText);
        pack+= num(cells[5].innerText);
        bag += num(cells[6].innerText);
      }
    });

    countBadge.textContent = `${vis} ${vis===1?'batch':'batches'}`;
    tForecast.textContent  = kgFmt(f);
    tProduced.textContent  = kgFmt(p);
    tPack.textContent      = pesoFmt(pack);
    tBag.textContent       = pesoFmt(bag);
  }

  filterInput?.addEventListener('input', applyFilter);
  window.addEventListener('load', applyFilter);

  document.querySelectorAll('.js-chip').forEach(chip=>{
    chip.addEventListener('click', ()=>{
      const key = chip.dataset.key || '';
      const active = chip.getAttribute('data-active') === 'true';
      document.querySelectorAll('.js-chip').forEach(c=>c.setAttribute('data-active','false'));
      filterInput.value = active ? '' : key;
      chip.setAttribute('data-active', active ? 'false' : 'true');
      applyFilter();
    });
  });

  $$('#addOrderBtn')?.addEventListener('click', openOrderModal);

  @if ($errors->any())
    window.addEventListener('load', openOrderModal);
  @endif
</script>
@endsection
