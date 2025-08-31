<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * (Optional) Legacy placeholder.
 * Not used by the new Inventory dashboard which reads canonical tables.
 */
class Inventory extends Model
{
    protected $table = 'inventory';

    protected $fillable = [
        'product_name',
        'batch_number',
        'production_date',
        'quantity',
        'stock_status',
    ];

    public $timestamps = true;
}
