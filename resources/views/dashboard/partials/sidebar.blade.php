@php($role = Auth::user()->role)

<li><a href="{{ route($role === 'Admin' ? 'dashboard.admin' : ($role === 'Sales' ? 'dashboard.sales' : 'dashboard.inventory')) }}">Dashboard</a></li>

@if($role === 'Admin' || $role === 'Inventory')
  <li><a href="{{ route('production.index') }}">Production</a></li>
@endif

@if($role === 'Admin' || $role === 'Sales')
  <li><a href="{{ route('sales.index') }}">Sales</a></li>
@endif

@if($role === 'Admin' || $role === 'Inventory')
  <li><a href="{{ route('inventory.index') }}">Inventory</a></li>
  <li><a href="{{ route('materials.index') }}">Materials</a></li>
@endif

@if($role === 'Admin')
  <li><a href="{{ route('employee.index') }}">Employee</a></li>
@endif

<li><a href="{{ route('settings.index') }}">Settings</a></li>
