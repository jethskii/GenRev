@extends('layout.mainlayout')
@section('title', 'Meat Production Inventory')

@section('styles')
<style>
  /* light polish on your existing tokens / utilities */
  .page-wrap{max-width:1400px;margin-inline:auto}
  .card{background:linear-gradient(180deg,rgba(255,255,255,.04),rgba(255,255,255,.02));
        border:1px solid rgba(255,255,255,.08); box-shadow:0 8px 24px rgba(0,0,0,.25);
        backdrop-filter: blur(6px)}
  .neon-ring{box-shadow:0 0 0 1px rgba(130,255,130,.25),0 0 24px rgba(34,197,94,.12) inset}
  .kpi{display:grid;grid-template-columns:auto 1fr;gap:.5rem;align-items:center}
  .kpi .icon{width:36px;height:36px;border-radius:.875rem;border:1px solid rgba(255,255,255,.12);
             display:grid;place-items:center}
  .table-dark thead th{position:sticky;top:0;z-index:1;background:rgba(0,0,0,.35);backdrop-filter:blur(4px)}
  .table-dark tbody tr:hover{background:rgba(255,255,255,.045)}
  .pill{padding:.25rem .5rem;border-radius:.5rem;font-size:.75rem;font-weight:600;letter-spacing:.01em}
  .pill-ok{background:rgba(16,185,129,.12);color:rgb(167,243,208)}
  .pill-warn{background:rgba(245,158,11,.12);color:rgb(253,230,138)}
  .pill-bad{background:rgba(239,68,68,.12);color:rgb(254,202,202)}
  .list-divider li+li{border-top:1px dashed rgba(255,255,255,.08)}
  .progress{height:6px;border-radius:999px;background:rgba(255,255,255,.08);overflow:hidden}
  .progress > i{display:block;height:100%}
  @media (max-width: 1024px){
    .kpi-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
  }
</style>
@endsection

@section('actions')
  <div class="flex flex-wrap gap-2">
    <a href="{{ route('materials.index') }}" class="btn-armygreen">Raw Materials</a>
    <a href="{{ route('production.index') }}" class="btn-armygreen">Production Schedule</a>
    <a href="{{ route('sales.index') }}" class="btn-armygreen">Sales & Orders</a>
  </div>
@endsection

@section('content')
<div x-data="inventoryIndex()" class="page-wrap space-y-6">

  {{-- Production Alarms --}}
  @if(!empty($productionAlarms))
    <div class="card rounded-2xl p-4 border-l-4 border-amber-400/80">
      <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold text-amber-300 flex items-center gap-2">
          ⚠️ Production Alarms
        </h3>
        <span class="text-xs bg-amber-900/50 px-2 py-1 rounded">{{ count($productionAlarms) }} active</span>
      </div>
      <ul class="space-y-2 max-h-32 overflow-y-auto pr-1 list-divider">
        @foreach($productionAlarms as $alarm)
          <li class="flex items-start gap-3 text-sm py-1">
            <span class="mt-1 w-2 h-2 rounded-full
              {{ $alarm['severity'] === 'critical' ? 'bg-red-400' : ($alarm['severity'] === 'warning' ? 'bg-amber-400' : 'bg-blue-400') }}"></span>
            <span class="opacity-90">{{ $alarm['message'] }}</span>
          </li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- Filters --}}
  <div class="card rounded-2xl p-4">
    <form method="GET" class="grid md:grid-cols-4 gap-3 items-end">
      <div>
        <label class="text-sm block mb-1">Search</label>
        <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Cut name or material…" class="w-full input-dark rounded-xl px-3 py-2">
      </div>
      <div>
        <label class="text-sm block mb-1">Cut Category</label>
        <select name="cat" class="w-full input-dark rounded-xl px-3 py-2">
          <option value="">All Cuts</option>
          @foreach($categories as $c)
            <option value="{{ $c }}" @selected(($cat ?? null)===$c)>{{ $c }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="text-sm block mb-1">Low stock threshold (kg)</label>
        <input type="number" step="0.001" min="0" name="low_material_threshold" value="{{ $lowThresh }}" class="w-full input-dark rounded-xl px-3 py-2">
      </div>
      <div class="flex gap-2">
        <button class="btn-armygreen flex-1">Apply</button>
        <a href="{{ route('inventory.index') }}" class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/15">Reset</a>
      </div>
    </form>
  </div>

  {{-- KPIs --}}
  <div class="grid kpi-grid grid-cols-2 lg:grid-cols-6 gap-4">
    <div class="card rounded-2xl p-4 neon-ring">
      <div class="kpi">
        <span class="icon">🥩</span>
        <div>
          <div class="text-xs opacity-70">Finished Cuts</div>
          <div class="text-2xl font-semibold">{{ $totalProducts }}</div>
        </div>
      </div>
    </div>
    <div class="card rounded-2xl p-4">
      <div class="kpi">
        <span class="icon">📦</span>
        <div>
          <div class="text-xs opacity-70">Raw Materials (kg)</div>
          <div class="text-2xl font-semibold">{{ number_format($totalMaterialsWeight,3) }}</div>
        </div>
      </div>
    </div>
    <div class="card rounded-2xl p-4">
      <div class="kpi">
        <span class="icon">🏷️</span>
        <div>
          <div class="text-xs opacity-70">Batches (All)</div>
          <div class="text-2xl font-semibold">{{ $batchesInProduction }}</div>
        </div>
      </div>
    </div>
    <div class="card rounded-2xl p-4">
      <div class="kpi">
        <span class="icon">✅</span>
        <div>
          <div class="text-xs opacity-70">With Stock</div>
          <div class="text-2xl font-semibold">{{ $batchesReleased }}</div>
        </div>
      </div>
    </div>
    <div class="card rounded-2xl p-4">
      <div class="kpi">
        <span class="icon">⏳</span>
        <div>
          <div class="text-xs opacity-70">Expiring ≤7d</div>
          <div class="text-2xl font-semibold text-amber-300">{{ $batchesExpiringSoon }}</div>
        </div>
      </div>
    </div>
    <div class="card rounded-2xl p-4">
      <div class="kpi">
        <span class="icon">💸</span>
        <div>
          <div class="text-xs opacity-70">Revenue (₱)</div>
          <div class="text-2xl font-semibold">{{ number_format($totalRevenue,2) }}</div>
        </div>
      </div>
    </div>
  </div>

  {{-- Finished Cuts + Expiry Risk --}}
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 card rounded-2xl p-4">
      <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold">Finished Meat Cuts</h3>
        <a href="{{ route('products.index') }}" class="text-xs opacity-70 hover:opacity-100">Manage Cuts →</a>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm table-dark border-collapse min-w-[880px]">
          <thead>
          <tr class="text-left">
            <th class="p-2">Cut</th>
            <th class="p-2 text-center">Category</th>
            <th class="p-2 text-right">Available (kg)</th>
            <th class="p-2 text-right">Forecast (kg)</th>
            <th class="p-2 text-right">Days to Stockout</th>
            <th class="p-2 text-right">Price</th>
            <th class="p-2 text-right">Unit Cost</th>
            <th class="p-2"></th>
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
              <td class="p-2">
                <div class="flex items-center gap-3">
                  <img src="{{ $p->image_url ?? asset('images/default-product.png') }}" class="w-10 h-10 rounded-lg object-cover border border-white/10">
                  <div class="min-w-0">
                    <div class="font-medium truncate max-w-[180px]">{{ $p->product_name }}</div>
                    <div class="text-xs opacity-60">Last prod: {{ optional($p->production_date)->format('Y-m-d') ?: '—' }}</div>
                  </div>
                </div>
              </td>
              <td class="p-2 text-center">{{ $p->category ?? '—' }}</td>
              <td class="p-2 text-right">{{ number_format((float)($p->available_stock_kg ?? 0),3) }}</td>
              <td class="p-2 text-right">{{ number_format((float)($p->forecasted_demand ?? 0),3) }}</td>
              <td class="p-2 text-right">
                <span class="pill
                  {{ $status === 'critical' ? 'pill-bad' : ($status === 'warning' ? 'pill-warn' : 'pill-ok') }}">
                  {{ $days !== null ? number_format($days,1) : '∞' }}
                </span>
              </td>
              <td class="p-2 text-right">₱{{ number_format((float)($p->default_price ?? 0),2) }}</td>
              <td class="p-2 text-right">₱{{ number_format((float)($p->unit_cost ?? 0),2) }}</td>
              <td class="p-2 text-right">
                <div class="flex gap-2 justify-end">
                  <a href="{{ route('products.show',$p->id) }}" class="px-3 py-1 rounded-lg bg-white/10 text-xs hover:bg-white/15">View</a>
                  <button class="px-3 py-1 rounded-lg bg-emerald-800/50 text-xs hover:bg-emerald-800/60"
                          @click="openAdjustProduct({ id: {{ $p->id }}, name: @js($p->product_name), price: {{ (float)($p->default_price ?? 0) }}, forecast: {{ (float)($p->forecasted_demand ?? 0) }}, cost: {{ (float)($p->unit_cost ?? 0) }} })">
                    Quick Edit
                  </button>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="8" class="p-3 opacity-60">No meat cuts found.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
      <div class="mt-3">{{ $products->onEachSide(1)->links() }}</div>
    </div>

    <div class="card rounded-2xl p-4">
      <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold">🚨 Expiry Risk (≤ 7 days)</h3>
        <a href="{{ route('production.index') }}" class="text-xs opacity-70 hover:opacity-100">Manage Batches →</a>
      </div>
      <ul class="text-sm space-y-2 max-h-[520px] overflow-y-auto pr-1 list-divider">
        @forelse($expiringSoon as $b)
          @php
            $ratio = max(0,min(100, 100-($b->days_to_expiry ?? 0)*100/7));
            $bar = $b->days_to_expiry <= 3 ? 'background:rgba(239,68,68,.8)'
                 : ($b->days_to_expiry <= 7 ? 'background:rgba(245,158,11,.8)' : 'background:rgba(16,185,129,.8)');
          @endphp
          <li class="p-2 rounded-lg">
            <div class="flex items-center justify-between gap-2">
              <div class="min-w-0">
                <div class="truncate font-medium">{{ $b->product?->product_name }} <span class="opacity-60 font-normal">({{ $b->batch_number }})</span></div>
                <div class="text-xs opacity-60">Exp: {{ optional($b->expiration_date)->format('Y-m-d') ?? '—' }}</div>
              </div>
              <div class="text-right">
                <div class="text-amber-300 text-xs font-medium">{{ $b->days_to_expiry }} days</div>
                <div class="text-xs opacity-70">{{ number_format((float)$b->current_inventory,3) }} kg</div>
              </div>
            </div>
            <div class="progress mt-2"><i style="{{ $bar }};width:{{ $ratio }}%"></i></div>
          </li>
        @empty
          <li class="opacity-60">No cuts expiring soon.</li>
        @endforelse
      </ul>
    </div>
  </div>

  {{-- Batch Traceability --}}
  <div class="card rounded-2xl p-4">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-semibold">🔍 Batch Traceability</h3>
      <a href="{{ route('production.index') }}" class="text-xs opacity-70 hover:opacity-100">View All Batches →</a>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm table-dark border-collapse min-w-[960px]">
        <thead>
        <tr>
          <th class="p-2 text-left">Batch Code</th>
          <th class="p-2 text-left">Cut</th>
          <th class="p-2 text-center">Production Date</th>
          <th class="p-2 text-center">Expiry Date</th>
          <th class="p-2 text-right">Total (kg)</th>
          <th class="p-2 text-right">Available (kg)</th>
          <th class="p-2 text-center">Status</th>
          <th class="p-2 text-center">Days to Expiry</th>
        </tr>
        </thead>
        <tbody>
        @forelse($recentBatches as $batch)
          <tr>
            <td class="p-2 font-mono text-xs">{{ $batch->batch_code }}</td>
            <td class="p-2">{{ $batch->product?->product_name }}</td>
            <td class="p-2 text-center">{{ optional($batch->produced_at)->format('Y-m-d') }}</td>
            <td class="p-2 text-center">{{ optional($batch->expiry_date)->format('Y-m-d') }}</td>
            <td class="p-2 text-right">{{ number_format($batch->qty_total,3) }}</td>
            <td class="p-2 text-right">{{ number_format($batch->qty_available,3) }}</td>
            <td class="p-2 text-center">
              @php
                $status = $batch->status;
                $cls = $status === 'RELEASED' ? 'pill-ok' : ($status === 'QA_HOLD' ? 'pill-warn' : ($status === 'CREATED' ? 'pill' : 'pill'));
              @endphp
              <span class="pill {{ $cls }}">{{ $status }}</span>
            </td>
            <td class="p-2 text-center">
              @if($batch->days_to_expiry !== null)
                <span class="pill {{ $batch->days_to_expiry <= 3 ? 'pill-bad' : ($batch->days_to_expiry <= 7 ? 'pill-warn' : 'pill-ok') }}">
                  {{ $batch->days_to_expiry }}
                </span>
              @else
                <span class="opacity-50">—</span>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="8" class="p-3 opacity-60">No batches found.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- Materials + Usage --}}
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 card rounded-2xl p-4">
      <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold">Raw Materials</h3>
        <a href="{{ route('materials.index') }}" class="text-xs opacity-70 hover:opacity-100">Manage Materials →</a>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm table-dark border-collapse min-w-[720px]">
          <thead>
          <tr>
            <th class="p-2 text-left">Material</th>
            <th class="p-2 text-center">Unit</th>
            <th class="p-2 text-right">On-hand (kg)</th>
            <th class="p-2 text-right">Unit Price</th>
            <th class="p-2"></th>
          </tr>
          </thead>
          <tbody>
          @forelse($materials as $m)
            @php $low = (float)$m->quantity_kg <= $lowThresh; @endphp
            <tr>
              <td class="p-2">
                <div class="flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full {{ $low ? 'bg-amber-400' : 'bg-emerald-400' }}"></span>
                  <span class="font-medium">{{ $m->material_name ?? $m->name }}</span>
                </div>
              </td>
              <td class="p-2 text-center">{{ $m->unit ?? 'kg' }}</td>
              <td class="p-2 text-right {{ $low ? 'text-amber-300' : '' }}">{{ number_format((float)$m->quantity_kg,3) }}</td>
              <td class="p-2 text-right">₱{{ number_format((float)($m->unit_price ?? 0),2) }}</td>
              <td class="p-2 text-right">
                <button class="px-3 py-1 rounded-lg bg-emerald-800/50 text-xs hover:bg-emerald-800/60"
                        @click="openAdjustMaterial({ id: {{ $m->id }}, name: @js($m->material_name ?? $m->name) })">
                  Adjust
                </button>
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="p-3 opacity-60">No materials found.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
      <div class="mt-3">{{ $materials->onEachSide(1)->links() }}</div>
    </div>

    <div class="card rounded-2xl p-4">
      <h3 class="font-semibold mb-2">Material Usage (This Week)</h3>
      <div class="flex items-center justify-between text-xs opacity-70 mb-2">
        <div>Total Qty: {{ number_format($materialsUsageTotals['qty'],3) }} kg</div>
        <div>Total Cost: ₱{{ number_format($materialsUsageTotals['cost'],2) }}</div>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm table-dark border-collapse min-w-[520px]">
          <thead>
          <tr>
            <th class="p-2 text-left">Material</th>
            <th class="p-2 text-right">Qty Used</th>
            <th class="p-2 text-right">Cost</th>
          </tr>
          </thead>
          <tbody>
          @forelse($materialsUsage as $u)
            <tr>
              <td class="p-2">{{ $u->material_name }}</td>
              <td class="p-2 text-right">{{ number_format((float)$u->qty_used,3) }}</td>
              <td class="p-2 text-right">₱{{ number_format((float)$u->cost_used,2) }}</td>
            </tr>
          @empty
            <tr><td colspan="3" class="p-3 opacity-60">No usage recorded this week.</td></tr>
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
    <div @click.outside="close()" class="card rounded-2xl w-full max-w-md p-4">
      <h3 class="text-lg font-semibold mb-2">Adjust Raw Material</h3>
      <p class="text-sm opacity-80 mb-3 truncate">Material: <span class="font-medium" x-text="name"></span></p>
      <form method="POST" action="{{ route('inventory.store') }}" class="space-y-3">
        @csrf
        <input type="hidden" name="kind" value="material">
        <input type="hidden" name="id" :value="id">
        <div>
          <label class="text-sm block mb-1">Delta (kg) — use negative to deduct</label>
          <input type="number" step="0.001" name="delta_kg" class="w-full input-dark rounded-xl px-3 py-2" required>
        </div>
        <div class="flex justify-end gap-2">
          <button type="button" class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/15" @click="close()">Cancel</button>
          <button class="btn-armygreen">Apply</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Quick Edit Product --}}
  <div x-data="adjustProduct()" x-show="open" x-cloak class="fixed inset-0 modal flex items-end md:items-center justify-center p-4">
    <div @click.outside="close()" class="card rounded-2xl w-full max-w-md p-4">
      <h3 class="text-lg font-semibold mb-2">Quick Edit Meat Cut</h3>
      <p class="text-sm opacity-80 mb-3 truncate">Cut: <span class="font-medium" x-text="name"></span></p>
      <form method="POST" action="{{ route('inventory.store') }}" class="space-y-3">
        @csrf
        <input type="hidden" name="kind" value="product">
        <input type="hidden" name="id" :value="id">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-sm block mb-1">Forecast (kg)</label>
            <input type="number" step="0.001" min="0" name="set_forecasted_demand" :value="forecast" class="w-full input-dark rounded-xl px-3 py-2">
          </div>
          <div>
            <label class="text-sm block mb-1">Price (₱/kg)</label>
            <input type="number" step="0.01" min="0" name="set_default_price" :value="price" class="w-full input-dark rounded-xl px-3 py-2">
          </div>
        </div>
        <div>
          <label class="text-sm block mb-1">Unit Cost (₱/kg)</label>
          <input type="number" step="0.01" min="0" name="set_unit_cost" :value="cost" class="w-full input-dark rounded-xl px-3 py-2">
        </div>
        <div class="flex justify-end gap-2">
          <button type="button" class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/15" @click="close()">Cancel</button>
          <button class="btn-armygreen">Save</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Alpine helpers (unchanged logic) --}}
  <script>
    function inventoryIndex(){ return {} }
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
