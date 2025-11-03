{{-- resources/views/sales/edit.blade.php --}}
@extends('layout.mainlayout')

@section('title', 'Edit Sale')

@section('head')
<style>
  /* ===== Glassy / Glow theme tuned for LIGHT UI with dark text ===== */
  :root{
    --ink:#0f172a;           /* near-black */
    --muted:#475569;         /* slate-600 */
    --line:#e2e8f0;          /* slate-200 */
    --card:rgba(255,255,255,.92);
    --glass:rgba(255,255,255,.72);
    --accent:#10b981;        /* emerald */
    --accent-2:#2563eb;      /* blue */
    --accent-3:#f59e0b;      /* amber */
    --danger:#ef4444;        /* red */
    --ok:#22c55e;            /* green */
    --bg:linear-gradient(180deg,#f8fafc 0%,#eef2ff 50%,#f0fdf4 100%);
  }
  body{ color:var(--ink); }

  .hero-band{
    min-height:18vh; border-radius:16px;
    background:var(--bg);
  }

  .glass{
    background:var(--glass);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border:1px solid rgba(148,163,184,.35);
    box-shadow:
      0 10px 30px rgba(2,6,23,.06),
      inset 0 1px 0 rgba(255,255,255,.35);
  }
  .card{
    background:var(--card);
    border:1px solid var(--line);
    border-radius:16px;
    box-shadow:0 12px 24px rgba(2,6,23,.06);
  }
  .glow-ring{ position:relative; }
  .glow-ring::before{
    content:""; position:absolute; inset:-2px; border-radius:18px;
    background: conic-gradient(from 180deg at 50% 50%, #34d399, #60a5fa, #f59e0b, #34d399);
    filter: blur(12px); opacity:.28; z-index:-1;
  }

  .label{ color:var(--ink); font-weight:600; }
  .help{ color:var(--muted); font-size:.78rem; }

  .chip{
    display:inline-flex; align-items:center; gap:.35rem;
    padding:.28rem .55rem; border-radius:999px;
    background:#f8fafc; border:1px solid var(--line); color:var(--ink);
    font-weight:700; font-size:.72rem;
  }
  .chip-ok{ border-color:rgba(16,185,129,.35); background:#ecfdf5; }
  .chip-warn{ border-color:rgba(245,158,11,.35); background:#fffbeb; }
  .chip-bad{ border-color:rgba(239,68,68,.35); background:#fef2f2; }
  .chip-soft{ background:#ffffff; border:1px dashed var(--line); font-weight:600; }

  .input{
    width:100%; color:var(--ink);
    background:rgba(255,255,255,.9);
    border:1px solid var(--line);
    border-radius:12px; padding:.7rem .9rem;
    transition: box-shadow .15s, border-color .15s, transform .12s;
  }
  .input::placeholder{ color:#94a3b8; }
  .input:hover{ border-color:#cbd5e1; }
  .input:focus{
    outline:0;
    border-color:#93c5fd;
    box-shadow:0 0 0 3px rgba(37,99,235,.18);
    transform: translateY(-1px);
  }
  select.input{ appearance:none; background-position:right .8rem center; background-repeat:no-repeat; }

  .btn{ display:inline-flex; align-items:center; justify-content:center; gap:.55rem; font-weight:800; border-radius:12px; padding:.7rem 1rem; }
  .btn-primary{ background:linear-gradient(90deg,#10b981,#22c55e); color:#fff; border:1px solid rgba(16,185,129,.35); }
  .btn-primary:hover{ filter:brightness(.97); }
  .btn-ghost{ background:#ffffff; color:var(--ink); border:1px solid var(--line); }
  .btn-ghost:hover{ background:#f8fafc; }
  .btn-danger{ background: linear-gradient(90deg,#ef4444,#f97316); color:#fff; }

  .grid-2{ display:grid; grid-template-columns:1fr; gap:1rem; }
  .grid-3{ display:grid; grid-template-columns:1fr; gap:1rem; }
  @media (min-width:640px){ .grid-2{ grid-template-columns:1fr 1fr; } }
  @media (min-width:768px){ .grid-3{ grid-template-columns:1fr 1fr 1fr; } }

  .divider{ height:1px; background:var(--line); margin:1rem 0; opacity:.6; }

  /* Change log panel (light) */
  .change-card{ border: 1px solid var(--line); background:#ffffff; border-radius:14px; padding:.75rem .9rem; }
  .change-title{ color:var(--ink); font-weight:700; display:flex; align-items:center; gap:.6rem; }
  .badge{
    display:inline-flex; align-items:center; justify-content:center;
    min-width:1.5rem; height:1.3rem; border-radius:999px;
    font-size:.75rem; padding:0 .4rem; background:var(--accent); color:#fff;
  }
  .change-list{ margin-top:.6rem; color:#0f172a; font-size:.9rem; max-height:220px; overflow:auto; }
  .change-row{ display:flex; flex-direction:column; gap:.15rem; padding:.4rem .45rem; border-radius:8px; }
  .change-row:nth-child(odd){ background:#f8fafc; }
  .oldval{ color:#b91c1c; }
  .newval{ color:#047857; }

  /* Toasts (light) */
  .toast-wrap{ position:fixed; top:16px; right:16px; z-index:9999; display:flex; flex-direction:column; gap:10px; }
  .toast{
    border-radius:12px; padding:.7rem .9rem; min-width:260px;
    border:1px solid var(--line); color:#0f172a; background:#ffffff;
    box-shadow:0 10px 20px rgba(2,6,23,.08);
    display:flex; gap:.6rem; align-items:flex-start; animation: slideIn .25s ease-out;
  }
  .toast-success{ border-color:rgba(16,185,129,.35); background:#ecfdf5; }
  .toast-error{ border-color:rgba(239,68,68,.35); background:#fef2f2; }
  .toast-info{ border-color:rgba(59,130,246,.35); background:#eff6ff; }
  .toast-title{ font-weight:800; }
  .toast-close{ cursor:pointer; opacity:.8; }
  @keyframes slideIn { from { transform: translateY(-10px); opacity:.5; } to { transform: translateY(0); opacity:1; } }

  /* Drawer */
  .drawer{
    position: fixed; top:0; right:-420px; width:420px; height:100vh; z-index:9998;
    background:#ffffff; border-left:1px solid var(--line);
    box-shadow:-12px 0 24px rgba(2,6,23,.08);
    transition:right .28s ease-in-out;
    display:flex; flex-direction:column;
  }
  .drawer.open{ right:0; }
  .drawer-header{ padding:.8rem 1rem; border-bottom:1px solid var(--line); display:flex; align-items:center; justify-content:space-between; }
  .drawer-body{ flex:1; }
  .drawer iframe{ width:100%; height:100%; border:0; }
</style>
@endsection

@section('content')
<div class="hero-band"></div>

<div class="max-w-5xl mx-auto -mt-14 px-4 pb-10">
  {{-- Header --}}
  <div class="glass glow-ring rounded-2xl p-6 mb-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h2 class="text-2xl font-extrabold tracking-tight">Edit Sale</h2>
        <p class="help mt-1">Update sale details, link a batch, and ensure pricing & expiry are correct.</p>
      </div>
      <div class="flex items-center gap-2">
        @php $invoice = $sale->invoice_number ?? $sale->order_number ?? '—'; @endphp
        <span class="chip">Invoice: {{ $invoice }}</span>
        <button type="button" class="btn btn-ghost" id="btnPreviewDrawer" title="Quick receipt preview">Quick Preview</button>
        <a href="{{ route('sales.receipt', $sale) }}" class="btn btn-ghost">View Receipt</a>
      </div>
    </div>
  </div>

  {{-- Flash + Errors (light) --}}
  @if (session('success'))
    <div class="card p-4 mb-4 text-green-700 border-green-200"><strong>Success:</strong> {{ session('success') }}</div>
  @endif
  @if (session('error'))
    <div class="card p-4 mb-4 text-red-700 border-red-200"><strong>Error:</strong> {{ session('error') }}</div>
  @endif
  @if (session('info'))
    <div class="card p-4 mb-4 text-blue-700 border-blue-200"><strong>Note:</strong> {{ session('info') }}</div>
  @endif
  @if ($errors->any())
    <div class="card p-4 mb-4 text-red-700 border-red-200">
      <div class="font-semibold mb-1">Please fix the following:</div>
      <ul class="list-disc list-inside">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  @php
    $initOrder = \Carbon\Carbon::parse($sale->order_date ?? $sale->date)->toDateString();
    $qtyInit   = (float) ($sale->quantity_kg ?? $sale->quantity ?? 0);
    $unitInit  = (float) ($sale->unit_price ?? $sale->price ?? 0);
    $totInit   = (float) ($sale->total_price ?? $sale->total ?? ($qtyInit*$unitInit));

    $unitTypeCol  = \Illuminate\Support\Facades\Schema::hasColumn('sales','unit_type') ? 'unit_type'
                  : (\Illuminate\Support\Facades\Schema::hasColumn('sales','unit') ? 'unit' : null);
    $unitTypeInit = $unitTypeCol ? ($sale->{$unitTypeCol} ?? '') : '';
    $unitTypeInit = in_array($unitTypeInit, ['kg','pack','bag'], true) ? $unitTypeInit : '';
    $typeLabelInit = $sale->type_label ?? ($sale->production->product_name_snapshot ?? '');
  @endphp

  {{-- Main --}}
  <div class="grid gap-5 md:grid-cols-[1fr,320px]">
    <div class="card p-6 sm:p-7">
      <form method="POST" action="{{ route('sales.update', $sale) }}" id="saleEditForm" class="space-y-4" novalidate>
        @csrf
        @method('PUT')
        <input type="hidden" name="change_log" id="change_log" value="[]">

        {{-- Top row --}}
        <div class="grid-3">
          <div>
            <label class="label" for="product_id">Product</label>
            <select name="product_id" id="product_id" class="input" data-initial="{{ (int)$sale->product_id }}" aria-describedby="help_product">
              @foreach($products as $p)
                <option value="{{ $p->id }}"
                        data-shelf="{{ (int)($p->shelf_life_days ?? 0) }}"
                        data-price="{{ is_numeric($p->unit_cost ?? null) ? (float)$p->unit_cost : 0 }}"
                        {{ (int)$sale->product_id === (int)$p->id ? 'selected' : '' }}>
                  {{ $p->product_name }}
                </option>
              @endforeach
            </select>
            <div id="help_product" class="help mt-1">Switching product refreshes batches, availability, and suggested price.</div>
            @error('product_id')<div class="help mt-1 text-red-600">{{ $message }}</div>@enderror
          </div>

          <div>
            <div class="flex items-center justify-between">
              <label class="label" for="production_id">Batch (Production)</label>
              <span id="batch_badge" class="chip chip-soft" style="display:none;">price source: —</span>
            </div>
            <select name="production_id" id="production_id" class="input" data-initial="{{ (int)($sale->production_id ?? 0) }}" aria-describedby="help_batch">
              <option value="">— Auto-pick by date —</option>
              @foreach($batches as $b)
                <option value="{{ $b->id }}"
                  data-pdate="{{ \Carbon\Carbon::parse($b->production_date)->toDateString() }}"
                  data-edate="{{ $b->expiration_date ? \Carbon\Carbon::parse($b->expiration_date)->toDateString() : '' }}"
                  data-upack="{{ is_numeric($b->unit_price_pack ?? null) ? (float)$b->unit_price_pack : '' }}"
                  data-ubag="{{ is_numeric($b->unit_price_bag ?? null) ? (float)$b->unit_price_bag : '' }}"
                  data-snap="{{ $b->product_name_snapshot ?? '' }}"
                  {{ (int)$sale->production_id === (int)$b->id ? 'selected' : '' }}>
                  {{ $b->batch_number }} — {{ \Carbon\Carbon::parse($b->production_date)->format('Y-m-d') }}
                </option>
              @endforeach
            </select>
            <div id="help_batch" class="help mt-1">If empty, the nearest batch on/before the order date will be linked.</div>
            @error('production_id')<div class="help mt-1 text-red-600">{{ $message }}</div>@enderror
          </div>

          <div>
            <label class="label" for="status">Status</label>
            <select name="status" id="status" class="input" data-initial="{{ $sale->status ?? '' }}" aria-describedby="help_status">
              @foreach($statusOptions as $st)
                <option value="{{ $st }}" {{ ($sale->status ?? '') === $st ? 'selected' : '' }}>{{ $st }}</option>
              @endforeach
            </select>
            <div id="help_status" class="help mt-1">Set to “Paid” when fully collected; “Completed” when delivered.</div>
            @error('status')<div class="help mt-1 text-red-600">{{ $message }}</div>@enderror
          </div>
        </div>

        <div class="divider"></div>

        {{-- Dates + expiry awareness --}}
        <div class="grid-3 items-end">
          <div>
            <label class="label" for="order_date">Order Date</label>
            <input type="date" name="date" id="order_date" class="input"
                   value="{{ $initOrder }}" data-initial="{{ $initOrder }}" aria-describedby="help_date">
            <div id="help_date" class="help mt-1">Used to auto-pick batch when none is selected.</div>
            @error('date')<div class="help mt-1 text-red-600">{{ $message }}</div>@enderror
          </div>

          <div>
            <label class="label" for="production_date">Production Date</label>
            <input type="date" name="production_date" id="production_date" class="input"
                   value="{{ $productionDate }}" data-initial="{{ $productionDate }}" aria-describedby="help_pdate">
            <div id="help_pdate" class="help mt-1">Auto-filled from batch; you may override.</div>
            @error('production_date')<div class="help mt-1 text-red-600">{{ $message }}</div>@enderror
          </div>

          <div>
            <div class="flex items-center justify-between">
              <label class="label" for="expiration_date">Expiration Date</label>
              <span id="expiry_chip" class="chip chip-ok" style="display:none;">0 days left</span>
            </div>
            <input type="date" name="expiration_date" id="expiration_date" class="input"
                   value="{{ $expirationDate }}" data-initial="{{ $expirationDate }}" aria-describedby="help_edate">
            <div id="help_edate" class="help mt-1">From batch (or computed via shelf life when missing).</div>
            @error('expiration_date')<div class="help mt-1 text-red-600">{{ $message }}</div>@enderror
          </div>
        </div>

        <div class="divider"></div>

        {{-- Numbers + unit --}}
        <div class="grid-3">
          <div>
            <label class="label" for="quantity">Quantity <span class="help">(kg)</span></label>
            <input type="number" step="0.001" min="0.001" name="quantity" id="quantity" class="input"
                   value="{{ number_format($qtyInit, 3, '.', '') }}" data-initial="{{ number_format($qtyInit, 3, '.', '') }}" aria-describedby="help_qty">
            <div id="help_qty" class="help mt-1">Friendly guard caps at 5000 kg and warns if exceeded.</div>
            @error('quantity')<div class="help mt-1 text-red-600">{{ $message }}</div>@enderror
          </div>

          <div>
            <label class="label" for="price">Unit Price <span id="unit_price_hint" class="help">(per kg)</span></label>
            <input type="number" step="0.01" min="0" name="price" id="price" class="input"
                   value="{{ number_format($unitInit, 2, '.', '') }}" data-initial="{{ number_format($unitInit, 2, '.', '') }}" aria-describedby="help_price">
            <div id="help_price" class="help mt-1">Price may auto-fill from batch pack/bag or product price.</div>
            <div class="help mt-1" id="availHelp" aria-live="polite"></div>
            @error('price')<div class="help mt-1 text-red-600">{{ $message }}</div>@enderror
          </div>

          <div>
            <label class="label" for="unit_type">Unit Type</label>
            <select name="unit_type" id="unit_type" class="input" data-initial="{{ $unitTypeInit }}" aria-describedby="help_utype">
              <option value="">— None —</option>
              @foreach(($unitTypeOptions ?? ['kg','pack','bag']) as $opt)
                <option value="{{ $opt }}" {{ $unitTypeInit === $opt ? 'selected' : '' }}>
                  {{ $opt === 'kg' ? 'Per kg' : ($opt === 'pack' ? 'Per pack' : 'Per bag') }}
                </option>
              @endforeach
            </select>
            <div id="help_utype" class="help mt-1">Unit price and receipt label follow this selection.</div>
            @error('unit_type')<div class="help mt-1 text-red-600">{{ $message }}</div>@enderror
          </div>
        </div>

        <div class="divider"></div>

        {{-- Type label + total --}}
        <div class="grid-2">
          <div>
            <label class="label" for="type_label">Type Label <span class="help">(optional)</span></label>
            <input type="text" name="type_label" id="type_label" class="input"
                   value="{{ $typeLabelInit }}" data-initial="{{ $typeLabelInit }}" aria-describedby="help_typelabel">
            <div id="help_typelabel" class="help mt-1">Shown in receipts and dashboards. If blank, batch snapshot may be used.</div>
            @error('type_label')<div class="help mt-1 text-red-600">{{ $message }}</div>@enderror
          </div>
          <div>
            <label class="label" for="total_display">Total</label>
            <input type="text" id="total_display" class="input" value="₱ {{ number_format($totInit, 2) }}" disabled aria-describedby="help_total">
            <div id="help_total" class="help mt-1">Auto-calculated as quantity × unit price.</div>
          </div>
        </div>

        <div class="divider"></div>

        {{-- Customer + Notes --}}
        <div class="grid-2">
          <div>
            <label class="label" for="customer_name">Customer (optional)</label>
            <input type="text" name="customer_name" id="customer_name" class="input"
                   value="{{ $sale->customer_name }}" data-initial="{{ $sale->customer_name }}">
          </div>

          <div>
            <label class="label" for="notes">Internal Notes</label>
            <input type="text" name="notes" id="notes" class="input"
                   value="{{ $sale->notes }}" data-initial="{{ $sale->notes }}">
          </div>
        </div>

        {{-- Actions --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-2">
          <a href="{{ route('sales.index') }}" class="btn btn-ghost">← Back to Sales</a>
          <div class="flex items-center gap-2">
            <button type="submit" class="btn btn-primary" id="btnSave">Save Changes</button>
            <a href="{{ route('sales.receipt', $sale) }}" class="btn btn-ghost">Preview Receipt</a>
          </div>
        </div>
      </form>
    </div>

    {{-- Side: Change Log --}}
    <div class="card p-5 h-max">
      <div class="change-card">
        <div class="change-title">
          Change Log <span class="badge" id="changeCount">0</span>
        </div>
        <div class="help mt-1">This panel updates live as you edit.</div>
        <div class="change-list" id="changeList">
          <div class="help">No changes yet.</div>
        </div>
      </div>
    </div>
  </div>

  {{-- Drawer: Receipt Preview --}}
  <aside class="drawer" id="receiptDrawer" aria-label="Receipt preview drawer">
    <div class="drawer-header">
      <div class="font-semibold">Receipt Preview</div>
      <button type="button" class="btn btn-ghost" id="btnCloseDrawer" title="Close">Close</button>
    </div>
    <div class="drawer-body">
      <iframe id="receiptFrame" src="{{ route('sales.receipt', $sale) }}" title="Receipt preview"></iframe>
    </div>
  </aside>

  {{-- Toast container (ARIA live) --}}
  <div class="toast-wrap" id="toastWrap" aria-live="polite" aria-atomic="true"></div>
</div>

@endsection

@push('scripts')
<script>
  (function(){
    const productSel   = document.getElementById('product_id');
    const batchSel     = document.getElementById('production_id');
    const orderDateEl  = document.getElementById('order_date');
    const prodDateEl   = document.getElementById('production_date');
    const expDateEl    = document.getElementById('expiration_date');
    const qtyEl        = document.getElementById('quantity');
    const priceEl      = document.getElementById('price');
    const unitTypeEl   = document.getElementById('unit_type');
    const typeLabelEl  = document.getElementById('type_label');
    const totalEl      = document.getElementById('total_display');
    const availHelp    = document.getElementById('availHelp');
    const changeLogEl  = document.getElementById('change_log');
    const formEl       = document.getElementById('saleEditForm');
    const unitPriceHint= document.getElementById('unit_price_hint');
    const expiryChip   = document.getElementById('expiry_chip');
    const batchBadge   = document.getElementById('batch_badge');

    const toastWrap    = document.getElementById('toastWrap');
    const changeList   = document.getElementById('changeList');
    const changeCount  = document.getElementById('changeCount');

    const drawer       = document.getElementById('receiptDrawer');
    const btnDrawer    = document.getElementById('btnPreviewDrawer');
    const btnCloseDr   = document.getElementById('btnCloseDrawer');
    const receiptFrame = document.getElementById('receiptFrame');

    let submitting = false;

    function fmt(n, d){ return Number(n||0).toFixed(d); }
    function computeTotal(){
      const q = parseFloat(qtyEl.value || '0');
      const p = parseFloat(priceEl.value || '0');
      totalEl.value = '₱ ' + (q*p).toFixed(2);
    }

    function addDays(iso, days){
      if (!iso) return '';
      const dt = new Date(iso + 'T00:00:00');
      dt.setDate(dt.getDate() + days);
      return dt.toISOString().slice(0,10);
    }
    function daysLeft(iso){
      if(!iso) return null;
      const d = new Date(iso+'T00:00:00');
      const today = new Date(); today.setHours(0,0,0,0);
      const diff = Math.ceil((d - today) / 86400000);
      return diff;
    }
    function updateExpiryChip(){
      const ed = expDateEl.value;
      const dl = daysLeft(ed);
      if (dl === null) { expiryChip.style.display='none'; return; }
      expiryChip.style.display='inline-flex';
      expiryChip.textContent = (dl >= 0 ? dl : Math.abs(dl)) + ' day' + (Math.abs(dl)===1?'':'s') + (dl>=0?' left':' past');
      expiryChip.className = 'chip ' + (dl < 0 ? 'chip-bad' : dl <= 3 ? 'chip-warn' : 'chip-ok');
    }
    function getSelectedShelfLife(){
      const opt = productSel.options[productSel.selectedIndex];
      return parseInt(opt?.dataset?.shelf || '0', 10) || 0;
    }

    /* ---------- Toasts ---------- */
    function showToast(type, title, message){
      const div = document.createElement('div');
      div.className = 'toast ' + (type === 'error' ? 'toast-error' : type === 'success' ? 'toast-success' : 'toast-info');
      div.innerHTML = `
        <div style="line-height:1">
          <div class="toast-title">${title}</div>
          <div class="toast-msg" style="opacity:.9">${message || ''}</div>
        </div>
        <div class="toast-close" aria-label="Close">✕</div>
      `;
      div.querySelector('.toast-close').onclick = () => div.remove();
      toastWrap.appendChild(div);
      setTimeout(() => { div.remove(); }, 4500);
    }

    /* ---------- Change Log ---------- */
    const trackIds = [
      'product_id','production_id','status','order_date',
      'production_date','expiration_date','quantity','price','unit_type',
      'type_label','customer_name','notes'
    ];
    const niceName = {
      product_id: 'Product',
      production_id: 'Batch',
      status: 'Status',
      order_date: 'Order Date',
      production_date: 'Production Date',
      expiration_date: 'Expiration Date',
      quantity: 'Quantity (kg)',
      price: 'Unit Price',
      unit_type: 'Unit Type',
      type_label: 'Type Label',
      customer_name: 'Customer',
      notes: 'Notes'
    };
    const getVal = (id) => {
      const el = document.getElementById(id);
      if (!el) return '';
      if (el.tagName === 'SELECT') {
        if (id === 'product_id') return el.options[el.selectedIndex]?.text || '';
        if (id === 'production_id') {
          const v = el.value || '';
          if (!v) return '';
          const opt = el.options[el.selectedIndex];
          return (opt?.text || '').trim();
        }
        if (id === 'unit_type') {
          const v = el.value || '';
          if (!v) return '';
          return v === 'kg' ? 'Per kg' : v === 'pack' ? 'Per pack' : 'Per bag';
        }
        return el.value || '';
      }
      if (id === 'quantity') return fmt(el.value || '0', 3);
      if (id === 'price')    return fmt(el.value || '0', 2);
      return (el.value || '').toString();
    };
    const getInitial = (id) => {
      const el = document.getElementById(id);
      if (!el) return '';
      let init = el.getAttribute('data-initial') || '';
      if (el.tagName === 'SELECT') {
        if (id === 'product_id') {
          const opt = Array.from(el.options).find(o => String(o.value) === String(init));
          return opt ? opt.text : '';
        }
        if (id === 'production_id') {
          if (!init || init === '0') return '';
          const opt = Array.from(el.options).find(o => String(o.value) === String(init));
          return opt ? opt.text : '';
        }
        if (id === 'unit_type') {
          if (!init) return '';
          return init === 'kg' ? 'Per kg' : init === 'pack' ? 'Per pack' : 'Per bag';
        }
      }
      if (id === 'quantity') return fmt(init || '0', 3);
      if (id === 'price')    return fmt(init || '0', 2);
      return init;
    };

    function collectChanges(){
      const changes = [];
      trackIds.forEach(id => {
        const oldV = getInitial(id);
        const newV = getVal(id);
        if ((oldV || '') !== (newV || '')) {
          changes.push({ field: id, old: oldV || null, new: newV || null });
        }
      });
      return changes;
    }

    function renderChanges(){
      const changes = collectChanges();
      changeList.innerHTML = '';
      changeCount.textContent = changes.length;
      if (changes.length === 0){
        changeList.innerHTML = '<div class="help">No changes yet.</div>';
        return;
      }
      changes.forEach(ch => {
        const row = document.createElement('div');
        row.className = 'change-row';
        row.innerHTML = `
          <div><strong>${niceName[ch.field] || ch.field}</strong></div>
          <div><span class="oldval">Old:</span> ${escapeHtml(ch.old ?? '—')}</div>
          <div><span class="newval">New:</span> ${escapeHtml(ch.new ?? '—')}</div>
        `;
        changeList.appendChild(row);
      });
    }

    function escapeHtml(str){
      return String(str).replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s]));
    }

    /* ---------- Controller-aligned behaviors ---------- */

    function updateBatchBadge(){
      const opt = batchSel.options[batchSel.selectedIndex];
      if(!opt){ batchBadge.style.display='none'; return; }
      const up = opt?.dataset?.upack;
      const ub = opt?.dataset?.ubag;
      if (up || ub){
        batchBadge.style.display='inline-flex';
        batchBadge.textContent = `price source: ${(up?('pack ₱'+fmt(up,2)):'–')} | ${(ub?('bag ₱'+fmt(ub,2)):'–')}`;
      } else {
        batchBadge.style.display='inline-flex';
        batchBadge.textContent = 'price source: —';
      }
    }

    function syncUnitPriceFromContext(){
      const utype = unitTypeEl.value;
      const opt   = batchSel.options[batchSel.selectedIndex];
      const prodOpt = productSel.options[productSel.selectedIndex];
      let price = priceEl.value ? parseFloat(priceEl.value) : 0;

      unitPriceHint.textContent = utype === 'pack' ? '(per pack)'
                             : utype === 'bag'  ? '(per bag)'
                             : '(per kg)';

      // don't override if user already typed a price
      if (price > 0) { updateBatchBadge(); return; }

      if (opt){
        const up = opt?.dataset?.upack, ub = opt?.dataset?.ubag;
        if (utype === 'pack' && up) { priceEl.value = fmt(up,2); computeTotal(); updateBatchBadge(); return; }
        if (utype === 'bag'  && ub) { priceEl.value = fmt(ub,2); computeTotal(); updateBatchBadge(); return; }
        if (!utype && up) { priceEl.value = fmt(up,2); computeTotal(); updateBatchBadge(); return; }
        if (!utype && ub) { priceEl.value = fmt(ub,2); computeTotal(); updateBatchBadge(); return; }
      }

      const prodPrice = parseFloat(prodOpt?.dataset?.price || '0');
      if (prodPrice > 0) { priceEl.value = fmt(prodPrice,2); computeTotal(); updateBatchBadge(); return; }

      refreshAvailable(); // fallback
      updateBatchBadge();
    }

    async function refreshBatches(){
      const pid = productSel.value;
      if (!pid) return;
      const url = "{{ route('production.api.byProduct', 0) }}".replace('/0', '/' + pid);
      const prior = batchSel.value;
      batchSel.innerHTML = '<option value="">— Auto-pick by date —</option>';
      try{
        const r = await fetch(url, { headers: { 'X-Requested-With':'XMLHttpRequest' }});
        const data = await r.json();
        (data || []).forEach(b => {
          const opt = document.createElement('option');
          opt.value = b.id;
          opt.dataset.pdate = b.production_date || '';
          opt.dataset.edate = b.expiration_date || '';
          opt.dataset.upack = (b.unit_price_pack ?? '') === '' ? '' : b.unit_price_pack;
          opt.dataset.ubag  = (b.unit_price_bag ?? '')  === '' ? '' : b.unit_price_bag;
          opt.dataset.snap  = b.product_name_snapshot || '';
          opt.textContent   = `${b.batch_number} — ${(b.production_date || '').slice(0,10)}`;
          batchSel.appendChild(opt);
        });
        if (prior && Array.from(batchSel.options).some(o => o.value === prior)){
          batchSel.value = prior;
        }
        renderChanges();
        syncUnitPriceFromContext();
      }catch(e){ console.error('Batch load failed', e); }
    }

    async function refreshAvailable(){
      const pid = productSel.value;
      if (!pid) { availHelp.textContent = ''; return; }
      try{
        const r = await fetch("{{ route('sales.available') }}", {
          method:'POST',
          headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':'{{ csrf_token() }}' },
          body: JSON.stringify({ product_id: parseInt(pid,10) })
        });
        const data = await r.json();
        if (typeof data?.price === 'number'){
          if (!priceEl.value || parseFloat(priceEl.value) <= 0){
            priceEl.value = fmt(data.price, 2);
            computeTotal();
          }
        }
        if (typeof data?.available === 'number'){
          availHelp.textContent = `Available: ${fmt(data.available,3)} kg`;
        }
      }catch(e){
        console.error('Available fetch failed', e);
        availHelp.textContent = '';
      }
    }

    function onBatchChange(){
      const opt = batchSel.options[batchSel.selectedIndex];
      const pdate = opt?.dataset?.pdate || '';
      const edate = opt?.dataset?.edate || '';
      const snap  = opt?.dataset?.snap || '';

      if (typeLabelEl && (!typeLabelEl.value || typeLabelEl.value.trim()==='') && snap){
        typeLabelEl.value = snap;
      }

      if (pdate){ prodDateEl.value = (pdate || '').slice(0,10); }
      if (edate){
        expDateEl.value = (edate || '').slice(0,10);
      } else if (pdate){
        const shelf = getSelectedShelfLife();
        if (shelf > 0) expDateEl.value = addDays((pdate || '').slice(0,10), shelf);
      }
      updateExpiryChip();
      syncUnitPriceFromContext();
      renderChanges();
    }

    function onUnitTypeChange(){
      syncUnitPriceFromContext();
      renderChanges();
    }

    function onProdDateChange(){
      const shelf = getSelectedShelfLife();
      if (shelf > 0 && prodDateEl.value){
        if (!expDateEl.value || expDateEl.value < prodDateEl.value){
          expDateEl.value = addDays(prodDateEl.value, shelf);
        }
      }
      updateExpiryChip();
      renderChanges();
    }

    productSel?.addEventListener('change', async () => {
      await refreshBatches();
      await refreshAvailable();
      const shelf = getSelectedShelfLife();
      if (shelf > 0 && prodDateEl.value && !expDateEl.value){
        expDateEl.value = addDays(prodDateEl.value, shelf);
      }
      updateExpiryChip();
    });

    orderDateEl?.addEventListener('change', renderChanges);
    batchSel?.addEventListener('change', onBatchChange);
    unitTypeEl?.addEventListener('change', onUnitTypeChange);

    qtyEl?.addEventListener('input', () => {
      // Inline guard: friendly warning beyond 5000 kg
      const q = parseFloat(qtyEl.value || '0');
      if (q > 5000){
        showToast('info','Large quantity','Heads up: quantity above 5000 kg. Please double-check.');
      }
      computeTotal(); renderChanges();
    });
    priceEl?.addEventListener('input', () => { computeTotal(); renderChanges(); });

    prodDateEl?.addEventListener('change', onProdDateChange);
    expDateEl?.addEventListener('change', () => { updateExpiryChip(); renderChanges(); });

    document.getElementById('customer_name')?.addEventListener('input', renderChanges);
    document.getElementById('notes')?.addEventListener('input', renderChanges);
    document.getElementById('status')?.addEventListener('change', renderChanges);
    typeLabelEl?.addEventListener('input', renderChanges);

    // Soft "unsaved changes" guard
    window.addEventListener('beforeunload', (e) => {
      if (submitting) return;
      if (collectChanges().length > 0){
        e.preventDefault();
        e.returnValue = '';
      }
    });

    // Keyboard flows: Ctrl/Cmd+S to save, Esc to close drawer or blur
    document.addEventListener('keydown', (e) => {
      const isMac = navigator.platform.toUpperCase().indexOf('MAC')>=0;
      if ((isMac && e.metaKey && e.key.toLowerCase()==='s') || (!isMac && e.ctrlKey && e.key.toLowerCase()==='s')){
        e.preventDefault();
        document.getElementById('btnSave')?.click();
      }
      if (e.key === 'Escape'){
        if (drawer.classList.contains('open')) {
          drawer.classList.remove('open');
        } else {
          (document.activeElement || {}).blur?.();
        }
      }
    });

    // Drawer controls
    btnDrawer?.addEventListener('click', () => {
      // refresh preview to reflect unsaved inputs? keep existing route preview
      drawer.classList.add('open');
      receiptFrame?.focus();
    });
    btnCloseDr?.addEventListener('click', () => drawer.classList.remove('open'));

    // Before submit: serialize change log & disable leave guard
    formEl.addEventListener('submit', () => {
      const changes = collectChanges();
      changeLogEl.value = JSON.stringify(changes);
      submitting = true;
    });

    // Initial
    computeTotal();
    refreshAvailable();
    updateExpiryChip();
    renderChanges();
    updateBatchBadge();

    (function initHint(){
      const utype = unitTypeEl.value;
      unitPriceHint.textContent = utype === 'pack' ? '(per pack)' : utype === 'bag' ? '(per bag)' : '(per kg)';
    })();

    /* ---------- Flash → toast bridge ---------- */
    @if ($errors->any())
      @foreach ($errors->all() as $err)
        showToast('error','Validation error','{{ addslashes($err) }}');
      @endforeach
    @endif
    @if (session('success'))
      showToast('success','Saved','{{ addslashes(session('success')) }}');
    @endif
    @if (session('error'))
      showToast('error','Error','{{ addslashes(session('error')) }}');
    @endif
    @if (session('info'))
      showToast('info','Note','{{ addslashes(session('info')) }}');
    @endif
  })();
</script>
@endpush
