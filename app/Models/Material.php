<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Material extends Model
{
    use HasFactory;

    /** Table & PK */
    protected $table = 'materials';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

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

    /** Casts (note: decimal casts return strings; accessors coerce to float) */
    protected $casts = [
        'unit_price'   => 'decimal:2',
        'quantity_kg'  => 'decimal:3',
        'min_stock_kg' => 'decimal:3',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    /** Defaults to avoid null math */
    protected $attributes = [
        'unit_price'   => 0.00,
        'quantity_kg'  => 0.000,
        'min_stock_kg' => null,
        'unit'         => 'kg',
    ];

    /** Append computed fields to arrays/JSON */
    protected $appends = [
        'inventory_value',
        'unit_label',
    ];

    /** Allowed units (should match controller + DB enum) */
    public const ALLOWED_UNITS = ['kg','g','lbs','pcs','pkg','box','bag','roll','tray','lt','ml','m3'];

    /* ------------------------- ACCESSORS ------------------------- */

    public function getUnitPriceAttribute($value): float
    {
        return is_null($value) ? 0.0 : (float) $value;
    }

    public function getQuantityKgAttribute($value): float
    {
        return is_null($value) ? 0.0 : (float) $value;
    }

    public function getMinStockKgAttribute($value): ?float
    {
        return is_null($value) ? null : (float) $value;
    }

    /** Computed: unit_price * quantity_kg */
    public function getInventoryValueAttribute(): float
    {
        $price = (float) ($this->attributes['unit_price'] ?? 0);
        $qty   = (float) ($this->attributes['quantity_kg'] ?? 0);
        return round($price * $qty, 2);
    }

    /** Friendly label for the unit (nice for APIs/UI) */
    public function getUnitLabelAttribute(): string
    {
        $map = [
            'kg'  => 'Kilograms',   'g'   => 'Grams',        'lbs' => 'Pounds',
            'pcs' => 'Pieces',      'pkg' => 'Package',      'box' => 'Box',
            'bag' => 'Bag',         'roll'=> 'Roll',         'tray'=> 'Tray',
            'lt'  => 'Liters',      'ml'  => 'Milliliters',  'm3'  => 'Cubic Meter',
        ];
        $u = $this->attributes['unit'] ?? 'kg';
        return $map[$u] ?? strtoupper($u);
    }

    /* ------------------------- MUTATORS ------------------------- */

    /** Normalize and constrain unit to allowed list; default to 'kg' */
    public function setUnitAttribute($value): void
    {
        $v = strtolower(trim((string) $value));
        $this->attributes['unit'] = in_array($v, self::ALLOWED_UNITS, true) ? $v : 'kg';
    }

    /** Trim, collapse whitespace for material_name */
    public function setMaterialNameAttribute($value): void
    {
        $this->attributes['material_name'] = $this->cleanText($value);
    }

    /** Optional: trim category; empty → null */
    public function setCategoryAttribute($value): void
    {
        $v = $this->cleanText($value);
        $this->attributes['category'] = ($v === '') ? null : $v;
    }

    /** Uppercase, strip spaces for SKU (keep dashes/underscores) */
    public function setSkuAttribute($value): void
    {
        $v = strtoupper(trim((string) $value));
        // Allow A-Z 0-9 - _
        $v = preg_replace('/[^A-Z0-9\-\_]/', '', $v);
        $this->attributes['sku'] = ($v === '') ? null : $v;
    }

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
        $this->attributes['min_stock_kg'] = ($value !== null && $value !== '')
            ? $this->normalizeQty($value)
            : null;
    }

    /* ------------------------- SCOPES ------------------------- */

    public function scopeSearch($query, ?string $term)
    {
        $term = trim((string) $term);
        if ($term === '') return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('material_name', 'like', "%{$term}%")
              ->orWhere('sku', 'like', "%{$term}%");
        });
    }

    /* ------------------------- HELPERS ------------------------- */

    private function cleanText($v): string
    {
        $v = (string) $v;
        // Collapse multiple whitespaces to single spaces and trim
        $v = preg_replace('/\s+/u', ' ', $v);
        return trim($v);
    }

    private function normalizeMoney($v): float
    {
        if (is_null($v)) return 0.00;
        if (is_numeric($v)) {
            $val = round((float) $v, 2);
            return min(max($val, 0.00), 9999999999.99);
        }

        $s = (string) $v;
        // remove currency symbols + spaces
        $s = preg_replace('/[₱\p{Sc}\s]+/u', '', $s);

        // If both comma and dot appear, treat comma as thousands sep
        if (str_contains($s, ',') && str_contains($s, '.')) {
            $s = str_replace(',', '', $s);
        } elseif (str_contains($s, ',') && !str_contains($s, '.')) {
            // only comma: use as decimal separator
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } else {
            // only dot or digits: strip any stray commas
            $s = str_replace(',', '', $s);
        }

        if ($s === '' || !is_numeric($s)) {
            return 0.00;
        }

        $val = round((float) $s, 2);
        return min(max($val, 0.00), 9999999999.99); // clamp to DECIMAL(12,2)
    }

    private function normalizeQty($v): float
    {
        if (is_null($v)) return 0.000;
        if (is_numeric($v)) {
            $val = round((float) $v, 3);
            return min(max($val, 0.000), 999999999999.999);
        }

        $s = (string) $v;
        $s = preg_replace('/[\s,]+/u', '', $s); // remove spaces & commas

        // Handle comma-as-decimal if present alone (rare for qty but safe)
        if (str_contains($s, ',') && !str_contains($s, '.')) {
            $s = str_replace(',', '.', $s);
        }

        if ($s === '' || !is_numeric($s)) {
            return 0.000;
        }

        $val = round((float) $s, 3);
        return min(max($val, 0.000), 999999999999.999); // clamp to DECIMAL(14,3)
    }
}
