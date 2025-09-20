<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>GenRev Admin Dashboard</title>
  <meta name="csrf-token" content="{{ csrf_token() }}"> {{-- helpful for AJAX --}}

  <!-- Tailwind -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Fonts (Liquid UI) -->
  <link href="https://fonts.googleapis.com/css2?family=Kalam:wght@400;700&family=Inria+Sans:wght@300;400;700&display=swap" rel="stylesheet">

  @yield('head') {{-- allow pages to push extra <style> or tags --}}

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
    body{
      background: linear-gradient(135deg, #1F1E1E 0%, #001C00 100%);
      min-height: 100vh;
      font-family: 'Inria Sans', sans-serif;
      color: #fff;
      overflow-x: hidden;
    }
    body::before{
      content:''; position:fixed; top:-50%; left:-50%;
      width:200%; height:200%;
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
    }
    @keyframes liquidFlow{
      0%{ transform: rotate(30deg) translate(-10%, -10%);}
      50%{ transform: rotate(30deg) translate(10%, 10%);}
      100%{ transform: rotate(30deg) translate(-10%, -10%);}
    }
    .glass{ background: rgba(255,255,255,0.08); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); border: 1px solid var(--dark-line); box-shadow: 0 10px 24px rgba(0,0,0,0.25); }
    .bg-navbar{ background: var(--navbar); border-bottom: 1px solid var(--dark-line); }
    .bg-sidebar{ background-color: var(--sidebar); }
    .bg-sidebar-hover:hover{ background-color: var(--sidebar-hover); }
    .bg-sidebar-active{ background-color: var(--sidebar-active); color:#1F1E1E; font-weight:600; position:relative; }
    .bg-sidebar-active::before{ content:''; position:absolute; left:0; top:0; width:4px; height:100%; background:#91EAAF; border-radius:0 4px 4px 0; }
    .border-dark-line{ border-color: var(--dark-line); }
    .nav-link{ position:relative; overflow:hidden; transition: all .4s ease;}
    .nav-link::before{ content:''; position:absolute; bottom:0; left:50%; transform:translateX(-50%); width:0; height:2px; background: linear-gradient(90deg, transparent, #EDD100, transparent); transition: width .4s ease; }
    .nav-link:hover::before{ width:100%; }
    .nav-link::after{ content:''; position:absolute; top:50%; left:50%; width:5px; height:5px; background: rgba(237,209,0,0.4); opacity:0; border-radius:9999px; transform: scale(1) translate(-50%); transition: all .6s ease; }
    .nav-link:hover::after{ animation: ripple 1s ease-out; }
    @keyframes ripple{ 0%{ transform: scale(0); opacity:.4; } 100%{ transform: scale(20); opacity:0; } }
    .liquid-toggle-switch{ position:relative; display:inline-block; width:28px; height:50px; cursor:pointer; }
    .liquid-toggle-switch input{ opacity:0; width:0; height:0; }
    .liquid-slider{ position:absolute; inset:0; border-radius:25px; background: linear-gradient(180deg, #1F1E1E 0%, #001C00 100%); border:1px solid rgba(255,255,255,0.2); transition: all .4s ease; box-shadow: 0 4px 15px rgba(0,28,0,0.3); }
    .liquid-slider::before{ content:''; position:absolute; left:4px; bottom:4px; height:20px; width:20px; border-radius:9999px; background: linear-gradient(90deg, #047705 0%, #0aad0a 100%); box-shadow: 0 2px 8px rgba(4,119,5,0.4); transition: all .4s ease; }
    .liquid-toggle-switch input:checked + .liquid-slider{ background: linear-gradient(180deg, #ffffff 0%, #e0e0e0 100%); border: 1px solid rgba(4,119,5,0.3);}
    .liquid-toggle-switch input:checked + .liquid-slider::before{ transform: translateY(-26px); }
    .slider-icon{ position:absolute; top:50%; left:50%; transform: translate(-50%,-50%); font-size:12px; color:#fff; transition: color .4s ease; }
    .liquid-toggle-switch input:checked + .liquid-slider .slider-icon{ color:#1F1E1E; }
    .light-mode{ background: linear-gradient(135deg, #ffffff 0%, #f0f0f0 100%) !important; color:#1F1E1E; }
    .light-mode .glass{ background: rgba(255,255,255,0.9); color:#1F1E1E; border:1px solid rgba(31,30,30,0.2); box-shadow: 0 10px 24px rgba(0,0,0,0.08); }
    .light-mode .bg-navbar{ background: linear-gradient(90deg, #ffffff, #eaeaea); border-bottom: 1px solid rgba(0,0,0,0.1);}
    .light-mode .bg-sidebar{ background: rgba(255,255,255,0.75); }
    .light-mode .bg-sidebar-hover:hover{ background: rgba(4,119,5,0.08); }
    .light-mode .bg-sidebar-active{ background:#C3E956; color:#1F1E1E; }
    .light-mode .border-dark-line{ border-color: rgba(0,0,0,0.1); }
    .brand-title{ font-family:'Kalam', cursive; text-shadow: -2px 1px 0px #047705; }
    #addOrderModal{ z-index:9999 !important; }
    @media (max-width:1024px){
      aside{ position:fixed; z-index:50; transform:translateX(-100%); transition: transform .3s ease; }
      aside.open{ transform: translateX(0); }
    }
  </style>
</head>
<body>
  <div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <aside id="sidebar" class="w-64 glass text-white flex-shrink-0 flex flex-col">
      <div class="p-6 text-2xl font-bold tracking-wide border-b border-dark-line flex items-center justify-between">
        <span class="brand-title">GenRev</span>
        <button id="sidebarClose" class="lg:hidden text-2xl leading-none">&times;</button>
      </div>

      <!-- User Info -->
      <div class="px-6 pt-4 pb-2">
        <div class="flex items-center space-x-3">
          <div class="w-10 h-10 bg-[#C3E956] rounded-full flex items-center justify-center font-bold text-[#1F1E1E]">
            {{ Auth::check() ? strtoupper(substr(Auth::user()->name, 0, 1)) : '?' }}
          </div>
          <div class="text-sm">
            <p class="font-semibold">{{ Auth::check() ? Auth::user()->name : 'Guest' }}</p>
            <p class="text-xs text-gray-300">Admin</p>
          </div>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 mt-4 space-y-1 text-sm font-medium">
        {{-- Dashboard --}}
        <a href="{{ route('dashboard') }}"
           class="nav-link block px-6 py-3 rounded-r-full transition-all duration-150 hover:bg-sidebar-hover
           {{ request()->routeIs('dashboard*') ? 'bg-sidebar-active text-[#1F1E1E]' : 'text-white' }}">
          Dashboard
        </a>

        <div class="mx-6 my-2 border-t border-dark-line"></div>

        {{-- Production --}}
        <a href="{{ route('production.index') }}"
           class="nav-link block px-6 py-3 rounded-r-full transition-all duration-150 hover:bg-sidebar-hover
           {{ request()->routeIs('production.*') ? 'bg-sidebar-active text-[#1F1E1E]' : 'text-white' }}">
          Production
        </a>

        {{-- Sales --}}
        <a href="{{ route('sales.index') }}"
           class="nav-link block px-6 py-3 rounded-r-full transition-all duration-150 hover:bg-sidebar-hover
           {{ request()->routeIs('sales.*') ? 'bg-sidebar-active text-[#1F1E1E]' : 'text-white' }}">
          Sales
        </a>

        {{-- Inventory --}}
        <a href="{{ route('inventory.index') }}"
           class="nav-link block px-6 py-3 rounded-r-full transition-all duration-150 hover:bg-sidebar-hover
           {{ request()->routeIs('inventory.*') ? 'bg-sidebar-active text-[#1F1E1E]' : 'text-white' }}">
          Inventory
        </a>

        {{-- Products (NEW) --}}
        <a href="{{ route('products.index') }}"
           class="nav-link block px-6 py-3 rounded-r-full transition-all duration-150 hover:bg-sidebar-hover
           {{ request()->routeIs('products.*') ? 'bg-sidebar-active text-[#1F1E1E]' : 'text-white' }}">
          Products
        </a>

        {{-- Materials (Global list) --}}
        <a href="{{ route('materials.index') }}"
           class="nav-link block px-6 py-3 rounded-r-full transition-all duration-150 hover:bg-sidebar-hover
           {{ request()->routeIs('materials.*') && !request()->routeIs('products.materials.*') ? 'bg-sidebar-active text-[#1F1E1E]' : 'text-white' }}">
          Materials
        </a>

        {{-- Employee --}}
        <a href="{{ route('employee.index') }}"
           class="nav-link block px-6 py-3 rounded-r-full transition-all duration-150 hover:bg-sidebar-hover
           {{ request()->routeIs('employee.*') ? 'bg-sidebar-active text-[#1F1E1E]' : 'text-white' }}">
          Employee
        </a>

        {{-- Settings --}}
        <a href="{{ route('settings.index') }}"
           class="nav-link block px-6 py-3 rounded-r-full transition-all duration-150 hover:bg-sidebar-hover
           {{ request()->routeIs('settings.*') ? 'bg-sidebar-active text-[#1F1E1E]' : 'text-white' }}">
          Settings
        </a>

        @if(request()->routeIs('products.materials.*'))
          <div class="px-6 pt-2">
            <a href="{{ route('products.index') }}" class="text-xs text-[#C3E956] hover:underline">← Back to Products</a>
          </div>
        @endif
      </nav>

      <div class="p-6 text-xs text-gray-300">© {{ now()->year }} GenRev</div>
    </aside>

    <!-- Main Content -->
    <div class="flex flex-col flex-1 overflow-hidden">
      <header class="bg-navbar text-white px-6 py-4 flex justify-between items-center shadow-lg border-b border-dark-line">
        <div class="flex items-center gap-4">
          <button id="sidebarToggle" class="lg:hidden text-2xl">&#9776;</button>
          <h1 class="text-xl font-bold tracking-wide">Dashboard Overview</h1>
        </div>

        <div class="flex items-center gap-4">
          <!-- Theme Toggle -->
          <div class="theme-toggle relative group" title="Toggle Light/Dark Mode">
            <label class="liquid-toggle-switch">
              <input type="checkbox" id="themeToggle" aria-label="Toggle theme">
              <span class="liquid-slider">
                <span class="slider-icon">🌙</span>
              </span>
            </label>
          </div>

          <!-- User Menu -->
          <div class="relative z-30">
            <button id="userMenuButton" class="focus:outline-none">
              <div class="w-9 h-9 rounded-full bg-[#91EAAF] flex items-center justify-center font-bold uppercase text-sm text-[#1F1E1E]">
                {{ Auth::check() ? strtoupper(substr(Auth::user()->name, 0, 1)) : '?' }}
              </div>
            </button>
            <div id="userDropdown" class="hidden absolute right-0 mt-2 w-48 glass rounded-lg shadow-xl text-white border border-dark-line overflow-hidden">
              <div class="px-4 py-3 border-b border-dark-line text-sm">
                Logged in as<br>
                <span class="font-semibold">{{ Auth::check() ? Auth::user()->name : 'Guest' }}</span>
                @if(Auth::check() && Auth::user()->role)
                  <div class="text-xs text-gray-300 capitalize">({{ Auth::user()->role }})</div>
                @endif
              </div>
              <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full text-left px-4 py-2 text-sm hover:bg:white/10 hover:bg-white/10 transition-colors">
                  Logout
                </button>
              </form>
            </div>
          </div>
        </div>
      </header>

      <main class="flex-1 overflow-y-auto p-8">
        @yield('content')
      </main>
    </div>
  </div>

  {{-- IMPORTANT: use @stack so @push('scripts') runs (charts, etc.) --}}
  @stack('scripts')

  <script>
    document.addEventListener("DOMContentLoaded", () => {
      // User dropdown
      const userMenuBtn = document.getElementById('userMenuButton');
      const userDropdown = document.getElementById('userDropdown');
      userMenuBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        userDropdown?.classList.toggle('hidden');
      });
      document.addEventListener('click', (e) => {
        if (!userMenuBtn?.contains(e.target) && !userDropdown?.contains(e.target)) {
          userDropdown?.classList.add('hidden');
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
