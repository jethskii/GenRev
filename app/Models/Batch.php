<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A physical batch of a given product, produced at a specific time, with its own expiry.
 *
 * @property int              $id
 * @property string           $batch_code
 * @property int              $product_id
 * @property int|null         $production_id
 * @property \Carbon\Carbon   $produced_at
 * @property \Carbon\Carbon   $expiry_date
 * @property int              $shelf_life_days
 * @property int              $qty_total
 * @property int              $qty_available
 * @property int              $qty_reserved
 * @property string           $status
 * @property int              $dispatch_sequence
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\Production|null $production
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\BatchAllocation[] $allocations
 */
class Batch extends Model
{
    use SoftDeletes;

    public const STATUS_CREATED              = 'CREATED';
    public const STATUS_QA_HOLD              = 'QA_HOLD';
    public const STATUS_RELEASED             = 'RELEASED';
    public const STATUS_RESERVED             = 'RESERVED';
    public const STATUS_PARTIALLY_DISPATCHED = 'PARTIALLY_DISPATCHED';
    public const STATUS_DISPATCHED           = 'DISPATCHED';
    public const STATUS_EXPIRED              = 'EXPIRED';
    public const STATUS_SCRAPPED             = 'SCRAPPED';

    protected $fillable = [
        'batch_code',
        'product_id',
        'production_id',
        'produced_at',
        'expiry_date',
        'shelf_life_days',
        'qty_total',
        'qty_available',
        'qty_reserved',
        'status',
        'dispatch_sequence',
    ];

    protected $casts = [
        'produced_at'       => 'datetime',
        'expiry_date'       => 'datetime',
        'shelf_life_days'   => 'integer',
        'qty_total'         => 'integer',
        'qty_available'     => 'integer',
        'qty_reserved'      => 'integer',
        'dispatch_sequence' => 'integer',
    ];

    /* Relationships */

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
        return $this->hasMany(BatchAllocation::class);
    }

    /* Scopes */

    public function scopeReleased($q)
    {
        return $q->where('status', self::STATUS_RELEASED);
    }

    public function scopeNotExpired($q)
    {
        return $q->whereDate('expiry_date', '>=', now());
    }

    /* Derived attributes */

    public function getDaysToExpiryAttribute(): ?int
    {
        return $this->expiry_date ? now()->diffInDays($this->expiry_date, false) : null;
    }
}
