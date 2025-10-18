@php
    /** @var \App\Models\SalesOrderItem $item */
    // Derive unit type (for display + validations): 'pack' | 'bag' | null
    $unitType = $item->unit_type ?? $item->unit ?? null;
    $unitType = in_array($unitType, ['pack','bag'], true) ? $unitType : null;

    // Helper: format qty based on unit type (whole for pack/bag, 3dp otherwise)
    $fmtQty = function($n) use ($unitType) {
        $n = (float) $n;
        return $unitType ? number_format($n, 0) : number_format($n, 3);
    };

    $unitLabel = $unitType ? ($unitType === 'pack' ? 'pack(s)' : 'bag(s)') : 'kg';
@endphp

{{-- Batch Allocations – Liquid/Glass card --}}
<div class="mt-6 rounded-2xl border border-white/10 bg-gradient-to-br from-[#1F1E1E]/95 to-[#001C00]/80 text-white shadow-xl overflow-hidden">
    {{-- Header --}}
    <div class="flex items-center justify-between px-5 py-4 border-b border-white/10">
        <h3 class="text-lg font-semibold" style="text-shadow:-1px 1px 0 #047705">Batch Allocations</h3>

        <div class="flex flex-wrap items-center gap-2 text-sm">
            <span class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-2.5 py-1">
                Needed:
                <span class="font-semibold text-yellow-300">
                    {{ $fmtQty($item->quantity) }} <span class="opacity-75">/{{ $unitLabel }}</span>
                </span>
            </span>
            <span class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-2.5 py-1">
                Allocated:
                <span class="font-semibold text-emerald-300">
                    {{ $fmtQty($item->allocated_qty) }} <span class="opacity-75">/{{ $unitLabel }}</span>
                </span>
            </span>
            <span class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-2.5 py-1">
                Remaining:
                <span class="font-semibold text-sky-300">
                    {{ $fmtQty($item->remaining_qty) }} <span class="opacity-75">/{{ $unitLabel }}</span>
                </span>
            </span>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gradient-to-r from-[#047705] to-[#0aad0a] text-white">
                <tr>
                    <th class="px-4 py-2 text-left font-medium">Batch Code</th>
                    <th class="px-4 py-2 text-left font-medium">Status</th>
                    <th class="px-4 py-2 text-left font-medium">Produced</th>
                    <th class="px-4 py-2 text-left font-medium">Expiry</th>
                    <th class="px-4 py-2 text-left font-medium">Days Left</th>
                    <th class="px-4 py-2 text-right font-medium">Allocated Qty ({{ $unitLabel }})</th>
                    <th class="px-4 py-2 text-left font-medium">Lock</th>
                    <th class="px-4 py-2 text-left font-medium">Approved By</th>
                    <th class="px-4 py-2 text-right font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/10">
                @forelse($item->allocations()->with('batch')->orderBy('id')->get() as $allocation)
                    @php
                        $batch = $allocation->batch;
                        $daysLeft = $batch?->days_to_expiry;
                        $daysClass = $daysLeft === null ? '' : ($daysLeft < 2 ? 'text-red-400' : ($daysLeft < 6 ? 'text-yellow-300' : 'text-emerald-300'));
                    @endphp
                    <tr class="hover:bg-white/5 transition">
                        <td class="px-4 py-2 font-mono">{{ $batch?->batch_code ?? '—' }}</td>
                        <td class="px-4 py-2">{{ $batch?->status ?? '—' }}</td>
                        <td class="px-4 py-2">{{ optional($batch?->produced_at)->format('Y-m-d') }}</td>
                        <td class="px-4 py-2">{{ optional($batch?->expiry_date)->format('Y-m-d') }}</td>
                        <td class="px-4 py-2 {{ $daysClass }}">{{ $daysLeft ?? '—' }}</td>
                        <td class="px-4 py-2 text-right">{{ $fmtQty($allocation->allocated_qty) }}</td>
                        <td class="px-4 py-2">
                            @if($allocation->locked_by_admin)
                                <span class="inline-flex items-center rounded-full bg-emerald-600/20 text-emerald-300 border border-emerald-700/40 px-2 py-0.5 text-xs">Locked</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-white/10 text-white/80 border border-white/20 px-2 py-0.5 text-xs">Unlocked</span>
                            @endif
                        </td>
                        <td class="px-4 py-2">{{ optional($allocation->approved_by)->name ?? ($allocation->approved_by ? $allocation->approved_by : '—') }}</td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-2">
                                {{-- Approve & Lock --}}
                                @can('approveOverride', $allocation)
                                <form action="{{ route('allocations.approve', $allocation) }}" method="POST" class="inline">
                                    @csrf @method('PATCH')
                                    <button class="rounded-lg bg-emerald-600/80 hover:bg-emerald-600 text-white px-2.5 py-1 text-xs transition" title="Approve & Lock">
                                        Approve
                                    </button>
                                </form>
                                @endcan

                                {{-- Release --}}
                                @can('update', $allocation)
                                <form action="{{ route('allocations.release', $allocation) }}" method="POST" class="inline">
                                    @csrf @method('PATCH')
                                    <button class="rounded-lg bg-white/10 hover:bg-white/15 border border-white/15 text-white px-2.5 py-1 text-xs transition" title="Release Lock">
                                        Release
                                    </button>
                                </form>
                                @endcan

                                {{-- Reallocate --}}
                                @can('update', $allocation)
                                <button
                                    class="rounded-lg bg-amber-600/80 hover:bg-amber-600 text-white px-2.5 py-1 text-xs transition"
                                    title="Reallocate"
                                    data-reallocate
                                    data-allocation-id="{{ $allocation->id }}"
                                    data-item-id="{{ $allocation->order_item_id }}"
                                    data-product-id="{{ $item->product_id }}"
                                    data-current-qty="{{ $allocation->allocated_qty }}"
                                    data-unit-type="{{ $unitType ?? '' }}"
                                >Reallocate</button>
                                @endcan

                                {{-- Delete --}}
                                @can('delete', $allocation)
                                <form action="{{ route('allocations.destroy', $allocation) }}" method="POST" class="inline" onsubmit="return confirm('Remove this allocation?');">
                                    @csrf @method('DELETE')
                                    <button class="rounded-lg bg-rose-700/80 hover:bg-rose-700 text-white px-2.5 py-1 text-xs transition">
                                        Delete
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-sm text-white/70">
                            No allocations yet. Use the Allocate action in your Sales flow or the Reallocate button after picking a batch.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Reallocate Modal – Liquid/Glass --}}
<div id="reallocate-modal" class="hidden fixed inset-0 z-50" aria-modal="true" role="dialog" aria-labelledby="reallocTitle">
    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" data-close-modal aria-hidden="true"></div>

    {{-- Modal box --}}
    <div class="relative mx-auto mt-24 w-[95%] max-w-5xl rounded-2xl border border-white/10
                bg-gradient-to-br from-[#1F1E1E]/95 to-[#001C00]/85 text-white shadow-2xl">
        <div class="px-6 py-4 border-b border-white/10 flex items-center justify-between">
            <h3 id="reallocTitle" class="text-lg font-semibold" style="text-shadow:-1px 1px 0 #047705">Reallocate to Another Batch</h3>
            <button type="button" class="grid h-9 w-9 place-items-center rounded-full border border-white/10 text-white/80 hover:text-white hover:bg-white/10 transition" data-close-modal aria-label="Close">✖</button>
        </div>

        <form id="reallocate-form" method="POST" class="px-6 py-5">
            @csrf
            @method('PATCH')

            <input type="hidden" name="to_batch_id" id="to_batch_id">
            <input type="hidden" name="quantity"  id="move_qty">
            <input type="hidden" name="reason"    id="move_reason">

            {{-- Batch picker --}}
            <div class="mt-2">
                @include('allocations._batch_picker', [
                    'productId' => $item->product_id,
                    'deliveryDate' => optional($item->delivery_date)?->format('Y-m-d')
                ])
            </div>

            {{-- Inputs --}}
            <div class="mt-4 grid gap-3 md:grid-cols-3">
                <div>
                    <label class="block text-sm text-white/80 mb-1">
                        Move Quantity
                        <span id="unitBadge" class="ml-1 inline-flex items-center rounded-full border border-white/10 bg-white/5 px-2 py-0.5 text-[.7rem] align-middle">
                            {{ $unitLabel }}
                        </span>
                    </label>
                    <input
                        type="number"
                        id="move_qty_input"
                        class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2.5 text-white outline-none focus:border-[#047705] focus:ring-2 focus:ring-[#047705]/30"
                        placeholder="e.g. {{ $unitType ? '10' : '5.000' }}"
                        @if($unitType) step="1" min="1" @else step="0.001" min="0.001" @endif
                    >
                    <small id="move_qty_hint" class="text-white/70"></small>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm text-white/80 mb-1">Reason (optional)</label>
                    <input type="text" class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2.5 text-white outline-none focus:border-[#047705] focus:ring-2 focus:ring-[#047705]/30" id="move_reason_input" placeholder="Reason for reallocation">
                </div>
            </div>

            {{-- Actions --}}
            <div class="mt-6 flex items-center justify-end gap-2 border-t border-white/10 pt-4">
                <button type="button" class="rounded-xl px-4 py-2 text-white/90 border border-white/10 bg-white/5 hover:bg-white/10 transition" data-close-modal>
                    Cancel
                </button>
                <button type="submit" class="rounded-xl px-4 py-2 bg-gradient-to-r from-[#047705] to-[#0aad0a] text-white shadow-[0_6px_18px_rgba(4,119,5,.35)] hover:brightness-110 active:scale-[.99] transition">
                    Confirm Reallocation
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function(){
    const modal      = document.getElementById('reallocate-modal');
    const form       = document.getElementById('reallocate-form');
    const toBatchId  = document.getElementById('to_batch_id');
    const qtyHidden  = document.getElementById('move_qty');
    const reasonHid  = document.getElementById('move_reason');
    const qtyInput   = document.getElementById('move_qty_input');
    const reasonIn   = document.getElementById('move_reason_input');
    const qtyHint    = document.getElementById('move_qty_hint');
    const unitBadge  = document.getElementById('unitBadge');

    let currentAllocationId = null;
    let currentMaxQty = null;
    let currentUnitType = null; // 'pack' | 'bag' | ''

    // open modal
    document.querySelectorAll('[data-reallocate]').forEach(btn => {
        btn.addEventListener('click', () => {
            currentAllocationId = btn.dataset.allocationId;
            currentMaxQty       = parseFloat(btn.dataset.currentQty || '0');
            currentUnitType     = (btn.dataset.unitType || '').trim();

            // Adjust step/min and badge label depending on unit type
            if (currentUnitType === 'pack' || currentUnitType === 'bag') {
                qtyInput.setAttribute('step','1');
                qtyInput.setAttribute('min','1');
                unitBadge.textContent = currentUnitType === 'pack' ? 'pack(s)' : 'bag(s)';
                qtyInput.placeholder = 'e.g. 10';
            } else {
                qtyInput.setAttribute('step','0.001');
                qtyInput.setAttribute('min','0.001');
                unitBadge.textContent = 'kg';
                qtyInput.placeholder = 'e.g. 5.000';
            }

            // Initialize with max and hint
            qtyInput.value = (currentUnitType ? Math.floor(currentMaxQty) : Number(currentMaxQty).toFixed(3));
            qtyHint.textContent = `Max you can move: ${currentUnitType ? Math.floor(currentMaxQty) : Number(currentMaxQty).toFixed(3)}`;
            toBatchId.value = '';
            reasonIn.value  = '';

            form.action = `{{ url('/allocations') }}/${currentAllocationId}/reallocate`;
            modal.classList.remove('hidden');
            loadBatches(btn.dataset.productId);
        });
    });

    // select batch in the picker table
    document.addEventListener('click', (e) => {
        const row = e.target.closest('[data-pick-batch]');
        if (!row) return;
        const batchId = row.dataset.id;
        toBatchId.value = batchId;

        document.querySelectorAll('[data-pick-batch]').forEach(r => r.classList.remove('ring','ring-[#047705]','bg-white/5'));
        row.classList.add('ring','ring-[#047705]','bg-white/5');
    });

    // close modal
    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => modal.classList.add('hidden'));
    });

    // Esc to close
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') modal.classList.add('hidden');
    });

    // submit → mirror inputs into hidden
    form.addEventListener('submit', (e) => {
        if (!toBatchId.value) {
            e.preventDefault();
            alert('Please select a destination batch.');
            return;
        }
        const raw = qtyInput.value || '0';
        const move = currentUnitType ? parseInt(raw, 10) : parseFloat(raw);
        const max  = currentUnitType ? Math.floor(currentMaxQty) : parseFloat(currentMaxQty);

        if (!move || move < (currentUnitType ? 1 : 0.001) || move > max) {
            e.preventDefault();
            alert('Invalid quantity to move.');
            return;
        }
        qtyHidden.value = move;
        reasonHid.value = reasonIn.value || '';
    });

    // load batches (expects #picker-tbody in included partial)
    async function loadBatches(productId) {
        const url = `{{ route('production.batches.byProduct', ['product' => 'PID']) }}`.replace('PID', productId);
        const tableBody = document.getElementById('picker-tbody');
        if (!tableBody) return;
        tableBody.innerHTML = `<tr><td colspan="7" class="py-6 text-center text-white/70">Loading batches…</td></tr>`;

        try {
            const res  = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
            const data = await res.json();
            if (!Array.isArray(data) || !data.length) {
                tableBody.innerHTML = `<tr><td colspan="7" class="py-6 text-center text-white/70">No eligible batches found.</td></tr>`;
                return;
            }

            tableBody.innerHTML = data.map(b => {
                const freeRaw = Math.max(0, (parseFloat(b.qty_available ?? 0) - parseFloat(b.qty_reserved ?? 0)));
                const free    = (currentUnitType ? Math.floor(freeRaw) : freeRaw);
                const days    = b.days_to_expiry ?? '';
                const color   = days === '' ? '' : (days < 2 ? 'text-red-400' : (days < 6 ? 'text-yellow-300' : 'text-emerald-300'));

                const fmtFree = currentUnitType ? free.toLocaleString() : Number(free).toFixed(3);

                return `
                    <tr class="cursor-pointer hover:bg-white/5 transition" data-pick-batch data-id="${b.id}">
                        <td class="px-3 py-2 font-mono">${b.batch_code ?? ''}</td>
                        <td class="px-3 py-2">${b.status ?? ''}</td>
                        <td class="px-3 py-2">${(b.produced_at ?? '').slice(0,10)}</td>
                        <td class="px-3 py-2">${(b.expiry_date ?? '').slice(0,10)}</td>
                        <td class="px-3 py-2 ${color}">${days}</td>
                        <td class="px-3 py-2 text-right">${fmtFree}</td>
                        <td class="px-3 py-2">
                            <span class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-2 py-0.5 text-xs">Pick</span>
                        </td>
                    </tr>
                `;
            }).join('');
        } catch (e) {
            tableBody.innerHTML = `<tr><td colspan="7" class="py-6 text-center text-rose-300">Failed to load batches.</td></tr>`;
        }
    }
})();
</script>
@endpush
