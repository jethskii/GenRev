<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="color-scheme" content="light" />
  <meta name="theme-color" content="#E11D48" />
  <title>GenRev Admin Dashboard · Neon</title>

  <!-- Tailwind CDN (pinned major) -->
  <script src="https://cdn.tailwindcss.com"></script>

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
    body{ background:var(--page); color:var(--ink); font-family:'Inria Sans',system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif; min-height:100vh; overflow-x:hidden; }

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

    .btn{ display:inline-flex; align-items:center; justify-content:center; gap:.5rem; padding:.65rem 1rem; border-radius:12px; border:1px solid transparent; font-weight:700; }
    .btn-primary{ background:var(--brand-red); color:#fff; border-color:var(--brand-red); }
    .btn-primary:hover{ filter:brightness(.97); }
    .btn-ghost{ background:#fff; border:1px solid var(--line); color:var(--ink); }
    .btn-ghost:hover{ background:var(--hover); }
    .btn-green{ background:var(--green); color:#fff; border-color:var(--green); }
    .btn-blue{ background:var(--blue); color:#fff; border-color:var(--blue); }

    .input{ width:100%; padding:.65rem .9rem; border-radius:12px; background:#fff; border:1px solid var(--line); color:var(--ink); transition:border-color .15s, box-shadow .15s, transform .12s; }
    .input::placeholder{ color:#9ca3af; }
    .input:hover{ border-color:#e2e8f0; }
    .input:focus{ outline:0; border-color:#93c5fd; box-shadow:0 0 0 2px rgba(37,99,235,.18); transform:translateY(-1px); }

    .chip{ display:inline-flex; align-items:center; gap:.4rem; padding:.32rem .6rem; border-radius:999px; font-size:.72rem; font-weight:700; background:var(--chip); border:1px solid var(--line); color:var(--ink); }

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
    :where(a,button,[role="menuitem"],.side-link,.btn):focus{ outline:2px solid var(--brand-yellow); outline-offset:2px; }

    /* Neon status dot */
    #dot3d{ width:8px; height:8px; border-radius:999px; background:var(--brand-red); box-shadow:0 0 0 2px rgba(177,18,26,.18), 0 0 12px var(--brand-red-80); }

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
    .sr-only{ position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,1px,1px); white-space:nowrap; border:0; }
  </style>
</head>
<body>
  <div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar w-64 flex-shrink-0 flex flex-col" aria-label="Primary" tabindex="-1">
      <div class="p-6 border-b border-[var(--line)] flex justify-between items-center">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3" aria-label="GenRev Home">
          <div class="h-10 w-10 rounded-2xl bg-white border border-gray-200 shadow-sm overflow-hidden flex items-center justify-center">
            <img src="{{ asset('images/GENREV_FINAL.png') }}" alt="GenRev" loading="lazy" decoding="async" class="h-full w-full object-contain" onerror="this.closest('div').innerHTML='<span class=&quot;sr-only&quot;>GenRev</span>';">
          </div>
          <span class="text-2xl font-bold tracking-wide brand-title">GenRev</span>
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

      <!-- Nav (role-aware) -->
      <nav class="flex-1 mt-4 space-y-1 text-sm font-medium" role="navigation" aria-label="Primary navigation">
        @php
          $modules = [];
          if (Auth::check() && method_exists(Auth::user(), 'allowedModules')) {
              $modules = (array) (Auth::user()->allowedModules() ?? []);
          }
          if (empty($modules)) {
            $role = \Illuminate\Support\Str::lower((string) (Auth::user()->role ?? ''));
            $fallback = [
              'masters admin'      => ['dashboard','materials','production','sales','inventory','products','reports','settings','employee'],
              'production manager' => ['dashboard','production','products','settings'],
              'sales'              => ['dashboard','sales','settings'],
              'inventory'          => ['dashboard','inventory','materials','settings'],
            ];
            $modules = $fallback[$role] ?? ['dashboard','settings'];
          }
          $menu = [
            'dashboard'  => ['label'=>'Dashboard',  'route'=>'dashboard',           'active'=>['dashboard*']],
            'production' => ['label'=>'Production', 'route'=>'production.index',    'active'=>['production.*']],
            'sales'      => ['label'=>'Sales',      'route'=>'sales',               'active'=>['sales*','sales.*']],
            'inventory'  => ['label'=>'Inventory',  'route'=>'inventory',           'active'=>['inventory*','inventory.*']],
            'materials'  => ['label'=>'Materials',  'route'=>'materials',           'active'=>['materials*','materials.*','products.materials.*']],
            'products'   => ['label'=>'Products',   'route'=>'products.index',      'active'=>['products*','products.*']],
            'reports'    => ['label'=>'Reports',    'route'=>'reports.index',       'active'=>['reports*','reports.*']],
            'employee'   => ['label'=>'Employee',   'route'=>'employees.index',     'active'=>['employees*','employees.*']],
            'settings'   => ['label'=>'Settings',   'route'=>'settings.index',      'active'=>['settings*','settings.*']],
          ];
          $isActive = fn(array $patterns) => collect($patterns)->some(fn($p) => request()->routeIs($p));
        @endphp

        @foreach ($modules as $key)
          @php
            if (!isset($menu[$key])) continue;
            $item = $menu[$key];
            if (!\Illuminate\Support\Facades\Route::has($item['route'])) continue;
            $active = $isActive($item['active']) ? 'side-link--active' : '';
            $aria   = $active ? 'aria-current=page' : '';
          @endphp
          <a href="{{ route($item['route']) }}" class="side-link {{ $active }}" {!! $aria !!}>{{ $item['label'] }}</a>

          @if($key === 'dashboard')
            <div class="mx-6 my-2 border-t" style="border-color:var(--line)"></div>
          @endif
        @endforeach
      </nav>

      <div class="p-6 text-xs muted border-t border-[var(--line)]">© {{ now()->year }} GenRev</div>
    </aside>

    <!-- Main -->
    <div class="flex flex-col flex-1 overflow-hidden">
      <!-- Top Nav -->
      <header class="nav-surface px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-4">
          <button id="sidebarToggle" class="lg:hidden text-2xl" aria-label="Open sidebar" aria-controls="sidebar" aria-expanded="false">&#9776;</button>
          <h1 class="text-xl font-bold tracking-wide brand-title">Dashboard Overview</h1>
        </div>

        <div class="flex flex-wrap items-center gap-4">
          <label class="flex items-center gap-2 text-xs">
            <input id="toggle3D" type="checkbox" checked class="sr-only" aria-controls="productionChart salesChart expiryChart">
            <span class="px-2 py-1 rounded-full border border-[var(--line)] bg-white">
              <span class="inline-block align-middle mr-1" id="dot3d"></span>
              3D ON/OFF
            </span>
          </label>

          <label class="flex items-center gap-2 text-xs">
            Depth
            <input id="depthRange" type="range" min="0" max="24" value="10" class="w-28" aria-label="3D depth">
            <span id="depthVal" class="tabular-nums">10</span>
          </label>

          <label class="flex items-center gap-2 text-xs">
            Tilt
            <input id="liftRange" type="range" min="-16" max="0" value="-6" class="w-28" aria-label="3D tilt">
            <span id="liftVal" class="tabular-nums">-6</span>
          </label>
        </div>
      </header>

      <!-- Content -->
      <main class="flex-1 overflow-y-auto p-8">
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

          <!-- Metrics Cards -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" aria-live="polite">
            @php
              $metrics = [
                ['label' => 'Total Products',        'value' => $totalProducts,                          'note' => 'Based on weekly production', 'icon' => '📦'],
                ['label' => 'Total Materials (kg)',  'value' => number_format($totalMaterialsWeight, 2), 'note' => 'Weekly materials on hand',   'icon' => '⚖️'],
                ['label' => 'Total Revenue',         'value' => '₱' . number_format($totalRevenue, 2),   'note' => 'Weekly product sales',      'icon' => '💰'],
                ['label' => 'Sales Transactions',    'value' => $totalSales,                             'note' => 'Weekly transactions',       'icon' => '📈'],
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
          <div class="card p-5 rounded-2xl">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h2 class="text-lg font-semibold mb-1">📈 Sales Report</h2>
                <p class="text-xs muted">Real time sales analytics</p>
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
                $avgPrice = $totalSales > 0 ? ($totalRevenue / max($totalSales,1)) : 0;
                $salesStats = [
                  ['label' => 'Total Revenue', 'value' => '₱' . number_format($totalRevenue, 2), 'icon' => '💰', 'color' => 'text-[var(--brand-red)]'],
                  ['label' => 'Units Sold',    'value' => number_format($totalSales, 0),         'icon' => '📦', 'color' => 'text-[var(--blue)]'],
                  ['label' => 'Avg Price/Unit','value' => '₱' . number_format($avgPrice, 2),     'icon' => '📊', 'color' => 'text-[var(--green)]'],
                  ['label' => 'Biggest Day',   'value' => 'N/A',                                 'icon' => '🔥', 'color' => 'text-[var(--yellow)]'],
                ];
              @endphp
              @foreach($salesStats as $stat)
                <div class="text-center">
                  <div class="text-2xl mb-1" aria-hidden="true">{{ $stat['icon'] }}</div>
                  <div class="text-xs muted mb-1">{{ $stat['label'] }}</div>
                  <div class="text-sm font-semibold {{ $stat['color'] }}">{{ $stat['value'] }}</div>
                </div>
              @endforeach
            </div>

            <div class="h-36 relative">
              <p id="salesTrendsDesc" class="sr-only">Glassy neon ridge of revenue over the selected period.</p>
              <canvas id="salesTrendsChart" aria-label="Sales trend chart" aria-describedby="salesTrendsDesc"></canvas>
            </div>
          </div>

          <!-- Most Sold Products and Types -->
          <div class="card p-5 rounded-2xl">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h2 class="text-lg font-semibold mb-1">🏆 Most Sold Products and Variant</h2>
                <p class="text-xs muted">Top 5 Product Variant combinations by revenue</p>
              </div>
              @if(\Illuminate\Support\Facades\Route::has('sales'))
                <a href="{{ route('sales') }}" class="btn btn-green text-xs">View all</a>
              @endif
            </div>

            @if(($topProducts ?? collect())->isEmpty())
              <div class="text-center py-8">
                <div class="text-4xl mb-2" aria-hidden="true">📊</div>
                <div class="text-sm muted">No sales data available</div>
                <div class="text-xs muted mt-1">Start recording sales to see top product Variant combinations</div>
              </div>
            @else
              <div class="space-y-3">
                @foreach($topProducts as $index => $product)
                  <div class="flex items-center gap-3 p-3 rounded-lg bg-[#fafafa] hover:bg-[#f3f4f6] transition">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold text-white" style="background:var(--brand-red);" aria-label="Rank {{ $index + 1 }}">{{ $index + 1 }}</div>
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center justify-between mb-1">
                        <div class="font-medium truncate">{{ $product->product_name ?? 'Product' }}</div>
                        <div class="text-sm font-semibold" style="color:var(--brand-red)">₱{{ number_format($product->revenue ?? 0, 2) }}</div>
                      </div>
                      <div class="mb-1">
                        <span class="chip">{{ $product->sale_type ?? 'N/A' }}</span>
                      </div>
                      <div class="flex items-center justify-between text-xs muted">
                        <span>{{ number_format($product->quantity ?? 0, 2) }} sold</span>
                        <span>{{ number_format($product->revenue_share ?? 0, 1) }}% of week</span>
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
                <h2 class="text-base font-semibold mb-1">Recent Sales</h2>
                <p class="text-xs muted mb-3">Latest from <strong>weekly product sales</strong></p>
              </div>
              @if(\Illuminate\Support\Facades\Route::has('sales'))
                <a href="{{ route('sales') }}" class="btn btn-blue text-xs">View all</a>
              @endif
            </div>

            <table class="text-sm text-left">
              <thead class="uppercase">
                <tr>
                  <th scope="col" class="py-2 px-3">Product</th>
                  <th scope="col" class="py-2 px-3">Variant</th>
                  <th scope="col" class="py-2 px-3 text-right">Qty</th>
                  <th scope="col" class="py-2 px-3 text-right">Price</th>
                  <th scope="col" class="py-2 px-3">Date</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($recentSales as $sale)
                  <tr class="border-t">
                    <td class="py-2 px-3">{{ $sale->product_name }}</td>
                    <td class="py-2 px-3"><span class="chip">{{ $sale->sale_type ?? 'N/A' }}</span></td>
                    <td class="py-2 px-3 text-right">{{ number_format($sale->quantity, 3) }}</td>
                    <td class="py-2 px-3 text-right">₱{{ number_format($sale->unit_price, 2) }}</td>
                    <td class="py-2 px-3">{{ \Carbon\Carbon::parse($sale->date)->timezone('Asia/Manila')->format('M d, Y') }}</td>
                  </tr>
                @empty
                  <tr><td colspan="5" class="py-3 text-center muted">No sales found.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <!-- Materials Snapshot -->
          <div class="card p-5 rounded-2xl">
            <div class="flex items-center justify-between mb-2">
              <h2 class="text-base font-semibold">Materials Logged (This Week)</h2>
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
            // Expiry quick stats (packs or bags)
            $expiryStats      = $expiryStats ?? [];
            $totalExpiring    = $expiryStats['total_expiring'] ?? 0;
            $criticalExpiring = $expiryStats['critical'] ?? 0;
            $highExpiring     = $expiryStats['high'] ?? 0;
            $mediumExpiring   = $expiryStats['medium'] ?? 0;

            // Priority list for actions
            $expiryPriority   = $expiryPriority ?? collect();
          @endphp

          <!-- Expiration Trend · Predictive and Action Oriented -->
          <div class="card p-5 rounded-2xl">
            <div class="flex items-center justify-between mb-2">
              <div>
                <h2 class="text-base font-semibold">Expiration Risk and Actions</h2>
                <p class="text-[11px] muted">
                  Shows packs and bags that are closest to expiry and what to prioritize.
                </p>
              </div>
              <div class="flex flex-col items-end gap-1 text-[11px]">
                <div class="flex items-center gap-2">
                  <span class="px-2 py-0.5 rounded-full bg-yellow-50 border border-yellow-100 text-[10px] font-semibold">
                    Total at risk
                  </span>
                  <span class="font-semibold tabular-nums">{{ number_format($totalExpiring, 0) }}</span>
                </div>
                <div class="flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-red-500 shadow-[0_0_8px_rgba(248,113,113,.9)]"></span>
                  <span class="muted">Critical (0 to 2 days):</span>
                  <span class="font-semibold text-red-600 tabular-nums">{{ number_format($criticalExpiring, 0) }}</span>
                </div>
                <div class="flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-amber-400 shadow-[0_0_8px_rgba(251,191,36,.9)]"></span>
                  <span class="muted">High (3 to 5 days):</span>
                  <span class="font-semibold text-amber-600 tabular-nums">{{ number_format($highExpiring, 0) }}</span>
                </div>
              </div>
            </div>
            <div class="h-56 relative">
              <p id="expiryDesc" class="sr-only">
                3D bar chart of packs and bags expiring this week by day, with color coded urgency.
              </p>
              <canvas id="expiryChart" aria-label="Expiry chart" aria-describedby="expiryDesc"></canvas>
              <div id="expEmpty" class="absolute inset-0 hidden items-center justify-center text-sm muted text-center px-4">
                No expiries this week. You are safe from spoilage based on current data.
              </div>
            </div>

            <p class="text-[11px] muted mt-2">
              Bars represent expiring packs and bags per day. The higher the bar, the more aggressive you should be with promos, dispatch or slowing new production for that item.
            </p>

            <div class="border-t border-[var(--line)] mt-3 pt-3">
              <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-semibold uppercase muted">Priority to sell or move first</p>
                <p class="text-[11px] muted hidden sm:block">
                  Auto suggestions based on days left before expiry.
                </p>
              </div>

              @if($expiryPriority instanceof \Illuminate\Support\Collection && $expiryPriority->isNotEmpty())
                <div class="space-y-2 max-h-44 overflow-y-auto pr-1">
                  @foreach($expiryPriority as $row)
                    @php
                      $pName   = $row->product_name ?? $row['product_name'] ?? 'Product';
                      $batch   = $row->batch_code ?? $row['batch_code'] ?? null;
                      $variant = $row->variant_label ?? $row['variant_label'] ?? null;
                      $days    = $row->days_left ?? $row['days_left'] ?? null;
                      $units   = $row->units_at_risk ?? $row['units_at_risk'] ?? 0; // packs or bags
                      $action  = $row->recommended_action ?? $row['recommended_action'] ?? null;

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
                    <div class="flex items-start justify-between gap-3 text-xs">
                      <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                          <span class="font-medium truncate">{{ $pName }}</span>
                          @if($variant)
                            <span class="chip text-[10px]">{{ $variant }}</span>
                          @endif
                          @if($batch)
                            <span class="chip text-[10px]">Batch {{ $batch }}</span>
                          @endif
                          <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $badgeClass }}">
                            {{ $badgeLabel }}
                          </span>
                        </div>
                        <div class="muted text-[11px]">
                          {{ number_format($units, 0) }} pack(s) or bag(s) at risk
                          @if(!is_null($days))
                            •
                            @if($days <= 0)
                              expiry reached
                            @elseif($days === 1)
                              1 day before expiry
                            @else
                              {{ $days }} days before expiry
                            @endif
                          @endif
                        </div>
                      </div>
                      @if($action)
                        <div class="text-right text-[11px]">
                          <div class="muted">Suggested move:</div>
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
                  Once expiry risk is detected, this section will list which batches to push first,
                  how many packs or bags are at risk, and what operational move makes sense
                  such as promo, priority dispatch, stock rotation or pausing production.
                </p>
              @endif
            </div>
          </div>

          <!-- Weekly Production -->
          <div class="card p-5 rounded-2xl">
            <div class="flex items-center justify-between mb-2">
              <h2 class="text-base font-semibold">Weekly Production</h2>
              <label class="text-xs flex items-center gap-2">
                <input id="toggleProduction" type="checkbox" checked class="sr-only">
                <span class="px-2 py-1 rounded-full border border-[var(--line)] bg-white">3D</span>
              </label>
            </div>
            <div class="h-56 relative">
              <p id="prodDesc" class="sr-only">Bar chart of weekly units produced.</p>
              <canvas id="productionChart" aria-label="Production chart" aria-describedby="prodDesc"></canvas>
              <div id="prodEmpty" class="absolute inset-0 hidden items-center justify-center text-sm muted">No data for this week</div>
            </div>
          </div>

          <!-- Weekly Sales with AI forecast -->
          <div class="card p-5 rounded-2xl">
            <div class="flex items-center justify-between mb-2">
              <h2 class="text-base font-semibold">Weekly Sales</h2>
              <div class="flex items-center gap-3">
                <select id="weeklySalesMode" class="input w-48 py-1 text-xs" aria-label="Weekly sales view mode">
                  <option value="quantity" selected>Qty + Revenue</option>
                  <option value="profit">Revenue + Profit</option>
                  <option value="forecast">Next Week Forecast (AI)</option>
                </select>
                <label class="text-xs flex items-center gap-2">
                  <input id="toggleSales" type="checkbox" checked class="sr-only">
                  <span class="px-2 py-1 rounded-full border border-[var(--line)] bg-white">3D (Qty)</span>
                </label>
              </div>
            </div>
            <div class="h-56 relative">
              <p id="salesDesc" class="sr-only">Combo chart showing quantity sold, revenue, estimated profit, and AI forecast.</p>
              <canvas id="salesChart" aria-label="Sales chart" aria-describedby="salesDesc"></canvas>
              <div id="salesEmpty" class="absolute inset-0 hidden items-center justify-center text-sm muted">No data for this week</div>
            </div>
            <p id="weeklySalesInsight" class="text-[11px] muted mt-2">
              This week you recorded ₱{{ number_format($weekRevenue ?? 0, 2) }} in revenue.
              Estimated profit is ₱{{ number_format($estimatedWeekProfit ?? 0, 2) }}
              @if(!is_null($estimatedGrossMarginPct ?? null))
                with about {{ $estimatedGrossMarginPct }}% gross margin.
              @endif
            </p>
          </div>

          <!-- Predictive Analytics · Production Planning Assistant -->
          @php
            $forecastSummary       = $forecastSummary ?? [];
            $forecastHorizonDays   = $forecastSummary['horizon_days'] ?? 30;
            $globalStockoutDateRaw = $forecastSummary['global_stockout_date'] ?? null;
            $globalStockoutLabel   = $globalStockoutDateRaw
                ? \Carbon\Carbon::parse($globalStockoutDateRaw)->timezone('Asia/Manila')->format('M d, Y')
                : 'No projected stockout';
            $globalRecommendedProd = $forecastSummary['total_recommended_production'] ?? null;
            $forecastTopProducts   = $forecastTopProducts ?? collect();
            $productForecast       = $productForecast ?? collect();

            $atRiskCount = ($forecastTopProducts instanceof \Illuminate\Support\Collection)
                ? $forecastTopProducts->count()
                : 0;

            $soonestDaysToStockout = null;
            if ($forecastTopProducts instanceof \Illuminate\Support\Collection) {
                foreach ($forecastTopProducts as $row) {
                    $d = $row->days_to_stockout ?? $row['days_to_stockout'] ?? null;
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
                <p class="text-xs muted">What to produce next in the next {{ $forecastHorizonDays }} days</p>
              </div>
              <div class="text-right text-xs space-y-1">
                <div class="muted">Global stockout:</div>
                <div class="font-semibold">{{ $globalStockoutLabel }}</div>

                @if(!is_null($globalRecommendedProd))
                  <div class="text-[10px] muted">
                    Suggested total production:<br>
                    <span class="font-semibold">{{ number_format($globalRecommendedProd, 2) }} kg</span>
                  </div>
                @endif

                @if($atRiskCount > 0)
                  <div class="text-[10px] muted">
                    Items at risk: <span class="font-semibold">{{ $atRiskCount }}</span>
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
                Bar chart of expected daily orders and remaining stock for the upcoming days.
              </p>
              <canvas id="forecastChart" aria-label="Forecast chart" aria-describedby="forecastDesc"></canvas>
              <div id="forecastEmpty" class="absolute inset-0 hidden items-center justify-center text-sm muted text-center px-4">
                Not enough historical data yet to generate a forecast. Keep recording production and sales to unlock production planning suggestions.
              </div>
            </div>

            <p class="text-[11px] muted mb-3">
              Bars show expected daily orders and estimated remaining stock if you do not add new production.
            </p>

            <div class="border-t border-[var(--line)] pt-3 mt-1">
              <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-semibold uppercase muted">What to produce next</p>
              </div>
              @if($forecastTopProducts instanceof \Illuminate\Support\Collection && $forecastTopProducts->isNotEmpty())
                <div class="space-y-2 max-h-44 overflow-y-auto pr-1">
                  @foreach($forecastTopProducts as $row)
                    @php
                      $name      = $row->name ?? $row['name'] ?? 'Product';
                      $daily     = $row->daily_demand ?? $row['daily_demand'] ?? 0;
                      $daysLeft  = $row->days_to_stockout ?? $row['days_to_stockout'] ?? null;
                      $rec       = $row->recommended_production ?? $row['recommended_production'] ?? null;

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
                      $targetWindow = (!is_null($daysLeft) && (int)$daysLeft > 0) ? (int)$daysLeft : 1;
                    @endphp

                    <div class="flex items-center justify-between text-xs gap-3">
                      <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                          <div class="font-medium truncate">{{ $name }}</div>
                          <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $badgeClass }}">
                            {{ $badgeLabel }}
                          </span>
                        </div>
                        <div class="muted text-[11px]">
                          Daily demand about {{ number_format($daily, 2) }} kg
                        </div>
                      </div>
                      <div class="text-right ml-3">
                        @if(!is_null($daysLeft))
                          <div class="text-[11px] {{ $daysLeft <= 3 ? 'text-red-600' : ($daysLeft <= 7 ? 'text-amber-500' : 'text-green-600') }}">
                            {{ $daysLeft <= 0 ? 'No days left' : $daysLeft . ' days left' }}
                          </div>
                        @endif
                        @if(!is_null($rec) && $rec > 0)
                          <div class="text-[11px] muted">
                            Produce
                            <span class="font-semibold">{{ number_format($rec, 1) }} kg</span>
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
                  Once enough sales history exists, this section will list which products you should produce next,
                  how much, and how urgent they are.
                </p>
              @endif
            </div>
          </div>

          <!-- NEW: Top 5 Products to Produce This Week (AI) -->
          <div class="card p-5 rounded-2xl">
            <div class="flex items-center justify-between mb-3">
              <div>
                <h2 class="text-base font-semibold">🚀 Top 5 Products to Produce This Week (AI)</h2>
                <p class="text-xs muted">
                  Based on recent demand, inventory, and a {{ $productForecast->isNotEmpty() ? '7-day' : 'short' }} forecast horizon.
                </p>
              </div>
              <div class="text-right text-xs muted hidden sm:block">
                <p>Uses AI-smoothed daily demand</p>
                <p>to estimate how much to produce.</p>
              </div>
            </div>

            @if($productForecast instanceof \Illuminate\Support\Collection && $productForecast->isNotEmpty())
              @php
                $topToProduce = $productForecast->sortByDesc('suggested_production')->take(5);
              @endphp
              <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                @foreach($topToProduce as $row)
                  @php
                    $pName       = $row['product_name'] ?? 'Product';
                    $avgDemand   = $row['avg_daily_demand'] ?? 0;
                    $forecastTot = $row['forecast_total'] ?? 0;
                    $stock       = $row['current_inventory'] ?? 0;
                    $daysLeft    = $row['days_to_stockout'] ?? null;
                    $suggested   = $row['suggested_production'] ?? 0;

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
                        AI daily demand: <span class="font-semibold">{{ number_format($avgDemand, 2) }} kg</span>
                        • Week demand: <span class="font-semibold">{{ number_format($forecastTot, 1) }} kg</span>
                      </div>
                      <div class="muted text-[11px]">
                        Current inventory: <span class="font-semibold">{{ number_format($stock, 1) }} kg</span>
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
                        Suggested this week:
                      </div>
                      <div class="font-semibold text-[13px]">
                        {{ number_format($suggested, 1) }} kg
                      </div>
                    </div>
                  </div>
                @endforeach
              </div>
            @else
              <p class="text-xs muted">
                Once enough sales and production history exist, this panel will highlight the top 5 products
                you should prioritize this week with suggested production quantities in kg.
              </p>
            @endif
          </div>

        </div>
      </main>
    </div>
  </div>

  <!-- Materials Used (full-width) -->
  <div class="mx-8 my-6 card p-5 rounded-2xl">
    <div class="flex items-center justify-between mb-2">
      <div>
        <h2 class="text-base font-semibold">Materials Used (This Week)</h2>
        <p class="text-xs muted">Based on production times recipe</p>
      </div>
      <div class="text-right text-xs muted">
        <div>Total Qty: {{ number_format($materialsUsageTotals['qty'] ?? 0, 3) }}</div>
        <div>Total Cost: ₱{{ number_format($materialsUsageTotals['cost'] ?? 0, 2) }}</div>
      </div>
    </div>

    @php $rows = $materialsUsage ?? collect(); @endphp
    @if($rows->isEmpty())
      <div class="text-sm muted">No materials consumed this week.</div>
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
                <td class="py-2 px-3 text-right">{{ number_format($r->qty_used, 3) }} {{ $r->unit ?? 'kg' }}</td>
                <td class="py-2 px-3 text-right">₱{{ number_format($r->cost_used, 2) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1"></script>

  <!-- Simple Bar “3D” faces plugin -->
  <script>
/* ---------- 3D faces plugin (safe) ---------- */
const Bar3DPlugin = {
  id: 'bar3d',
  afterDatasetDraw(chart, args, opts) {
    try {
      if (!opts || !opts.enabled) return;
      if (window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) return;

      const meta = args.meta;
      if (!meta || meta.type !== 'bar') return;
      const {ctx, chartArea} = chart;
      if (!chartArea) return;

      const depth = Number(opts.depth ?? 10);
      const lift  = Number(opts.lift ?? -6);

      const dim = (rgba, f=0.85) => {
        const m = (rgba||'').match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/i);
        if (!m) return rgba || 'rgba(0,0,0,0.2)';
        const [r,g,b] = [m[1],m[2],m[3]].map(n=>Math.max(0,Math.min(255,Math.floor(n*f))));
        const a = (rgba.match(/rgba\(.+,\s*([.\d]+)\)/i)?.[1]) ?? 1;
        return `rgba(${r},${g},${b},${a})`;
      };

      const ds = chart.config.data.datasets?.[args.index] || {};
      const baseFill   = ds.backgroundColor || 'rgba(16,185,129,0.25)';
      const baseStroke = ds.borderColor     || 'rgba(16,185,129,1)';
      const topFill    = ds.topFaceColor    || dim(baseFill, 1.15);
      const sideFill   = ds.sideFaceColor   || dim(baseFill, 0.78);
      const topStroke  = ds.topStrokeColor  || dim(baseStroke, 1.1);
      const sideStroke = ds.sideStrokeColor || dim(baseStroke, 0.85);

      meta.data.forEach(bar => {
        const p = bar.getProps?.(['x','y','base','width'], true);
        if (!p) return;
        const x = p.x - p.width/2;
        const y = p.y;
        const w = p.width;
        const h = Math.max(0, (p.base ?? chartArea.bottom) - y);
        if (!isFinite(h) || h === 0) return;

        const dx = depth, dy = lift;

        const poly = (pts, fill, stroke) => {
          ctx.beginPath(); ctx.moveTo(pts[0].x, pts[0].y);
          for (let i=1;i<pts.length;i++) ctx.lineTo(pts[i].x, pts[i].y);
          ctx.closePath(); ctx.fillStyle = fill; ctx.fill();
          if (stroke) { ctx.strokeStyle = stroke; ctx.lineWidth = 1; ctx.stroke(); }
        };
        const top  = [{x, y},{x:x+dx, y:y+dy},{x:x+dx+w, y:y+dy},{x:x+w, y}];
        const side = [{x:x+w, y},{x:x+w+dx, y:y+dy},{x:x+w+dx, y:y+dy+h},{x:x+w, y:y+h}];

        ctx.save(); ctx.shadowColor='rgba(0,0,0,.12)'; ctx.shadowBlur=6; ctx.shadowOffsetY=3;
        poly(top, topFill, topStroke); ctx.restore();
        poly(side, sideFill, sideStroke);
      });
    } catch(e) {
      // fail silently to avoid breaking the chart
    }
  }
};
  </script>

  <script>
document.addEventListener('DOMContentLoaded', () => {
  if (typeof Chart === 'undefined') {
    console.error('Chart.js not loaded.');
    return;
  }
  Chart.register(Bar3DPlugin);

  // 2) Data from server (fallback to zeros so axes always show)
  const labels = @json($labels ?? []) || ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
  const prod   = (@json($weeklyProductionSeries ?? []) || []).map(v=>Number(v)||0);
  const qty    = (@json($weeklySalesQtySeries ?? []) || []).map(v=>Number(v)||0);
  const rev    = (@json($weeklySalesRevenueSeries ?? []) || []).map(v=>Number(v)||0);
  const profit = (@json($weeklySalesProfitSeries ?? []) || []).map(v=>Number(v)||0);

  // AI forecast series for weekly sales (next week, aligned by weekday)
  const qtyForecast    = (@json($weeklySalesForecastQtySeries ?? []) || []).map(v=>Number(v)||0);
  const revForecast    = (@json($weeklySalesForecastRevenueSeries ?? []) || []).map(v=>Number(v)||0);
  const profitForecast = (@json($weeklySalesForecastProfitSeries ?? []) || []).map(v=>Number(v)||0);

  const exp    = (@json($weeklyExpirySeries ?? []) || []).map(v=>Number(v)||0);

  // weekly summaries for insights
  const weekRevenueJS         = Number(@json($weekRevenue ?? 0));
  const estimatedWeekProfitJS = Number(@json($estimatedWeekProfit ?? 0));
  const estimatedMarginJS     = Number(@json($estimatedGrossMarginPct ?? 0));
  const forecastTopProductsJS = @json($forecastTopProducts ?? []);

  // Forecast data (next N days) for production planning
  const forecastLabelsRaw   = @json($forecastLabels ?? []);
  const forecastDemandRaw   = @json($forecastDemandSeries ?? []);
  const forecastInventoryRaw= @json($forecastInventorySeries ?? []);
  const forecastLabels      = Array.isArray(forecastLabelsRaw) ? forecastLabelsRaw : [];
  const forecastDemand      = (forecastDemandRaw || []).map(v => Number(v) || 0);
  const forecastInventory   = (forecastInventoryRaw || []).map(v => Number(v) || 0);

  // 3) Colors
  const C_RED='rgba(239,68,68,1)',     C_RED_30='rgba(239,68,68,.3)';
  const C_GREEN='rgba(16,185,129,1)',  C_GREEN_30='rgba(16,185,129,.3)';
  const C_BLUE='rgba(37,99,235,1)',    C_BLUE_30='rgba(37,99,235,1,.3)';
  const C_YELLOW='rgba(245,158,11,1)', C_YELLOW_30='rgba(245,158,11,.3)';
  const gridColor='rgba(107,114,128,.25)', tickColor='#0f172a', barRadius=6;

  const prefersReduced = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
  const toggle3D = document.getElementById('toggle3D');
  if (prefersReduced && toggle3D) toggle3D.checked = false;

  const setEmptyBanner = (arr, id) => {
    const el = document.getElementById(id);
    if (!el) return;
    const empty = !arr.length || arr.every(v => Number(v) === 0);
    el.classList.toggle('hidden', !empty);
    el.classList.add('flex');
  };

  const sumArray = (arr = []) => arr.reduce((a,b)=>a+(Number(b)||0),0);

  // 4) Create charts
  const mk = (id, config) => {
    const c = document.getElementById(id);
    return c ? new Chart(c.getContext('2d'), config) : null;
  };

  // Production chart (3D bar)
  setEmptyBanner(prod, 'prodEmpty');
  const productionChart = mk('productionChart', {
    type: 'bar',
    data: { labels, datasets: [{
      label: 'Units Produced', data: prod,
      backgroundColor: C_GREEN_30, borderColor: C_GREEN, borderWidth: 2, borderRadius: barRadius,
      topFaceColor:'rgba(16,185,129,.45)', sideFaceColor:'rgba(16,185,129,.25)'
    }]},
    options: {
      responsive:true, maintainAspectRatio:false,
      plugins:{ legend:{ labels:{ color: tickColor } }, title:{ display:true, text:'Weekly Production', color:'#0f172a' }, bar3d:{ enabled:true, depth:10, lift:-6 } },
      scales:{ x:{ ticks:{ color: tickColor }, grid:{ color: gridColor } }, y:{ beginAtZero:true, ticks:{ color: tickColor }, grid:{ color: gridColor } } }
    }
  });

  // Weekly sales chart with AI forecast datasets (more animated + creative)
  setEmptyBanner([...qty, ...rev], 'salesEmpty');
  const salesChart = mk('salesChart', {
    data: {
      labels,
      datasets: [
        {
          key:'qtyActual',
          type:'bar',
          label:'Qty Sold',
          data: qty,
          yAxisID:'y',
          backgroundColor:(ctx) => {
            const chart = ctx.chart;
            const {ctx: c, chartArea} = chart;
            if (!chartArea) return C_BLUE_30;
            const g = c.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
            g.addColorStop(0, 'rgba(37,99,235,.12)');
            g.addColorStop(0.5, 'rgba(59,130,246,.45)');
            g.addColorStop(1, 'rgba(191,219,254,.95)');
            return g;
          },
          borderColor:C_BLUE,
          borderWidth:2,
          borderRadius:barRadius,
          topFaceColor:'rgba(37,99,235,.55)',
          sideFaceColor:'rgba(30,64,175,.35)'
        },
        {
          key:'revActual',
          type:'line',
          label:'Revenue',
          data: rev,
          yAxisID:'y1',
          borderColor:(ctx) => {
            const chart = ctx.chart;
            const {ctx: c, chartArea} = chart;
            if (!chartArea) return C_RED;
            const g = c.createLinearGradient(chartArea.left, 0, chartArea.right, 0);
            g.addColorStop(0, 'rgba(248,113,113,1)');
            g.addColorStop(0.5, 'rgba(239,68,68,1)');
            g.addColorStop(1, 'rgba(248,250,252,1)');
            return g;
          },
          backgroundColor:C_RED,
          borderWidth:3,
          tension:.4,
          pointRadius:3,
          pointHoverRadius:6,
          pointHitRadius:10,
          fill:false
        },
        {
          key:'profitActual',
          type:'line',
          label:'Estimated Profit',
          data: profit,
          yAxisID:'y1',
          borderColor:'rgba(16,185,129,.9)',
          backgroundColor:'rgba(16,185,129,.9)',
          borderWidth:2,
          tension:.35,
          pointRadius:2.5,
          pointHoverRadius:5,
          fill:false,
          hidden:true
        },
        {
          key:'qtyForecast',
          type:'bar',
          label:'Predicted Qty (AI)',
          data: qtyForecast,
          yAxisID:'y',
          backgroundColor:'rgba(37,99,235,.15)',
          borderColor:'rgba(37,99,235,.6)',
          borderWidth:2,
          borderRadius:barRadius,
          topFaceColor:'rgba(59,130,246,.35)',
          sideFaceColor:'rgba(30,64,175,.25)',
          borderDash:[4,3],
          hidden:true
        },
        {
          key:'revForecast',
          type:'line',
          label:'Predicted Revenue (AI)',
          data: revForecast,
          yAxisID:'y1',
          borderColor:'rgba(239,68,68,.6)',
          backgroundColor:'rgba(239,68,68,.6)',
          borderWidth:2,
          tension:.4,
          pointRadius:2,
          pointHoverRadius:5,
          fill:false,
          borderDash:[6,4],
          hidden:true
        },
        {
          key:'profitForecast',
          type:'line',
          label:'Predicted Profit (AI)',
          data: profitForecast,
          yAxisID:'y1',
          borderColor:'rgba(16,185,129,.5)',
          backgroundColor:'rgba(16,185,129,.5)',
          borderWidth:2,
          tension:.4,
          pointRadius:2,
          pointHoverRadius:5,
          fill:false,
          borderDash:[6,4],
          hidden:true
        }
      ]
    },
    options: {
      responsive:true,
      maintainAspectRatio:false,
      plugins:{
        legend:{ labels:{ color: tickColor } },
        title:{ display:true, text:'Weekly Sales', color:'#0f172a' },
        tooltip:{
          backgroundColor:'rgba(15,23,42,.96)',
          borderColor:'rgba(148,163,184,.7)',
          borderWidth:1,
          padding:10,
          cornerRadius:10,
          callbacks:{
            label:(ctx)=>{
              const label  = ctx.dataset.label || '';
              const val    = Number(ctx.parsed.y);
              if (label.toLowerCase().includes('revenue')) {
                const tag = label.toLowerCase().includes('predicted') ? 'Predicted revenue' : 'Revenue';
                return `${tag}: ₱${val.toLocaleString(undefined,{ minimumFractionDigits:2, maximumFractionDigits:2 })}`;
              }
              if (label.toLowerCase().includes('profit')) {
                const tag = label.toLowerCase().includes('predicted') ? 'Predicted profit' : 'Estimated profit';
                return `${tag}: ₱${val.toLocaleString(undefined,{ minimumFractionDigits:2, maximumFractionDigits:2 })}`;
              }
              if (label.toLowerCase().includes('predicted qty')) {
                return `Predicted qty: ${val.toLocaleString()}`;
              }
              return `Qty: ${val.toLocaleString()}`;
            }
          }
        },
        bar3d:{ enabled:true, depth:10, lift:-6 }
      },
      animation:{
        duration:1000,
        easing:'easeOutQuart',
        delay:(ctx) => {
          if (ctx.type !== 'data' || ctx.mode !== 'default') return 0;
          return ctx.dataIndex * 60 + ctx.datasetIndex * 80;
        }
      },
      datasets:{
        bar:{
          animations:{
            y:{
              duration:900,
              easing:'easeOutBack',
              from:(ctx)=>{
                const chart = ctx.chart;
                const yAxis = chart.scales.y;
                return yAxis ? yAxis.getPixelForValue(0) : chart.chartArea.bottom;
              }
            }
          }
        },
        line:{
          animations:{
            y:{
              duration:900,
              easing:'easeOutCubic',
              from:(ctx)=>{
                const chart = ctx.chart;
                const yAxis = ctx.scale || chart.scales.y1 || chart.scales.y;
                return yAxis ? yAxis.getPixelForValue(0) : chart.chartArea.bottom;
              }
            }
          }
        }
      },
      scales:{
        x:{ ticks:{ color: tickColor }, grid:{ color: gridColor } },
        y:{ position:'left', beginAtZero:true, ticks:{ color: tickColor }, grid:{ color: gridColor } },
        y1:{ position:'right', beginAtZero:true, ticks:{ color: tickColor }, grid:{ drawOnChartArea:false } }
      }
    }
  });

  // Sparkline (simple animated line)
  const spark = mk('salesTrendsChart', {
    type:'line',
    data:{ labels, datasets:[{
      label:'Revenue',
      data: rev,
      borderColor:C_RED,
      backgroundColor:C_RED,
      borderWidth:2,
      tension:.35,
      pointRadius:0,
      fill:false
    }] },
    options:{
      responsive:true, maintainAspectRatio:false,
      plugins:{ legend:{ display:false } },
      scales:{ x:{ display:false }, y:{ display:false, beginAtZero:true } },
      animation:{ duration:700, easing:'easeOutCubic' }
    }
  });

  // Expiry chart (3D bar, neon gradient, bouncy)
  setEmptyBanner(exp, 'expEmpty');
  const expiryChart = mk('expiryChart', {
    type:'bar',
    data:{ labels, datasets:[{
      label:'Packs or bags expiring', data: exp,
      backgroundColor:(ctx) => {
        const chart = ctx.chart;
        const {ctx: c, chartArea} = chart;
        if (!chartArea) return C_YELLOW_30;
        const g = c.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
        g.addColorStop(0, 'rgba(250,204,21,.12)');
        g.addColorStop(0.5,'rgba(245,158,11,.5)');
        g.addColorStop(1, 'rgba(255,251,235,.95)');
        return g;
      },
      borderColor:C_YELLOW,
      borderWidth:2,
      borderRadius: barRadius,
      topFaceColor:'rgba(245,158,11,.55)',
      sideFaceColor:'rgba(202,138,4,.35)'
    }]},
    options:{
      responsive:true,
      maintainAspectRatio:false,
      plugins:{
        legend:{ labels:{ color: tickColor } },
        title:{ display:true, text:'Packs and bags at risk this week', color:'#0f172a' },
        tooltip:{
          callbacks:{
            label:(ctx)=>{
              const v = Number(ctx.parsed.y);
              return `Expiring packs or bags: ${v.toLocaleString()}`;
            }
          }
        },
        bar3d:{ enabled:true, depth:10, lift:-6 }
      },
      animation:{
        duration:900,
        easing:'easeOutCubic'
      },
      datasets:{
        bar:{
          animations:{
            y:{
              duration:1100,
              easing:'easeOutElastic',
              from:(ctx)=>{
                const chart = ctx.chart;
                const yAxis = chart.scales.y;
                return yAxis ? yAxis.getPixelForValue(0) : chart.chartArea.bottom;
              }
            }
          }
        }
      },
      scales:{
        x:{ ticks:{ color: tickColor }, grid:{ color: gridColor } },
        y:{ beginAtZero:true, ticks:{ color: tickColor }, grid:{ color: gridColor } }
      }
    }
  });

  // Predictive Analytics chart as glassy neon 3D bar graph
  setEmptyBanner([...forecastDemand, ...forecastInventory], 'forecastEmpty');

  const forecastChart = mk('forecastChart', {
    type: 'bar',
    data: {
      labels: forecastLabels,
      datasets: [
        {
          type: 'bar',
          label: 'Expected Daily Orders',
          data: forecastDemand,
          yAxisID: 'y',
          borderColor: 'rgba(239,68,68,1)',
          backgroundColor: (ctx) => {
            const chart = ctx.chart;
            const {ctx: c, chartArea} = chart;
            if (!chartArea) return C_RED_30;
            const gradient = c.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
            gradient.addColorStop(0, 'rgba(248,113,113,.18)');
            gradient.addColorStop(0.5, 'rgba(239,68,68,.45)');
            gradient.addColorStop(1, 'rgba(248,250,252,.9)');
            return gradient;
          },
          borderWidth: 2,
          borderRadius: 10,
          topFaceColor: 'rgba(248,113,113,.55)',
          sideFaceColor:'rgba(127,29,29,.35)',
          hoverBorderWidth: 3
        },
        {
          type: 'bar',
          label: 'Estimated Remaining Stock',
          data: forecastInventory,
          yAxisID: 'y',
          borderColor: 'rgba(34,197,94,1)',
          backgroundColor: (ctx) => {
            const chart = ctx.chart;
            const {ctx: c, chartArea} = chart;
            if (!chartArea) return C_GREEN_30;
            const gradient = c.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
            gradient.addColorStop(0, 'rgba(22,163,74,.16)');
            gradient.addColorStop(0.5, 'rgba(34,197,94,.45)');
            gradient.addColorStop(1, 'rgba(240,253,250,.9)');
            return gradient;
          },
          borderWidth: 2,
          borderRadius: 10,
          topFaceColor: 'rgba(74,222,128,.55)',
          sideFaceColor:'rgba(22,101,52,.35)',
          hoverBorderWidth: 3
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { labels: { color: tickColor } },
        title: { display: false },
        tooltip: {
          backgroundColor: 'rgba(15,23,42,.9)',
          borderColor: 'rgba(148,163,184,.6)',
          borderWidth: 1,
          padding: 10,
          cornerRadius: 10,
          callbacks: {
            label: (ctx) => {
              const val = Number(ctx.parsed.y);
              const ds  = ctx.dataset.label || '';
              return `${ds}: ${val.toLocaleString(undefined,{ minimumFractionDigits:2, maximumFractionDigits:2 })} kg`;
            }
          }
        },
        bar3d: { enabled: true, depth: 12, lift: -8 }
      },
      animation: { duration: 900, easing: 'easeOutCubic' },
      datasets: {
        bar: {
          animations: {
            y: {
              duration: 1100,
              easing: 'easeOutElastic',
              from: (ctx) => {
                const chart = ctx.chart;
                const yAxis = chart.scales.y;
                return yAxis ? yAxis.getPixelForValue(0) : chart.chartArea.bottom;
              }
            }
          }
        }
      },
      scales: {
        x: { ticks: { color: tickColor }, grid: { color: 'rgba(148,163,184,.12)', drawBorder: false } },
        y: { beginAtZero: true, ticks: { color: tickColor }, grid: { color: 'rgba(148,163,184,.22)', drawBorder: false } }
      }
    }
  });

  // 5) Master 3D toggles
  const toggleProduction = document.getElementById('toggleProduction');
  const toggleSales = document.getElementById('toggleSales');
  const toggleExpiry = document.getElementById('toggleExpiry');
  const depthRange = document.getElementById('depthRange');
  const liftRange = document.getElementById('liftRange');
  const depthVal = document.getElementById('depthVal');
  const liftVal = document.getElementById('liftVal');
  const dot3d = document.getElementById('dot3d');

  let t;
  const debounce = (fn, ms=120) => (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), ms); };

  const set3D = (chart, enabled, depth, lift) => {
    if (!chart) return;
    chart.options.plugins = chart.options.plugins || {};
    chart.options.plugins.bar3d = chart.options.plugins.bar3d || {};
    chart.options.plugins.bar3d.enabled = enabled && (toggle3D?.checked ?? true) && !prefersReduced;
    chart.options.plugins.bar3d.depth = depth;
    chart.options.plugins.bar3d.lift = lift;
    chart.update();
  };

  const apply3D = () => {
    const depth = Number(depthRange?.value ?? 10);
    const lift  = Number(liftRange?.value ?? -6);
    if (depthVal) depthVal.textContent = depth;
    if (liftVal)  liftVal.textContent  = lift;
    if (dot3d) dot3d.style.background = (toggle3D?.checked ? 'var(--green)' : 'var(--red)');

    set3D(productionChart, toggleProduction ? toggleProduction.checked : true, depth, lift);
    set3D(salesChart,      toggleSales ? toggleSales.checked : true,      depth, lift);
    set3D(expiryChart,     toggleExpiry ? toggleExpiry.checked : true,   depth, lift);
    set3D(forecastChart,   true,                                          depth, lift);
  };

  [toggle3D, toggleProduction, toggleSales, toggleExpiry, depthRange, liftRange]
    .forEach(el => el?.addEventListener('input', debounce(apply3D)));

  apply3D();

  // Weekly Sales mode logic
  const weeklySalesMode    = document.getElementById('weeklySalesMode');
  const weeklySalesInsight = document.getElementById('weeklySalesInsight');

  function findDs(key) {
    if (!salesChart) return null;
    return salesChart.data.datasets.find(d => d.key === key) || null;
  }

  function updateWeeklySalesMode() {
    if (!salesChart || !weeklySalesMode) return;
    const mode         = weeklySalesMode.value;
    const dsQty        = findDs('qtyActual');
    const dsRev        = findDs('revActual');
    const dsProfit     = findDs('profitActual');
    const dsQtyF       = findDs('qtyForecast');
    const dsRevF       = findDs('revForecast');
    const dsProfitF    = findDs('profitForecast');

    if (mode === 'quantity') {
      if (dsQty)     dsQty.hidden     = false;
      if (dsRev)     dsRev.hidden     = false;
      if (dsProfit)  dsProfit.hidden  = true;
      if (dsQtyF)    dsQtyF.hidden    = true;
      if (dsRevF)    dsRevF.hidden    = true;
      if (dsProfitF) dsProfitF.hidden = true;

      salesChart.options.plugins.title.text = 'Weekly Sales: Quantity and Revenue';

      if (weeklySalesInsight) {
        weeklySalesInsight.textContent =
          `This week you recorded ₱${weekRevenueJS.toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2})} in revenue. ` +
          `Estimated profit is ₱${estimatedWeekProfitJS.toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2})}` +
          (estimatedMarginJS ? ` with about ${estimatedMarginJS}% gross margin.` : '.');
      }
    } else if (mode === 'profit') {
      if (dsQty)     dsQty.hidden     = true;
      if (dsRev)     dsRev.hidden     = false;
      if (dsProfit)  dsProfit.hidden  = false;
      if (dsQtyF)    dsQtyF.hidden    = true;
      if (dsRevF)    dsRevF.hidden    = true;
      if (dsProfitF) dsProfitF.hidden = true;

      salesChart.options.plugins.title.text = 'Weekly Sales: Revenue and Estimated Profit';

      if (weeklySalesInsight) {
        weeklySalesInsight.textContent =
          'Each point shows how much of your revenue is likely turning into profit this week based on materials cost. Use spikes to time promos and production pushes.';
      }
    } else if (mode === 'forecast') {
      const hasForecast =
        (qtyForecast && qtyForecast.some(v => v > 0)) ||
        (revForecast && revForecast.some(v => v > 0)) ||
        (profitForecast && profitForecast.some(v => v > 0));

      if (dsQty)     dsQty.hidden     = false; // keep as context
      if (dsRev)     dsRev.hidden     = false;
      if (dsProfit)  dsProfit.hidden  = true;
      if (dsQtyF)    dsQtyF.hidden    = !hasForecast;
      if (dsRevF)    dsRevF.hidden    = !hasForecast;
      if (dsProfitF) dsProfitF.hidden = !hasForecast;

      salesChart.options.plugins.title.text = 'Weekly Sales: Next Week Forecast (AI)';

      if (weeklySalesInsight) {
        if (hasForecast) {
          const totalQtyF     = sumArray(qtyForecast);
          const totalRevenueF = sumArray(revForecast);
          const totalProfitF  = sumArray(profitForecast);

          let text = `AI forecast suggests around ${totalQtyF.toLocaleString()} unit(s) next week `;
          text    += `with about ₱${totalRevenueF.toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2})} in revenue`;

          if (totalProfitF > 0) {
            text += ` and roughly ₱${totalProfitF.toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2})} in profit.`;
          } else {
            text += '.';
          }

          text += ' Use this as a guide for stock, staffing, and promos.';
          weeklySalesInsight.textContent = text;
        } else {
          weeklySalesInsight.textContent =
            'Once enough history is available, this view will show AI predicted quantity, revenue, and profit for next week on top of your current week performance.';
        }
      }
    }

    salesChart.update();
  }

  weeklySalesMode?.addEventListener('change', updateWeeklySalesMode);
  updateWeeklySalesMode();

  // For quick debugging
  window.GenRevDashboard = { productionChart, salesChart, expiryChart, spark, forecastChart, apply3D };
});
  </script>
</body>
</html>
