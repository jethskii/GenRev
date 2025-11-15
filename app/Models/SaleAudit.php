<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleAudit extends Model
{
    /** Table name */
    protected $table = 'sale_audits';

    /** Mass-assignable columns */
    protected $fillable = [
        'sale_id',
        'order_item_id',   // optional, for future line-items
        'message',
        'at',
    ];

    /** Casts */
    protected $casts = [
        'at' => 'datetime',
    ];

    /* ---------------- Relationships ---------------- */

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    // Only if you later add a SaleItem model; harmless if unused
    public function orderItem()
    {
        return $this->belongsTo(SaleItem::class, 'order_item_id');
    }
}
