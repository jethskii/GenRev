<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryLedger extends Model
{
    protected $table = 'inventory_ledger';

    protected $fillable = [
        'product_id',
        'production_id',
        'qty_delta',
        'reason',
        'rel_type',
        'rel_id',
        'notes',
    ];

    public function product()  { return $this->belongsTo(Product::class); }
    public function production(){ return $this->belongsTo(Production::class); }
}
