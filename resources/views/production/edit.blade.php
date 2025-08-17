@extends('layout.mainlayout')

@section('content')
<div class="glass section-liquid-shine text-white p-6 rounded-2xl shadow-md w-full max-w-2xl mx-auto border border-dark-line">
    <h2 class="text-xl font-semibold mb-4 tracking-wide">Edit Production</h2>

    <form action="{{ route('production.update', $product->id) }}" method="POST" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm text-white/80 mb-1">Product Name</label>
            <input name="product_name" value="{{ $product->product_name }}" required
                   class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 text-white/90 placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-emerald-400">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm text-white/80 mb-1">Forecasted Demand (kg)</label>
                <input type="number" name="forecasted_demand" value="{{ $product->forecasted_demand }}" required
                       class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 text-white/90 focus:outline-none focus:ring-2 focus:ring-emerald-400">
            </div>
            <div>
                <label class="block text-sm text-white/80 mb-1">Current Inventory (kg)</label>
                <input type="number" name="current_inventory" value="{{ $product->current_inventory }}" required
                       class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 text-white/90 focus:outline-none focus:ring-2 focus:ring-emerald-400">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm text-white/80 mb-1">Unit Cost</label>
                <input type="number" step="0.01" name="unit_cost" value="{{ $product->unit_cost }}" required
                       class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 text-white/90 focus:outline-none focus:ring-2 focus:ring-emerald-400">
            </div>
            <div>
                <label class="block text-sm text-white/80 mb-1">Production Date</label>
                <input type="date" name="production_date" value="{{ \Carbon\Carbon::parse($product->production_date)->format('Y-m-d') }}" required
                       class="w-full rounded-xl bg-white/5 border border-white/10 px-3 py-2 text-white/90 focus:outline-none focus:ring-2 focus:ring-emerald-400">
            </div>
        </div>

        <button type="submit" class="w-full mt-2 px-4 py-2 rounded-xl bg-[var(--sidebar-active,#EDD100)] text-[#1F1E1E] font-semibold shadow hover:opacity-90 transition">Update Production</button>
    </form>
</div>
@endsection
