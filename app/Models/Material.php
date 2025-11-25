<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

class Material extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** Table & PK */
    protected $table = 'materials';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    /** Allowed units (keep in sync with controller + DB) */
    public const ALLOWED_UNITS = [
        'kg','g','lbs','pcs','pkg','box','bag','roll','tray','lt','ml','m3',
    ];

    /** Allowed storage types (for dropdowns + validation) */
    public const STORAGE_TYPES = [
        'chiller',
        'freezer',
        'dry',
        'ambient',
    ];

    /** Mass-assignable fields */
    protected $fillable = [
        'material_name',
        'category',
        'unit',
        'sku',
        'unit_price',
        'quantity_kg',
        'min_stock_kg',
        'stock_status',

        // NEW FIELDS
        'supplier_name',
        'batch_code',
        'storage_type',
        'manufactured_at',
        'received_at',
        'expires_at',
        'notes',
    ];

    /** Casts (note: decimal casts return strings; accessors coerce to float) */
    protected $casts = [
        'unit_price'    => 'decimal:2',
        'quantity_kg'   => 'decimal:3',
        'min_stock_kg'  => 'decimal:3',
        'manufactured_at' => 'date',
        'received_at'     => 'date',
        'expires_at'      => 'date',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
        'deleted_at'    => 'datetime',
    ];

    /** Defaults to avoid null math */
    protected $attributes = [
        'unit_price'    => 0.00,
        'quantity_kg'   => 0.000,
        'min_stock_kg'  => null,
        'unit'          => 'kg',
        'stock_status'  => null,  // recomputed on saving
        'storage_type'  => null,
    ];

    /** Append computed fields to arrays/JSON */
    protected $appends = [
        'inventory_value',
        'unit_label',
        'is_low_stock',
        'days_until_expiry',
        'expiry_status',
    ];

    /* -----------------------------------------------------------------
     | Model events
     * -----------------------------------------------------------------*/
    protected static function booted(): void
    {
        static::saving(function (self $m): void {
            // Auto-compute stock_status if the column exists (matches controller)
            if (Schema::hasColumn($m->getTable(), 'stock_status')) {
                $q   = (float) ($m->attributes['quantity_kg']  ?? 0);
                $min = (float) ($m->attributes['min_stock_kg'] ?? 0);
                $m->attributes['stock_status'] = self::computeStockStatus($q, $min);
            }

            // Normalize storage_type if column exists
            if (Schema::hasColumn($m->getTable(), 'storage_type')) {
                $st = $m->attributes['storage_type'] ?? null;
                if ($st !== null) {
                    $st = strtolower(trim((string) $st));
                    $m->attributes['storage_type'] = in_array($st, self::STORAGE_TYPES, true) ? $st : null;
                }
            }
        });
    }

    /* -----------------------------------------------------------------
     | ACCESSORS (ensure API returns floats + helper fields)
     * -----------------------------------------------------------------*/

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
        $price = (float) ($this->attributes['unit_price']  ?? 0);
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

    /** Convenience: is quantity at/below min? */
    public function getIsLowStockAttribute(): bool
    {
        $q   = (float) ($this->attributes['quantity_kg']  ?? 0);
        $min = (float) ($this->attributes['min_stock_kg'] ?? 0);
        return $min > 0 && $q <= $min;
    }

    /** Days until expiry (negative if already expired, null if no expiry set) */
    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (! $this->expires_at) {
            return null;
        }

        return now()->startOfDay()->diffInDays(
            $this->expires_at->startOfDay(),
            false // negative when expired
        );
    }

    /**
     * Expiry status for UI badges:
     * - null     : no expiry tracking
     * - expired  : expired (days < 0)
     * - near     : 0–7 days left
     * - fresh    : > 7 days left
     */
    public function getExpiryStatusAttribute(): ?string
    {
        if (! $this->expires_at) {
            return null;
        }

        $days = $this->days_until_expiry;

        if ($days < 0) {
            return 'expired';
        }

        if ($days <= 7) {
            return 'near';
        }

        return 'fresh';
    }

    /* -----------------------------------------------------------------
     | MUTATORS (normalize incoming values)
     * -----------------------------------------------------------------*/

    public function setUnitAttribute($value): void
    {
        $v = strtolower(trim((string) $value));
        $this->attributes['unit'] = in_array($v, self::ALLOWED_UNITS, true) ? $v : 'kg';
    }

    public function setMaterialNameAttribute($value): void
    {
        $this->attributes['material_name'] = $this->cleanText($value);
    }

    public function setCategoryAttribute($value): void
    {
        $v = $this->cleanText($value);
        $this->attributes['category'] = ($v === '') ? null : $v;
    }

    public function setSkuAttribute($value): void
    {
        $v = strtoupper(trim((string) $value));
        $v = preg_replace('/[^A-Z0-9\-\_]/', '', $v); // allow A-Z 0-9 - _
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

    public function setStockStatusAttribute($value): void
    {
        // Normalize to known states if manually set
        if ($value === null) {
            $this->attributes['stock_status'] = null;
            return;
        }

        $v = strtolower(trim((string) $value));
        $this->attributes['stock_status'] = in_array($v, ['low','in_stock'], true) ? $v : null;
    }

    public function setStorageTypeAttribute($value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['storage_type'] = null;
            return;
        }

        $v = strtolower(trim((string) $value));
        $this->attributes['storage_type'] = in_array($v, self::STORAGE_TYPES, true) ? $v : null;
    }

    /* -----------------------------------------------------------------
     | SCOPES
     * -----------------------------------------------------------------*/

    public function scopeSearch($query, ?string $term)
    {
        $term = trim((string) $term);
        if ($term === '') return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('material_name', 'like', "%{$term}%")
              ->orWhere('sku', 'like', "%{$term}%");
        });
    }

    public function scopeLowStock($query)
    {
        return $query->whereNotNull('min_stock_kg')
                     ->whereColumn('quantity_kg', '<=', 'min_stock_kg');
    }

    public function scopeSortBy($query, string $key)
    {
        $map = [
            'name_asc'     => ['material_name', 'asc'],
            'name_desc'    => ['material_name', 'desc'],
            'qty_desc'     => ['quantity_kg', 'desc'],
            'qty_asc'      => ['quantity_kg', 'asc'],
            'price_desc'   => ['unit_price', 'desc'],
            'price_asc'    => ['unit_price', 'asc'],
            'updated_desc' => ['updated_at', 'desc'],
            'updated_asc'  => ['updated_at', 'asc'],
        ];
        [$col, $dir] = $map[$key] ?? ['material_name', 'asc'];
        return $query->orderBy($col, $dir);
    }

    /** Expired materials: expires_at < today */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
                     ->whereDate('expires_at', '<', now()->toDateString());
    }

    /** Expiring soon: expires_at between today and +N days (default 7) */
    public function scopeExpiringSoon($query, int $days = 7)
    {
        $today = now()->toDateString();
        $limit = now()->addDays($days)->toDateString();

        return $query->whereNotNull('expires_at')
                     ->whereBetween('expires_at', [$today, $limit]);
    }

    /** Fresh: expires_at > (today + N days) */
    public function scopeFresh($query, int $days = 7)
    {
        $limit = now()->addDays($days)->toDateString();

        return $query->whereNotNull('expires_at')
                     ->whereDate('expires_at', '>', $limit);
    }

    /* -----------------------------------------------------------------
     | RELATIONSHIPS (optional, if you use recipes)
     * -----------------------------------------------------------------*/

    // public function recipesAsMaterial()
    // {
    //     return $this->hasMany(ProductRecipe::class, 'material_id');
    // }

    // public function recipesAsIngredient()
    // {
    //     return $this->hasMany(ProductRecipe::class, 'ingredient_id');
    // }

    /* -----------------------------------------------------------------
     | HELPERS
     * -----------------------------------------------------------------*/

    /** Shared stock status logic (also used in controller) */
    public static function computeStockStatus(float $qty, float $min): string
    {
        if ($min <= 0) {
            return 'in_stock';
        }

        return $qty <= $min ? 'low' : 'in_stock';
    }

    /** Batch code generator (used in controller store/update) */
    public static function generateBatchCode(array $data = []): string
    {
        $prefix = 'MAT';
        $date   = now()->format('Ymd');
        $rand   = strtoupper(str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT));

        return "{$prefix}-{$date}-{$rand}";
    }

    private function cleanText($v): string
    {
        $v = (string) $v;
        $v = preg_replace('/\s+/u', ' ', $v); // collapse whitespace
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
        $s = preg_replace('/[₱\p{Sc}\s]+/u', '', $s); // remove currency symbols & spaces

        if (str_contains($s, ',') && str_contains($s, '.')) {
            $s = str_replace(',', '', $s);
        } elseif (str_contains($s, ',') && !str_contains($s, '.')) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } else {
            $s = str_replace(',', '', $s);
        }

        if ($s === '' || !is_numeric($s)) return 0.00;

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

        if ($s !== '' && str_contains($s, ',') && !str_contains($s, '.')) {
            $s = str_replace(',', '.', $s); // comma-as-decimal
        }

        if ($s === '' || !is_numeric($s)) return 0.000;

        $val = round((float) $s, 3);
        return min(max($val, 0.000), 999999999999.999); // clamp to DECIMAL(14,3)
    }
}
