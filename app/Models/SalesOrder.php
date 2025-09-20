<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SalesOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sales_orders';

    /** Simple statuses (keep in sync with your DB/UX) */
    public const STATUS_PENDING   = 'Pending';
    public const STATUS_COMPLETED = 'Completed';
    public const STATUS_CANCELLED = 'Cancelled';
    public const STATUS_PAID      = 'Paid';

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

    /** Expose totals without extra code in controllers */
    protected $appends = [
        'total_amount',
        'items_count',
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

    /* --------------------------- Attribute Accessors ------------------------- */

    /**
     * Total amount = Σ (item.total_price) or (qty * unit_price) if total_price missing.
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

        return (float) DB::table((new SalesOrderItem)->getTable())
            ->where('sales_order_id', $this->getKey())
            ->selectRaw('SUM(COALESCE(total_price, (COALESCE(quantity_kg, quantity, 0) * COALESCE(unit_price, 0)))) as total')
            ->value('total') ?? 0.0;
    }

    public function getItemsCountAttribute(): int
    {
        if ($this->relationLoaded('items')) {
            return $this->items->count();
        }
        return (int) DB::table((new SalesOrderItem)->getTable())
            ->where('sales_order_id', $this->getKey())
            ->count();
    }

    /* ------------------------------- Events ---------------------------------- */

    protected static function booted()
    {
        // Auto-generate order_number if missing (atomic per day)
        static::creating(function (SalesOrder $order) {
            if (!filled($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }
            // Default status
            if (!filled($order->status)) {
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
     */
    public static function generateOrderNumber(): string
    {
        $ymd = now()->format('Ymd');
        $prefix = 'SO-' . $ymd . '-';

        // Use a lightweight per-day counter in a transaction to avoid collisions.
        return DB::transaction(function () use ($ymd, $prefix) {
            $row = DB::table('order_sequences')->where('date_key', $ymd)->lockForUpdate()->first();

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
                DB::table('order_sequences')->where('date_key', $ymd)->update([
                    'last_seq'   => $seq,
                    'updated_at' => now(),
                ]);
            }

            return $prefix . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
        }, 3);
    }
}
