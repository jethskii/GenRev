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
    body{font-family:'Inria Sans',system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;color:var(--text);
      background:linear-gradient(135deg,#1F1E1E 0%,#001C00 100%) fixed!important;min-height:100vh;overflow-x:hidden;}
    body::before{content:'';position:fixed;inset:-50% -50%;width:200%;height:200%;
      background:linear-gradient(to bottom right,rgba(18,108,7,.15) 0%,rgba(113,200,98,.15) 25%,rgba(210,220,50,.12) 50%,rgba(113,200,98,.15) 75%,rgba(10,56,14,.15) 100%);
      transform:rotate(30deg);animation:liquidFlow 15s linear infinite;z-index:-1;opacity:.5;}
    @keyframes liquidFlow{0%{transform:rotate(30deg) translate(-10%,-10%)}50%{transform:rotate(30deg) translate(10%,10%)}100%{transform:rotate(30deg) translate(-10%,-10%)}}
    .glass{background:var(--sidebar)!important;backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);
      border:1px solid var(--dark-line)!important;box-shadow:0 10px 24px rgba(0,0,0,.35)!important;color:var(--text)}
    .bg-navbar{background:var(--navbar)!important;border-bottom:.5px solid var(--dark-line);
      box-shadow:0 8px 26px rgba(0,0,0,.45),0 0 0 1px rgba(255,255,255,.06) inset}
    .brand-title{font-family:'Kalam',cursive;letter-spacing:.02em;text-shadow:-2px 1px 0 var(--brand-green)}
    .text-muted{color:var(--muted)}
    .section-liquid-shine{position:relative}
    .section-liquid-shine::after{content:'';position:absolute;inset:0;background:linear-gradient(45deg,rgba(4,119,5,.10) 0%,rgba(237,209,0,.10) 50%,rgba(4,119,5,.10) 100%);
      border-radius:inherit;animation:cardShine 8s ease infinite;pointer-events:none}
    @keyframes cardShine{0%{opacity:.3}50%{opacity:.1}100%{opacity:.3}}
  </style>
</head>
<body>
  <div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <aside id="sidebar" class="w-64 glass flex-shrink-0 flex flex-col">
      <div class="p-6 text-2xl font-bold tracking-wide border-b border-[var(--dark-line)] flex justify-between items-center">
        GenRev
        <button id="sidebarClose" class="lg:hidden text-xl font-bold">&times;</button>
      </div>

      <!-- User Info -->
      <div class="px-6 pt-4 pb-2">
        <div class="flex items-center space-x-3">
          <div class="w-10 h-10 bg-[var(--sidebar-active)] rounded-full flex items-center justify-center font-bold text-[#1F1E1E]">
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
             class="block px-6 py-3 rounded-r-full transition-all duration-150 hover:bg-[rgba(4,119,5,.24)] {{ request()->routeIs($route . '*') ? 'bg-[var(--sidebar-active)] text-[#1F1E1E] font-bold' : '' }}">
            {{ $label }}
          </a>
        @endforeach
      </nav>

      <div class="p-6 text-xs text-muted border-t border-[var(--dark-line)]">© 2025 GenRev</div>
    </aside>

    <!-- Main Content -->
    <div class="flex flex-col flex-1 overflow-hidden">
      <header class="bg-navbar text-white px-6 py-4 flex justify-between items-center shadow-md">
        <div class="flex items-center space-x-4">
          <button id="sidebarToggle" class="lg:hidden text-2xl">&#9776;</button>
          <h1 class="text-xl font-bold tracking-wide brand-title">Dashboard Overview</h1>
        </div>

        <!-- Controls -->
        <div class="flex flex-wrap items-center gap-4">
          <!-- Master toggle -->
          <label class="flex items-center gap-2 text-xs">
            <input id="toggle3D" type="checkbox" checked class="sr-only peer">
            <span class="px-2 py-1 rounded-full glass border border-white/15">
              <span class="inline-block w-2 h-2 rounded-full align-middle mr-1 peer-checked:bg-emerald-400 bg-red-400"></span>
              3D ON/OFF
            </span>
          </label>

          <!-- Depth -->
          <label class="flex items-center gap-2 text-xs">
            Depth
            <input id="depthRange" type="range" min="0" max="24" value="10" class="w-28 accent-emerald-400">
            <span id="depthVal" class="tabular-nums">10</span>
          </label>

          <!-- Tilt -->
          <label class="flex items-center gap-2 text-xs">
            Tilt
            <input id="liftRange" type="range" min="-16" max="0" value="-6" class="w-28 accent-emerald-400">
            <span id="liftVal" class="tabular-nums">-6</span>
          </label>
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
                ['label' => 'Sales Transactions',    'value' => $totalSales,                             'note' => 'Weekly transactions',       'icon' => '📈'],
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

          {{-- 📊 Sales Report Widget (sparkline) --}}
          <div class="glass section-liquid-shine border border-[var(--dark-line)] shadow-md p-5 rounded-2xl backdrop-blur-lg">
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
          <div class="glass section-liquid-shine border border-[var(--dark-line)] shadow-md p-5 rounded-2xl backdrop-blur-lg">
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
          <div class="glass section-liquid-shine border border-[var(--dark-line)] shadow-md p-5 rounded-2xl backdrop-blur-lg overflow-auto">
            <div class="flex items-center justify-between">
              <div>
                <h2 class="text-base font-semibold mb-1">Recent Sales</h2>
                <p class="text-xs text-[var(--muted)] mb-3">Latest from <strong>weekly product sales</strong></p>
              </div>
              <a href="{{ route('sales') }}" class="text-xs px-3 py-1 rounded-full bg-[var(--sidebar-active)] text-[#1F1E1E] hover:opacity-90 transition">View all</a>
            </div>

            <table class="w-full text-sm text-left border-collapse">
              <thead class="uppercase border-b border-[var(--dark-line)] bg-opacity-20">
                <tr>
                  <th class="py-2 px-3">Product</th>
                  <th class="py-2 px-3">Qty</th>
                  <th class="py-2 px-3">Price</th>
                  <th class="py-2 px-3">Date</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($recentSales as $sale)
                  <tr class="border-t border-[var(--dark-line)] hover:bg-[rgba(255,255,255,.06)] transition">
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

          {{-- 📦 Materials Snapshot --}}
          <div class="glass section-liquid-shine border border-[var(--dark-line)] shadow-md p-5 rounded-2xl backdrop-blur-lg">
            <div class="flex items-center justify-between mb-2">
              <h2 class="text-base font-semibold">Materials Logged (This Week)</h2>
              <span class="text-xs text-[var(--muted)]">On hand: {{ number_format($totalMaterialsWeight, 2) }} kg</span>
            </div>
            @php $recentMaterials = $recentMaterials ?? collect(); @endphp
            @if($recentMaterials->isEmpty())
              <div class="text-sm text-[var(--muted)]">No materials logged this week.</div>
            @else
              <table class="w-full text-sm text-left border-collapse">
                <thead class="uppercase border-b border-[var(--dark-line)] bg-opacity-20">
                  <tr>
                    <th class="py-2 px-3">Material</th>
                    <th class="py-2 px-3">Qty (kg)</th>
                    <th class="py-2 px-3">Date</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($recentMaterials as $m)
                    <tr class="border-t border-[var(--dark-line)] hover:bg-[rgba(255,255,255,.06)] transition">
                      <td class="py-2 px-3">{{ $m->name ?? $m->material_name ?? 'Material' }}</td>
                      <td class="py-2 px-3">{{ number_format($m->quantity_kg, 2) }}</td>
                      <td class="py-2 px-3">{{ \Carbon\Carbon::parse($m->created_at)->format('M d, Y') }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            @endif
          </div>

          {{-- 📈 Expiration Trend (3D BAR + per-chart toggle) --}}
          <div class="glass section-liquid-shine border border-[var(--dark-line)] shadow-md p-5 rounded-2xl backdrop-blur-lg">
            <div class="flex items-center justify-between mb-2">
              <h2 class="text-base font-semibold">Expiration Trend</h2>
              <label class="text-xs flex items-center gap-2">
                <input id="toggleExpiry" type="checkbox" checked class="sr-only peer">
                <span class="px-2 py-1 rounded-full glass border border-white/15">3D</span>
              </label>
            </div>
            <div class="h-56 relative">
              <canvas id="expiryChart" aria-label="Expiration Trend"></canvas>
              <div id="expEmpty" class="absolute inset-0 hidden items-center justify-center text-sm text-[var(--muted)]">No expiries this week</div>
            </div>
          </div>

          {{-- 📊 Weekly Production Chart (3D BAR + per-chart toggle) --}}
          <div class="glass section-liquid-shine border border-[var(--dark-line)] shadow-md p-5 rounded-2xl backdrop-blur-lg">
            <div class="flex items-center justify-between mb-2">
              <h2 class="text-base font-semibold">Weekly Production</h2>
              <label class="text-xs flex items-center gap-2">
                <input id="toggleProduction" type="checkbox" checked class="sr-only peer">
                <span class="px-2 py-1 rounded-full glass border border-white/15">3D</span>
              </label>
            </div>
            <div class="h-56 relative">
              <canvas id="productionChart" aria-label="Weekly Production"></canvas>
              <div id="prodEmpty" class="absolute inset-0 hidden items-center justify-center text-sm text-[var(--muted)]">No data for this week</div>
            </div>
          </div>

          {{-- 💸 Weekly Sales Chart (Qty 3D + Revenue line + per-chart toggle) --}}
          <div class="glass section-liquid-shine border border-[var(--dark-line)] shadow-md p-5 rounded-2xl backdrop-blur-lg">
            <div class="flex items-center justify-between mb-2">
              <h2 class="text-base font-semibold">Weekly Sales</h2>
              <label class="text-xs flex items-center gap-2">
                <input id="toggleSales" type="checkbox" checked class="sr-only peer">
                <span class="px-2 py-1 rounded-full glass border border-white/15">3D (Qty)</span>
              </label>
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

  {{-- 📦 Materials Used --}}
  <div class="mx-8 my-6 glass section-liquid-shine border border-[var(--dark-line)] shadow-md p-5 rounded-2xl backdrop-blur-lg">
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
          <thead class="uppercase border-b border-[var(--dark-line)] bg-opacity-20">
            <tr>
              <th class="py-2 px-3">Material</th>
              <th class="py-2 px-3 text-right">Qty Used</th>
              <th class="py-2 px-3 text-right">Cost</th>
            </tr>
          </thead>
          <tbody>
            @foreach($rows as $r)
              <tr class="border-t border-[var(--dark-line)] hover:bg-[rgba(255,255,255,.06)] transition">
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

  <!-- ===== Chart.js Bar 3D plugin (isometric faces) ===== -->
  <script>
    const Bar3DPlugin = {
      id: 'bar3d',
      afterDatasetDraw(chart, args, pluginOptions) {
        const enabled = (pluginOptions && pluginOptions.enabled) ?? true;
        if (!enabled) return;

        const {ctx, chartArea} = chart;
        const meta = args.meta;
        if (meta.type !== 'bar') return;

        const depth  = pluginOptions?.depth ?? 10;
        const lift   = pluginOptions?.lift ?? -6;

        const shade = (rgba, factor=0.85) => {
          const m = rgba && rgba.toString().match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/i);
          if (!m) return rgba || 'rgba(67,160,71,0.35)';
          const [r,g,b] = [m[1],m[2],m[3]].map(n => Math.max(0, Math.min(255, Math.floor(n*factor))));
          const aMatch = rgba.match(/rgba\(.+,\s*([.\d]+)\)/i);
          const a = aMatch ? parseFloat(aMatch[1]) : 1;
          return `rgba(${r},${g},${b},${a})`;
        };

        const dataset = chart.config.data.datasets[args.index];
        const baseFill   = dataset.backgroundColor || 'rgba(67,160,71,0.35)';
        const baseStroke = dataset.borderColor     || 'rgba(67,160,71,1)';
        const topFill    = dataset.topFaceColor    || shade(baseFill, 1.15);
        const sideFill   = dataset.sideFaceColor   || shade(baseFill, 0.75);
        const topStroke  = dataset.topStrokeColor  || shade(baseStroke, 1.1);
        const sideStroke = dataset.sideStrokeColor || shade(baseStroke, 0.8);

        meta.data.forEach((bar) => {
          const p = bar.getProps(['x','y','base','width'], true);
          if (!p) return;
          const x = p.x - p.width/2;
          const y = p.y;
          const w = p.width;
          const h = Math.max(0, (p.base ?? chartArea.bottom) - y);
          if (!isFinite(h) || h === 0) return;

          const dx = depth, dy = lift;

          const top = [
            {x: x,     y: y},
            {x: x+dx,  y: y+dy},
            {x: x+dx+w,y: y+dy},
            {x: x+w,   y: y},
          ];
          const side = [
            {x: x+w,   y: y},
            {x: x+w+dx,y: y+dy},
            {x: x+w+dx,y: y+dy+h},
            {x: x+w,   y: y+h},
          ];

          const drawPoly = (pts, fill, stroke) => {
            ctx.save();
            ctx.beginPath();
            ctx.moveTo(pts[0].x, pts[0].y);
            for (let j=1;j<pts.length;j++) ctx.lineTo(pts[j].x, pts[j].y);
            ctx.closePath();
            ctx.fillStyle = fill; ctx.fill();
            if (stroke) { ctx.strokeStyle = stroke; ctx.lineWidth = 1; ctx.stroke(); }
            ctx.restore();
          };

          // top face with soft shadow
          ctx.save();
          ctx.shadowColor = 'rgba(0,0,0,0.25)';
          ctx.shadowBlur = 8;
          ctx.shadowOffsetX = 0;
          ctx.shadowOffsetY = 4;
          drawPoly(top, topFill, topStroke);
          ctx.restore();

          // side face
          drawPoly(side, sideFill, sideStroke);
        });
      }
    };
    Chart.register(Bar3DPlugin);
  </script>

  <!-- ===== Dashboard Charts + Toggles ===== -->
  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const userMenuButton = document.getElementById('userMenuButton');
      const userDropdown = document.getElementById('userDropdown');
      const sidebar = document.getElementById('sidebar');
      const sidebarToggle = document.getElementById('sidebarToggle');
      const sidebarClose = document.getElementById('sidebarClose');

      // UI toggles
      const toggle3D = document.getElementById('toggle3D');
      const toggleProduction = document.getElementById('toggleProduction');
      const toggleSales = document.getElementById('toggleSales');
      const toggleExpiry = document.getElementById('toggleExpiry');
      const depthRange = document.getElementById('depthRange');
      const liftRange = document.getElementById('liftRange');
      const depthVal = document.getElementById('depthVal');
      const liftVal = document.getElementById('liftVal');

      // dropdown
      userMenuButton?.addEventListener('click', (e)=>{e.stopPropagation();userDropdown?.classList.toggle('hidden')});
      document.addEventListener('click', (e)=>{
        if (!userMenuButton?.contains(e.target) && !userDropdown?.contains(e.target)) userDropdown?.classList.add('hidden');
      });

      // sidebar mobile
      sidebarToggle?.addEventListener('click', ()=> sidebar?.classList.add('open'));
      sidebarClose?.addEventListener('click', ()=> sidebar?.classList.remove('open'));

      // ==== Data ====
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
      const barRadius = 6;

      const showIfEmpty = (arr, elId) => {
        const el = document.getElementById(elId);
        if (!el) return;
        if (!arr || arr.length === 0 || arr.every(v => Number(v) === 0)) el.classList.remove('hidden');
        else el.classList.add('hidden');
      };

      // keep refs
      const charts = {};

      // Production
      showIfEmpty(prod, 'prodEmpty');
      charts.production = new Chart(document.getElementById('productionChart'), {
        type: 'bar',
        data: { labels, datasets: [{
          label: 'Units Produced', data: prod,
          backgroundColor: barFill, borderColor: barLine, borderWidth: 2, borderRadius: barRadius,
          topFaceColor:'rgba(120,220,140,0.45)', sideFaceColor:'rgba(40,120,50,0.35)'
        }]},
        options: {
          responsive:true, maintainAspectRatio:false,
          plugins: { legend:{ labels:{ color: tick } }, title:{ display:true, text:'Weekly Production', color: title },
                     bar3d:{ enabled: true, depth: Number(depthRange.value), lift: Number(liftRange.value) } },
          scales: { x:{ ticks:{ color: tick }, grid:{ color: grid } }, y:{ beginAtZero:true, ticks:{ color: tick }, grid:{ color: grid } } }
        }
      });

      // Sales (Qty 3D + Revenue line)
      showIfEmpty([...(qty||[]), ...(rev||[])], 'salesEmpty');
      charts.sales = new Chart(document.getElementById('salesChart'), {
        data: {
          labels,
          datasets: [
            { type:'bar', label:'Qty Sold', data: qty, backgroundColor: barFill, borderColor: barLine, borderWidth:2, borderRadius: barRadius,
              yAxisID:'y', topFaceColor:'rgba(120,220,140,0.45)', sideFaceColor:'rgba(40,120,50,0.35)'},
            { type:'line', label:'Revenue', data: rev, borderColor: lineClr, borderWidth:3, tension:.3, pointRadius:3, yAxisID:'y1'}
          ]
        },
        options: {
          responsive:true, maintainAspectRatio:false,
          plugins: { legend:{ labels:{ color: tick } }, title:{ display:true, text:'Weekly Sales', color: title },
            tooltip:{ callbacks:{ label:(ctx)=> ctx.dataset.type==='line'
              ? `Revenue: ₱${Number(ctx.parsed.y).toLocaleString(undefined,{ minimumFractionDigits:2, maximumFractionDigits:2 })}`
              : `Qty: ${ctx.parsed.y}`} },
            bar3d:{ enabled: true, depth: Number(depthRange.value), lift: Number(liftRange.value) } },
          scales: {
            x:{ ticks:{ color: tick }, grid:{ color: grid } },
            y:{ position:'left', beginAtZero:true, ticks:{ color: tick }, grid:{ color: grid } },
            y1:{ position:'right', beginAtZero:true, ticks:{ color: tick }, grid:{ drawOnChartArea:false } }
          }
        }
      });

      // Sparkline
      charts.spark = new Chart(document.getElementById('salesTrendsChart'), {
        type:'line',
        data:{ labels, datasets:[{ label:'Revenue', data: rev, borderColor: lineClr, borderWidth:2, tension:.35, pointRadius:0, fill:false }] },
        options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false } }, scales:{ x:{ display:false }, y:{ display:false, beginAtZero:true } } }
      });

      // Expiration
      showIfEmpty(exp,'expEmpty');
      charts.expiry = new Chart(document.getElementById('expiryChart'), {
        type:'bar',
        data:{ labels, datasets:[{
          label:'Expiring Items', data: exp, backgroundColor:'rgba(237,209,0,0.35)', borderColor: yellow, borderWidth:2, borderRadius: barRadius,
          maxBarThickness: 32, categoryPercentage:.7, barPercentage:.8,
          topFaceColor:'rgba(255,240,120,0.45)', sideFaceColor:'rgba(210,180,0,0.35)'
        }]},
        options:{
          responsive:true, maintainAspectRatio:false,
          plugins:{ legend:{ labels:{ color: tick } }, title:{ display:true, text:'Expirations This Week', color: title },
                   tooltip:{ callbacks:{ label:(ctx)=> `Expiring: ${Number(ctx.parsed.y).toLocaleString()}` } },
                   bar3d:{ enabled: true, depth: Number(depthRange.value), lift: Number(liftRange.value) } },
          scales:{ x:{ ticks:{ color: tick }, grid:{ color: grid } }, y:{ beginAtZero:true, ticks:{ color: tick }, grid:{ color: grid } } }
        }
      });

      // ===== Helper to propagate 3D options =====
      const apply3D = () => {
        const master = toggle3D.checked;
        const depth = Number(depthRange.value);
        const lift  = Number(liftRange.value);
        depthVal.textContent = depth;
        liftVal.textContent = lift;

        const flags = {
          production: master && toggleProduction.checked,
          sales:      master && toggleSales.checked,
          expiry:     master && toggleExpiry.checked
        };

        Object.entries(flags).forEach(([key, enabled]) => {
          const c = charts[key]; if (!c) return;
          c.options.plugins = c.options.plugins || {};
          c.options.plugins.bar3d = c.options.plugins.bar3d || {};
          c.options.plugins.bar3d.enabled = enabled;
          c.options.plugins.bar3d.depth = depth;
          c.options.plugins.bar3d.lift = lift;
          c.update();
        });
      };

      // Wire events
      [toggle3D, toggleProduction, toggleSales, toggleExpiry, depthRange, liftRange]
        .forEach(el => el?.addEventListener('input', apply3D));

      // initial
      apply3D();
    });
  </script>
</body>
</html>
