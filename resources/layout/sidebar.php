<aside class="sidebar">
    <ul class="sidebar-menu">
    <li class="{{ Request::is('dashboard') ? 'active' : '' }}">
        <a href="{{ route('dashboard') }}">Dashboard</a>
    </li>
    <li class="{{ Request::is('production') ? 'active' : '' }}">
        <a href="{{ route('production') }}">Production</a>
    </li>
    <li class="{{ Request::is('sales') ? 'active' : '' }}">
        <a href="{{ route('sales') }}">Sales</a>
    </li>
    <li class="{{ Request::is('inventory') ? 'active' : '' }}">
        <a href="{{ route('inventory') }}">Inventory</a>
    </li>
    <li class="{{ Request::is('employee') ? 'active' : '' }}">
        <a href="{{ route('employee') }}">Employee</a>
    </li>
    <li class="{{ Request::is('settings') ? 'active' : '' }}">
        <a href="{{ route('settings') }}">Settings</a>
    </li>
</ul>

</aside>
