<aside class="sidebar">
  <ul class="sidebar-menu">

    {{-- Dashboard - visible to everyone --}}
    <li class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
      <a href="{{ route('dashboard') }}">Dashboard</a>
    </li>

    {{-- Production - visible to both Admin and Employee --}}
    <li class="{{ request()->routeIs('production.*') ? 'active' : '' }}">
      <a href="{{ route('production.index') }}">Production</a>
    </li>

    {{-- Sales - visible to both Admin and Employee --}}
    <li class="{{ request()->routeIs('sales.*') ? 'active' : '' }}">
      <a href="{{ route('sales.index') }}">Sales</a>
    </li>

    {{-- Inventory - visible to both Admin and Employee --}}
    <li class="{{ request()->routeIs('inventory.*') ? 'active' : '' }}">
      <a href="{{ route('inventory.index') }}">Inventory</a>
    </li>

    {{-- Admin-only sections --}}
    @if(Auth::check() && Auth::user()->role === 'Admin')

      {{-- Materials --}}
      <li class="{{ request()->routeIs('materials.*') ? 'active' : '' }}">
        <a href="{{ route('materials.index') }}">Materials</a>
      </li>

      {{-- Employee --}}
      <li class="{{ request()->routeIs('employee.*') ? 'active' : '' }}">
        <a href="{{ route('employee.index') }}">Employee</a>
      </li>

      {{-- Settings --}}
      <li class="{{ request()->routeIs('settings.*') ? 'active' : '' }}">
        <a href="{{ route('settings.index') }}">Settings</a>
      </li>

    @endif

  </ul>
</aside>
