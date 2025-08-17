@extends('layout.mainlayout')

@section('content')
<div class="glass section-liquid-shine text-white p-6 rounded-2xl shadow-md border border-dark-line">

    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-semibold tracking-wide">Production Overview</h2>
        <button onclick="openAddModal()" class="px-4 py-2 rounded-xl bg-[var(--sidebar-active,#EDD100)] text-[#1F1E1E] font-semibold shadow hover:opacity-90 transition">
            + Add Order
        </button>
    </div>

    {{-- Category Filter --}}
    <div class="flex flex-wrap gap-3 mb-6" id="category-buttons">
        @foreach ($categories as $category)
            <button
                class="px-4 py-2 rounded-full text-white/95 bg-white/5 border border-white/10 hover:bg-white/10 hover:border-white/20 transition category-btn"
                data-category="{{ $category }}"
                type="button"
            >
                {{ $category }}
            </button>
        @endforeach
        @if(count($categories))
            <button type="button" class="text-sm text-red-300 underline ml-2 clear-filter">Clear Filter</button>
        @endif
    </div>

    {{-- Search + Sort --}}
    <div class="mb-6">
        <form method="GET" action="{{ route('production.index') }}" class="flex flex-wrap gap-2 items-center" id="filtersForm">
            <input
                type="text"
                name="search"
                placeholder="Search product name..."
                value="{{ request('search') }}"
                class="w-full sm:w-1/3 rounded-xl bg-white/5 border border-white/10 px-3 py-2 text-white/90 placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-emerald-400"
            >
            <select name="sort" id="sort-select"
                    class="rounded-xl bg-white/5 border border-white/10 px-3 py-2 text-white/90 focus:outline-none focus:ring-2 focus:ring-emerald-400">
                @php $currentSort = request('sort', $sort ?? 'urgency'); @endphp
                <option value="urgency" {{ $currentSort === 'urgency' ? 'selected' : '' }}>Urgency (Low Stock First)</option>
                <option value="expiry"  {{ $currentSort === 'expiry'  ? 'selected' : '' }}>Soonest Expiry</option>
                <option value="name"    {{ $currentSort === 'name'    ? 'selected' : '' }}>Name A–Z</option>
            </select>
            <button type="submit" class="px-4 py-2 rounded-xl bg-[var(--sidebar-active,#EDD100)] text-[#1F1E1E] font-semibold shadow hover:opacity-90 transition">Apply</button>
        </form>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 mb-6">
        @foreach ([
            ['label' => 'Forecasted Demand', 'value' => number_format((float)$forecastedDemand, 3) . ' kg'],
            ['label' => 'Current Inventory', 'value' => number_format((float)$actualInventory, 3) . ' kg'],
            ['label' => 'Shortfall', 'value' => number_format((float)$shortfall, 3) . ' kg', 'class' => 'text-red-300'],
            ['label' => 'Recommended Production', 'value' => number_format((float)$recommendedProduction, 3) . ' kg', 'class' => 'text-emerald-300'],
        ] as $card)
            <div class="glass rounded-2xl p-4 border border-dark-line">
                <p class="text-sm text-white/70">{{ $card['label'] }}</p>
                <h3 class="text-lg font-bold {{ $card['class'] ?? '' }}">{{ $card['value'] }}</h3>
            </div>
        @endforeach
    </div>

    {{-- Product cards --}}
    <div id="product-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        {{-- This partial should use new helpers like $p->days_to_expiry, $p->is_expired, $p->remaining_qty --}}
        @include('production.partials.product-cards', ['products' => $products])
    </div>
</div>

{{-- Modal (updated Add Order modal) --}}
@include('production.modal', ['products' => $allProducts])

{{-- Scripts --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('addModal');
    const sortSelect = document.getElementById('sort-select');

    // Open/Close modal (ensure centering by toggling flex)
    window.openAddModal = () => {
        resetModalFields();
        generateBatchNumber();
        modal?.classList.remove('hidden');
        modal?.classList.add('flex');
    };
    window.closeAddModal = () => {
        modal?.classList.add('hidden');
        modal?.classList.remove('flex');
    };

    // Batch number generator
    function generateBatchNumber() {
        const now = new Date();
        const pad = n => n.toString().padStart(2, '0');
        const batch = `B-${now.getFullYear()}${pad(now.getMonth()+1)}${pad(now.getDate())}-${pad(now.getHours())}${pad(now.getMinutes())}${pad(now.getSeconds())}`;
        const input = document.getElementById('batch_number');
        if (input && !input.value) input.value = batch;
    }

    // Reset fields in modal (supports updated Add Order modal fields)
    function resetModalFields() {
        [
            'product_id','product_name','batch_number',
            'forecasted_demand','produced_qty_kg',
            'unit_cost','unit_price',
            'production_date','expiration_date',
            'order_date','order_quantity_kg',
            'customer_name','notes'
        ].forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            if (el.tagName === 'SELECT') el.selectedIndex = 0;
            else el.value = '';
        });
    }

    // Optional: name-based product info fetch (kept for backward compat)
    window.loadProductInfo = () => {
        const sel = document.getElementById("product_name");
        const name = (sel?.value || '').trim();
        if (!name) return;

        fetch(`{{ url('/production/info') }}/${encodeURIComponent(name)}`)
            .then(res => res.ok ? res.json() : Promise.reject())
            .then(data => {
                const fd = document.getElementById("forecasted_demand");
                const ci = document.getElementById("current_inventory");
                const uc = document.getElementById("unit_cost");
                if (fd) fd.value = data.forecasted_demand ?? '';
                if (ci) ci.value = data.current_inventory ?? '';
                if (uc) uc.value = data.unit_cost ?? '';
            })
            .catch(() => {
                ["forecasted_demand","current_inventory","unit_cost"].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.value = '';
                });
            });
    };

    // AJAX category filter (now passes &sort=)
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const category = btn.dataset.category;
            const sort = sortSelect ? sortSelect.value : 'urgency';
            fetch(`{{ route('production.filter') }}?category=${encodeURIComponent(category)}&sort=${encodeURIComponent(sort)}`)
                .then(res => res.json())
                .then(data => { document.getElementById('product-container').innerHTML = data.html; })
                .catch(console.error);
        });
    });

    // Clear filter (keeps current sort)
    const clear = document.querySelector('.clear-filter');
    if (clear) clear.addEventListener('click', () => {
        const sort = sortSelect ? sortSelect.value : 'urgency';
        fetch(`{{ route('production.filter') }}?sort=${encodeURIComponent(sort)}`)
            .then(res => res.json())
            .then(data => { document.getElementById('product-container').innerHTML = data.html; })
            .catch(console.error);
    });

    // Prevent double-submit anywhere on this page
    document.querySelectorAll('form').forEach(f => {
        f.addEventListener('submit', () => {
            const btn = f.querySelector('button[type="submit"], input[type="submit"]');
            if (btn) { btn.disabled = true; btn.classList.add('opacity-70','cursor-not-allowed'); }
        });
    });
});
</script>
@endsection
