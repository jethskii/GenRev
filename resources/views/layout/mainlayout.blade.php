<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta
    name="viewport"
    content="width=device-width, initial-scale=1, viewport-fit=cover" />
  <title>GenRev Admin Dashboard</title>
  <meta name="csrf-token" content="{{ csrf_token() }}"> {{-- helpful for AJAX --}}

  <!-- Tailwind (CDN) -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Fonts (Liquid UI) -->
  <link href="https://fonts.googleapis.com/css2?family=Kalam:wght@400;700&family=Inria+Sans:wght@300;400;700&display=swap" rel="stylesheet">

  @yield('head') {{-- pages can inject extra <style> or tags --}}

  <!-- Liquid + Glass Theme -->
  <style>
    :root{
      --navbar: linear-gradient(90deg, #1F1E1E, #100E00);
      --sidebar: rgba(255,255,255,0.08);
      --sidebar-hover: rgba(237,209,0,0.08);
      --sidebar-active: #C3E956;
      --dark-line: rgba(255,255,255,0.2);
      --text-dark: #1F1E1E;
    }

    /* Base */
    html, body { height: 100%; }
    body{
      background: linear-gradient(135deg, #1F1E1E 0%, #001C00 100%);
      min-height: 100vh;
      font-family: 'Inria Sans', system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
      color: #fff;
      overflow-x: hidden;
    }
    body::before{
      content:'';
      position:fixed; inset:-50%;
      background: linear-gradient(
        to bottom right,
        rgba(18,108,7,0.15) 0%,
        rgba(113,200,98,0.15) 25%,
        rgba(210,220,50,0.12) 50%,
        rgba(113,200,98,0.15) 75%,
        rgba(10,56,14,0.15) 100%
      );
      transform: rotate(30deg);
      animation: liquidFlow 15s linear infinite;
      z-index:-1; opacity:.5;
      pointer-events: none;
    }
    @keyframes liquidFlow{
      0%  { transform: rotate(30deg) translate(-10%, -10%); }
      50% { transform: rotate(30deg) translate( 10%,  10%); }
      100%{ transform: rotate(30deg) translate(-10%, -10%); }
    }
    @media (prefers-reduced-motion: reduce){
      body::before{ animation: none; }
    }

    /* Components */
    .glass{
      background: rgba(255,255,255,0.08);
      backdrop-filter: blur(14px);
      -webkit-backdrop-filter: blur(14px);
      border: 1px solid var(--dark-line);
      box-shadow: 0 10px 24px rgba(0,0,0,0.25);
    }
    .bg-navbar{ background: var(--navbar); border-bottom: 1px solid var(--dark-line); }
    .bg-sidebar{ background-color: var(--sidebar); }
    .bg-sidebar-hover:hover{ background-color: var(--sidebar-hover); }
    .bg-sidebar-active{
      background-color: var(--sidebar-active);
      color:#1F1E1E; font-weight:600; position:relative;
    }
    .bg-sidebar-active::before{
      content:''; position:absolute; left:0; top:0; width:4px; height:100%;
      background:#91EAAF; border-radius:0 4px 4px 0;
    }
    .border-dark-line{ border-color: var(--dark-line); }

    .nav-link{ position:relative; overflow:hidden; transition: all .3s ease; }
    .nav-link::before{
      content:''; position:absolute; bottom:0; left:50%; transform:translateX(-50%);
      width:0; height:2px; background: linear-gradient(90deg, transparent, #EDD100, transparent);
      transition: width .3s ease;
    }
    .nav-link:hover::before{ width:100%; }
    .nav-link::after{
      content:''; position:absolute; top:50%; left:50%;
      width:5px; height:5px; background: rgba(237,209,0,0.4); opacity:0; border-radius:9999px;
      transform: scale(1) translate(-50%);
      transition: all .6s ease;
    }
    .nav-link:hover::after{ animation: ripple 1s ease-out; }
    @keyframes ripple{ 0%{ transform: scale(0); opacity:.4; } 100%{ transform: scale(20); opacity:0; } }

    /* Toggle */
    .liquid-toggle-switch{ position:relative; display:inline-block; width:28px; height:50px; cursor:pointer; }
    .liquid-toggle-switch input{ opacity:0; width:0; height:0; }
    .liquid-slider{
      position:absolute; inset:0; border-radius:25px;
      background: linear-gradient(180deg, #1F1E1E 0%, #001C00 100%);
      border:1px solid rgba(255,255,255,0.2); transition: all .3s ease;
      box-shadow: 0 4px 15px rgba(0,28,0,0.3);
      display:grid; place-items:center;
    }
    .liquid-slider::before{
      content:''; position:absolute; left:4px; bottom:4px; height:20px; width:20px; border-radius:9999px;
      background: linear-gradient(90deg, #047705 0%, #0aad0a 100%);
      box-shadow: 0 2px 8px rgba(4,119,5,0.4); transition: all .3s ease;
    }
    .liquid-toggle-switch input:checked + .liquid-slider{
      background: linear-gradient(180deg, #ffffff 0%, #eaeaea 100%);
      border: 1px solid rgba(4,119,5,0.3);
      color:#1F1E1E;
    }
    .liquid-toggle-switch input:checked + .liquid-slider::before{ transform: translateY(-26px); }
    .slider-icon{ font-size:12px; }

    /* Light mode */
    .light-mode{ background: linear-gradient(135deg, #ffffff 0%, #f0f0f0 100%) !important; color:#1F1E1E; }
    .light-mode .glass{
      background: rgba(255,255,255,0.9); color:#1F1E1E;
      border:1px solid rgba(31,30,30,0.2); box-shadow: 0 10px 24px rgba(0,0,0,0.08);
    }
    .light-mode .bg-navbar{ background: linear-gradient(90deg, #ffffff, #eaeaea); border-bottom: 1px solid rgba(0,0,0,0.1); }
    .light-mode .bg-sidebar{ background: rgba(255,255,255,0.75); }
    .light-mode .bg-sidebar-hover:hover{ background: rgba(4,119,5,0.08); }
    .light-mode .bg-sidebar-active{ background:#C3E956; color:#1F1E1E; }
    .light-mode .border-dark-line{ border-color: rgba(0,0,0,0.1); }

    .brand-title{ font-family:'Kalam', cursive; text-shadow: -2px 1px 0px #047705; }

    /* Dialog defaults (global so every page works) */
    dialog.modal{ border:0; padding:0; background:transparent; z-index:60; }
    dialog::backdrop{ background:rgba(0,0,0,.65); -webkit-backdrop-filter: blur(3px); backdrop-filter: blur(3px); }
    dialog[open]{ display:block; }
    .modal-box{ transform:translateY(8px) scale(.985); opacity:0; transition:.18s ease; }
    dialog[open] .modal-box{ transform:translateY(0) scale(1); opacity:1; }

    /* Responsive sidebar */
    @media (max-width:1024px){
      aside{ position:fixed; z-index:50; transform:translateX(-100%); transition: transform .3s ease; }
      aside.open{ transform: translateX(0); }
    }

    /* Skip link */
    .skip-link{
      position:absolute; left:50%; transform:translateX(-50%);
      top:-40px; background:#000; color:#fff; padding:.5rem .75rem; border-radius:.5rem;
      transition: top .2s ease; z-index:100;
    }
    .skip-link:focus{ top:.5rem; outline: 2px solid #C3E956; }
  </style>
</head>
<body>
  <a href="#main" class="skip-link">Skip to content</a>

  <div class="flex min-h-screen overflow-hidden">
    <!-- Sidebar -->
    <aside id="sidebar" class="w-64 glass text-white flex-shrink-0 flex flex-col" aria-label="Primary">
      <div class="p-6 text-2xl font-bold tracking-wide border-b border-dark-line flex items-center justify-between">
        <span class="brand-title">GenRev</span>
        <button id="sidebarClose" class="lg:hidden text-2xl leading-none" aria-label="Close sidebar">&times;</button>
      </div>

      <!-- User Info -->
      <div class="px-6 pt-4 pb-2">
        <div class="flex items-center space-x-3">
          <div class="w-10 h-10 bg-[#C3E956] rounded-full flex items-center justify-center font-bold text-[#1F1E1E]">
            {{ Auth::check() ? strtoupper(substr(Auth::user()->name, 0, 1)) : '?' }}
          </div>
          <div class="text-sm">
            <p class="font-semibold">{{ Auth::check() ? Auth::user()->name : 'Guest' }}</p>
            <p class="text-xs text-gray-300 capitalize">{{ Auth::check() && Auth::user()->role ? Auth::user()->role : 'Admin' }}</p>
          </div>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 mt-4 space-y-1 text-sm font-medium">
        <a href="{{ route('dashboard') }}"
           class="nav-link block px-6 py-3 rounded-r-full transition-all hover:bg-sidebar-hover
           {{ request()->routeIs('dashboard*') ? 'bg-sidebar-active text-[#1F1E1E]' : 'text-white' }}">
          Dashboard
        </a>

        <div class="mx-6 my-2 border-t border-dark-line"></div>

        <a href="{{ route('production.index') }}"
           class="nav-link block px-6 py-3 rounded-r-full transition-all hover:bg-sidebar-hover
           {{ request()->routeIs('production.*') ? 'bg-sidebar-active text-[#1F1E1E]' : 'text-white' }}">
          Production
        </a>

        <a href="{{ route('sales.index') }}"
           class="nav-link block px-6 py-3 rounded-r-full transition-all hover:bg-sidebar-hover
           {{ request()->routeIs('sales.*') ? 'bg-sidebar-active text-[#1F1E1E]' : 'text-white' }}">
          Sales
        </a>

        <a href="{{ route('inventory.index') }}"
           class="nav-link block px-6 py-3 rounded-r-full transition-all hover:bg-sidebar-hover
           {{ request()->routeIs('inventory.*') ? 'bg-sidebar-active text-[#1F1E1E]' : 'text-white' }}">
          Inventory
        </a>

        <a href="{{ route('products.index') }}"
           class="nav-link block px-6 py-3 rounded-r-full transition-all hover:bg-sidebar-hover
           {{ request()->routeIs('products.*') ? 'bg-sidebar-active text-[#1F1E1E]' : 'text-white' }}">
          Products
        </a>

        <a href="{{ route('materials.index') }}"
           class="nav-link block px-6 py-3 rounded-r-full transition-all hover:bg-sidebar-hover
           {{ request()->routeIs('materials.*') && !request()->routeIs('products.materials.*') ? 'bg-sidebar-active text-[#1F1E1E]' : 'text-white' }}">
          Materials
        </a>

        <a href="{{ route('employee.index') }}"
           class="nav-link block px-6 py-3 rounded-r-full transition-all hover:bg-sidebar-hover
           {{ request()->routeIs('employee.*') ? 'bg-sidebar-active text-[#1F1E1E]' : 'text-white' }}">
          Employee
        </a>

        <a href="{{ route('settings.index') }}"
           class="nav-link block px-6 py-3 rounded-r-full transition-all hover:bg-sidebar-hover
           {{ request()->routeIs('settings.*') ? 'bg-sidebar-active text-[#1F1E1E]' : 'text-white' }}">
          Settings
        </a>

        @if(request()->routeIs('products.materials.*'))
          <div class="px-6 pt-2">
            <a href="{{ route('products.index') }}" class="text-xs text-[#C3E956] hover:underline">← Back to Products</a>
          </div>
        @endif
      </nav>

      <div class="p-6 text-xs text-gray-300 mt-auto">© {{ now()->year }} GenRev</div>
    </aside>

    <!-- Main Content -->
    <div class="flex flex-col flex-1 overflow-hidden">
      <header class="bg-navbar text-white px-6 py-4 flex justify-between items-center shadow-lg border-b border-dark-line" role="banner">
        <div class="flex items-center gap-4">
          <button id="sidebarToggle" class="lg:hidden text-2xl" aria-label="Open sidebar">&#9776;</button>
          <h1 class="text-xl font-bold tracking-wide">Dashboard Overview</h1>
        </div>

        <div class="flex items-center gap-4">
          <!-- Theme Toggle -->
          <div class="theme-toggle relative" title="Toggle Light/Dark Mode">
            <label class="liquid-toggle-switch">
              <input type="checkbox" id="themeToggle" aria-label="Toggle theme">
              <span class="liquid-slider">
                <span class="slider-icon">🌙</span>
              </span>
            </label>
          </div>

          <!-- User Menu -->
          <div class="relative z-30">
            <button id="userMenuButton" class="focus:outline-none" aria-haspopup="menu" aria-expanded="false">
              <div class="w-9 h-9 rounded-full bg-[#91EAAF] flex items-center justify-center font-bold uppercase text-sm text-[#1F1E1E]">
                {{ Auth::check() ? strtoupper(substr(Auth::user()->name, 0, 1)) : '?' }}
              </div>
            </button>
            <div id="userDropdown" class="hidden absolute right-0 mt-2 w-48 glass rounded-lg shadow-xl text-white border border-dark-line overflow-hidden" role="menu" aria-label="User menu">
              <div class="px-4 py-3 border-b border-dark-line text-sm">
                Logged in as<br>
                <span class="font-semibold">{{ Auth::check() ? Auth::user()->name : 'Guest' }}</span>
                @if(Auth::check() && Auth::user()->role)
                  <div class="text-xs text-gray-300 capitalize">({{ Auth::user()->role }})</div>
                @endif
              </div>
              <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full text-left px-4 py-2 text-sm hover:bg-white/10 transition-colors" role="menuitem">
                  Logout
                </button>
              </form>
            </div>
          </div>
        </div>
      </header>

      <main id="main" class="flex-1 overflow-y-auto p-8" role="main" tabindex="-1">
        @yield('content')
      </main>
    </div>
  </div>

  {{-- IMPORTANT: Use stacks so pages can @push('scripts') (charts, modals, etc.) --}}
  @stack('scripts')

  <script>
    // Global helpers for dialogs (works on ANY page using data-open / data-close)
    (function () {
      const byId = (id) => document.getElementById(id);

      function openDialog(dlg) {
        if (!dlg) return;
        try { dlg.showModal(); }
        catch { dlg.setAttribute('open', 'open'); }
        const first = dlg.querySelector('input, select, textarea, button');
        if (first) setTimeout(() => first.focus(), 40);
      }

      function closeDialog(dlg) {
        if (!dlg) return;
        try { dlg.close(); }
        catch { dlg.removeAttribute('open'); }
      }

      // Global delegation (open / close)
      document.addEventListener('click', (e) => {
        const openTarget = e.target.closest('[data-open]');
        const closeTarget = e.target.closest('[data-close]');
        const clickedDialogShell = e.target instanceof Element && e.target.matches('dialog[open]');

        if (openTarget) {
          const id = openTarget.getAttribute('data-open');
          const dlg = byId(id);
          if (!dlg) { console.warn('Dialog not found:', id); return; }
          openDialog(dlg);
          e.preventDefault(); e.stopPropagation();
          return;
        }
        if (closeTarget) {
          const dlg = closeTarget.closest('dialog');
          closeDialog(dlg);
          e.preventDefault(); e.stopPropagation();
          return;
        }
        // Click outside modal-box closes
        if (clickedDialogShell) {
          closeDialog(e.target);
          e.preventDefault(); e.stopPropagation();
        }
      }, { capture: true });

      // Keyboard helpers (Esc, Ctrl/Cmd+Enter)
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
          document.querySelectorAll('dialog[open]').forEach(closeDialog);
        }
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'enter') {
          const openDlg = document.querySelector('dialog[open]');
          openDlg?.querySelector('form')?.requestSubmit();
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

      // Theme toggle (persisted)
      const themeToggle = document.getElementById('themeToggle');
      const sliderIcon  = document.querySelector('.slider-icon');
      const saved = localStorage.getItem('theme');
      if (saved === 'light'){
        document.body.classList.add('light-mode');
        if (themeToggle) themeToggle.checked = true;
        if (sliderIcon) sliderIcon.textContent = '☀️';
      }
      themeToggle?.addEventListener('change', function(){
        if (this.checked){
          document.body.classList.add('light-mode');
          if (sliderIcon) sliderIcon.textContent = '☀️';
          localStorage.setItem('theme', 'light');
        } else {
          document.body.classList.remove('light-mode');
          if (sliderIcon) sliderIcon.textContent = '🌙';
          localStorage.setItem('theme', 'dark');
        }
      });
    });
  </script>
</body>
</html>
