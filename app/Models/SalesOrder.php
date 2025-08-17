<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrder extends Model
{
    use SoftDeletes;

    protected $table = 'sales_orders';

    protected $fillable = [
        'order_number',
        'customer_name',
        'order_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'order_date' => 'datetime',
    ];

    public function items()   { return $this->hasMany(SalesOrderItem::class); }
}
