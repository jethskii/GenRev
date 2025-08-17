{{-- resources/views/partials/sidebar.blade.php --}}
<aside
  class="bg-[#1b241b] text-white w-64 min-w-64 h-screen sticky top-0 flex flex-col border-r border-dark-line"
  x-data="{ open: true }"
>
    {{-- Brand / User --}}
    <div class="px-5 py-4 flex items-center justify-between border-b border-dark-line">
        <div class="flex items-center gap-3">
            <div class="h-9 w-9 rounded-full bg-armygreen/90 text-black font-bold grid place-items-center">
                G
            </div>
            <div>
                <div class="font-semibold leading-tight">GenRev Production</div>
                <div class="text-xs text-gray-400">Admin</div>
            </div>
        </div>
        {{-- optional collapse (requires AlpineJS if you want to use it) --}}
        {{-- <button class="text-gray-400 hover:text-white" @click="open=!open">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button> --}}
    </div>

    {{-- Nav --}}
    <nav class="flex-1 overflow-y-auto py-3">
        @php
            $item = function ($label, $route, $iconPath, $isActive = false) {
                $classes = $isActive
                    ? 'bg-armygreen/20 text-white border-l-4 border-armygreen'
                    : 'text-gray-300 hover:text-white hover:bg-white/5';
                return <<<HTML
                    <a href="{$route}" class="group flex items-center gap-3 px-5 py-2.5 transition {$classes}">
                        <svg class="h-5 w-5 opacity-80 group-hover:opacity-100" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            {$iconPath}
                        </svg>
                        <span class="text-sm">{$label}</span>
                    </a>
                HTML;
            };
        @endphp

        {{-- Dashboard --}}
        {!! $item(
            'Dashboard',
            route('dashboard'),
            '<path d="M3 12a9 9 0 1118 0 9 9 0 01-18 0zm9-7v7l5 3"/>',
            request()->routeIs('dashboard')
        ) !!}

        {{-- Production --}}
        {!! $item(
            'Production',
            route('production.index'),
            '<path d="M3 7h18M3 12h18M3 17h18" />',
            request()->routeIs('production.*')
        ) !!}

        {{-- Sales --}}
        {!! $item(
            'Sales',
            route('sales.index'),
            '<path d="M3 4h18v6H3zM3 14h18v6H3z" />',
            request()->routeIs('sales.*')
        ) !!}

        {{-- Inventory --}}
        {!! $item(
            'Inventory',
            route('inventory.index'),
            '<path d="M4 6h16l-2 12H6L4 6zm3-3h10v3H7V3z" />',
            request()->routeIs('inventory.*')
        ) !!}

        {{-- Products (NEW) --}}
        {!! $item(
            'Products',
            route('products.index'),
            '<path d="M4 7l8-4 8 4-8 4-8-4zm0 5l8 4 8-4M4 17l8 4 8-4" />',
            request()->routeIs('products.*')
        ) !!}

        {{-- Materials (Global stock list) --}}
        {!! $item(
            'Materials',
            route('materials.index'),
            '<path d="M4 6h16v12H4zM8 10h8M8 14h5" />',
            request()->routeIs('materials.*') && !request()->routeIs('products.materials.*')
        ) !!}

        {{-- Employee --}}
        {!! $item(
            'Employee',
            route('employee.index'),
            '<path d="M16 14a4 4 0 10-8 0v4h8v-4zM12 4a3 3 0 110 6 3 3 0 010-6z" />',
            request()->routeIs('employee.*')
        ) !!}

        {{-- Settings --}}
        {!! $item(
            'Settings',
            route('settings.index'),
            '<path d="M12 8a4 4 0 100 8 4 4 0 000-8zm8.94 4a7.94 7.94 0 00-.34-2l2-1.5-2-3.46-2.36.5a8.09 8.09 0 00-1.72-1L14 1h-4l-.52 3.04a8.09 8.09 0 00-1.72 1l-2.36-.5-2 3.46 2 1.5c-.16.64-.28 1.31-.34 2 .06.69.18 1.36.34 2l-2 1.5 2 3.46 2.36-.5c.53.38 1.11.71 1.72 1L10 23h4l.52-3.04c.61-.29 1.19-.62 1.72-1l2.36.5 2-3.46-2-1.5c.16-.64.28-1.31.34-2z" />',
            request()->routeIs('settings.*')
        ) !!}

        {{-- When inside a product’s Materials (recipe) page, show a subtle breadcrumb link back to Products --}}
        @if(request()->routeIs('products.materials.*'))
            <div class="px-5 pt-2">
                <a href="{{ route('products.index') }}" class="text-xs text-armygreen hover:underline">
                    ← Back to Products
                </a>
            </div>
        @endif
    </nav>

    {{-- Footer / Logout --}}
    <div class="px-5 py-4 border-t border-dark-line">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button
                type="submit"
                class="w-full text-left text-sm px-4 py-2 rounded bg-white/5 hover:bg-white/10 text-gray-200 transition"
            >
                Log out
            </button>
        </form>
        <div class="text-[11px] text-gray-500 mt-3">© {{ now()->year }} GenRev</div>
    </div>
</aside>
