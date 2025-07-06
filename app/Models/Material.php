<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $table = 'materials';

    protected $fillable = [
        'material_name',
        'quantity_kg',
        'stock_status',
    ];

    public $timestamps = false;
}
