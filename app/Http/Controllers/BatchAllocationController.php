<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\BatchAllocation;
use App\Models\SalesOrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BatchAllocationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    /**
     * (Optional) List allocations for a SalesOrderItem (JSON for modals).
     */
    public function byItem(SalesOrderItem $item): JsonResponse
    {
        $allocs = $item->allocations()
            ->with('batch:id,batch_code,status,produced_at,expiry_date,qty_available,qty_reserved')
            ->orderBy('id')
            ->get();

        $data = $allocs->map(fn ($a) => [
            'id'             => $a->id,
            'allocated_qty'  => (int) $a->allocated_qty,
            'locked_by_admin'=> (bool) $a->locked_by_admin,
            'override_reason'=> $a->override_reason,
            'approved_by'    => $a->approved_by,
            'approved_at'    => optional($a->approved_at)->toIso8601String(),
            'batch' => $a->batch ? [
                'id'             => $a->batch->id,
                'batch_code'     => $a->batch->batch_code,
                'status'         => $a->batch->status,
                'produced_at'    => optional($a->batch->produced_at)->toIso8601String(),
                'expiry_date'    => optional($a->batch->expiry_date)->toIso8601String(),
                'qty_available'  => (int) $a->batch->qty_available,
                'qty_reserved'   => (int) $a->batch->qty_reserved,
            ] : null,
        ]);

        return response()->json($data);
    }

    /**
     * Approve and lock an allocation (admin-only via policy).
     */
    public function approve(BatchAllocation $allocation): RedirectResponse
    {
        $this->authorize('approveOverride', $allocation);

        $allocation->update([
            'locked_by_admin' => true,
            'approved_by'     => auth()->id(),
            'approved_at'     => now(),
        ]);

        return back()->with('success', 'Allocation approved and locked.');
    }

    /**
     * Release/unlock an allocation (does not change reserved quantities).
     */
    public function release(BatchAllocation $allocation): RedirectResponse
    {
        $this->authorize('update', $allocation);

        $allocation->update([
            'locked_by_admin' => false,
            'approved_by'     => null,
            'approved_at'     => null,
            'override_reason' => null,
        ]);

        return back()->with('success', 'Allocation lock released.');
    }

    /**
     * Reallocate (move) some or all quantity to a different batch.
     * Validates product, expiry buffer, destination capacity;
     * adjusts qty_reserved with row locks to avoid races.
     */
    public function reallocate(Request $request, BatchAllocation $allocation): RedirectResponse
    {
        $this->authorize('update', $allocation);

        $validated = $request->validate([
            'to_batch_id' => ['required', 'integer', Rule::exists('batches', 'id')],
            'quantity'    => ['required', 'integer', 'min:1'],
            'reason'      => ['nullable', 'string', 'max:255'],
        ]);

        if ($allocation->locked_by_admin && ! auth()->user()->can('approveOverride', $allocation)) {
            return back()->withErrors('This allocation is locked by admin. Approval required to modify.');
        }

        try {
            DB::transaction(function () use ($allocation, $validated) {
                /** @var BatchAllocation $alloc */
                $alloc = BatchAllocation::query()
                    ->whereKey($allocation->id)->lockForUpdate()->firstOrFail();

                /** @var Batch $fromBatch */
                $fromBatch = Batch::query()
                    ->whereKey($alloc->batch_id)->lockForUpdate()->firstOrFail();

                /** @var Batch $toBatch */
                $toBatch = Batch::query()
                    ->whereKey($validated['to_batch_id'])->lockForUpdate()->firstOrFail();

                if ($toBatch->id === $fromBatch->id) {
                    throw new \InvalidArgumentException('Destination batch is the same as the current batch.');
                }

                /** @var SalesOrderItem $item */
                $item = SalesOrderItem::query()
                    ->whereKey($alloc->order_item_id)->firstOrFail();

                // product consistency
                if ($toBatch->product_id !== $item->product_id) {
                    throw new \InvalidArgumentException('Destination batch product does not match the order item product.');
                }

                // optional expiry buffer: at least +1 day beyond delivery date
                if ($item->delivery_date && $toBatch->expiry_date->lt($item->delivery_date->copy()->addDay())) {
                    throw new \InvalidArgumentException('Destination batch expires too soon for the scheduled delivery.');
                }

                $moveQty = (int) $validated['quantity'];
                if ($moveQty > $alloc->allocated_qty) {
                    throw new \InvalidArgumentException('Cannot move more than currently allocated.');
                }

                // Destination free capacity
                $toFree = max(0, (int)$toBatch->qty_available - (int)$toBatch->qty_reserved);
                if ($moveQty > $toFree) {
                    throw new \InvalidArgumentException('Not enough free quantity in destination batch.');
                }

                // Adjust reserved on both batches with clamps
                $fromDec = min($moveQty, max(0, (int)$fromBatch->qty_reserved));
                $fromBatch->qty_reserved = max(0, (int)$fromBatch->qty_reserved - $fromDec);
                $fromBatch->save();

                $toBatch->qty_reserved = (int)$toBatch->qty_reserved + $moveQty;
                $toBatch->save();

                if ($moveQty === $alloc->allocated_qty) {
                    // Full move
                    $alloc->update([
                        'batch_id'        => $toBatch->id,
                        'override_reason' => $validated['reason'] ?? $alloc->override_reason,
                        'locked_by_admin' => false,
                        'approved_by'     => null,
                        'approved_at'     => null,
                    ]);
                } else {
                    // Partial move
                    $alloc->decrement('allocated_qty', $moveQty);

                    BatchAllocation::create([
                        'batch_id'        => $toBatch->id,
                        'order_item_id'   => $alloc->order_item_id,
                        'allocated_qty'   => $moveQty,
                        'locked_by_admin' => false,
                        'override_reason' => $validated['reason'] ?? null,
                        'approved_by'     => null,
                        'approved_at'     => null,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            return back()->withErrors($e->getMessage());
        }

        return back()->with('success', 'Allocation reallocated successfully.');
    }

    /**
     * Delete an allocation and release its reserved quantity.
     */
    public function destroy(BatchAllocation $allocation): RedirectResponse
    {
        $this->authorize('delete', $allocation);

        try {
            DB::transaction(function () use ($allocation) {
                /** @var BatchAllocation $alloc */
                $alloc = BatchAllocation::query()
                    ->whereKey($allocation->id)->lockForUpdate()->firstOrFail();

                /** @var Batch $batch */
                $batch = Batch::query()
                    ->whereKey($alloc->batch_id)->lockForUpdate()->firstOrFail();

                $dec = min((int)$alloc->allocated_qty, max(0, (int)$batch->qty_reserved));
                $batch->qty_reserved = max(0, (int)$batch->qty_reserved - $dec);
                $batch->save();

                $alloc->delete(); // soft delete
            });
        } catch (\Throwable $e) {
            return back()->withErrors($e->getMessage());
        }

        return back()->with('success', 'Allocation removed and inventory released.');
    }
}
