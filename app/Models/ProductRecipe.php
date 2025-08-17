<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductRecipe extends Model
{
    protected $fillable = [
        'product_id',
        'ingredient_id',          // FK to materials.id
        'qty',
        'unit_price_snapshot',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // "ingredient" = a row in materials table
    public function ingredient()
    {
        return $this->belongsTo(Material::class, 'ingredient_id');
    }

    // Convenience accessor
    public function getLineTotalAttribute(): float
    {
        return round((float)$this->qty * (float)$this->unit_price_snapshot, 2);
    }
}
