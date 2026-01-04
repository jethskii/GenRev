{{-- resources/views/inventory/export_pdf.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Meat Production Inventory PDF</title>
  <style>
    /* Basic palette (no CSS vars so DomPDF is happier) */
    *{
      box-sizing:border-box;
      font-family: DejaVu Sans, Arial, sans-serif;
    }
    body{
      background:#f7f8fb;
      color:#0f172a;
      margin:0;
      padding:0;
      font-size:12px;
    }
    .page-wrap{
      max-width:100%;
      padding:12px;
    }
    .card{
      background:#ffffff;
      border:1px solid #e7ebf0;
      border-radius:12px;
      margin-bottom:12px;
      padding:10px;
    }
    .title{
      font-weight:900;
      letter-spacing:.2px;
      font-size:16px;
    }
    .text-sm{font-size:11px}
    .meta{font-size:10px;color:#667085}
    .tag{
      font-size:9px;
      border-radius:999px;
      padding:2px 6px;
      border:1px solid #e7ebf0;
      background:#fff;
    }

    /* Simple utility classes */
    .flex{display:flex}
    .items-center{align-items:center}
    .justify-between{justify-content:space-between}
    .gap-2{gap:6px}
    .gap-3{gap:8px}
    .gap-4{gap:10px}
    .text-right{text-align:right}
    .text-center{text-align:center}
    .font-semibold{font-weight:600}
    .font-medium{font-weight:500}
    .font-mono{
      font-family:ui-monospace,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace;
    }
    .mt-1{margin-top:4px}
    .mt-2{margin-top:6px}
    .mt-3{margin-top:8px}
    .mb-2{margin-bottom:6px}
    .mb-3{margin-bottom:8px}

    /* KPI cards */
    .kpi .icon{
      width:30px;
      height:30px;
      border-radius:8px;
      border:1px solid #e7ebf0;
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:14px;
    }
    .kpi .value{
      font-size:15px;
      font-weight:800;
    }

    /* Tables */
    .table-wrap{
      width:100%;
      border-radius:10px;
      border:1px solid #e7ebf0;
      background:#fff;
      overflow:hidden;
    }
    table.table{
      width:100%;
      border-collapse:collapse;
    }
    .table thead th{
      background:#f9fafb;
      border-bottom:1px solid #e7ebf0;
      font-size:9px;
      text-transform:uppercase;
      padding:5px 4px;
      color:#334155;
    }
    .table tbody td{
      padding:5px 4px;
      border-bottom:1px dashed #edf2f7;
      font-size:10px;
    }
    .table tbody tr:last-child td{
      border-bottom:0;
    }

    /* Pills */
    .pill{
      padding:2px 7px;
      border-radius:999px;
      font-size:9px;
      font-weight:700;
      border:1px solid #e7ebf0;
      white-space:nowrap;
      display:inline-block;
    }
    .ok{
      background:#ecfdf5;
      color:#065f46;
      border-color:rgba(16,185,129,.35);
    }
    .warn{
      background:#fffbeb;
      color:#92400e;
      border-color:rgba(245,158,11,.35);
    }
    .bad{
      background:#fef2f2;
      color:#991b1b;
      border-color:rgba(239,68,68,.35);
    }

    /* Chips */
    .chip{
      display:inline-flex;
      align-items:center;
      gap:4px;
      border-radius:10px;
      border:1px solid #e7ebf0;
      padding:3px 5px;
      background:#ffffff;
      font-size:9px;
    }
    .chip-pack{
      background:rgba(255,193,0,.12);
      border-color:rgba(255,193,0,.38);
    }
    .chip-bag{
      background:rgba(239,68,68,.10);
      border-color:rgba(239,68,68,.38);
    }

    /* Bars */
    .bar{
      height:5px;
      border-radius:999px;
      background:#edf2f7;
      overflow:hidden;
    }
    .bar > i{
      display:block;
      height:100%;
    }
    .bar-kg > i{background:#10b981;}
    .bar-pack > i{background:#FFC100;}
    .bar-bag > i{background:#ef4444;}

    /* Alerts (no CSS grid to keep DomPDF happy) */
    .alerts{
      width:100%;
    }
    .alert{
      display:flex;
      align-items:flex-start;
      justify-content:space-between;
      gap:6px;
      padding:6px 7px;
      border:1px solid #e7ebf0;
      border-radius:10px;
      background:#ffffff;
      margin-bottom:4px;
    }
    .alert .left{
      display:flex;
      align-items:flex-start;
      gap:6px;
      flex:1 1 auto;
    }
    .alert .badge{
      width:26px;
      height:26px;
      border-radius:8px;
      display:flex;
      align-items:center;
      justify-content:center;
      border:1px solid #e7ebf0;
      background:#fff;
      font-size:12px;
      flex-shrink:0;
    }
    .alert.critical{
      background:rgba(254,242,242,.9);
    }
    .alert.warning{
      background:rgba(255,251,235,.9);
    }
    .alert.info{
      background:rgba(239,246,255,.9);
    }

    .w-10{
      width:26px;
      height:26px;
      object-fit:cover;
      border-radius:6px;
      border:1px solid #e7ebf0;
    }

  </style>
</head>
<body>
<div class="page-wrap">

  {{-- Header --}}
  <div class="card">
    <div class="flex justify-between items-center gap-4">
      <div>
        <div class="title">Meat Production Inventory</div>
        <p class="text-sm" style="color:#667085">Live stock with per-pack and per-bag visibility.</p>
        @if(!empty($search))
          <p class="meta">Filtered by search: "{{ $search }}"</p>
        @endif
      </div>
      <div class="meta">
        Generated at: {{ now()->format('Y-m-d H:i') }}
      </div>
    </div>
  </div>

  {{-- Notifications --}}
  @if(!empty($productionAlarms ?? []))
    <div class="card">
      <div class="flex justify-between items-center mb-2">
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
            <div class="left">
              <div class="badge">{{ $icon }}</div>
              <div>
                <div class="text-sm font-medium">{{ $alarm['message'] ?? '' }}</div>
                @if(!empty($alarm['hint']))
                  <div class="meta mt-1">{{ $alarm['hint'] }}</div>
                @endif
              </div>
            </div>
            <div class="meta" style="text-transform:capitalize">{{ $sev }}</div>
          </div>
        @endforeach
      </div>
    </div>
  @endif

  {{-- KPIs --}}
  <div class="flex gap-4" style="flex-wrap:wrap">
    <div class="card kpi" style="flex:1 1 140px">
      <div class="flex items-center gap-3">
        <span class="icon">🥩</span>
        <div>
          <div class="meta">Products</div>
          <div class="value">{{ $totalProducts ?? 0 }}</div>
        </div>
      </div>
    </div>
    <div class="card kpi" style="flex:1 1 140px">
      <div class="flex items-center gap-3">
        <span class="icon">📦</span>
        <div>
          <div class="meta">Materials (kg)</div>
          <div class="value">{{ number_format($totalMaterialsWeight ?? 0,3) }}</div>
        </div>
      </div>
    </div>
    <div class="card kpi" style="flex:1 1 140px">
      <div class="flex items-center gap-3">
        <span class="icon">🏷️</span>
        <div>
          <div class="meta">Batches</div>
          <div class="value">{{ $batchesInProduction ?? 0 }}</div>
        </div>
      </div>
    </div>
    <div class="card kpi" style="flex:1 1 140px">
      <div class="flex items-center gap-3">
        <span class="icon">✅</span>
        <div>
          <div class="meta">With Stock</div>
          <div class="value">{{ $batchesReleased ?? 0 }}</div>
        </div>
      </div>
    </div>
    <div class="card kpi" style="flex:1 1 140px">
      <div class="flex items-center gap-3">
        <span class="icon">⏳</span>
        <div>
          <div class="meta">Expiring ≤7d</div>
          <div class="value" style="color:#f59e0b">{{ $batchesExpiringSoon ?? 0 }}</div>
        </div>
      </div>
    </div>
    <div class="card kpi" style="flex:1 1 140px">
      <div class="flex items-center gap-3">
        <span class="icon">💸</span>
        <div>
          <div class="meta">Revenue (₱)</div>
          <div class="value">{{ number_format($totalRevenue ?? 0,2) }}</div>
        </div>
      </div>
    </div>
  </div>

  {{-- Finished Meat Cuts --}}
  <div class="card">
    <h3 class="font-semibold mb-2">Finished Meat Cuts</h3>
    <div class="table-wrap">
      <table class="table">
        <thead>
        <tr>
          <th>Cut</th>
          <th class="text-center">Variant</th>
          <th class="text-right">Available (kg)</th>
          <th class="text-right">Forecast (kg)</th>
          <th class="text-right">Days to Stockout</th>
        </tr>
        </thead>
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
                <div>
                  <div class="font-medium">{{ $p->product_name }}</div>
                  <div class="meta">
                    Last prod: {{ optional($p->production_date)->format('Y-m-d') ?? '—' }}
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
          <tr><td colspan="5" class="meta">No meat cuts found.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- Batch Traceability --}}
  <div class="card">
    <h3 class="font-semibold mb-2">Batch Traceability</h3>
    <div class="table-wrap">
      <table class="table">
        <thead>
        <tr>
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
        </tr>
        </thead>
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
              <div class="chip chip-pack">
                <span class="font-semibold">{{ $packs }}</span>
                <span class="meta">pack(s)</span>
              </div>
              <div class="bar bar-pack mt-1"><i style="width:{{ $ratioPack }}%"></i></div>
            </td>
            <td class="text-right">
              <div class="chip chip-bag">
                <span class="font-semibold">{{ $bags }}</span>
                <span class="meta">bag(s)</span>
              </div>
              <div class="bar bar-bag mt-1"><i style="width:{{ $ratioBag }}%"></i></div>
            </td>
            <td class="text-right">₱{{ number_format($packPrice,2) }}</td>
            <td class="text-right">₱{{ number_format($bagPrice,2) }}</td>
            <td class="text-center">
              <span class="pill {{ $status === 'RELEASED' ? 'ok' : 'warn' }}">{{ $status }}</span>
              @if(!is_null($days))
                <span class="pill {{ $days <= 3 ? 'bad' : ($days <= 7 ? 'warn' : 'ok') }}">{{ $days }}d</span>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="11" class="meta">No batches found.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- Raw Materials + Usage + Expiry Risk --}}
  <div class="card">
    <h3 class="font-semibold mb-2">Raw Materials</h3>
    <div class="table-wrap">
      <table class="table">
        <thead>
        <tr>
          <th>Material</th>
          <th class="text-center">Unit</th>
          <th class="text-right">On-hand (kg)</th>
          <th class="text-right">Unit Price</th>
        </tr>
        </thead>
        <tbody>
        @forelse($materials as $m)
          @php
            $qtyKg = (float)($m->quantity_kg ?? 0);
            $low   = $qtyKg <= ($lowThresh ?? 5);
          @endphp
          <tr>
            <td>
              <span class="font-medium">{{ $m->material_name ?? $m->name }}</span>
              @if($low)
                <span class="meta">(Low)</span>
              @endif
            </td>
            <td class="text-center">{{ $m->unit ?? 'kg' }}</td>
            <td class="text-right">{{ number_format($qtyKg,3) }}</td>
            <td class="text-right">₱{{ number_format((float)($m->unit_price ?? 0),2) }}</td>
          </tr>
        @empty
          <tr><td colspan="4" class="meta">No materials found.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>

    <div class="flex gap-4 mt-3" style="flex-wrap:wrap">
      {{-- Material Usage --}}
      <div class="card" style="flex:1 1 48%;margin-bottom:0">
        <h4 class="font-semibold mb-2">Material Usage (This Week)</h4>
        <div class="flex justify-between meta mb-2">
          <div>Total Qty: {{ number_format($materialsUsageTotals['qty'] ?? 0,3) }} kg</div>
          <div>Total Cost: ₱{{ number_format($materialsUsageTotals['cost'] ?? 0,2) }}</div>
        </div>
        <div class="table-wrap">
          <table class="table">
            <thead>
            <tr>
              <th>Material</th>
              <th class="text-right">Qty Used</th>
              <th class="text-right">Cost</th>
            </tr>
            </thead>
            <tbody>
            @forelse($materialsUsage as $u)
              <tr>
                <td>{{ $u->material_name }}</td>
                <td class="text-right">{{ number_format((float)($u->qty_used ?? 0),3) }}</td>
                <td class="text-right">₱{{ number_format((float)($u->cost_used ?? 0),2) }}</td>
              </tr>
            @empty
              <tr><td colspan="3" class="meta">No usage recorded this week.</td></tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </div>

      {{-- Expiry Risk --}}
      <div class="card" style="flex:1 1 48%;margin-bottom:0">
        <h4 class="font-semibold mb-2">Expiry Risk ≤ 7 days</h4>
        @forelse($expiringSoon as $b)
          @php
            $days = $b->days_to_expiry ?? null;
            $cls  = $days <= 3 ? 'bad' : ($days <= 7 ? 'warn' : 'ok');
            $ratio = $days !== null ? max(0,min(100, 100-($days*100/7))) : 0;
            $invKg = (float)($b->current_inventory ?? 0);
          @endphp
          <div class="card" style="margin-bottom:4px;padding:6px">
            <div class="flex justify-between items-center gap-3">
              <div>
                <div class="font-medium">
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
              <i style="background:{{ $days<=3?'#ef4444':($days<=7?'#f59e0b':'#10b981') }};width:{{ $ratio }}%"></i>
            </div>
          </div>
        @empty
          <p class="meta">No cuts expiring soon.</p>
        @endforelse
      </div>
    </div>
  </div>

</div>
</body>
</html>
