<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>GenRev Admin Dashboard</title>

  <!-- Tailwind CDN (pinned major) -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Kalam:wght@400;700&family=Inria+Sans:wght@300;400;700&display=swap" rel="stylesheet">

  <style>
    :root{
      --page:#f7f8fb; --nav:#ffffff; --card:#ffffff; --line:#e5e7eb; --shadow:0 8px 20px rgba(17,24,39,.06);
      --ink:#111827; --muted:#6b7280;
      --red:#ef4444; --green:#10b981; --blue:#2563eb; --yellow:#f59e0b;
      --hover:#f3f4f6; --chip:#f9fafb;
    }
    body{ background:var(--page); color:var(--ink); font-family:'Inria Sans',system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif; min-height:100vh; overflow-x:hidden; }
    .nav-surface{ background:var(--nav); border-bottom:1px solid var(--line); box-shadow:var(--shadow); }
    .sidebar{ background:#ffffff; border-right:1px solid var(--line); }
    .card{ background:var(--card); border:1px solid var(--line); border-radius:16px; box-shadow:var(--shadow); }
    .side-link{ display:block; padding:.75rem 1.25rem; border-radius:999px 0 0 999px; transition:.16s; color:var(--ink); }
    .side-link:hover{ background:var(--hover); }
    .side-link--active{ background:linear-gradient(90deg, rgba(16,185,129,.12) 0%, rgba(16,185,129,.10) 100%); border-left:3px solid var(--green); font-weight:700; }
    .btn{ display:inline-flex; align-items:center; justify-content:center; gap:.5rem; padding:.65rem 1rem; border-radius:12px; border:1px solid transparent; font-weight:700; }
    .btn-primary{ background:var(--red); color:#fff; border-color:var(--red); } .btn-primary:hover{ filter:brightness(.97); }
    .btn-ghost{ background:#fff; border:1px solid var(--line); color:var(--ink); } .btn-ghost:hover{ background:var(--hover); }
    .btn-green{ background:var(--green); color:#fff; border-color:var(--green); }
    .btn-blue{ background:var(--blue); color:#fff; border-color:var(--blue); }
    .input{ width:100%; padding:.65rem .9rem; border-radius:12px; background:#fff; border:1px solid var(--line); color:var(--ink); transition:border-color .15s, box-shadow .15s, transform .12s; }
    .input::placeholder{ color:#9ca3af; } .input:hover{ border-color:#e2e8f0; }
    .input:focus{ outline:0; border-color:#93c5fd; box-shadow:0 0 0 2px rgba(37,99,235,.18); transform:translateY(-1px); }
    .chip{ display:inline-flex; align-items:center; gap:.4rem; padding:.32rem .6rem; border-radius:999px; font-size:.72rem; font-weight:700; background:var(--chip); border:1px solid var(--line); color:var(--ink); }
    table{ border-collapse:separate; border-spacing:0; width:100%; }
    thead th{ background:#f9fafb; color:#374151; font-weight:800; border-bottom:1px solid var(--line); }
    tbody td{ color:var(--ink); } tbody tr:nth-child(even){ background:#fafafa; } tbody tr:hover{ background:var(--hover); } th, td{ border-color:var(--line)!important; }
    .brand-title{ font-family:'Kalam',cursive; letter-spacing:.02em; color:var(--ink); } .muted{ color:var(--muted); }
    @media (max-width:1024px){ #sidebar{ transform:translateX(-100%); transition:transform .3s ease; } #sidebar.open{ transform:translateX(0); } }
    :where(a,button,[role="menuitem"],.side-link,.btn):focus{ outline:2px solid var(--green); outline-offset:2px; }

    /* Reduced motion: respect OS settings */
    @media (prefers-reduced-motion: reduce) {
      * { animation-duration: 0.01ms !important; animation-iteration-count: 1 !important; transition-duration: 0.01ms !important; scroll-behavior: auto !important; }
      .card { box-shadow: none; }
    }

    /* Utility for visually hidden text (a11y) */
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
            <img
              src="{{ asset('images/GENREV_FINAL.png') }}"
              alt="GenRev"
              loading="lazy"
              decoding="async"
              class="h-full w-full object-contain"
              onerror="this.closest('div').innerHTML='<span class=&quot;sr-only&quot;>GenRev</span>';"
            >
          </div>
          <span class="text-2xl font-bold tracking-wide brand-title">GenRev</span>
        </a>
        <button id="sidebarClose" class="lg:hidden text-xl font-bold" aria-label="Close sidebar">&times;</button>
      </div>

      <!-- User -->
      <div class="px-6 pt-4 pb-2">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-white" style="background:var(--green);" aria-hidden="true">
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
              <span class="inline-block w-2 h-2 rounded-full align-middle mr-1 bg-[var(--red)]" id="dot3d"></span>
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

          {{-- Metrics Cards --}}
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" aria-live="polite">
            @php
              $metrics = [
                ['label' => 'Total Products',        'value' => $totalProducts,                          'note' => 'Based on weekly production', 'icon' => '📦'],
                ['label' => 'Total Materials (kg)',  'value' => number_format($totalMaterialsWeight, 2), 'note' => 'Weekly materials',           'icon' => '⚖️'],
                ['label' => 'Total Revenue',         'value' => '₱' . number_format($totalRevenue, 2),   'note' => 'Weekly product sales',      'icon' => '💰'],
                ['label' => 'Sales Transactions',    'value' => $totalSales,                             'note' => 'Weekly transactions',       'icon' => '📈'],
              ];
            @endphp
            @foreach ($metrics as $metric)
              <div class="card p-5 rounded-2xl hover:shadow-lg transition">
                <div class="flex items-center gap-4">
                  <div class="w-10 h-10 rounded-full flex items-center justify-center text-xl text-white" style="background:var(--blue);" aria-hidden="true">
                    {{ $metric['icon'] }}
                  </div>
                  <div>
                    <p class="text-xs uppercase font-semibold tracking-wide muted">{{ $metric['label'] }}</p>
                    <h3 class="text-2xl font-bold">{{ $metric['value'] }}</h3>
                    <p class="text-xs muted">{{ $metric['note'] }}</p>
                  </div>
                </div>
              </div>
            @endforeach
          </div>

          {{-- Sales Report (sparkline) --}}
          <div class="card p-5 rounded-2xl">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h2 class="text-lg font-semibold mb-1">📈 Sales Report</h2>
                <p class="text-xs muted">Real-time sales analytics</p>
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
                  ['label' => 'Total Revenue', 'value' => '₱' . number_format($totalRevenue, 2), 'icon' => '💰', 'color' => 'text-[var(--red)]'],
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
            <div class="h-32 relative">
              <p id="salesTrendsDesc" class="sr-only">Sparkline of revenue over the selected period.</p>
              <canvas id="salesTrendsChart" aria-label="Sales trend chart" aria-describedby="salesTrendsDesc"></canvas>
            </div>
          </div>

          {{-- Most Sold Products & Types --}}
          <div class="card p-5 rounded-2xl">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h2 class="text-lg font-semibold mb-1">🏆 Most Sold Products & Types</h2>
                <p class="text-xs muted">Top 5 product–type combos by revenue</p>
              </div>
              @if(\Illuminate\Support\Facades\Route::has('sales'))
                <a href="{{ route('sales') }}" class="btn btn-green text-xs">View all</a>
              @endif
            </div>

            @if(($topProducts ?? collect())->isEmpty())
              <div class="text-center py-8">
                <div class="text-4xl mb-2" aria-hidden="true">📊</div>
                <div class="text-sm muted">No sales data available</div>
                <div class="text-xs muted mt-1">Start recording sales to see top product–type combos</div>
              </div>
            @else
              <div class="space-y-3">
                @foreach($topProducts as $index => $product)
                  <div class="flex items-center gap-3 p-3 rounded-lg bg-[#fafafa] hover:bg-[#f3f4f6] transition">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold text-white" style="background:var(--red);" aria-label="Rank {{ $index + 1 }}">
                      {{ $index + 1 }}
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center justify-between mb-1">
                        <div class="font-medium truncate">{{ $product->product_name ?? 'Product' }}</div>
                        <div class="text-sm font-semibold text-[var(--blue)]">₱{{ number_format($product->revenue ?? 0, 2) }}</div>
                      </div>
                      <div class="mb-1">
                        <span class="chip">{{ $product->sale_type ?? '—' }}</span>
                      </div>
                      <div class="flex items-center justify-between text-xs muted">
                        <span>{{ number_format($product->quantity ?? 0, 2) }} sold</span>
                        <span>{{ number_format($product->revenue_share ?? 0, 1) }}% of week</span>
                      </div>
                      <div class="w-full bg-[var(--line)]/40 rounded-full h-1.5 mt-2" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ min(($product->revenue_share ?? 0), 100) }}">
                        <div class="h-1.5 rounded-full" style="width: {{ min(($product->revenue_share ?? 0), 100) }}%; background:linear-gradient(90deg,var(--red),var(--yellow));"></div>
                      </div>
                    </div>
                  </div>
                @endforeach
              </div>
            @endif
          </div>

          {{-- Recent Sales --}}
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
                  <th scope="col" class="py-2 px-3">Type</th>
                  <th scope="col" class="py-2 px-3 text-right">Qty</th>
                  <th scope="col" class="py-2 px-3 text-right">Price</th>
                  <th scope="col" class="py-2 px-3">Date</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($recentSales as $sale)
                  <tr class="border-t">
                    <td class="py-2 px-3">{{ $sale->product_name }}</td>
                    <td class="py-2 px-3">
                      <span class="chip">{{ $sale->sale_type ?? '—' }}</span>
                    </td>
                    <td class="py-2 px-3 text-right">{{ number_format($sale->quantity, 3) }}</td>
                    <td class="py-2 px-3 text-right">₱{{ number_format($sale->unit_price, 2) }}</td>
                    <td class="py-2 px-3">{{ \Carbon\Carbon::parse($sale->date)->timezone('Asia/Manila')->format('M d, Y') }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="py-3 text-center muted">No sales found.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          {{-- Materials Snapshot --}}
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

          {{-- Expiration Trend --}}
          <div class="card p-5 rounded-2xl">
            <div class="flex items-center justify-between mb-2">
              <h2 class="text-base font-semibold">Expiration Trend</h2>
              <label class="text-xs flex items-center gap-2">
                <input id="toggleExpiry" type="checkbox" checked class="sr-only">
                <span class="px-2 py-1 rounded-full border border-[var(--line)] bg-white">3D</span>
              </label>
            </div>
            <div class="h-56 relative">
              <p id="expiryDesc" class="sr-only">Bar chart of items expiring this week.</p>
              <canvas id="expiryChart" aria-label="Expiry chart" aria-describedby="expiryDesc"></canvas>
              <div id="expEmpty" class="absolute inset-0 hidden items-center justify-center text-sm muted">No expiries this week</div>
            </div>
          </div>

          {{-- Weekly Production --}}
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

          {{-- Weekly Sales --}}
          <div class="card p-5 rounded-2xl">
            <div class="flex items-center justify-between mb-2">
              <h2 class="text-base font-semibold">Weekly Sales</h2>
              <label class="text-xs flex items-center gap-2">
                <input id="toggleSales" type="checkbox" checked class="sr-only">
                <span class="px-2 py-1 rounded-full border border-[var(--line)] bg-white">3D (Qty)</span>
              </label>
            </div>
            <div class="h-56 relative">
              <p id="salesDesc" class="sr-only">Combo chart showing quantity sold and revenue.</p>
              <canvas id="salesChart" aria-label="Sales chart" aria-describedby="salesDesc"></canvas>
              <div id="salesEmpty" class="absolute inset-0 hidden items-center justify-center text-sm muted">No data for this week</div>
            </div>
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
        <p class="text-xs muted">Based on production × recipe</p>
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
    const Bar3DPlugin = {
      id: 'bar3d',
      afterDatasetDraw(chart, args, opts) {
        if (!opts?.enabled) return;
        // respect reduced motion
        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        const {ctx, chartArea} = chart;
        const meta = args.meta;
        if (meta.type !== 'bar') return;

        const depth = opts.depth ?? 10;
        const lift  = opts.lift ?? -6;

        const dim = (rgba, f=0.85) => {
          const m = (rgba||'').match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/i);
          if (!m) return rgba||'rgba(0,0,0,0.2)';
          const [r,g,b] = [m[1],m[2],m[3]].map(n=>Math.max(0,Math.min(255,Math.floor(n*f))));
          const a = (rgba.match(/rgba\(.+,\s*([.\d]+)\)/i)?.[1]) ?? 1;
          return `rgba(${r},${g},${b},${a})`;
        };

        const ds = chart.config.data.datasets[args.index];
        const baseFill   = ds.backgroundColor || 'rgba(16,185,129,0.25)';
        const baseStroke = ds.borderColor     || 'rgba(16,185,129,1)';
        const topFill    = ds.topFaceColor    || dim(baseFill, 1.15);
        const sideFill   = ds.sideFaceColor   || dim(baseFill, 0.78);
        const topStroke  = ds.topStrokeColor  || dim(baseStroke, 1.1);
        const sideStroke = ds.sideStrokeColor || dim(baseStroke, 0.85);

        meta.data.forEach(bar => {
          const p = bar.getProps(['x','y','base','width'], true);
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
          const top = [{x, y},{x:x+dx, y:y+dy},{x:x+dx+w, y:y+dy},{x:x+w, y}];
          const side = [{x:x+w, y},{x:x+w+dx, y:y+dy},{x:x+w+dx, y:y+dy+h},{x:x+w, y:y+h}];

          ctx.save(); ctx.shadowColor='rgba(0,0,0,.12)'; ctx.shadowBlur=6; ctx.shadowOffsetY=3;
          poly(top, topFill, topStroke); ctx.restore();
          poly(side, sideFill, sideStroke);
        });
      }
    };
    Chart.register(Bar3DPlugin);
  </script>

  <!-- Dashboard Charts + Toggles -->
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const sidebar = document.getElementById('sidebar');
      const openBtn = document.getElementById('sidebarToggle');
      const closeBtn = document.getElementById('sidebarClose');

      // --- Sidebar a11y: open/close + focus trap + scroll lock
      const focusableSelector = 'a[href], area[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), [tabindex]:not([tabindex="-1"])';
      let lastFocused = null;

      const setAriaExpanded = (btn, expanded) => btn?.setAttribute('aria-expanded', String(expanded));

      const trapFocus = (container, e) => {
        const nodes = container.querySelectorAll(focusableSelector);
        if (!nodes.length) return;
        const first = nodes[0], last = nodes[nodes.length - 1];
        if (e.key !== 'Tab') return;
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
      };

      const openSidebar = () => {
        lastFocused = document.activeElement;
        sidebar?.classList.add('open');
        document.body.classList.add('overflow-hidden');
        setAriaExpanded(openBtn, true);
        sidebar?.focus();
        document.addEventListener('keydown', escClose, { once: true });
        sidebar?.addEventListener('keydown', (e)=>trapFocus(sidebar, e));
      };

      const closeSidebar = () => {
        sidebar?.classList.remove('open');
        document.body.classList.remove('overflow-hidden');
        setAriaExpanded(openBtn, false);
        lastFocused?.focus?.();
      };

      const escClose = (e) => { if (e.key === 'Escape') closeSidebar(); };

      openBtn?.addEventListener('click', openSidebar);
      closeBtn?.addEventListener('click', closeSidebar);

      // --- Data (server-injected)
      const labels = @json($labels ?? []);
      const prod   = @json($weeklyProductionSeries ?? []);
      const qty    = @json($weeklySalesQtySeries ?? []);
      const rev    = @json($weeklySalesRevenueSeries ?? []);
      const exp    = @json($weeklyExpirySeries ?? []);

      // --- Colors
      const C_RED='rgba(239,68,68,1)', C_RED_30='rgba(239,68,68,.3)';
      const C_GREEN='rgba(16,185,129,1)', C_GREEN_30='rgba(16,185,129,.3)';
      const C_BLUE='rgba(37,99,235,1)', C_BLUE_30='rgba(37,99,235,.3)';
      const C_YELLOW='rgba(245,158,11,1)', C_YELLOW_30='rgba(245,158,11,.3)';
      const gridColor='rgba(107,114,128,.25)', tickColor='#4b5563', barRadius=6;

      const showIfEmpty = (arr, id) => {
        const el = document.getElementById(id);
        if (!el) return;
        const empty = !arr || arr.length === 0 || arr.every(v => Number(v) === 0);
        el.classList.toggle('hidden', !empty);
        el.classList.add('flex');
      };

      // Prefer reduced motion: disable 3D by default if user asks
      const prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      const toggle3D = document.getElementById('toggle3D');
      if (prefersReduced && toggle3D) toggle3D.checked = false;

      showIfEmpty(prod, 'prodEmpty');
      const productionChart = new Chart(document.getElementById('productionChart'), {
        type: 'bar',
        data: { labels, datasets: [{
          label: 'Units Produced', data: prod,
          backgroundColor: C_GREEN_30, borderColor: C_GREEN, borderWidth: 2, borderRadius: barRadius,
          topFaceColor: 'rgba(16,185,129,.45)', sideFaceColor: 'rgba(16,185,129,.25)'
        }]},
        options: {
          responsive:true, maintainAspectRatio:false,
          plugins: { legend:{ labels:{ color: tickColor } }, title:{ display:true, text:'Weekly Production', color:'#111827' }, bar3d:{ enabled: true, depth: 10, lift: -6 } },
          scales: { x:{ ticks:{ color: tickColor }, grid:{ color: gridColor } }, y:{ beginAtZero:true, ticks:{ color: tickColor }, grid:{ color: gridColor } } }
        }
      });

      showIfEmpty([...(qty||[]), ...(rev||[])], 'salesEmpty');
      const salesChart = new Chart(document.getElementById('salesChart'), {
        data: {
          labels,
          datasets: [
            { type:'bar', label:'Qty Sold', data: qty, yAxisID:'y', backgroundColor: C_BLUE_30, borderColor: C_BLUE, borderWidth:2, borderRadius: barRadius, topFaceColor:'rgba(37,99,235,.5)', sideFaceColor:'rgba(37,99,235,.3)' },
            { type:'line', label:'Revenue',  data: rev, yAxisID:'y1', borderColor: C_RED, backgroundColor: C_RED, borderWidth:3, tension:.35, pointRadius:3, fill:false }
          ]
        },
        options: {
          responsive:true, maintainAspectRatio:false,
          plugins: {
            legend:{ labels:{ color: tickColor } },
            title:{ display:true, text:'Weekly Sales', color:'#111827' },
            tooltip:{ callbacks:{ label:(ctx)=> ctx.dataset.type==='line'
              ? `Revenue: ₱${Number(ctx.parsed.y).toLocaleString(undefined,{ minimumFractionDigits:2, maximumFractionDigits:2 })}`
              : `Qty: ${ctx.parsed.y}` } },
            bar3d:{ enabled: true, depth: 10, lift: -6 }
          },
          scales: {
            x:{ ticks:{ color: tickColor }, grid:{ color: gridColor } },
            y:{ position:'left', beginAtZero:true, ticks:{ color: tickColor }, grid:{ color: gridColor } },
            y1:{ position:'right', beginAtZero:true, ticks:{ color: tickColor }, grid:{ drawOnChartArea:false } }
          }
        }
      });

      const spark = new Chart(document.getElementById('salesTrendsChart'), {
        type:'line',
        data:{ labels, datasets:[{ label:'Revenue', data: rev, borderColor:C_RED, backgroundColor:C_RED, borderWidth:2, tension:.35, pointRadius:0, fill:false }] },
        options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false } }, scales:{ x:{ display:false }, y:{ display:false, beginAtZero:true } } }
      });

      showIfEmpty(exp, 'expEmpty');
      const expiryChart = new Chart(document.getElementById('expiryChart'), {
        type:'bar',
        data:{ labels, datasets:[{
          label:'Expiring Items', data: exp,
          backgroundColor: C_YELLOW_30, borderColor: C_YELLOW, borderWidth:2, borderRadius: barRadius,
          topFaceColor:'rgba(245,158,11,.45)', sideFaceColor:'rgba(245,158,11,.28)'
        }]},
        options:{
          responsive:true, maintainAspectRatio:false,
          plugins:{ legend:{ labels:{ color: tickColor } }, title:{ display:true, text:'Expirations This Week', color:'#111827' }, tooltip:{ callbacks:{ label:(ctx)=> `Expiring: ${Number(ctx.parsed.y).toLocaleString()}` } }, bar3d:{ enabled: true, depth: 10, lift: -6 } },
          scales:{ x:{ ticks:{ color: tickColor }, grid:{ color: gridColor } }, y:{ beginAtZero:true, ticks:{ color: tickColor }, grid:{ color: gridColor } } }
        }
      });

      // --- 3D toggles with debounced updates
      const toggleProduction = document.getElementById('toggleProduction');
      const toggleSales = document.getElementById('toggleSales');
      const toggleExpiry = document.getElementById('toggleExpiry');
      const depthRange = document.getElementById('depthRange');
      const liftRange = document.getElementById('liftRange');
      const depthVal = document.getElementById('depthVal');
      const liftVal = document.getElementById('liftVal');
      const dot3d = document.getElementById('dot3d');

      let debounceTimer;
      const debounce = (fn, ms=120) => (...args) => { clearTimeout(debounceTimer); debounceTimer = setTimeout(()=>fn(...args), ms); };

      const apply3D = () => {
        const master = toggle3D?.checked ?? true;
        const depth = Number(depthRange?.value ?? 10);
        const lift  = Number(liftRange?.value ?? -6);
        if (depthVal) depthVal.textContent = depth;
        if (liftVal) liftVal.textContent = lift;
        if (dot3d) dot3d.style.background = master ? 'var(--green)' : 'var(--red)';

        const set = (chart, enabled) => {
          if (!chart) return;
          chart.options.plugins = chart.options.plugins || {};
          chart.options.plugins.bar3d = chart.options.plugins.bar3d || {};
          chart.options.plugins.bar3d.enabled = enabled && master && !prefersReduced;
          chart.options.plugins.bar3d.depth = depth;
          chart.options.plugins.bar3d.lift = lift;
          chart.update();
        };

        set(productionChart, toggleProduction?.checked);
        set(salesChart, toggleSales?.checked);
        set(expiryChart, toggleExpiry?.checked);
      };

      [toggle3D, toggleProduction, toggleSales, toggleExpiry, depthRange, liftRange]
        .forEach(el => el?.addEventListener('input', debounce(apply3D)));

      apply3D();
    });
  </script>
</body>
</html>
