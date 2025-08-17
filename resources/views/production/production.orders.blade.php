@extends('layout.mainlayout')

@section('content')
<div class="glass section-liquid-shine text-white p-6 rounded-2xl shadow-md border border-dark-line">

    {{-- 🧠 Product Info Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold tracking-wide">{{ $product->product_name }}</h2>
            <p class="text-sm text-[var(--muted,#A3B4A7)]">Category: {{ $product->category ?? 'Uncategorized' }}</p>
        </div>
        <img src="{{ $product->image_url ?? '/images/default-burger.png' }}"
             class="w-24 h-24 object-cover rounded-xl shadow border border-dark-line ring-1 ring-white/10"
             alt="{{ $product->product_name }}">
    </div>

    {{-- ➕ Add Order Button --}}
    <div class="flex justify-end mb-4">
        <button onclick="openOrderModal()"
                class="px-4 py-2 rounded-xl bg-[var(--sidebar-active,#EDD100)] text-[#1F1E1E] font-semibold shadow hover:opacity-90 transition">
            + Add Order
        </button>
    </div>

    {{-- 📋 Orders Table --}}
    <div class="overflow-x-auto rounded-2xl ring-1 ring-white/10">
        <table class="min-w-full text-sm text-left rounded-2xl overflow-hidden">
            <thead class="bg-white/5 text-white uppercase text-xs">
                <tr>
                    <th class="py-3 px-4">Batch #</th>
                    <th class="py-3 px-4">Forecasted</th>
                    <th class="py-3 px-4">Produced</th>
                    <th class="py-3 px-4">Unit Cost</th>
                    <th class="py-3 px-4">Production Date</th>
                    <th class="py-3 px-4">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/10">
                @forelse ($orders as $order)
                    <tr class="hover:bg-white/5 transition">
                        <td class="py-3 px-4">{{ $order->batch_number }}</td>
                        <td class="py-3 px-4">{{ $order->forecasted_demand }} kg</td>
                        <td class="py-3 px-4">{{ $order->quantity ?? $order->current_inventory }} kg</td>
                        <td class="py-3 px-4">₱{{ number_format($order->unit_cost, 2) }}</td>
                        <td class="py-3 px-4">{{ \Carbon\Carbon::parse($order->production_date)->format('M d, Y') }}</td>
                        <td class="py-3 px-4">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                <a href="{{ route('production.edit', $order->id) }}"
                                   class="px-3 py-1.5 rounded-full border border-white/20 hover:border-[var(--brand-green,#047705)] hover:bg-[var(--brand-green,#047705)]/20 transition">Edit</a>
                                <form action="{{ route('production.destroy', $order->id) }}" method="POST"
                                      onsubmit="return confirm('Are you sure? This will be soft-deleted for 7 days.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="px-3 py-1.5 rounded-full border border-red-500/40 text-red-300 hover:bg-red-500/10 transition">Delete</button>
                                </form>
                                <a href="{{ route('production.export.pdf', $order->id) }}"
                                   class="px-3 py-1.5 rounded-full border border-emerald-400/40 text-emerald-200 hover:bg-emerald-400/10 transition">PDF</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-4 text-center text-[var(--muted,#A3B4A7)]">No production orders found for this product.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- 🔙 Back to Main Production --}}
    <div class="mt-6">
        <a href="{{ route('production.index') }}" class="text-[var(--sidebar-active,#EDD100)] hover:opacity-90">&larr; Back to Production</a>
    </div>
</div>

{{-- Add Order Modal --}}
<div id="addOrderModal" class="fixed inset-0 z-[9999] hidden">
  <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeOrderModal()"></div>
  <div class="relative mx-auto my-10 max-w-md w-[92%] glass border border-dark-line rounded-2xl shadow-xl text-white p-6">

    {{-- Close button --}}
    <button onclick="closeOrderModal()" class="absolute top-2 right-4 text-2xl font-bold hover:text-red-400">&times;</button>

    <h2 class="text-xl font-semibold mb-4">Add Order</h2>

    {{-- NOTE: posts to production.storeOrder to create Production + Sale + Inventory update --}}
    <form action="{{ route('production.storeOrder', $product->id) }}" method="POST" class="space-y-4">
        @csrf

        {{-- Hidden identifiers --}}
        <input type="hidden" name="product_id" value="{{ $product->id }}">

        {{-- Batch & Demand --}}
        <div>
            <label for="order_batch_number" class="block text-sm font-medium mb-1">Batch Number</label>
            <input type="text" id="order_batch_number" name="batch_number"
                   class="input-dark w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[var(--sidebar-active,#EDD100)]"
                   readonly required>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Forecasted Demand (kg)</label>
                <input type="number" step="any" name="forecasted_demand"
                       class="input-dark w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-400"
                       value="{{ (float)($product->forecasted_demand ?? 0) }}">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Produced Quantity (kg)</label>
                <input type="number" step="any" name="produced_qty_kg"
                       class="input-dark w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-400"
                       required>
            </div>
        </div>

        {{-- Cost & Price --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Unit Cost (₱)</label>
                <input type="number" step="any" name="unit_cost" id="order_unit_cost"
                       value="{{ (float)($product->unit_cost ?? 0) }}"
                       class="input-dark w-full bg-white/10 border border-white/10 rounded-xl px-3 py-2 cursor-not-allowed"
                       readonly required>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Unit Price (₱)</label>
                <input type="number" step="any" name="unit_price"
                       value="{{ (float)($product->default_price ?? 0) }}"
                       class="input-dark w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-400"
                       required>
            </div>
        </div>

        {{-- Dates --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Production Date</label>
                <input type="date" id="production_date" name="production_date"
                       class="input-dark w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-400"
                       required>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Expiration Date</label>
                <input type="date" id="expiration_date" name="expiration_date"
                       class="input-dark w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-400">
                <p class="text-xs text-[var(--muted,#A3B4A7)] mt-1">
                    Auto-calculates from shelf life ({{ (int)($product->shelf_life_days ?? 7) }} days). You can override.
                </p>
            </div>
        </div>

        {{-- Sales details --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Order Date</label>
                <input type="date" id="order_date" name="order_date"
                       class="input-dark w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-400"
                       required>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Order Quantity (kg)</label>
                <input type="number" step="any" name="order_quantity_kg"
                       class="input-dark w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-400"
                       required>
            </div>
        </div>

        {{-- Optional meta --}}
        <div>
            <label class="block text-sm font-medium mb-1">Customer (optional)</label>
            <input type="text" name="customer_name"
                   class="input-dark w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-400"
                   placeholder="Walk-in, Distributor A, etc.">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Notes</label>
            <textarea name="notes" rows="2"
                      class="input-dark w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-400"
                      placeholder="Special handling, delivery date, etc."></textarea>
        </div>

        <button type="submit"
                class="w-full mt-2 px-4 py-2 rounded-xl bg-[var(--sidebar-active,#EDD100)] text-[#1F1E1E] font-semibold shadow hover:opacity-90 transition">
            Add Order (creates Sale & updates Inventory)
        </button>
    </form>
  </div>
</div>
@endsection

@section('scripts')
<script>
    function openOrderModal() {
        generateOrderBatchNumber();
        document.getElementById("addOrderModal").classList.remove("hidden");
    }
    function closeOrderModal() {
        document.getElementById("addOrderModal").classList.add("hidden");
    }
    function generateOrderBatchNumber() {
        const now = new Date();
        const pad = n => n.toString().padStart(2, '0');
        const batch = `BATCH-${now.getFullYear()}${pad(now.getMonth()+1)}${pad(now.getDate())}-${pad(now.getHours())}${pad(now.getMinutes())}${pad(now.getSeconds())}`;
        document.getElementById("order_batch_number").value = batch;
    }

    // Auto-calc expiry from production date + shelf life
    (function autoExpiry(){
        const prod = document.getElementById('production_date');
        const exp  = document.getElementById('expiration_date');
        const shelf = {{ (int)($product->shelf_life_days ?? 7) }};
        function recalc(){
            if(!prod.value) return;
            const d = new Date(prod.value);
            d.setDate(d.getDate() + shelf);
            exp.value = d.toISOString().slice(0,10);
            exp.min = prod.value;
        }
        if (prod) { prod.addEventListener('change', recalc); }
    })();
</script>
@endsection
