<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;

/**
 * SalesOrder
 *
 * Holds the header info for a sale (customer, date, status).
 * Line items live in SalesOrderItem and handle inventory side-effects.
 */
class SalesOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sales_orders';

    /** Simple statuses (keep in sync with DB + UI) */
    public const STATUS_PENDING   = 'Pending';
    public const STATUS_COMPLETED = 'Completed';
    public const STATUS_CANCELLED = 'Cancelled';
    public const STATUS_PAID      = 'Paid';

    /** Useful list for validation/UI */
    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
        self::STATUS_PAID,
    ];

    protected $fillable = [
        'order_number',
        'customer_name',
        'order_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'order_date' => 'datetime:Y-m-d',
        'status'     => 'string',
        'deleted_at' => 'datetime',
    ];

    /**
     * Dashboard-friendly computed fields
     * (These are virtual; they’re appended to array/json automatically.)
     */
    protected $appends = [
        'total_amount',
        'items_count',
        'is_paid',
        'total_qty',
        'total_revenue',
        'types_summary',
        'types_badge',
    ];

    /* -------------------------------------------------------------------------
     | Relationships
     * ------------------------------------------------------------------------ */

    public function items()
    {
        // FK must be sales_order_id on sales_order_items
        return $this->hasMany(SalesOrderItem::class, 'sales_order_id');
    }

    /* -------------------------------------------------------------------------
     | Scopes
     * ------------------------------------------------------------------------ */

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeDateBetween($query, ?string $from, ?string $to)
    {
        if ($from) $query->whereDate('order_date', '>=', $from);
        if ($to)   $query->whereDate('order_date', '<=', $to);
        return $query;
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        $term = trim($term);
        return $query->where(function ($q) use ($term) {
            $q->where('order_number', 'like', "%{$term}%")
              ->orWhere('customer_name', 'like', "%{$term}%")
              ->orWhere('notes', 'like', "%{$term}%");
        });
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_COMPLETED]);
    }

    /** Orders that have at least one item with problems (requires item scope). */
    public function scopeProblematic($query)
    {
        return $query->whereHas('items', function ($q) {
            $q->problematic();
        });
    }

    /**
     * Eager-load with a lightweight type breakdown (great for lists).
     */
    public function scopeWithTypeBreakdown($query)
    {
        $itemsTable = (new SalesOrderItem())->getTable();
        return $query->withCount('items')->with(['items' => function ($q) use ($itemsTable) {
            $q->select("$itemsTable.*")->orderBy('id', 'asc');
        }]);
    }

    /* -------------------------------------------------------------------------
     | Accessors / Virtuals
     * ------------------------------------------------------------------------ */

    public function getTotalAmountAttribute(): float
    {
        if ($this->relationLoaded('items')) {
            return (float) $this->items->sum(function ($it) {
                $qty  = (float) ($it->quantity_kg ?? $it->quantity ?? 0);
                $unit = (float) ($it->unit_price ?? 0);
                $tot  = (float) ($it->total_price ?? ($qty * $unit));
                return $tot;
            });
        }

        $itemsTable = (new SalesOrderItem())->getTable();
        return (float) (DB::table($itemsTable)
            ->where('sales_order_id', $this->getKey())
            ->selectRaw('SUM(COALESCE(total_price, (COALESCE(quantity_kg, quantity, 0) * COALESCE(unit_price, 0)))) as total')
            ->value('total') ?? 0.0);
    }

    public function getTotalRevenueAttribute(): float
    {
        return $this->total_amount;
    }

    public function getItemsCountAttribute(): int
    {
        if ($this->relationLoaded('items')) {
            return $this->items->count();
        }
        $itemsTable = (new SalesOrderItem())->getTable();
        return (int) DB::table($itemsTable)
            ->where('sales_order_id', $this->getKey())
            ->count();
    }

    public function getTotalQtyAttribute(): float
    {
        if ($this->relationLoaded('items')) {
            return (float) $this->items->sum(function ($it) {
                return (float) ($it->quantity_kg ?? $it->quantity ?? 0);
            });
        }

        $itemsTable = (new SalesOrderItem())->getTable();
        return (float) (DB::table($itemsTable)
            ->where('sales_order_id', $this->getKey())
            ->selectRaw('SUM(COALESCE(quantity_kg, quantity, 0)) as q')
            ->value('q') ?? 0.0);
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /** Array keyed by type_label: ['qty' => float, 'revenue' => float] */
    public function getTypesSummaryAttribute(): array
    {
        $itemsTable = (new SalesOrderItem())->getTable();

        $merge = function (array &$acc, ?string $label, float $qty, float $revenue): void {
            $key = $label !== null && $label !== '' ? $label : 'Unspecified';
            if (!isset($acc[$key])) $acc[$key] = ['qty' => 0.0, 'revenue' => 0.0];
            $acc[$key]['qty']     += $qty;
            $acc[$key]['revenue'] += $revenue;
        };

        $out = [];

        if ($this->relationLoaded('items')) {
            foreach ($this->items as $it) {
                $label   = trim((string) ($it->type_label ?? ''));
                $qty     = (float) ($it->quantity_kg ?? $it->quantity ?? 0);
                $unit    = (float) ($it->unit_price ?? 0);
                $total   = (float) ($it->total_price ?? ($qty * $unit));
                $merge($out, $label, $qty, $total);
            }
            return $out;
        }

        $rows = DB::table($itemsTable)
            ->where('sales_order_id', $this->getKey())
            ->selectRaw("
                NULLIF(TRIM(COALESCE(type_label, '')), '') as type_label,
                SUM(COALESCE(quantity_kg, quantity, 0))     as qty,
                SUM(COALESCE(total_price, (COALESCE(quantity_kg, quantity, 0) * COALESCE(unit_price, 0)))) as revenue
            ")
            ->groupBy('type_label')
            ->get();

        foreach ($rows as $r) {
            $merge($out, $r->type_label, (float) $r->qty, (float) $r->revenue);
        }

        return $out;
    }

    /** Compact, human-friendly badge string for UI lists */
    public function getTypesBadgeAttribute(): string
    {
        $summary = $this->types_summary;
        if (empty($summary)) return '';

        // Sort by revenue desc then qty desc
        uasort($summary, function ($a, $b) {
            if ($a['revenue'] === $b['revenue']) {
                return $b['qty'] <=> $a['qty'];
            }
            return $b['revenue'] <=> $a['revenue'];
        });

        $parts = [];
        foreach ($summary as $label => $v) {
            $qty = number_format((float) $v['qty'], 2);
            $parts[] = "{$label} {$qty}";
        }
        return implode(' • ', $parts);
    }

    /* -------------------------------------------------------------------------
     | Model Events
     * ------------------------------------------------------------------------ */

    protected static function booted()
    {
        // Assign order number + defaults
        static::creating(function (SalesOrder $order) {
            if (!filled($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }

            if (!filled($order->status) || !in_array($order->status, self::STATUSES, true)) {
                $order->status = self::STATUS_COMPLETED;
            }

            if (!filled($order->order_date)) {
                $order->order_date = now();
            }
        });

        // Cascade soft-delete to items
        static::deleting(function (SalesOrder $order) {
            if ($order->isForceDeleting()) {
                $order->items()->withTrashed()->forceDelete();
            } else {
                $order->items()->delete();
            }
        });

        // Cascade restore to items
        static::restoring(function (SalesOrder $order) {
            $order->items()->withTrashed()->restore();
        });

        // Optional: keep a persisted rollup up-to-date if columns exist
        static::saved(function (SalesOrder $order) {
            $order->maybePersistRollups();
        });
    }

    /* -------------------------------------------------------------------------
     | Commands / Helpers
     * ------------------------------------------------------------------------ */

    /** Quick helper to add one item (attributes are passed to SalesOrderItem::create). */
    public function addItem(array $attrs): SalesOrderItem
    {
        /** @var \App\Models\SalesOrderItem $item */
        $item = $this->items()->create($attrs);
        $this->refresh(); // so accessors reflect the new item
        $this->maybePersistRollups();
        return $item;
    }

    /**
     * Replace all items with the provided list (each entry is SalesOrderItem::fillable array).
     * This will soft-delete any previous items, add new ones, and refresh totals.
     */
    public function replaceItems(array $items): void
    {
        DB::transaction(function () use ($items) {
            $this->items()->delete();
            foreach ($items as $attrs) {
                $this->items()->create($attrs);
            }
            $this->refresh();
            $this->maybePersistRollups();
        });
    }

    /** Transition helpers */
    public function markPaid(): bool
    {
        $this->status = self::STATUS_PAID;
        return $this->save();
    }

    public function markCompleted(): bool
    {
        $this->status = self::STATUS_COMPLETED;
        return $this->save();
    }

    public function markCancelled(): bool
    {
        $this->status = self::STATUS_CANCELLED;
        return $this->save();
    }

    /**
     * If you later add persisted rollup columns on sales_orders (e.g. total_amount, items_count),
     * this will auto-detect and write them. Safe to call even if they don't exist.
     */
    public function maybePersistRollups(): void
    {
        $updates = [];

        if (Schema::hasColumn($this->getTable(), 'items_count')) {
            $updates['items_count'] = $this->items_count;
        }
        if (Schema::hasColumn($this->getTable(), 'total_amount')) {
            $updates['total_amount'] = $this->total_amount;
        }
        if (Schema::hasColumn($this->getTable(), 'total_qty')) {
            $updates['total_qty'] = $this->total_qty;
        }
        if (Schema::hasColumn($this->getTable(), 'total_revenue')) {
            $updates['total_revenue'] = $this->total_revenue;
        }

        if (!empty($updates)) {
            // Avoid recursion by direct query
            DB::table($this->getTable())->where('id', $this->getKey())->update($updates);
        }
    }

    /* -------------------------------------------------------------------------
     | Numbering
     * ------------------------------------------------------------------------ */

    /**
     * Generates a human-friendly unique order number:
     * SO-YYYYMMDD-### (sequence per day)
     * Uses order_sequences table if present; falls back to MAX()-scan.
     */
    public static function generateOrderNumber(): string
    {
        $ymd = now()->format('Ymd');
        $prefix = 'SO-' . $ymd . '-';

        if (Schema::hasTable('order_sequences')) {
            try {
                return DB::transaction(function () use ($ymd, $prefix) {
                    $row = DB::table('order_sequences')
                        ->where('date_key', $ymd)
                        ->lockForUpdate()
                        ->first();

                    if (!$row) {
                        DB::table('order_sequences')->insert([
                            'date_key'   => $ymd,
                            'last_seq'   => 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $seq = 1;
                    } else {
                        $seq = (int) $row->last_seq + 1;
                        DB::table('order_sequences')
                            ->where('date_key', $ymd)
                            ->update([
                                'last_seq'   => $seq,
                                'updated_at' => now(),
                            ]);
                    }

                    return $prefix . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
                }, 3);
            } catch (\Throwable $e) {
                // fall through
            }
        }

        // Fallback: scan
        $maxToday = static::query()
            ->whereDate('order_date', Carbon::now()->toDateString())
            ->where('order_number', 'like', $prefix . '%')
            ->max('order_number');

        $next = 1;
        if ($maxToday) {
            $tail = substr((string) $maxToday, strlen($prefix));
            $next = (ctype_digit($tail) ? (int) $tail : 0) + 1;
        }

        return $prefix . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }
}
