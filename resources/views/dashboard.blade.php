<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="color-scheme" content="light" />
  <meta name="theme-color" content="#E11D48" />
  <title>GenRev Meat Production Dashboard · Neon</title>

  <!-- Tailwind CDN (pinned major) -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Kalam:wght@400;700&family=Inria+Sans:wght@300;400;700&display=swap" rel="stylesheet">

  <style>
    :root{
      /* Brand core (inspired by the GenRev logo) */
      --brand-yellow:#FFE71A;   /* neon yellow */
      --brand-yellow-20:rgba(255,231,26,.20);
      --brand-yellow-40:rgba(255,231,26,.40);
      --brand-yellow-06:rgba(255,231,26,.06);
      --brand-red:#B1121A;      /* deep red */
      --brand-red-80:rgba(177,18,26,.8);
      --brand-red-30:rgba(177,18,26,.30);
      --brand-ivory:#fffdf3;    /* soft paper for cards */

      /* UI surface + neutrals */
      --page:#f7f8fb; --nav:#ffffff; --card:var(--brand-ivory); --line:#e5e7eb; --shadow:0 8px 24px rgba(17,24,39,.08);
      --ink:#0f172a; --muted:#6b7280; --hover:#f3f4f6; --chip:#f9fafb;

      /* Secondary accents for charts */
      --green:#10b981; --blue:#2563eb; --yellow:#f59e0b;

      /* Neon helpers */
      --neon-cyan:#67E8F9; --neon-blue:#3B82F6; --neon-amber:#FBBF24;
    }
    html,body{ height:100%; }
    body{
      background:var(--page);
      color:var(--ink);
      font-family:'Inria Sans',system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
      min-height:100vh;
      overflow-x:hidden;
    }

    .nav-surface{ background:var(--nav); border-bottom:1px solid var(--line); box-shadow:var(--shadow); }
    .sidebar{ background:#ffffff; border-right:1px solid var(--line); }
    .card{ background:var(--card); border:1px solid var(--line); border-radius:16px; box-shadow:var(--shadow); }

    /* Glassmorphism for Predictive Analytics card */
    .glass-card{
      position: relative;
      background: radial-gradient(circle at top left, rgba(255,255,255,.22), rgba(255,255,255,.04));
      border-radius: 18px;
      border: 1px solid rgba(255,255,255,.3);
      box-shadow:
        0 18px 45px rgba(15,23,42,.18),
        0 0 0 1px rgba(148,163,184,.18);
      backdrop-filter: blur(18px);
      -webkit-backdrop-filter: blur(18px);
      overflow: hidden;
    }
    .glass-card::before{
      content:''; position:absolute; inset:-40%;
      background:
        radial-gradient(circle at 0% 0%, rgba(255,231,26,.22), transparent 55%),
        radial-gradient(circle at 100% 0%, rgba(59,130,246,.22), transparent 55%);
      opacity:.85; mix-blend-mode:screen; pointer-events:none;
    }
    .glass-card > *{ position: relative; z-index: 1; }
    .glass-chart-wrap{ position:relative; height:14rem; }
    @media (min-width:1280px){
      .glass-chart-wrap{height:20rem;}
    }

    .side-link{ display:block; padding:.75rem 1.25rem; border-radius:999px 0 0 999px; transition:.16s; color:var(--ink); }
    .side-link:hover{ background:var(--hover); }
    .side-link--active{
      background:linear-gradient(90deg, var(--brand-yellow-20) 0%, rgba(255,231,26,.12) 100%);
      border-left:3px solid var(--brand-red); font-weight:700;
    }

    .btn{
      display:inline-flex; align-items:center; justify-content:center;
      gap:.5rem; padding:.65rem 1rem; border-radius:12px;
      border:1px solid transparent; font-weight:700;
    }
    .btn-primary{ background:var(--brand-red); color:#fff; border-color:var(--brand-red); }
    .btn-primary:hover{ filter:brightness(.97); }
    .btn-ghost{ background:#fff; border:1px solid var(--line); color:var(--ink); }
    .btn-ghost:hover{ background:var(--hover); }
    .btn-green{ background:var(--green); color:#fff; border-color:var(--green); }
    .btn-blue{ background:var(--blue); color:#fff; border-color:var(--blue); }

    .input{
      width:100%; padding:.65rem .9rem; border-radius:12px;
      background:#fff; border:1px solid var(--line); color:var(--ink);
      transition:border-color .15s, box-shadow .15s, transform .12s;
    }
    .input::placeholder{ color:#9ca3af; }
    .input:hover{ border-color:#e2e8f0; }
    .input:focus{
      outline:0; border-color:#93c5fd;
      box-shadow:0 0 0 2px rgba(37,99,235,.18);
      transform:translateY(-1px);
    }

    .chip{
      display:inline-flex; align-items:center; gap:.4rem;
      padding:.32rem .6rem; border-radius:999px;
      font-size:.72rem; font-weight:700;
      background:var(--chip); border:1px solid var(--line); color:var(--ink);
    }

    table{ border-collapse:separate; border-spacing:0; width:100%; }
    thead th{ background:#f9fafb; color:#374151; font-weight:800; border-bottom:1px solid var(--line); }
    tbody td{ color:var(--ink); }
    tbody tr:nth-child(even){ background:#fafafa; }
    tbody tr:hover{ background:var(--hover); }
    th, td{ border-color:var(--line)!important; }

    .brand-title{ font-family:'Kalam',cursive; letter-spacing:.02em; color:var(--ink); }
    .muted{ color:var(--muted); }

    @media (max-width:1024px){
      #sidebar{ transform:translateX(-100%); transition:transform .3s ease; }
      #sidebar.open{ transform:translateX(0); }
    }
    :where(a,button,[role="menuitem"],.side-link,.btn):focus{
      outline:2px solid var(--brand-yellow); outline-offset:2px;
    }

    /* Neon status dot */
    #dot3d{
      width:8px; height:8px; border-radius:999px;
      background:var(--brand-red);
      box-shadow:0 0 0 2px rgba(177,18,26,.18), 0 0 12px var(--brand-red-80);
    }

    /* Reduced motion */
    @media (prefers-reduced-motion: reduce) {
      * { animation-duration: 0.01ms !important; animation-iteration-count: 1 !important; transition-duration: 0.01ms !important; scroll-behavior: auto !important; }
      .card { box-shadow: none; }
    }

    /* Ensure Chart.js gets real pixels to draw into */
    .card .chart-wrap{position:relative; height:14rem;}
    .card .chart-wrap > canvas{display:block !important; width:100% !important; height:100% !important;}
    @media (min-width:1280px){
      .card .chart-wrap{height:22rem;}
    }

    .sr-only{
      position:absolute; width:1px; height:1px; padding:0; margin:-1px;
      overflow:hidden; clip:rect(0,0,1px,1px); white-space:nowrap; border:0;
    }
  </style>
</head>
<body>
  <div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar w-64 flex-shrink-0 flex flex-col" aria-label="Primary" tabindex="-1">
      <div class="p-6 border-b border-[var(--line)] flex justify-between items-center">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3" aria-label="GenRev Home">
          <div class="h-10 w-10 rounded-2xl bg-white border border-gray-200 shadow-sm overflow-hidden flex items-center justify-center">
            <img src="{{ asset('images/GENREV_FINAL.png') }}" alt="GenRev" loading="lazy" decoding="async" class="h-full w-full object-contain" onerror="this.closest('div').innerHTML='<span class=&quot;sr-only&quot;>GenRev</span>'; ">
          </div>
          <span class="text-2xl font-bold tracking-wide brand-title">GenRev Meat Production</span>
        </a>
        <button id="sidebarClose" class="lg:hidden text-xl font-bold" aria-label="Close sidebar" aria-controls="sidebar">&times;</button>
      </div>

      <!-- User -->
      <div class="px-6 pt-4 pb-2">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-white" style="background:var(--brand-red);" aria-hidden="true">
            {{ Auth::check() ? strtoupper(substr(Auth::user()->name, 0, 1)) : '?' }}
          </div>
          <div class="text-sm min-w-0">
            <p class="font-semibold truncate">{{ Auth::check() ? Auth::user()->name : 'Guest' }}</p>
            <p class="text-xs muted truncate">
              {{ Auth::check() && method_exists(Auth::user(), 'getRoleLabelAttribute')
  ? (Auth::user()->role_label ?? 'User')
  : (\Illuminate\Support\Str::headline(\Illuminate\Support\Str::lower((string) (Auth::user()->role ?? 'user')))) }}
            </p>
          </div>
        </div>
      </div>

      <!-- Nav (role aware) -->
      <nav class="flex-1 mt-4 space-y-1 text-sm font-medium" role="navigation" aria-label="Primary navigation">
        @php
$modules = [];
if (Auth::check() && method_exists(Auth::user(), 'allowedModules')) {
  $modules = (array) (Auth::user()->allowedModules() ?? []);
}
if (empty($modules)) {
  $role = \Illuminate\Support\Str::lower((string) (Auth::user()->role ?? ''));
  $fallback = [
    'masters admin' => ['dashboard', 'materials', 'production', 'sales', 'inventory', 'products', 'reports', 'settings', 'employee'],
    'production manager' => ['dashboard', 'production', 'products', 'settings'],
    'sales' => ['dashboard', 'sales', 'settings'],
    'inventory' => ['dashboard', 'inventory', 'materials', 'settings'],
  ];
  $modules = $fallback[$role] ?? ['dashboard', 'settings'];
}
$menu = [
  'dashboard' => ['label' => 'Dashboard', 'route' => 'dashboard', 'active' => ['dashboard*']],
  'production' => ['label' => 'Production', 'route' => 'production.index', 'active' => ['production.*']],
  'sales' => ['label' => 'Sales', 'route' => 'sales', 'active' => ['sales*', 'sales.*']],
  'inventory' => ['label' => 'Inventory', 'route' => 'inventory', 'active' => ['inventory*', 'inventory.*']],
  'materials' => ['label' => 'Materials', 'route' => 'materials', 'active' => ['materials*', 'materials.*', 'products.materials.*']],
  'products' => ['label' => 'Products', 'route' => 'products.index', 'active' => ['products*', 'products.*']],
  'reports' => ['label' => 'Reports', 'route' => 'reports.index', 'active' => ['reports*', 'reports.*']],
  'employee' => ['label' => 'Employee', 'route' => 'employees.index', 'active' => ['employees*', 'employees.*']],
  'settings' => ['label' => 'Settings', 'route' => 'settings.index', 'active' => ['settings*', 'settings.*']],
];
$isActive = fn(array $patterns) => collect($patterns)->some(fn($p) => request()->routeIs($p));
        @endphp

        @foreach ($modules as $key)
          @php
  if (!isset($menu[$key]))
    continue;
  $item = $menu[$key];
  if (!\Illuminate\Support\Facades\Route::has($item['route']))
    continue;
  $active = $isActive($item['active']) ? 'side-link--active' : '';
  $aria = $active ? 'aria-current=page' : '';
          @endphp
          <a href="{{ route($item['route']) }}" class="side-link {{ $active }}" {!! $aria !!}>{{ $item['label'] }}</a>

          @if($key === 'dashboard')
            <div class="mx-6 my-2 border-t" style="border-color:var(--line)"></div>
          @endif
        @endforeach
      </nav>

      <div class="p-6 text-xs muted border-t border-[var(--line)]">© {{ now()->year }} GenRev Meat Production</div>
    </aside>

    <!-- Main -->
    <div class="flex flex-col flex-1 overflow-hidden">
      <!-- Top Nav -->
      <header class="nav-surface px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-4">
          <button id="sidebarToggle" class="lg:hidden text-2xl" aria-label="Open sidebar" aria-controls="sidebar" aria-expanded="false">&#9776;</button>
          <h1 class="text-xl font-bold tracking-wide brand-title">Dashboard Overview</h1>
        </div>
      </header>

      <!-- Content -->
      <main class="flex-1 overflow-y-auto p-8">
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

          <!-- Metrics Cards -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" aria-live="polite">
            @php
$metrics = [
  ['label' => 'Finished Products', 'value' => $totalProducts, 'note' => 'Meat items tracked in packs and bags', 'icon' => '🥩'],
  ['label' => 'Materials On Hand (kg)', 'value' => number_format($totalMaterialsWeight, 2), 'note' => 'Raw meat and ingredients', 'icon' => '⚖️'],
  ['label' => 'Total Revenue', 'value' => '₱' . number_format($totalRevenue, 2), 'note' => 'All recorded product sales', 'icon' => '💰'],
  ['label' => 'Sales Transactions', 'value' => $totalSales, 'note' => 'Total sales entries logged', 'icon' => '📈'],
];
            @endphp
            @foreach ($metrics as $metric)
              <div class="card p-5 rounded-2xl hover:shadow-lg transition">
                <div class="flex items-center gap-4">
                  <div class="w-10 h-10 rounded-full flex items-center justify-center text-xl text-white" style="background:var(--brand-red);" aria-hidden="true">{{ $metric['icon'] }}</div>
                  <div>
                    <p class="text-xs uppercase font-semibold tracking-wide muted">{{ $metric['label'] }}</p>
                    <h3 class="text-2xl font-bold">{{ $metric['value'] }}</h3>
                    <p class="text-xs muted">{{ $metric['note'] }}</p>
                  </div>
                </div>
              </div>
            @endforeach
          </div>

         <!-- Sales Report (Neon Ridge Sparkline) -->
<div class="card p-5 rounded-2xl" 
     id="salesReportCard"
     data-sales-range-endpoint="{{ route('dashboard.sales.range') }}">
  <div class="flex items-center justify-between mb-4">
    <div>
      <h2 class="text-lg font-semibold mb-1">📈 Sales Report</h2>
      <p class="text-xs muted" id="salesRangeLabel">
        Meat product sales in finished units (packs and bags) • This Week
      </p>
    </div>
    <select id="salesRange" class="input w-40 py-1" aria-label="Sales range">
      <option value="today">Today</option>
      <option value="week" selected>This Week</option>
      <option value="month">This Month</option>
      <option value="7days">Last 7 Days</option>
      <option value="30days">Last 30 Days</option>
    </select>
  </div>

  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
    @php
      $avgPrice = $totalSales > 0 ? ($totalRevenue / max($totalSales, 1)) : 0;
      $biggest = $biggestSalesDay ?? null;
      $biggestLabel = $biggest ? $biggest : 'No data yet';

      $salesStats = [
        [
          'key'   => 'total_revenue',
          'label' => 'Total Revenue',
          'value' => '₱' . number_format($totalRevenue, 2),
          'icon'  => '💰',
          'color' => 'text-[var(--brand-red)]',
        ],
        [
          'key'   => 'total_sales',
          'label' => 'Sales Count',
          'value' => number_format($totalSales, 0),
          'icon'  => '📦',
          'color' => 'text-[var(--blue)]',
        ],
        [
          'key'   => 'avg_price',
          'label' => 'Average Price Unit',
          'value' => '₱' . number_format($avgPrice, 2),
          'icon'  => '📊',
          'color' => 'text-[var(--green)]',
        ],
        [
          'key'   => 'strongest_day',
          'label' => 'Strongest Sales Day',
          'value' => $biggestLabel,
          'icon'  => '🔥',
          'color' => 'text-[var(--yellow)]',
        ],
      ];
    @endphp

    @foreach($salesStats as $stat)
      <div class="text-center">
        <div class="text-2xl mb-1" aria-hidden="true">{{ $stat['icon'] }}</div>
        <div class="text-xs muted mb-1">{{ $stat['label'] }}</div>
        <div class="text-sm font-semibold {{ $stat['color'] }}"
             data-sales-stat="{{ $stat['key'] }}">
          {{ $stat['value'] }}
        </div>
      </div>
    @endforeach
  </div>

  <div class="h-36 relative">
    <p id="salesTrendsDesc" class="sr-only">
      Glassy neon ridge of revenue per day for finished meat products sold as packs and bags.
    </p>
    <canvas id="salesTrendsChart"
            aria-label="Sales trend chart"
            aria-describedby="salesTrendsDesc"></canvas>
  </div>
</div>

@push('scripts')
<script>
  (function () {
    const ctx = document.getElementById('salesTrendsChart').getContext('2d');
    const rangeSelect = document.getElementById('salesRange');
    const rangeLabel  = document.getElementById('salesRangeLabel');
    const card        = document.getElementById('salesReportCard');
    const endpoint    = card.dataset.salesRangeEndpoint;

    // Initial data from PHP (This Week)
    const initialLabels = @json($labels ?? []);
    const initialData   = @json($weeklySalesRevenueSeries ?? []);

    // Helpers to format numbers the same way as Blade
    function formatCurrency(value) {
      const num = Number(value) || 0;
      return '₱' + num.toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
    }

    function formatInteger(value) {
      const num = Number(value) || 0;
      return num.toLocaleString('en-PH', { maximumFractionDigits: 0 });
    }

    // Neon ridge line chart
    const salesChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: initialLabels,
        datasets: [{
          label: 'Revenue',
          data: initialData,
          tension: 0.4,
          fill: true,
          borderWidth: 2,
          // Neon ridge style (colors picked from CSS vars if you have them)
          borderColor: 'rgba(0, 255, 255, 0.9)',
          backgroundColor: 'rgba(0, 255, 255, 0.12)',
          pointRadius: 0,
          pointHitRadius: 6,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: {
            grid: { display: false },
            ticks: {
              maxTicksLimit: 7,
            }
          },
          y: {
            grid: { color: 'rgba(255,255,255,0.06)' },
            ticks: {
              callback: (v) => '₱' + v.toLocaleString('en-PH')
            }
          }
        },
        plugins: {
          legend: { display: false },
          tooltip: {
            mode: 'index',
            intersect: false,
            callbacks: {
              label: function (ctx) {
                const val = ctx.parsed.y || 0;
                return 'Revenue: ' + formatCurrency(val);
              }
            }
          }
        }
      }
    });

    async function loadSalesRange(range) {
      try {
        const url = new URL(endpoint, window.location.origin);
        url.searchParams.set('range', range);

        const res = await fetch(url.toString(), {
          headers: { 'Accept': 'application/json' }
        });

        if (!res.ok) {
          console.error('Failed to load sales range', res.status);
          return;
        }

        const data = await res.json();

        // 1. Update label under title
        if (data.label) {
          rangeLabel.textContent = `Meat product sales in finished units (packs and bags) • ${data.label}`;
        }

        // 2. Update stats
        if (data.stats) {
          const stats = data.stats;

          const totalRevenueEl = card.querySelector('[data-sales-stat="total_revenue"]');
          const totalSalesEl   = card.querySelector('[data-sales-stat="total_sales"]');
          const avgPriceEl     = card.querySelector('[data-sales-stat="avg_price"]');
          const strongestEl    = card.querySelector('[data-sales-stat="strongest_day"]');

          if (totalRevenueEl) totalRevenueEl.textContent = formatCurrency(stats.total_revenue || 0);
          if (totalSalesEl)   totalSalesEl.textContent   = formatInteger(stats.total_sales || 0);
          if (avgPriceEl)     avgPriceEl.textContent     = formatCurrency(stats.avg_price || 0);
          if (strongestEl)    strongestEl.textContent    = stats.biggest_day || 'No data yet';
        }

        // 3. Update chart
        if (data.chart) {
          salesChart.data.labels = data.chart.labels || [];
          salesChart.data.datasets[0].data = data.chart.data || [];
          salesChart.update();
        }

      } catch (err) {
        console.error('Error loading sales range', err);
      }
    }

    // Change handler
    rangeSelect.addEventListener('change', function () {
      loadSalesRange(this.value);
    });
  })();
</script>
@endpush

          <!-- Most Sold Products and Types -->
          <div class="card p-5 rounded-2xl">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h2 class="text-lg font-semibold mb-1">🏆 Most Sold Meat Products and Variants</h2>
                <p class="text-xs muted">Top five product and variant combinations by revenue in units (packs and bags)</p>
              </div>
              @if(\Illuminate\Support\Facades\Route::has('sales'))
                <a href="{{ route('sales') }}" class="btn btn-green text-xs">View all sales</a>
              @endif
            </div>

            @if(($topProducts ?? collect())->isEmpty())
              <div class="text-center py-8">
                <div class="text-4xl mb-2" aria-hidden="true">📊</div>
                <div class="text-sm muted">No sales data available yet</div>
                <div class="text-xs muted mt-1">Start recording finished meat product sales in packs and bags to see best sellers.</div>
              </div>
            @else
              <div class="space-y-3">
                @foreach($topProducts as $index => $product)
                  @php
    $unitRaw = strtolower((string) ($product->unit_type ?? 'pack'));
    if (!in_array($unitRaw, ['kg', 'pack', 'bag'], true)) {
      $unitRaw = 'pack';
    }
    $unitLabel = $unitRaw === 'bag'
      ? 'Bag'
      : ($unitRaw === 'kg' ? 'Kg' : 'Pack');
    $displayLabel = $product->display_label ?? ($product->product_name ?? 'Product');
                  @endphp
                  <div class="flex items-center gap-3 p-3 rounded-lg bg-[#fafafa] hover:bg-[#f3f4f6] transition">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold text-white" style="background:var(--brand-red);" aria-label="Rank {{ $index + 1 }}">{{ $index + 1 }}</div>
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center justify-between mb-1">
                        <div class="font-medium truncate">{{ $displayLabel }}</div>
                        <div class="text-sm font-semibold" style="color:var(--brand-red)">₱{{ number_format($product->revenue ?? 0, 2) }}</div>
                      </div>
                      <div class="mb-1 flex flex-wrap gap-1">
                        @if(!empty($product->sale_type))
                          <span class="chip">{{ $product->sale_type }}</span>
                        @endif
                        <span class="chip">{{ $unitLabel }}</span>
                      </div>
                      <div class="flex items-center justify-between text-xs muted">
                        <span>{{ number_format($product->quantity ?? 0, 2) }} unit(s) sold</span>
                        <span>{{ number_format($product->revenue_share ?? 0, 1) }}% of week revenue</span>
                      </div>
                      <div class="w-full bg-[var(--line)]/40 rounded-full h-1.5 mt-2" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ min(($product->revenue_share ?? 0), 100) }}">
                        <div class="h-1.5 rounded-full" style="width: {{ min(($product->revenue_share ?? 0), 100) }}%; background:linear-gradient(90deg,var(--brand-red),var(--brand-yellow));"></div>
                      </div>
                    </div>
                  </div>
                @endforeach
              </div>
            @endif
          </div>

          <!-- Recent Sales -->
          <div class="card p-5 rounded-2xl overflow-auto">
            <div class="flex items-center justify-between">
              <div>
                <h2 class="text-base font-semibold mb-1">Recent Meat Sales</h2>
                <p class="text-xs muted mb-3">Latest finished meat packs and bags sold</p>
              </div>
              @if(\Illuminate\Support\Facades\Route::has('sales'))
                <a href="{{ route('sales') }}" class="btn btn-blue text-xs">View all sales</a>
              @endif
            </div>
            <div class="overflow-x-auto">
            <table class="text-sm text-left">
              <thead class="uppercase">
                <tr>
                  <th scope="col" class="py-2 px-3">Product</th>
                  <th scope="col" class="py-2 px-3">Variant</th>
                  <th scope="col" class="py-2 px-3 text-center">Unit</th>
                  <th scope="col" class="py-2 px-3 text-right">Qty</th>
                  <th scope="col" class="py-2 px-3 text-right">Price per unit</th>
                  <th scope="col" class="py-2 px-3">Date</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($recentSales as $sale)
                  @php
  $utRaw = strtolower((string) ($sale->unit_type ?? 'pack'));
  if (!in_array($utRaw, ['kg', 'pack', 'bag'], true)) {
    $utRaw = 'pack';
  }
  $unitLabel = $utRaw === 'bag'
    ? 'Bag'
    : ($utRaw === 'kg' ? 'Kg' : 'Pack');
                  @endphp
                  <tr class="border-t">
                    <td class="py-2 px-3">{{ $sale->product_name }}</td>
                    <td class="py-2 px-3">
                      <span class="chip">{{ $sale->sale_type ?? 'N/A' }}</span>
                    </td>
                    <td class="py-2 px-3 text-center">{{ $unitLabel }}</td>
                    <td class="py-2 px-3 text-right">
                      {{ number_format($sale->quantity, 3) }}
                    </td>
                    <td class="py-2 px-3 text-right">
                      ₱{{ number_format($sale->unit_price, 2) }} / {{ strtolower($unitLabel) }}
                    </td>
                    <td class="py-2 px-3">
                      {{ \Carbon\Carbon::parse($sale->date)->timezone('Asia/Manila')->format('M d, Y') }}
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="6" class="py-3 text-center muted">No sales found.</td></tr>
                @endforelse
              </tbody>
            </table>
            </div>
          </div>

          <!-- Materials Snapshot (raw ingredients stay in kg) -->
          <div class="card p-5 rounded-2xl">
            <div class="flex items-center justify-between mb-2">
              <h2 class="text-base font-semibold">Materials Logged This Week</h2>
              <span class="text-xs muted">On hand: {{ number_format($totalMaterialsWeight, 2) }} kg</span>
            </div>
            @php $recentMaterials = $recentMaterials ?? collect(); @endphp
            @if($recentMaterials->isEmpty())
              <div class="text-sm muted">No materials logged this week.</div>
            @else
              <table class="text-sm text-left">
                <thead class="uppercase">
                  <tr>
                    <th scope="col" class="py-2 px-3">Material</th>
                    <th scope="col" class="py-2 px-3 text-right">Qty (kg)</th>
                    <th scope="col" class="py-2 px-3">Date</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($recentMaterials as $m)
                    <tr class="border-t">
                      <td class="py-2 px-3">{{ $m->name ?? $m->material_name ?? 'Material' }}</td>
                      <td class="py-2 px-3 text-right">{{ number_format($m->quantity_kg, 2) }}</td>
                      <td class="py-2 px-3">{{ \Carbon\Carbon::parse($m->created_at)->timezone('Asia/Manila')->format('M d, Y') }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            @endif
          </div>

          @php
// Expiry quick stats for finished meat units
$expiryStats = $expiryStats ?? [];
$totalExpiring = $expiryStats['total_expiring'] ?? 0;
$criticalExpiring = $expiryStats['critical'] ?? 0;
$highExpiring = $expiryStats['high'] ?? 0;
$mediumExpiring = $expiryStats['medium'] ?? 0;

// Priority list for actions
$expiryPriority = $expiryPriority ?? collect();
@endphp

<!-- Expiration Trend · Predictive and Action Oriented -->
<div class="card p-5 rounded-2xl">
  <div class="flex items-center justify-between mb-2">
    <div>
      <h2 class="text-base font-semibold">Expiration Risk and Actions</h2>
      <p class="text-[11px] muted">
        Finished meat packs and bags that are closest to expiry and what to move first.
      </p>
    </div>
    <div class="flex flex-col items-end gap-1 text-[11px]">
      <div class="flex items-center gap-2">
        <span class="px-2 py-0.5 rounded-full bg-yellow-50 border border-yellow-100 text-[10px] font-semibold">
          Packs or bags at risk
        </span>
        <span class="font-semibold tabular-nums">{{ number_format($totalExpiring, 0) }}</span>
      </div>
      <div class="flex items-center gap-2">
        <span class="w-2 h-2 rounded-full bg-red-500 shadow-[0_0_8px_rgba(248,113,113,.9)]"></span>
        <span class="muted">Critical (0 to 2 days)</span>
        <span class="font-semibold text-red-600 tabular-nums">{{ number_format($criticalExpiring, 0) }}</span>
      </div>
      <div class="flex items-center gap-2">
        <span class="w-2 h-2 rounded-full bg-amber-400 shadow-[0_0_8px_rgba(251,191,36,.9)]"></span>
        <span class="muted">High (3 to 5 days)</span>
        <span class="font-semibold text-amber-600 tabular-nums">{{ number_format($highExpiring, 0) }}</span>
      </div>
    </div>
  </div>

  <div class="h-56 relative">
    <p id="expiryDesc" class="sr-only">
      Bar chart of finished meat packs and bags that will expire in the next seven days by calendar day.
    </p>
    <canvas id="expiryChart" aria-label="Expiry chart" aria-describedby="expiryDesc"></canvas>
    <div id="expEmpty" class="absolute inset-0 hidden items-center justify-center text-sm muted text-center px-4">
      No expiries this week. Meat inventory is safe based on current data.
    </div>
  </div>

  <p class="text-[11px] muted mt-2">
    Bars represent expiring packs and bags per day. Taller bars signal where promos, priority dispatch or slower
    production may be needed.
  </p>

  <div class="border-t border-[var(--line)] mt-3 pt-3">
    <div class="flex items-center justify-between mb-2">
      <p class="text-xs font-semibold uppercase muted">Batches to move first</p>
      <p class="text-[11px] muted hidden sm:block">
        Suggestions based on days left before expiry. Tap a row to inspect that day in the demand calendar.
      </p>
    </div>

    @if($expiryPriority instanceof \Illuminate\Support\Collection && $expiryPriority->isNotEmpty())
      <div class="space-y-2 max-h-44 overflow-y-auto pr-1">
        @foreach($expiryPriority as $row)
          @php
    // Safe access for array or object
    $pName = data_get($row, 'product_name', 'Product');
    $batchNumber = data_get($row, 'batch_number');           // canonical batch from Production
    $batchCode = $batchNumber;                             // alias for tooltip
    $batchDisplay = data_get($row, 'batch_display_number');   // 1,2,3... for UI
    $variant = data_get($row, 'variant_label');
    $days = data_get($row, 'days_left');
    $units = (float) data_get($row, 'units_at_risk', 0); // packs or bags
    $action = data_get($row, 'recommended_action');

    $productId = data_get($row, 'product_id');
    $productionId = data_get($row, 'production_id');

    // Badge logic
    $badgeLabel = 'Monitor';
    $badgeClass = 'text-emerald-700 bg-emerald-50 border border-emerald-100';

    if (!is_null($days)) {
      $d = (int) $days;
      if ($d <= 0) {
        $badgeLabel = 'Very urgent';
        $badgeClass = 'text-red-700 bg-red-50 border border-red-100';
      } elseif ($d <= 2) {
        $badgeLabel = 'Sell today';
        $badgeClass = 'text-red-700 bg-red-50 border border-red-100';
      } elseif ($d <= 5) {
        $badgeLabel = 'Push this';
        $badgeClass = 'text-amber-700 bg-amber-50 border border-amber-100';
      }
    }
          @endphp

          <div
            class="flex items-start justify-between gap-3 text-xs expiry-batch-row cursor-pointer hover:bg-[var(--hover)] rounded-md px-1 py-1 transition"
            data-expiry-batch="true"
            @if(!is_null($days)) data-days-left="{{ (int) $days }}" @endif
            @if($productionId)  data-production-id="{{ $productionId }}" @endif
            @if($productId)     data-product-id="{{ $productId }}"       @endif
            @if($batchNumber)   data-batch-number="{{ $batchNumber }}"   @endif
            title="Open this batch day in the demand calendar"
          >
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-0.5">
                <span class="font-medium truncate">{{ $pName }}</span>

                @if($variant)
                  <span class="chip text-[10px]">{{ $variant }}</span>
                @endif

                {{-- Updated batch chip: show Batch 1, 2, 3... but keep real batch_number in title --}}
                @if($batchDisplay)
                  <span
                    class="chip text-[10px]"
                    @if($batchCode)
                      title="Actual batch number: {{ $batchCode }}"
                    @endif
                  >
                    Batch {{ $batchDisplay }}
                  </span>
                @elseif($batchCode)
                  <span class="chip text-[10px]">Batch {{ $batchCode }}</span>
                @endif

                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $badgeClass }}">
                  {{ $badgeLabel }}
                </span>
              </div>

              <div class="muted text-[11px]">
                {{ number_format($units, 0) }} pack(s) or bag(s) at risk
                @if(!is_null($days))
                  •
                  @php $d = (int) $days; @endphp
                  @if($d <= 0)
                    expiry reached
                  @elseif($d === 1)
                    1 day before expiry
                  @else
                    {{ $d }} days before expiry
                  @endif
                @endif
              </div>
            </div>

            @if($action)
              <div class="text-right text-[11px]">
                <div class="muted">Suggested move</div>
                <div class="font-semibold">
                  {{ $action }}
                </div>
              </div>
            @endif
          </div>
        @endforeach
      </div>
    @else
      <p class="text-xs muted">
        When expiry risk appears, this list will show which meat batches to push first, how many packs or bags are at risk and the suggested operational move.
      </p>
    @endif
  </div>
</div>

<!-- Weekly Production (units) -->
<div class="card p-5 rounded-2xl">
  <div class="flex items-center justify-between mb-2">
    <h2 class="text-base font-semibold">Weekly Production in Finished Units</h2>
  </div>
  <div class="h-56 relative">
    <p id="prodDesc" class="sr-only">
      Bar chart of weekly finished meat units produced, combined view of packs and bags.
    </p>
    <canvas id="productionChart" aria-label="Production chart" aria-describedby="prodDesc"></canvas>
    <div id="prodEmpty" class="absolute inset-0 hidden items-center justify-center text-sm muted">
      No data for this week
    </div>
  </div>
</div>

<!-- Weekly Sales with AI forecast -->
<div class="card p-5 rounded-2xl">
  <div class="flex items-center justify-between mb-2">
    <h2 class="text-base font-semibold">Weekly Sales in Finished Units</h2>
    <div class="flex items-center gap-3">
      <select id="weeklySalesMode" class="input w-48 py-1 text-xs" aria-label="Weekly sales view mode">
        <option value="quantity" selected>Quantity and revenue</option>
        <option value="profit">Revenue and profit</option>
        <option value="forecast">Next week forecast AI</option>
      </select>
    </div>
  </div>
  <div class="h-56 relative">
    <p id="salesDesc" class="sr-only">
      Combined chart of quantity sold in units for finished meat products, revenue, estimated profit and AI forecast.
    </p>
    <canvas id="salesChart" aria-label="Sales chart" aria-describedby="salesDesc"></canvas>
    <div id="salesEmpty" class="absolute inset-0 hidden items-center justify-center text-sm muted">
      No data for this week
    </div>
  </div>
  <p id="weeklySalesSummary" class="text-[11px] muted mt-2">
    This week you recorded ₱{{ number_format($weekRevenue ?? 0, 2) }} in revenue.
    Estimated profit is ₱{{ number_format($estimatedWeekProfit ?? 0, 2) }}
    @if(!is_null($estimatedGrossMarginPct ?? null))
      with about {{ $estimatedGrossMarginPct }}% gross margin.
    @endif
  </p>
  <p id="weeklySalesInsights" class="text-[11px] muted mt-1"></p>
</div>

<!-- Predictive Analytics · Production Planning Assistant -->
@php
$forecastSummary = $forecastSummary ?? [];
$forecastHorizonDays = $forecastSummary['horizon_days'] ?? 30;
$globalStockoutDateRaw = $forecastSummary['global_stockout_date'] ?? null;
$globalStockoutLabel = $globalStockoutDateRaw
  ? \Carbon\Carbon::parse($globalStockoutDateRaw)->timezone('Asia/Manila')->format('M d, Y')
  : 'No projected stockout';

$globalRecommendedProdUnits = $forecastSummary['total_recommended_production'] ?? null;

$forecastTopProducts = $forecastTopProducts ?? collect();
$productForecast = $productForecast ?? collect();

$atRiskCount = ($forecastTopProducts instanceof \Illuminate\Support\Collection)
  ? $forecastTopProducts->count()
  : 0;

$soonestDaysToStockout = null;
if ($forecastTopProducts instanceof \Illuminate\Support\Collection) {
  foreach ($forecastTopProducts as $row) {
    $d = $row['days_to_stockout'] ?? null;
    if (!is_null($d)) {
      $d = (int) $d;
      if (is_null($soonestDaysToStockout) || $d < $soonestDaysToStockout) {
        $soonestDaysToStockout = $d;
      }
    }
  }
}
@endphp


          <div class="card glass-card p-5 rounded-2xl">
            <div class="flex items-center justify-between mb-3">
              <div>
                <h2 class="text-base font-semibold">🔮 Production Planning Assistant</h2>
                <p class="text-xs muted">Suggested production in finished units for the next {{ $forecastHorizonDays }} days</p>
              </div>
              <div class="flex flex-col items-end gap-1 text-xs">
                <button type="button"
                        class="model-info-trigger text-[11px] underline text-blue-600 hover:text-blue-700"
                        data-context="Production Planning Model">
                  Model info
                </button>
                <div>
                  <div class="muted">Global stockout</div>
                  <div class="font-semibold">{{ $globalStockoutLabel }}</div>
                </div>

                @if(!is_null($globalRecommendedProdUnits))
                  <div class="text-[10px] muted">
                    Suggested total production in units<br>
                    <span class="font-semibold">{{ number_format($globalRecommendedProdUnits, 0) }} unit(s)</span>
                    <span class="muted block">(combined packs and bags)</span>
                  </div>
                @endif

                @if($atRiskCount > 0)
                  <div class="text-[10px] muted">
                    Products to watch: <span class="font-semibold">{{ $atRiskCount }}</span>
                  </div>
                @endif

                @if(!is_null($soonestDaysToStockout))
                  @php
  $urgencyClass = $soonestDaysToStockout <= 3 ? 'text-red-600' : ($soonestDaysToStockout <= 7 ? 'text-amber-600' : 'text-green-600');
  $soonestLabel = $soonestDaysToStockout <= 0 ? 'Already out of stock' : $soonestDaysToStockout . ' days';
                  @endphp
                  <div class="text-[10px] {{ $urgencyClass }}">
                    Earliest stockout: <span class="font-semibold">{{ $soonestLabel }}</span>
                  </div>
                @endif
              </div>
            </div>

            <div class="glass-chart-wrap mb-2 relative">
              <p id="forecastDesc" class="sr-only">
                Bar chart of expected daily orders in units for finished meat products and remaining stock if production is not increased.
              </p>
              <canvas id="forecastChart" aria-label="Forecast chart" aria-describedby="forecastDesc"></canvas>
              <div id="forecastEmpty" class="absolute inset-0 hidden items-center justify-center text-sm muted text-center px-4">
                Not enough historical data yet to generate a forecast. Keep recording production and sales to unlock planning suggestions.
              </div>
            </div>

            <p class="text-[11px] muted mb-1">
              Bars show expected daily orders and estimated stock in finished units. Use this to slot production runs for meat products.
            </p>
            <p id="forecastInsights" class="text-[11px] muted mb-3"></p>

            <div class="border-t border-[var(--line)] pt-3 mt-1">
              <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-semibold uppercase muted">What to produce next in units</p>
              </div>
              @if($forecastTopProducts instanceof \Illuminate\Support\Collection && $forecastTopProducts->isNotEmpty())
                <div class="space-y-2 max-h-44 overflow-y-auto pr-1">
                  @foreach($forecastTopProducts as $row)
                    @php
    $name = $row['name'] ?? 'Product';
    $dailyUnits = $row['daily_demand'] ?? 0;
    $recUnits = $row['recommended_production'] ?? null;
    $daysLeft = $row['days_to_stockout'] ?? null;
    $unitType = $row['unit_type'] ?? 'pack';
    $label = $row['label'] ?? ($name . ' (' . $unitType . ')');

    $badgeLabel = 'Planned';
    $badgeClass = 'text-green-700 bg-emerald-50 border border-emerald-100';
    if (!is_null($daysLeft)) {
      $d = (int) $daysLeft;
      if ($d <= 0) {
        $badgeLabel = 'Out of stock';
        $badgeClass = 'text-red-700 bg-red-50 border border-red-100';
      } elseif ($d <= 3) {
        $badgeLabel = 'Urgent';
        $badgeClass = 'text-red-700 bg-red-50 border border-red-100';
      } elseif ($d <= 7) {
        $badgeLabel = 'Produce this week';
        $badgeClass = 'text-amber-700 bg-amber-50 border border-amber-100';
      }
    }
    $targetWindow = (!is_null($daysLeft) && (int) $daysLeft > 0) ? (int) $daysLeft : 1;
                    @endphp

                    <div class="flex items-center justify-between text-xs gap-3">
                      <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                          <div class="font-medium truncate">{{ $label }}</div>
                          <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $badgeClass }}">
                            {{ $badgeLabel }}
                          </span>
                        </div>
                        <div class="muted text-[11px]">
                          AI daily demand about {{ number_format($dailyUnits, 0) }} unit(s) in {{ $unitType }} form
                        </div>
                      </div>
                      <div class="text-right ml-3">
                        @if(!is_null($daysLeft))
                          <div class="text-[11px] {{ $daysLeft <= 3 ? 'text-red-600' : ($daysLeft <= 7 ? 'text-amber-500' : 'text-green-600') }} ">
                            {{ $daysLeft <= 0 ? 'No days left' : $daysLeft . ' days left' }}
                          </div>
                        @endif
                        @if(!is_null($recUnits) && $recUnits > 0)
                          <div class="text-[11px] muted">
                            Produce
                            <span class="font-semibold">{{ number_format($recUnits, 0) }} unit(s)</span>
                            in the next
                            <span class="font-semibold">{{ $targetWindow }}</span>
                            {{ $targetWindow === 1 ? 'day' : 'days' }}
                          </div>
                        @endif
                      </div>
                    </div>
                  @endforeach
                </div>
              @else
                <p class="text-xs muted">
                  As soon as the system has enough history, it will suggest which finished meat items to produce next, how many units and how urgent they are.
                </p>
              @endif
            </div>
          </div>

          <!-- Top 5 Products to Produce This Week (AI) -->
          <div class="card p-5 rounded-2xl">
            <div class="flex items-center justify-between mb-3">
              <div>
                <h2 class="text-base font-semibold">🚀 Top Five Products To Produce This Week</h2>
                <p class="text-xs muted">
                  Based on recent demand, inventory and a short horizon forecast in finished units for packs and bags.
                </p>
              </div>
              <div class="text-right text-xs muted hidden sm:block">
                <p>Uses smoothed daily demand</p>
                <p>to estimate production in units.</p>
                <button type="button"
                        class="model-info-trigger mt-1 text-[11px] underline text-blue-600 hover:text-blue-700"
                        data-context="AI Top Five Recommendation Model">
                  Model info
                </button>
              </div>
            </div>

            @if($productForecast instanceof \Illuminate\Support\Collection && $productForecast->isNotEmpty())
              @php
  $topToProduce = $productForecast->sortByDesc('suggested_production')->take(5);
              @endphp
              <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                @foreach($topToProduce as $row)
                  @php
    $pName = $row['product_name'] ?? 'Product';
    $avgDemandUnits = $row['avg_daily_demand'] ?? 0;
    $forecastTotalUnits = $row['forecast_total'] ?? 0;
    $stockUnits = $row['current_inventory'] ?? 0;
    $suggestedUnits = $row['suggested_production'] ?? 0;
    $daysLeft = $row['days_to_stockout'] ?? null;

    $badgeLabel = 'Planned';
    $badgeClass = 'text-emerald-700 bg-emerald-50 border border-emerald-100';
    if (!is_null($daysLeft)) {
      $d = (int) $daysLeft;
      if ($d <= 0) {
        $badgeLabel = 'Out of stock';
        $badgeClass = 'text-red-700 bg-red-50 border border-red-100';
      } elseif ($d <= 3) {
        $badgeLabel = 'Urgent this week';
        $badgeClass = 'text-red-700 bg-red-50 border border-red-100';
      } elseif ($d <= 7) {
        $badgeLabel = 'Produce this week';
        $badgeClass = 'text-amber-700 bg-amber-50 border border-amber-100';
      }
    }
                  @endphp

                  <div class="flex items-start justify-between gap-3 text-xs">
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center gap-2 mb-0.5">
                        <span class="font-medium truncate">{{ $pName }}</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $badgeClass }}">
                          {{ $badgeLabel }}
                        </span>
                      </div>
                      <div class="muted text-[11px]">
                        AI daily demand
                        <span class="font-semibold">{{ number_format($avgDemandUnits, 0) }} unit(s)</span>
                        • Week demand
                        <span class="font-semibold">{{ number_format($forecastTotalUnits, 0) }} unit(s)</span>
                      </div>
                      <div class="muted text-[11px]">
                        Current inventory
                        <span class="font-semibold">{{ number_format($stockUnits, 0) }} unit(s)</span>
                        @if(!is_null($daysLeft))
                          •
                          @if($daysLeft <= 0)
                            already out of stock
                          @elseif($daysLeft === 1)
                            approx. 1 day before stockout
                          @else
                            approx. {{ $daysLeft }} days before stockout
                          @endif
                        @endif
                      </div>
                    </div>
                    <div class="text-right">
                      <div class="text-[11px] muted">
                        Suggested this week
                      </div>
                      <div class="font-semibold text-[13px]">
                        {{ number_format($suggestedUnits, 0) }} unit(s)
                      </div>
                    </div>
                  </div>
                @endforeach
              </div>
            @else
              <p class="text-xs muted">
                Once the plant has enough sales and production history, this panel will highlight the top five finished meat products to prioritize with suggested production in units.
              </p>
            @endif
          </div>

        </div>
      </main>
    </div>
  </div>

  <!-- Materials Used (full width, raw ingredients in kg) -->
  @php
use Carbon\Carbon;

$rangeLabel = isset($filterStart, $filterEnd)
  ? Carbon::parse($filterStart)->format('M d, Y') . ' to ' . Carbon::parse($filterEnd)->format('M d, Y')
  : 'This week';
  @endphp

  <div class="mx-8 my-6 card p-5 rounded-2xl">
    <div class="flex items-center justify-between mb-2">
      <div>
        <h2 class="text-base font-semibold">
          Materials Used
          <span class="text-[11px] font-normal muted">
            ({{ $rangeLabel }})
          </span>
        </h2>
        <p class="text-xs muted">Raw meat and ingredients based on production recipes</p>
      </div>
      <div class="text-right text-xs muted">
        <div>Total quantity: {{ number_format($materialsUsageTotals['qty'] ?? 0, 3) }}</div>
        <div>Total cost: ₱{{ number_format($materialsUsageTotals['cost'] ?? 0, 2) }}</div>
      </div>
    </div>

    @php $rows = $materialsUsage ?? collect(); @endphp
    @if($rows->isEmpty())
      <div class="text-sm muted">No materials consumed for this period.</div>
    @else
      <div class="overflow-x-auto">
        <table class="text-sm text-left">
          <thead class="uppercase">
            <tr>
              <th scope="col" class="py-2 px-3">Material</th>
              <th scope="col" class="py-2 px-3 text-right">Qty Used</th>
              <th scope="col" class="py-2 px-3 text-right">Cost</th>
            </tr>
          </thead>
          <tbody>
            @foreach($rows as $r)
              <tr class="border-t">
                <td class="py-2 px-3">{{ $r->material_name }}</td>
                <td class="py-2 px-3 text-right">
                  {{ number_format($r->qty_used, 3) }} {{ $r->unit ?? 'kg' }}
                </td>
                <td class="py-2 px-3 text-right">₱{{ number_format($r->cost_used, 2) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  <!-- Floating Demand Forecast Calendar Button -->
  <button
      id="demandCalendarButton"
      type="button"
      class="fixed z-40 bottom-6 right-6 md:bottom-8 md:right-8 h-12 w-12 rounded-full shadow-lg border border-[var(--line)] bg-white flex items-center justify-center hover:shadow-xl hover:-translate-y-0.5 transition transform"
      title="View demand forecast calendar"
      aria-label="View demand forecast calendar"
  >
    <span class="relative inline-flex items-center justify-center">
      <span class="text-xl" aria-hidden="true">📅</span>
      <span class="sr-only">Open inventory demand forecast calendar</span>
    </span>
  </button>

  <!-- Reservation Modal -->
  <div id="reservationModal"
       class="fixed inset-0 bg-black/40 z-50 hidden items-center justify-center">
    <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full mx-4 p-5">
      <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-semibold">Add reservation</h3>
        <button type="button"
                class="text-xs px-2 py-1 rounded-full border border-gray-200 hover:bg-gray-100"
                data-reservation-close="true">✕</button>
      </div>

            <form method="POST" action="{{ route('reservations.store') }}">
        @csrf

        {{-- hidden date used by controller --}}
        <input type="hidden" name="reserved_date" id="reservation_date_input">

        {{-- preserve current filter range so dashboard reload keeps same dates --}}
        @if(request('start'))
          <input type="hidden" name="start" value="{{ request('start') }}">
        @endif
        @if(request('end'))
          <input type="hidden" name="end" value="{{ request('end') }}">
        @endif

        <div class="space-y-3 text-sm">
  {{-- Date (read only label) --}}
  <div>
    <label class="block text-xs font-semibold mb-1">
      Date
    </label>
    <input type="text"
           id="reservation_date_label"
           class="input bg-gray-100 cursor-not-allowed"
           disabled>
  </div>

  {{-- Product (required → fixes product_id not null) --}}
  <div>
    <label class="block text-xs font-semibold mb-1">
      Product
      <span class="text-red-500">*</span>
    </label>
    <select name="product_id" class="input" required>
      <option value="">Select meat product</option>
      @foreach($products as $product)
        <option value="{{ $product->id }}">
          {{ $product->product_name }}
        </option>
      @endforeach
    </select>
  </div>

  {{-- NEW: Type / Variant (optional, but selectable) --}}
  <div>
    <label class="block text-xs font-semibold mb-1">
      Type / variant
    </label>
    <select name="type_label" id="reservation_type_select" class="input" required>
      <option value="">Select type / variant</option>
    </select>
    <p class="text-[11px] muted mt-1">
      Pick the exact variant for this reservation. It should match the production variant.
    </p>
  </div>

  {{-- Unit type: per pack / per bag --}}
  <div class="grid grid-cols-2 gap-2">
    <div>
      <label class="block text-xs font-semibold mb-1">
        Units to reserve
        <span class="text-red-500">*</span>
      </label>
      <input type="number"
             name="units"
             class="input"
             min="1"
             required>
    </div>

    <div>
      <label class="block text-xs font-semibold mb-1">
        Unit type
      </label>
      <select name="unit_type" class="input">
        <option value="pack">Per pack</option>
        <option value="bag">Per bag</option>
      </select>
    </div>
  </div>

  <div>
    <label class="block text-xs font-semibold mb-1">
      Reference / customer / note
    </label>
    <textarea name="notes"
              class="input min-h-[70px]"
              rows="3"
              placeholder="Ex: Reservation for Client X, supermarket order, etc."></textarea>
  </div>
</div>

        <div class="mt-4 flex justify-end gap-2">
          <button type="button"
                  data-reservation-close="true"
                  class="btn btn-ghost text-xs">
            Cancel
          </button>
          <button type="submit" class="btn btn-primary text-xs">
            Save reservation
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Model Info Modal (shared for planning and top five AI) -->
  <div id="modelInfoModal"
       class="fixed inset-0 bg-black/40 z-40 hidden items-center justify-center"
       data-modal-overlay="true">
    <div class="bg-white rounded-2xl shadow-xl max-w-md w-full mx-4 p-5 relative">
      <div class="flex items-center justify-between mb-2">
        <h3 id="modelInfoTitle" class="text-sm font-semibold">Model information</h3>
        <button type="button"
                class="text-xs px-2 py-1 rounded-full border border-gray-200 hover:bg-gray-100"
                data-close-model="true">
          ✕
        </button>
      </div>
      <p class="text-xs muted mb-2">
        These details describe how the AI forecast behind this panel was configured.
      </p>
      <ul class="text-xs muted space-y-1">
        <li>Model used: <span class="font-semibold">[to fill]</span></li>
        <li>Training range: <span class="font-semibold">[to fill]</span></li>
        <li>Error metric MAPE: <span class="font-semibold">[to fill]%</span></li>
      </ul>
      <p class="text-[11px] muted mt-3">
        For capstone defense, be ready to explain how this model was selected, how it was validated and how often it will be retrained on fresh production and sales data.
      </p>
    </div>
  </div>

  @php
// Use the filter range (if any) to decide which month to show in the calendar
$filterStartDate = isset($filterStart) ? Carbon::parse($filterStart) : Carbon::today()->startOfMonth();
$filterEndDate = isset($filterEnd) ? Carbon::parse($filterEnd) : Carbon::today();

$calendarRef = $filterStartDate->copy(); // month anchor
$calendarYear = $calendarRef->year;
$calendarMonth = $calendarRef->month;

$calendarDemand = $demandCalendar ?? [];
  @endphp

  <!-- Demand Forecast Calendar Modal (LIGHT theme, logo-inspired) -->
  <div
      id="demandCalendarModal"
      class="fixed inset-0 z-40 hidden items-center justify-center bg-black/30 backdrop-blur-sm"
  >
    <div class="w-full max-w-5xl mx-4 bg-[var(--card)] text-[var(--ink)] rounded-2xl shadow-2xl flex flex-col md:flex-row overflow-hidden border border-[var(--line)]">
        <!-- LEFT: Calendar -->
        <div class="w-full md:w-2/3 p-5 border-b md:border-b-0 md:border-r border-[var(--line)] bg-[#fdfcf6]">
            <div class="flex items-center justify-between mb-4 gap-3">
                <button
                    id="calendarPrevMonth"
                    type="button"
                    class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-white border border-[var(--line)] text-[var(--ink)] hover:bg-[var(--hover)] transition"
                >
                    ‹
                </button>

                <div class="text-center flex-1">
                    <div id="calendarMonthLabel" class="text-sm font-semibold text-[var(--ink)]">
                        {{ \Carbon\Carbon::create($calendarYear, $calendarMonth, 1)->format('F Y') }}
                    </div>
                    <div id="calendarSelectedRangeLabel" class="text-[11px] text-[var(--muted)] mt-0.5">
                        @if(isset($filterStart, $filterEnd))
                          {{ \Carbon\Carbon::parse($filterStart)->format('M d, Y') }} – {{ \Carbon\Carbon::parse($filterEnd)->format('M d, Y') }}
                        @else
                          No range selected yet
                        @endif
                    </div>
                </div>

                <button
                    id="calendarNextMonth"
                    type="button"
                    class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-white border border-[var(--line)] text-[var(--ink)] hover:bg-[var(--hover)] transition"
                >
                    ›
                </button>
            </div>

            <!-- Weekday header -->
            <div class="grid grid-cols-7 text-[11px] font-medium text-[var(--muted)] tracking-wide mb-1.5">
                <div class="text-center">Mon</div>
                <div class="text-center">Tue</div>
                <div class="text-center">Wed</div>
                <div class="text-center">Thu</div>
                <div class="text-center">Fri</div>
                <div class="text-center">Sat</div>
                <div class="text-center">Sun</div>
            </div>

            <!-- Calendar grid will be injected here -->
            <div id="calendarGrid" class="grid grid-cols-7 gap-1.5 text-xs md:text-sm"></div>

            <!-- Legend and actions -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mt-4">
                <div class="flex flex-wrap gap-2 text-[11px] text-[var(--muted)]">
                    <span class="inline-flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-[var(--brand-yellow)]"></span>
                        High demand
                    </span>
                    <span class="inline-flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-[#bbf7d0]"></span>
                        Low demand
                    </span>
                    <span class="inline-flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-[#22c55e]"></span>
                        Reservations
                    </span>
                    <span class="inline-flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-[#fb923c]"></span>
                        Holidays and events
                    </span>
                </div>

                <div class="flex gap-2 w-full sm:w-auto">
                    <button
                        id="demandCalendarReset"
                        type="button"
                        class="flex-1 sm:flex-none px-3 py-1.5 rounded-lg border border-[var(--line)] bg-white text-[12px] text-[var(--ink)] hover:bg-[var(--hover)] transition"
                    >
                        Reset
                    </button>
                    <button
                        id="demandCalendarApply"
                        type="button"
                        class="flex-1 sm:flex-none px-3 py-1.5 rounded-lg bg-[var(--brand-red)] text-[12px] font-semibold text-white hover:brightness-95 disabled:opacity-40 disabled:cursor-not-allowed transition"
                        disabled
                    >
                        Apply range
                    </button>
                </div>
            </div>
        </div>

        <!-- RIGHT: Day details -->
        <div class="w-full md:w-1/3 p-5 space-y-3 bg-white">
            <div class="flex items-center justify-between">
                <div class="space-y-0.5">
                    <div class="text-[13px] font-semibold uppercase tracking-wide text-[var(--muted)]">
                        Day insights
                    </div>
                    <div id="calendarDayTitle" class="text-base font-semibold text-[var(--ink)]">
                        Pick a day on the calendar
                    </div>
                </div>

            <button
                id="demandCalendarClose"
                type="button"
                class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-white border border-[var(--line)] text-[var(--muted)] hover:bg-[var(--hover)] transition"
            >
                ✕
            </button>
            </div>

            <div id="calendarDayBadges" class="flex flex-wrap gap-1.5 text-[10px]"></div>

            <dl class="space-y-2 text-[11px]">

    <!-- Forecast Demand -->
    <div class="flex items-center justify-between">
      <dt class="text-[var(--muted)]">Forecast demand</dt>
      <dd id="calendarDayDemand" class="font-semibold text-[var(--ink)]">–</dd>
    </div>
    
    <!-- Forecast Remaining -->
    <div class="flex items-center justify-between">
      <dt class="text-[var(--muted)]">Forecast remaining stock</dt>
      <dd id="calendarDayInventory" class="font-semibold text-[var(--ink)]">–</dd>
    </div>
    
    <!-- Reserved Units -->
    <div class="flex items-center justify-between">
      <dt class="text-[var(--muted)]">Reserved units</dt>
      <dd id="calendarDayReserved" class="font-semibold text-[var(--ink)]">–</dd>
    </div>
    
    <!-- Net Available -->
    <div class="flex items-center justify-between">
      <dt class="text-[var(--muted)]">Net available</dt>
      <dd id="calendarDayNet" class="font-semibold text-[var(--ink)]">–</dd>
    </div>
    
    <!-- NEW: Product -->
    <div class="flex items-center justify-between">
      <dt class="text-[var(--muted)]">Product</dt>
      <dd id="calendarDayProduct" class="font-semibold text-[var(--ink)] truncate max-w-[140px]">–</dd>
    </div>
    
    <!-- NEW: Unit Type -->
    <div class="flex items-center justify-between">
      <dt class="text-[var(--muted)]">Unit type</dt>
      <dd id="calendarDayUnitType" class="font-semibold text-[var(--ink)]">–</dd>
    </div>
    
    <!-- NEW: Reference / Notes -->
    <div class="flex items-center justify-between">
      <dt class="text-[var(--muted)]">Reference / notes</dt>
      <dd id="calendarDayNotesDetails" class="font-semibold text-[var(--ink)] text-right truncate max-w-[140px]">–</dd>
    </div>
    
    </dl>


            <div class="rounded-xl bg-[var(--brand-yellow-06)] border border-[var(--brand-yellow-40)] px-3 py-2.5">
                <p id="calendarDayNotes" class="text-[11px] leading-relaxed text-[var(--muted)]">
                    Use the calendar on the left to inspect a day. High demand days are perfect for planning extra production and staffing.
                </p>

                <button
                    id="openReservationButton"
                    type="button"
                    class="mt-3 inline-flex items-center px-3 py-1.5 rounded-lg bg-[var(--brand-red)] text-[11px] font-semibold text-white hover:brightness-95">
                    Add reservation for this day
                </button>
            </div>
        </div>
    </div>
  </div>

  <!-- ===========================
     SCRIPTS (Unified & Updated)
     =========================== -->
<script>
  document.addEventListener('DOMContentLoaded', () => {
    /* ============================================================
     * 0. SHARED CONSTANTS + HELPERS
     * ============================================================ */
    const C_RED        = 'rgba(239,68,68,1)';
    const C_RED_SOFT   = 'rgba(248,113,113,0.6)';
    const C_GREEN      = 'rgba(34,197,94,1)';
    const C_GREEN_SOFT = 'rgba(34,197,94,0.6)';
    const C_BLUE       = 'rgba(37,99,235,1)';
    const TICK_COLOR   = '#64748b';
    const GRID_COLOR   = 'rgba(148,163,184,0.16)';
    const BAR_RADIUS   = 10;

    const palette = [
      'rgba(248,113,113,0.9)',
      'rgba(251,146,60,0.9)',
      'rgba(52,211,153,0.9)',
      'rgba(59,130,246,0.9)',
      'rgba(244,114,182,0.9)',
      'rgba(96,165,250,0.9)'
    ];

    const barFillByIndex = (i) => palette[i % palette.length];

    const sumArray = (arr) => (arr || []).reduce((s, v) => s + Number(v || 0), 0);

    const maxIndex = (arr) => {
      let max = -Infinity;
      let idx = -1;
      (arr || []).forEach((v, i) => {
        const n = Number(v || 0);
        if (n > max) { max = n; idx = i; }
      });
      return idx;
    };

    const setEmptyBanner = (values, emptyId) => {
      const el = document.getElementById(emptyId);
      if (!el) return;
      const hasData = (values || []).some(v => Number(v || 0) !== 0);
      el.classList.toggle('hidden', hasData);
      el.classList.toggle('flex', !hasData);
    };

    const mk = (id, cfg) => {
      if (typeof Chart === 'undefined') return null;
      const canvas = document.getElementById(id);
      if (!canvas) return null;
      const ctx = canvas.getContext('2d');
      return new Chart(ctx, cfg);
    };

    let debounceTimer;
    const debounce = (fn, ms = 120) => (...a) => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => fn(...a), ms);
    };

    // 💰 Shared number formatters (used by Sales Report + charts)
    const formatCurrency = (value) => {
      const num = Number(value) || 0;
      return '₱' + num.toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
    };

    const formatInteger = (value) => {
      const num = Number(value) || 0;
      return num.toLocaleString('en-PH', { maximumFractionDigits: 0 });
    };

    /* ============================================================
     * 1. BACK-END DATA (Blade → JS)
     * ============================================================ */
    const filterStart       = @json($filterStart ?? null);
    const filterEnd         = @json($filterEnd ?? null);
    const calendarEventsRaw = @json($calendarEvents ?? []);

    const labels   = @json($labels ?? []);
    const prod     = @json($weeklyProductionSeries ?? []);
    const qty      = @json($weeklySalesQtySeries ?? []);
    const rev      = @json($weeklySalesRevenueSeries ?? []);
    const profit   = @json($weeklySalesProfitSeries ?? []);

    const expiryLabels = @json($expiryLabels ?? []);
    const exp          = @json($weeklyExpirySeries ?? []);

    const forecastLabels    = @json($forecastLabels ?? []);
    const forecastDemand    = @json($forecastDemandSeries ?? []);
    const forecastInventory = @json($forecastInventorySeries ?? []);

    const qtyForecast    = @json($weeklySalesForecastQtySeries ?? []);
    const revForecast    = @json($weeklySalesForecastRevenueSeries ?? []);
    const profitForecast = @json($weeklySalesForecastProfitSeries ?? []);

    const weekRevenueJS         = @json($weekRevenue ?? 0);
    const estimatedWeekProfitJS = @json($estimatedWeekProfit ?? 0);
    const estimatedMarginJS     = @json($estimatedGrossMarginPct ?? null);

    // API route for reservation type picker
    const typesApiUrl = @json(\Illuminate\Support\Facades\Route::has('sales.api.types') ? route('sales.api.types') : null);

    // Normalize calendar events into { 'YYYY-MM-DD': meta }
    const calendarEvents = {};
    (calendarEventsRaw || []).forEach(item => {
      if (!item || !item.date) return;
      const key = item.date;

      calendarEvents[key] = {
        ...item,

        // demand level
        level: item.demand_level || item.level || null,

        // numeric metrics
        demand_units:    item.demand_units    ?? item.demand    ?? null,
        inventory_units: item.inventory_units ?? item.inventory ?? null,
        net_available:   item.net_available   ?? null,
        reservations:    item.reservations    ?? item.reserved_units ?? 0,

        // product / unit / summary
        product_name:        item.product_name ?? item.product_label ?? item.product ?? null,
        unit_type:           item.unit_type ?? item.unit ?? null,
        reservation_summary: item.reservation_summary ?? item.reference ?? null,

        // text notes
        notes: item.note ?? item.notes ?? null,
      };
    });

    /* ============================================================
     * 2. CHARTS
     * ============================================================ */
    function initCharts() {
      // Production chart
      setEmptyBanner(prod, 'prodEmpty');
      const productionChart = mk('productionChart', {
        type: 'bar',
        data: {
          labels,
          datasets: [{
            label: 'Units produced (packs and bags)',
            data: prod,
            backgroundColor: (ctx) => {
              const chart = ctx.chart;
              const { ctx: c, chartArea } = chart;
              if (!chartArea) return 'rgba(248,250,252,1)';
              const g = c.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
              g.addColorStop(0, 'rgba(241,245,249,1)');
              g.addColorStop(0.5, 'rgba(248,113,113,.5)');
              g.addColorStop(1, 'rgba(248,113,113,.9)');
              return g;
            },
            borderColor: 'rgba(15,23,42,1)',
            borderWidth: 1.5,
            borderRadius: BAR_RADIUS
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { labels: { color: TICK_COLOR } },
            title: {
              display: true,
              text: 'Weekly production in finished units',
              color: '#0f172a'
            }
          },
          scales: {
            x: { ticks: { color: TICK_COLOR }, grid: { color: GRID_COLOR } },
            y: {
              beginAtZero: true,
              ticks: { color: TICK_COLOR },
              grid:  { color: GRID_COLOR },
              title: { display: true, text: 'Units', color: TICK_COLOR }
            }
          }
        }
      });

      // Sales with AI (actual and forecast)
      setEmptyBanner([...qty, ...rev], 'salesEmpty');
      const salesChart = mk('salesChart', {
        data: {
          labels,
          datasets: [
            {
              key: 'qtyActual',
              type: 'bar',
              label: 'Quantity sold in units',
              data: qty,
              yAxisID: 'y',
              backgroundColor: (ctx) => {
                const chart = ctx.chart;
                const { ctx: c, chartArea } = chart;
                if (!chartArea) return 'rgba(251,113,133,.7)';
                const g = c.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
                g.addColorStop(0, 'rgba(248,250,252,1)');
                g.addColorStop(0.5, 'rgba(251,113,133,.7)');
                g.addColorStop(1, 'rgba(248,113,113,1)');
                return g;
              },
              borderColor: 'rgba(15,23,42,1)',
              borderWidth: 1.5,
              borderRadius: BAR_RADIUS
            },
            {
              key: 'revActual',
              type: 'line',
              label: 'Revenue',
              data: rev,
              yAxisID: 'y1',
              borderColor: C_RED,
              backgroundColor: C_RED,
              borderWidth: 3,
              tension: 0.4,
              pointRadius: 3,
              pointHoverRadius: 6,
              fill: false
            },
            {
              key: 'profitActual',
              type: 'line',
              label: 'Estimated profit',
              data: profit,
              yAxisID: 'y1',
              borderColor: C_GREEN_SOFT,
              backgroundColor: C_GREEN_SOFT,
              borderWidth: 2,
              tension: 0.35,
              pointRadius: 2.5,
              pointHoverRadius: 5,
              fill: false,
              hidden: true
            },
            {
              key: 'qtyForecast',
              type: 'bar',
              label: 'Predicted quantity in units AI',
              data: qtyForecast,
              yAxisID: 'y',
              backgroundColor: 'rgba(59,130,246,.25)',
              borderColor: 'rgba(37,99,235,1)',
              borderWidth: 1.5,
              borderRadius: BAR_RADIUS,
              borderDash: [4,3],
              hidden: true
            },
            {
              key: 'revForecast',
              type: 'line',
              label: 'Predicted revenue AI',
              data: revForecast,
              yAxisID: 'y1',
              borderColor: C_RED_SOFT,
              backgroundColor: C_RED_SOFT,
              borderWidth: 2,
              tension: 0.4,
              pointRadius: 2,
              pointHoverRadius: 5,
              fill: false,
              borderDash: [6,4],
              hidden: true
            },
            {
              key: 'profitForecast',
              type: 'line',
              label: 'Predicted profit AI',
              data: profitForecast,
              yAxisID: 'y1',
              borderColor: C_GREEN_SOFT,
              backgroundColor: C_GREEN_SOFT,
              borderWidth: 2,
              tension: 0.4,
              pointRadius: 2,
              pointHoverRadius: 5,
              fill: false,
              borderDash: [6,4],
              hidden: true
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { labels: { color: TICK_COLOR } },
            title: {
              display: true,
              text: 'Weekly sales in units and revenue',
              color: '#0f172a'
            },
            tooltip: {
              backgroundColor: 'rgba(15,23,42,.96)',
              borderColor: 'rgba(148,163,184,.7)',
              borderWidth: 1,
              padding: 10,
              cornerRadius: 10,
              callbacks: {
                label: (ctx) => {
                  const label = ctx.dataset.label || '';
                  const val   = Number(ctx.parsed.y);
                  const formatted = val.toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                  });

                  const lower = label.toLowerCase();
                  if (lower.includes('revenue')) {
                    const tag = lower.includes('predicted') ? 'Predicted revenue' : 'Revenue';
                    return `${tag}: ₱${formatted}`;
                  }
                  if (lower.includes('profit')) {
                    const tag = lower.includes('predicted') ? 'Predicted profit' : 'Estimated profit';
                    return `${tag}: ₱${formatted}`;
                  }
                  if (lower.includes('predicted quantity')) {
                    return `Predicted quantity: ${val.toLocaleString()} unit(s)`;
                  }
                  return `Quantity: ${val.toLocaleString()} unit(s)`;
                }
              }
            }
          },
          scales: {
            x: { ticks: { color: TICK_COLOR }, grid: { color: GRID_COLOR } },
            y: {
              position: 'left',
              beginAtZero: true,
              ticks: { color: TICK_COLOR },
              grid:  { color: GRID_COLOR },
              title: { display: true, text: 'Units', color: TICK_COLOR }
            },
            y1: {
              position: 'right',
              beginAtZero: true,
              ticks: { color: TICK_COLOR },
              grid:  { drawOnChartArea: false },
              title: { display: true, text: '₱', color: TICK_COLOR }
            }
          }
        }
      });

      // Sales sparkline (small trend) – now stored so we can update it via range selector
      const salesSparklineChart = mk('salesTrendsChart', {
        type: 'line',
        data: {
          labels,
          datasets: [{
            label: 'Revenue',
            data: rev,
            borderColor: 'rgba(34,211,238,0.95)',        // neon cyan
            backgroundColor: 'rgba(34,211,238,0.16)',    // soft glow
            borderWidth: 2,
            tension: 0.4,
            pointRadius: 0,
            fill: true
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            x: { display: false },
            y: { display: false, beginAtZero: true }
          }
        }
      });

      // Expiry chart
      setEmptyBanner(exp, 'expEmpty');
      mk('expiryChart', {
        type: 'bar',
        data: {
          labels: expiryLabels,
          datasets: [{
            label: 'Packs or bags expiring',
            data: exp,
            backgroundColor: 'rgba(249,115,22,.7)',
            borderColor: 'rgba(148,27,12,1)',
            borderWidth: 1.5,
            borderRadius: BAR_RADIUS
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { labels: { color: TICK_COLOR } },
            title: {
              display: true,
              text: 'Packs and bags at risk this week',
              color: '#0f172a'
            },
            tooltip: {
              callbacks: {
                label: (ctx) => {
                  const v = Number(ctx.parsed.y);
                  return `Expiring packs or bags: ${v.toLocaleString()}`;
                }
              }
            }
          },
          scales: {
            x: { ticks: { color: TICK_COLOR }, grid: { color: GRID_COLOR } },
            y: { beginAtZero: true, ticks: { color: TICK_COLOR }, grid: { color: GRID_COLOR } }
          }
        }
      });

      // Forecast chart (global)
      setEmptyBanner([...forecastDemand, ...forecastInventory], 'forecastEmpty');
      mk('forecastChart', {
        type: 'bar',
        data: {
          labels: forecastLabels,
          datasets: [
            {
              type: 'bar',
              label: 'Expected daily orders (units)',
              data: forecastDemand,
              yAxisID: 'y',
              borderColor: C_RED,
              backgroundColor: 'rgba(248,113,113,.55)',
              borderWidth: 2,
              borderRadius: BAR_RADIUS
            },
            {
              type: 'bar',
              label: 'Estimated remaining stock (units)',
              data: forecastInventory,
              yAxisID: 'y',
              borderColor: C_GREEN,
              backgroundColor: 'rgba(34,197,94,.5)',
              borderWidth: 2,
              borderRadius: BAR_RADIUS
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { labels: { color: TICK_COLOR } }
          },
          scales: {
            x: {
              ticks: { color: TICK_COLOR },
              grid:  { color: 'rgba(148,163,184,.12)', drawBorder:false }
            },
            y: {
              beginAtZero: true,
              ticks: { color: TICK_COLOR },
              grid:  { color: 'rgba(148,163,184,.22)', drawBorder:false },
              title: { display: true, text: 'Units', color: TICK_COLOR }
            }
          }
        }
      });

      /* ---------- Forecast insights (text under chart) ---------- */
      const forecastInsightsEl = document.getElementById('forecastInsights');
      const updateForecastInsights = () => {
        if (!forecastInsightsEl) return;
        const totalDemand = sumArray(forecastDemand);
        if (!forecastDemand.length || totalDemand === 0) {
          forecastInsightsEl.textContent =
            'When forecast data is available, this will highlight the busiest day and the day with the lowest projected stock in finished units.';
          return;
        }
        const bestIdx = maxIndex(forecastDemand);
        const bestLabel = bestIdx >= 0 && forecastLabels[bestIdx] !== undefined
          ? forecastLabels[bestIdx]
          : 'Day';
        const bestVal = bestIdx >= 0 ? forecastDemand[bestIdx] : 0;

        // pseudo minimum: invert values and use maxIndex
        const minStockIdx = maxIndex(forecastInventory.map(v => v === 0 ? -Infinity : -v));
        const minStockLabel = minStockIdx >= 0 && forecastLabels[minStockIdx] !== undefined
          ? forecastLabels[minStockIdx]
          : null;
        const minStockVal = minStockIdx >= 0 ? forecastInventory[minStockIdx] : null;

        const lines = [
          `Busiest expected orders day is ${bestLabel} with about ${Math.round(bestVal).toLocaleString()} unit(s).`,
          `Total expected demand over the forecast window is about ${Math.round(totalDemand).toLocaleString()} unit(s).`
        ];
        if (minStockLabel !== null && minStockVal !== null) {
          lines.push(
            `Lowest projected remaining stock is about ${Math.round(minStockVal).toLocaleString()} unit(s) on ${minStockLabel}.`
          );
        }
        forecastInsightsEl.textContent = lines.join(' ');
      };
      updateForecastInsights();

      /* ---------- Weekly sales mode + insights ---------- */
      const weeklySalesMode     = document.getElementById('weeklySalesMode');
      const weeklySalesInsights = document.getElementById('weeklySalesInsights');

      const findDs = (key) => salesChart?.data.datasets.find(d => d.key === key) || null;

      const buildWeeklySalesInsights = (mode) => {
        if (!weeklySalesInsights) return;
        const insights = [];

        const totalQty     = sumArray(qty);
        const totalRevenue = weekRevenueJS || sumArray(rev);
        const totalProfit  = estimatedWeekProfitJS || sumArray(profit);
        const margin       = isFinite(estimatedMarginJS) && estimatedMarginJS > 0
          ? estimatedMarginJS
          : null;

        const bestIdx      = maxIndex(qty);
        const bestDayLabel = bestIdx >= 0 && labels[bestIdx] !== undefined ? labels[bestIdx] : null;
        const bestDayQty   = bestIdx >= 0 ? qty[bestIdx] : 0;

        if (mode === 'quantity') {
          if (totalQty > 0) {
            insights.push(`You sold about ${Math.round(totalQty).toLocaleString()} finished unit(s) in packs and bags for this period.`);
          }
          if (bestDayLabel && bestDayQty > 0) {
            insights.push(`${bestDayLabel} was the strongest day with about ${Math.round(bestDayQty).toLocaleString()} unit(s) sold.`);
          }
          if (totalRevenue > 0) {
            insights.push(`Total recorded revenue for this period is around ₱${totalRevenue.toLocaleString(undefined,{ maximumFractionDigits: 2 })}.`);
          }
        } else if (mode === 'profit') {
          if (totalRevenue > 0) {
            insights.push(`This period generated about ₱${totalRevenue.toLocaleString(undefined,{ maximumFractionDigits: 2 })} in revenue.`);
          }
          if (totalProfit > 0) {
            insights.push(`Estimated profit is around ₱${totalProfit.toLocaleString(undefined,{ maximumFractionDigits: 2 })}.`);
          }
          if (margin !== null) {
            insights.push(`Gross margin is approximately ${margin.toFixed(1)}%.`);
          } else {
            insights.push('As cost data improves, the gross margin estimate will become more accurate.');
          }
        } else if (mode === 'forecast') {
          const totalForecastQty = sumArray(qtyForecast);
          const totalForecastRev = sumArray(revForecast);
          const hasForecast = totalForecastQty > 0 || totalForecastRev > 0;

          if (!hasForecast) {
            insights.push('Not enough historical data yet to show a forecast. Keep recording production and sales to unlock next week predictions.');
          } else {
            insights.push(`Next week the AI expects about ${Math.round(totalForecastQty).toLocaleString()} finished unit(s) to be sold in packs and bags.`);
            if (totalForecastRev > 0) {
              insights.push(`Forecast revenue for the same period is around ₱${totalForecastRev.toLocaleString(undefined,{ maximumFractionDigits: 2 })}.`);
            }
            if (bestDayLabel && bestDayQty > 0) {
              insights.push(`Use this to schedule staffing and production around the busier days like ${bestDayLabel}.`);
            }
          }
        }

        if (!insights.length) {
          insights.push('When more data is recorded, this section will summarize which days are strongest and how current results compare to the forecast.');
        }

        weeklySalesInsights.textContent = insights.join(' ');
      };

      const updateWeeklySalesMode = () => {
        if (!salesChart) return;
        const mode = weeklySalesMode?.value || 'quantity';

        const dsQty     = findDs('qtyActual');
        const dsRev     = findDs('revActual');
        const dsProfit  = findDs('profitActual');
        const dsQtyF    = findDs('qtyForecast');
        const dsRevF    = findDs('revForecast');
        const dsProfitF = findDs('profitForecast');

        const setHidden = (ds, hidden) => { if (ds) ds.hidden = hidden; };

        const hasForecast =
          sumArray(qtyForecast)    > 0 ||
          sumArray(revForecast)    > 0 ||
          sumArray(profitForecast) > 0;

        if (mode === 'quantity') {
          setHidden(dsQty, false);
          setHidden(dsRev, false);
          setHidden(dsProfit, true);
          setHidden(dsQtyF, true);
          setHidden(dsRevF, true);
          setHidden(dsProfitF, true);
          salesChart.options.plugins.title.text = 'Weekly sales in units and revenue';
        } else if (mode === 'profit') {
          setHidden(dsQty, true);
          setHidden(dsRev, false);
          setHidden(dsProfit, false);
          setHidden(dsQtyF, true);
          setHidden(dsRevF, true);
          setHidden(dsProfitF, true);
          salesChart.options.plugins.title.text = 'Weekly revenue and profit';
        } else if (mode === 'forecast') {
          setHidden(dsQty, true);
          setHidden(dsRev, false);
          setHidden(dsProfit, true);

          setHidden(dsQtyF, !hasForecast);
          setHidden(dsRevF, !hasForecast);
          setHidden(dsProfitF, !hasForecast);

          salesChart.options.plugins.title.text = 'Weekly sales forecast for next week (AI)';
        }

        salesChart.update();
        buildWeeklySalesInsights(mode);
      };

      weeklySalesMode?.addEventListener('change', updateWeeklySalesMode);
      updateWeeklySalesMode();

      /* ---------- 📈 Sales Report Range + Sparkline wiring ---------- */
      const salesReportCard  = document.getElementById('salesReportCard');
      const salesRangeSelect = document.getElementById('salesRange');
      const salesRangeLabel  = document.getElementById('salesRangeLabel');
      const salesRangeEndpoint = salesReportCard?.dataset.salesRangeEndpoint || null;

      async function loadSalesRange(range) {
        if (!salesRangeEndpoint || !salesSparklineChart) return;

        try {
          const url = new URL(salesRangeEndpoint, window.location.origin);
          url.searchParams.set('range', range);

          const res = await fetch(url.toString(), {
            headers: { 'Accept': 'application/json' }
          });

          if (!res.ok) {
            console.error('Failed to load sales range', res.status);
            return;
          }

          const data = await res.json();

          // Update label under title
          if (data.label && salesRangeLabel) {
            salesRangeLabel.textContent =
              `Meat product sales in finished units (packs and bags) • ${data.label}`;
          }

          // Update stats in the card
          if (data.stats && salesReportCard) {
            const stats = data.stats;

            const totalRevenueEl = salesReportCard.querySelector('[data-sales-stat="total_revenue"]');
            const totalSalesEl   = salesReportCard.querySelector('[data-sales-stat="total_sales"]');
            const avgPriceEl     = salesReportCard.querySelector('[data-sales-stat="avg_price"]');
            const strongestEl    = salesReportCard.querySelector('[data-sales-stat="strongest_day"]');

            if (totalRevenueEl) totalRevenueEl.textContent = formatCurrency(stats.total_revenue || 0);
            if (totalSalesEl)   totalSalesEl.textContent   = formatInteger(stats.total_sales || 0);
            if (avgPriceEl)     avgPriceEl.textContent     = formatCurrency(stats.avg_price || 0);
            if (strongestEl)    strongestEl.textContent    = stats.biggest_day || 'No data yet';
          }

          // Update sparkline chart
          if (data.chart) {
            salesSparklineChart.data.labels = data.chart.labels || [];
            salesSparklineChart.data.datasets[0].data = data.chart.data || [];
            salesSparklineChart.update();
          }
        } catch (err) {
          console.error('Error loading sales range', err);
        }
      }

      // Hook change event
      if (salesRangeSelect) {
        salesRangeSelect.addEventListener('change', function () {
          loadSalesRange(this.value);
        });
      }

      return { productionChart, salesChart };
    }

    /* ============================================================
     * 3. MODEL INFO MODAL
     * ============================================================ */
    function initModelInfoModal() {
      const modelInfoModal = document.getElementById('modelInfoModal');
      const modelInfoTitle = document.getElementById('modelInfoTitle');

      const openModelInfo = (contextLabel) => {
        if (!modelInfoModal) return;
        if (modelInfoTitle && contextLabel) modelInfoTitle.textContent = contextLabel;
        modelInfoModal.classList.remove('hidden');
        modelInfoModal.classList.add('flex');
      };

      const closeModelInfo = () => {
        if (!modelInfoModal) return;
        modelInfoModal.classList.add('hidden');
        modelInfoModal.classList.remove('flex');
      };

      document.querySelectorAll('.model-info-trigger').forEach(btn => {
        btn.addEventListener('click', () => {
          const label = btn.getAttribute('data-context') || 'Model information';
          openModelInfo(label);
        });
      });

      modelInfoModal?.querySelectorAll('[data-close-model]').forEach(btn => {
        btn.addEventListener('click', closeModelInfo);
      });

      modelInfoModal?.addEventListener('click', (e) => {
        if (e.target === modelInfoModal || e.target.dataset.modalOverlay === 'true') {
          closeModelInfo();
        }
      });
    }

    /* ============================================================
     * 4. SIDEBAR
     * ============================================================ */
    function initSidebar() {
      const sidebar       = document.getElementById('sidebar');
      const sidebarToggle = document.getElementById('sidebarToggle');
      const sidebarClose  = document.getElementById('sidebarClose');

      sidebarToggle?.addEventListener('click', () => {
        sidebar?.classList.add('open');
      });

      sidebarClose?.addEventListener('click', () => {
        sidebar?.classList.remove('open');
      });
    }

    /* ============================================================
     * 5. RESERVATION MODAL (OPEN/CLOSE)
     * ============================================================ */
    function initReservationModal() {
      const modal = document.getElementById('reservationModal');
      if (!modal) return;

      const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
      };

      modal.querySelectorAll('[data-reservation-close="true"]').forEach(btn => {
        btn.addEventListener('click', closeModal);
      });

      modal.addEventListener('click', (e) => {
        if (e.target === modal) {
          closeModal();
        }
      });
    }

    /* ============================================================
     * 6. DEMAND CALENDAR (LIGHT THEME + RESERVATION HOOK)
     * ============================================================ */
    function initDemandCalendar() {
      const modal     = document.getElementById('demandCalendarModal');
      const openBtn   = document.getElementById('demandCalendarButton');
      const closeBtn  = document.getElementById('demandCalendarClose');
      const prevBtn   = document.getElementById('calendarPrevMonth');
      const nextBtn   = document.getElementById('calendarNextMonth');
      const monthLbl  = document.getElementById('calendarMonthLabel');
      const rangeLbl  = document.getElementById('calendarSelectedRangeLabel');
      const grid      = document.getElementById('calendarGrid');
      const resetBtn  = document.getElementById('demandCalendarReset');
      const applyBtn  = document.getElementById('demandCalendarApply');

      const dayTitle  = document.getElementById('calendarDayTitle');
      const dayBadges = document.getElementById('calendarDayBadges');
      const dayDemand = document.getElementById('calendarDayDemand');
      const dayInv    = document.getElementById('calendarDayInventory');
      const dayRes    = document.getElementById('calendarDayReserved');
      const dayNet    = document.getElementById('calendarDayNet');
      const dayNotes  = document.getElementById('calendarDayNotes');

      const dayProduct   = document.getElementById('calendarDayProduct');
      const dayUnitType  = document.getElementById('calendarDayUnitType');
      const dayNotesDet  = document.getElementById('calendarDayNotesDetails');

      const openReservationButton = document.getElementById('openReservationButton');

      if (!modal || !grid) return;

      // initial month from Blade
      let currentYear  = {{ $calendarYear }};
      let currentMonth = {{ $calendarMonth }}; // 1-12

      const parseIso = (iso) => {
        if (!iso) return null;
        const d = new Date(iso + 'T00:00:00');
        return isNaN(d.getTime()) ? null : d;
      };

      let selectedStart = parseIso(filterStart);
      let selectedEnd   = parseIso(filterEnd);
      let selectedDay   = null;

      const eventsMeta = calendarEvents || {};

      const formatDateLabel = (d) => {
        if (!d) return 'No range selected yet';
        return d.toLocaleDateString(undefined, {
          month:'short', day:'2-digit', year:'numeric'
        });
      };

      const formatFullDate = (d) => d.toLocaleDateString(undefined, {
        weekday:'long', month:'long', day:'2-digit', year:'numeric'
      });

      const dateKey = (d) => d.toISOString().slice(0,10);

      const updateRangeLabel = () => {
        if (!rangeLbl) return;
        if (selectedStart && selectedEnd) {
          rangeLbl.textContent = `${formatDateLabel(selectedStart)} to ${formatDateLabel(selectedEnd)}`;
        } else if (selectedStart) {
          rangeLbl.textContent = formatDateLabel(selectedStart);
        } else {
          rangeLbl.textContent = 'No range selected yet';
        }
      };

      const updateApplyState = () => {
        if (!applyBtn) return;
        const enabled = !!(selectedStart && selectedEnd);
        applyBtn.disabled = !enabled;
      };

      const updateDayDetails = (d) => {
        selectedDay = d;
        const key   = dateKey(d);
        const meta  = eventsMeta[key] || {};

        if (dayTitle) dayTitle.textContent = formatFullDate(d);

        // badges: demand level + extra labels
        if (dayBadges) {
          dayBadges.innerHTML = '';
          const badgeChips = [];

          const level = meta.level || meta.demand_level || 'normal';

          if (level === 'high' || level === 'forecast_high') {
            badgeChips.push({
              label: 'High demand day',
              class: 'bg-[var(--brand-yellow-40)] text-[var(--ink)] border border-[var(--brand-red-30)]'
            });
          } else if (level === 'forecast_medium') {
            badgeChips.push({
              label: 'Forecast medium demand day',
              class: 'bg-[#eff6ff] text-[#1d4ed8] border border-[#bfdbfe]'
            });
          } else if (level === 'low') {
            badgeChips.push({
              label: 'Low demand day',
              class: 'bg-[#ecfdf3] text-[#166534] border border-[#6ee7b7]'
            });
          } else {
            badgeChips.push({
              label: 'Normal demand',
              class: 'bg-[#eff6ff] text-[#1d4ed8] border border-[#bfdbfe]'
            });
          }

          if (Array.isArray(meta.badges)) {
            meta.badges.forEach(label => {
              badgeChips.push({
                label,
                class: 'bg-[#f9fafb] text-[#374151] border border-[#e5e7eb]'
              });
            });
          }

          if (meta.reservations && meta.reservations > 0) {
            badgeChips.push({
              label: 'Has reservations',
              class: 'bg-[#f0f9ff] text-[#0369a1] border border-[#7dd3fc]'
            });
          }

          badgeChips.forEach(b => {
            const span = document.createElement('span');
            span.className = `px-2 py-0.5 rounded-full ${b.class}`;
            span.textContent = b.label;
            dayBadges.appendChild(span);
          });
        }

        const demandUnits = meta.demand_units    != null ? `${meta.demand_units} unit(s)`    : '–';
        const invUnits    = meta.inventory_units != null ? `${meta.inventory_units} unit(s)` : '–';
        const resUnits    = meta.reservations    != null ? `${meta.reservations} unit(s)`    : '–';
        const netUnits    = meta.net_available   != null ? `${meta.net_available} unit(s)`   : '–';
        const notesText   = meta.notes || 'Use this day to plan production and staffing based on expected or forecast demand.';

        if (dayDemand) dayDemand.textContent = demandUnits;
        if (dayInv)    dayInv.textContent    = invUnits;
        if (dayRes)    dayRes.textContent    = resUnits;
        if (dayNet)    dayNet.textContent    = netUnits;
        if (dayNotes)  dayNotes.textContent  = notesText;

        // Product / unit type / summary in side panel
        if (dayProduct)  dayProduct.textContent  = meta.product_name        || '–';
        if (dayUnitType) dayUnitType.textContent = meta.unit_type           || '–';
        if (dayNotesDet) dayNotesDet.textContent = meta.reservation_summary || meta.notes || '–';
      };

      const isSameDay = (a, b) => (
        a && b &&
        a.getFullYear() === b.getFullYear() &&
        a.getMonth() === b.getMonth() &&
        a.getDate() === b.getDate()
      );

      const isBetween = (d, start, end) => {
        if (!start || !end) return false;
        const t  = d.getTime();
        const t1 = start.getTime();
        const t2 = end.getTime();
        return t >= Math.min(t1, t2) && t <= Math.max(t1, t2);
      };

      const renderMonth = () => {
        const firstOfMonth = new Date(currentYear, currentMonth - 1, 1);
        const daysInMonth  = new Date(currentYear, currentMonth, 0).getDate();
        const weekdayIndex = (firstOfMonth.getDay() + 6) % 7; // Monday=0

        if (monthLbl) {
          monthLbl.textContent = firstOfMonth.toLocaleDateString(undefined, {
            month:'long', year:'numeric'
          });
        }

        grid.innerHTML = '';

        // leading blanks
        for (let i = 0; i < weekdayIndex; i++) {
          const empty = document.createElement('div');
          grid.appendChild(empty);
        }

        for (let day = 1; day <= daysInMonth; day++) {
          const d = new Date(currentYear, currentMonth - 1, day);
          const key = dateKey(d);
          const meta = eventsMeta[key] || {};
          const level = meta.level || meta.demand_level || 'normal';

          const btn = document.createElement('button');
          btn.type  = 'button';
          btn.dataset.date = key;
          btn.className = 'flex flex-col items-center justify-center py-2 rounded-lg border text-xs cursor-pointer select-none transition bg-white border-[var(--line)] text-[var(--ink)] hover:bg-[var(--hover)]';

          let colorClass = 'bg-white border-[var(--line)]';
          if (level === 'high' || level === 'forecast_high') {
            colorClass = 'bg-[var(--brand-yellow-20)] border-[var(--brand-red-30)]';
          } else if (level === 'forecast_medium') {
            colorClass = 'bg-[#eff6ff] border-[#bfdbfe]';
          }
          btn.className += ' ' + colorClass;

          const inRange = selectedStart && selectedEnd && isBetween(d, selectedStart, selectedEnd);
          const isStart = selectedStart && isSameDay(d, selectedStart);
          const isEnd   = selectedEnd   && isSameDay(d, selectedEnd);

          if (inRange) {
            btn.className += ' ring-1 ring-blue-400/80 ring-offset-1 ring-offset-[var(--card)]';
          }
          if (isStart || isEnd) {
            btn.className += ' font-semibold bg-[var(--brand-yellow)] text-[#1f2937] border-[var(--brand-red)]';
          }

          const weekdayLabel = d.toLocaleDateString(undefined, { weekday:'short' });
          btn.innerHTML = `
            <span class="text-[11px] mb-0.5 text-[var(--muted)]">${weekdayLabel}</span>
            <span class="text-sm font-semibold">${day}</span>
          `;

          btn.addEventListener('click', () => {
            if (!selectedStart || (selectedStart && selectedEnd)) {
              selectedStart = d;
              selectedEnd   = null;
            } else {
              if (d.getTime() < selectedStart.getTime()) {
                selectedEnd   = selectedStart;
                selectedStart = d;
              } else {
                selectedEnd = d;
              }
            }
            updateRangeLabel();
            updateApplyState();
            updateDayDetails(d);
            renderMonth(); // refresh highlight
          });

          grid.appendChild(btn);
        }
      };

      const openModal = () => {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        renderMonth();
        updateRangeLabel();
        updateApplyState();
      };

      const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
      };

      openBtn?.addEventListener('click', openModal);
      closeBtn?.addEventListener('click', closeModal);

      modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
      });

      // Month navigation
      prevBtn?.addEventListener('click', () => {
        currentMonth--;
        if (currentMonth < 1) {
          currentMonth = 12;
          currentYear--;
        }
        renderMonth();
      });

      nextBtn?.addEventListener('click', () => {
        currentMonth++;
        if (currentMonth > 12) {
          currentMonth = 1;
          currentYear++;
        }
        renderMonth();
      });

      // Reset
      resetBtn?.addEventListener('click', () => {
        selectedStart = null;
        selectedEnd   = null;
        selectedDay   = null;
        updateRangeLabel();
        updateApplyState();
        if (dayTitle)    dayTitle.textContent    = 'Pick a day on the calendar';
        if (dayBadges)   dayBadges.innerHTML     = '';
        if (dayDemand)   dayDemand.textContent   = '–';
        if (dayInv)      dayInv.textContent      = '–';
        if (dayRes)      dayRes.textContent      = '–';
        if (dayNet)      dayNet.textContent      = '–';
        if (dayNotes)    dayNotes.textContent    = 'Use the calendar on the left to inspect a day. High or forecast demand days are ideal for planning extra production and staffing.';
        if (dayProduct)  dayProduct.textContent  = '–';
        if (dayUnitType) dayUnitType.textContent = '–';
        if (dayNotesDet) dayNotesDet.textContent = '–';
        renderMonth();
      });

      // Apply: reload with query params
      applyBtn?.addEventListener('click', () => {
        if (!selectedStart || !selectedEnd) return;
        const startStr = dateKey(selectedStart);
        const endStr   = dateKey(selectedEnd);

        const url   = new URL(window.location.href);
        url.searchParams.set('start', startStr);
        url.searchParams.set('end',   endStr);
        window.location.href = url.toString();
      });

      // "Add reservation for this day" → open reservation modal with prefilled date
      if (openReservationButton) {
        openReservationButton.addEventListener('click', () => {
          const reservationModal = document.getElementById('reservationModal');
          const dateInput = document.getElementById('reservation_date_input');
          const dateLabel = document.getElementById('reservation_date_label');

          // If no day selected yet, default to today
          if (!selectedDay) {
            selectedDay = new Date();
            updateDayDetails(selectedDay);
          }

          const key = dateKey(selectedDay);

          if (dateInput) {
            dateInput.value = key;
          }
          if (dateLabel) {
            dateLabel.value = selectedDay.toLocaleDateString(undefined, {
              weekday:'long',
              month:'long',
              day:'2-digit',
              year:'numeric'
            });
          }

          if (reservationModal) {
            reservationModal.classList.remove('hidden');
            reservationModal.classList.add('flex');
          }
        });
      }

      // Auto-open day details if filterStart exists
      if (selectedStart) {
        updateDayDetails(selectedStart);
      }
    }

    /* ============================================================
     * 7. 3D TOGGLE (visual only)
     * ============================================================ */
    function init3DControls() {
      const toggle3D   = document.getElementById('toggle3D');
      const depthRange = document.getElementById('depthRange');
      const liftRange  = document.getElementById('liftRange');
      const depthVal   = document.getElementById('depthVal');
      const liftVal    = document.getElementById('liftVal');
      const dot3d      = document.getElementById('dot3d');

      const update = () => {
        if (depthVal && depthRange) depthVal.textContent = depthRange.value;
        if (liftVal && liftRange)   liftVal.textContent  = liftRange.value;
        if (dot3d && toggle3D) {
          dot3d.style.background = toggle3D.checked ? 'var(--green)' : 'var(--brand-red)';
        }
      };

      depthRange?.addEventListener('input', debounce(update));
      liftRange?.addEventListener('input', debounce(update));
      toggle3D?.addEventListener('input', debounce(update));
      update();
    }

    /* ============================================================
     * 8. RESERVATION TYPE PICKER (product → variants)
     * ============================================================ */
    function initReservationTypePicker() {
      const modal = document.getElementById('reservationModal');
      if (!modal) return;

      const productSelect = modal.querySelector('select[name="product_id"]');
      const typeSelect    = document.getElementById('reservation_type_select');
      if (!productSelect || !typeSelect) return;

      const defaultLabel = 'Select type or variant (optional)';

      const resetTypeOptions = () => {
        typeSelect.innerHTML = '';
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = defaultLabel;
        typeSelect.appendChild(opt);
      };

      resetTypeOptions();

      const loadTypes = (productId) => {
        if (!typesApiUrl || !productId) {
          resetTypeOptions();
          typeSelect.disabled = !productId;
          return;
        }

        typeSelect.disabled = true;

        fetch(typesApiUrl + '?product_id=' + encodeURIComponent(productId))
          .then(res => res.ok ? res.json() : Promise.reject())
          .then(data => {
            resetTypeOptions();
            const list = (data && Array.isArray(data.list)) ? data.list : [];

            list.forEach(label => {
              const opt = document.createElement('option');
              opt.value = label;
              opt.textContent = label;
              typeSelect.appendChild(opt);
            });

            typeSelect.disabled = false;
          })
          .catch(() => {
            // fail gracefully
            resetTypeOptions();
            typeSelect.disabled = false;
          });
      };

      productSelect.addEventListener('change', (e) => {
        const productId = e.target.value;
        loadTypes(productId);
      });

      // If a product might be preselected
      if (productSelect.value) {
        loadTypes(productSelect.value);
      }
    }

    /* ============================================================
     * 9. BOOT EVERYTHING
     * ============================================================ */
    initCharts();
    initModelInfoModal();
    initSidebar();
    initReservationModal();
    initDemandCalendar();
    init3DControls();
    initReservationTypePicker();
  });
</script>

</body>
</html>
