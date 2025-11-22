@extends('layout.mainlayout')
@section('title', 'Meat Production Inventory')

@section('styles')
<style>
  /* Light glass UI with sunset accents */
  :root{
    --page:#f7f8fb; --card:#ffffff; --ink:#0f172a; --muted:#667085; --border:#e7ebf0;
    --shadow:0 10px 30px rgba(17,24,39,.08);
    /* palette */
    --tangerine:#EF7722;     /* accent */
    --sunburst:#FFC100;      /* PACK chips / bars */
    --bag:#ef4444;           /* BAG chips / bars */
    --ok:#10b981; --warn:#f59e0b; --bad:#ef4444;
  }
  body{background:var(--page);color:var(--ink)}
  .page-wrap{max-width:1400px;margin-inline:auto;padding:1rem 1rem 2.25rem}

  /* Cards */
  .card{background:linear-gradient(180deg,#fff,rgba(255,255,255,.96));
    border:1px solid var(--border); border-radius:20px; box-shadow:var(--shadow); backdrop-filter:saturate(1.1) blur(6px)}
  .title{font-weight:900;letter-spacing:.2px}

  /* Toolbar */
  .toolbar{position:sticky;top:.5rem;z-index:20;display:flex;gap:.6rem;align-items:center;
    padding:.6rem;background:rgba(255,255,255,.9);border:1px solid var(--border);border-radius:14px;backdrop-filter:blur(6px)}
  .btn{border-radius:12px;padding:.55rem .9rem;border:1px solid var(--border);background:#fff;font-size:.8rem;display:inline-flex;align-items:center;gap:.35rem}
  .btn-primary{background:linear-gradient(180deg,#f5892d,var(--tangerine));color:#fff;border:1px solid rgba(239,119,34,.45)}
  .btn:hover{filter:brightness(1.03)}
  .input{height:40px;border-radius:12px;border:1px solid var(--border);padding:.55rem .75rem;background:#fff;font-size:.8rem}

  /* KPIs */
  .kpi .icon{width:40px;height:40px;border-radius:12px;border:1px solid var(--border);display:grid;place-items:center;font-size:1.2rem}
  .kpi .value{font-size:1.35rem;font-weight:800}

  /* Tables */
  .table-wrap{overflow:hidden;border-radius:14px;border:1px solid var(--border);background:#fff}
  .table{width:100%;border-collapse:separate;border-spacing:0}
  .table thead th{position:sticky;top:0;background:#f9fafb;border-bottom:1px solid var(--border);font-size:.72rem;letter-spacing:.06em;text-transform:uppercase;padding:.65rem .6rem;color:#334155;z-index:5}
  .table tbody td{padding:.65rem .6rem;border-bottom:1px dashed #edf2f7}
  .table tbody tr:last-child td{border-bottom:0}

  /* Pills */
  .pill{padding:.2rem .55rem;border-radius:999px;font-size:.72rem;font-weight:800;border:1px solid var(--border);white-space:nowrap}
  .ok{background:#ecfdf5;color:#065f46;border-color:rgba(16,185,129,.35)}
  .warn{background:#fffbeb;color:#92400e;border-color:rgba(245,158,11,.35)}
  .bad{background:#fef2f2;color:#991b1b;border-color:rgba(239,68,68,.35)}

  /* Chips + bars */
  .chip{display:inline-flex;align-items:center;gap:.5rem;border-radius:14px;border:1px solid var(--border);padding:.4rem .6rem;background:rgba(255,255,255,.9);font-size:.75rem}
  .chip-pack{background:rgba(255,193,0,.12);border-color:rgba(255,193,0,.38)}
  .chip-bag{background:rgba(239,68,68,.10);border-color:rgba(239,68,68,.38)}
  .bar{height:7px;border-radius:999px;background:#edf2f7;overflow:hidden}
  .bar>i{display:block;height:100%}
  .bar-pack>i{background:var(--sunburst)}
  .bar-bag>i{background:var(--bag)}
  .bar-kg>i{background:var(--ok)}

  /* Notifications */
  .alerts{display:grid;gap:.65rem;max-height:220px;overflow:auto;padding-right:.25rem}
  .alert{display:grid;grid-template-columns:40px 1fr auto;gap:.8rem;align-items:start;
    padding:.7rem .8rem;border:1px solid var(--border);border-radius:14px;background:linear-gradient(180deg,#fff,rgba(255,255,255,.92))}
  .alert .badge{width:40px;height:40px;border-radius:12px;display:grid;place-items:center;border:1px solid var(--border);background:#fff;font-size:1.2rem}
  .alert.critical{background:linear-gradient(180deg, rgba(254,242,242,.9), #fff)}
  .alert.warning {background:linear-gradient(180deg, rgba(255,251,235,.9), #fff)}
  .alert.info    {background:linear-gradient(180deg, rgba(239,246,255,.9), #fff)}
  .meta{font-size:.72rem;color:var(--muted)}
  .tag{font-size:.7rem;border-radius:999px;padding:.15rem .45rem;border:1px solid var(--border);background:#fff}
</style>
@endsection

@section('actions')
  <div class="toolbar">
    <a href="{{ route('materials.index') }}" class="btn btn-primary">Raw Materials</a>
    <a href="{{ route('production.index') }}" class="btn btn-primary">Production</a>
    <a href="{{ route('sales.index') }}" class="btn btn-primary">Sales</a>
    <form method="GET" action="{{ route('inventory.index') }}" class="ml-auto flex gap-2">
      <input
        class="input"
        type="text"
        name="q"
        value="{{ request('q') }}"
        placeholder="Search products, batches, materials">
      <button class="btn" type="submit">Search</button>
      <a class="btn" href="{{ route('inventory.index') }}">Reset</a>
    </form>
  </div>
@endsection

@section('content')
<div class="page-wrap space-y-6">

  {{-- Header --}}
  <div class="card p-4">
    <div class="flex items-center justify-between gap-4">
      <div>
        <h1 class="title text-xl">Meat Production Inventory</h1>
        <p class="text-sm" style="color:var(--muted)">Live stock with per-pack and per-bag visibility.</p>
      </div>
      <div class="flex flex-wrap gap-2 justify-end">
        <a href="{{ route('inventory.export.csv', ['q' => request('q')]) }}" class="btn">
          {{-- simple icon-ish feel --}}
          <span>📄</span><span>Export CSV</span>
        </a>
        <a href="{{ route('inventory.export.pdf', ['q' => request('q')]) }}" class="btn">
          <span>📑</span><span>Export PDF</span>
        </a>
      </div>
    </div>
  </div>

  {{-- Notifications --}}
  @if(!empty($productionAlarms ?? []))
  <div class="card p-4">
    <div class="flex items-center justify-between mb-2">
      <h3 class="font-semibold">
        Notifications
        <span class="tag">{{ count($productionAlarms) }} active</span>
      </h3>
      <span class="meta">Auto-generated from batch checks</span>
    </div>
    <div class="alerts">
      @foreach($productionAlarms as $alarm)
        @php
          $sev = $alarm['severity'] ?? 'info';
          $cls = $sev === 'critical' ? 'critical' : ($sev === 'warning' ? 'warning' : 'info');
          $icon = $sev === 'critical' ? '🚨' : ($sev === 'warning' ? '⚠️' : 'ℹ️');
        @endphp
        <div class="alert {{ $cls }}">
          <div class="badge">{{ $icon }}</div>
          <div>
            <div class="text-sm font-medium">{{ $alarm['message'] ?? '' }}</div>
            @if(!empty($alarm['hint']))
              <div class="meta mt-1">{{ $alarm['hint'] }}</div>
            @endif
          </div>
          <div class="meta capitalize">{{ $sev }}</div>
        </div>
      @endforeach
    </div>
  </div>
  @endif

  {{-- KPIs --}}
  <div class="grid grid-cols-2 lg:grid-cols-6 gap-4">
    <div class="card p-4 kpi">
      <div class="flex items-center gap-3">
        <span class="icon">🥩</span>
        <div>
          <div class="meta">Products</div>
          <div class="value">{{ $totalProducts ?? 0 }}</div>
        </div>
      </div>
    </div>
    <div class="card p-4 kpi">
      <div class="flex items-center gap-3">
        <span class="icon">📦</span>
        <div>
          <div class="meta">Materials (kg)</div>
          <div class="value">{{ number_format($totalMaterialsWeight ?? 0,3) }}</div>
        </div>
      </div>
    </div>
    <div class="card p-4 kpi">
      <div class="flex items-center gap-3">
        <span class="icon">🏷️</span>
        <div>
          <div class="meta">Batches</div>
          <div class="value">{{ $batchesInProduction ?? 0 }}</div>
        </div>
      </div>
    </div>
    <div class="card p-4 kpi">
      <div class="flex items-center gap-3">
        <span class="icon">✅</span>
        <div>
          <div class="meta">With Stock</div>
          <div class="value">{{ $batchesReleased ?? 0 }}</div>
        </div>
      </div>
    </div>
    <div class="card p-4 kpi">
      <div class="flex items-center gap-3">
        <span class="icon">⏳</span>
        <div>
          <div class="meta">Expiring ≤7d</div>
          <div class="value" style="color:var(--warn)">{{ $batchesExpiringSoon ?? 0 }}</div>
        </div>
      </div>
    </div>
    <div class="card p-4 kpi">
      <div class="flex items-center gap-3">
        <span class="icon">💸</span>
        <div>
          <div class="meta">Revenue (₱)</div>
          <div class="value">{{ number_format($totalRevenue ?? 0,2) }}</div>
        </div>
      </div>
    </div>
  </div>

  {{-- Products (kg + forecast) --}}
  <div class="card p-4">
    <div class="flex items-center justify-between mb-3">
      <h3 class="font-semibold">Finished Meat Cuts</h3>
      <a href="{{ route('products.index') }}" class="meta hover:underline">Manage Cuts →</a>
    </div>
    <div class="table-wrap">
      <table class="table min-w-[880px]">
        <thead><tr>
          <th>Cut</th>
          <th class="text-center">Variant</th>
          <th class="text-right">Available (kg)</th>
          <th class="text-right">Forecast (kg)</th>
          <th class="text-right">Days to Stockout</th>
        </tr></thead>
        <tbody>
          @forelse($products as $p)
            @php
              $forecastItem = collect($stockForecasting ?? [])->firstWhere('product_id', $p->id);
              $days = $forecastItem['days_until_stockout'] ?? null;
              $status = $forecastItem['forecast_status'] ?? 'normal';
              $availableKg = (float)($p->available_stock_kg ?? 0);
              $forecastKg  = (float)($p->forecasted_demand ?? 0);
            @endphp
            <tr>
              <td>
                <div class="flex items-center gap-3">
                  <img
                    src="{{ $p->image_url ?? asset('images/default-product.png') }}"
                    class="w-10 h-10 rounded-lg object-cover border border-[var(--border)]"
                    alt="{{ $p->product_name }}"
                  >
                  <div class="min-w-0">
                    <div class="font-medium truncate max-w-[200px]">{{ $p->product_name }}</div>
                    <div class="meta">
                      Last prod:
                      {{ optional($p->production_date)->format('Y-m-d') ?? '—' }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="text-center">{{ $p->category ?? '—' }}</td>
              <td class="text-right">{{ number_format($availableKg,3) }}</td>
              <td class="text-right">{{ number_format($forecastKg,3) }}</td>
              <td class="text-right">
                <span class="pill {{ $status === 'critical' ? 'bad' : ($status === 'warning' ? 'warn' : 'ok') }}">
                  {{ $days !== null ? number_format($days,1) : '∞' }}
                </span>
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="p-3 meta">No meat cuts found.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if(method_exists($products, 'onEachSide'))
      <div class="mt-3">{{ $products->onEachSide(1)->links() }}</div>
    @endif
  </div>

  {{-- Batch Traceability (per-pack & per-bag) --}}
  <div class="card p-4">
    <div class="flex items-center justify-between mb-3">
      <h3 class="font-semibold">Batch Traceability</h3>
      <a href="{{ route('production.index') }}" class="meta hover:underline">View All Batches →</a>
    </div>
    <div class="table-wrap">
      <table class="table min-w-[1100px]">
        <thead><tr>
          <th>Batch</th>
          <th>Cut</th>
          <th class="text-center">Produced</th>
          <th class="text-center">Expiry</th>
          <th class="text-right">Total (kg)</th>
          <th class="text-right">Avail (kg)</th>
          <th class="text-right">Packs</th>
          <th class="text-right">Bags</th>
          <th class="text-right">Pack Price</th>
          <th class="text-right">Bag Price</th>
          <th class="text-center">Status</th>
        </tr></thead>
        <tbody>
          @forelse($recentBatches as $b)
            @php
              $packs = (int) ($b->available_pack ?? 0);
              $bags  = (int) ($b->available_bag ?? 0);
              $packPrice = (float) ($b->unit_price_pack ?? 0);
              $bagPrice  = (float) ($b->unit_price_bag ?? 0);
              $days = $b->days_to_expiry ?? null;
              $qtyTotal = (float)($b->quantity ?? $b->qty_total ?? 0);
              $qtyAvail = (float)($b->current_inventory ?? $b->qty_available ?? 0);
              $ratioPack = min(100, max(0, $packs));
              $ratioBag  = min(100, max(0, $bags));
              $ratioKg   = $qtyTotal > 0 ? (100 * $qtyAvail / $qtyTotal) : 0;
              $status = $qtyAvail > 0 ? 'RELEASED' : 'CREATED';
            @endphp
            <tr>
              <td class="font-mono text-xs">{{ $b->batch_number ?? $b->batch_code }}</td>
              <td>{{ $b->product?->product_name ?? '—' }}</td>
              <td class="text-center">{{ optional($b->production_date ?? $b->produced_at)->format('Y-m-d') }}</td>
              <td class="text-center">{{ optional($b->expiration_date ?? $b->expiry_date)->format('Y-m-d') }}</td>
              <td class="text-right">{{ number_format($qtyTotal,3) }}</td>
              <td class="text-right">
                <div>{{ number_format($qtyAvail,3) }}</div>
                <div class="bar bar-kg mt-1"><i style="width:{{ number_format($ratioKg,2) }}%"></i></div>
              </td>
              <td class="text-right">
                <div class="chip chip-pack justify-end w-full">
                  <span class="font-semibold">{{ $packs }}</span><span class="meta">pack(s)</span>
                </div>
                <div class="bar bar-pack mt-1"><i style="width:{{ $ratioPack }}%"></i></div>
              </td>
              <td class="text-right">
                <div class="chip chip-bag justify-end w-full">
                  <span class="font-semibold">{{ $bags }}</span><span class="meta">bag(s)</span>
                </div>
                <div class="bar bar-bag mt-1"><i style="width:{{ $ratioBag }}%"></i></div>
              </td>
              <td class="text-right">₱{{ number_format($packPrice,2) }}</td>
              <td class="text-right">₱{{ number_format($bagPrice,2) }}</td>
              <td class="text-center">
                <div class="flex items-center justify-center gap-2">
                  <span class="pill {{ $status === 'RELEASED' ? 'ok' : 'warn' }}">{{ $status }}</span>
                  @if(!is_null($days))
                    <span class="pill {{ $days <= 3 ? 'bad' : ($days <= 7 ? 'warn' : 'ok') }}">{{ $days }}d</span>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="11" class="p-3 meta">No batches found.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- Materials --}}
  <div class="card p-4">
    <div class="flex items-center justify-between mb-3">
      <h3 class="font-semibold">Raw Materials</h3>
      <a href="{{ route('materials.index') }}" class="meta hover:underline">Manage Materials →</a>
    </div>
    <div class="table-wrap">
      <table class="table min-w-[720px]">
        <thead><tr>
          <th>Material</th>
          <th class="text-center">Unit</th>
          <th class="text-right">On-hand (kg)</th>
          <th class="text-right">Unit Price</th>
        </tr></thead>
        <tbody>
          @forelse($materials as $m)
            @php
              $qtyKg = (float)($m->quantity_kg ?? 0);
              $low   = $qtyKg <= ($lowThresh ?? 5);
            @endphp
            <tr>
              <td>
                <div class="flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full {{ $low ? 'bg-yellow-500' : 'bg-emerald-500' }}"></span>
                  <span class="font-medium">{{ $m->material_name ?? $m->name }}</span>
                </div>
              </td>
              <td class="text-center">{{ $m->unit ?? 'kg' }}</td>
              <td class="text-right {{ $low ? 'text-amber-600' : '' }}">{{ number_format($qtyKg,3) }}</td>
              <td class="text-right">₱{{ number_format((float)($m->unit_price ?? 0),2) }}</td>
            </tr>
          @empty
            <tr><td colspan="4" class="p-3 meta">No materials found.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if(method_exists($materials, 'onEachSide'))
      <div class="mt-3">{{ $materials->onEachSide(1)->links() }}</div>
    @endif

    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="card p-4">
        <h4 class="font-semibold mb-2">Material Usage (This Week)</h4>
        <div class="flex items-center justify-between meta mb-2">
          <div>Total Qty: {{ number_format($materialsUsageTotals['qty'] ?? 0,3) }} kg</div>
          <div>Total Cost: ₱{{ number_format($materialsUsageTotals['cost'] ?? 0,2) }}</div>
        </div>
        <div class="table-wrap">
          <table class="table min-w-[520px]">
            <thead><tr>
              <th>Material</th>
              <th class="text-right">Qty Used</th>
              <th class="text-right">Cost</th>
            </tr></thead>
            <tbody>
            @forelse($materialsUsage as $u)
              <tr>
                <td>{{ $u->material_name }}</td>
                <td class="text-right">{{ number_format((float)($u->qty_used ?? 0),3) }}</td>
                <td class="text-right">₱{{ number_format((float)($u->cost_used ?? 0),2) }}</td>
              </tr>
            @empty
              <tr><td colspan="3" class="p-3 meta">No usage recorded this week.</td></tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </div>

      <div class="card p-4">
        <h4 class="font-semibold mb-2">Expiry Risk ≤ 7 days</h4>
        <ul class="space-y-2 max-h-[360px] overflow-auto pr-1">
          @forelse($expiringSoon as $b)
            @php
              $days = $b->days_to_expiry ?? null;
              $cls  = $days <= 3 ? 'bad' : ($days <= 7 ? 'warn' : 'ok');
              $ratio = $days !== null ? max(0,min(100, 100-($days*100/7))) : 0;
              $invKg = (float)($b->current_inventory ?? 0);
            @endphp
            <li class="p-2 rounded-lg border border-[var(--border)] bg-white">
              <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                  <div class="truncate font-medium">
                    {{ $b->product?->product_name ?? 'Unknown cut' }}
                    <span class="meta">({{ $b->batch_number ?? $b->batch_code }})</span>
                  </div>
                  <div class="meta">
                    Exp: {{ optional($b->expiration_date ?? $b->expiry_date)->format('Y-m-d') ?? '—' }}
                  </div>
                </div>
                <div class="text-right">
                  @if(!is_null($days))
                    <span class="pill {{ $cls }}">{{ $days }}d</span>
                  @endif
                  <div class="meta mt-1">{{ number_format($invKg,3) }} kg</div>
                </div>
              </div>
              <div class="bar mt-2">
                <i style="background:{{ $days<=3?'var(--bad)':($days<=7?'var(--warn)':'var(--ok)') }};width:{{ $ratio }}%"></i>
              </div>
            </li>
          @empty
            <li class="meta">No cuts expiring soon.</li>
          @endforelse
        </ul>
      </div>
    </div>
  </div>

</div>
@endsection
