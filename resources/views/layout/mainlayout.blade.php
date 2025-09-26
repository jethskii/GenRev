<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
  <title>GenRev Admin Dashboard</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <!-- Tailwind (CDN) -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Kalam:wght@400;700&family=Inria+Sans:wght@300;400;700&display=swap" rel="stylesheet">

  @yield('head') {{-- pages can inject extra <style> or tags --}}

  <style>
    /* =========================
       THEME TOKENS (Light default)
       ========================= */
    :root{
      /* Accents */
      --accent-red:    #ef4444;  /* red-500 */
      --accent-green:  #22c55e;  /* green-500 */
      --accent-blue:   #3b82f6;  /* blue-500 */
      --accent-yellow: #f59e0b;  /* amber-500 */

      /* Layout */
      --bg-page:   #f7f8fb;      /* off-white page background */
      --bg-card:   #ffffff;      /* white panels/cards */
      --text:      #111827;      /* gray-900 */
      --text-sub:  #6b7280;      /* gray-500 */
      --line:      #e5e7eb;      /* gray-200 */

      /* Sidebar */
      --sidebar-bg:    #ffffff;
      --sidebar-hover: #f3f4f6;  /* gray-100 */
      --sidebar-active:#fee2e2;  /* red-100 */

      /* Avatar pill */
      --brand-pill:    #22c55e;

      /* Charts (R/G/Y/B) */
      --chart-1: var(--accent-red);
      --chart-2: var(--accent-green);
      --chart-3: var(--accent-yellow);
      --chart-4: var(--accent-blue);
    }

    /* =========================
       DARK MODE (optional toggle)
       ========================= */
    body.dark-mode{
      --bg-page:   linear-gradient(135deg, #1F1E1E 0%, #001C00 100%);
      --bg-card:   rgba(31,30,30,.92);
      --text:      #ffffff;
      --text-sub:  #cbd5e1;
      --line:      rgba(255,255,255,.15);

      --sidebar-bg:    rgba(255,255,255,0.08);
      --sidebar-hover: rgba(255,255,255,0.12);
      --sidebar-active:#0f172a;
      --brand-pill:    #91EAAF;
    }

    /* =========================
       BASE / SURFACES
       ========================= */
    html, body { height: 100%; }
    body{
      min-height: 100vh;
      font-family: 'Inria Sans', system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
      color: var(--text);
      background: var(--bg-page);
      overflow-x: hidden;
    }

    .card{
      background: var(--bg-card);
      border: 1px solid var(--line);
      border-radius: 1rem;
      box-shadow: 0 8px 20px rgba(0,0,0,.05);
    }

    /* =========================
       HEADER (white + red/green accent)
       ========================= */
    .header-bar{
      background: #ffffff;
      border-bottom: 1px solid var(--line);
      position: relative;
    }
    .header-bar::after{
      content:'';
      position:absolute; left:0; bottom:0; height:3px; width:100%;
      background: linear-gradient(90deg, var(--accent-red), var(--accent-green));
    }

    /* =========================
       SIDEBAR (light)
       ========================= */
    .sidebar{
      background: var(--sidebar-bg);
      border-right: 1px solid var(--line);
      color: var(--text);
    }
    .nav-link{
      display:block; padding:.75rem 1.5rem; border-radius: 9999px 0 0 9999px;
      transition: background-color .2s ease, color .2s ease;
      color: var(--text);
    }
    .nav-link:hover{ background: var(--sidebar-hover); }
    .nav-active{
      background: var(--sidebar-active);
      color:#991b1b; /* red-800 for active */
      font-weight:700;
      position: relative;
    }
    .nav-active::before{
      content:''; position:absolute; left:0; top:0; height:100%; width:4px; border-radius:0 4px 4px 0;
      background: var(--accent-green);
    }

    /* =========================
       BUTTONS (shared)
       ========================= */
    .btn{ font-weight:600; border-radius:.75rem; padding:.5rem .9rem; border:1px solid transparent; }
    .btn:disabled{ opacity:.6; cursor:not-allowed; }

    /* Primary (RED) */
    .btn-primary{
      background: var(--accent-red); color:#fff;
      box-shadow: 0 6px 14px rgba(239,68,68,.25);
    }
    .btn-primary:hover{ filter: brightness(1.05); }

    /* Secondary (GREEN) */
    .btn-secondary-green{
      background:#ecfdf5; color:#065f46; border-color:#a7f3d0; border-width:1px;
    }
    .btn-secondary-green:hover{ background:#d1fae5; }

    /* Secondary (BLUE) */
    .btn-secondary-blue{
      background:#eff6ff; color:#1e40af; border-color:#bfdbfe; border-width:1px;
    }
    .btn-secondary-blue:hover{ background:#dbeafe; }

    .btn-ghost{
      background:#f3f4f6; color:#374151; border:1px solid var(--line);
    }
    .btn-ghost:hover{ background:#e5e7eb; }

    /* =========================
       USER DROPDOWN (light)
       ========================= */
    .dropdown{
      background:#ffffff; color: var(--text);
      border:1px solid var(--line);
      box-shadow: 0 16px 32px rgba(0,0,0,.08);
      border-radius:.75rem;
      overflow:hidden;
    }

    /* =========================
       RESPONSIVE SIDEBAR
       ========================= */
    @media (max-width:1024px){
      aside{ position:fixed; z-index:50; transform:translateX(-100%); transition: transform .3s ease; }
      aside.open{ transform: translateX(0); }
    }

    /* =========================
       CHART PALETTE HELPERS
       ========================= */
    .chart-palette{
      --c1: var(--chart-1);
      --c2: var(--chart-2);
      --c3: var(--chart-3);
      --c4: var(--chart-4);
    }

    /* Accessibility: skip link */
    .skip-link{
      position:absolute; left:50%; transform:translateX(-50%);
      top:-40px; background:#000; color:#fff; padding:.5rem .75rem; border-radius:.5rem;
      transition: top .2s ease; z-index:100;
    }
    .skip-link:focus{ top:.5rem; outline: 2px solid var(--accent-green); }
  </style>
</head>
<body class="chart-palette">
  <a href="#main" class="skip-link">Skip to content</a>

  <div class="flex min-h-screen overflow-hidden">
    <!-- Sidebar -->
    <aside id="sidebar" class="w-64 sidebar flex-shrink-0 flex flex-col" aria-label="Primary">
      <div class="px-6 py-5 text-2xl font-bold tracking-wide border-b" style="border-color:var(--line)">
        <span style="font-family:'Kalam',cursive">GenRev</span>
        <button id="sidebarClose" class="lg:hidden text-2xl leading-none float-right" aria-label="Close sidebar">&times;</button>
      </div>

      <!-- User Info -->
      <div class="px-6 pt-4 pb-2">
        <div class="flex items-center space-x-3">
          <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-white"
               style="background: var(--brand-pill);">
            {{ Auth::check() ? strtoupper(substr(Auth::user()->name, 0, 1)) : '?' }}
          </div>
          <div class="text-sm">
            <p class="font-semibold">{{ Auth::check() ? Auth::user()->name : 'Guest' }}</p>
            <p class="text-xs text-gray-500 capitalize">{{ Auth::check() && Auth::user()->role ? Auth::user()->role : 'Admin' }}</p>
          </div>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 mt-4 space-y-1 text-sm font-medium">
        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard*') ? 'nav-active' : '' }}">Dashboard</a>

        <div class="mx-6 my-2 border-t" style="border-color:var(--line)"></div>

        <a href="{{ route('production.index') }}" class="nav-link {{ request()->routeIs('production.*') ? 'nav-active' : '' }}">Production</a>
        <a href="{{ route('sales.index') }}" class="nav-link {{ request()->routeIs('sales.*') ? 'nav-active' : '' }}">Sales</a>
        <a href="{{ route('inventory.index') }}" class="nav-link {{ request()->routeIs('inventory.*') ? 'nav-active' : '' }}">Inventory</a>
        <a href="{{ route('products.index') }}" class="nav-link {{ request()->routeIs('products.*') ? 'nav-active' : '' }}">Products</a>
        <a href="{{ route('materials.index') }}" class="nav-link {{ request()->routeIs('materials.*') && !request()->routeIs('products.materials.*') ? 'nav-active' : '' }}">Materials</a>
        <a href="{{ route('employee.index') }}" class="nav-link {{ request()->routeIs('employee.*') ? 'nav-active' : '' }}">Employee</a>
        <a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings.*') ? 'nav-active' : '' }}">Settings</a>

        @if(request()->routeIs('products.materials.*'))
          <div class="px-6 pt-2">
            <a href="{{ route('products.index') }}" class="text-xs" style="color:var(--accent-green)">← Back to Products</a>
          </div>
        @endif
      </nav>

      <div class="p-6 text-xs text-gray-500 mt-auto">© {{ now()->year }} GenRev</div>
    </aside>

    <!-- Main Content -->
    <div class="flex flex-col flex-1 overflow-hidden">
      <header class="header-bar px-6 py-4 flex justify-between items-center" role="banner">
        <div class="flex items-center gap-4">
          <button id="sidebarToggle" class="lg:hidden text-2xl" aria-label="Open sidebar">&#9776;</button>
          <h1 class="text-xl font-bold tracking-wide text-gray-900">Dashboard Overview</h1>
        </div>

        <div class="flex items-center gap-4">
          <!-- Theme Toggle -->
          <div class="relative" title="Toggle Light/Dark Mode">
            <label class="inline-flex items-center gap-2 cursor-pointer">
              <input type="checkbox" id="themeToggle" class="sr-only" aria-label="Toggle theme">
              <span class="px-3 py-1 rounded-full text-sm font-medium border"
                    style="border-color:var(--line); color:#111827; background:#f9fafb">Light</span>
            </label>
          </div>

          <!-- User Menu -->
          <div class="relative z-30">
            <button id="userMenuButton" class="focus:outline-none" aria-haspopup="menu" aria-expanded="false">
              <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold uppercase text-sm text-white"
                   style="background: var(--accent-green);">
                {{ Auth::check() ? strtoupper(substr(Auth::user()->name, 0, 1)) : '?' }}
              </div>
            </button>
            <div id="userDropdown" class="hidden absolute right-0 mt-2 w-56 dropdown" role="menu" aria-label="User menu">
              <div class="px-4 py-3 border-b" style="border-color:var(--line)">
                <div class="text-xs text-gray-500">Logged in as</div>
                <div class="font-semibold">{{ Auth::check() ? Auth::user()->name : 'Guest' }}</div>
                @if(Auth::check() && Auth::user()->role)
                  <div class="text-xs text-gray-500 capitalize">({{ Auth::user()->role }})</div>
                @endif
              </div>
              <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full text-left px-4 py-2 text-sm hover:bg-gray-50 transition-colors" role="menuitem">
                  Logout
                </button>
              </form>
            </div>
          </div>
        </div>
      </header>

      <main id="main" class="flex-1 overflow-y-auto p-8" role="main" tabindex="-1">
        <div class="card p-6">
          @yield('content')
        </div>
      </main>
    </div>
  </div>

  {{-- IMPORTANT: Use stacks so pages can @push('scripts') --}}
  @stack('scripts')

  <script>
    // Dialog helpers (open/close via data-open / data-close)
    (function () {
      const byId = (id) => document.getElementById(id);
      function openDialog(dlg) { if (!dlg) return; try { dlg.showModal(); } catch { dlg.setAttribute('open','open'); } }
      function closeDialog(dlg){ if (!dlg) return; try { dlg.close(); } catch { dlg.removeAttribute('open'); } }

      document.addEventListener('click', (e) => {
        const openTarget = e.target.closest('[data-open]');
        const closeTarget = e.target.closest('[data-close]');
        const clickedDialogShell = e.target instanceof Element && e.target.matches('dialog[open]');
        if (openTarget) { openDialog(byId(openTarget.getAttribute('data-open'))); e.preventDefault(); e.stopPropagation(); return; }
        if (closeTarget){ closeDialog(closeTarget.closest('dialog')); e.preventDefault(); e.stopPropagation(); return; }
        if (clickedDialogShell){ closeDialog(e.target); e.preventDefault(); e.stopPropagation(); }
      }, { capture:true });

      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') document.querySelectorAll('dialog[open]').forEach(closeDialog);
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'enter') {
          const openDlg = document.querySelector('dialog[open]'); openDlg?.querySelector('form')?.requestSubmit();
        }
      });
    })();

    // Layout interactions
    document.addEventListener("DOMContentLoaded", () => {
      // User dropdown
      const userMenuBtn = document.getElementById('userMenuButton');
      const userDropdown = document.getElementById('userDropdown');
      userMenuBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        const expanded = userMenuBtn.getAttribute('aria-expanded') === 'true';
        userMenuBtn.setAttribute('aria-expanded', String(!expanded));
        userDropdown?.classList.toggle('hidden');
      });
      document.addEventListener('click', (e) => {
        if (!userMenuBtn?.contains(e.target) && !userDropdown?.contains(e.target)) {
          userDropdown?.classList.add('hidden');
          userMenuBtn?.setAttribute('aria-expanded', 'false');
        }
      });

      // Sidebar (mobile)
      const sidebar = document.getElementById('sidebar');
      document.getElementById('sidebarToggle')?.addEventListener('click', () => sidebar?.classList.add('open'));
      document.getElementById('sidebarClose')?.addEventListener('click',  () => sidebar?.classList.remove('open'));

      // Theme toggle (persist)
      const themeToggle = document.getElementById('themeToggle');
      const saved = localStorage.getItem('theme');
      if (saved === 'dark'){ document.body.classList.add('dark-mode'); themeToggle.checked = true; }
      themeToggle?.addEventListener('change', function(){
        if (this.checked){ document.body.classList.add('dark-mode'); localStorage.setItem('theme','dark'); }
        else { document.body.classList.remove('dark-mode'); localStorage.setItem('theme','light'); }
      });
    });

    /* OPTIONAL: expose button classes */
    window.btnClasses = {
      primary: 'btn btn-primary',
      secondaryGreen: 'btn btn-secondary-green',
      secondaryBlue: 'btn btn-secondary-blue',
      ghost: 'btn btn-ghost'
    };
  </script>
</body>
</html>
