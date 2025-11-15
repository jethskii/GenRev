<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BatchAllocation extends Model
{
    use SoftDeletes;

    protected $table = 'batch_allocations';

    /**
     * Mass assignable fields – must match your table.
     */
    protected $fillable = [
        'sale_id',
        'order_item_id',   // optional, for future line-item use
        'production_id',
        'mode',            // 'kg' | 'pack' | 'bag'
        'quantity_value',  // numeric qty reserved from that batch
    ];

    protected $casts = [
        'sale_id'        => 'integer',
        'order_item_id'  => 'integer',
        'production_id'  => 'integer',
        'quantity_value' => 'float',   // or 'decimal:3' if you prefer
    ];

    /* ---------------- Relationships ---------------- */

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function production()
    {
        return $this->belongsTo(Production::class, 'production_id');
    }
}
