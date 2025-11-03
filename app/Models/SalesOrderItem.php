<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrderItem extends Model
{
    use SoftDeletes;

    protected $table = 'sales_order_items';

    /** Optional per-item status (align with your workflow as needed) */
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
        'production_id',      // optional traceability
        'description',        // display name / override

        // quantity + unitization
        'quantity',
        'unit_type',          // 'kg' | 'pack' | 'bag' (nullable → treat as 'kg')

        // pricing
        'unit_price',
        'total_price',

        // type/variant (DASHBOARD will read this)
        'type_label',         // preferred canonical column

        // logistics/meta
        'delivery_date',      // expected delivery
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

        // strings for type fallbacks (safe if not present)
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
        'type_label_clean',   // normalized human label
        'qty_value',          // numeric qty used for math (kg-aware)
        'revenue_value',      // numeric total used for math
        'display_name',       // already present below, keep in appends for convenience
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

    public function allocations()
    {
        return $this->hasMany(BatchAllocation::class, 'order_item_id');
    }

    public function batches()
    {
        return $this->belongsToMany(Batch::class, 'batch_allocations', 'order_item_id', 'batch_id')
            ->withPivot([
                'allocated_qty',
                'locked_by_admin',
                'override_reason',
                'approved_by',
                'approved_at',
            ])
            ->withTimestamps();
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
        $allocs = $this->relationLoaded('allocations')
            ? $this->allocations
            : $this->allocations()->get();

        $sum = (float) ($allocs->sum('allocated_qty') ?? 0);

        // For pack/bag, treat allocations as integers
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
        return $this->remaining_qty <= 0;
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->description ?: optional($this->product)->product_name ?: 'Unnamed Item';
    }

    /**
     * Normalized type label for dashboards (uses first non-empty among:
     * type_label, variant_name, variant, type, product_type). Trim + collapse spaces + Title Case.
     */
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

        // Title-case without shouting acronyms
        $label = mb_convert_case($label, MB_CASE_TITLE, 'UTF-8');
        return $label;
    }

    /** Flag if this line has issues: under-allocated OR any linked batch expired/near-expiry */
    public function getHasProblemsAttribute(): bool
    {
        if ($this->remaining_qty > 0.0) {
            return true;
        }

        // If batches are loaded, do a quick scan; otherwise be conservative
        if ($this->relationLoaded('allocations')) {
            foreach ($this->allocations as $alloc) {
                $b = $alloc->batch ?? null;
                if (!$b) continue;

                // If your Batch model exposes days_to_expiry, use it:
                $days = $b->days_to_expiry ?? null;
                if ($days !== null && $days < 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /* ----------
     |  Scopes
     * ---------- */

    public function scopeNeedingAllocation($q)
    {
        // Under-allocated lines
        return $q->whereRaw('COALESCE(
            (SELECT SUM(allocated_qty) FROM batch_allocations 
             WHERE order_item_id = sales_order_items.id AND deleted_at IS NULL), 0
        ) < quantity');
    }

    public function scopeForProduct($q, int $productId)
    {
        return $q->where('product_id', $productId);
    }

    public function scopeForOrder($q, int $orderId)
    {
        return $q->where('sales_order_id', $orderId);
    }

    /** Items that are "problematic": not fully allocated, or with expired allocations (if joinable). */
    public function scopeProblematic($q)
    {
        // Basic: not fully allocated
        $q->where(function ($qq) {
            $qq->whereRaw('COALESCE(
                (SELECT SUM(allocated_qty) FROM batch_allocations 
                 WHERE order_item_id = sales_order_items.id AND deleted_at IS NULL), 0
            ) < quantity');
        });

        // Optional: expiry via join if schema has batches.expiry_date
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('batches') &&
                \Illuminate\Support\Facades\Schema::hasColumn('batches', 'expiry_date')) {
                $q->orWhereHas('allocations.batch', function ($bq) {
                    $bq->whereDate('expiry_date', '<', now()->toDateString());
                });
            }
        } catch (\Throwable $e) {
            // ignore join errors silently
        }

        return $q;
    }

    /** Filter by unitization (kg/pack/bag) */
    public function scopeUnitType($q, ?string $unit)
    {
        if (!$unit) return $q;
        return $q->where('unit_type', $unit);
    }

    /**
     * Minimal projection for dashboard aggregations:
     * ->selectDashboardFields()->get()
     */
    public function scopeSelectDashboardFields($q)
    {
        return $q->select([
            "{$this->table}.id",
            "{$this->table}.sales_order_id",
            "{$this->table}.product_id",
            "{$this->table}.quantity",
            "{$this->table}.unit_type",
            "{$this->table}.unit_price",
            "{$this->table}.total_price",
            // bring all potential type columns so accessors work without extra queries
            "{$this->table}.type_label",
            "{$this->table}.variant_name",
            "{$this->table}.variant",
            "{$this->table}.type",
            "{$this->table}.product_type",
        ]);
    }

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

    /** Normalize incoming type labels (trim/collapse spaces; keep original casing, title-case later in accessor) */
    public function setTypeLabelAttribute($value): void
    {
        $v = is_string($value) ? $value : (string) $value;
        $v = trim(preg_replace('/\s+/', ' ', $v));
        $this->attributes['type_label'] = $v;
    }

    protected static function booted(): void
    {
        static::saving(function (self $item) {
            // Normalize unit_type
            $u = strtolower(trim((string) ($item->unit_type ?: 'kg')));
            $item->unit_type = in_array($u, [self::UNIT_KG, self::UNIT_PACK, self::UNIT_BAG], true) ? $u : self::UNIT_KG;

            // For pack/bag, clamp quantity to integer
            if (in_array($item->unit_type, [self::UNIT_PACK, self::UNIT_BAG], true)) {
                $item->quantity = (int) round((float) $item->quantity);
            }

            // If type_label empty but fallbacks exist, hydrate it so future reads are consistent
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
    }
}
