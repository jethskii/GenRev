<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;

class SalesOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sales_orders';

    /** Simple statuses (keep in sync with your DB/UX) */
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
    ];

    /**
     * Dashboard-friendly computed fields
     * - total_amount:  Σ item totals (stored or computed)
     * - items_count  : number of line items
     * - is_paid      : boolean flag from status
     * - total_qty    : Σ item quantities (kg-aware)
     * - total_revenue: alias of total_amount (handy for charts)
     * - types_summary: array breakdown by type_label with qty & revenue
     * - types_badge  : compact string summary for UI badges
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

    /* ----------------------------- Relationships ----------------------------- */

    public function items()
    {
        // Assumes sales_order_id foreign key on sales_order_items table
        return $this->hasMany(SalesOrderItem::class, 'sales_order_id');
    }

    /* ------------------------------- Scopes ---------------------------------- */

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

    /** Orders that have at least one item with problems (example scope on items model). */
    public function scopeProblematic($query)
    {
        return $query->whereHas('items', function ($q) {
            $q->problematic();
        });
    }

    /**
     * Eager-load with a lightweight type breakdown (for lists).
     * Example:
     * SalesOrder::withTypeBreakdown()->latest()->take(10)->get();
     */
    public function scopeWithTypeBreakdown($query)
    {
        $itemsTable = (new SalesOrderItem())->getTable();
        return $query->withCount('items')->with(['items' => function ($q) use ($itemsTable) {
            // Only bring columns we use frequently
            $q->select("$itemsTable.*")
              ->orderBy('id', 'asc');
        }]);
    }

    /* --------------------------- Attribute Accessors ------------------------- */

    /**
     * Σ (item.total_price) or (qty * unit_price) if total_price missing.
     * Uses loaded relation when available; falls back to an aggregate query.
     */
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

    /** Mirror total_amount for readability in charts */
    public function getTotalRevenueAttribute(): float
    {
        return $this->total_amount;
    }

    /** Count items (uses relation if loaded) */
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

    /** Σ quantities across items (kg-aware) */
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

    /**
     * Type breakdown per order:
     * Returns an array keyed by type_label with:
     *   [
     *     'qty'     => float,
     *     'revenue' => float
     *   ]
     *
     * Uses loaded relation when available; otherwise performs a grouped query.
     *
     * Expected item columns:
     * - type_label
     * - quantity_kg (or quantity)
     * - unit_price / total_price
     */
    public function getTypesSummaryAttribute(): array
    {
        $itemsTable = (new SalesOrderItem())->getTable();

        // Helper to merge a row into an accumulator
        $merge = function (array &$acc, ?string $label, float $qty, float $revenue): void {
            $key = $label !== null && $label !== '' ? $label : 'Unspecified';
            if (!isset($acc[$key])) {
                $acc[$key] = ['qty' => 0.0, 'revenue' => 0.0];
            }
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

        // Not loaded: run a single grouped aggregate query
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

    /**
     * Compact, human-friendly badge string for UI:
     * e.g. "Garlic Skinless 8.0kg • Regular Skinless 5.0kg"
     */
    public function getTypesBadgeAttribute(): string
    {
        $summary = $this->types_summary;
        if (empty($summary)) return '';

        // Order by revenue desc, then qty desc
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

    /* ------------------------------- Events ---------------------------------- */

    protected static function booted()
    {
        // Auto-generate order_number, default status/date
        static::creating(function (SalesOrder $order) {
            if (!filled($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }

            // Default status
            if (!filled($order->status) || !in_array($order->status, self::STATUSES, true)) {
                $order->status = self::STATUS_COMPLETED;
            }

            // Default date
            if (!filled($order->order_date)) {
                $order->order_date = now();
            }
        });

        // Soft-delete cascade for items
        static::deleting(function (SalesOrder $order) {
            if ($order->isForceDeleting()) {
                $order->items()->withTrashed()->forceDelete();
            } else {
                $order->items()->delete();
            }
        });

        // Restore cascade for items
        static::restoring(function (SalesOrder $order) {
            $order->items()->withTrashed()->restore();
        });
    }

    /**
     * Generates a human-friendly unique order number:
     * SO-YYYYMMDD-### (sequence per day, with DB-safe increment).
     * Falls back to MAX()-based scan if the sequence table isn't available.
     */
    public static function generateOrderNumber(): string
    {
        $ymd = now()->format('Ymd');
        $prefix = 'SO-' . $ymd . '-';

        // If sequence table is present, use it atomically
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
                // fall through to MAX()-based fallback
            }
        }

        // Fallback: scan existing orders for today and increment the tail
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
