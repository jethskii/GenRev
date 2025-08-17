@php
    // Props: $productId (int), optional $deliveryDate (string Y-m-d)
@endphp

<div class="mt-2">
    <div class="flex items-center justify-between">
        <div class="text-sm opacity-70">
            Product ID: <span class="font-semibold">{{ $productId }}</span>
            @if(!empty($deliveryDate))
                • Delivery: <span class="font-semibold">{{ $deliveryDate }}</span>
            @endif
        </div>
        <div class="text-sm opacity-70">Tip: pick batches with green days-left for fresher stock.</div>
    </div>

    <div class="overflow-x-auto mt-3">
        <table class="table w-full">
            <thead>
                <tr>
                    <th>Batch Code</th>
                    <th>Status</th>
                    <th>Produced</th>
                    <th>Expiry</th>
                    <th>Days Left</th>
                    <th class="text-right">Free Qty</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="picker-tbody">
                <tr>
                    <td colspan="7" class="py-6 text-center opacity-70">Loading batches…</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
