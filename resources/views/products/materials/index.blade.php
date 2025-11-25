@extends('layout.mainlayout')
@section('title', 'Recipe / BOM · '.$product->product_name)

@section('styles')
<style>
  :root{
    --bg-offwhite:#f7f7f5; --ink:#0f172a; --muted:#475569; --line:#e5e7eb;
    --red:#dc2626; --green:#16a34a; --blue:#2563eb;
  }
  .page-wrap{
    min-height:100%;
    background:
      radial-gradient(1100px 700px at 0% -20%, rgba(220,38,38,.06), transparent 60%),
      radial-gradient(900px 600px at 120% 120%, rgba(37,99,235,.06), transparent 60%),
      var(--bg-offwhite);
    padding: 2rem 1rem 3rem; color: var(--ink);
    font-family: 'Inria Sans', system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
  }
  .card{ background:#fff; border:1px solid var(--line); border-radius:1rem; padding:1rem; box-shadow:0 1px 2px rgba(0,0,0,.04), 0 10px 24px rgba(0,0,0,.05); }
  .card + .card{ margin-top:1rem }
  .label{font-size:.85rem;color:var(--muted);margin-bottom:.35rem;display:block}
  .input,.select{
    width:100%; background:#fff; color:var(--ink);
    border:1px solid var(--line); border-radius:.75rem;
    padding:.6rem .8rem; line-height:1.35; transition: box-shadow .15s ease, border-color .15s ease;
  }
  .input:focus,.select:focus{outline:0;border-color:var(--blue);box-shadow:0 0 0 3px rgba(37,99,235,.15)}
  .help{font-size:.75rem;color:#64748b}
  .btn{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;border-radius:.75rem;padding:.6rem .9rem;font-weight:700;border:1px solid transparent;transition:filter .12s ease}
  .btn:disabled{opacity:.6;cursor:not-allowed}
  .btn-primary{background:var(--red);color:#fff}
  .btn-primary:hover{filter:brightness(.97)}
  .btn-muted{background:#fff;color:var(--ink);border:1px solid var(--line)}
  .btn-muted:hover{filter:brightness(.98)}
  .btn-ghost{background:transparent;color:var(--ink);border:1px dashed var(--line)}
  .chip{display:inline-flex;align-items:center;gap:.4rem;background:#fff;border:1px solid var(--line);border-radius:999px;font-weight:700;font-size:.72rem;padding:.25rem .6rem}
  .chip-blue{border-color:#bfdbfe;background:#eff6ff}
  .chip-green{border-color:#bbf7d0;background:#ecfdf5}
  table{border-collapse:separate;border-spacing:0;width:100%}
  thead th{font-size:.75rem;letter-spacing:.02em;text-transform:uppercase;color:#334155;background:#fafafa;border-bottom:1px solid var(--line)}
  tbody td{border-top:1px solid var(--line)}
  tfoot th,tfoot td{border-top:2px solid var(--line);background:#fafafa}
  .w-110{width:110px}
  .text-right{ text-align:right }
  .tabular-nums{ font-variant-numeric: tabular-nums }
  .row-actions{ display:flex; gap:.5rem; justify-content:flex-end }

  /* Multi-select styles */
  .select-col{ width:42px }
  .row-select{ width:16px; height:16px }
  #bulkBar{
    position: sticky; top: 0; z-index: 30;
    display: none;
  }
  #bulkBar.active{ display: block; }
  @media print{
    #bulkBar,[onclick],.btn{ display:none !important; }
  }
</style>
@endsection

@section('content')
<div class="page-wrap mx-auto max-w-5xl">

  {{-- Header --}}
  <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <div class="min-w-0">
      <h1 class="text-2xl font-semibold tracking-wide truncate">
        Recipe / BOM — {{ $product->product_name }}
      </h1>
      <div class="mt-2 flex flex-wrap items-center gap-2">
        <span class="chip chip-blue">
          Category: {{ $product->category ?: 'Uncategorized' }}
        </span>
        {{-- Types fetched from ProductionController@suggestTypes --}}
        <div class="flex flex-wrap gap-1.5" id="typeChips" data-types-url="{{ url('/production/'.$product->id.'/types') }}">
          <span class="chip">Loading types…</span>
        </div>
      </div>
    </div>

    <div class="flex items-center gap-2 shrink-0">
      <a href="{{ route('production.orders', $product->id) }}" class="btn btn-primary">View Production Orders</a>
      <a href="{{ route('products.show', $product) }}" class="btn btn-muted">← Back to Product</a>
    </div>
  </div>

  {{-- Defaults loaded banner (hidden until JS shows it) --}}
  <div id="defaultsBanner" class="card mb-4" style="display:none;">
    <div class="text-sm text-slate-700">
      Defaults loaded from:
      <a id="defaultsBannerLink" href="#" class="font-semibold text-blue-600 hover:underline">
        <span id="defaultsBannerName"></span>
      </a>
    </div>
  </div>

  {{-- Flash + Errors --}}
  @if(session('success'))
    <div class="card" role="status">{{ session('success') }}</div>
  @endif
  @if($errors->any())
    <div class="card" role="alert">
      <strong class="block mb-2">Please fix the following:</strong>
      <ul class="list-disc ml-6">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
      </ul>
    </div>
  @endif

  {{-- Add multiple lines --}}
  <div class="card">
    <form id="add-lines-form" method="POST" action="{{ route('products.materials.store', $product) }}">
      @csrf

      <div class="flex items-center justify-between mb-3">
        <h2 class="text-lg font-semibold">Add Materials</h2>
        <div class="flex gap-2">
          @if(Route::has('products.materials.defaults'))
            <button type="button" id="load-defaults-btn" class="btn btn-muted">
              Load defaults
            </button>
          @endif
          <button type="button" id="add-row-btn" class="btn btn-ghost">+ Add Row</button>
          <button type="submit" class="btn btn-primary">Save Lines</button>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="text-sm" id="entry-table">
          <thead>
            <tr>
              <th class="text-left p-2">Material</th>
              <th class="text-right p-2 w-110">Qty</th>
              <th class="text-left p-2 w-110">Unit</th>
              <th class="text-right p-2 w-110">Unit Price</th>
              <th class="text-right p-2 w-110">Line Total</th>
              <th class="text-right p-2 w-110">Action</th>
            </tr>
          </thead>
          <tbody id="entry-body"><!-- rows via JS --></tbody>
          <tfoot>
            <tr>
              <th colspan="4" class="p-2 text-right text-gray-700">New Lines Total</th>
              <th class="p-2 text-right font-semibold tabular-nums" id="new-grand">₱ 0.00</th>
              <th></th>
            </tr>
          </tfoot>
        </table>
      </div>

      <p class="help mt-2">
        Tip: choose a material to auto-fill unit and default price.
        If this product has similar type or variant, you can use “Load defaults” to pull its usual raw materials.
      </p>
    </form>
  </div>

  {{-- Current recipe table --}}
  <div class="card">

    {{-- Bulk bar for current lines --}}
    <div id="bulkBar" class="px-4 py-3 bg-blue-50 border border-blue-200 rounded-lg mb-3">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="text-sm font-medium text-blue-900">
          <span id="bulkCount">0</span> selected
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <button type="button" class="btn btn-muted" id="bulkExportPdf">Export Selected PDF</button>
          <button type="button" class="btn btn-primary" style="background:var(--red)" id="bulkDelete">Delete Selected</button>
        </div>
      </div>
    </div>

    <div class="mb-3">
      <h2 class="text-lg font-semibold">Current Lines</h2>
      <p class="text-sm text-gray-600">Costs use the unit price snapshot stored for each line.</p>
    </div>

    <div class="overflow-x-auto">
      <table class="text-sm" id="current-table">
        <thead>
          <tr>
            <th class="select-col p-2 text-center">
              <input type="checkbox" id="selectAll" class="row-select" aria-label="Select all lines">
            </th>
            <th class="text-left p-2">Material</th>
            <th class="text-right p-2 w-110">Qty</th>
            <th class="text-left p-2 w-110">Unit</th>
            <th class="text-right p-2 w-110">Unit Price</th>
            <th class="text-right p-2 w-110">Line Total</th>
            <th class="text-right p-2 w-110">Actions</th>
          </tr>
        </thead>
        <tbody>
        @php $grand = 0; @endphp
        @forelse($recipe as $line)
          @php
            $mat   = $line->material;
            $unit  = $mat->unit ?? ($line->unit ?? '');
            $snap  = (float)($line->unit_price_snapshot ?? $line->unit_price ?? 0);
            $qty   = (float)($line->quantity_per_unit ?? $line->qty ?? 0);
            $total = $qty * $snap;
            $grand += $total;
          @endphp
          <tr data-line-id="{{ $line->id }}">
            <td class="p-2 text-center">
              <input type="checkbox" class="row-select" data-line-id="{{ $line->id }}" aria-label="Select line {{ $line->id }}">
            </td>
            <td class="p-2">{{ $mat->material_name ?? '—' }}</td>
            <td class="p-2 text-right tabular-nums">{{ number_format($qty, 3) }}</td>
            <td class="p-2">{{ $unit }}</td>
            <td class="p-2 text-right tabular-nums">₱ {{ number_format($snap, 2) }}</td>
            <td class="p-2 text-right tabular-nums">₱ {{ number_format($total, 2) }}</td>
            <td class="p-2">
              <div class="row-actions">
                <form method="POST"
                      action="{{ route('products.materials.destroy', [$product, $line]) }}"
                      onsubmit="return confirm('Remove this line?')"
                      class="delete-form">
                  @csrf @method('DELETE')
                  <button class="btn btn-primary" style="background:var(--red)">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="p-4 text-gray-600">No lines yet. Add one above or load defaults.</td>
          </tr>
        @endforelse
        </tbody>
        <tfoot>
          <tr>
            <th colspan="5" class="p-2 text-right text-gray-700">Grand Total</th>
            <th class="p-2 text-right font-semibold tabular-nums">₱ {{ number_format($grand, 2) }}</th>
            <th></th>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>
@endsection

@section('scripts')
@php
  $materialsPayload = $materials->map(function($m){
      return [
          'id'    => $m->id,
          'name'  => $m->material_name,
          'unit'  => $m->unit,
          'price' => (float)($m->default_unit_price ?? 0),
      ];
  })->values();

  $defaultsRoute = \Illuminate\Support\Facades\Route::has('products.materials.defaults')
      ? route('products.materials.defaults', $product)
      : null;

  $hasExistingLines = $recipe->isNotEmpty();
@endphp

<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>

<script>
(() => {
  const materials = @json($materialsPayload);
  const defaultsUrl = @json($defaultsRoute);
  const hasExistingLines = @json($hasExistingLines);

  const typesEl = document.getElementById('typeChips');
  const typesUrl = typesEl ? typesEl.getAttribute('data-types-url') : null;

  // Defaults banner elements
  const defaultsBanner = document.getElementById('defaultsBanner');
  const defaultsBannerName = document.getElementById('defaultsBannerName');
  const defaultsBannerLink = document.getElementById('defaultsBannerLink');

  async function loadTypes(){
    if(!typesEl || !typesUrl) return;
    try{
      const r = await fetch(typesUrl, { headers: {'X-Requested-With':'XMLHttpRequest'} });
      if(!r.ok){ typesEl.innerHTML = '<span class="chip">No types</span>'; return; }
      const j = await r.json();
      const list = (Array.isArray(j.types) ? j.types : (j.list || [])).slice(0,6);
      if(list.length){
        typesEl.innerHTML = list.map(s => `<span class="chip chip-green">${escapeHtml(String(s))}</span>`).join(' ');
      }else{
        typesEl.innerHTML = '<span class="chip">No types</span>';
      }
    }catch(e){
      typesEl.innerHTML = '<span class="chip">No types</span>';
    }
  }

  function escapeHtml(s){
    return s
      .replaceAll('&','&amp;')
      .replaceAll('<','&lt;')
      .replaceAll('>','&gt;')
      .replaceAll('"','&quot;')
      .replaceAll("'","&#039;");
  }

  /************ Add rows form ************/
  const body = document.getElementById('entry-body');
  const newGrandEl = document.getElementById('new-grand');
  const addBtn = document.getElementById('add-row-btn');
  const loadDefaultsBtn = document.getElementById('load-defaults-btn');

  let rowIdx = 0;

  function money(n){ return '₱ ' + Number(n || 0).toFixed(2); }
  function recalcGrand(){
    let g = 0;
    body.querySelectorAll('tr').forEach(tr => {
      const qty = parseFloat((tr.querySelector('.qty') || {}).value || '0');
      const price = parseFloat((tr.querySelector('.price') || {}).value || '0');
      g += qty * price;
      const totalCell = tr.querySelector('.line-total');
      if(totalCell){ totalCell.textContent = money(qty * price); }
    });
    newGrandEl.textContent = money(g);
  }

  function createMaterialSelect(nameAttr, unitInput, priceInput, initialId = null, initialPrice = null){
    const sel = document.createElement('select');
    sel.className = 'select mat';
    sel.name = nameAttr;
    sel.required = true;

    const opt0 = document.createElement('option');
    opt0.value = ''; opt0.textContent = '— choose —';
    sel.appendChild(opt0);

    for (let i = 0; i < materials.length; i++) {
      const m = materials[i];
      const o = document.createElement('option');
      o.value = m.id;
      o.textContent = m.name;
      o.dataset.unit = m.unit || '';
      o.dataset.price = (m.price != null ? m.price : 0);
      if (initialId !== null && Number(initialId) === Number(m.id)) {
        o.selected = true;
      }
      sel.appendChild(o);
    }

    sel.addEventListener('change', () => {
      const opt = sel.options[sel.selectedIndex];
      if(!opt) return;
      unitInput.value = opt.dataset.unit || '';
      if (!priceInput.value || Number(priceInput.value) === 0) {
        priceInput.value = (opt.dataset.price || 0);
      }
      recalcGrand();
    });

    if (initialId !== null) {
      const opt = Array.from(sel.options).find(o => Number(o.value) === Number(initialId));
      if (opt) {
        sel.value = String(initialId);
        unitInput.value = opt.dataset.unit || unitInput.value;
        if (initialPrice === null || Number(initialPrice) === 0) {
          priceInput.value = opt.dataset.price || priceInput.value || 0;
        }
      }
    }

    return sel;
  }

  function addRow(cfg = {}){
    const config = {
      material_id: cfg.material_id ?? null,
      qty: cfg.qty ?? 1,
      unit: cfg.unit ?? '',
      unit_price: cfg.unit_price ?? null,
    };

    const tr = document.createElement('tr');

    const cMat   = document.createElement('td'); cMat.className = 'p-2';
    const cQty   = document.createElement('td'); cQty.className = 'p-2 text-right w-110';
    const cUnit  = document.createElement('td'); cUnit.className = 'p-2 w-110';
    const cPrice = document.createElement('td'); cPrice.className = 'p-2 text-right w-110';
    const cTotal = document.createElement('td'); cTotal.className = 'p-2 text-right w-110 line-total tabular-nums';
    const cAct   = document.createElement('td'); cAct.className = 'p-2 w-110';

    const unitHidden = document.createElement('input');
    unitHidden.type = 'hidden';
    unitHidden.name = 'rows['+rowIdx+'][unit]';
    unitHidden.value = config.unit || '';

    const qty = document.createElement('input');
    qty.type = 'number'; qty.step='0.001'; qty.min='0';
    qty.value = String(config.qty);
    qty.className = 'input qty w-110';
    qty.name = 'rows['+rowIdx+'][qty]'; qty.required = true;
    qty.addEventListener('input', recalcGrand);

    const price = document.createElement('input');
    price.type='number'; price.step='0.01'; price.min='0';
    price.value = config.unit_price !== null ? String(config.unit_price) : '0';
    price.className='input price w-110';
    price.name = 'rows['+rowIdx+'][unit_price]'; price.required = true;
    price.addEventListener('input', recalcGrand);

    const sel = createMaterialSelect(
      'rows['+rowIdx+'][material_id]',
      unitHidden,
      price,
      config.material_id,
      config.unit_price
    );

    const rm = document.createElement('button');
    rm.type='button'; rm.className='btn btn-muted'; rm.textContent='Remove';
    rm.addEventListener('click', () => { tr.remove(); recalcGrand(); });

    cMat.appendChild(sel);
    cQty.appendChild(qty);
    cUnit.appendChild(document.createTextNode(''));
    cUnit.appendChild(unitHidden);
    cPrice.appendChild(price);
    cTotal.textContent = money((config.qty || 0) * (config.unit_price || 0));
    cAct.appendChild(rm);

    tr.appendChild(cMat);
    tr.appendChild(cQty);
    tr.appendChild(cUnit);
    tr.appendChild(cPrice);
    tr.appendChild(cTotal);
    tr.appendChild(cAct);

    body.appendChild(tr);
    rowIdx++;
    recalcGrand();
  }

  async function loadDefaultsIntoForm(){
    if (!defaultsUrl) return;
    try{
      const res = await fetch(defaultsUrl, { headers: {'X-Requested-With':'XMLHttpRequest'} });
      if (!res.ok) throw new Error('HTTP '+res.status);
      const json = await res.json();

      // Show "defaults loaded from" banner if we know the base product
      const from = json.from || null;
      if (from && defaultsBanner && defaultsBannerName && defaultsBannerLink) {
        defaultsBanner.style.display = 'block';
        defaultsBannerName.textContent = from.name || ('Product #'+from.id);
        defaultsBannerLink.href = '/products/' + from.id;
      }

      let rows = [];
      if (Array.isArray(json)) {
        rows = json;
      } else if (Array.isArray(json.rows)) {
        rows = json.rows;
      } else if (Array.isArray(json.data)) {
        rows = json.data;
      }

      if (!rows.length) {
        if (!body.querySelector('tr')) addRow();
        return;
      }

      body.innerHTML = '';
      rowIdx = 0;

      rows.forEach(r => {
        addRow({
          material_id: r.material_id ?? r.ingredient_id ?? null,
          qty: r.quantity_per_unit ?? r.qty ?? r.quantity ?? 0,
          unit: r.unit ?? '',
          unit_price: r.unit_price_snapshot ?? r.unit_price ?? r.default_unit_price ?? 0,
        });
      });

      recalcGrand();
    } catch (e){
      console.error(e);
      alert('Could not load defaults for this product. You can still add lines manually.');
      if (!body.querySelector('tr')) addRow();
    }
  }

  // initializers
  if (!hasExistingLines && defaultsUrl) {
    // Auto-load defaults for new products of a known type/variant
    loadDefaultsIntoForm();
  } else {
    addRow();
  }

  addBtn && addBtn.addEventListener('click', () => addRow());
  loadDefaultsBtn && loadDefaultsBtn.addEventListener('click', () => {
    loadDefaultsIntoForm();
  });

  loadTypes();

  const form = document.getElementById('add-lines-form');
  form && form.addEventListener('submit', (e) => {
    const rows = body.querySelectorAll('tr');
    if(!rows.length){
      e.preventDefault();
      alert('Add at least one material row.');
      return false;
    }
    rows.forEach(tr => {
      const sel   = tr.querySelector('select.mat');
      const uhid  = tr.querySelector('input[type="hidden"][name*="[unit]"]');
      const opt   = sel ? sel.options[sel.selectedIndex] : null;
      if (opt && uhid && !uhid.value) {
        uhid.value = opt.dataset.unit || '';
      }
    });
  });

  /************ Multi-select for Current Lines ************/
  const selectAll = document.getElementById('selectAll');
  const tbodyCurrent = document.querySelector('#current-table tbody');
  const selected = new Set();
  const bulkBar = document.getElementById('bulkBar');
  const bulkCount = document.getElementById('bulkCount');
  const bulkDelete = document.getElementById('bulkDelete');
  const bulkExportPdf = document.getElementById('bulkExportPdf');

  function rowCheckboxes(){ return Array.from(tbodyCurrent.querySelectorAll('.row-select')); }

  function updateBulkUI(){
    const count = selected.size;
    bulkCount.textContent = count;
    bulkBar.classList.toggle('active', count > 0);
    const boxes = rowCheckboxes();
    const allChecked = boxes.length > 0 && boxes.every(cb => cb.checked);
    if (selectAll) {
      selectAll.checked = allChecked;
      selectAll.indeterminate = count > 0 && !allChecked;
    }
  }

  selectAll?.addEventListener('change', () => {
    rowCheckboxes().forEach(cb => {
      cb.checked = selectAll.checked;
      const id = cb.dataset.lineId;
      if (cb.checked) selected.add(id); else selected.delete(id);
    });
    updateBulkUI();
  });

  tbodyCurrent?.addEventListener('change', (e) => {
    if (!e.target.matches('.row-select')) return;
    const id = e.target.dataset.lineId;
    if (!id) return;
    if (e.target.checked) selected.add(id); else selected.delete(id);
    updateBulkUI();
  });

  bulkDelete?.addEventListener('click', async () => {
    if (selected.size === 0) return;
    if (!confirm(`Delete ${selected.size} selected line(s)?`)) return;
    const ids = Array.from(selected);
    for (let i = 0; i < ids.length; i++){
      const id = ids[i];
      const tr = tbodyCurrent.querySelector(`tr[data-line-id="${id}"]`);
      const form = tr?.querySelector('form.delete-form');
      if (form) form.submit();
      await new Promise(r => setTimeout(r, 120));
    }
  });

  bulkExportPdf?.addEventListener('click', async () => {
    if (selected.size === 0) return;
    try{
      const ids = Array.from(selected);
      const table = document.createElement('table');
      table.style.width = '100%';
      table.style.borderCollapse = 'collapse';
      table.innerHTML = `
        <thead>
          <tr>
            <th style="text-align:left;padding:8px;border:1px solid #e5e7eb;">Material</th>
            <th style="text-align:right;padding:8px;border:1px solid #e5e7eb;">Qty</th>
            <th style="text-align:left;padding:8px;border:1px solid #e5e7eb;">Unit</th>
            <th style="text-align:right;padding:8px;border:1px solid #e5e7eb;">Unit Price</th>
            <th style="text-align:right;padding:8px;border:1px solid #e5e7eb;">Line Total</th>
          </tr>
        </thead>
        <tbody></tbody>
      `;
      const tb = table.querySelector('tbody');

      let grand = 0;
      ids.forEach(id => {
        const tr = tbodyCurrent.querySelector(`tr[data-line-id="${id}"]`);
        if (!tr) return;
        const tds = tr.querySelectorAll('td');
        const row = document.createElement('tr');
        const get = (i) => (tds[i]?.textContent || '').trim();
        const qty = get(2);
        const unit = get(3);
        const price = get(4);
        const total = get(5);
        const num = parseFloat((total || '0').replace(/[^\d.]/g,''));
        grand += isNaN(num) ? 0 : num;

        row.innerHTML = `
          <td style="padding:8px;border:1px solid #e5e7eb;">${get(1)}</td>
          <td style="padding:8px;text-align:right;border:1px solid #e5e7eb;">${qty}</td>
          <td style="padding:8px;border:1px solid #e5e7eb;">${unit}</td>
          <td style="padding:8px;text-align:right;border:1px solid #e5e7eb;">${price}</td>
          <td style="padding:8px;text-align:right;border:1px solid #e5e7eb;">${total}</td>
        `;
        tb.appendChild(row);
      });

      const wrap = document.createElement('div');
      wrap.style.padding = '20px';
      const title = document.createElement('h2');
      title.textContent = 'Selected Recipe Lines';
      title.style.margin = '0 0 10px 0';
      title.style.font = '600 16px/1.25 system-ui, -apple-system, Segoe UI, Roboto, sans-serif';

      const grandEl = document.createElement('div');
      grandEl.textContent = 'Grand Total: ₱ ' + grand.toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2});
      grandEl.style.margin = '10px 0 0 0';
      grandEl.style.font = '600 14px/1.25 system-ui, -apple-system, Segoe UI, Roboto, sans-serif';
      wrap.appendChild(title);
      wrap.appendChild(table);
      wrap.appendChild(grandEl);
      document.body.appendChild(wrap);

      const canvas = await html2canvas(wrap, { backgroundColor:'#ffffff', scale: Math.min(Math.max(window.devicePixelRatio || 1, 2), 3) });
      const { jsPDF } = window.jspdf;
      const pdf = new jsPDF({ orientation:'p', unit:'pt', format:'a4' });
      const pageWidth  = pdf.internal.pageSize.getWidth();
      const imgWidth = pageWidth - 40;
      const imgHeight = (canvas.height * imgWidth) / canvas.width;
      if (imgHeight + 40 <= pdf.internal.pageSize.getHeight()){
        pdf.addImage(canvas.toDataURL('image/jpeg', 0.95), 'JPEG', 20, 20, imgWidth, imgHeight, undefined, 'FAST');
      } else {
        const pageHeight = pdf.internal.pageSize.getHeight() - 40;
        const sliceHeightPx = (pageHeight * canvas.width) / imgWidth;
        let top = 0, page = 0;
        while (top < canvas.height){
          const part = document.createElement('canvas');
          part.width = canvas.width;
          part.height = Math.min(sliceHeightPx, canvas.height - top);
          const ctx = part.getContext('2d');
          ctx.drawImage(canvas, 0, top, part.width, part.height, 0, 0, part.width, part.height);
          const partData = part.toDataURL('image/jpeg', 0.95);
          if (page > 0) pdf.addPage();
          pdf.addImage(partData, 'JPEG', 20, 20, imgWidth, (part.height * imgWidth) / part.width, undefined, 'FAST');
          top += sliceHeightPx; page++;
        }
      }
      const d = new Date();
      pdf.save(`Recipe_Selected_${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}.pdf`);
      document.body.removeChild(wrap);
    }catch(e){
      console.error(e);
      alert('Export failed. Please try again.');
    }
  });

})();
</script>
@endsection
