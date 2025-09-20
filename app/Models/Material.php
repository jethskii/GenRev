<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $table = 'materials';

    /** Mass-assignable fields */
    protected $fillable = [
        'material_name',
        'category',
        'unit',
        'sku',
        'unit_price',
        'quantity_kg',
        'min_stock_kg',
    ];

    /** Enable timestamps */
    public $timestamps = true;

    /** Safe numeric casts for math & formatting */
    protected $casts = [
        'unit_price'   => 'decimal:2',
        'quantity_kg'  => 'decimal:3',
        'min_stock_kg' => 'decimal:3',
    ];

    /** Defaults to avoid null math */
    protected $attributes = [
        'unit_price'   => 0.00,
        'quantity_kg'  => 0.000,
        'min_stock_kg' => null,
    ];

    /* ------------------------- ACCESSORS ------------------------- */

    public function getUnitPriceAttribute($value): float
    {
        return (float)$value;
    }

    public function getQuantityKgAttribute($value): float
    {
        return (float)$value;
    }

    public function getMinStockKgAttribute($value): ?float
    {
        return $value !== null ? (float)$value : null;
    }

    /**
     * Computed: inventory value (unit_price * quantity_kg)
     */
    public function getInventoryValueAttribute(): float
    {
        return round((float)$this->unit_price * (float)$this->quantity_kg, 2);
    }

    /* ------------------------- MUTATORS ------------------------- */

    public function setUnitPriceAttribute($value): void
    {
        $this->attributes['unit_price'] = $this->normalizeMoney($value);
    }

    public function setQuantityKgAttribute($value): void
    {
        $this->attributes['quantity_kg'] = $this->normalizeQty($value);
    }

    public function setMinStockKgAttribute($value): void
    {
        $this->attributes['min_stock_kg'] = $value !== null ? $this->normalizeQty($value) : null;
    }

    /* ------------------------- HELPERS ------------------------- */

    private function normalizeMoney($v): float
    {
        $v = (string) $v;
        $v = str_replace(['₱', ',', ' '], '', $v);
        if ($v === '' || !is_numeric($v)) {
            return 0.00;
        }
        $val = round((float)$v, 2);
        // clamp to DECIMAL(12,2)
        return min(max($val, 0.00), 9999999999.99);
    }

    private function normalizeQty($v): float
    {
        $v = (string) $v;
        $v = str_replace([',', ' '], '', $v);
        if ($v === '' || !is_numeric($v)) {
            return 0.000;
        }
        $val = round((float)$v, 3);
        // clamp to DECIMAL(14,3)
        return min(max($val, 0.000), 999999999999.999);
    }
}
