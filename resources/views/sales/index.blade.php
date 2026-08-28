{{-- resources/views/sales/index.blade.php (LIGHT THEME + PACK/BAG PRICES + FIXED CHART INIT + PDF EXPORT + MULTI-SELECT + FLASH POPUP + SCROLLABLE ADD-SALE MODAL) --}}
@php
    /** @var \Illuminate\Support\Collection|\App\Models\Sale[] $sales */
    $statusColors = [
        'Paid'      => 'bg-green-50 text-green-800 border-green-200',
        'Completed' => 'bg-blue-50 text-blue-800 border-blue-200',
        'Pending'   => 'bg-amber-50 text-amber-800 border-amber-200',
        'Cancelled' => 'bg-rose-50 text-rose-800 border-rose-200',
    ];

    $chartMonths    = $chartMonths    ?? ['Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec','Jan'];
    $chartTotals    = $chartTotals    ?? array_fill(0, count($chartMonths), 0);
    $annualRevenue  = $annualRevenue  ?? array_sum($chartTotals);
    $monthlyRevenue = $monthlyRevenue ?? (end($chartTotals) ?: 0);
    $orderCount     = $orderCount     ?? ($sales->count() ?? 0);

    // Variant-aware donut (last 90 days) – uses actual sales only, no forecasting.
    $cutoff = \Carbon\Carbon::now()->subDays(90);
    $byVariant = [];
    foreach ($sales as $s) {
        $d = $s->order_date ?? $s->date ?? $s->created_at ?? null;
        if ($d && \Carbon\Carbon::parse($d)->lt($cutoff)) continue;

        $prod = $s->display_product
            ?? ($s->product ?? optional($s->productRef)->product_name ?? 'Unknown');

        $type = trim((string)($s->type_label ?? ''));
        if ($type === '' && isset($s->production) && $s->production?->product_name_snapshot) {
            $type = $s->production->product_name_snapshot;
        }
        if ($type === '') $type = 'Base';

        $label = $prod . ' · ' . $type;

        $qty  = (float) ($s->quantity_kg ?? $s->quantity ?? 0);
        $unit = (float) ($s->unit_price  ?? $s->price     ?? 0);
        $tot  = (float) ($s->total_price ?? $s->total     ?? ($qty * $unit));

        $byVariant[$label] = ($byVariant[$label] ?? 0) + $tot;
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

  body, p, ul, li, a, button {
    font-family: 'Jost', system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
  }

  .light-card{
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:16px;
    box-shadow:0 8px 18px rgba(17,24,39,.04);
  }

  .input-light{
    background:#fff;
    border:1px solid #e5e7eb;
    color:#111827;
    border-radius:12px;
    padding:.5rem .75rem;
  }
  .input-light::placeholder{ color:#9ca3af; }
  .input-light:focus{
    outline:none;
    box-shadow:0 0 0 2px rgba(59,130,246,.25);
    border-color:#93c5fd;
  }

  .btn{ @apply inline-flex items-center justify-center px-3 py-2 rounded-md border text-sm font-medium; }
  .btn-primary{ @apply bg-blue-600 text-white border-blue-600 hover:bg-blue-700; }
  .btn-secondary{ @apply bg-white text-gray-700 border-gray-300 hover:bg-gray-50; }
  .btn-secondary-blue{ @apply bg-white text-blue-700 border-blue-200 hover:bg-blue-50; }
  .btn-secondary-green{ @apply bg-white text-green-700 border-green-200 hover:bg-green-50; }
  .btn-ghost{ @apply bg-transparent; }

  .chip{
    border:1px solid;
    padding:.25rem .6rem;
    border-radius:999px;
    font-size:.72rem;
    font-weight:600;
  }

  .table-wrap{
    border:1px solid #e5e7eb;
    border-radius:14px;
    overflow-x:auto;
    -webkit-overflow-scrolling:touch;
  }

  thead th{
    background:#f9fafb;
    color:#374151;
    font-weight:700;
  }

  tbody td{ color:#111827; }

  /* Unit type chip (kg / pack / bag) */
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

  /* Pack/bag price chips under product */
  .price-chip{
    display:inline-flex;
    align-items:center;
    gap:.35rem;
    padding:.12rem .45rem;
    border-radius:999px;
    font-size:.68rem;
    font-weight:600;
    border:1px solid #e5e7eb;
    background:#f8fafc;
    color:#334155;
  }

  .price-row{
    margin-top:.25rem;
    display:flex;
    flex-wrap:wrap;
    gap:.35rem;
  }

  /* Exporting state */
  #salesOverviewRoot.exporting .light-card{
    background:#ffffff !important;
  }

  /* Selection column */
  .select-col{ width: 38px; }
  .row-select{ width: 16px; height: 16px; }

  /* Bulk bar */
  #bulkBar{
    position: sticky;
    top: 0;
    z-index: 30;
    display: none;
  }
  #bulkBar.active{ display: block; }

  /* Flash popup (center bubble) */
  #flashModal{
    transition: opacity .25s ease, transform .25s ease;
  }
  #flashModal.hidden-soft{
    opacity:0;
    pointer-events:none;
    transform:translateY(-10px) scale(.98);
  }

  /* SCROLLABLE ADD-SALE MODAL
     This keeps the modal panel within the viewport and allows its content to scroll */
  #addSaleModal {
    overflow-y: auto;
  }
  #addSaleModal > div {
    max-height: calc(100vh - 3rem);
    overflow-y: auto;
  }

  @media print{
    body{ background:#ffffff !important; }
    .btn,
    .input-light,
    #exportPdfBtn,
    #bulkBar,
    [onclick]{ display:none !important; }
    .light-card{
      break-inside: avoid;
      box-shadow:none;
      border:1px solid #e5e7eb;
    }
    thead{ display: table-header-group; }
    tfoot{ display: table-footer-group; }
  }
</style>
@endsection

@section('content')
<div class="px-6 py-6 text-gray-900" id="salesOverviewRoot" aria-label="Sales Overview">
  {{-- HEADER --}}
  <div class="mb-4">
    <h1 class="text-2xl font-bold">Sales Overview</h1>
    <p class="text-sm text-gray-500">Trends and product breakdown at a glance.</p>
  </div>

  {{-- INLINE ALERTS (top of page) --}}
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

  {{-- CHARTS --}}
  <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-6">
    <div class="light-card p-5 xl:col-span-2">
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-xl font-semibold">Sales Report (12 months)</h2>
        <div class="text-sm text-gray-500">{{ now('Asia/Manila')->format('M d, Y') }}</div>
      </div>
      <div id="salesBar3D" class="w-full" style="height: 340px;" role="img" aria-label="12-month revenue bar chart"></div>
      <noscript class="text-sm text-amber-700">Charts require JavaScript enabled.</noscript>
    </div>

    <div class="light-card p-5">
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-xl font-semibold">Top Products (Revenue by Type)</h2>
        <div class="text-sm text-gray-500">Last 90 days</div>
      </div>
      <div id="topProductsDonut" class="w-full" style="height: 340px;" role="img" aria-label="Top product types donut chart"></div>
      <ul id="topProductsLegend" class="mt-3 space-y-1 text-sm text-gray-700" aria-label="Top product types legend"></ul>
    </div>
  </div>

  {{-- SALES TABLE --}}
  <div class="light-card">
    {{-- Bulk action bar (appears when selection > 0) --}}
    <div id="bulkBar" class="px-5 py-3 bg-blue-50 border-b border-blue-200 rounded-t-[14px]">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="text-sm font-medium text-blue-900">
          <span id="bulkCount">0</span> selected
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <button type="button" class="btn btn-secondary-blue" id="bulkOpenReceipts">Open Receipts</button>
          <button type="button" class="btn btn-secondary" id="bulkExportPdf">Export Selected PDF</button>
          <button
            type="button"
            class="btn btn-ghost text-rose-700 border border-rose-200 hover:bg-rose-50"
            id="bulkArchive"
          >
            Archive Selected
          </button>
        </div>
      </div>
    </div>

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
        <input id="dateFilter" type="date" class="input-light" aria-label="Filter by date">
        <button type="button" onclick="toggleAddSaleModal(true)" class="btn btn-primary">+ Add New Sale</button>
        <button type="button" id="exportPdfBtn" class="btn btn-secondary" aria-live="polite">⤓ Export PDF</button>
      </div>
    </div>

    <div class="px-5 pb-5">
      <div class="table-wrap">
        <table class="min-w-full border-collapse">
          <thead>
            <tr class="text-sm">
              <th class="select-col px-3 py-3 text-center">
                <input type="checkbox" id="selectAll" aria-label="Select all visible rows" class="row-select">
              </th>
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
                $pname   = $row->display_product ?? ($row->product ?? optional($row->productRef)->product_name);
                $date    = $row->order_date ?? $row->date ?? $row->created_at ?? null;
                $qty     = (float) ($row->quantity_kg ?? $row->quantity ?? 0);
                $unit    = (float) ($row->unit_price ?? $row->price ?? 0);
                $tot     = (float) ($row->total_price ?? $row->total ?? ($qty * $unit));
                $invoice = $row->invoice_number ?? $row->order_number ?? ('INV-'.str_pad((string)$row->id,3,'0',STR_PAD_LEFT));

                $uTypeRaw = $row->unit_type ?? $row->unit ?? null;
                $uType    = in_array($uTypeRaw, ['kg','pack','bag'], true) ? $uTypeRaw : 'kg';

                $typeLabel = trim((string)($row->type_label ?? ''));
                if ($typeLabel === '' && isset($row->production) && $row->production?->product_name_snapshot) {
                  $typeLabel = $row->production->product_name_snapshot;
                }
                if ($typeLabel === '') {
                  $typeLabel = '—';
                }

                // Batch prices from eager-loaded production (used for pack/bag only – no kg forecast).
                $packPrice = optional($row->production)->unit_price_pack;
                $bagPrice  = optional($row->production)->unit_price_bag;
                $hasPack = is_numeric($packPrice);
                $hasBag  = is_numeric($bagPrice);
              @endphp
              <tr class="hover:bg-gray-50 transition-colors" data-row-id="{{ $row->id }}">
                <td class="select-col px-3 py-3 text-center">
                  <input
                    type="checkbox"
                    class="row-select"
                    aria-label="Select row {{ $invoice }}"
                    data-row-id="{{ $row->id }}"
                  >
                </td>
                <td class="px-4 py-3 whitespace-nowrap">{{ $invoice }}</td>

                {{-- Product + per-pack/bag chips --}}
                <td class="px-4 py-3">
                  <div class="font-medium">{{ $pname }}</div>
                  @if($hasPack || $hasBag)
                    <div class="price-row">
                      @if($hasPack)
                        <span class="price-chip">Pack: ₱ {{ number_format((float)$packPrice, 2) }}</span>
                      @endif
                      @if($hasBag)
                        <span class="price-chip">Bag: ₱ {{ number_format((float)$bagPrice, 2) }}</span>
                      @endif
                    </div>
                  @else
                    <div class="text-xs text-gray-400">No batch prices</div>
                  @endif
                </td>

                <td class="px-4 py-3">
                  <span class="chip border bg-slate-50 text-slate-700">{{ $typeLabel }}</span>
                </td>

                <td class="px-4 py-3">
                  {{ $date ? \Carbon\Carbon::parse($date)->timezone('Asia/Manila')->format('Y-m-d') : '' }}
                </td>

                {{-- Quantity with unit chip --}}
                <td class="px-4 py-3 text-right">
                  {{ number_format($qty, $uType === 'kg' ? 3 : 0) }}
                  <span class="u-chip">{{ $uType }}</span>
                </td>

                {{-- Unit price (hint to pack/bag if available) --}}
                <td class="px-4 py-3 text-right">
                  ₱ {{ number_format($unit, 2) }}
                  @if($uType === 'kg' && ($hasPack || $hasBag))
                    <div class="mt-1 text-xs text-gray-500">
                      @if($hasPack)
                        <span>pack ₱ {{ number_format((float)$packPrice, 2) }}</span>
                      @endif
                      @if($hasPack && $hasBag)
                        <span class="mx-1">·</span>
                      @endif
                      @if($hasBag)
                        <span>bag ₱ {{ number_format((float)$bagPrice, 2) }}</span>
                      @endif
                    </div>
                  @endif
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
                  <span class="chip {{ $cls }} border">{{ $row->status ?? 'Pending' }}</span>
                </td>

                <td class="px-4 py-3">
                  <div class="flex items-center justify-center gap-2">
                    <a href="{{ route('sales.receipt', $row) }}" class="btn btn-secondary-blue receipt-link">Receipt</a>
                    <a href="{{ route('sales.edit', $row) }}" class="btn btn-secondary-green">Edit</a>
                    <form
                      action="{{ route('sales.destroy', $row) }}"
                      method="POST"
                      onsubmit="return confirm('Archive this sale?')"
                      class="inline archive-form"
                    >
                      @csrf
                      @method('DELETE')
                      <button class="btn btn-ghost text-rose-700 border border-rose-200 hover:bg-rose-50">
                        Archive
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="10" class="px-4 py-6 text-center text-gray-600">No sales yet.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="flex items-center justify-between text-sm text-gray-600 mt-4">
        <div>
          <span class="mr-2">Next invoice:</span>{{ $nextInvoice ?? '' }}
        </div>
        <div></div>
      </div>
    </div>
  </div>
</div>

{{-- Center flash popup (success / error) --}}
@if (session('success') || session('error'))
  <div
    id="flashModal"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/30"
    role="dialog"
    aria-modal="true"
    aria-live="polite"
  >
    <div class="max-w-sm w-full mx-4 rounded-2xl shadow-2xl bg-white px-6 py-5 text-center">
      @if (session('success'))
        <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-green-100 text-green-600">
          ✓
        </div>
        <h3 class="text-base font-semibold text-gray-900 mb-1">Success</h3>
        <p class="text-sm text-gray-700">{{ session('success') }}</p>
      @else
        <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-rose-100 text-rose-600">
          !
        </div>
        <h3 class="text-base font-semibold text-gray-900 mb-1">Something went wrong</h3>
        <p class="text-sm text-gray-700">{{ session('error') }}</p>
      @endif

      <button
        type="button"
        data-close-flash="1"
        class="mt-4 inline-flex items-center justify-center rounded-full bg-gray-900 text-white text-xs px-4 py-1.5"
      >
        Close
      </button>
    </div>
  </div>
@endif

{{-- Add Sale Modal --}}
@include('sales.partials.add-sale-modal', [
  'products' => $products ?? null,
  'statusOptions' => $statusOptions ?? ['Pending','Completed','Cancelled','Paid'],
  'nextInvoice' => $nextInvoice ?? null
])
@endsection

@push('scripts')
{{-- PDF libs --}}
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  /* ---------- Filters ---------- */
  const search = document.getElementById('salesSearch');
  const dateFilter = document.getElementById('dateFilter');
  const rows = Array.from(document.querySelectorAll('#salesTableBody tr'));

  function applyFilters() {
    const term = (search?.value || '').toLowerCase();
    const date = (dateFilter?.value || '');

    rows.forEach(tr => {
      const tds = tr.querySelectorAll('td');
      if (!tds.length) return;

      const invoice = (tds[1].textContent || '').toLowerCase();
      const product = (tds[2].textContent || '').toLowerCase();
      const typeCol = (tds[3].textContent || '').toLowerCase();
      const rowDate = (tds[4].textContent || '').trim();
      const status  = (tds[8].textContent || '').toLowerCase();

      const matchTerm = !term || invoice.includes(term) || product.includes(term) || typeCol.includes(term) || status.includes(term);
      const matchDate = !date || rowDate === date;

      tr.style.display = (matchTerm && matchDate) ? '' : 'none';

      // If hidden, uncheck from selection
      if (tr.style.display === 'none') {
        const cb = tr.querySelector('.row-select');
        if (cb && cb.checked) {
          cb.checked = false;
          selected.delete(tr.dataset.rowId);
          updateBulkUI();
        }
      }
    });
  }

  search?.addEventListener('input', applyFilters);
  dateFilter?.addEventListener('change', applyFilters);

  window.toggleAddSaleModal = (open) => {
    const el = document.getElementById('addSaleModal');
    if (!el) return;

    el.classList.toggle('hidden', !open);
    el.classList.toggle('flex', open);

    if (open) {
      // ensure modal scroll starts at the top
      el.scrollTop = 0;

      const form = el.querySelector('form');
      if (form) {
        // keep old input when coming from validation error
        if (!form.dataset.hasOld) {
          form.reset();
        }
        const btn = form.querySelector('button[type="submit"]');
        if (btn) {
          btn.disabled = false;
          btn.classList.remove('opacity-70', 'cursor-not-allowed');
        }
      }
    }
  };

  /* ---------- Charts ---------- */
  function cssVar(name, fallback) {
    const v = getComputedStyle(document.body).getPropertyValue(name).trim();
    return v || fallback;
  }

  const C_RED    = cssVar('--chart-1', '#ef4444');
  const C_GREEN  = cssVar('--chart-2', '#22c55e');
  const C_YELLOW = cssVar('--chart-3', '#f59e0b');
  const C_BLUE   = cssVar('--chart-4', '#3b82f6');

  const ApexOK = typeof ApexCharts !== 'undefined';
  if (!ApexOK) {
    console.warn('ApexCharts not loaded. Charts will not render.');
  } else {
    // BAR (12 months)
    try {
      const barEl = document.querySelector('#salesBar3D');
      const months = @json(array_values($chartMonths));
      const totals = @json(array_map('floatval', $chartTotals));

      const barOpts = {
        chart: {
          type: 'bar',
          height: 340,
          toolbar: { show: false },
          foreColor: '#374151',
          background: 'transparent',
          animations: { enabled: true, easing: 'easeinout', speed: 600 }
        },
        grid: {
          borderColor: '#e5e7eb',
          strokeDashArray: 4,
          padding: { left: 10, right: 10 }
        },
        plotOptions: {
          bar: {
            columnWidth: '45%',
            borderRadius: 8,
            borderRadiusApplication: 'around'
          }
        },
        colors: [C_BLUE],
        dataLabels: { enabled: false },
        series: [
          { name: 'Revenue', data: totals }
        ],
        xaxis: {
          categories: months,
          labels: { rotate: -15, style: { colors: '#374151' } },
          axisBorder: { color: '#e5e7eb' },
          axisTicks: { color: '#e5e7eb' }
        },
        yaxis: {
          labels: {
            formatter: (v) => '₱ ' + Number(v).toLocaleString(),
            style: { colors: '#374151' }
          }
        },
        tooltip: {
          theme: 'light',
          y: {
            formatter: (v) => '₱ ' + Number(v).toLocaleString()
          }
        },
        fill: {
          type: 'gradient',
          gradient: {
            shade: 'light',
            type: 'vertical',
            gradientToColors: [C_GREEN],
            opacityFrom: .95,
            opacityTo: .9,
            stops: [0, 60, 100]
          }
        }
      };

      if (barEl) {
        const barChart = new ApexCharts(barEl, barOpts);
        barChart.render();
      }
    } catch (e) {
      console.error('Bar chart error:', e);
    }

    // DONUT (Top Products by Type – actual revenue, no forecast)
    try {
      const donutEl = document.querySelector('#topProductsDonut');
      const labels = @json($donutLabels);
      const values = @json($donutValues);
      const colors = [C_RED, C_GREEN, C_YELLOW, C_BLUE, '#60a5fa', '#34d399'];

      const donutOpts = {
        chart: {
          type: 'donut',
          height: 340,
          foreColor: '#374151',
          background: 'transparent'
        },
        series: values,
        labels: labels,
        legend: { show: false },
        colors: colors,
        tooltip: {
          theme: 'light',
          y: {
            formatter: (v) => '₱ ' + Number(v).toLocaleString()
          },
          x: {
            formatter: (name) => name
          }
        },
        dataLabels: {
          enabled: true,
          formatter: (val, opts) => {
            const full = opts.w.globals.labels[opts.seriesIndex] || '';
            const parts = full.split('·');
            const type = (parts[1] || full).trim();
            return type;
          }
        },
        plotOptions: {
          pie: {
            donut: {
              size: '68%',
              labels: {
                show: true,
                total: {
                  show: true,
                  label: 'Total',
                  formatter: (w) => {
                    const sum = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                    return '₱ ' + Number(sum).toLocaleString();
                  }
                }
              }
            }
          }
        },
        stroke: { colors: ['#ffffff'] }
      };

      if (donutEl) {
        const donut = new ApexCharts(donutEl, donutOpts);
        donut.render().then(() => {
          const ul = document.getElementById('topProductsLegend');
          if (ul) {
            ul.innerHTML = labels.map((label, i) => {
              const value = Number(values[i] ?? 0);
              return `
                <li class="flex justify-between">
                  <span>${label}</span>
                  <span>₱ ${value.toLocaleString()}</span>
                </li>`;
            }).join('');
          }
        });
      }
    } catch (e) {
      console.error('Donut chart error:', e);
    }
  }

  /* ---------- PDF EXPORT (Full Page) ---------- */
  const pdfBtn = document.getElementById('exportPdfBtn');
  const ROOT = document.getElementById('salesOverviewRoot');

  function ensurePdfLibs() {
    if (typeof html2canvas === 'undefined' || !window.jspdf) {
      alert('PDF libraries failed to load. Please check your internet connection and try again.');
      return false;
    }
    return true;
  }

  if (pdfBtn && ROOT) {
    pdfBtn.addEventListener('click', async () => {
      if (!ensurePdfLibs()) return;

      try {
        const original = pdfBtn.innerHTML;
        pdfBtn.disabled = true;
        pdfBtn.classList.add('opacity-70', 'cursor-not-allowed');
        pdfBtn.innerHTML = 'Generating…';
        ROOT.classList.add('exporting');

        await new Promise(resolve => setTimeout(resolve, 350));

        const canvas = await html2canvas(ROOT, {
          backgroundColor: '#ffffff',
          scale: Math.min(Math.max(window.devicePixelRatio || 1, 2), 3),
          useCORS: true,
          windowWidth: document.documentElement.scrollWidth,
          windowHeight: document.documentElement.scrollHeight
        });

        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF({ orientation: 'p', unit: 'pt', format: 'a4' });
        const pageWidth  = pdf.internal.pageSize.getWidth();
        const pageHeight = pdf.internal.pageSize.getHeight();

        const imgWidth = pageWidth;
        const sliceHeightPx = (pageHeight * canvas.width) / pageWidth;

        let y = 0;
        let page = 0;

        while (y < canvas.height) {
          const part = document.createElement('canvas');
          part.width  = canvas.width;
          part.height = Math.min(sliceHeightPx, canvas.height - y);
          const ctx = part.getContext('2d');
          ctx.drawImage(canvas, 0, y, part.width, part.height, 0, 0, part.width, part.height);

          const partData = part.toDataURL('image/jpeg', 0.95);
          const partHeightOnPdf = (part.height * imgWidth) / part.width;

          if (page > 0) {
            pdf.addPage();
          }
          pdf.addImage(partData, 'JPEG', 0, 0, imgWidth, partHeightOnPdf, undefined, 'FAST');

          y += sliceHeightPx;
          page++;
        }

        const d = new Date();
        const name = `Sales_Overview_${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}.pdf`;
        pdf.save(name);

        pdfBtn.innerHTML = original;
        pdfBtn.disabled = false;
        pdfBtn.classList.remove('opacity-70', 'cursor-not-allowed');
        ROOT.classList.remove('exporting');
      } catch (err) {
        console.error('PDF export failed:', err);
        alert('Export failed. Please try again.');
        pdfBtn.disabled = false;
        pdfBtn.classList.remove('opacity-70', 'cursor-not-allowed');
        pdfBtn.innerHTML = '⤓ Export PDF';
        ROOT.classList.remove('exporting');
      }
    });
  }

  /* ---------- MULTI-SELECT ---------- */
  const selectAll = document.getElementById('selectAll');
  const rowCbs = () => Array.from(document.querySelectorAll('#salesTableBody .row-select'));
  const selected = new Set();
  const bulkBar = document.getElementById('bulkBar');
  const bulkCount = document.getElementById('bulkCount');
  const bulkOpenReceipts = document.getElementById('bulkOpenReceipts');
  const bulkArchive = document.getElementById('bulkArchive');
  const bulkExportPdf = document.getElementById('bulkExportPdf');

  function updateBulkUI() {
    const count = selected.size;
    bulkCount.textContent = count;
    bulkBar.classList.toggle('active', count > 0);

    const visibles = rowCbs().filter(cb => cb.closest('tr').style.display !== 'none');
    const allVisibleChecked = visibles.length > 0 && visibles.every(cb => cb.checked);

    if (selectAll) {
      selectAll.checked = allVisibleChecked;
      selectAll.indeterminate = count > 0 && !allVisibleChecked;
    }
  }

  // Handle Select All (visible only)
  selectAll?.addEventListener('change', () => {
    const visibleRows = Array.from(document.querySelectorAll('#salesTableBody tr'))
      .filter(tr => tr.style.display !== 'none');

    visibleRows.forEach(tr => {
      const cb = tr.querySelector('.row-select');
      if (!cb) return;
      cb.checked = selectAll.checked;
      const id = tr.dataset.rowId;
      if (!id) return;
      if (cb.checked) {
        selected.add(id);
      } else {
        selected.delete(id);
      }
    });
    updateBulkUI();
  });

  // Handle each row checkbox
  document.getElementById('salesTableBody')?.addEventListener('change', (e) => {
    if (!e.target.matches('.row-select')) return;
    const tr = e.target.closest('tr');
    const id = tr?.dataset.rowId;
    if (!id) return;

    if (e.target.checked) {
      selected.add(id);
    } else {
      selected.delete(id);
    }
    updateBulkUI();
  });

  // Bulk: Open Receipts
  bulkOpenReceipts?.addEventListener('click', () => {
    if (selected.size === 0) return;
    const ids = Array.from(selected);
    ids.forEach((id, i) => {
      const tr = document.querySelector(`tr[data-row-id="${id}"]`);
      const a = tr?.querySelector('.receipt-link');
      if (a && a.href) {
        setTimeout(() => window.open(a.href, '_blank'), i * 120);
      }
    });
  });

  // Bulk: Archive (reuses each row's archive form)
  bulkArchive?.addEventListener('click', async () => {
    if (selected.size === 0) return;
    if (!confirm(`Archive ${selected.size} selected sale(s)?`)) return;

    const ids = Array.from(selected);
    for (let i = 0; i < ids.length; i++) {
      const id = ids[i];
      const tr = document.querySelector(`tr[data-row-id="${id}"]`);
      const form = tr?.querySelector('form.archive-form');
      if (form) form.submit();
      await new Promise(resolve => setTimeout(resolve, 150));
    }
  });

  // Bulk: Export Selected PDF (table-only, clean)
  bulkExportPdf?.addEventListener('click', async () => {
    if (selected.size === 0) return;
    if (!ensurePdfLibs()) return;

    try {
      const ids = Array.from(selected);

      // Build a minimal table clone with selected rows
      const table = document.createElement('table');
      table.style.width = '100%';
      table.style.borderCollapse = 'collapse';
      table.innerHTML = `
        <thead>
          <tr>
            <th style="text-align:left;padding:8px;border:1px solid #e5e7eb;">Invoice</th>
            <th style="text-align:left;padding:8px;border:1px solid #e5e7eb;">Product</th>
            <th style="text-align:left;padding:8px;border:1px solid #e5e7eb;">Type</th>
            <th style="text-align:left;padding:8px;border:1px solid #e5e7eb;">Date</th>
            <th style="text-align:right;padding:8px;border:1px solid #e5e7eb;">Qty</th>
            <th style="text-align:right;padding:8px;border:1px solid #e5e7eb;">Unit</th>
            <th style="text-align:right;padding:8px;border:1px solid #e5e7eb;">Total</th>
            <th style="text-align:left;padding:8px;border:1px solid #e5e7eb;">Status</th>
          </tr>
        </thead>
        <tbody></tbody>
      `;

      const tbody = table.querySelector('tbody');

      const safeText = (el) => (el?.textContent || '').trim();

      ids.forEach(id => {
        const tr = document.querySelector(`tr[data-row-id="${id}"]`);
        if (!tr) return;

        const tds = tr.querySelectorAll('td');
        const row = document.createElement('tr');

        row.innerHTML = `
          <td style="padding:8px;border:1px solid #e5e7eb;">${safeText(tds[1])}</td>
          <td style="padding:8px;border:1px solid #e5e7eb;">${safeText(tds[2])}</td>
          <td style="padding:8px;border:1px solid #e5e7eb;">${safeText(tds[3])}</td>
          <td style="padding:8px;border:1px solid #e5e7eb;">${safeText(tds[4])}</td>
          <td style="padding:8px;text-align:right;border:1px solid #e5e7eb;">${safeText(tds[5])}</td>
          <td style="padding:8px;text-align:right;border:1px solid #e5e7eb;">${safeText(tds[6])}</td>
          <td style="padding:8px;text-align:right;border:1px solid #e5e7eb;">${safeText(tds[7])}</td>
          <td style="padding:8px;border:1px solid #e5e7eb;">${safeText(tds[8])}</td>
        `;

        tbody.appendChild(row);
      });

      const wrap = document.createElement('div');
      wrap.style.padding = '20px';

      const title = document.createElement('h2');
      title.textContent = 'Selected Sales';
      title.style.margin = '0 0 12px 0';
      title.style.font = '600 16px/1.3 system-ui, -apple-system, Segoe UI, Roboto, sans-serif';

      wrap.appendChild(title);
      wrap.appendChild(table);
      document.body.appendChild(wrap);

      const canvas = await html2canvas(wrap, {
        backgroundColor: '#ffffff',
        scale: Math.min(Math.max(window.devicePixelRatio || 1, 2), 3)
      });

      const { jsPDF } = window.jspdf;
      const pdf = new jsPDF({ orientation: 'p', unit: 'pt', format: 'a4' });
      const pageWidth  = pdf.internal.pageSize.getWidth();
      const pageHeight = pdf.internal.pageSize.getHeight();

      const imgWidth = pageWidth - 40; // margins
      const imgHeight = (canvas.height * imgWidth) / canvas.width;

      if (imgHeight + 40 <= pageHeight) {
        pdf.addImage(canvas.toDataURL('image/jpeg', 0.95), 'JPEG', 20, 20, imgWidth, imgHeight, undefined, 'FAST');
      } else {
        // slice into pages
        const usableHeight = pageHeight - 40;
        const sliceHeightPx = (usableHeight * canvas.width) / imgWidth;
        let top = 0;
        let page = 0;

        while (top < canvas.height) {
          const part = document.createElement('canvas');
          part.width = canvas.width;
          part.height = Math.min(sliceHeightPx, canvas.height - top);
          const ctx = part.getContext('2d');
          ctx.drawImage(canvas, 0, top, part.width, part.height, 0, 0, part.width, part.height);

          const partData = part.toDataURL('image/jpeg', 0.95);
          if (page > 0) {
            pdf.addPage();
          }
          pdf.addImage(
            partData,
            'JPEG',
            20,
            20,
            imgWidth,
            (part.height * imgWidth) / part.width,
            undefined,
            'FAST'
          );

          top += sliceHeightPx;
          page++;
        }
      }

      const d = new Date();
      pdf.save(`Sales_Selected_${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}.pdf`);

      document.body.removeChild(wrap);
    } catch (e) {
      console.error(e);
      alert('Export failed. Please try again.');
    }
  });
});

/* ---------- FLASH POPUP AUTO-CLOSE ---------- */
const flashModal = document.getElementById('flashModal');
if (flashModal) {
  const hideFlash = () => {
    flashModal.classList.add('hidden-soft');
    setTimeout(() => {
      flashModal.style.display = 'none';
    }, 260);
  };

  setTimeout(hideFlash, 2600);

  flashModal.addEventListener('click', (e) => {
    if (e.target.id === 'flashModal' || e.target.closest('[data-close-flash]')) {
      hideFlash();
    }
  });
}

/* ---------- AUTO-OPEN ADD-SALE MODAL ON VALIDATION ERROR ---------- */
@if ($errors->any())
window.addEventListener('load', function () {
  if (window.toggleAddSaleModal) {
    const modal = document.getElementById('addSaleModal');
    const form = modal ? modal.querySelector('form') : null;
    if (form) {
      form.dataset.hasOld = '1';
    }
    window.toggleAddSaleModal(true);
  }
});
@endif
</script>
@endpush
