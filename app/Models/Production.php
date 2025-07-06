<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Production extends Model
{
    protected $fillable = [
        'product_name',
        'forecasted_demand',
        'current_inventory',
        'unit_cost',
    ];
}
