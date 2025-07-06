<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GenRev Admin Dashboard</title>

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Theme Styling -->
    <style>
        :root {
            --navbar: #2e3b2c;
            --sidebar: #1c241b;
            --sidebar-hover: #3f4e3a;
            --sidebar-active: #556b2f;
            --dark-bg: #121a13;
            --dark-line: #2a3527;
        }
        .bg-navbar        { background-color: var(--navbar); }
        .bg-sidebar       { background-color: var(--sidebar); padding-right: 1rem; }
        .bg-sidebar-hover:hover { background-color: var(--sidebar-hover); }
        .bg-sidebar-active { background-color: var(--sidebar-active); }
        .bg-dark-bg       { background-color: var(--dark-bg); }
        .border-dark-line { border-color: var(--dark-line); }
        .btn-armygreen {
            background-color: var(--sidebar-active);
            color: white;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            border-radius: 0.375rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);
            transition: background-color 0.2s ease-in-out;
        }
        .btn-armygreen:hover { background-color: var(--sidebar-hover); }
        .btn-cancel {
            background-color: #444;
            color: white;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            border-radius: 0.375rem;
        }
        .input-dark {
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
            background-color: white;
            color: black;
            border: 1px solid #ccc;
        }
    </style>
</head>

<body class="bg-gray-100 text-gray-900 dark:bg-dark-bg dark:text-white">
<div class="flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <aside class="w-64 bg-sidebar text-white flex-shrink-0 flex flex-col">
        <div class="p-6 text-2xl font-semibold tracking-wide border-b border-dark-line">
            Admin Controls
        </div>
        <nav class="flex-1 mt-4 space-y-1 text-sm">
            @php
                $routes = [
                    'dashboard' => 'Dashboard',
                    'production' => 'Production',
                    'sales' => 'Sales',
                    'inventory' => 'Inventory',
                    'materials' => 'Materials',
                    'employee' => 'Employee',
                    'settings' => 'Settings',
                ];
            @endphp

            @foreach($routes as $route => $label)
                <a href="{{ route($route) }}"
                   class="block px-6 py-3 rounded-r-full transition-all duration-150 hover:bg-sidebar-hover
                   {{ request()->routeIs($route . '*') ? 'bg-sidebar-active' : '' }}">
                   {{ $label }}
                </a>
            @endforeach
        </nav>
    </aside>

    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-hidden">

        <!-- Navbar -->
        <header class="bg-navbar text-white px-6 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold tracking-wide">Master Admin</h1>

            <!-- User Dropdown -->
            <div class="relative z-50">
                <button id="userMenuButton" class="focus:outline-none">
                    <div class="w-9 h-9 rounded-full bg-gray-600 flex items-center justify-center font-bold uppercase text-sm">
                        {{ Auth::check() ? strtoupper(substr(Auth::user()->name, 0, 1)) : '?' }}
                    </div>
                </button>

                <div id="userDropdown" class="hidden absolute right-0 mt-2 w-48 bg-[#1f2a1d] border border-dark-line rounded-md shadow-lg text-white">
                    <div class="px-4 py-2 border-b border-dark-line text-sm">
                        Logged in as<br>
                        <span class="font-semibold">{{ Auth::check() ? Auth::user()->name : 'Guest' }}</span>
                        @if(Auth::check() && Auth::user()->role)
                            <div class="text-xs text-gray-400 capitalize">({{ Auth::user()->role }})</div>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-2 text-sm hover:bg-armygreen hover:text-white transition-colors">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1 overflow-y-auto p-6">
            @yield('content')
        </main>
    </div>
</div>

<!-- Global Script Slot -->
@yield('scripts')

<!-- Core Scripts -->
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const userMenuBtn = document.getElementById('userMenuButton');
        const userDropdown = document.getElementById('userDropdown');

        userMenuBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            userDropdown.classList.toggle('hidden');
        });

        document.addEventListener('click', (e) => {
            if (!userMenuBtn.contains(e.target) && !userDropdown.contains(e.target)) {
                userDropdown.classList.add('hidden');
            }
        });

        const alert = document.getElementById('successAlert');
        if (alert) {
            setTimeout(() => alert.style.display = 'none', 5000);
        }
    });
</script>
</body>
</html>
