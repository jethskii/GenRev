<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\BatchAllocation;
use App\Models\SalesOrderItem;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AllocationService
{
    public function allocateForItem(SalesOrderItem $item, int $bufferDays = 1): void
    {
        if ($item->quantity <= 0) {
            throw new InvalidArgumentException('Item quantity must be > 0');
        }

        DB::transaction(function () use ($item, $bufferDays) {
            // revert any existing allocations for this item first (idempotent re-run)
            $this->releaseAllocations($item);

            $needed = $item->quantity;

            $batches = Batch::query()
                ->released()
                ->where('product_id', $item->product_id)
                ->whereDate('expiry_date', '>=', optional($item->delivery_date)->copy()->addDays($bufferDays) ?? now()->addDays($bufferDays))
                ->orderBy('dispatch_sequence')
                ->orderBy('expiry_date')
                ->lockForUpdate() // prevent race conditions
                ->get();

            foreach ($batches as $b) {
                $free = max(0, $b->qty_available - $b->qty_reserved);
                if ($free <= 0) continue;

                $take = min($needed, $free);
                if ($take <= 0) continue;

                BatchAllocation::create([
                    'batch_id'      => $b->id,
                    'order_item_id' => $item->id,
                    'allocated_qty' => $take,
                ]);

                // update reserved qty
                $b->increment('qty_reserved', $take);

                $needed -= $take;
                if ($needed === 0) break;
            }

            if ($needed > 0) {
                // not enough inventory – rollback allocations for this item
                throw new InvalidArgumentException('Insufficient batch inventory to allocate this item.');
            }
        });
    }

    public function releaseAllocations(SalesOrderItem $item): void
    {
        DB::transaction(function () use ($item) {
            $allocs = $item->allocations()->with('batch')->lockForUpdate()->get();

            foreach ($allocs as $alloc) {
                if ($alloc->batch) {
                    $alloc->batch->decrement('qty_reserved', $alloc->allocated_qty);
                }
            }

            $item->allocations()->delete(); // soft delete
        });
    }
}
