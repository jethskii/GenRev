<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $table = 'sales';

    protected $fillable = [
    'invoice_number',
    'product_name',
    'date',
    'quantity',
    'price',
    'status',
];

    public $timestamps = true;
}
