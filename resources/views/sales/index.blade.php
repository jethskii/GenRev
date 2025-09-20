{{-- resources/views/sales/index.blade.php --}}
@php
    /** @var \Illuminate\Support\Collection|\App\Models\Sale[] $sales */
    $statusColors = [
        'Paid'      => 'bg-[#047705]/15 text-[#91F0A6] border-[#047705]/30',
        'Completed' => 'bg-sky-600/15 text-sky-300 border-sky-700/40',
        'Pending'   => 'bg-[#EDD100]/15 text-[#EDD100] border-[#EDD100]/30',
        'Cancelled' => 'bg-rose-600/15 text-rose-300 border-rose-700/40',
    ];

    // Optional data from controller (safe fallbacks here)
    $chartMonths    = $chartMonths    ?? ['Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec','Jan'];
    $chartTotals    = $chartTotals    ?? array_fill(0, count($chartMonths), 0);
    $annualRevenue  = $annualRevenue  ?? array_sum($chartTotals);
    $monthlyRevenue = $monthlyRevenue ?? (end($chartTotals) ?: 0);
    $orderCount     = $orderCount     ?? ($sales->count() ?? 0);

    // Donut data: Top products by revenue in the last 90 days (computed from $sales)
    $cutoff = \Carbon\Carbon::now()->subDays(90);
    $byProduct = [];
    foreach ($sales as $s) {
        $d = $s->order_date ?? $s->date;
        if ($d && \Carbon\Carbon::parse($d)->lt($cutoff)) continue;

        $name = $s->display_product ?? ($s->product ?? optional($s->productRef)->product_name ?? 'Unknown');
        $qty  = (float) ($s->quantity_kg ?? $s->quantity ?? 0);
        $unit = (float) ($s->unit_price ?? $s->price ?? 0);
        $tot  = (float) ($s->total_price ?? $s->total ?? ($qty * $unit));

        $byProduct[$name] = ($byProduct[$name] ?? 0) + $tot;
    }
    arsort($byProduct);
    $topPairs    = array_slice($byProduct, 0, 6, true);
    $donutLabels = array_keys($topPairs) ?: ['No Data'];
    $donutValues = array_map('floatval', array_values($topPairs)) ?: [0];
@endphp

@extends('layout.mainlayout')

@section('head')
<link href="https://fonts.googleapis.com/css2?family=Jost:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  body, p, ul, li, a, button { font-family: 'Jost', system-ui, -apple-system, Segoe UI, Roboto, sans-serif; }
  .liquid-card{
    position:relative; overflow:hidden; border-radius:16px;
    background:linear-gradient(135deg,#1F1E1E 0%, #001C00 100%);
    border:.5px solid rgba(255,255,255,.2);
    box-shadow:0 8px 32px rgba(0,28,0,.35);
    backdrop-filter:blur(8px);
  }
  .liquid-card::before{
    content:''; position:absolute; inset:0; pointer-events:none;
    background:linear-gradient(45deg, rgba(4,119,5,.10), rgba(237,209,0,.08), rgba(4,119,5,.10));
    animation:cardShine 8s ease infinite;
  }
  @keyframes cardShine {0%{opacity:.35} 50%{opacity:.15} 100%{opacity:.35}}
  .liquid-input{ background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.2); color:#fff; }
  .liquid-input::placeholder{ color:rgba(255,255,255,.6) }
  .liquid-input:focus{ outline:none; border-color:#047705; box-shadow:0 0 0 2px rgba(4,119,5,.3); }
  .chip{ border:1px solid; padding:.2rem .6rem; border-radius:999px; font-size:.72rem; }
  .btn-primary{
    background:linear-gradient(90deg,#047705 0%, #0aad0a 100%);
    color:#fff; border:1px solid rgba(255,255,255,.15);
    border-radius:12px; padding:.5rem 1rem; transition:.2s;
    box-shadow:0 4px 15px rgba(4,119,5,.35);
  }
  .btn-primary:hover{ transform:translateY(-1px); }
  .btn-ghost{ border:1px solid rgba(255,255,255,.15); color:#f8fafc; border-radius:10px; padding:.4rem .8rem; background:rgba(255,255,255,.03); }
  .table-wrap{ border:1px solid rgba(255,255,255,.15); border-radius:14px; overflow:hidden; }
  thead th{ background:rgba(255,255,255,.05); color:#cbd5e1; font-weight:600; }
  tbody td{ color:#e5e7eb; }
</style>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
@endsection

@section('content')
<div class="px-6 py-6">
  {{-- HEADER --}}
  <div class="mb-4">
    <h1 class="text-2xl font-semibold text-white">Sales Overview</h1>
    <p class="text-sm text-gray-400">3D bar + donut breakdown above, detailed records below.</p>
  </div>

  {{-- KPIs --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
    <div class="liquid-card p-4">
      <p class="text-gray-400 text-sm">Annual Revenue</p>
      <h2 class="text-2xl font-semibold text-white">₱ {{ number_format($annualRevenue, 2) }}</h2>
    </div>
    <div class="liquid-card p-4">
      <p class="text-gray-400 text-sm">Monthly Revenue</p>
      <h2 class="text-2xl font-semibold text-white">₱ {{ number_format($monthlyRevenue, 2) }}</h2>
    </div>
    <div class="liquid-card p-4">
      <p class="text-gray-400 text-sm">Orders</p>
      <h2 class="text-2xl font-semibold text-white">{{ $orderCount }}</h2>
    </div>
  </div>

  {{-- CHARTS: 3D Bar + Donut --}}
  <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-6">
    <div class="liquid-card p-5 xl:col-span-2">
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-xl font-semibold text-white">Sales Report (12 months)</h2>
        <div class="text-sm text-gray-300">{{ now()->format('M d, Y') }}</div>
      </div>
      <div id="salesBar3D" class="w-full" style="height: 340px;"></div>
    </div>

    <div class="liquid-card p-5">
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-xl font-semibold text-white">Top Products (Revenue)</h2>
        <div class="text-sm text-gray-300">Last 90 days</div>
      </div>
      <div id="topProductsDonut" class="w-full" style="height: 340px;"></div>
      <ul id="topProductsLegend" class="mt-3 space-y-1 text-sm text-gray-300"></ul>
    </div>
  </div>

  {{-- SALES TABLE --}}
  <div class="liquid-card">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 px-5 py-4">
      <h2 class="text-xl font-semibold text-white">Sales</h2>
      <div class="flex flex-wrap items-center gap-3">
        <div class="relative">
          <input id="salesSearch" type="text" placeholder="Search invoice / product / status…" class="w-64 rounded-xl liquid-input px-3 py-2 pr-8">
          <span class="absolute right-3 top-2.5 text-white/60">⌕</span>
        </div>
        <input id="dateFilter" type="date" class="rounded-xl liquid-input px-3 py-2">
        <button type="button" onclick="toggleAddSaleModal(true)" class="btn-primary">+ Add New Sale</button>
      </div>
    </div>

    <div class="px-5 pb-5">
      <div class="table-wrap">
        <table class="min-w-full border-collapse">
          <thead>
            <tr>
              <th class="px-4 py-3 text-left">Invoice</th>
              <th class="px-4 py-3 text-left">Product</th>
              <th class="px-4 py-3 text-left">Date</th>
              <th class="px-4 py-3 text-right">Quantity (kg)</th>
              <th class="px-4 py-3 text-right">Unit Price</th>
              <th class="px-4 py-3 text-right">Total</th>
              <th class="px-4 py-3 text-left">Status</th>
              <th class="px-4 py-3 text-center">Actions</th>
            </tr>
          </thead>
          <tbody id="salesTableBody" class="divide-y divide-white/10">
            @forelse($sales as $row)
              @php
                $pname   = $row->display_product ?? ($row->product ?? optional($row->productRef)->product_name);
                $date    = $row->order_date ?? $row->date;
                $qty     = (float) ($row->quantity_kg ?? $row->quantity ?? 0);
                $unit    = (float) ($row->unit_price ?? $row->price ?? 0);
                $tot     = (float) ($row->total_price ?? $row->total ?? ($qty * $unit));
                $invoice = $row->invoice_number ?? $row->order_number;
              @endphp
              <tr class="hover:bg-white/5 transition-colors">
                <td class="px-4 py-3 whitespace-nowrap">{{ $invoice }}</td>
                <td class="px-4 py-3">{{ $pname }}</td>
                <td class="px-4 py-3">{{ $date ? \Carbon\Carbon::parse($date)->format('Y-m-d') : '' }}</td>
                <td class="px-4 py-3 text-right">{{ number_format($qty, 3) }}</td>
                <td class="px-4 py-3 text-right">₱ {{ number_format($unit, 2) }}</td>
                <td class="px-4 py-3 text-right">₱ {{ number_format($tot, 2) }}</td>
                <td class="px-4 py-3">
                  @php $cls = $statusColors[$row->status] ?? 'bg-white/10 text-white/80 border-white/20'; @endphp
                  <span class="chip {{ $cls }} border">{{ $row->status ?? 'Pending' }}</span>
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center justify-center gap-2">
                    <a href="{{ route('sales.receipt', $row) }}" class="btn-ghost">Receipt</a>
                    <a href="{{ route('sales.edit', $row) }}" class="btn-ghost">Edit</a>
                    <form action="{{ route('sales.destroy', $row) }}" method="POST" onsubmit="return confirm('Delete this sale?')" class="inline">
                      @csrf @method('DELETE')
                      <button class="btn-ghost border-rose-800/60 text-rose-300 hover:bg-rose-900/20">Delete</button>
                    </form>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="px-4 py-6 text-center text-white/70">No sales yet.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="flex items-center justify-between text-sm text-white/70 mt-4">
        <div>
          <span class="mr-2">INV-</span>{{ now()->format('Ymd') }}<span class="mx-2">—</span>{{ $nextInvoice ?? '' }}
        </div>
        <div></div>
      </div>
    </div>
  </div>
</div>

{{-- Add Sale Modal --}}
@include('sales.partials.add-sale-modal', [
  'products' => $products ?? null,
  'statusOptions' => $statusOptions ?? ['Pending','Completed','Cancelled','Paid'],
  'nextInvoice' => $nextInvoice ?? null
])
@endsection

@push('scripts')
<script>
  // ----- filters -----
  const search = document.getElementById('salesSearch');
  const dateFilter = document.getElementById('dateFilter');
  const rows = Array.from(document.querySelectorAll('#salesTableBody tr'));
  function applyFilters() {
    const term = (search?.value || '').toLowerCase();
    const date = (dateFilter?.value || '');
    rows.forEach(tr => {
      const tds = tr.querySelectorAll('td'); if (!tds.length) return;
      const invoice = (tds[0].textContent || '').toLowerCase();
      const product = (tds[1].textContent || '').toLowerCase();
      const rowDate = (tds[2].textContent || '').trim();
      const status  = (tds[6].textContent || '').toLowerCase();
      const matchTerm = !term || invoice.includes(term) || product.includes(term) || status.includes(term);
      const matchDate = !date || rowDate === date;
      tr.style.display = (matchTerm && matchDate) ? '' : 'none';
    });
  }
  search?.addEventListener('input', applyFilters);
  dateFilter?.addEventListener('change', applyFilters);

  window.toggleAddSaleModal = (open) => {
    const el = document.getElementById('addSaleModal'); if (!el) return;
    el.classList.toggle('hidden', !open);
    el.classList.toggle('flex', open);
    if (open) {
      const form = el.querySelector('form');
      if (form) {
        form.reset();
        const btn = form.querySelector('button[type="submit"]');
        if (btn) { btn.disabled = false; btn.classList.remove('opacity-70','cursor-not-allowed'); }
      }
    }
  };

  // ----- 3D BAR -----
  (function(){
    const el = document.querySelector('#salesBar3D');
    if (!el || !window.ApexCharts) return;

    const months = @json(array_values($chartMonths));
    const totals = @json(array_map('floatval', $chartTotals));

    const options = {
      chart: {
        type:'bar', height:340, toolbar:{show:false}, foreColor:'#cbd5e1', background:'transparent',
        dropShadow:{enabled:true, top:10,left:8, blur:8, opacity:.35},
        animations:{enabled:true, easing:'easeinout', speed:700}
      },
      grid:{borderColor:'rgba(255,255,255,.12)', strokeDashArray:4, padding:{left:10,right:10}},
      plotOptions:{ bar:{
        columnWidth:'45%', borderRadius:8, borderRadiusApplication:'around',
        dataLabels:{position:'top'}, colors:{ranges:[{from:0,to:Number.MAX_VALUE,color:'#91F0A6'}]}
      }},
      dataLabels:{enabled:false},
      stroke:{show:true, width:6, colors:['rgba(0,0,0,0)']},
      series:[{name:'Revenue', data: totals}],
      xaxis:{categories:months, labels:{rotate:-15, style:{colors:'#cbd5e1'}},
             axisBorder:{color:'rgba(255,255,255,.2)'}, axisTicks:{color:'rgba(255,255,255,.2)'}},
      yaxis:{labels:{formatter:(v)=>'₱ '+Number(v).toLocaleString(), style:{colors:'#cbd5e1'}}},
      tooltip:{theme:'dark', y:{formatter:(v)=>'₱ '+Number(v).toLocaleString()}},
      fill:{type:'gradient', gradient:{shade:'dark', type:'vertical', shadeIntensity:.35,
            gradientToColors:['#047705'], inverseColors:false, opacityFrom:.95, opacityTo:.9, stops:[0,60,100]}},
      states:{hover:{filter:{type:'darken',value:.6}}, active:{filter:{type:'darken',value:.8}}}
    };

    el.style.filter = 'drop-shadow(0 12px 24px rgba(0,0,0,.35))';
    new ApexCharts(el, options).render();
  })();

  // ----- DONUT (Top Products, 90d) -----
  (function(){
    const el = document.querySelector('#topProductsDonut');
    if (!el || !window.ApexCharts) return;

    const labels = @json($donutLabels);
    const values = @json($donutValues);

    const chart = new ApexCharts(el, {
      chart:{type:'donut', height:340, foreColor:'#cbd5e1', background:'transparent'},
      series: values, labels: labels, legend:{show:false},
      tooltip:{theme:'dark', y:{formatter:(v)=>'₱ '+Number(v).toLocaleString()}},
      dataLabels:{enabled:true, formatter:(val,opts)=>`${opts.w.globals.labels[opts.seriesIndex]}`},
      plotOptions:{ pie:{ donut:{ size:'68%', labels:{ show:true, total:{ show:true, label:'Total',
        formatter:(w)=>{ const sum=w.globals.seriesTotals.reduce((a,b)=>a+b,0); return '₱ '+Number(sum).toLocaleString(); }}}}} },
      fill:{type:'gradient'}
    });
    chart.render();

    const ul = document.getElementById('topProductsLegend');
    if (ul) {
      ul.innerHTML = labels.map((label,i)=>`<li class="flex justify-between"><span>${label}</span><span>₱ ${Number(values[i]??0).toLocaleString()}</span></li>`).join('');
    }
  })();
</script>
@endpush
