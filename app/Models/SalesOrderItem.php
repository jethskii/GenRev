<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrderItem extends Model
{
    use SoftDeletes;

    protected $table = 'sales_order_items';

    protected $fillable = [
        'sales_order_id',
        'product_id',
        'production_id',   // optional traceability
        'description',     // display name / override
        'quantity',
        'unit_price',
        'total_price',
        'delivery_date',   // expected delivery / expiry buffer checks
        'status',
    ];

    protected $casts = [
        'quantity'      => 'decimal:3',
        'unit_price'    => 'decimal:2',
        'total_price'   => 'decimal:2',
        'delivery_date' => 'datetime:Y-m-d',
        'status'        => 'string',
    ];

    /** Automatically expose useful derived fields */
    protected $appends = [
        'allocated_qty',
        'remaining_qty',
        'is_fully_allocated',
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

    public function getAllocatedQtyAttribute(): float
    {
        $allocs = $this->relationLoaded('allocations')
            ? $this->allocations
            : $this->allocations()->get();

        return (float) ($allocs->sum('allocated_qty') ?? 0);
    }

    public function getRemainingQtyAttribute(): float
    {
        return max(0, (float) $this->quantity - $this->allocated_qty);
    }

    public function getIsFullyAllocatedAttribute(): bool
    {
        return $this->remaining_qty <= 0;
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->description ?: optional($this->product)->product_name ?: 'Unnamed Item';
    }

    /* ----------
     |  Scopes
     * ---------- */

    public function scopeNeedingAllocation($q)
    {
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

    /* -------------
     |  Mutators / Events
     * ------------- */

    /** Keep total_price always in sync */
    public function refreshTotals(): void
    {
        $qty  = (float) ($this->quantity ?? 0);
        $unit = (float) ($this->unit_price ?? 0);
        $this->total_price = round($qty * $unit, 2);
    }

    protected static function booted(): void
    {
        static::saving(function (self $item) {
            $item->refreshTotals();
        });
    }
}
