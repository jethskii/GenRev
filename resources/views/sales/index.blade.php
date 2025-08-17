{{-- resources/views/sales/index.blade.php --}}
@php
    /** @var \Illuminate\Support\Collection|\App\Models\Sale[] $sales */
    $statusColors = [
        'Paid'      => 'bg-[#047705]/15 text-[#91F0A6] border-[#047705]/30',
        'Completed' => 'bg-sky-600/15 text-sky-300 border-sky-700/40',
        'Pending'   => 'bg-[#EDD100]/15 text-[#EDD100] border-[#EDD100]/30',
        'Cancelled' => 'bg-rose-600/15 text-rose-300 border-rose-700/40',
    ];
@endphp

@extends('layout.mainlayout')

@section('head')
    {{-- Jost for UI parity with Items page --}}
    <link href="https://fonts.googleapis.com/css2?family=Jost:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
      body, p, ul, li, a, button { font-family: 'Jost', system-ui, -apple-system, Segoe UI, Roboto, sans-serif; }
      .liquid-card {
        position: relative; overflow: hidden; border-radius: 16px;
        background: linear-gradient(135deg,#1F1E1E 0%, #001C00 100%);
        border: .5px solid rgba(255,255,255,.2);
        box-shadow: 0 8px 32px rgba(0,28,0,.35);
        backdrop-filter: blur(8px);
      }
      .liquid-card::before{
        content:''; position:absolute; inset:0;
        background: linear-gradient(45deg, rgba(4,119,5,.10), rgba(237,209,0,.08), rgba(4,119,5,.10));
        animation: cardShine 8s ease infinite; pointer-events:none;
      }
      @keyframes cardShine { 0%{opacity:.35} 50%{opacity:.15} 100%{opacity:.35} }
      .liquid-input {
        background: rgba(255,255,255,.08);
        border: 1px solid rgba(255,255,255,.2);
        color: #fff;
      }
      .liquid-input::placeholder{ color: rgba(255,255,255,.6) }
      .liquid-input:focus{
        outline: none; border-color:#047705; box-shadow:0 0 0 2px rgba(4,119,5,.3);
      }
      .chip { border:1px solid; padding:.2rem .6rem; border-radius:999px; font-size:.72rem; }
      .btn-primary {
        background: linear-gradient(90deg,#047705 0%, #0aad0a 100%);
        color:#fff; border:1px solid rgba(255,255,255,.15);
        border-radius:12px; padding:.5rem 1rem; transition:.2s;
        box-shadow: 0 4px 15px rgba(4,119,5,.35);
      }
      .btn-primary:hover{ transform: translateY(-1px); }
      .btn-ghost{
        border:1px solid rgba(255,255,255,.15);
        color:#f8fafc; border-radius:10px; padding:.4rem .8rem;
        background: rgba(255,255,255,.03);
      }
      .table-wrap{ border:1px solid rgba(255,255,255,.15); border-radius:14px; overflow:hidden; }
      thead th{ background: rgba(255,255,255,.05); color:#cbd5e1; font-weight:600; }
      tbody td{ color:#e5e7eb; }
    </style>
@endsection

@section('content')
<div class="px-6 py-6">
    {{-- Page header --}}
    <div class="mb-4">
        <h1 class="text-2xl font-semibold text-white">Sales Overview</h1>
        <p class="text-sm text-gray-400">Monitor receipts, totals, and statuses at a glance.</p>
    </div>

    {{-- Card --}}
    <div class="liquid-card">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 px-5 py-4">
            <h2 class="text-xl font-semibold text-white">Sales</h2>

            <div class="flex flex-wrap items-center gap-3">
                {{-- Search box --}}
                <div class="relative">
                    <input id="salesSearch" type="text" placeholder="Search invoice / product / status…"
                           class="w-64 rounded-xl liquid-input px-3 py-2 pr-8">
                    <span class="absolute right-3 top-2.5 text-white/60">⌕</span>
                </div>

                {{-- Date filter --}}
                <input id="dateFilter" type="date"
                       class="rounded-xl liquid-input px-3 py-2">

                {{-- Add Sale button --}}
                <button type="button" onclick="toggleAddSaleModal(true)" class="btn-primary">
                    + Add New Sale
                </button>
            </div>
        </div>

        {{-- Table --}}
        <div class="px-5 pb-5">
            <div class="table-wrap">
                <table class="min-w-full border-collapse">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left">Invoice</th>
                            <th class="px-4 py-3 text-left">Product</th>
                            <th class="px-4 py-3 text-left">Date</th>
                            <th class="px-4 py-3 text-right">Quantity (kg)</th>
                            <th class="px-4 py-3 text-right">Unit Price</th>
                            <th class="px-4 py-3 text-right">Total</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="salesTableBody" class="divide-y divide-white/10">
                        @forelse($sales as $row)
                            @php
                                // Prefer new schema, fallback to legacy
                                $pname = $row->display_product ?? ($row->product ?? optional($row->productRef)->product_name);
                                $date  = $row->order_date ?? $row->date;
                                $qty   = (float) ($row->quantity_kg ?? $row->quantity ?? 0);
                                $unit  = (float) ($row->unit_price ?? $row->price ?? 0);
                                $tot   = (float) ($row->total_price ?? $row->total ?? ($qty * $unit));
                            @endphp
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap">{{ $row->invoice_number ?? $row->order_number }}</td>
                                <td class="px-4 py-3">
                                    {{ $pname }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ $date ? \Carbon\Carbon::parse($date)->format('Y-m-d') : '' }}
                                </td>
                                <td class="px-4 py-3 text-right">{{ number_format($qty, 3) }}</td>
                                <td class="px-4 py-3 text-right">₱ {{ number_format($unit, 2) }}</td>
                                <td class="px-4 py-3 text-right">₱ {{ number_format($tot, 2) }}</td>
                                <td class="px-4 py-3">
                                    @php $cls = $statusColors[$row->status] ?? 'bg-white/10 text-white/80 border-white/20'; @endphp
                                    <span class="chip {{ $cls }}">{{ $row->status ?? 'Pending' }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                        @if (Route::has('sales.receipt'))<a href="{{ route('sales.receipt', $row) }}" class="btn-ghost">Receipt</a>@endif
                                        @if (Route::has('sales.download'))<a href="{{ route('sales.download', $row) }}" class="btn-ghost">PDF</a>@endif
                                        @if (Route::has('sales.edit'))    <a href="{{ route('sales.edit', $row) }}" class="btn-ghost">Edit</a>@endif
                                        @if (Route::has('sales.destroy'))
                                        <form action="{{ route('sales.destroy', $row) }}" method="POST"
                                              onsubmit="return confirm('Delete this sale?')" class="inline">
                                            @csrf @method('DELETE')
                                            <button class="btn-ghost border-rose-800/60 text-rose-300 hover:bg-rose-900/20">
                                                Delete
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-6 text-center text-white/70">No sales yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Footer meta --}}
            <div class="flex items-center justify-between text-sm text-white/70 mt-4">
                <div>
                    <span class="mr-2">INV-</span>{{ now()->format('Ymd') }}<span class="mx-2">—</span>{{ $nextInvoice }}
                </div>
                <div></div>
            </div>
        </div>
    </div>
</div>

{{-- Modal include (kept as-is) --}}
@include('sales.partials.add-sale-modal', [
    'products' => $products ?? null,
    'statusOptions' => $statusOptions ?? ['Pending','Completed','Cancelled','Paid'],
    'nextInvoice' => $nextInvoice
])
@endsection

@push('scripts')
<script>
    // Simple client-side search by product/invoice/status + date filter
    const search = document.getElementById('salesSearch');
    const dateFilter = document.getElementById('dateFilter');
    const rows = Array.from(document.querySelectorAll('#salesTableBody tr'));

    function applyFilters() {
        const term = (search?.value || '').toLowerCase();
        const date = (dateFilter?.value || '');

        rows.forEach(tr => {
            const tds = tr.querySelectorAll('td');
            if (!tds.length) return;
            const invoice = (tds[0].textContent || '').toLowerCase();
            const product = (tds[1].textContent || '').toLowerCase();
            const rowDate = (tds[2].textContent || '').trim(); // YYYY-MM-DD
            const status = (tds[6].textContent || '').toLowerCase();

            const matchTerm = !term || invoice.includes(term) || product.includes(term) || status.includes(term);
            const matchDate = !date || rowDate === date;

            tr.style.display = (matchTerm && matchDate) ? '' : 'none';
        });
    }

    search?.addEventListener('input', applyFilters);
    dateFilter?.addEventListener('change', applyFilters);

    // Prevent accidental double-submit from any form on this page
    document.querySelectorAll('form').forEach(f => {
        f.addEventListener('submit', () => {
            const btn = f.querySelector('button[type="submit"], input[type="submit"]');
            if (btn) { btn.disabled = true; btn.classList.add('opacity-70','cursor-not-allowed'); }
        });
    });

    // Ensure only one Add-Sale submit happens (if your modal has a form with id=saleForm)
    window.toggleAddSaleModal = (open) => {
        const el = document.getElementById('addSaleModal');
        if (!el) return;
        el.classList.toggle('hidden', !open);
        el.classList.toggle('flex', open);
        if (open) {
            // Reset the form values and buttons if needed
            const form = el.querySelector('form');
            if (form) {
                form.reset();
                const btn = form.querySelector('button[type="submit"]');
                if (btn) { btn.disabled = false; btn.classList.remove('opacity-70','cursor-not-allowed'); }
            }
        }
    };
</script>
@endpush
