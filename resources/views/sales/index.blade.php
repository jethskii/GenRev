{{-- resources/views/sales/index.blade.php (LIGHT THEME) --}}
@php
    /** @var \Illuminate\Support\Collection|\App\Models\Sale[] $sales */
    // Light-theme status chips
    $statusColors = [
        'Paid'      => 'bg-green-50 text-green-800 border-green-200',
        'Completed' => 'bg-blue-50 text-blue-800 border-blue-200',
        'Pending'   => 'bg-amber-50 text-amber-800 border-amber-200',
        'Cancelled' => 'bg-rose-50 text-rose-800 border-rose-200',
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
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<style>
  /* Typography + light utilities just for this page */
  body, p, ul, li, a, button { font-family: 'Jost', system-ui, -apple-system, Segoe UI, Roboto, sans-serif; }
  .light-card{
    background:#fff; border:1px solid #e5e7eb; border-radius:16px;
    box-shadow: 0 8px 18px rgba(17,24,39,.04);
  }
  .input-light{
    background:#fff; border:1px solid #e5e7eb; color:#111827; border-radius:12px; padding:.5rem .75rem;
  }
  .input-light::placeholder{ color:#9ca3af; }
  .input-light:focus{ outline:none; box-shadow:0 0 0 2px rgba(59,130,246,.25); border-color:#93c5fd; }

  .chip{
    border:1px solid; padding:.25rem .6rem; border-radius:999px; font-size:.72rem; font-weight:600;
  }

  /* Table */
  .table-wrap{ border:1px solid #e5e7eb; border-radius:14px; overflow:hidden; }
  thead th{ background:#f9fafb; color:#374151; font-weight:700; }
  tbody td{ color:#111827; }
</style>
@endsection

@section('content')
<div class="px-6 py-6 text-gray-900">
  {{-- HEADER --}}
  <div class="mb-4">
    <h1 class="text-2xl font-bold">Sales Overview</h1>
    <p class="text-sm text-gray-500">Trends and product breakdown at a glance.</p>
  </div>

  {{-- KPIs --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
    <div class="light-card p-4">
      <p class="text-gray-500 text-sm">Annual Revenue</p>
      <h2 class="text-2xl font-semibold">₱ {{ number_format($annualRevenue, 2) }}</h2>
    </div>
    <div class="light-card p-4">
      <p class="text-gray-500 text-sm">Monthly Revenue</p>
      <h2 class="text-2xl font-semibold">₱ {{ number_format($monthlyRevenue, 2) }}</h2>
    </div>
    <div class="light-card p-4">
      <p class="text-gray-500 text-sm">Orders</p>
      <h2 class="text-2xl font-semibold">{{ $orderCount }}</h2>
    </div>
  </div>

  {{-- CHARTS: Bar + Donut (light) --}}
  <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-6">
    <div class="light-card p-5 xl:col-span-2">
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-xl font-semibold">Sales Report (12 months)</h2>
        <div class="text-sm text-gray-500">{{ now()->format('M d, Y') }}</div>
      </div>
      <div id="salesBar3D" class="w-full" style="height: 340px;"></div>
    </div>

    <div class="light-card p-5">
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-xl font-semibold">Top Products (Revenue)</h2>
        <div class="text-sm text-gray-500">Last 90 days</div>
      </div>
      <div id="topProductsDonut" class="w-full" style="height: 340px;"></div>
      <ul id="topProductsLegend" class="mt-3 space-y-1 text-sm text-gray-700"></ul>
    </div>
  </div>

  {{-- SALES TABLE --}}
  <div class="light-card">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 px-5 py-4">
      <h2 class="text-xl font-semibold">Sales</h2>
      <div class="flex flex-wrap items-center gap-3">
        <div class="relative">
          <input id="salesSearch" type="text" placeholder="Search invoice / product / status…" class="w-64 input-light pr-8">
          <span class="absolute right-3 top-2.5 text-gray-400">⌕</span>
        </div>
        <input id="dateFilter" type="date" class="input-light">
        {{-- Primary = RED, Secondary = BLUE/GREEN (from mainlayout classes) --}}
        <button type="button" onclick="toggleAddSaleModal(true)" class="btn btn-primary">+ Add New Sale</button>
      </div>
    </div>

    <div class="px-5 pb-5">
      <div class="table-wrap">
        <table class="min-w-full border-collapse">
          <thead>
            <tr class="text-sm">
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
          <tbody id="salesTableBody" class="divide-y divide-gray-200">
            @forelse($sales as $row)
              @php
                $pname   = $row->display_product ?? ($row->product ?? optional($row->productRef)->product_name);
                $date    = $row->order_date ?? $row->date;
                $qty     = (float) ($row->quantity_kg ?? $row->quantity ?? 0);
                $unit    = (float) ($row->unit_price ?? $row->price ?? 0);
                $tot     = (float) ($row->total_price ?? $row->total ?? ($qty * $unit));
                $invoice = $row->invoice_number ?? $row->order_number;
              @endphp
              <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-4 py-3 whitespace-nowrap">{{ $invoice }}</td>
                <td class="px-4 py-3">{{ $pname }}</td>
                <td class="px-4 py-3">{{ $date ? \Carbon\Carbon::parse($date)->format('Y-m-d') : '' }}</td>
                <td class="px-4 py-3 text-right">{{ number_format($qty, 3) }}</td>
                <td class="px-4 py-3 text-right">₱ {{ number_format($unit, 2) }}</td>
                <td class="px-4 py-3 text-right">₱ {{ number_format($tot, 2) }}</td>
                <td class="px-4 py-3">
                  @php $cls = $statusColors[$row->status] ?? 'bg-gray-100 text-gray-800 border-gray-200'; @endphp
                  <span class="chip {{ $cls }} border">{{ $row->status ?? 'Pending' }}</span>
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center justify-center gap-2">
                    <a href="{{ route('sales.receipt', $row) }}" class="btn btn-secondary-blue">Receipt</a>
                    <a href="{{ route('sales.edit', $row) }}" class="btn btn-secondary-green">Edit</a>
                    <form action="{{ route('sales.destroy', $row) }}" method="POST" onsubmit="return confirm('Delete this sale?')" class="inline">
                      @csrf @method('DELETE')
                      <button class="btn btn-ghost text-rose-700 border border-rose-200 hover:bg-rose-50">Delete</button>
                    </form>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="px-4 py-6 text-center text-gray-600">No sales yet.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="flex items-center justify-between text-sm text-gray-600 mt-4">
        <div>
          <span class="mr-2">INV-</span>{{ now()->format('Ymd') }}<span class="mx-2">—</span>{{ $nextInvoice ?? '' }}
        </div>
        <div></div>
      </div>
    </div>
  </div>
</div>

{{-- Add Sale Modal (kept via partial, no style changes needed) --}}
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

  // Helper: read CSS vars from :root/body so charts match the layout theme
  function cssVar(name, fallback){
    const v = getComputedStyle(document.body).getPropertyValue(name).trim();
    return v || fallback;
  }
  const C_RED    = cssVar('--chart-1', '#ef4444');
  const C_GREEN  = cssVar('--chart-2', '#22c55e');
  const C_YELLOW = cssVar('--chart-3', '#f59e0b');
  const C_BLUE   = cssVar('--chart-4', '#3b82f6');

  // ----- BAR (12 months) -----
  (function(){
    const el = document.querySelector('#salesBar3D');
    if (!el || !window.ApexCharts) return;

    const months = @json(array_values($chartMonths));
    const totals = @json(array_map('floatval', $chartTotals));

    const options = {
      chart: {
        type:'bar', height:340, toolbar:{show:false}, foreColor:'#374151', background:'transparent',
        animations:{enabled:true, easing:'easeinout', speed:600}
      },
      grid:{borderColor:'#e5e7eb', strokeDashArray:4, padding:{left:10,right:10}},
      plotOptions:{ bar:{
        columnWidth:'45%', borderRadius:8, borderRadiusApplication:'around',
        dataLabels:{position:'top'},
      }},
      colors:[C_BLUE], // blue primary for bars
      dataLabels:{enabled:false},
      stroke:{show:false},
      series:[{name:'Revenue', data: totals}],
      xaxis:{
        categories:months,
        labels:{rotate:-15, style:{colors:'#374151'}},
        axisBorder:{color:'#e5e7eb'}, axisTicks:{color:'#e5e7eb'}
      },
      yaxis:{labels:{formatter:(v)=>'₱ '+Number(v).toLocaleString(), style:{colors:'#374151'}}},
      tooltip:{theme:'light', y:{formatter:(v)=>'₱ '+Number(v).toLocaleString()}},
      fill:{type:'gradient', gradient:{shade:'light', type:'vertical',
            gradientToColors:[C_GREEN], opacityFrom:.95, opacityTo:.9, stops:[0,60,100]}}
    };

    new ApexCharts(el, options).render();
  })();

  // ----- DONUT (Top Products, 90d) -----
  (function(){
    const el = document.querySelector('#topProductsDonut');
    if (!el || !window.ApexCharts) return;

    const labels = @json($donutLabels);
    const values = @json($donutValues);

    const colors = [C_RED, C_GREEN, C_YELLOW, C_BLUE, '#60a5fa', '#34d399']; // keep mix if >4 slices

    const chart = new ApexCharts(el, {
      chart:{type:'donut', height:340, foreColor:'#374151', background:'transparent'},
      series: values, labels: labels, legend:{show:false},
      colors: colors,
      tooltip:{theme:'light', y:{formatter:(v)=>'₱ '+Number(v).toLocaleString()}},
      dataLabels:{enabled:true, formatter:(val,opts)=>`${opts.w.globals.labels[opts.seriesIndex]}`},
      plotOptions:{ pie:{ donut:{ size:'68%', labels:{ show:true, total:{ show:true, label:'Total',
        formatter:(w)=>{ const sum=w.globals.seriesTotals.reduce((a,b)=>a+b,0); return '₱ '+Number(sum).toLocaleString(); }}}}} },
      stroke:{colors:['#ffffff']}
    });
    chart.render();

    const ul = document.getElementById('topProductsLegend');
    if (ul) {
      ul.innerHTML = labels.map((label,i)=>`<li class="flex justify-between"><span>${label}</span><span>₱ ${Number(values[i]??0).toLocaleString()}</span></li>`).join('');
    }
  })();
</script>
@endpush
