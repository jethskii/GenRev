@extends('layout.mainlayout')
@section('title', 'Meat Production Inventory')

@section('styles')
<style>
  :root{
    --page:#f7f8fb;
    --ink:#0f172a;
    --muted:#6b7280;
    --card:#ffffff;
    --border:#e5e7eb;
    --shadow:0 10px 30px rgba(17, 24, 39, .08);
    --ring:0 0 0 1px rgba(16,185,129,.30), 0 8px 22px rgba(16,185,129,.16);
    --emerald:#10b981; --amber:#f59e0b; --red:#ef4444; --blue:#2563eb; --violet:#7c3aed;
  }
  body{background:var(--page); color:var(--ink)}
  .page-wrap{max-width:1400px;margin-inline:auto;padding-bottom:2rem}

  /* White glass cards */
  .card{
    background:linear-gradient(180deg, rgba(255,255,255,1), rgba(255,255,255,.96));
    border:1px solid var(--border);
    border-radius:20px; box-shadow:var(--shadow); backdrop-filter:saturate(1.2) blur(4px);
  }
  .card.holo{position:relative}
  .card.holo:before{
    content:""; position:absolute; inset:0; border-radius:inherit; pointer-events:none; padding:1px;
    background:linear-gradient(130deg, rgba(16,185,129,.35), rgba(124,58,237,.28) 45%, rgba(59,130,246,.28));
    -webkit-mask:linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
    -webkit-mask-composite:xor; mask-composite:exclude;
  }

  /* Sticky toolbar */
  .toolbar-wrap{position:sticky; top:0; z-index:20; padding-top:.5rem}
  .toolbar{display:flex; gap:.6rem; align-items:center; background:rgba(255,255,255,.9);
    border:1px solid var(--border); border-radius:14px; padding:.5rem; box-shadow:0 6px 18px rgba(15,23,42,.06); backdrop-filter:blur(6px)}

  /* Buttons */
  .btn-armygreen{background:linear-gradient(180deg, #16a34a, #0f9a51);
    border:1px solid rgba(16,185,129,.35); color:#fff; padding:.55rem .9rem; border-radius:12px; font-weight:700}
  .btn-armygreen:hover{filter:brightness(1.05)}
  .btn-ghost{background:#fff; border:1px solid var(--border); border-radius:12px; padding:.55rem .9rem}
  .btn-ghost:hover{background:#f9fafb}
  .btn-soft{background:rgba(16,185,129,.10); color:#065f46; border:1px solid rgba(16,185,129,.25); border-radius:12px; padding:.55rem .9rem}
  .btn-soft:hover{background:rgba(16,185,129,.14)}

  /* Chips */
  .chip{font-size:.75rem; padding:.35rem .6rem; border-radius:999px; border:1px solid var(--border); background:#fff; color:var(--muted)}
  .chip.active{color:#065f46; background:rgba(16,185,129,.10); border-color:rgba(16,185,129,.35); box-shadow:var(--ring)}
  .chip-filter{cursor:pointer}
  .chip-filter:hover{background:#f3f4f6}

  /* Inputs */
  .input-dark{background:#fff; border:1px solid var(--border); color:var(--ink); outline:none}
  .input-dark:focus{box-shadow:0 0 0 3px rgba(16,185,129,.20), inset 0 0 0 1px rgba(16,185,129,.35); border-color:rgba(16,185,129,.35)}
  input, select{height:40px}

  /* KPI */
  .kpi-card .icon{width:40px;height:40px;border-radius:12px;border:1px solid var(--border);
    display:grid;place-items:center;background:#fbfefc}
  .kpi-card.glow{box-shadow:var(--ring)}
  .kpi-card .value{font-size:1.45rem; font-weight:800; letter-spacing:.2px}

  /* Tables */
  .table-wrap{overflow:hidden;border-radius:14px;border:1px solid var(--border); background:#fff}
  .table{width:100%;border-collapse:separate;border-spacing:0}
  .table thead th{
    position:sticky;top:0;z-index:2;background:#f9fafb; color:#334155;
    font-size:.72rem; text-transform:uppercase; letter-spacing:.06em; padding:.7rem .6rem;
    border-bottom:1px solid var(--border);
  }
  .table tbody td{padding:.7rem .6rem; border-bottom:1px dashed #eaecef; color:#111827}
  .table tbody tr:hover{background:#fcfcfd}
  .table tbody tr:last-child td{border-bottom:0}
  .table thead th:first-child, .table tbody td:first-child{padding-left:1rem}
  .table thead th:last-child, .table tbody td:last-child{padding-right:1rem}
  .dense .table tbody td{padding:.45rem .5rem}

  /* Status pills */
  .pill{padding:.18rem .55rem;border-radius:9px;font-size:.72rem;font-weight:800;letter-spacing:.02em}
  .pill-ok{background:#ecfdf5;color:#065f46; border:1px solid rgba(16,185,129,.30)}
  .pill-warn{background:#fffbeb;color:#92400e; border:1px solid rgba(245,158,11,.35)}
  .pill-bad{background:#fef2f2;color:#991b1b; border:1px solid rgba(239,68,68,.35)}

  /* Progress */
  .progress{height:7px;border-radius:999px;background:#edf2f7;overflow:hidden}
  .progress > i{display:block;height:100%;border-radius:inherit}

  /* Notification Center - Light */
  .notice-list{display:grid;gap:.6rem;max-height:180px;overflow:auto;padding-right:.25rem}
  .notice{display:grid;grid-template-columns:36px 1fr auto;gap:.8rem;align-items:start;
    background:linear-gradient(180deg, #ffffff, #fbfbfb);
    border:1px solid var(--border); border-radius:14px; padding:.65rem .8rem; animation:pop .22s ease-out}
  .notice .badge{width:36px;height:36px;border-radius:10px;display:grid;place-items:center;border:1px solid var(--border); background:#fff}
  .notice.critical .badge{background:#fff5f5; border-color:#ffe0e0}
  .notice.warning  .badge{background:#fffbef; border-color:#ffefcf}
  .notice.info     .badge{background:#eff6ff; border-color:#e0eaff}
  .notice .meta{font-size:.72rem;color:var(--muted)}
  @keyframes pop{from{transform:translateY(6px);opacity:.0} to{transform:translateY(0);opacity:1}}

  /* Card grid view for products */
  .grid-cards{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
  @media (min-width:1024px){ .grid-cards{grid-template-columns:repeat(3,minmax(0,1fr))} }
  .p-card{background:#fff;border:1px solid var(--border);border-radius:14px;padding:.8rem;display:grid;gap:.5rem}
  .p-card:hover{box-shadow:0 8px 20px rgba(17,24,39,.08)}

  /* Quick utility */
  .text-muted{color:var(--muted)}
</style>
@endsection

@section('actions')
  <div class="toolbar-wrap">
    <div class="toolbar">
      <a href="{{ route('materials.index') }}" class="btn-armygreen">Raw Materials</a>
      <a href="{{ route('production.index') }}" class="btn-armygreen">Production Schedule</a>
      <a href="{{ route('sales.index') }}" class="btn-armygreen">Sales & Orders</a>
      <div class="ml-auto flex gap-2">
        <a href="{{ route('inventory.index') }}?export=csv"  class="btn-ghost">Export CSV</a>
        <a href="{{ route('inventory.index') }}?export=pdf"  class="btn-ghost">Export PDF</a>
      </div>
    </div>
  </div>
@endsection

@section('content')
<div x-data="inventoryIndex()" :class="tableDense ? 'dense page-wrap space-y-6' : 'page-wrap space-y-6'">

  {{-- Header + Search with chips --}}
  <div class="card holo p-4">
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 justify-between">
      <div>
        <h1 class="text-xl font-extrabold tracking-tight">Meat Production Inventory</h1>
        <p class="text-sm text-muted">White glass dashboard with glow accents. Organized and friendly.</p>
      </div>
      <form method="GET" class="flex gap-2 w-full sm:w-auto">
        <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Search cuts, batches, materials" class="input-dark rounded-xl px-3 py-2 w-full sm:w-72">
        <button class="btn-ghost">Search</button>
        <a href="{{ route('inventory.index') }}" class="btn-ghost">Reset</a>
      </form>
    </div>

    <div class="mt-3 flex flex-wrap gap-2">
      {{-- quick filters, update hrefs as needed --}}
      <a class="chip chip-filter" href="{{ request()->fullUrlWithQuery(['stock'=>'low']) }}">Low stock</a>
      <a class="chip chip-filter" href="{{ request()->fullUrlWithQuery(['expiry'=>'7']) }}">Expiring 7d</a>
      <a class="chip chip-filter" href="{{ request()->fullUrlWithQuery(['status'=>'released']) }}">Released</a>
      <span class="chip active">Live data</span>
    </div>
  </div>

  {{-- Elegant Notifications --}}
  @if(!empty($productionAlarms))
    <div class="card p-4">
      <div class="flex items-center justify-between mb-2">
        <h3 class="font-semibold flex items-center gap-2">
          Notifications <span class="chip active">{{ count($productionAlarms) }} active</span>
        </h3>
        <small class="text-muted">Auto generated from production checks</small>
      </div>
      <div class="notice-list">
        @foreach($productionAlarms as $alarm)
          @php
            $sev = $alarm['severity'] ?? 'info';
            $sevClass = $sev === 'critical' ? 'critical' : ($sev === 'warning' ? 'warning' : 'info');
            $icon = $sev === 'critical' ? '🚨' : ($sev === 'warning' ? '⚠️' : 'ℹ️');
          @endphp
          <div class="notice {{ $sevClass }}">
            <div class="badge">{{ $icon }}</div>
            <div>
              <div class="text-sm leading-snug">{{ $alarm['message'] }}</div>
              @if(!empty($alarm['hint']))
                <div class="meta mt-1">{{ $alarm['hint'] }}</div>
              @endif
            </div>
            <div class="meta">{{ ucfirst($sev) }}</div>
          </div>
        @endforeach
      </div>
    </div>
  @endif

  {{-- Filter Panel --}}
  <div class="card p-4">
    <form method="GET" class="grid md:grid-cols-4 gap-3 items-end">
      <div>
        <label class="text-sm block mb-1 text-muted">Cut Category</label>
        <select name="cat" class="w-full input-dark rounded-xl px-3 py-2">
          <option value="">All Cuts</option>
          @foreach($categories as $c)
            <option value="{{ $c }}" @selected(($cat ?? null)===$c)>{{ $c }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="text-sm block mb-1 text-muted">Low stock threshold (kg)</label>
        <input type="number" step="0.001" min="0" name="low_material_threshold" value="{{ $lowThresh }}" class="w-full input-dark rounded-xl px-3 py-2">
      </div>
      <div class="md:col-span-2 flex flex-wrap gap-2">
        <button class="btn-armygreen">Apply Filters</button>
        <a href="{{ route('inventory.index') }}" class="btn-ghost">Clear</a>

        {{-- New controls --}}
        <button type="button" class="btn-ghost" @click="tableDense = !tableDense">
          <span x-text="tableDense ? 'Comfortable density' : 'Compact density'"></span>
        </button>

        <div class="chip">
          <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" class="accent-emerald-600" x-model="productViewCards">
            <span>Cards view</span>
          </label>
        </div>
      </div>
    </form>
  </div>

  {{-- KPIs --}}
  <div class="grid kpi-grid grid-cols-2 lg:grid-cols-6 gap-4">
    <div class="card kpi-card glow p-4">
      <div class="flex items-center gap-3">
        <span class="icon">🥩</span>
        <div>
          <div class="text-xs text-muted">Finished Cuts</div>
          <div class="value">{{ $totalProducts }}</div>
        </div>
      </div>
    </div>
    <div class="card kpi-card p-4">
      <div class="flex items-center gap-3">
        <span class="icon">📦</span>
        <div>
          <div class="text-xs text-muted">Raw Materials (kg)</div>
          <div class="value">{{ number_format($totalMaterialsWeight,3) }}</div>
        </div>
      </div>
    </div>
    <div class="card kpi-card p-4">
      <div class="flex items-center gap-3">
        <span class="icon">🏷️</span>
        <div>
          <div class="text-xs text-muted">Batches (All)</div>
          <div class="value">{{ $batchesInProduction }}</div>
        </div>
      </div>
    </div>
    <div class="card kpi-card p-4">
      <div class="flex items-center gap-3">
        <span class="icon">✅</span>
        <div>
          <div class="text-xs text-muted">With Stock</div>
          <div class="value">{{ $batchesReleased }}</div>
        </div>
      </div>
    </div>
    <div class="card kpi-card p-4">
      <div class="flex items-center gap-3">
        <span class="icon">⏳</span>
        <div>
          <div class="text-xs text-muted">Expiring ≤7d</div>
          <div class="value text-amber-600">{{ $batchesExpiringSoon }}</div>
        </div>
      </div>
    </div>
    <div class="card kpi-card p-4">
      <div class="flex items-center gap-3">
        <span class="icon">💸</span>
        <div>
          <div class="text-xs text-muted">Revenue (₱)</div>
          <div class="value">{{ number_format($totalRevenue,2) }}</div>
        </div>
      </div>
    </div>
  </div>

  {{-- Finished Cuts + Expiry Risk --}}
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4" x-data>
    <div class="lg:col-span-2 card p-4">
      <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold">Finished Meat Cuts</h3>
        <div class="flex items-center gap-2">
          <label class="chip text-sm">
            <input type="checkbox" class="mr-1 accent-emerald-600" x-model="$root.cols.price"> Price
          </label>
          <label class="chip text-sm">
            <input type="checkbox" class="mr-1 accent-emerald-600" x-model="$root.cols.cost"> Unit Cost
          </label>
          <a href="{{ route('products.index') }}" class="text-xs text-muted hover:text-emerald-700">Manage Cuts →</a>
        </div>
      </div>

      {{-- Cards view --}}
      <div class="grid-cards" x-show="productViewCards">
        @forelse($products as $p)
          @php
            $forecastItem = collect($stockForecasting)->firstWhere('product_id', $p->id);
            $days = $forecastItem['days_until_stockout'] ?? null;
            $status = $forecastItem['forecast_status'] ?? 'normal';
            $pill = $status === 'critical' ? 'pill-bad' : ($status === 'warning' ? 'pill-warn' : 'pill-ok');
          @endphp
          <div class="p-card">
            <div class="flex items-center gap-3">
              <img src="{{ $p->image_url ?? asset('images/default-product.png') }}" class="w-12 h-12 rounded-lg object-cover border border-[var(--border)]">
              <div class="min-w-0">
                <div class="font-semibold truncate">{{ $p->product_name }}</div>
                <div class="text-xs text-muted">{{ $p->category ?? '—' }}</div>
              </div>
              <div class="ml-auto text-right">
                <div class="text-xs text-muted">Available</div>
                <div class="font-bold">{{ number_format((float)($p->available_stock_kg ?? 0),3) }} kg</div>
              </div>
            </div>
            <div class="flex items-center justify-between">
              <span class="pill {{ $pill }}">{{ $days !== null ? number_format($days,1).' days' : '∞' }}</span>
              <div class="flex gap-2">
                <a href="{{ route('products.show',$p->id) }}" class="btn-ghost text-xs">View</a>
                <button class="btn-soft text-xs"
                        @click="openAdjustProduct({ id: {{ $p->id }}, name: @js($p->product_name), price: {{ (float)($p->default_price ?? 0) }}, forecast: {{ (float)($p->forecasted_demand ?? 0) }}, cost: {{ (float)($p->unit_cost ?? 0) }} })">
                  Quick Edit
                </button>
              </div>
            </div>
          </div>
        @empty
          <div class="text-muted">No meat cuts found.</div>
        @endforelse
      </div>

      {{-- Table view --}}
      <div class="table-wrap" x-show="!productViewCards">
        <table class="table min-w-[880px]">
          <thead>
          <tr>
            <th>Cut</th>
            <th class="text-center">Category</th>
            <th class="text-right">Available (kg)</th>
            <th class="text-right">Forecast (kg)</th>
            <th class="text-right">Days to Stockout</th>
            <th class="text-right" x-show="$root.cols.price">Price</th>
            <th class="text-right" x-show="$root.cols.cost">Unit Cost</th>
            <th></th>
          </tr>
          </thead>
          <tbody>
          @forelse($products as $p)
            @php
              $forecastItem = collect($stockForecasting)->firstWhere('product_id', $p->id);
              $days = $forecastItem['days_until_stockout'] ?? null;
              $status = $forecastItem['forecast_status'] ?? 'normal';
            @endphp
            <tr>
              <td>
                <div class="flex items-center gap-3">
                  <img src="{{ $p->image_url ?? asset('images/default-product.png') }}" class="w-10 h-10 rounded-lg object-cover border border-[var(--border)]">
                  <div class="min-w-0">
                    <div class="font-medium truncate max-w-[180px]">{{ $p->product_name }}</div>
                    <div class="text-xs text-muted">Last prod: {{ optional($p->production_date)->format('Y-m-d') ?: '—' }}</div>
                  </div>
                </div>
              </td>
              <td class="text-center">{{ $p->category ?? '—' }}</td>
              <td class="text-right">{{ number_format((float)($p->available_stock_kg ?? 0),3) }}</td>
              <td class="text-right">{{ number_format((float)($p->forecasted_demand ?? 0),3) }}</td>
              <td class="text-right">
                <span class="pill
                  {{ $status === 'critical' ? 'pill-bad' : ($status === 'warning' ? 'pill-warn' : 'pill-ok') }}">
                  {{ $days !== null ? number_format($days,1) : '∞' }}
                </span>
              </td>
              <td class="text-right" x-show="$root.cols.price">₱{{ number_format((float)($p->default_price ?? 0),2) }}</td>
              <td class="text-right" x-show="$root.cols.cost">₱{{ number_format((float)($p->unit_cost ?? 0),2) }}</td>
              <td class="text-right">
                <div class="flex gap-2 justify-end">
                  <a href="{{ route('products.show',$p->id) }}" class="btn-ghost text-xs">View</a>
                  <button class="btn-soft text-xs"
                          @click="openAdjustProduct({ id: {{ $p->id }}, name: @js($p->product_name), price: {{ (float)($p->default_price ?? 0) }}, forecast: {{ (float)($p->forecasted_demand ?? 0) }}, cost: {{ (float)($p->unit_cost ?? 0) }} })">
                    Quick Edit
                  </button>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="8" class="p-3 text-muted">No meat cuts found.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-3">{{ $products->onEachSide(1)->links() }}</div>
    </div>

    <div class="card p-4">
      <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold">Expiry Risk ≤ 7 days</h3>
        <a href="{{ route('production.index') }}" class="text-xs text-muted hover:text-emerald-700">Manage Batches →</a>
      </div>
      <ul class="text-sm space-y-2 max-h-[520px] overflow-y-auto pr-1">
        @forelse($expiringSoon as $b)
          @php
            $ratio = max(0,min(100, 100-($b->days_to_expiry ?? 0)*100/7));
            $bar = $b->days_to_expiry <= 3 ? 'background:#ef4444' : ($b->days_to_expiry <= 7 ? 'background:#f59e0b' : 'background:#10b981');
          @endphp
          <li class="p-2 rounded-lg border border-[var(--border)] bg-white">
            <div class="flex items-center justify-between gap-2">
              <div class="min-w-0">
                <div class="truncate font-medium">{{ $b->product?->product_name }} <span class="text-muted font-normal">({{ $b->batch_number }})</span></div>
                <div class="text-xs text-muted">Exp: {{ optional($b->expiration_date)->format('Y-m-d') ?? '—' }}</div>
              </div>
              <div class="text-right">
                <div class="text-amber-600 text-xs font-semibold">{{ $b->days_to_expiry }} days</div>
                <div class="text-xs text-muted">{{ number_format((float)$b->current_inventory,3) }} kg</div>
              </div>
            </div>
            <div class="progress mt-2"><i style="{{ $bar }};width:{{ $ratio }}%"></i></div>
          </li>
        @empty
          <li class="text-muted">No cuts expiring soon.</li>
        @endforelse
      </ul>
    </div>
  </div>

  {{-- Batch Traceability --}}
  <div class="card p-4">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-semibold">Batch Traceability</h3>
      <a href="{{ route('production.index') }}" class="text-xs text-muted hover:text-emerald-700">View All Batches →</a>
    </div>
    <div class="table-wrap">
      <table class="table min-w-[960px]">
        <thead>
        <tr>
          <th class="text-left">Batch Code</th>
          <th class="text-left">Cut</th>
          <th class="text-center">Production Date</th>
          <th class="text-center">Expiry Date</th>
          <th class="text-right">Total (kg)</th>
          <th class="text-right">Available (kg)</th>
          <th class="text-center">Status</th>
          <th class="text-center">Days to Expiry</th>
        </tr>
        </thead>
        <tbody>
        @forelse($recentBatches as $batch)
          <tr>
            <td class="font-mono text-xs">{{ $batch->batch_code }}</td>
            <td>{{ $batch->product?->product_name }}</td>
            <td class="text-center">{{ optional($batch->produced_at)->format('Y-m-d') }}</td>
            <td class="text-center">{{ optional($batch->expiry_date)->format('Y-m-d') }}</td>
            <td class="text-right">{{ number_format($batch->qty_total,3) }}</td>
            <td class="text-right">{{ number_format($batch->qty_available,3) }}</td>
            <td class="text-center">
              @php
                $status = $batch->status;
                $cls = $status === 'RELEASED' ? 'pill-ok' : ($status === 'QA_HOLD' ? 'pill-warn' : 'pill');
              @endphp
              <span class="pill {{ $cls }}">{{ $status }}</span>
            </td>
            <td class="text-center">
              @if($batch->days_to_expiry !== null)
                <span class="pill {{ $batch->days_to_expiry <= 3 ? 'pill-bad' : ($batch->days_to_expiry <= 7 ? 'pill-warn' : 'pill-ok') }}">
                  {{ $batch->days_to_expiry }}
                </span>
              @else
                <span class="text-muted">—</span>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="8" class="p-3 text-muted">No batches found.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- Materials + Usage --}}
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 card p-4">
      <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold">Raw Materials</h3>
        <a href="{{ route('materials.index') }}" class="text-xs text-muted hover:text-emerald-700">Manage Materials →</a>
      </div>
      <div class="table-wrap">
        <table class="table min-w-[720px]">
          <thead>
          <tr>
            <th class="text-left">Material</th>
            <th class="text-center">Unit</th>
            <th class="text-right">On-hand (kg)</th>
            <th class="text-right">Unit Price</th>
            <th></th>
          </tr>
          </thead>
          <tbody>
          @forelse($materials as $m)
            @php $low = (float)$m->quantity_kg <= $lowThresh; @endphp
            <tr>
              <td>
                <div class="flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full {{ $low ? 'bg-amber-500' : 'bg-emerald-500' }}"></span>
                  <span class="font-medium">{{ $m->material_name ?? $m->name }}</span>
                </div>
              </td>
              <td class="text-center">{{ $m->unit ?? 'kg' }}</td>
              <td class="text-right {{ $low ? 'text-amber-600' : '' }}">{{ number_format((float)$m->quantity_kg,3) }}</td>
              <td class="text-right">₱{{ number_format((float)($m->unit_price ?? 0),2) }}</td>
              <td class="text-right">
                <button class="btn-soft text-xs"
                        @click="openAdjustMaterial({ id: {{ $m->id }}, name: @js($m->material_name ?? $m->name) })">
                  Adjust
                </button>
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="p-3 text-muted">No materials found.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
      <div class="mt-3">{{ $materials->onEachSide(1)->links() }}</div>
    </div>

    <div class="card p-4">
      <h3 class="font-semibold mb-2">Material Usage This Week</h3>
      <div class="flex items-center justify-between text-xs text-muted mb-2">
        <div>Total Qty: {{ number_format($materialsUsageTotals['qty'],3) }} kg</div>
        <div>Total Cost: ₱{{ number_format($materialsUsageTotals['cost'],2) }}</div>
      </div>
      <div class="table-wrap">
        <table class="table min-w-[520px]">
          <thead>
          <tr>
            <th class="text-left">Material</th>
            <th class="text-right">Qty Used</th>
            <th class="text-right">Cost</th>
          </tr>
          </thead>
          <tbody>
          @forelse($materialsUsage as $u)
            <tr>
              <td>{{ $u->material_name }}</td>
              <td class="text-right">{{ number_format((float)$u->qty_used,3) }}</td>
              <td class="text-right">₱{{ number_format((float)$u->cost_used,2) }}</td>
            </tr>
          @empty
            <tr><td colspan="3" class="p-3 text-muted">No usage recorded this week.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>
@endsection

@section('modals')
  {{-- Adjust Material --}}
  <div x-data="adjustMaterial()" x-show="open" x-cloak class="fixed inset-0 modal flex items-end md:items-center justify-center p-4">
    <div @click.outside="close()" class="card w-full max-w-md p-4">
      <h3 class="text-lg font-semibold mb-2">Adjust Raw Material</h3>
      <p class="text-sm text-muted mb-3 truncate">Material: <span class="font-medium" x-text="name"></span></p>
      <form method="POST" action="{{ route('inventory.store') }}" class="space-y-3">
        @csrf
        <input type="hidden" name="kind" value="material">
        <input type="hidden" name="id" :value="id">
        <div>
          <label class="text-sm block mb-1 text-muted">Delta (kg) use negative to deduct</label>
          <input type="number" step="0.001" name="delta_kg" class="w-full input-dark rounded-xl px-3 py-2" required>
        </div>
        <div class="flex justify-end gap-2">
          <button type="button" class="btn-ghost" @click="close()">Cancel</button>
          <button class="btn-armygreen">Apply</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Quick Edit Product --}}
  <div x-data="adjustProduct()" x-show="open" x-cloak class="fixed inset-0 modal flex items-end md:items-center justify-center p-4">
    <div @click.outside="close()" class="card w-full max-w-md p-4">
      <h3 class="text-lg font-semibold mb-2">Quick Edit Meat Cut</h3>
      <p class="text-sm text-muted mb-3 truncate">Cut: <span class="font-medium" x-text="name"></span></p>
      <form method="POST" action="{{ route('inventory.store') }}" class="space-y-3">
        @csrf
        <input type="hidden" name="kind" value="product">
        <input type="hidden" name="id" :value="id">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-sm block mb-1 text-muted">Forecast (kg)</label>
            <input type="number" step="0.001" min="0" name="set_forecasted_demand" :value="forecast" class="w-full input-dark rounded-xl px-3 py-2">
          </div>
          <div>
            <label class="text-sm block mb-1 text-muted">Price (₱/kg)</label>
            <input type="number" step="0.01" min="0" name="set_default_price" :value="price" class="w-full input-dark rounded-xl px-3 py-2">
          </div>
        </div>
        <div>
          <label class="text-sm block mb-1 text-muted">Unit Cost (₱/kg)</label>
          <input type="number" step="0.01" min="0" name="set_unit_cost" :value="cost" class="w-full input-dark rounded-xl px-3 py-2">
        </div>
        <div class="flex justify-end gap-2">
          <button type="button" class="btn-ghost" @click="close()">Cancel</button>
          <button class="btn-armygreen">Save</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Alpine helpers --}}
  <script>
    function inventoryIndex(){
      return {
        tableDense:false,
        productViewCards:false,
        cols:{ price:true, cost:true },
      }
    }
    function adjustMaterial(){
      return { open:false,id:null,name:'',
        openWith(p){ this.id=p.id; this.name=p.name; this.open=true; },
        close(){ this.open=false; } }
    }
    function adjustProduct(){
      return { open:false,id:null,name:'',forecast:0,price:0,cost:0,
        openWith(p){ this.id=p.id; this.name=p.name; this.forecast=p.forecast??0; this.price=p.price??0; this.cost=p.cost??0; this.open=true; },
        close(){ this.open=false; } }
    }
    function openAdjustMaterial(p){ document.querySelectorAll('[x-data="adjustMaterial()"]').forEach(el=>el.__x.$data.openWith(p)); }
    function openAdjustProduct(p){ document.querySelectorAll('[x-data="adjustProduct()"]').forEach(el=>el.__x.$data.openWith(p)); }
  </script>
@endsection
