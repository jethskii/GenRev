<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductRecipe extends Model
{
    protected $table = 'product_recipes';

    protected $fillable = [
        'product_id',
        // We keep ingredient_id in fillable for legacy forms,
        // but controllers can use material_id thanks to the mutator below.
        'ingredient_id',
        'qty',
        'unit_price_snapshot',
    ];

    protected $casts = [
        'qty'                 => 'decimal:3',
        'unit_price_snapshot' => 'decimal:2',
    ];

    protected $appends = [
        'line_total',
    ];

    /* ----------------------------------------------------------------------
     | Attribute mapping: material_id <-> ingredient_id (backward compatible)
     * ---------------------------------------------------------------------*/

    /**
     * Allow reading $model->material_id even if DB column is ingredient_id.
     */
    public function getMaterialIdAttribute(): ?int
    {
        // Prefer real attribute if column exists; fallback to ingredient_id
        return array_key_exists('material_id', $this->attributes)
            ? (int) $this->attributes['material_id']
            : (isset($this->attributes['ingredient_id']) ? (int) $this->attributes['ingredient_id'] : null);
    }

    /**
     * Allow setting $model->material_id and persist to ingredient_id for legacy schema.
     */
    public function setMaterialIdAttribute($value): void
    {
        // If your table already has a material_id column, write it.
        if (array_key_exists('material_id', $this->attributes)) {
            $this->attributes['material_id'] = $value;
        }

        // Always mirror to ingredient_id so legacy column continues to work.
        $this->attributes['ingredient_id'] = $value;
    }

    /* ----------------------------------------------------------------------
     | Relationships
     * ---------------------------------------------------------------------*/

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /** Preferred name: the BOM points to a material row */
    public function material()
    {
        return $this->belongsTo(Material::class, 'ingredient_id');
    }

    /** Backward-compat alias used by some older blades */
    public function ingredient()
    {
        return $this->belongsTo(Material::class, 'ingredient_id');
    }

    /* ----------------------------------------------------------------------
     | Scopes
     * ---------------------------------------------------------------------*/

    public function scopeByProduct($q, int $productId)
    {
        return $q->where('product_id', $productId);
    }

    /* ----------------------------------------------------------------------
     | Accessors
     * ---------------------------------------------------------------------*/

    public function getLineTotalAttribute(): float
    {
        $qty   = (float) ($this->qty ?? 0);
        $price = (float) ($this->unit_price_snapshot ?? 0);
        return round($qty * $price, 2);
    }
}
