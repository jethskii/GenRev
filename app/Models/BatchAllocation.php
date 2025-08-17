<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Reserves a quantity from a Batch for a specific Sale (per-line later if you add SaleItems).
 *
 * @property int            $id
 * @property int            $batch_id
 * @property int            $sale_id
 * @property int            $allocated_qty
 * @property bool           $locked_by_admin
 * @property string|null    $override_reason
 * @property int|null       $approved_by
 * @property \Carbon\Carbon $approved_at
 * @property-read \App\Models\Batch $batch
 * @property-read \App\Models\Sale  $sale
 */
class BatchAllocation extends Model
{
    use SoftDeletes;

    protected $table = 'batch_allocations';

    protected $fillable = [
        'batch_id',
        'sale_id',          // if you later move to SaleItems, change to order_item_id
        'allocated_qty',
        'locked_by_admin',
        'override_reason',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'allocated_qty'   => 'integer',
        'locked_by_admin' => 'boolean',
        'approved_at'     => 'datetime',
    ];

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
