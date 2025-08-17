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
        'description',     // display name
        'quantity',
        'unit_price',
        'total_price',
        'delivery_date',   // used for expiry buffer checks
        'status',
    ];

    protected $casts = [
        'quantity'      => 'integer',
        'unit_price'    => 'decimal:2',
        'total_price'   => 'decimal:2',
        'delivery_date' => 'datetime',
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
            ->withPivot(['allocated_qty', 'locked_by_admin', 'override_reason', 'approved_by', 'approved_at'])
            ->withTimestamps();
    }

    /* -------------
     |  Accessors
     * ------------- */

    public function getAllocatedQtyAttribute(): int
    {
        $allocs = $this->relationLoaded('allocations') ? $this->allocations : $this->allocations()->get();
        return (int) ($allocs->sum('allocated_qty') ?? 0);
    }

    public function getRemainingQtyAttribute(): int
    {
        return max(0, (int) $this->quantity - $this->allocated_qty);
    }

    public function getIsFullyAllocatedAttribute(): bool
    {
        return $this->remaining_qty === 0;
    }

    /* ----------
     |  Scopes
     * ---------- */

    public function scopeNeedingAllocation($q)
    {
        return $q->where(function ($qq) {
            $qq->whereRaw('(COALESCE((SELECT SUM(allocated_qty) FROM batch_allocations WHERE order_item_id = sales_order_items.id AND deleted_at IS NULL), 0)) < quantity');
        });
    }

    public function scopeForProduct($q, int $productId)
    {
        return $q->where('product_id', $productId);
    }

    /* -------------
     |  Mutators / Model events
     * ------------- */

    // Keep total_price always in sync
    public function refreshTotals(): void
    {
        $this->total_price = (float) $this->quantity * (float) $this->unit_price;
    }

    protected static function booted(): void
    {
        static::saving(function (self $item) {
            // Auto-calc total before save
            $item->refreshTotals();
        });
    }
}
