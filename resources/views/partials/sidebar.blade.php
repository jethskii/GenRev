{{-- resources/views/partials/sidebar.blade.php — 3D Neon Glass Sidebar (role-aware) --}}
{{-- Requires Tailwind; optional custom vars:
   :root { --surface:#0B0F10; --card:#11161A; --line:rgba(255,255,255,.08); } --}}

@php
  use Illuminate\Support\Str;

  $role = Str::ucfirst(Auth::user()->role ?? 'Admin');

  // ✅ Final allowlist per role
  $allowed = [
    'Admin'     => ['dashboard','materials','production','sales','inventory','products','reports','settings','employee'],
    'Sales'     => ['dashboard','sales','settings'],
    'Inventory' => ['dashboard','inventory','materials','settings'],
  ];

  $modules = $allowed[$role] ?? [];
  $can = fn(string $m) => in_array($m, $modules, true);

  // Active-link helper
  $link = fn($r) => request()->routeIs($r) ? 'active' : '';
@endphp

<aside x-data="sidebar()" class="group fixed inset-y-0 left-0 z-40 w-64 lg:w-72 p-3">
  <div class="relative h-full">
    <!-- 3D rail -->
    <div class="absolute inset-0 rounded-3xl bg-gradient-to-br from-white/8 via-white/5 to-white/3 backdrop-blur-xl border border-white/10 shadow-[0_10px_30px_rgba(0,0,0,.45),inset_0_1px_0_rgba(255,255,255,.08)] [perspective:1000px]"></div>

    <!-- Glow edges -->
    <div class="pointer-events-none absolute -inset-0.5 rounded-3xl opacity-40 blur-2xl bg-[radial-gradient(80%_80%_at_20%_10%,#34f3d4,transparent),radial-gradient(80%_80%_at_80%_90%,#7cfc9f,transparent)]"></div>

    <nav class="relative h-full overflow-y-auto rounded-3xl p-4">
      <div class="mb-4 flex items-center gap-3">
        <div class="h-10 w-10 rounded-2xl bg-gradient-to-br from-emerald-400/90 to-cyan-400/90 shadow-[0_10px_25px_rgba(56,255,203,.35)]"></div>
        <div>
          <div class="text-white font-semibold leading-5">GenRev Admin</div>
          <div class="text-[11px] text-white/60">Production Dashboard</div>
        </div>
      </div>

      <ul class="space-y-1 text-sm">
        {{-- Dashboard --}}
        @if($can('dashboard'))
          <li>
            <a href="{{ route('dashboard') }}" class="sb-link {{ $link('dashboard*') }}">
              @svg('heroicon-o-home', 'h-5 w-5')
              <span>Dashboard</span>
            </a>
          </li>
        @endif

        {{-- Materials (Inventory + Admin) --}}
        @if($can('materials'))
          <li>
            <button @click="toggle('materials')" class="sb-link justify-between">
              <span class="inline-flex items-center gap-3">
                @svg('heroicon-o-squares-2x2', 'h-5 w-5')
                <span>Materials</span>
              </span>
              <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180' : open['materials'] }" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.108l3.71-3.878a.75.75 0 011.08 1.04l-4.24 4.43a.75.75 0 01-1.08 0l-4.24-4.43a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
            </button>
            <div x-show="open['materials']" x-collapse class="ml-2 mt-1 space-y-1 pl-4 border-l border-white/10">
              <a href="{{ route('materials.index') }}" class="sb-sublink {{ $link('materials.index') }}">List</a>
              <a href="{{ route('materials.create') }}" class="sb-sublink {{ $link('materials.create') }}">Add Material</a>
              <a href="{{ route('recipes.index') }}" class="sb-sublink {{ $link('recipes.*') }}">Recipes</a>
            </div>
          </li>
        @endif

        {{-- Production (Admin only) --}}
        @if($can('production'))
          <li>
            <a href="{{ route('production.index') }}" class="sb-link {{ $link('production.*') }}">
              @svg('heroicon-o-cog-6-tooth', 'h-5 w-5')
              <span>Production</span>
            </a>
          </li>
        @endif

        {{-- Sales (Sales + Admin) --}}
        @if($can('sales'))
          <li>
            <a href="{{ route('sales.index') }}" class="sb-link {{ $link('sales.*') }}">
              @svg('heroicon-o-banknotes', 'h-5 w-5')
              <span>Sales</span>
            </a>
          </li>
        @endif

        {{-- Inventory (Inventory + Admin) --}}
        @if($can('inventory'))
          <li>
            <a href="{{ route('inventory.index') }}" class="sb-link {{ $link('inventory.*') }}">
              @svg('heroicon-o-archive-box', 'h-5 w-5')
              <span>Inventory</span>
            </a>
          </li>
        @endif

        {{-- Products (Admin only) --}}
        @if($can('products'))
          <li>
            <a href="{{ route('products.index') }}" class="sb-link {{ $link('products.*') }}">
              @svg('heroicon-o-cube', 'h-5 w-5')
              <span>Products</span>
            </a>
          </li>
        @endif

        {{-- Reports (Admin only) --}}
        @if($can('reports'))
          <li>
            <a href="{{ route('reports.index') }}" class="sb-link {{ $link('reports.*') }}">
              @svg('heroicon-o-chart-bar', 'h-5 w-5')
              <span>Reports</span>
            </a>
          </li>
        @endif

        {{-- Employee (Admin only) --}}
        @if($can('employee'))
          <li>
            <a href="{{ route('employee') }}" class="sb-link {{ $link('employees.*') }}">
              @svg('heroicon-o-user-group', 'h-5 w-5')
              <span>Employee</span>
            </a>
          </li>
        @endif

        {{-- Settings (everyone) --}}
        @if($can('settings'))
          <li class="pt-2 mt-4 border-t border-white/10">
            <a href="{{ route('settings.index') }}" class="sb-link {{ $link('settings.*') }}">
              @svg('heroicon-o-cog-8-tooth', 'h-5 w-5')
              <span>Settings</span>
            </a>
          </li>
        @endif
      </ul>

      <!-- floating highlight -->
      <div class="pointer-events-none absolute inset-x-6 bottom-6 rounded-2xl h-14 bg-gradient-to-br from-emerald-400/15 via-cyan-400/10 to-transparent blur-2xl"></div>
    </nav>
  </div>

  <!-- neon rim -->
  <div class="absolute inset-0 rounded-3xl ring-1 ring-white/15 group-hover:ring-white/25 transition"></div>
</aside>

{{-- Styles --}}
<style>
  .sb-link{ @apply relative flex items-center gap-3 px-3 py-2 rounded-xl text-white/85 hover:text-white bg-white/0 hover:bg-white/5 border border-transparent hover:border-white/10 shadow-[inset_0_1px_0_rgba(255,255,255,.06),0_8px_20px_rgba(0,0,0,.25)] transition; }
  .sb-link.active{ @apply text-white bg-gradient-to-br from-emerald-400/15 to-cyan-400/10 border-white/15 shadow-[0_10px_30px_rgba(56,255,203,.12),inset_0_1px_0_rgba(255,255,255,.08)]; }
  .sb-sublink{ @apply block px-3 py-1.5 rounded-lg text-white/75 hover:text-white hover:bg-white/5 border border-transparent hover:border-white/10 transition; }
  .sb-sublink.active{ @apply text-white bg-white/5 border-white/10; }
</style>

{{-- Alpine tiny controller (persist open state) --}}
<script>
  function sidebar(){
    const key='sidebar-open';
    const state = JSON.parse(localStorage.getItem(key) || '{}');
    return {
      open: { materials: !!state.materials },
      toggle(section){
        this.open[section] = !this.open[section];
        localStorage.setItem(key, JSON.stringify(this.open));
      }
    }
  }
</script>
