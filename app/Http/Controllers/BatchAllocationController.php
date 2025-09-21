<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\BatchAllocation;
use App\Models\SalesOrderItem;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;   // ⬅️ add
use Illuminate\Foundation\Validation\ValidatesRequests;     // ⬅️ add
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;        // ⬅️ extend this
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BatchAllocationController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests; // ⬅️ gives authorize(), validate()

    public function __construct()
    {
        $this->middleware('auth'); // ⬅️ now recognized by IDE
    }

    /** Serialize Carbon|nullable to ISO8601 or null. */
    protected function iso(?Carbon $dt): ?string
    {
        return $dt ? $dt->toIso8601String() : null;
    }

    /** Best-effort Carbon parser: returns Carbon|null. */
    protected function toCarbon(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) return $value;
        if ($value instanceof \DateTimeInterface) return Carbon::instance($value);
        if (is_string($value) && trim($value) !== '') {
            try { return Carbon::parse($value); } catch (\Throwable) { return null; }
        }
        return null;
    }

    /**
     * List allocations for a SalesOrderItem (JSON for modals).
     */
    public function byItem(SalesOrderItem $item): JsonResponse
    {
        $allocs = $item->allocations()
            ->with('batch:id,batch_code,status,produced_at,expiry_date,qty_available,qty_reserved,product_id')
            ->orderBy('id')
            ->get();

        $data = $allocs->map(function (BatchAllocation $a) {
            $approvedAt = $this->toCarbon($a->approved_at);
            $batch      = $a->batch;

            return [
                'id'               => (int) $a->id,
                'allocated_qty'    => (int) $a->allocated_qty,
                'locked_by_admin'  => (bool) $a->locked_by_admin,
                'override_reason'  => $a->override_reason,
                'approved_by'      => $a->approved_by ? (int) $a->approved_by : null,
                'approved_at'      => $this->iso($approvedAt),
                'batch' => $batch ? [
                    'id'            => (int) $batch->id,
                    'batch_code'    => (string) $batch->batch_code,
                    'status'        => (string) $batch->status,
                    'produced_at'   => $this->iso($this->toCarbon($batch->produced_at)),
                    'expiry_date'   => $this->iso($this->toCarbon($batch->expiry_date)),
                    'qty_available' => (int) $batch->qty_available,
                    'qty_reserved'  => (int) $batch->qty_reserved,
                    'product_id'    => (int) $batch->product_id,
                ] : null,
            ];
        });

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
            'approved_by'     => Auth::id(),
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
     */
    public function reallocate(Request $request, BatchAllocation $allocation): RedirectResponse
    {
        $this->authorize('update', $allocation);

        $validated = $request->validate([
            'to_batch_id' => ['required', 'integer', Rule::exists('batches', 'id')],
            'quantity'    => ['required', 'integer', 'min:1'],
            'reason'      => ['nullable', 'string', 'max:255'],
        ]);

        if ($allocation->locked_by_admin) {
            $user = Auth::user();
            if (! $user || ! $user->can('approveOverride', $allocation)) {
                return back()->withErrors(['allocation' => 'This allocation is locked by admin. Approval required to modify.']);
            }
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

                if ((int)$toBatch->id === (int)$fromBatch->id) {
                    throw new \InvalidArgumentException('Destination batch is the same as the current batch.');
                }

                /** @var SalesOrderItem $item */
                $item = SalesOrderItem::query()
                    ->whereKey($alloc->order_item_id)->firstOrFail();

                // product consistency
                if ((int)$toBatch->product_id !== (int)$item->product_id) {
                    throw new \InvalidArgumentException('Destination batch product does not match the order item product.');
                }

                // expiry buffer: +1 day beyond delivery date (if both present)
                $delivery = $this->toCarbon($item->delivery_date);
                $toExpiry = $this->toCarbon($toBatch->expiry_date);
                if ($delivery && $toExpiry && $toExpiry->lt($delivery->copy()->addDay())) {
                    throw new \InvalidArgumentException('Destination batch expires too soon for the scheduled delivery.');
                }
                if ($delivery && ! $toExpiry) {
                    throw new \InvalidArgumentException('Destination batch has no expiry date set.');
                }

                $moveQty = (int) $validated['quantity'];
                if ($moveQty > (int)$alloc->allocated_qty) {
                    throw new \InvalidArgumentException('Cannot move more than currently allocated.');
                }

                // Destination free capacity
                $toReserved = max(0, (int)$toBatch->qty_reserved);
                $toFree     = max(0, (int)$toBatch->qty_available - $toReserved);
                if ($moveQty > $toFree) {
                    throw new \InvalidArgumentException('Not enough free quantity in destination batch.');
                }

                // Adjust reserved
                $fromReserved = max(0, (int)$fromBatch->qty_reserved);
                $fromDec      = min($moveQty, $fromReserved);
                $fromBatch->qty_reserved = max(0, $fromReserved - $fromDec);
                $fromBatch->save();

                $toBatch->qty_reserved = $toReserved + $moveQty;
                $toBatch->save();

                if ($moveQty === (int)$alloc->allocated_qty) {
                    // Full move
                    $alloc->update([
                        'batch_id'        => (int) $toBatch->id,
                        'override_reason' => $validated['reason'] ?? $alloc->override_reason,
                        'locked_by_admin' => false,
                        'approved_by'     => null,
                        'approved_at'     => null,
                    ]);
                } else {
                    // Partial move
                    $alloc->decrement('allocated_qty', $moveQty);

                    BatchAllocation::create([
                        'batch_id'        => (int) $toBatch->id,
                        'order_item_id'   => (int) $alloc->order_item_id,
                        'allocated_qty'   => (int) $moveQty,
                        'locked_by_admin' => false,
                        'override_reason' => $validated['reason'] ?? null,
                        'approved_by'     => null,
                        'approved_at'     => null,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['reallocate' => $e->getMessage()]);
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

                $batchReserved = max(0, (int)$batch->qty_reserved);
                $dec           = min((int)$alloc->allocated_qty, $batchReserved);

                $batch->qty_reserved = max(0, $batchReserved - $dec);
                $batch->save();

                $alloc->delete(); // soft delete
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['delete' => $e->getMessage()]);
        }

        return back()->with('success', 'Allocation removed and inventory released.');
    }
}
