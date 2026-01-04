<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductRecipe extends Model
{
    protected $table = 'product_recipes';

    protected $fillable = [
        'product_id',
        'material_id',

        // per-unit recipe quantity (in kg or in the given unit, depending on your design)
        'qty',

        // optional: unit of qty (kg/g/pcs/etc)
        'unit',

        // optional: snapshot price captured at recipe save time
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

    /* =======================================================================
     | RELATIONSHIPS
     * ======================================================================= */

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /* =======================================================================
     | SCOPES (OPTIONAL BUT USEFUL)
     * ======================================================================= */

    /**
     * Filter recipes by product_id.
     */
    public function scopeForProduct(Builder $query, int $productId): Builder
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Filter recipes by material_id.
     */
    public function scopeForMaterial(Builder $query, int $materialId): Builder
    {
        return $query->where('material_id', $materialId);
    }

    /**
     * Only recipes with qty > 0.
     */
    public function scopeWithPositiveQty(Builder $query): Builder
    {
        return $query->where('qty', '>', 0);
    }

    /* =======================================================================
     | HELPERS (OPTIONAL)
     * ======================================================================= */

    /**
     * Returns the effective unit price for costing.
     * Prefers snapshot if present, otherwise falls back to current material->unit_price.
     */
    public function effectiveUnitPrice(): float
    {
        $snap = (float) ($this->unit_price_snapshot ?? 0);
        if ($snap > 0) return $snap;

        return (float) ($this->material?->unit_price ?? 0);
    }

    /**
     * Returns line cost for this recipe row: qty * effective unit price.
     */
    public function lineCost(): float
    {
        return (float) ($this->qty ?? 0) * $this->effectiveUnitPrice();
    }
}
