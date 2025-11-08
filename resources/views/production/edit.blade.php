{{-- resources/views/production/edit.blade.php --}}
@extends('layout.mainlayout')

@section('title', 'Edit Production')

@section('content')
<style>
  :root{
    --ink:#0f172a; --muted:#475569; --line:#e2e8f0;
    --card:#ffffffcc; --glass:#ffffffb8;
    --accent:#10b981; --blue:#2563eb; --amber:#f59e0b; --red:#ef4444; --green:#22c55e;
    --bg:linear-gradient(180deg,#f8fafc 0%,#eef2ff 50%,#f0fdf4 100%);
  }
  .glass{background:var(--glass);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);border:1px solid rgba(148,163,184,.35);box-shadow:0 10px 30px rgba(2,6,23,.06),inset 0 1px 0 rgba(255,255,255,.35)}
  .card{background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:0 12px 24px rgba(2,6,23,.06)}
  .label{font-weight:700;color:var(--ink)}
  .help{color:var(--muted);font-size:.8rem}
  .input,.select,textarea{width:100%;background:#fff;border:1px solid var(--line);border-radius:12px;padding:.7rem .9rem;transition:border-color .15s,box-shadow .15s}
  .input:focus,.select:focus,textarea:focus{outline:0;border-color:#93c5fd;box-shadow:0 0 0 3px rgba(37,99,235,.18)}
  .btn{display:inline-flex;align-items:center;gap:.5rem;border-radius:12px;padding:.7rem 1rem;font-weight:800}
  .btn-primary{background:linear-gradient(90deg,#10b981,#22c55e);color:#fff}
  .btn-ghost{background:#fff;border:1px solid var(--line);color:var(--ink)}
  .btn-danger{background:linear-gradient(90deg,#ef4444,#f97316);color:#fff}
  .pill{display:inline-flex;align-items:center;gap:.35rem;border:1px solid var(--line);border-radius:999px;padding:.25rem .6rem;font-weight:700;font-size:.72rem;color:#334155;background:#f8fafc}
  .grid-2{display:grid;grid-template-columns:repeat(1,minmax(0,1fr));gap:1rem}
  @media(min-width:640px){.grid-2{grid-template-columns:repeat(2,minmax(0,1fr))}}
  .insight{border-radius:14px;padding:14px;border:1px solid}
  .insight-pack{background:#FFFCF0;border-color:#F6E9A8;color:#6B4C00}
  .insight-bag{background:#FFF5F6;border-color:#F5C5CA;color:#831843}
  .insight .k{font-size:1.8rem;font-weight:900;letter-spacing:.3px}
</style>

{{-- Top backdrop --}}
<div class="min-h-[20vh] rounded-2xl px-4 pt-4 pb-0" style="background:var(--bg)"></div>

@php
  /** @var \App\Models\Production $production */
  $production = $product; // controller passes Production as $product
  $p = $production->product; // related Product row
@endphp

<div class="max-w-4xl mx-auto -mt-16 px-4 pb-10">

  {{-- Header --}}
  <div class="glass rounded-2xl p-6 mb-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h2 class="text-2xl font-extrabold tracking-tight">Edit Production</h2>
        <p class="help mt-1">Update batch inventory, per-unit prices, and dates.</p>
      </div>
      <div class="flex items-center gap-2">
        <span class="pill">Batch: {{ $production->batch_number ?? '—' }}</span>
        <span class="pill">Product: {{ $p?->product_name ?? '—' }}</span>
        @if($production->expiration_date)
          <span class="pill">
            @php $days = $production->days_to_expiry; @endphp
            {{ $days < 0 ? 'Expired' : $days.' days to expiry' }}
          </span>
        @endif
      </div>
    </div>
  </div>

  {{-- Flash + Errors --}}
  @if (session('status'))
    <div class="card p-4 mb-4 text-green-700 border-green-200"><strong>Success:</strong> {{ session('status') }}</div>
  @endif
  @if ($errors->any())
    <div class="card p-4 mb-4 text-red-700 border-red-200">
      <div class="font-semibold mb-1">Fix the following</div>
      <ul class="list-disc list-inside">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
  @endif

  {{-- Form --}}
  <form action="{{ route('production.update', $production->id) }}" method="POST" class="glass rounded-2xl p-6 space-y-6">
    @csrf
    @method('PUT')

    {{-- Type snapshot + Forecast/Inventory --}}
    <div class="grid-2">
      <div>
        <label class="label mb-1 block">Type</label>
        <input name="product_name_snapshot"
               value="{{ old('product_name_snapshot', $production->product_name_snapshot) }}"
               placeholder="e.g., Garlic Skinless"
               class="input">
        <p class="help mt-1">Saved on each batch so you can label variants quickly.</p>
      </div>

      <div>
        <label class="label mb-1 block">Batch Number</label>
        <input name="batch_number"
               value="{{ old('batch_number', $production->batch_number) }}"
               placeholder="Auto if unique not set"
               class="input">
        <p class="help mt-1">Must be unique per product.</p>
      </div>
    </div>

    <div class="grid-2">
      <div>
        <label class="label mb-1 block">Forecasted Demand <span class="help">(kg)</span></label>
        <input type="number" step="0.001" min="0"
               name="forecasted_demand"
               value="{{ old('forecasted_demand', $production->forecasted_demand) }}"
               placeholder="0.000"
               class="input" id="forecasted_demand">
      </div>
      <div>
        <label class="label mb-1 block">Current Inventory <span class="help">(kg)</span></label>
        <input type="number" step="0.001" min="0"
               name="current_inventory"
               value="{{ old('current_inventory', $production->current_inventory) }}"
               placeholder="0.000"
               class="input" id="current_inventory">
      </div>
    </div>

    {{-- Prices and per-unit availabilities --}}
    <div class="grid-2">
      <div>
        <label class="label mb-1 block">Unit Cost <span class="help">(per kg)</span></label>
        <input type="number" step="0.01" min="0"
               name="unit_cost"
               value="{{ old('unit_cost', $production->unit_cost) }}"
               placeholder="0.00"
               class="input" id="unit_cost">
      </div>
      <div class="grid-2">
        <div>
          <label class="label mb-1 block">Price per Pack (₱)</label>
          <input type="number" step="0.01" min="0"
                 name="unit_price_pack"
                 value="{{ old('unit_price_pack', $production->unit_price_pack) }}"
                 placeholder="0.00" class="input">
        </div>
        <div>
          <label class="label mb-1 block">Price per Bag (₱)</label>
          <input type="number" step="0.01" min="0"
                 name="unit_price_bag"
                 value="{{ old('unit_price_bag', $production->unit_price_bag) }}"
                 placeholder="0.00" class="input">
        </div>
      </div>
    </div>

    <div class="grid-2">
      <div class="grid-2">
        <div>
          <label class="label mb-1 block">Available Packs</label>
          <input type="number" step="1" min="0"
                 name="available_pack"
                 value="{{ old('available_pack', $production->available_pack ?? 0) }}"
                 placeholder="0" class="input">
        </div>
        <div>
          <label class="label mb-1 block">Available Bags</label>
          <input type="number" step="1" min="0"
                 name="available_bag"
                 value="{{ old('available_bag', $production->available_bag ?? 0) }}"
                 placeholder="0" class="input">
        </div>
      </div>

      {{-- Insight cards like your sales modal --}}
      <div class="grid-2">
        <div class="insight insight-pack">
          <div class="flex items-start justify-between gap-3">
            <div>
              <div class="text-sm font-semibold">Pack Availability</div>
              <div class="k"><span id="packsAvail">{{ (int)($production->available_pack ?? 0) }}</span> <span class="text-base font-bold">packs</span></div>
              <div class="help">Per this batch</div>
            </div>
            <div class="text-right">
              <div class="text-sm help">Suggested Price</div>
              <div class="text-xl font-extrabold">₱{{ number_format((float)($production->unit_price_pack ?? 0), 2) }}</div>
            </div>
          </div>
        </div>

        <div class="insight insight-bag">
          <div class="flex items-start justify-between gap-3">
            <div>
              <div class="text-sm font-semibold">Bag Availability</div>
              <div class="k"><span id="bagsAvail">{{ (int)($production->available_bag ?? 0) }}</span> <span class="text-base font-bold">bags</span></div>
              <div class="help">Per this batch</div>
            </div>
            <div class="text-right">
              <div class="text-sm help">Suggested Price</div>
              <div class="text-xl font-extrabold">₱{{ number_format((float)($production->unit_price_bag ?? 0), 2) }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Dates --}}
    <div class="grid-2">
      <div>
        <label class="label mb-1 block">Prod. Date</label>
        <input type="date" name="production_date"
               value="{{ old('production_date', optional($production->production_date)->format('Y-m-d')) }}"
               class="input">
      </div>
      <div>
        <label class="label mb-1 block">Expiry</label>
        <input type="date" name="expiration_date"
               value="{{ old('expiration_date', optional($production->expiration_date)->format('Y-m-d')) }}"
               class="input">
        <p class="help mt-1">
          @if($production->expiration_date)
            {{ $production->is_expired ? 'Already expired' : ($production->days_to_expiry.' days remaining') }}
          @else
            Set shelf life on the product to auto-calculate if empty.
          @endif
        </p>
      </div>
    </div>

    {{-- Live Calculations --}}
    <div class="grid-2">
      <div>
        <label class="label mb-1 block">Suggested Make Quantity <span class="help">(kg)</span></label>
        <input type="number" step="0.001" min="0" class="input" id="suggested_qty" value="0.000" readonly>
        <p class="help mt-1">Auto equals max of zero or forecast minus inventory.</p>
      </div>
      <div>
        <label class="label mb-1 block">Estimated Make Cost <span class="help">(₱)</span></label>
        <input type="number" step="0.01" min="0" class="input" id="suggested_cost" value="0.00" readonly>
        <p class="help mt-1">Suggested qty multiplied by unit cost.</p>
      </div>
    </div>

    {{-- Notes --}}
    <div>
      <label class="label mb-1 block">Remarks</label>
      <textarea name="remarks" rows="4" class="input" placeholder="Add internal notes">{{ old('remarks', $production->remarks ?? '') }}</textarea>
    </div>

    {{-- Actions --}}
    <div class="flex flex-wrap items-center gap-3 pt-1">
      <button type="submit" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        Save Changes
      </button>
      <a href="{{ route('production.index') }}" class="btn btn-ghost">Cancel</a>

      <form action="{{ route('production.destroy', $production->id) }}"
            method="POST"
            onsubmit="return confirm('Delete this batch This cannot be restored after seven days if purged.')">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-danger">Delete</button>
      </form>
    </div>
  </form>

  {{-- Tips --}}
  <div class="mt-6 card p-4">
    <div class="font-semibold mb-2">Tips</div>
    <ul class="list-disc list-inside help">
      <li>Keep forecast realistic to avoid expiries.</li>
      <li>Update current inventory after sales or wastage.</li>
      <li>Set shelf life on the product for auto expiry.</li>
      <li>Use remarks for constraints or quality notes.</li>
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
  }

  ['forecasted_demand','current_inventory','unit_cost'].forEach(id=>{
    const el = document.getElementById(id);
    el && el.addEventListener('input', recompute);
  });
  window.addEventListener('DOMContentLoaded', recompute);
</script>
@endsection
