{{-- resources/views/partials/sidebar.blade.php — Role-aware sidebar (light-theme safe) --}}
{{-- Works with Tailwind CDN (no @apply). --}}

@php
  $user = Auth::user();

  // =========================
  // Robust role normalization
  // =========================
  // Accepts: Masters Admin, Master Admin, Admin, Administrator, Super Admin, etc.
  // Falls back safely to 'sales' when unknown/empty.
  $rawOriginal = (string) ($user->role ?? '');
  $rawLower    = strtolower(trim($rawOriginal));
  // Strip non-letters to be tolerant of spaces and hyphens
  $norm        = preg_replace('/[^a-z]/', '', $rawLower);

  $role = match (true) {
      // Any admin-ish value => 'masters admin'
      $norm === 'mastersadmin',
      $norm === 'masteradmin',
      $norm === 'admin',
      $norm === 'administrator',
      $norm === 'superadmin',
      $norm === 'superadministrator',
      str_contains($norm, 'admin') => 'masters admin',

      // Exact supported roles
      $norm === 'productionmanager' => 'production manager',
      $norm === 'inventory'         => 'inventory',
      $norm === 'sales'             => 'sales',

      default => ($rawLower ?: 'sales'),
  };

  // Allowlist per role
  $map = [
      'masters admin'      => ['dashboard','materials','production','sales','inventory','products','reports','settings','employee'],
      'production manager' => ['dashboard','production','products','settings'],
      'sales'              => ['dashboard','sales','settings'],
      'inventory'          => ['dashboard','inventory','materials','settings'],
  ];

  $modules = $map[$role] ?? ['dashboard','settings']; // safe minimum
  $can = fn(string $m) => in_array($m, $modules, true);

  // Active helper accepts string or array
  $link = function ($patterns) {
      foreach ((array) $patterns as $p) if (request()->routeIs($p)) return 'active';
      return '';
  };

  $roleLabel = \Illuminate\Support\Str::headline($role);
@endphp

<aside x-data="{ open: { materials: true }, toggle(s){ this.open[s] = !this.open[s]; } }"
       class="group fixed inset-y-0 left-0 z-40 w-64 lg:w-72 p-3">
  <div class="relative h-full">
    <!-- Light panel -->
    <div class="absolute inset-0 rounded-3xl bg-white border border-slate-200 shadow-lg"></div>

    <nav class="relative h-full overflow-y-auto rounded-3xl p-4">
      <div class="mb-4 flex items-center gap-3">
        {{-- Brand logo --}}
        <div class="h-10 w-10 rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden flex items-center justify-center">
          <img
            src="{{ asset('images/GENREV_FINAL.png') }}"
            alt="GenRev"
            loading="lazy"
            decoding="async"
            class="h-full w-full object-contain"
            onerror="this.closest('div').innerHTML='<span class=&quot;sr-only&quot;>GenRev</span>';"
          >
        </div>

        <div class="min-w-0">
          <div class="text-slate-900 font-semibold leading-5 truncate">GenRev Admin</div>
          <div class="text-[11px] text-slate-500 flex items-center gap-2">
            <span class="truncate">Production Dashboard</span>
            <span class="inline-flex items-center rounded-md border border-slate-200 px-1.5 py-0.5 text-[10px] text-slate-600 bg-slate-50">
              {{ $roleLabel }}
            </span>
          </div>
        </div>
      </div>

      <ul class="space-y-1 text-sm">
        {{-- Dashboard --}}
        @if($can('dashboard'))
          <li>
            <a href="{{ route('dashboard') }}" class="sb-link {{ $link(['dashboard','dashboard*']) }}">
              <span>Dashboard</span>
            </a>
          </li>
        @endif

        {{-- Materials --}}
        @if($can('materials'))
          <li>
            <button @click="toggle('materials')" type="button" class="sb-link justify-between">
              <span class="inline-flex items-center gap-3"><span>Materials</span></span>
              <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180' : open['materials'] }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.108l3.71-3.878a.75.75 0 011.08 1.04l-4.24 4.43a.75.75 0 01-1.08 0l-4.24-4.43a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
              </svg>
            </button>
            <div x-show="open['materials']" x-collapse class="ml-2 mt-1 space-y-1 pl-4 border-l border-slate-200">
              <a href="{{ route('materials.index') }}"  class="sb-sublink {{ $link('materials.index') }}">List</a>
              <a href="{{ route('materials.create') }}" class="sb-sublink {{ $link('materials.create') }}">Add Material</a>
              <a href="{{ route('recipes.index') }}"   class="sb-sublink {{ $link('recipes.*') }}">Recipes</a>
            </div>
          </li>
        @endif

        {{-- Production --}}
        @if($can('production'))
          <li>
            <a href="{{ route('production.index') }}" class="sb-link {{ $link('production.*') }}">
              <span>Production</span>
            </a>
          </li>
        @endif

        {{-- Sales --}}
        @if($can('sales'))
          <li>
            <a href="{{ route('sales.index') }}" class="sb-link {{ $link('sales.*') }}">
              <span>Sales</span>
            </a>
          </li>
        @endif

        {{-- Inventory --}}
        @if($can('inventory'))
          <li>
            <a href="{{ route('inventory.index') }}" class="sb-link {{ $link('inventory.*') }}">
              <span>Inventory</span>
            </a>
          </li>
        @endif

        {{-- Products --}}
        @if($can('products'))
          <li>
            <a href="{{ route('products.index') }}" class="sb-link {{ $link('products.*') }}">
              <span>Products</span>
            </a>
          </li>
        @endif

        {{-- Reports --}}
        @if($can('reports'))
          <li>
            <a href="{{ route('reports.index') }}" class="sb-link {{ $link('reports.*') }}">
              <span>Reports</span>
            </a>
          </li>
        @endif

        {{-- Employee --}}
        @if($can('employee'))
          <li>
            <a href="{{ route('employees.index') }}" class="sb-link {{ $link('employees.*') }}">
              <span>Employee</span>
            </a>
          </li>
        @endif

        {{-- Settings --}}
        @if($can('settings'))
          <li class="pt-2 mt-4 border-t border-slate-200">
            <a href="{{ route('settings.index') }}" class="sb-link {{ $link('settings.*') }}">
              <span>Settings</span>
            </a>
          </li>
        @endif
      </ul>
    </nav>
  </div>

  <div class="absolute inset-0 rounded-3xl ring-1 ring-slate-200/70"></div>
</aside>

<style>
  .sb-link{
    position:relative; display:flex; align-items:center; gap:.75rem;
    padding:.5rem .75rem; border-radius:.75rem;
    color:rgba(15,23,42,.92); text-decoration:none; background:transparent; border:1px solid transparent;
    transition:all .15s ease;
  }
  .sb-link:hover{ background:rgba(2,6,23,.04); border-color:rgba(2,6,23,.06); }
  .sb-link.active{
    color:rgb(15,23,42);
    background:linear-gradient(to bottom right, rgba(16,185,129,.12), rgba(34,211,238,.10));
    border-color:rgba(2,6,23,.08);
  }
  .sb-sublink{
    display:block; padding:.375rem .75rem; border-radius:.5rem;
    color:rgba(30,41,59,.85); text-decoration:none; border:1px solid transparent; transition:all .15s ease;
  }
  .sb-sublink:hover{ background:rgba(2,6,23,.04); border-color:rgba(2,6,23,.06); }
  .sb-sublink.active{ background:rgba(16,185,129,.10); border-color:rgba(2,6,23,.08); color:rgb(15,23,42); }
</style>
