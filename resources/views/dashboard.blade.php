<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>GenRev Admin Dashboard</title>

  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Kalam:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inria+Sans:wght@300;400;700&display=swap" rel="stylesheet">

  <!-- Modern Liquid/Glass UI Theme -->
  <style>
    :root{
      --navbar: linear-gradient(135deg,#1F1E1E 0%,#100E00 80%);
      --sidebar: rgba(31,30,30,.55);
      --sidebar-hover: rgba(4,119,5,.24);
      --sidebar-active:#EDD100;
      --dark-line: rgba(255,255,255,.18);
      --text:#F6F9F6;
      --muted:#A3B4A7;
      --brand-green:#047705;
      --brand-green-20: rgba(4,119,5,.20);
    }
    body{
      font-family:'Inria Sans',system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
      color:var(--text);
      background:linear-gradient(135deg,#1F1E1E 0%,#001C00 100%) fixed!important;
      min-height:100vh;overflow-x:hidden;
    }
    body::before{
      content:'';position:fixed;inset:-50% -50%;width:200%;height:200%;
      background:linear-gradient(to bottom right,rgba(18,108,7,.15) 0%,rgba(113,200,98,.15) 25%,rgba(210,220,50,.12) 50%,rgba(113,200,98,.15) 75%,rgba(10,56,14,.15) 100%);
      transform:rotate(30deg);animation:liquidFlow 15s linear infinite;z-index:-1;opacity:.5;
    }
    @keyframes liquidFlow{0%{transform:rotate(30deg) translate(-10%,-10%)}50%{transform:rotate(30deg) translate(10%,10%)}100%{transform:rotate(30deg) translate(-10%,-10%)}}
    .glass{background:var(--sidebar)!important;backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);border:1px solid var(--dark-line)!important;box-shadow:0 10px 24px rgba(0,0,0,.35)!important;color:var(--text)}
    .bg-navbar{background:var(--navbar)!important;border-bottom:.5px solid var(--dark-line);box-shadow:0 8px 26px rgba(0,0,0,.45),0 0 0 1px rgba(255,255,255,.06) inset}
    .bg-navbar:hover{box-shadow:0 8px 28px rgba(0,123,0,.35),0 0 0 1px rgba(4,119,5,.2) inset}
    .brand-title{font-family:'Kalam',cursive;letter-spacing:.02em;text-shadow:-2px 1px 0 var(--brand-green)}
    aside{border-right:.5px solid var(--dark-line)}
    aside .p-6.text-2xl{font-family:'Kalam',cursive;color:#fff;text-shadow:-2px 1px 0 var(--brand-green)}
    .bg-sidebar{background-color:var(--sidebar)}
    .bg-sidebar-hover:hover{background-color:var(--sidebar-hover);transition:background-color .25s ease}
    .bg-sidebar-active{background:var(--sidebar-active)!important;color:#1F1E1E!important;font-weight:700;position:relative;box-shadow:0 6px 18px rgba(237,209,0,.25)}
    .bg-sidebar-active::before{content:'';position:absolute;left:0;top:0;width:4px;height:100%;background:#91EAAF;border-radius:0 4px 4px 0}
    aside nav a{position:relative;overflow:hidden;color:#E7F3E9;border-radius:9999px 0 0 9999px}
    aside nav a::before{content:'';position:absolute;bottom:8px;left:50%;transform:translateX(-50%);width:0;height:2px;background:linear-gradient(90deg,transparent,#EDD100,transparent);transition:width .35s ease}
    aside nav a:hover::before{width:80%}
    aside nav a::after{content:'';position:absolute;top:50%;left:50%;width:6px;height:6px;background:rgba(237,209,0,.35);opacity:0;border-radius:9999px;transform:translate(-50%,-50%) scale(1)}
    aside nav a:hover::after{animation:ripple 1s ease-out}
    @keyframes ripple{0%{transform:translate(-50%,-50%) scale(0);opacity:.45}100%{transform:translate(-50%,-50%) scale(18);opacity:0}}
    .border-dark-line{border-color:var(--dark-line)}
    .text-muted{color:var(--muted)}
    @media (max-width:1024px){aside{position:fixed;z-index:50;transform:translateX(-100%);transition:transform .3s ease}aside.open{transform:translateX(0)}body{padding-left:0}}
    .section-liquid-shine{position:relative}
    .section-liquid-shine::after{content:'';position:absolute;inset:0;background:linear-gradient(45deg,rgba(4,119,5,.10) 0%,rgba(237,209,0,.10) 50%,rgba(4,119,5,.10) 100%);border-radius:inherit;animation:cardShine 8s ease infinite;pointer-events:none}
    @keyframes cardShine{0%{opacity:.3}50%{opacity:.1}100%{opacity:.3}}
  </style>
</head>
<body>
  <div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <aside id="sidebar" class="w-64 glass flex-shrink-0 flex flex-col">
      <div class="p-6 text-2xl font-bold tracking-wide border-b border-dark-line flex justify-between items-center">
        GenRev
        <button id="sidebarClose" class="lg:hidden text-xl font-bold">&times;</button>
      </div>

      <!-- User Info -->
      <div class="px-6 pt-4 pb-2">
        <div class="flex items-center space-x-3">
          <div class="w-10 h-10 bg-[var(--sidebar-active)] rounded-full flex items-center justify-center font-bold text-[#1F1E1E] shadow-[0_0_0_2px_rgba(4,119,5,.35),0_6px_16px_rgba(0,0,0,.35)]">
            {{ Auth::check() ? strtoupper(substr(Auth::user()->name, 0, 1)) : '?' }}
          </div>
          <div class="text-sm">
            <p class="font-semibold">{{ Auth::check() ? Auth::user()->name : 'Guest' }}</p>
            <p class="text-xs text-muted capitalize">{{ Auth::check() && Auth::user()->role ? Auth::user()->role : 'Admin' }}</p>
          </div>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 mt-4 space-y-1 text-sm font-medium">
        @php
          $routes = [
            'dashboard'  => 'Dashboard',
            'production' => 'Production',
            'sales'      => 'Sales',
            'inventory'  => 'Inventory',
            'materials'  => 'Materials',
            'employee'   => 'Employee',
            'settings'   => 'Settings',
          ];
        @endphp
        @foreach($routes as $route => $label)
          <a href="{{ route($route) }}"
             class="block px-6 py-3 rounded-r-full transition-all duration-150 hover:bg-sidebar-hover {{ request()->routeIs($route . '*') ? 'bg-sidebar-active' : '' }}">
            {{ $label }}
          </a>
        @endforeach
      </nav>

      <div class="p-6 text-xs text-muted border-t border-dark-line">© 2025 GenRev</div>
    </aside>

    <!-- Main Content -->
    <div class="flex flex-col flex-1 overflow-hidden">
      <header class="bg-navbar text-white px-6 py-4 flex justify-between items-center shadow-md">
        <div class="flex items-center space-x-4">
          <button id="sidebarToggle" class="lg:hidden text-2xl">&#9776;</button>
          <h1 class="text-xl font-bold tracking-wide brand-title">Dashboard Overview</h1>
        </div>

        <!-- User Menu -->
        <div class="relative z-50">
          <button id="userMenuButton" class="focus:outline-none">
            <div class="w-9 h-9 rounded-full bg-[#91EAAF] flex items-center justify-center font-bold uppercase text-sm text-[#1F1E1E] shadow-[0_0_0_2px_rgba(4,119,5,.35),0_6px_16px_rgba(0,0,0,.35)]">
              {{ Auth::check() ? strtoupper(substr(Auth::user()->name, 0, 1)) : '?' }}
            </div>
          </button>
          <div id="userDropdown" class="hidden absolute right-0 mt-2 w-52 glass border border-dark-line rounded-lg shadow-lg overflow-hidden">
            <div class="px-4 py-3 border-b border-dark-line text-sm">
              Logged in as<br>
              <span class="font-semibold">{{ Auth::check() ? Auth::user()->name : 'Guest' }}</span>
              @if(Auth::check() && Auth::user()->role)
                <div class="text-xs text-muted capitalize">({{ Auth::user()->role }})</div>
              @endif
            </div>
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button type="submit" class="w-full text-left px-4 py-2 text-sm hover:bg-[var(--brand-green-20)] transition-colors">
                Logout
              </button>
            </form>
          </div>
        </div>
      </header>

      <!-- DASHBOARD CONTENT -->
      <main class="flex-1 overflow-y-auto p-8">
        <div class="h-full grid grid-cols-1 xl:grid-cols-2 gap-6">

          {{-- 🧮 Metrics Cards --}}
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @php
              $metrics = [
                ['label' => 'Total Products',        'value' => $totalProducts,                          'note' => 'Based on weekly production', 'icon' => '📦'],
                ['label' => 'Total Materials (kg)',  'value' => number_format($totalMaterialsWeight, 2), 'note' => 'Weekly materials',           'icon' => '⚖️'],
                ['label' => 'Total Revenue',         'value' => '₱' . number_format($totalRevenue, 2),   'note' => 'Weekly product sales',      'icon' => '💰'],
                ['label' => 'Sales Transactions',    'value' => $totalSales,                             'note' => 'Weekly transactions',        'icon' => '📈'],
              ];
            @endphp

            @foreach ($metrics as $metric)
              <div class="glass section-liquid-shine p-5 rounded-2xl shadow-md hover:shadow-xl transition">
                <div class="flex items-center space-x-4">
                  <div class="w-10 h-10 rounded-full bg-[var(--sidebar-active)] flex items-center justify-center text-xl text-[#1F1E1E]">
                    {{ $metric['icon'] }}
                  </div>
                  <div>
                    <p class="text-xs uppercase font-semibold tracking-wide opacity-90">{{ $metric['label'] }}</p>
                    <h3 class="text-2xl font-bold">{{ $metric['value'] }}</h3>
                    <p class="text-xs text-[var(--muted)]">{{ $metric['note'] }}</p>
                  </div>
                </div>
              </div>
            @endforeach
          </div>

          {{-- 📊 Sales Report Widget (with sparkline) --}}
          <div class="glass section-liquid-shine border border-dark-line shadow-md p-5 rounded-2xl backdrop-blur-lg">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h2 class="text-lg font-semibold mb-1">📈 Sales Report</h2>
                <p class="text-xs text-[var(--muted)]">Real-time sales analytics</p>
              </div>
              <div class="flex space-x-2">
                <select id="salesRange" class="text-xs px-2 py-1 rounded bg-white/10 border border-white/20 text-white focus:outline-none focus:ring-1 focus:ring-emerald-400">
                  <option value="today">Today</option>
                  <option value="week" selected>This Week</option>
                  <option value="month">This Month</option>
                  <option value="7days">Last 7 Days</option>
                  <option value="30days">Last 30 Days</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
              @php
                $avgPrice = $totalSales > 0 ? ($totalRevenue / max($totalSales,1)) : 0;
                $salesStats = [
                  ['label' => 'Total Revenue', 'value' => '₱' . number_format($totalRevenue, 2), 'icon' => '💰', 'color' => 'text-emerald-300'],
                  ['label' => 'Units Sold',    'value' => number_format($totalSales, 0),         'icon' => '📦', 'color' => 'text-blue-300'],
                  ['label' => 'Avg Price/Unit','value' => '₱' . number_format($avgPrice, 2),     'icon' => '📊', 'color' => 'text-purple-300'],
                  ['label' => 'Biggest Day',   'value' => 'N/A',                                 'icon' => '🔥', 'color' => 'text-orange-300'],
                ];
              @endphp
              @foreach($salesStats as $stat)
                <div class="text-center">
                  <div class="text-2xl mb-1">{{ $stat['icon'] }}</div>
                  <div class="text-xs text-[var(--muted)] mb-1">{{ $stat['label'] }}</div>
                  <div class="text-sm font-semibold {{ $stat['color'] }}">{{ $stat['value'] }}</div>
                </div>
              @endforeach
            </div>

            <div class="h-32 relative">
              <canvas id="salesTrendsChart" aria-label="Sales Trend"></canvas>
            </div>
          </div>

          {{-- 🏆 Most Sold Products --}}
          <div class="glass section-liquid-shine border border-dark-line shadow-md p-5 rounded-2xl backdrop-blur-lg">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h2 class="text-lg font-semibold mb-1">🏆 Most Sold Products</h2>
                <p class="text-xs text-[var(--muted)]">Top 5 products by revenue</p>
              </div>
              <a href="{{ route('sales') }}" class="text-xs px-3 py-1 rounded-full bg-[var(--sidebar-active)] text-[#1F1E1E] hover:opacity-90 transition">View all</a>
            </div>

            @if(($topProducts ?? collect())->isEmpty())
              <div class="text-center py-8">
                <div class="text-4xl mb-2">📊</div>
                <div class="text-sm text-[var(--muted)]">No sales data available</div>
                <div class="text-xs text-[var(--muted)] mt-1">Start recording sales to see top products</div>
              </div>
            @else
              <div class="space-y-3">
                @foreach($topProducts as $index => $product)
                  <div class="flex items-center space-x-3 p-3 rounded-lg bg-white/5 hover:bg-white/10 transition">
                    <div class="w-8 h-8 rounded-full bg-[var(--sidebar-active)] flex items-center justify-center text-sm font-bold text-[#1F1E1E]">
                      {{ $index + 1 }}
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center justify-between mb-1">
                        <div class="font-medium truncate">{{ $product->product_name ?? 'Product' }}</div>
                        <div class="text-sm font-semibold text-emerald-300">₱{{ number_format($product->revenue ?? 0, 2) }}</div>
                      </div>
                      <div class="flex items-center justify-between text-xs text-[var(--muted)]">
                        <span>{{ number_format($product->quantity ?? 0, 2) }} sold</span>
                        <span>{{ number_format($product->revenue_share ?? 0, 1) }}% of total</span>
                      </div>
                      <div class="w-full bg-white/10 rounded-full h-1.5 mt-2">
                        <div class="bg-emerald-400 h-1.5 rounded-full" style="width: {{ min(($product->revenue_share ?? 0), 100) }}%"></div>
                      </div>
                    </div>
                  </div>
                @endforeach
              </div>
            @endif
          </div>

          {{-- 🧾 Recent Sales Table --}}
          <div class="glass section-liquid-shine border border-dark-line shadow-md p-5 rounded-2xl backdrop-blur-lg overflow-auto">
            <div class="flex items-center justify-between">
              <div>
                <h2 class="text-base font-semibold mb-1">Recent Sales</h2>
                <p class="text-xs text-[var(--muted)] mb-3">Latest from <strong>weekly product sales</strong></p>
              </div>
              <a href="{{ route('sales') }}" class="text-xs px-3 py-1 rounded-full bg-[var(--sidebar-active)] text-[#1F1E1E] hover:opacity-90 transition">View all</a>
            </div>

            <table class="w-full text-sm text-left border-collapse">
              <thead class="uppercase border-b border-dark-line bg-opacity-20">
                <tr>
                  <th class="py-2 px-3">Product</th>
                  <th class="py-2 px-3">Qty</th>
                  <th class="py-2 px-3">Price</th>
                  <th class="py-2 px-3">Date</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($recentSales as $sale)
                  <tr class="border-t border-dark-line hover:bg-[rgba(255,255,255,.06)] transition">
                    <td class="py-2 px-3">{{ $sale->product_name }}</td>
                    <td class="py-2 px-3">{{ $sale->quantity }}</td>
                    <td class="py-2 px-3">₱{{ number_format($sale->price, 2) }}</td>
                    <td class="py-2 px-3">{{ \Carbon\Carbon::parse($sale->date)->format('M d, Y') }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="4" class="py-3 text-center text-muted">No sales found.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          {{-- 📦 Materials Snapshot (recently added this week) --}}
          <div class="glass section-liquid-shine border border-dark-line shadow-md p-5 rounded-2xl backdrop-blur-lg">
            <div class="flex items-center justify-between mb-2">
              <h2 class="text-base font-semibold">Materials Logged (This Week)</h2>
              <span class="text-xs text-[var(--muted)]">On hand: {{ number_format($totalMaterialsWeight, 2) }} kg</span>
            </div>
            @php $recentMaterials = $recentMaterials ?? collect(); @endphp
            @if($recentMaterials->isEmpty())
              <div class="text-sm text-[var(--muted)]">No materials logged this week.</div>
            @else
              <table class="w-full text-sm text-left border-collapse">
                <thead class="uppercase border-b border-dark-line bg-opacity-20">
                  <tr>
                    <th class="py-2 px-3">Material</th>
                    <th class="py-2 px-3">Qty (kg)</th>
                    <th class="py-2 px-3">Date</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($recentMaterials as $m)
                    <tr class="border-t border-dark-line hover:bg-[rgba(255,255,255,.06)] transition">
                      <td class="py-2 px-3">{{ $m->name ?? $m->material_name ?? 'Material' }}</td>
                      <td class="py-2 px-3">{{ number_format($m->quantity_kg, 2) }}</td>
                      <td class="py-2 px-3">{{ \Carbon\Carbon::parse($m->created_at)->format('M d, Y') }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            @endif
          </div>

          {{-- 📈 Expiration Trend (NEW) --}}
          <div class="glass section-liquid-shine border border-dark-line shadow-md p-5 rounded-2xl backdrop-blur-lg">
            <div class="flex items-center justify-between mb-2">
              <h2 class="text-base font-semibold">Expiration Trend</h2>
              <span class="text-xs text-[var(--muted)]">Upcoming expirations</span>
            </div>
            <div class="h-56 relative">
              <canvas id="expiryChart" aria-label="Expiration Trend"></canvas>
              <div id="expEmpty" class="absolute inset-0 hidden items-center justify-center text-sm text-[var(--muted)]">No expiries this week</div>
            </div>
          </div>

          {{-- 📊 Weekly Production Chart --}}
          <div class="glass section-liquid-shine border border-dark-line shadow-md p-5 rounded-2xl backdrop-blur-lg">
            <div class="flex items-center justify-between mb-2">
              <h2 class="text-base font-semibold">Weekly Production</h2>
              <span class="text-xs text-[var(--muted)]">Live report</span>
            </div>
            <div class="h-56 relative">
              <canvas id="productionChart" aria-label="Weekly Production"></canvas>
              <div id="prodEmpty" class="absolute inset-0 hidden items-center justify-center text-sm text-[var(--muted)]">No data for this week</div>
            </div>
          </div>

          {{-- 💸 Weekly Sales Chart --}}
          <div class="glass section-liquid-shine border border-dark-line shadow-md p-5 rounded-2xl backdrop-blur-lg">
            <div class="flex items-center justify-between mb-2">
              <h2 class="text-base font-semibold">Weekly Sales</h2>
              <span class="text-xs text-[var(--muted)]">Live report</span>
            </div>
            <div class="h-56 relative">
              <canvas id="salesChart" aria-label="Weekly Sales"></canvas>
              <div id="salesEmpty" class="absolute inset-0 hidden items-center justify-center text-sm text-[var(--muted)]">No data for this week</div>
            </div>
          </div>

        </div>
      </main>
    </div>
  </div>

  {{-- 📦 Materials Used (This Week) – from production × recipe --}}
  <div class="mx-8 my-6 glass section-liquid-shine border border-dark-line shadow-md p-5 rounded-2xl backdrop-blur-lg">
    <div class="flex items-center justify-between mb-2">
      <div>
        <h2 class="text-base font-semibold">Materials Used (This Week)</h2>
        <p class="text-xs text-[var(--muted)]">Based on production × recipe</p>
      </div>
      <div class="text-right text-xs text-[var(--muted)]">
        <div>Total Qty: {{ number_format($materialsUsageTotals['qty'] ?? 0, 3) }}</div>
        <div>Total Cost: ₱{{ number_format($materialsUsageTotals['cost'] ?? 0, 2) }}</div>
      </div>
    </div>

    @php $rows = $materialsUsage ?? collect(); @endphp
    @if($rows->isEmpty())
      <div class="text-sm text-[var(--muted)]">No materials consumed this week.</div>
    @else
      <div class="overflow-x-auto">
        <table class="w-full text-sm text-left border-collapse">
          <thead class="uppercase border-b border-dark-line bg-opacity-20">
            <tr>
              <th class="py-2 px-3">Material</th>
              <th class="py-2 px-3 text-right">Qty Used</th>
              <th class="py-2 px-3 text-right">Cost</th>
            </tr>
          </thead>
          <tbody>
            @foreach($rows as $r)
              <tr class="border-t border-dark-line hover:bg-[rgba(255,255,255,.06)] transition">
                <td class="py-2 px-3">{{ $r->material_name }}</td>
                <td class="py-2 px-3 text-right">{{ number_format($r->qty_used, 3) }} {{ $r->unit ?? 'kg' }}</td>
                <td class="py-2 px-3 text-right">₱{{ number_format($r->cost_used, 2) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1"></script>
  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const userMenuBtn   = document.getElementById('userMenuButton');
      const userDropdown  = document.getElementById('userDropdown');
      const sidebar       = document.getElementById('sidebar');
      const sidebarToggle = document.getElementById('sidebarToggle');
      const sidebarClose  = document.getElementById('sidebarClose');

      // User dropdown
      userMenuBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        userDropdown?.classList.toggle('hidden');
      });
      document.addEventListener('click', (e) => {
        if (!userMenuBtn?.contains(e.target) && !userDropdown?.contains(e.target)) {
          userDropdown?.classList.add('hidden');
        }
      });

      // Sidebar toggle (mobile)
      sidebarToggle?.addEventListener('click', () => sidebar?.classList.add('open'));
      sidebarClose?.addEventListener('click', () => sidebar?.classList.remove('open'));

      // ===== Charts (dark theme tuned) =====
      const labels = @json($labels ?? []);
      const prod   = @json($weeklyProductionSeries ?? []);
      const qty    = @json($weeklySalesQtySeries ?? []);
      const rev    = @json($weeklySalesRevenueSeries ?? []);
      const exp    = @json($weeklyExpirySeries ?? []);

      const grid  = 'rgba(255,255,255,0.12)';
      const tick  = '#E8F5E9';
      const title = '#F6F9F6';

      const barFill = 'rgba(67,160,71,0.35)';
      const barLine = 'rgba(67,160,71,1)';
      const lineClr = 'rgba(145,234,175,1)';
      const yellow  = 'rgba(237,209,0,1)';

      const showIfEmpty = (arr, elId) => {
        const el = document.getElementById(elId);
        if (!el) return;
        if (!arr || arr.length === 0 || arr.every(v => Number(v) === 0)) el.classList.remove('hidden');
        else el.classList.add('hidden');
      };

      // Production Chart
      showIfEmpty(prod, 'prodEmpty');
      const prodCtx = document.getElementById('productionChart');
      if (prodCtx) {
        new Chart(prodCtx, {
          type: 'bar',
          data: { labels, datasets: [{ label: 'Units Produced', data: prod, backgroundColor: barFill, borderColor: barLine, borderWidth: 2 }] },
          options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { labels: { color: tick } }, title: { display: true, text: 'Weekly Production', color: title } },
            scales: { x: { ticks: { color: tick }, grid: { color: grid } }, y: { beginAtZero: true, ticks: { color: tick }, grid: { color: grid } } }
          }
        });
      }

      // Weekly Sales Chart (Qty + Revenue)
      showIfEmpty([...(qty||[]), ...(rev||[])], 'salesEmpty');
      const salesCtx = document.getElementById('salesChart');
      if (salesCtx) {
        new Chart(salesCtx, {
          data: {
            labels,
            datasets: [
              { type: 'bar',  label: 'Qty Sold', data: qty, backgroundColor: barFill, borderColor: barLine, borderWidth: 2, yAxisID: 'y'  },
              { type: 'line', label: 'Revenue',   data: rev, borderColor: lineClr, borderWidth: 3, tension: 0.3, pointRadius: 3, yAxisID: 'y1' }
            ]
          },
          options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
              legend: { labels: { color: tick } },
              title:  { display: true, text: 'Weekly Sales', color: title },
              tooltip:{ callbacks:{ label: (ctx) => ctx.dataset.type === 'line'
                ? `Revenue: ₱${Number(ctx.parsed.y).toLocaleString(undefined,{ minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
                : `Qty: ${ctx.parsed.y}` } }
            },
            scales: {
              x:  { ticks: { color: tick }, grid: { color: grid } },
              y:  { position: 'left',  beginAtZero: true, ticks: { color: tick }, grid: { color: grid } },
              y1: { position: 'right', beginAtZero: true, ticks: { color: tick }, grid: { drawOnChartArea: false } }
            }
          }
        });
      }

      // Sales Report Sparkline (uses weekly revenue)
      const tinyCtx = document.getElementById('salesTrendsChart');
      if (tinyCtx) {
        new Chart(tinyCtx, {
          type: 'line',
          data: { labels, datasets: [{ label: 'Revenue', data: rev, borderColor: lineClr, borderWidth: 2, tension: .35, pointRadius: 0, fill: false }] },
          options: {
            responsive:true, maintainAspectRatio:false,
            plugins:{ legend:{ display:false } },
            scales:{ x:{ display:false }, y:{ display:false, beginAtZero:true } },
            elements:{ line:{ capBezierPoints:true } }
          }
        });
      }

      // Expiration Trend (yellow line)
      showIfEmpty(exp, 'expEmpty');
      const expCtx = document.getElementById('expiryChart');
      if (expCtx) {
        new Chart(expCtx, {
          type: 'line',
          data: {
            labels,
            datasets: [{
              label: 'Expiring Items',
              data: exp,
              borderColor: yellow,
              backgroundColor: 'rgba(237,209,0,0.15)',
              borderWidth: 3,
              tension: 0.35,
              pointRadius: 3,
              fill: false
            }]
          },
          options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { labels: { color: tick } }, title: { display: true, text: 'Expirations This Week', color: title } },
            scales: { x: { ticks: { color: tick }, grid: { color: grid } }, y: { beginAtZero: true, ticks: { color: tick }, grid: { color: grid } } }
          }
        });
      }
    });
  </script>
</body>
</html>
