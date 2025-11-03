{{-- resources/views/production/edit.blade.php --}}
@extends('layout.mainlayout')

@section('title', 'Edit Production')

@section('content')
<style>
  /* ===== Glassy / Glow theme tuned for light UI with black text ===== */
  :root{
    --ink:#0f172a;           /* near-black */
    --muted:#475569;         /* slate-600 */
    --line:#e2e8f0;          /* slate-200 */
    --card:rgba(255,255,255,.85);
    --glass:rgba(255,255,255,.72);
    --accent:#10b981;        /* emerald */
    --accent-2:#2563eb;      /* blue */
    --accent-3:#f59e0b;      /* amber */
    --danger:#ef4444;        /* red */
    --ok:#22c55e;            /* green */
    --bg:linear-gradient(180deg,#f8fafc 0%,#eef2ff 50%,#f0fdf4 100%);
  }
  body{ color:var(--ink); }
  .glass{
    background:var(--glass);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border:1px solid rgba(148,163,184,.35);
    box-shadow:
      0 10px 30px rgba(2,6,23,.06),
      inset 0 1px 0 rgba(255,255,255,.35);
  }
  .glow-ring{
    position:relative;
  }
  .glow-ring::before{
    content:"";
    position:absolute; inset:-2px;
    border-radius: 18px;
    background: conic-gradient(from 180deg at 50% 50%, #34d399, #60a5fa, #f59e0b, #34d399);
    filter: blur(12px);
    opacity:.35;
    z-index:-1;
  }
  .label{ color:var(--ink); font-weight:600; }
  .help{ color:var(--muted); font-size:.78rem; }
  .chip{
    display:inline-flex; align-items:center; gap:.35rem;
    padding:.28rem .55rem; border-radius:999px;
    background:#f8fafc; border:1px solid var(--line); color:var(--ink); font-weight:700; font-size:.72rem;
  }
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
  .btn{ display:inline-flex; align-items:center; justify-content:center; gap:.55rem; font-weight:800; border-radius:12px; padding:.7rem 1rem; }
  .btn-primary{ background:linear-gradient(90deg,#10b981,#22c55e); color:#fff; }
  .btn-primary:hover{ filter:brightness(.97); }
  .btn-ghost{ background:#ffffff; color:var(--ink); border:1px solid var(--line); }
  .btn-ghost:hover{ background:#f8fafc; }
  .btn-danger{ background: linear-gradient(90deg,#ef4444,#f97316); color:#fff; }
  .card{ background:var(--card); border:1px solid var(--line); border-radius:16px; box-shadow:0 12px 24px rgba(2,6,23,.06); }
  .grid-min{ display:grid; grid-template-columns:repeat(1,minmax(0,1fr)); gap:1rem; }
  @media (min-width:640px){ .grid-min{ grid-template-columns:repeat(2,minmax(0,1fr)); } }
</style>

<div class="min-h-[20vh] rounded-2xl px-4 pt-4 pb-0"
     style="background:var(--bg)"></div>

<div class="max-w-4xl mx-auto -mt-16 px-4 pb-10">
  {{-- Header --}}
  <div class="glass glow-ring rounded-2xl p-6 mb-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h2 class="text-2xl font-extrabold tracking-tight">Edit Production</h2>
        <p class="help mt-1">Update batch economics, inventory, and dates. Text is now high-contrast for visibility.</p>
      </div>
      <div class="flex items-center gap-2">
        <span class="chip">ID: {{ $product->id }}</span>
        @if(!empty($product->product_code))
          <span class="chip">Code: {{ $product->product_code }}</span>
        @endif
        @if(!empty($product->shelf_life_days))
          <span class="chip">Shelf life: {{ (int)$product->shelf_life_days }} days</span>
        @endif
      </div>
    </div>
  </div>

  {{-- Flash + Errors --}}
  @if (session('status'))
    <div class="card p-4 mb-4 text-green-700 border-green-200" role="status">
      <strong>Success:</strong> {{ session('status') }}
    </div>
  @endif
  @if ($errors->any())
    <div class="card p-4 mb-4 text-red-700 border-red-200" role="alert">
      <div class="font-semibold mb-1">Please fix the following:</div>
      <ul class="list-disc list-inside">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- Form --}}
  <form action="{{ route('production.update', $product->id) }}" method="POST" class="glass glow-ring rounded-2xl p-6 space-y-6">
    @csrf
    @method('PUT')

    {{-- Product Name --}}
    <div>
      <label class="label mb-1 block">Product Name</label>
      <input name="product_name"
             value="{{ old('product_name', $product->product_name) }}"
             placeholder="e.g., Skinless Longganisa"
             required
             class="input">
      <p class="help mt-1">This name appears across inventory, sales, and dashboards.</p>
    </div>

    {{-- Metrics --}}
    <div class="grid-min">
      <div>
        <label class="label mb-1 block">Forecasted Demand <span class="help">(kg)</span></label>
        <input type="number" step="0.001" min="0"
               name="forecasted_demand"
               value="{{ old('forecasted_demand', $product->forecasted_demand) }}"
               placeholder="0.000"
               required
               class="input"
               id="forecasted_demand">
        <p class="help mt-1">Projected need for this period in kilograms.</p>
      </div>

      <div>
        <label class="label mb-1 block">Current Inventory <span class="help">(kg)</span></label>
        <input type="number" step="0.001" min="0"
               name="current_inventory"
               value="{{ old('current_inventory', $product->current_inventory) }}"
               placeholder="0.000"
               required
               class="input"
               id="current_inventory">
        <p class="help mt-1">Real-time available on hand for this product.</p>
      </div>
    </div>

    <div class="grid-min">
      <div>
        <label class="label mb-1 block">Unit Cost <span class="help">(per kg)</span></label>
        <input type="number" step="0.01" min="0"
               name="unit_cost"
               value="{{ old('unit_cost', $product->unit_cost) }}"
               placeholder="0.00"
               required
               class="input"
               id="unit_cost">
        <p class="help mt-1">Average cost per kilogram (materials + overhead).</p>
      </div>

      <div>
        <label class="label mb-1 block">Production Date</label>
        <input type="date"
               name="production_date"
               value="{{ old('production_date', \Carbon\Carbon::parse($product->production_date)->format('Y-m-d')) }}"
               required
               class="input">
        <p class="help mt-1">Used for expiry and scheduling calculations.</p>
      </div>
    </div>

    {{-- Live Calculations (read-only) --}}
    <div class="grid-min">
      <div>
        <label class="label mb-1 block">Suggested Make Quantity <span class="help">(kg)</span></label>
        <input type="number" step="0.001" min="0" class="input" id="suggested_qty" value="0.000" readonly>
        <p class="help mt-1">Auto = max(0, forecast − inventory). You can override while creating batches.</p>
      </div>
      <div>
        <label class="label mb-1 block">Estimated Make Cost <span class="help">(₱)</span></label>
        <input type="number" step="0.01" min="0" class="input" id="suggested_cost" value="0.00" readonly>
        <p class="help mt-1">Calculated as suggested_qty × unit_cost for quick budgeting.</p>
      </div>
    </div>

    {{-- Remarks / Notes --}}
    <div>
      <label class="label mb-1 block">Remarks</label>
      <textarea name="remarks" rows="4" class="input" placeholder="Add special instructions, supplier notes, batch constraints, etc.">{{ old('remarks', $product->remarks ?? '') }}</textarea>
      <p class="help mt-1">These notes help explain decisions for future audits.</p>
    </div>

    {{-- Smart Suggestions --}}
    <div class="card p-4">
      <div class="flex items-center justify-between">
        <div class="font-semibold">Smart Suggestions</div>
        <div class="text-xs help">Auto-refreshed as you edit inputs</div>
      </div>
      <ul id="suggestions" class="mt-2 space-y-2 text-[.9rem]">
        {{-- JS will populate suggestions here --}}
      </ul>
    </div>

    {{-- Actions --}}
    <div class="flex flex-wrap items-center gap-3 pt-1">
      <button type="submit" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        Save Changes
      </button>
      <a href="{{ route('production.index') }}" class="btn btn-ghost">Cancel</a>

      @can('delete', $product ?? null)
      <form action="{{ route('production.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Delete this production record? This may affect inventory.');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger">Delete</button>
      </form>
      @endcan
    </div>
  </form>

  {{-- Hints --}}
  <div class="mt-6 card p-4">
    <div class="font-semibold mb-2">Tips</div>
    <ul class="list-disc list-inside help">
      <li>Keep <strong>Forecasted Demand</strong> realistic to avoid overproduction and expiries.</li>
      <li>Update <strong>Current Inventory</strong> after sales or wastage to keep suggestions accurate.</li>
      <li>If you track expiry, ensure <strong>Shelf life</strong> is set on the product for better dashboard insights.</li>
      <li>Use <strong>Remarks</strong> to record supplier constraints, quality notes, or customer-specific batches.</li>
    </ul>
  </div>
</div>

{{-- Live logic for suggestions --}}
<script>
  const $F = id => document.getElementById(id);
  const fmtKg = v => (Number(v)||0).toFixed(3);
  const fmtPhp = v => (Number(v)||0).toFixed(2);

  function recompute(){
    const f = parseFloat($F('forecasted_demand')?.value || 0);
    const c = parseFloat($F('current_inventory')?.value || 0);
    const u = parseFloat($F('unit_cost')?.value || 0);

    const suggest = Math.max(0, f - c);
    $F('suggested_qty').value  = fmtKg(suggest);
    $F('suggested_cost').value = fmtPhp(suggest * u);

    const list = $F('suggestions');
    if(!list) return;
    list.innerHTML = '';

    const add = (html, tone='neutral')=>{
      const li = document.createElement('li');
      li.className = 'rounded-lg p-3 border flex items-start gap-2 ' + (tone==='warn'
        ? 'border-amber-300 bg-amber-50'
        : tone==='ok'
          ? 'border-emerald-300 bg-emerald-50'
          : 'border-slate-200 bg-white');
      li.innerHTML = html;
      list.appendChild(li);
    };

    // Suggestion 1: Understock warning
    if (c < 0.25 * f) {
      add(`<span>📉 Inventory is <strong>below 25%</strong> of forecast. Consider producing <strong>${fmtKg(suggest)} kg</strong> soon.</span>`, 'warn');
    } else {
      add(`✅ Inventory level looks acceptable against forecast. Suggested make: <strong>${fmtKg(suggest)} kg</strong>.`, 'ok');
    }

    // Suggestion 2: Over-forecast hint
    if (f === 0) {
      add(`ℹ️ Forecast is zero. Set a forecast to get better recommendations.`);
    } else if (suggest === 0) {
      add(`🎉 You are fully covered for this forecast window. Monitor sales for surprise spikes.`);
    }

    // Suggestion 3: Cost awareness
    if (u > 0 && suggest > 0) {
      add(`💰 Estimated make cost is <strong>₱${fmtPhp(suggest*u)}</strong>. Check margins before confirming batch.`);
    }
  }

  ['forecasted_demand','current_inventory','unit_cost'].forEach(id=>{
    const el = document.getElementById(id);
    el && el.addEventListener('input', recompute);
  });
  window.addEventListener('DOMContentLoaded', recompute);
</script>
@endsection
