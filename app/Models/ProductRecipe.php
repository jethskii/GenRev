<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductRecipe extends Model
{
    protected $table = 'product_recipes';

    protected $fillable = [
        'product_id',
        'material_id',
        'qty',
        'unit',
        'unit_price_snapshot',
    ];

    protected $casts = [
        'product_id'          => 'integer',
        'material_id'         => 'integer',
        'qty'                 => 'float',
        'unit_price_snapshot' => 'float',
        'created_at'          => 'datetime',
        'updated_at'          => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }
}
