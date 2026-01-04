<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class Material extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'materials';

    /** Allowed units */
    public const ALLOWED_UNITS = [
        'kg','g','lbs','pcs','pkg','box','bag','roll','tray','lt','ml','m3',
    ];

    /** Allowed storage types */
    public const STORAGE_TYPES = [
        'chiller','freezer','dry','ambient',
    ];

    protected $fillable = [
        'material_name',
        'category',
        'unit',
        'sku',
        'unit_price',
        'quantity_kg',
        'min_stock_kg',
        'stock_status',
        'supplier_name',
        'batch_code',
        'storage_type',
        'manufactured_at',
        'received_at',
        'expires_at',
        'notes',
    ];

    protected $casts = [
        'unit_price'      => 'decimal:2',
        'quantity_kg'     => 'decimal:3',
        'min_stock_kg'    => 'decimal:3',
        'manufactured_at' => 'date',
        'received_at'     => 'date',
        'expires_at'      => 'date',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
        'deleted_at'      => 'datetime',
    ];

    protected $attributes = [
        'unit_price'   => 0.00,
        'quantity_kg'  => 0.000,
        'min_stock_kg' => null,
        'unit'         => 'kg',
        'stock_status' => null,
        'storage_type' => null,
    ];

    protected $appends = [
        'inventory_value',
        'unit_label',
        'is_low_stock',
        'days_until_expiry',
        'expiry_status',

        // predictive helpers (controller can inject using applyPrediction())
        'avg_daily_usage_7d',
        'avg_daily_usage_30d',
        'burn_per_day',
        'days_to_min_stock',
        'predicted_reorder_date',

        // used-in convenience (works if query added it; otherwise returns fallback)
        'used_in_products',
    ];

    /** Runtime-only cache (not persisted) */
    protected array $predictionCache = [
        'avg7'   => null,
        'avg30'  => null,
        'burn'   => null,
        'daysTo' => null,
        'date'   => null,
    ];

    /* -----------------------------------------------------------------
     | Model events
     * -----------------------------------------------------------------*/
    protected static function booted(): void
    {
        static::saving(function (self $m): void {
            // Auto-compute stock_status if column exists
            if (Schema::hasColumn($m->getTable(), 'stock_status')) {
                $q   = (float) ($m->attributes['quantity_kg']  ?? 0);
                $min = (float) ($m->attributes['min_stock_kg'] ?? 0);
                $m->attributes['stock_status'] = self::computeStockStatus($q, $min);
            }

            // Normalize storage_type
            if (Schema::hasColumn($m->getTable(), 'storage_type')) {
                $st = $m->attributes['storage_type'] ?? null;
                if ($st !== null) {
                    $st = strtolower(trim((string) $st));
                    $m->attributes['storage_type'] = in_array($st, self::STORAGE_TYPES, true) ? $st : null;
                }
            }

            // Normalize unit
            if (Schema::hasColumn($m->getTable(), 'unit')) {
                $u = $m->attributes['unit'] ?? 'kg';
                $u = strtolower(trim((string) $u));
                $m->attributes['unit'] = in_array($u, self::ALLOWED_UNITS, true) ? $u : 'kg';
            }
        });
    }

    /* -----------------------------------------------------------------
     | ACCESSORS (normalize decimals to floats)
     * -----------------------------------------------------------------*/

    public function getUnitPriceAttribute($value): float
    {
        return $value === null ? 0.0 : (float) $value;
    }

    public function getQuantityKgAttribute($value): float
    {
        return $value === null ? 0.0 : (float) $value;
    }

    public function getMinStockKgAttribute($value): ?float
    {
        return $value === null ? null : (float) $value;
    }

    public function getInventoryValueAttribute(): float
    {
        return round(((float) $this->unit_price) * ((float) $this->quantity_kg), 2);
    }

    public function getUnitLabelAttribute(): string
    {
        $map = [
            'kg'  => 'Kilograms',   'g'   => 'Grams',        'lbs' => 'Pounds',
            'pcs' => 'Pieces',      'pkg' => 'Package',      'box' => 'Box',
            'bag' => 'Bag',         'roll'=> 'Roll',         'tray'=> 'Tray',
            'lt'  => 'Liters',      'ml'  => 'Milliliters',  'm3'  => 'Cubic Meter',
        ];
        $u = (string) ($this->attributes['unit'] ?? 'kg');
        return $map[$u] ?? strtoupper($u);
    }

    public function getIsLowStockAttribute(): bool
    {
        $q   = (float) $this->quantity_kg;
        $min = $this->min_stock_kg;

        return $min !== null && $min > 0 && $q <= $min;
    }

    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (!$this->expires_at) return null;

        return now()->startOfDay()->diffInDays(
            $this->expires_at->startOfDay(),
            false
        );
    }

    public function getExpiryStatusAttribute(): ?string
    {
        if (!$this->expires_at) return null;

        $days = $this->days_until_expiry;
        if ($days < 0) return 'expired';
        if ($days <= 7) return 'near';
        return 'fresh';
    }

    /* -----------------------------------------------------------------
     | USED-IN (supports controller query adding used_in_products)
     * -----------------------------------------------------------------*/
    public function getUsedInProductsAttribute(): int
    {
        // ✅ if controller added it via scopeWithUsedInProducts()
        if (array_key_exists('used_in_products', $this->attributes)) {
            return (int) ($this->attributes['used_in_products'] ?? 0);
        }

        // ✅ fallback (safe but can be N+1 if you rely on it heavily)
        return (int) DB::table('product_recipes')
            ->where('material_id', $this->id)
            ->distinct('product_id')
            ->count('product_id');
    }

    /**
     * ✅ Adds: used_in_products = COUNT(DISTINCT product_id) from product_recipes.
     * Use this in controller query to avoid N+1.
     */
    public function scopeWithUsedInProducts(Builder $query): Builder
    {
        return $query->addSelect([
            'used_in_products' => DB::table('product_recipes')
                ->selectRaw('COUNT(DISTINCT product_id)')
                ->whereColumn('product_recipes.material_id', 'materials.id')
        ]);
    }

    /* -----------------------------------------------------------------
     | PREDICTIVE ACCESSORS (no DB queries)
     * -----------------------------------------------------------------*/

    public function applyPrediction(array $p): void
    {
        $this->predictionCache['avg7']   = $p['avg7'] ?? $p['avg_daily_usage_7d'] ?? null;
        $this->predictionCache['avg30']  = $p['avg30'] ?? $p['avg_daily_usage_30d'] ?? null;
        $this->predictionCache['burn']   = $p['burn_per_day'] ?? null;
        $this->predictionCache['daysTo'] = $p['days_to_min'] ?? null;
        $this->predictionCache['date']   = $p['reorder_date'] ?? null;
    }

    public function getAvgDailyUsage7dAttribute(): ?float
    {
        return $this->predictionCache['avg7'] !== null ? (float) $this->predictionCache['avg7'] : null;
    }

    public function getAvgDailyUsage30dAttribute(): ?float
    {
        return $this->predictionCache['avg30'] !== null ? (float) $this->predictionCache['avg30'] : null;
    }

    public function getBurnPerDayAttribute(): ?float
    {
        return $this->predictionCache['burn'] !== null ? (float) $this->predictionCache['burn'] : null;
    }

    public function getDaysToMinStockAttribute(): ?float
    {
        return $this->predictionCache['daysTo'] !== null ? (float) $this->predictionCache['daysTo'] : null;
    }

    public function getPredictedReorderDateAttribute(): ?string
    {
        return $this->predictionCache['date'] !== null ? (string) $this->predictionCache['date'] : null;
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
        $this->attributes['category'] = $v === '' ? null : $v;
    }

    public function setSkuAttribute($value): void
    {
        $v = strtoupper(trim((string) $value));
        $v = preg_replace('/[^A-Z0-9\-\_]/', '', (string) $v);
        $this->attributes['sku'] = $v === '' ? null : $v;
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

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') return $query;

        return $query->where(function (Builder $q) use ($term) {
            $q->where('material_name', 'like', "%{$term}%")
              ->orWhere('sku', 'like', "%{$term}%");
        });
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereNotNull('min_stock_kg')
            ->whereColumn('quantity_kg', '<=', 'min_stock_kg');
    }

    public function scopeSortBy(Builder $query, string $key): Builder
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

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expires_at')
            ->whereDate('expires_at', '<', now()->toDateString());
    }

    public function scopeExpiringSoon(Builder $query, int $days = 7): Builder
    {
        $today = now()->toDateString();
        $limit = now()->addDays($days)->toDateString();

        return $query->whereNotNull('expires_at')
            ->whereBetween('expires_at', [$today, $limit]);
    }

    public function scopeFresh(Builder $query, int $days = 7): Builder
    {
        $limit = now()->addDays($days)->toDateString();

        return $query->whereNotNull('expires_at')
            ->whereDate('expires_at', '>', $limit);
    }

    /* -----------------------------------------------------------------
     | RELATIONSHIPS
     * -----------------------------------------------------------------*/

    public function recipes(): HasMany
    {
        // ✅ matches your product_recipes table column: material_id
        return $this->hasMany(ProductRecipe::class, 'material_id');
    }

    /* -----------------------------------------------------------------
     | HELPERS
     * -----------------------------------------------------------------*/

    public static function computeStockStatus(float $qty, float $min): string
    {
        if ($min <= 0) return 'in_stock';
        return $qty <= $min ? 'low' : 'in_stock';
    }

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
        $v = preg_replace('/\s+/u', ' ', $v);
        return trim($v);
    }

    private function normalizeMoney($v): float
    {
        if ($v === null) return 0.00;

        if (is_numeric($v)) {
            $val = round((float) $v, 2);
            return min(max($val, 0.00), 9999999999.99);
        }

        $s = (string) $v;
        $s = preg_replace('/[₱\p{Sc}\s]+/u', '', $s);

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
        return min(max($val, 0.00), 9999999999.99);
    }

    private function normalizeQty($v): float
    {
        if ($v === null) return 0.000;

        if (is_numeric($v)) {
            $val = round((float) $v, 3);
            return min(max($val, 0.000), 999999999999.999);
        }

        $s = (string) $v;
        $s = preg_replace('/\s+/u', '', $s);

        if (str_contains($s, ',') && !str_contains($s, '.')) {
            $s = str_replace(',', '.', $s);
        } else {
            $s = str_replace(',', '', $s);
        }

        if ($s === '' || !is_numeric($s)) return 0.000;

        $val = round((float) $s, 3);
        return min(max($val, 0.000), 999999999999.999);
    }
}
