{{-- resources/views/sales/index.blade.php (LIGHT THEME, VARIANT-AWARE, HOOKED TO sale-modal) --}}
@php
    /** @var \Illuminate\Support\Collection|\App\Models\Sale[] $sales */

    // Status chip styles for the light theme
    $statusColors = [
        'Paid'      => 'bg-green-50 text-green-800 border-green-200',
        'Completed' => 'bg-blue-50 text-blue-800 border-blue-200',
        'Pending'   => 'bg-amber-50 text-amber-800 border-amber-200',
        'Cancelled' => 'bg-rose-50 text-rose-800 border-rose-200',
    ];

    // Safe fallbacks for charts
    $chartMonths    = $chartMonths    ?? ['Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec','Jan'];
    $chartTotals    = $chartTotals    ?? array_fill(0, count($chartMonths), 0);
    $annualRevenue  = $annualRevenue  ?? array_sum($chartTotals);
    $monthlyRevenue = $monthlyRevenue ?? (end($chartTotals) ?: 0);
    $orderCount     = $orderCount     ?? ($sales->count() ?? 0);

    /*
     | Variant-aware donut (last 90 days)
     | Group by Product + Type (type_label or production snapshot, else "Base").
     */
    $cutoff = \Carbon\Carbon::now('Asia/Manila')->subDays(90);
    $byVariant = [];

    foreach ($sales as $s) {
        $d = $s->order_date ?? $s->date ?? $s->created_at ?? null;
        if ($d && \Carbon\Carbon::parse($d)->lt($cutoff)) {
            continue;
        }

        $prod = $s->display_product
            ?? ($s->product ?? optional($s->productRef)->product_name ?? 'Unknown');

        $type = trim((string)($s->type_label ?? ''));
        if ($type === '' && isset($s->production) && $s->production?->product_name_snapshot) {
            $type = $s->production->product_name_snapshot;
        }
        if ($type === '') {
            $type = 'Base';
        }

        $key = $prod.' · '.$type;

        $qty  = (float) ($s->quantity_kg ?? $s->quantity ?? 0);
        $unit = (float) ($s->unit_price  ?? $s->price     ?? 0);
        $tot  = (float) ($s->total_price ?? $s->total     ?? ($qty * $unit));

        $byVariant[$key] = ($byVariant[$key] ?? 0) + $tot;
    }

    arsort($byVariant);
    $topPairsVariant = array_slice($byVariant, 0, 6, true);
    $donutLabels = array_keys($topPairsVariant) ?: ['No Data'];
    $donutValues = array_map('floatval', array_values($topPairsVariant)) ?: [0];
@endphp

@extends('layout.mainlayout')

@section('head')
<link href="https://fonts.googleapis.com/css2?family=Jost:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<style>
  :root{
    --chart-1:#ef4444;
    --chart-2:#22c55e;
    --chart-3:#f59e0b;
    --chart-4:#3b82f6;
  }

  /* Base typography */
  body, p, ul, li, a, button, input, select {
    font-family: 'Jost', system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
  }

  /* Cards */
  .light-card{
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:16px;
    box-shadow:0 8px 18px rgba(17,24,39,.04);
  }

  /* Inputs */
  .input-light{
    background:#fff;
    border:1px solid #e5e7eb;
    color:#111827;
    border-radius:12px;
    padding:.5rem .75rem;
    font-size:.875rem;
  }
  .input-light::placeholder{ color:#9ca3af; }
  .input-light:focus{
    outline:none;
    box-shadow:0 0 0 2px rgba(59,130,246,.25);
    border-color:#93c5fd;
  }

  /* Buttons */
  .btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:.5rem .9rem;
    border-radius:12px;
    border:1px solid transparent;
    font-size:.875rem;
    font-weight:600;
    cursor:pointer;
    transition:background-color .12s ease, filter .12s ease, border-color .12s ease, color .12s ease;
    white-space:nowrap;
  }
  .btn-primary{
    background:#ef4444;
    color:#fff;
    border-color:#ef4444;
  }
  .btn-primary:hover{ filter:brightness(.95); }

  .btn-secondary-blue{
    background:#fff;
    color:#1d4ed8;
    border-color:#bfdbfe;
  }
  .btn-secondary-blue:hover{
    background:#eff6ff;
  }

  .btn-secondary-green{
    background:#fff;
    color:#15803d;
    border-color:#bbf7d0;
  }
  .btn-secondary-green:hover{
    background:#ecfdf3;
  }

  .btn-ghost{
    background:transparent;
    color:#374151;
    border-color:transparent;
  }

  /* Chip */
  .chip{
    border:1px solid;
    padding:.25rem .6rem;
    border-radius:999px;
    font-size:.72rem;
    font-weight:600;
  }

  /* Table */
  .table-wrap{
    border:1px solid #e5e7eb;
    border-radius:14px;
    overflow:hidden;
  }
  thead th{
    background:#f9fafb;
    color:#374151;
    font-weight:700;
    font-size:.78rem;
  }
  tbody td{
    color:#111827;
    font-size:.84rem;
  }

  /* Unit chip (kg/pack/bag) */
  .u-chip{
    display:inline-flex;
    align-items:center;
    gap:.35rem;
    padding:.15rem .5rem;
    border-radius:999px;
    font-size:.7rem;
    font-weight:600;
    border:1px solid #e5e7eb;
    background:#f8fafc;
    color:#334155;
    margin-left:.4rem;
  }
</style>
@endsection

@section('content')
<div class="px-6 py-6 text-gray-900" id="salesOverviewRoot" aria-label="Sales Overview">

  {{-- Header --}}
  <div class="mb-4">
    <h1 class="text-2xl font-bold">Sales Overview</h1>
    <p class="text-sm text-gray-500">Trends and product breakdown at a glance.</p>
  </div>

  {{-- Inline alerts (top of page) --}}
  @if (session('error'))
    <div class="mb-3 rounded-xl border border-rose-200 bg-rose-50 text-rose-800 px-4 py-3 text-sm">
      {{ session('error') }}
    </div>
  @endif

  @if (session('info'))
    <div class="mb-3 rounded-xl border border-amber-200 bg-amber-50 text-amber-800 px-4 py-3 text-sm">
      {{ session('info') }}
    </div>
  @endif

  @if ($errors->any())
    <div class="mb-3 rounded-xl border border-rose-200 bg-rose-50 text-rose-800 px-4 py-3 text-sm">
      <div class="font-semibold mb-1">There were problems saving this sale:</div>
      <ul class="list-disc list-inside space-y-0.5">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- KPIs --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
    <div class="light-card p-4" role="status" aria-label="Annual Revenue">
      <p class="text-gray-500 text-sm">Annual Revenue</p>
      <h2 class="text-2xl font-semibold">₱ {{ number_format($annualRevenue, 2) }}</h2>
    </div>
    <div class="light-card p-4" role="status" aria-label="Monthly Revenue">
      <p class="text-gray-500 text-sm">Monthly Revenue</p>
      <h2 class="text-2xl font-semibold">₱ {{ number_format($monthlyRevenue, 2) }}</h2>
    </div>
    <div class="light-card p-4" role="status" aria-label="Orders Count">
      <p class="text-gray-500 text-sm">Orders</p>
      <h2 class="text-2xl font-semibold">{{ $orderCount }}</h2>
    </div>
  </div>

  {{-- Charts --}}
  <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-6">
    <div class="light-card p-5 xl:col-span-2">
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-xl font-semibold">Sales Report (12 months)</h2>
        <div class="text-sm text-gray-500">
          {{ now('Asia/Manila')->format('M d, Y') }}
        </div>
      </div>
      <div
        id="salesBar3D"
        class="w-full"
        style="height: 340px;"
        role="img"
        aria-label="12-month revenue bar chart"
      ></div>
      <noscript class="text-sm text-amber-700">Charts require JavaScript enabled.</noscript>
    </div>

    <div class="light-card p-5">
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-xl font-semibold">Top Products (Revenue by Type)</h2>
        <div class="text-sm text-gray-500">Last 90 days</div>
      </div>
      <div
        id="topProductsDonut"
        class="w-full"
        style="height: 340px;"
        role="img"
        aria-label="Top product types donut chart"
      ></div>
      <ul
        id="topProductsLegend"
        class="mt-3 space-y-1 text-sm text-gray-700"
        aria-label="Top product types legend"
      ></ul>
    </div>
  </div>

  {{-- Sales Table --}}
  <div class="light-card">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 px-5 py-4">
      <h2 class="text-xl font-semibold">Sales</h2>
      <div class="flex flex-wrap items-center gap-3">
        <div class="relative">
          <input
            id="salesSearch"
            type="text"
            placeholder="Search invoice / product / type / status…"
            class="w-64 input-light pr-8"
            aria-label="Search sales"
          >
          <span class="absolute right-3 top-2.5 text-gray-400" aria-hidden="true">⌕</span>
        </div>
        <input
          id="dateFilter"
          type="date"
          class="input-light"
          aria-label="Filter by date"
        >
        {{-- Uses openSaleModal() from sale-modal.blade.php --}}
        <button type="button" onclick="openSaleModal && openSaleModal()" class="btn btn-primary">
          + Add New Sale
        </button>
      </div>
    </div>

    <div class="px-5 pb-5">
      <div class="table-wrap">
        <table class="min-w-full border-collapse">
          <thead>
            <tr class="text-sm">
              <th class="px-4 py-3 text-left">Invoice</th>
              <th class="px-4 py-3 text-left">Product</th>
              <th class="px-4 py-3 text-left">Type</th>
              <th class="px-4 py-3 text-left">Date</th>
              <th class="px-4 py-3 text-right">Quantity</th>
              <th class="px-4 py-3 text-right">Unit Price</th>
              <th class="px-4 py-3 text-right">Total</th>
              <th class="px-4 py-3 text-left">Status</th>
              <th class="px-4 py-3 text-center">Actions</th>
            </tr>
          </thead>
          <tbody id="salesTableBody" class="divide-y divide-gray-200">
            @forelse($sales as $row)
              @php
                $pname   = $row->display_product ?? ($row->product ?? optional($row->productRef)->product_name ?? '—');
                $date    = $row->order_date ?? $row->date ?? $row->created_at ?? null;
                $qty     = (float) ($row->quantity_kg ?? $row->quantity ?? 0);
                $unit    = (float) ($row->unit_price ?? $row->price ?? 0);
                $tot     = (float) ($row->total_price ?? $row->total ?? ($qty * $unit));
                $invoice = $row->invoice_number
                          ?? $row->order_number
                          ?? ('INV-'.str_pad((string)$row->id, 3, '0', STR_PAD_LEFT));

                // unit type: kg/pack/bag (default kg if missing)
                $uTypeRaw = $row->unit_type ?? $row->unit ?? null;
                $uType    = in_array($uTypeRaw, ['kg','pack','bag'], true) ? $uTypeRaw : 'kg';

                // TYPE column (variant)
                $typeLabel = trim((string)($row->type_label ?? ''));
                if ($typeLabel === '' && isset($row->production) && $row->production?->product_name_snapshot) {
                    $typeLabel = $row->production->product_name_snapshot;
                }
                if ($typeLabel === '') {
                    $typeLabel = '—';
                }
              @endphp
              <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-4 py-3 whitespace-nowrap">{{ $invoice }}</td>
                <td class="px-4 py-3">{{ $pname }}</td>
                <td class="px-4 py-3">
                  <span class="chip border bg-slate-50 text-slate-700">
                    {{ $typeLabel }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  {{ $date ? \Carbon\Carbon::parse($date)->timezone('Asia/Manila')->format('Y-m-d') : '' }}
                </td>

                <td class="px-4 py-3 text-right">
                  {{ number_format($qty, $uType === 'kg' ? 3 : 0) }}
                  <span class="u-chip">{{ $uType }}</span>
                </td>

                <td class="px-4 py-3 text-right">
                  ₱ {{ number_format($unit, 2) }}
                  @if(in_array($uType, ['pack','bag']))
                    <span class="u-chip">per {{ $uType }}</span>
                  @endif
                </td>

                <td class="px-4 py-3 text-right">
                  ₱ {{ number_format($tot, 2) }}
                </td>

                <td class="px-4 py-3">
                  @php
                    $cls = $statusColors[$row->status] ?? 'bg-gray-100 text-gray-800 border-gray-200';
                  @endphp
                  <span class="chip {{ $cls }} border">
                    {{ $row->status ?? 'Pending' }}
                  </span>
                </td>

                <td class="px-4 py-3">
                  <div class="flex items-center justify-center gap-2">
                    <a
                      href="{{ route('sales.receipt', $row) }}"
                      class="btn btn-secondary-blue"
                    >
                      Receipt
                    </a>
                    <a
                      href="{{ route('sales.edit', $row) }}"
                      class="btn btn-secondary-green"
                    >
                      Edit
                    </a>
                    <form
                      action="{{ route('sales.destroy', $row) }}"
                      method="POST"
                      onsubmit="return confirm('Archive this sale?')"
                      class="inline"
                    >
                      @csrf
                      @method('DELETE')
                      <button
                        type="submit"
                        class="btn btn-ghost text-rose-700 border border-rose-200 hover:bg-rose-50"
                      >
                        Archive
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="px-4 py-6 text-center text-gray-600">
                  No sales yet.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="flex items-center justify-between text-sm text-gray-600 mt-4">
        <div>
          <span class="font-medium mr-1">Next invoice:</span>
          <span>{{ $nextInvoice ?? '—' }}</span>
        </div>
        <div></div>
      </div>
    </div>
  </div>
</div>

{{-- Use the new dialog-based sale modal partial --}}
@include('sales.partials.sale-modal', [
  'allProducts' => $products ?? null,
])
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  // --- Filters (includes Type column + Status) ---
  const search = document.getElementById('salesSearch');
  const dateFilter = document.getElementById('dateFilter');
  const rows = Array.from(document.querySelectorAll('#salesTableBody tr'));

  function applyFilters() {
    const term = (search?.value || '').toLowerCase();
    const date = (dateFilter?.value || '');

    rows.forEach(tr => {
      const tds = tr.querySelectorAll('td');
      if (!tds.length) return;

      const invoice = (tds[0].textContent || '').toLowerCase();
      const product = (tds[1].textContent || '').toLowerCase();
      const typeCol = (tds[2].textContent || '').toLowerCase();
      const rowDate = (tds[3].textContent || '').trim();
      const status  = (tds[7].textContent || '').toLowerCase();

      const matchTerm =
        !term ||
        invoice.includes(term) ||
        product.includes(term) ||
        typeCol.includes(term) ||
        status.includes(term);

      const matchDate = !date || rowDate === date;

      tr.style.display = (matchTerm && matchDate) ? '' : 'none';
    });
  }

  search?.addEventListener('input', applyFilters);
  dateFilter?.addEventListener('change', applyFilters);

  // --- Colors from CSS vars or fallbacks ---
  function cssVar(name, fallback){
    const v = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    return v || fallback;
  }
  const C_RED    = cssVar('--chart-1', '#ef4444');
  const C_GREEN  = cssVar('--chart-2', '#22c55e');
  const C_YELLOW = cssVar('--chart-3', '#f59e0b');
  const C_BLUE   = cssVar('--chart-4', '#3b82f6');

  // --- BAR (12 months) ---
  (function(){
    const el = document.querySelector('#salesBar3D');
    if (!el || typeof ApexCharts === 'undefined') return;

    const months = @json(array_values($chartMonths));
    const totals = @json(array_map('floatval', $chartTotals));

    const options = {
      chart: {
        type:'bar',
        height:340,
        toolbar:{show:false},
        foreColor:'#374151',
        background:'transparent',
        animations:{enabled:true, easing:'easeinout', speed:600}
      },
      grid:{
        borderColor:'#e5e7eb',
        strokeDashArray:4,
        padding:{left:10,right:10}
      },
      plotOptions:{
        bar:{
          columnWidth:'45%',
          borderRadius:8,
          borderRadiusApplication:'around'
        }
      },
      colors:[C_BLUE],
      dataLabels:{enabled:false},
      stroke:{show:false},
      series:[{name:'Revenue', data: totals}],
      xaxis:{
        categories:months,
        labels:{rotate:-15, style:{colors:'#374151'}},
        axisBorder:{color:'#e5e7eb'},
        axisTicks:{color:'#e5e7eb'}
      },
      yaxis:{
        labels:{
          formatter:(v)=>'₱ '+Number(v).toLocaleString(),
          style:{colors:'#374151'}
        }
      },
      tooltip:{
        theme:'light',
        y:{formatter:(v)=>'₱ '+Number(v).toLocaleString()}
      },
      fill:{
        type:'gradient',
        gradient:{
          shade:'light',
          type:'vertical',
          gradientToColors:[C_GREEN],
          opacityFrom:.95,
          opacityTo:.9,
          stops:[0,60,100]
        }
      }
    };

    new ApexCharts(el, options).render();
  })();

  // --- DONUT (Top Products by Type, last 90 days) ---
  (function(){
    const el = document.querySelector('#topProductsDonut');
    if (!el || typeof ApexCharts === 'undefined') return;

    const labels = @json($donutLabels); // "Product · Type"
    const values = @json($donutValues);
    const colors = [C_RED, C_GREEN, C_YELLOW, C_BLUE, '#60a5fa', '#34d399'];

    const chart = new ApexCharts(el, {
      chart:{
        type:'donut',
        height:340,
        foreColor:'#374151',
        background:'transparent'
      },
      series: values,
      labels: labels,
      legend:{show:false},
      colors: colors,
      tooltip:{
        theme:'light',
        y:{ formatter:(v)=>'₱ '+Number(v).toLocaleString() },
        x:{ formatter:(name)=>name } // full "Product · Type" in tooltip
      },
      dataLabels:{
        enabled:true,
        formatter:(val,opts)=>{
          const full = opts.w.globals.labels[opts.seriesIndex] || '';
          const parts = full.split('·');
          const type = (parts[1] || full).trim();
          return type; // show Type inside slice
        }
      },
      plotOptions:{
        pie:{
          donut:{
            size:'68%',
            labels:{
              show:true,
              total:{
                show:true,
                label:'Total',
                formatter:(w)=>{
                  const sum = w.globals.seriesTotals.reduce((a,b)=>a+b,0);
                  return '₱ '+Number(sum).toLocaleString();
                }
              }
            }
          }
        }
      },
      stroke:{ colors:['#ffffff'] }
    });

    chart.render();

    // Legend with full "Product · Type"
    const ul = document.getElementById('topProductsLegend');
    if (ul) {
      ul.innerHTML = labels.map((label,i)=>
        `<li class="flex justify-between">
          <span>${label}</span>
          <span>₱ ${Number(values[i] ?? 0).toLocaleString()}</span>
        </li>`
      ).join('');
    }
  })();
});
</script>
@endpush
