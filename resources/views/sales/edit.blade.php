{{-- resources/views/sales/edit.blade.php --}}
@extends('layout.mainlayout')

@section('head')
  {{-- Fonts + local styles for liquid theme --}}
  <link href="https://fonts.googleapis.com/css2?family=Jost:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body, p, ul, li, a, button, input, select, textarea, label { font-family: 'Jost', system-ui, -apple-system, Segoe UI, Roboto, sans-serif; }
    .liquid-wrap { min-height: 100vh; background: linear-gradient(135deg,#1F1E1E 0%, #001C00 100%); }
    .liquid-card {
      position: relative; overflow: hidden; border-radius: 20px;
      background: linear-gradient(135deg, rgba(31,30,30,.95), rgba(0,28,0,.78));
      border: .5px solid rgba(255,255,255,.2);
      box-shadow: 0 10px 32px rgba(0,0,0,.45);
      backdrop-filter: blur(8px);
    }
    .liquid-card::before{
      content:''; position:absolute; inset:0; pointer-events:none;
      background: linear-gradient(45deg, rgba(4,119,5,.10), rgba(237,209,0,.10), rgba(4,119,5,.10));
      animation: cardShine 8s ease-in-out infinite;
    }
    @keyframes cardShine { 0%{opacity:.35} 50%{opacity:.18} 100%{opacity:.35} }

    .label { color:#e5e7eb; font-size:.9rem; margin-bottom:.35rem; display:block; }
    .help { color:#9ca3af; font-size:.8rem; }
    .divider{ height:1px; background:rgba(255,255,255,.12); margin:1rem 0; }
    .tag{
      display:inline-flex; align-items:center; gap:.4rem; padding:.25rem .55rem;
      font-size:.72rem; border-radius:999px; border:1px solid rgba(255,255,255,.2); color:#cbd5e1;
      background: rgba(255,255,255,.06);
    }

    .liquid-input{
      background: rgba(255,255,255,.06);
      border: 1px solid rgba(255,255,255,.2);
      color:#fff; border-radius:12px; padding:.6rem .75rem; width:100%;
    }
    .liquid-input::placeholder{ color: rgba(255,255,255,.6) }
    .liquid-input:focus{ outline: none; border-color:#047705; box-shadow:0 0 0 2px rgba(4,119,5,.35); }

    .btn-ghost{
      border:1px solid rgba(255,255,255,.15); color:#f8fafc;
      background: rgba(255,255,255,.06); border-radius:12px;
      padding:.55rem 1rem; transition:.2s;
    }
    .btn-ghost:hover{ background:rgba(255,255,255,.1); transform: translateY(-1px); }
    .btn-primary{
      background: linear-gradient(90deg,#047705 0%, #0aad0a 100%);
      color:#fff; border:1px solid rgba(255,255,255,.15);
      border-radius:12px; padding:.6rem 1.1rem;
      box-shadow:0 6px 18px rgba(4,119,5,.35);
      transition:.2s; font-weight:600;
    }
    .btn-primary:hover{ transform: translateY(-1px); }

    .grid-2 { display:grid; grid-template-columns: 1fr; gap:1rem; }
    .grid-3 { display:grid; grid-template-columns: 1fr; gap:1rem; }
    @media (min-width: 640px){ .grid-2{ grid-template-columns: 1fr 1fr; } }
    @media (min-width: 768px){ .grid-3{ grid-template-columns: 1fr 1fr 1fr; } }

    /* Change log panel */
    .change-card{
      border: 1px solid rgba(255,255,255,.15);
      background: rgba(255,255,255,.05);
      border-radius: 14px;
      padding: 0.75rem 0.9rem;
    }
    .change-title{ color:#e5e7eb; font-weight:600; display:flex; align-items:center; gap:.6rem; }
    .badge{
      display:inline-flex; align-items:center; justify-content:center;
      min-width: 1.5rem; height:1.3rem; border-radius:999px;
      font-size:.75rem; padding:0 .4rem;
      background:#047705; color:#fff;
    }
    .change-list{ margin-top:.6rem; color:#cbd5e1; font-size:.9rem; max-height: 200px; overflow:auto; }
    .change-row{ display:flex; flex-direction:column; gap:.15rem; padding:.35rem .4rem; border-radius:8px; }
    .change-row:nth-child(odd){ background: rgba(255,255,255,.03); }
    .oldval{ color:#fca5a5; }
    .newval{ color:#86efac; }

    /* Toasts */
    .toast-wrap{ position:fixed; top:16px; right:16px; z-index:9999; display:flex; flex-direction:column; gap:10px; }
    .toast{
      backdrop-filter: blur(8px);
      border-radius: 12px; padding: .7rem .9rem; min-width: 260px;
      border: 1px solid rgba(255,255,255,.2); color: #fff;
      box-shadow: 0 10px 20px rgba(0,0,0,.35);
      display:flex; gap:.6rem; align-items:flex-start;
      animation: slideIn .25s ease-out;
    }
    .toast-success{ background: linear-gradient(135deg, rgba(4,119,5,.85), rgba(4,119,5,.55)); }
    .toast-error{ background: linear-gradient(135deg, rgba(185,28,28,.9), rgba(239,68,68,.6)); }
    .toast-info{ background: linear-gradient(135deg, rgba(2,132,199,.9), rgba(14,165,233,.6)); }
    .toast-title{ font-weight:700; }
    .toast-close{ cursor:pointer; opacity:.85; }
    @keyframes slideIn { from { transform: translateY(-10px); opacity:.5; } to { transform: translateY(0); opacity:1; } }
  </style>
@endsection

@section('content')
@php
  /** $sale, $products, $batches, $statusOptions, $productionDate, $expirationDate provided by controller */
  $invoice   = $sale->invoice_number ?? $sale->order_number ?? '—';
  $initOrder = \Carbon\Carbon::parse($sale->order_date ?? $sale->date)->toDateString();
  $qtyInit   = (float) ($sale->quantity_kg ?? $sale->quantity ?? 0);
  $unitInit  = (float) ($sale->unit_price ?? $sale->price ?? 0);
  $totInit   = (float) ($sale->total_price ?? $sale->total ?? ($qtyInit*$unitInit));

  // NEW: determine initial unit type ("pack" / "bag" / null)
  $unitTypeInit = $sale->unit_type ?? $sale->unit ?? null;
  $unitTypeInit = in_array($unitTypeInit, ['pack','bag'], true) ? $unitTypeInit : '';
@endphp

<div class="liquid-wrap py-8 px-6">
  <div class="max-w-4xl mx-auto grid gap-5 md:grid-cols-[1fr,320px]">
    {{-- Main form card --}}
    <div class="liquid-card p-6 sm:p-8">
      {{-- Header --}}
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-white" style="text-shadow:-2px 1px 0px #047705;">
            Edit Sale
          </h1>
          <p class="text-white/60 text-sm">Update sale details, link a production batch, and keep expiration accurate.</p>
        </div>
        <div class="flex items-center gap-2">
          <span class="tag">Invoice: {{ $invoice }}</span>
          <a href="{{ route('sales.receipt', $sale) }}" class="btn-ghost">View Receipt</a>
        </div>
      </div>

      {{-- Form --}}
      <form method="POST" action="{{ route('sales.update', $sale) }}" id="saleEditForm">
        @csrf
        @method('PUT')
        <input type="hidden" name="change_log" id="change_log" value="[]"><!-- JSON payload -->

        {{-- Top row: Product + Batch + Status --}}
        <div class="grid-3">
          <div>
            <label class="label">Product</label>
            <select name="product_id" id="product_id" class="liquid-input" data-initial="{{ (int)$sale->product_id }}">
              @foreach($products as $p)
                <option value="{{ $p->id }}" data-shelf="{{ (int)($p->shelf_life_days ?? 0) }}"
                  {{ (int)$sale->product_id === (int)$p->id ? 'selected' : '' }}>
                  {{ $p->product_name }}
                </option>
              @endforeach
            </select>
            @error('product_id')<div class="help text-rose-300 mt-1">{{ $message }}</div>@enderror
          </div>

          <div>
            <label class="label">Batch (Production)</label>
            <select name="production_id" id="production_id" class="liquid-input" data-initial="{{ (int)($sale->production_id ?? 0) }}">
              <option value="">— Auto-pick by date —</option>
              @foreach($batches as $b)
                <option value="{{ $b->id }}"
                  data-pdate="{{ \Carbon\Carbon::parse($b->production_date)->toDateString() }}"
                  data-edate="{{ $b->expiration_date ? \Carbon\Carbon::parse($b->expiration_date)->toDateString() : '' }}"
                  {{ (int)$sale->production_id === (int)$b->id ? 'selected' : '' }}>
                  {{ $b->batch_number }} — {{ \Carbon\Carbon::parse($b->production_date)->format('Y-m-d') }}
                </option>
              @endforeach
            </select>
            <div class="help mt-1">If empty, the nearest batch on/before the order date will be linked.</div>
            @error('production_id')<div class="help text-rose-300 mt-1">{{ $message }}</div>@enderror
          </div>

          <div>
            <label class="label">Status</label>
            <select name="status" id="status" class="liquid-input" data-initial="{{ $sale->status ?? '' }}">
              @foreach($statusOptions as $st)
                <option value="{{ $st }}" {{ ($sale->status ?? '') === $st ? 'selected' : '' }}>{{ $st }}</option>
              @endforeach
            </select>
            @error('status')<div class="help text-rose-300 mt-1">{{ $message }}</div>@enderror
          </div>
        </div>

        <div class="divider"></div>

        {{-- Dates row --}}
        <div class="grid-3">
          <div>
            <label class="label">Order Date</label>
            <input type="date" name="date" id="order_date" class="liquid-input"
                   value="{{ $initOrder }}" data-initial="{{ $initOrder }}">
            @error('date')<div class="help text-rose-300 mt-1">{{ $message }}</div>@enderror
          </div>

          <div>
            <label class="label">Production Date</label>
            <input type="date" name="production_date" id="production_date" class="liquid-input"
                   value="{{ $productionDate }}" data-initial="{{ $productionDate }}">
            <div class="help mt-1">Auto-filled from batch; you may override.</div>
            @error('production_date')<div class="help text-rose-300 mt-1">{{ $message }}</div>@enderror
          </div>

          <div>
            <label class="label">Expiration Date</label>
            <input type="date" name="expiration_date" id="expiration_date" class="liquid-input"
                   value="{{ $expirationDate }}" data-initial="{{ $expirationDate }}">
            <div class="help mt-1">From batch (or computed via shelf life when missing).</div>
            @error('expiration_date')<div class="help text-rose-300 mt-1">{{ $message }}</div>@enderror
          </div>
        </div>

        <div class="divider"></div>

        {{-- Numbers row --}}
        <div class="grid-3">
          <div>
            <label class="label">Quantity (kg)</label>
            <input type="number" step="0.001" min="0.001" name="quantity" id="quantity" class="liquid-input"
                   value="{{ number_format($qtyInit, 3, '.', '') }}" data-initial="{{ number_format($qtyInit, 3, '.', '') }}">
            @error('quantity')<div class="help text-rose-300 mt-1">{{ $message }}</div>@enderror
          </div>

          <div>
            <label class="label">Unit Price
              <span class="help block">Shown price can be “per pack” or “per bag”.</span>
            </label>
            <input type="number" step="0.01" min="0" name="price" id="price" class="liquid-input"
                   value="{{ number_format($unitInit, 2, '.', '') }}" data-initial="{{ number_format($unitInit, 2, '.', '') }}">
            <div class="help mt-1" id="availHelp"></div>
            @error('price')<div class="help text-rose-300 mt-1">{{ $message }}</div>@enderror
          </div>

          {{-- NEW: Unit Type (per pack / per bag) --}}
          <div>
            <label class="label">Unit Type</label>
            <select name="unit_type" id="unit_type" class="liquid-input" data-initial="{{ $unitTypeInit }}">
              <option value="">— None —</option>
              <option value="pack" {{ $unitTypeInit === 'pack' ? 'selected' : '' }}>Per pack</option>
              <option value="bag"  {{ $unitTypeInit === 'bag'  ? 'selected' : '' }}>Per bag</option>
            </select>
            <div class="help mt-1">If set, receipts and lists will show “per pack” / “per bag”.</div>
            @error('unit_type')<div class="help text-rose-300 mt-1">{{ $message }}</div>@enderror
          </div>
        </div>

        <div class="divider"></div>

        {{-- Computed total --}}
        <div class="grid-2">
          <div>
            <label class="label">Total</label>
            <input type="text" id="total_display" class="liquid-input" value="₱ {{ number_format($totInit, 2) }}" disabled>
          </div>
          <div></div>
        </div>

        <div class="divider"></div>

        {{-- Customer + Notes --}}
        <div class="grid-2">
          <div>
            <label class="label">Customer (optional)</label>
            <input type="text" name="customer_name" id="customer_name" class="liquid-input"
                   value="{{ $sale->customer_name }}" data-initial="{{ $sale->customer_name }}">
          </div>

          <div>
            <label class="label">Internal Notes</label>
            <input type="text" name="notes" id="notes" class="liquid-input"
                   value="{{ $sale->notes }}" data-initial="{{ $sale->notes }}">
          </div>
        </div>

        {{-- Actions --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mt-8">
          <a href="{{ route('sales.index') }}" class="btn-ghost">← Back to Sales</a>
          <div class="flex items-center gap-2">
            <button type="submit" class="btn-primary">Save Changes</button>
            <a href="{{ route('sales.receipt', $sale) }}" class="btn-ghost">Preview Receipt</a>
          </div>
        </div>
      </form>
    </div>

    {{-- Side: Change Log --}}
    <div class="liquid-card p-5 h-max">
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

  {{-- Toast container --}}
  <div class="toast-wrap" id="toastWrap"></div>
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
    const totalEl      = document.getElementById('total_display');
    const availHelp    = document.getElementById('availHelp');
    const changeLogEl  = document.getElementById('change_log');
    const formEl       = document.getElementById('saleEditForm');

    const toastWrap    = document.getElementById('toastWrap');
    const changeList   = document.getElementById('changeList');
    const changeCount  = document.getElementById('changeCount');

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
      'customer_name','notes'
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
          return v === 'pack' ? 'Per pack' : (v === 'bag' ? 'Per bag' : v);
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
          return init === 'pack' ? 'Per pack' : (init === 'bag' ? 'Per bag' : init);
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

    // Bind change tracking
    trackIds.forEach(id => {
      const el = document.getElementById(id);
      if (el) el.addEventListener('input', renderChanges);
      if (el && el.tagName === 'SELECT') el.addEventListener('change', renderChanges);
    });

    /* ---------- Batches / availability ---------- */
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
          opt.textContent = `${b.batch_number} — ${(b.production_date || '').slice(0,10)}`;
          batchSel.appendChild(opt);
        });
        if (prior && Array.from(batchSel.options).some(o => o.value === prior)){
          batchSel.value = prior;
        }
        renderChanges();
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
          // if you want a max cap, add: qtyEl.setAttribute('max', data.available);
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
      if (pdate){ prodDateEl.value = (pdate || '').slice(0,10); }
      if (edate){
        expDateEl.value = (edate || '').slice(0,10);
      } else if (pdate){
        const shelf = getSelectedShelfLife();
        if (shelf > 0) expDateEl.value = addDays((pdate || '').slice(0,10), shelf);
      }
      renderChanges();
    }

    productSel?.addEventListener('change', async () => {
      await refreshBatches();
      await refreshAvailable();
      const shelf = getSelectedShelfLife();
      if (shelf > 0 && prodDateEl.value && !expDateEl.value){
        expDateEl.value = addDays(prodDateEl.value, shelf);
      }
    });

    orderDateEl?.addEventListener('change', renderChanges);
    batchSel?.addEventListener('change', onBatchChange);
    qtyEl?.addEventListener('input', () => { computeTotal(); renderChanges(); });
    priceEl?.addEventListener('input', () => { computeTotal(); renderChanges(); });
    unitTypeEl?.addEventListener('change', renderChanges);
    prodDateEl?.addEventListener('change', () => {
      const shelf = getSelectedShelfLife();
      if (shelf > 0 && prodDateEl.value){
        if (!expDateEl.value || expDateEl.value < prodDateEl.value){
          expDateEl.value = addDays(prodDateEl.value, shelf);
        }
      }
      renderChanges();
    });
    document.getElementById('customer_name')?.addEventListener('input', renderChanges);
    document.getElementById('notes')?.addEventListener('input', renderChanges);
    document.getElementById('status')?.addEventListener('change', renderChanges);

    // Before submit: serialize change log to hidden input
    formEl.addEventListener('submit', () => {
      const changes = collectChanges();
      changeLogEl.value = JSON.stringify(changes);
    });

    // Initial
    computeTotal();
    refreshAvailable();
    renderChanges();

    /* ---------- Auto-show validation messages as toasts ---------- */
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
