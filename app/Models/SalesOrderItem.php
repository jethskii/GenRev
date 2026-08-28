<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class SalesOrderItem extends Model
{
    use SoftDeletes;

    protected $table = 'sales_order_items';

    /** Optional per-item status */
    public const STATUS_PENDING    = 'Pending';
    public const STATUS_ALLOCATED  = 'Allocated';
    public const STATUS_FULFILLED  = 'Fulfilled';
    public const STATUS_CANCELLED  = 'Cancelled';

    /** Supported unit types for quantity */
    public const UNIT_KG   = 'kg';
    public const UNIT_PACK = 'pack';
    public const UNIT_BAG  = 'bag';

    protected $fillable = [
        'sales_order_id',
        'product_id',
        'production_id',      // optional traceability to a specific batch
        'description',        // display name / override

        // quantity + unitization
        'quantity',
        'unit_type',          // 'kg' | 'pack' | 'bag' (nullable → treat as 'kg')

        // pricing
        'unit_price',
        'total_price',

        // type/variant (dashboard)
        'type_label',

        // logistics/meta
        'delivery_date',
        'status',
        'notes',

        // ---- optional legacy/fallback columns (do not create if you don't have them) ----
        'variant_name',
        'variant',
        'type',
        'product_type',
    ];

    protected $casts = [
        'quantity'      => 'decimal:3', // stored with precision; display varies by unit_type
        'unit_price'    => 'decimal:2',
        'total_price'   => 'decimal:2',
        'delivery_date' => 'datetime:Y-m-d',
        'status'        => 'string',
        'unit_type'     => 'string',

        'type_label'    => 'string',
        'variant_name'  => 'string',
        'variant'       => 'string',
        'type'          => 'string',
        'product_type'  => 'string',
    ];

    /** Automatically expose useful derived fields */
    protected $appends = [
        'allocated_qty',
        'remaining_qty',
        'is_fully_allocated',
        'unit_label',
        'quantity_display',
        'has_problems',

        // type/variant helpers for the dashboard
        'type_label_clean',
        'qty_value',
        'revenue_value',
        'display_name',
    ];

    /* ----------------
     |  Relationships
     * ---------------- */

    public function order()
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function production()
    {
        return $this->belongsTo(Production::class);
    }

    /**
     * Allocation rows per batch.
     * Expected table (recommended): batch_allocations with columns:
     *   id, order_item_id, production_id, mode, quantity_value, timestamps
     *
     * This model will also work if the table has (sale_id) instead of (order_item_id),
     * but precise reversal then won’t attach to the item. It will still deduct/restore FIFO.
     */
    public function allocations()
    {
        return $this->hasMany(BatchAllocation::class, 'order_item_id');
    }

    /* -------------
     |  Accessors
     * ------------- */

    /** 'kg' | 'pack(s)' | 'bag(s)' */
    public function getUnitLabelAttribute(): string
    {
        $u = strtolower((string) ($this->unit_type ?: self::UNIT_KG));
        return match ($u) {
            self::UNIT_PACK => 'pack(s)',
            self::UNIT_BAG  => 'bag(s)',
            default         => 'kg',
        };
    }

    /** Formatted quantity string respecting the unit_type (integer for pack/bag, 3dp for kg) */
    public function getQuantityDisplayAttribute(): string
    {
        $qty = (float) ($this->quantity ?? 0);
        $u   = strtolower((string) ($this->unit_type ?: self::UNIT_KG));
        if ($u === self::UNIT_PACK || $u === self::UNIT_BAG) {
            return number_format((int) round($qty), 0) . ' ' . $this->unit_label;
        }
        return number_format($qty, 3) . ' ' . $this->unit_label;
    }

    /** Numeric quantity for math (kg-aware) */
    public function getQtyValueAttribute(): float
    {
        $qty = (float) ($this->quantity ?? 0);
        $u   = strtolower((string) ($this->unit_type ?: self::UNIT_KG));
        return ($u === self::UNIT_PACK || $u === self::UNIT_BAG)
            ? (float) (int) round($qty)
            : (float) round($qty, 3);
    }

    /** Numeric total for math (computed if needed) */
    public function getRevenueValueAttribute(): float
    {
        $qty  = $this->qty_value;
        $unit = (float) ($this->unit_price ?? 0);
        return (float) ($this->total_price ?? round($qty * $unit, 2));
    }

    public function getAllocatedQtyAttribute(): float
    {
        // If allocation table/column is missing, treat as 0 for UI
        if (!Schema::hasTable('batch_allocations') || !Schema::hasColumn('batch_allocations', 'order_item_id')) {
            return 0.0;
        }

        $sum = (float) ($this->allocations()->sum('quantity_value') ?? 0);

        $u = strtolower((string) ($this->unit_type ?: self::UNIT_KG));
        if ($u === self::UNIT_PACK || $u === self::UNIT_BAG) {
            return (float) (int) round($sum);
        }
        return (float) $sum;
    }

    public function getRemainingQtyAttribute(): float
    {
        $qty = $this->qty_value;
        $rem = max(0, $qty - $this->allocated_qty);

        $u = strtolower((string) ($this->unit_type ?: self::UNIT_KG));
        if ($u === self::UNIT_PACK || $u === self::UNIT_BAG) {
            return (float) (int) round($rem);
        }
        return (float) round($rem, 3);
    }

    public function getIsFullyAllocatedAttribute(): bool
    {
        // If there is no allocation table, assume fully allocated once saved (we deduct directly)
        if (!Schema::hasTable('batch_allocations') || !Schema::hasColumn('batch_allocations', 'order_item_id')) {
            return true;
        }
        return $this->remaining_qty <= 0;
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->description ?: optional($this->product)->product_name ?: 'Unnamed Item';
    }

    public function getTypeLabelCleanAttribute(): string
    {
        $raw = $this->type_label
            ?? $this->variant_name
            ?? $this->variant
            ?? $this->type
            ?? $this->product_type
            ?? '';

        $label = trim(preg_replace('/\s+/', ' ', (string) $raw));
        if ($label === '') return 'Unspecified';

        return mb_convert_case($label, MB_CASE_TITLE, 'UTF-8');
    }

    public function getHasProblemsAttribute(): bool
    {
        // Under-allocated OR any linked batch expired (if you model that)
        if (!$this->is_fully_allocated) return true;
        return false;
    }

    /* ----------
     |  Scopes
     * ---------- */

    public function scopeNeedingAllocation($q)
    {
        if (!Schema::hasTable('batch_allocations') || !Schema::hasColumn('batch_allocations', 'order_item_id')) {
            // If we cannot track allocations, nothing to do here
            return $q->whereRaw('1=0');
        }

        return $q->whereRaw('COALESCE(
            (SELECT SUM(quantity_value) FROM batch_allocations 
             WHERE order_item_id = sales_order_items.id), 0
        ) < quantity');
    }

    public function scopeForProduct($q, int $productId) { return $q->where('product_id', $productId); }
    public function scopeForOrder($q, int $orderId)     { return $q->where('sales_order_id', $orderId); }

    /* -------------
     |  Mutators / Events
     * ------------- */

    /** Keep total_price always in sync */
    public function refreshTotals(): void
    {
        $qty  = $this->qty_value;
        $unit = (float) ($this->unit_price ?? 0);
        $this->total_price = round($qty * $unit, 2);
    }

    public function setTypeLabelAttribute($value): void
    {
        $v = is_string($value) ? $value : (string) $value;
        $v = trim(preg_replace('/\s+/', ' ', $v));
        $this->attributes['type_label'] = $v;
    }

    protected static function booted(): void
    {
        // Normalize + compute totals
        static::saving(function (self $item) {
            $u = strtolower(trim((string) ($item->unit_type ?: self::UNIT_KG)));
            $item->unit_type = in_array($u, [self::UNIT_KG, self::UNIT_PACK, self::UNIT_BAG], true) ? $u : self::UNIT_KG;

            if (in_array($item->unit_type, [self::UNIT_PACK, self::UNIT_BAG], true)) {
                $item->quantity = (int) round((float) $item->quantity);
            }

            if (empty($item->type_label)) {
                foreach (['variant_name','variant','type','product_type'] as $col) {
                    if (!empty($item->{$col})) {
                        $item->type_label = $item->{$col};
                        break;
                    }
                }
            }

            $item->refreshTotals();
        });

        // Validate stock before create
        static::creating(function (self $item) {
            $item->guardStockAvailable();
        });

        // On create → deduct
        static::created(function (self $item) {
            $item->allocateAndDeduct();
            $item->updateStatusAllocatedIfNeeded();
        });

        // On update → undo old then re-apply if qty/product/batch/unit changed
        static::updating(function (self $item) {
            $dirty = array_intersect(
                array_keys($item->getDirty()),
                ['product_id','production_id','quantity','unit_type','unit_price','status']
            );
            if (!empty($dirty)) {
                $orig = (new self())->forceFill($item->getOriginal());
                $orig->releaseAllocations(); // put stock back to batches/fields
            }
        });

        static::updated(function (self $item) {
            if ($item->wasChanged(['product_id','production_id','quantity','unit_type','unit_price','status'])) {
                $item->guardStockAvailable();
                $item->allocateAndDeduct();
                $item->updateStatusAllocatedIfNeeded();
            }
        });

        // On delete → revert
        static::deleted(function (self $item) {
            $item->releaseAllocations();
        });

        // On restore → re-apply
        static::restored(function (self $item) {
            $item->guardStockAvailable();
            $item->allocateAndDeduct();
            $item->updateStatusAllocatedIfNeeded();
        });
    }

    /* ---------------------------
     |  Allocation / Inventory
     * -------------------------- */

    /** Normalize to 'kg' | 'pack' | 'bag' */
    protected function mode(): string
    {
        $u = strtolower((string) ($this->unit_type ?: self::UNIT_KG));
        return in_array($u, [self::UNIT_KG, self::UNIT_PACK, self::UNIT_BAG], true) ? $u : self::UNIT_KG;
    }

    /** Number requested in the chosen mode */
    protected function requestedAmount(): float
    {
        return (float) $this->qty_value;
    }

    /** Throws if not enough stock for the item’s mode and optional target batch */
    protected function guardStockAvailable(): void
    {
        $pid    = (int) ($this->product_id ?? 0);
        $prodId = $this->production_id ? (int) $this->production_id : null;
        $mode   = $this->mode();
        $req    = $this->requestedAmount();

        if ($pid <= 0 || $req <= 0) return;

        $available = $this->availableForMode($pid, $prodId, $mode);
        if ($available <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'No available stock for the selected product' . ($prodId ? ' / batch.' : '.'),
            ]);
        }
        if ($req > $available) {
            throw ValidationException::withMessages([
                'quantity' => 'Requested amount exceeds available ' . $mode . ' stock. Available: ' . number_format($available, 3),
            ]);
        }
    }

    /** Available stock for a mode (per-batch or per-product) */
    protected function availableForMode(int $productId, ?int $productionId, string $mode): float
    {
        $q = DB::table('productions')->whereNull('deleted_at');
        if ($mode === self::UNIT_PACK) {
            $q = $productionId ? $q->where('id', $productionId) : $q->where('product_id', $productId);
            return (float) $q->sum(DB::raw('COALESCE(available_pack,0)'));
        }
        if ($mode === self::UNIT_BAG) {
            $q = $productionId ? $q->where('id', $productionId) : $q->where('product_id', $productId);
            return (float) $q->sum(DB::raw('COALESCE(available_bag,0)'));
        }

        // kg: produced - sold(kg) - order_items(kg) if you want; we’ll just compare against productions.current_inventory sum
        $produced = $q->when($productionId, fn($qq) => $qq->where('id', $productionId))
                      ->when(!$productionId, fn($qq) => $qq->where('product_id', $productId))
                      ->sum(DB::raw('COALESCE(current_inventory,0)'));
        return (float) $produced;
    }

    /** Deduct from specific batch or FIFO across freshest batches. Records allocations when possible. */
    public function allocateAndDeduct(): void
    {
        $mode = $this->mode();
        $req  = $this->requestedAmount();
        if ($req <= 0 || !$this->product_id) return;

        DB::transaction(function () use ($mode, $req) {
            $remaining = $req;

            $deductFromProd = function (Production $p, float $take) use ($mode) {
                if ($mode === self::UNIT_PACK) {
                    $avail = (float) ($p->available_pack ?? 0);
                    $take  = min($take, $avail);
                    if ($take > 0) {
                        $this->recordAllocation($p->id, $mode, $take);
                        $p->available_pack = max(0, $avail - $take);
                        $p->save();
                        $this->audit("Deducted {$take} pack(s) from batch {$p->batch_number} (Production #{$p->id}).");
                    }
                    return $take;
                }

                if ($mode === self::UNIT_BAG) {
                    $avail = (float) ($p->available_bag ?? 0);
                    $take  = min($take, $avail);
                    if ($take > 0) {
                        $this->recordAllocation($p->id, $mode, $take);
                        $p->available_bag = max(0, $avail - $take);
                        $p->save();
                        $this->audit("Deducted {$take} bag(s) from batch {$p->batch_number} (Production #{$p->id}).");
                    }
                    return $take;
                }

                // kg
                $availKg = (float) ($p->current_inventory ?? 0);
                $takeKg  = min($take, $availKg);
                if ($takeKg > 0) {
                    $this->recordAllocation($p->id, self::UNIT_KG, $takeKg);
                    $p->current_inventory = max(0, $availKg - $takeKg);
                    $p->save();
                    $this->audit("Deducted {$takeKg} kg from batch {$p->batch_number} (Production #{$p->id}).");
                }
                return $takeKg;
            };

            // Specific batch first (if provided)
            if ($this->production_id) {
                $p = Production::lockForUpdate()->find($this->production_id);
                if ($p && !$p->deleted_at) {
                    $taken = $deductFromProd($p, $remaining);
                    $remaining -= $taken;
                }
            }

            // FIFO: oldest production_date first, so older stock (closer to expiry) sells first.
            if ($remaining > 0) {
                $batches = Production::query()
                    ->whereNull('deleted_at')
                    ->where('product_id', $this->product_id)
                    ->orderBy('production_date')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get(['id','batch_number','current_inventory','available_pack','available_bag']);

                foreach ($batches as $p) {
                    if ($remaining <= 0) break;
                    $taken = $deductFromProd($p, $remaining);
                    $remaining -= $taken;
                }
            }

            if ($remaining > 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Insufficient stock while allocating (concurrency). Please retry.',
                ]);
            }
        });
    }

    /** Put stock back exactly where it came from when possible, otherwise FIFO return. */
    public function releaseAllocations(): void
    {
        DB::transaction(function () {
            $hasAllocTable = Schema::hasTable('batch_allocations');
            $hasItemFk     = $hasAllocTable && Schema::hasColumn('batch_allocations', 'order_item_id');

            if ($hasItemFk) {
                // Exact reversion by reading rows
                $rows = $this->allocations()->lockForUpdate()->get();
                foreach ($rows as $alloc) {
                    /** @var BatchAllocation $alloc */
                    $p = Production::lockForUpdate()->find($alloc->production_id);
                    if (!$p || $p->deleted_at) continue;

                    if ($alloc->mode === self::UNIT_PACK) {
                        $p->available_pack = (float) ($p->available_pack ?? 0) + (float) $alloc->quantity_value;
                        $p->save();
                        $this->audit("Returned {$alloc->quantity_value} pack(s) to batch {$p->batch_number} (Production #{$p->id}).");
                    } elseif ($alloc->mode === self::UNIT_BAG) {
                        $p->available_bag = (float) ($p->available_bag ?? 0) + (float) $alloc->quantity_value;
                        $p->save();
                        $this->audit("Returned {$alloc->quantity_value} bag(s) to batch {$p->batch_number} (Production #{$p->id}).");
                    } else {
                        $p->current_inventory = (float) ($p->current_inventory ?? 0) + (float) $alloc->quantity_value;
                        $p->save();
                        $this->audit("Reverted {$alloc->quantity_value} kg back to batch {$p->batch_number} (Production #{$p->id}).");
                    }
                }
                // Clear precise allocations
                $this->allocations()->delete();
                return;
            }

            // If we cannot read precise allocations, do a simple FIFO return for the full requested amount
            $mode = $this->mode();
            $toReturn = $this->requestedAmount();
            if ($toReturn <= 0) return;

            $batches = Production::query()
                ->whereNull('deleted_at')
                ->where('product_id', $this->product_id)
                ->orderBy('production_date')->orderBy('id')
                ->lockForUpdate()
                ->get(['id','batch_number','current_inventory','available_pack','available_bag']);

            foreach ($batches as $p) {
                if ($toReturn <= 0) break;

                if ($mode === self::UNIT_PACK) {
                    $p->available_pack = (float) ($p->available_pack ?? 0) + $toReturn;
                    $p->save();
                    $this->audit("Returned {$toReturn} pack(s) to batch {$p->batch_number} (Production #{$p->id}).");
                    $toReturn = 0;
                } elseif ($mode === self::UNIT_BAG) {
                    $p->available_bag = (float) ($p->available_bag ?? 0) + $toReturn;
                    $p->save();
                    $this->audit("Returned {$toReturn} bag(s) to batch {$p->batch_number} (Production #{$p->id}).");
                    $toReturn = 0;
                } else {
                    $p->current_inventory = (float) ($p->current_inventory ?? 0) + $toReturn;
                    $p->save();
                    $this->audit("Reverted {$toReturn} kg back to batch {$p->batch_number} (Production #{$p->id}).");
                    $toReturn = 0;
                }
            }
        });
    }

    /** Create an allocation row if the table/columns are present. */
    protected function recordAllocation(int $productionId, string $mode, float $qty): void
    {
        if (!Schema::hasTable('batch_allocations')) return;

        // If order_item_id column exists, store it. Otherwise skip (we still deducted stock).
        if (Schema::hasColumn('batch_allocations', 'order_item_id')) {
            $this->allocations()->create([
                'production_id'  => $productionId,
                'mode'           => $mode,
                'quantity_value' => $qty,
            ]);
        }
    }

    /** Write audit message if you have an audit table, else append to notes. */
    protected function audit(string $message): void
    {
        // Preferred: sale_audits with order_item_id column
        if (Schema::hasTable('sale_audits')) {
            $payload = ['message' => $message, 'at' => now()];
            if (Schema::hasColumn('sale_audits', 'order_item_id')) {
                $payload['order_item_id'] = $this->getKey();
            } elseif (Schema::hasColumn('sale_audits', 'sale_id') && $this->sales_order_id ?? null) {
                $payload['sale_id'] = $this->sales_order_id;
            }
            try { DB::table('sale_audits')->insert(array_merge($payload, [
                'created_at' => now(), 'updated_at' => now(),
            ])); } catch (\Throwable $e) { /* fallback below */ }
            return;
        }

        // Fallback: append to notes (non-blocking)
        try {
            $this->notes = trim(rtrim((string) ($this->notes ?? '')) . "\n" . $message);
            // avoid recursion: direct table update
            DB::table($this->getTable())->where('id', $this->getKey())->update(['notes' => $this->notes]);
        } catch (\Throwable $e) { /* silent */ }
    }

    /** After allocation, optionally flip status to Allocated if fully allocated */
    protected function updateStatusAllocatedIfNeeded(): void
    {
        if ($this->status !== self::STATUS_ALLOCATED && $this->is_fully_allocated) {
            // direct update to avoid triggering save events again
            DB::table($this->getTable())->where('id', $this->getKey())->update(['status' => self::STATUS_ALLOCATED]);
            $this->setRawAttributes(array_merge($this->attributes, ['status' => self::STATUS_ALLOCATED]), true);
        }
    }
}
