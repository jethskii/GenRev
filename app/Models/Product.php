<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';

    protected $fillable = [
        'id',
        'product_name',
        'batch_number',
        'production_date',
        'quantity',
        'stock_status',
    ];


}
