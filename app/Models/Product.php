<?php

namespace App\Models;

use App\Models\Production;
use App\Models\ProductRecipe;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class Product extends Model
{
    use SoftDeletes;

    protected $table = 'products';

    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    /** Columns expected from controllers + schema (includes parent_id) */
    protected $fillable = [
        // Parent or Variant
        'parent_id',

        // Identity
        'product_code',
        'product_name',
        'category',

        // Pricing and costs
        'unit_cost',
        'price',
        'default_price',
        'last_cost_date',

        // Inventory and demand
        'quantity',
        'forecasted_demand',
        'current_inventory',

        // Shelf life and quality
        'shelf_life_days',
        'temp_requirements',
        'storage_zone',

        // Ops and scheduling
        'yield_rate',
        'standard_batch_size',
        'lead_time_days',
        'min_run_qty',
        'max_run_qty',
        'line_constraints',

        // Status and unit
        'status',
        'stock_status',
        'unit',

        // Media (legacy + new)
        'image_disk',
        'image_path',
        'image_medium_path',
        'image_thumb_path',
        'image_url',           // large or reference
        'card_image_url',      // mid-size for cards
        'card_image_srcset',   // responsive set
        'image_original_url',  // optional original URL

        // Legacy or optional
        'production_date',
    ];

    protected $casts = [
        'id' => 'integer',
        'parent_id' => 'integer',

        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'last_cost_date' => 'date',
        'production_date' => 'date',

        'quantity' => 'float',
        'current_inventory' => 'float',
        'unit_cost' => 'float',
        'price' => 'float',
        'default_price' => 'float',
        'forecasted_demand' => 'float',

        'shelf_life_days' => 'integer',
        'yield_rate' => 'float',
        'standard_batch_size' => 'float',
        'lead_time_days' => 'integer',
        'min_run_qty' => 'float',
        'max_run_qty' => 'float',

        'line_constraints' => 'array',
    ];

    /**
     * Appended accessors for UI.
     */
    protected $appends = [
        'effective_unit_cost',
        'gross_margin_pct',
        'produced_qty_kg',
        'sold_qty_kg',
        'available_stock_kg',
        'image_url',          // accessor below
        'unit_material_cost',
        'display_name',
        'is_variant',
        'base_name',

        // Type helpers
        'type_name',
        'type_keywords',

        // Latest production snapshot
        'latest_batch_number',
        'latest_production_date',
        'latest_expiration_date',
        'latest_unit_price_pack',
        'latest_unit_price_bag',
        'latest_available_pack',
        'latest_available_bag',

        // Rollups
        'total_available_pack',
        'total_available_bag',

        // Image helpers for cards
        'image_thumb_url',
        'card_image_url',
        'card_image_srcset',
    ];

    /* ----------------------------------------------------------------------
     | Relationships
     * ---------------------------------------------------------------------*/
    public function parent()
    {
        return $this->belongsTo(Product::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Product::class, 'parent_id');
    }

    public function variants()
    {
        return $this->children();
    }

    public function productions()
    {
        return $this->hasMany(Production::class, 'product_id');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'product_id');
    }

    public function recipes()
    {
        return $this->hasMany(ProductRecipe::class)->with('material');
    }

    /**
     * Latest production record (no N+1 when eager loaded).
     */
    public function latestProduction()
    {
        return $this->hasOne(Production::class, 'product_id')
            ->ofMany('production_date', 'max');
    }

    /* ----------------------------------------------------------------------
     | Accessors and Mutators
     * ---------------------------------------------------------------------*/
    protected function displayName(): Attribute
    {
        return Attribute::get(function () {
            $raw = $this->getAttributes();
            return Arr::get($raw, 'name', $this->product_name);
        });
    }

    protected function lineConstraints(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                if (is_array($value)) {
                    return $value;
                }

                if (is_string($value) && $value !== '') {
                    $decoded = json_decode($value, true);
                    return is_array($decoded) ? $decoded : $value;
                }

                return $value;
            }
        );
    }

    /** Virtual "image" maps to image_path for older forms or views. */
    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->image_path,
            set: fn($value) => ['image_path' => $value],
        );
    }

    /**
     * Which disk to use for product images.
     */
    protected function imageDisk(): string
    {
        // Always store product images on the public disk
        return $this->image_disk ?: 'public';
    }



    /**
     * Public URL for images, prefer DB image_url if present.
     */
    public function getImageUrlAttribute($value): string
    {
        // If DB already stores a full URL, use it.
        if (!empty($value) && is_string($value)) {
            return $value;
        }

        $disk = $this->imageDisk();

        // Try physical path columns
        if (!empty($this->image_path)) {
            try {
                return Storage::disk($disk)->url(ltrim($this->image_path, '/'));
            } catch (\Throwable $e) {
                return asset('storage/' . ltrim($this->image_path, '/'));
            }
        }

        // Then try thumb URL if accessor can resolve it
        if ($this->image_thumb_url !== null) {
            return (string) $this->image_thumb_url;
        }

        // Fallback placeholder
        return asset('images/default-product.png');
    }

    /**
     * Thumbnail URL (400px/800px/1200px) for cards.
     */
    public function getImageThumbUrlAttribute(): ?string
    {
        $disk = $this->imageDisk();

        try {
            if (!empty($this->image_thumb_path)) {
                return Storage::disk($disk)->url($this->image_thumb_path);
            }

            if (!empty($this->image_medium_path)) {
                return Storage::disk($disk)->url($this->image_medium_path);
            }

            if (!empty($this->image_path)) {
                return Storage::disk($disk)->url($this->image_path);
            }
        } catch (\Throwable $e) {
            // ignore and let caller fall back
        }

        return null;
    }

    /**
     * Main image URL for product cards.
     *
     * Priority:
     * 1) card_image_url column (if present)
     * 2) thumbnail URL
     * 3) main image URL accessor
     */
    public function getCardImageUrlAttribute($value): string
    {
        if (!empty($value) && is_string($value)) {
            return $value;
        }

        if ($this->image_thumb_url !== null) {
            return (string) $this->image_thumb_url;
        }

        // Use the main image accessor (already has fallbacks)
        return $this->image_url;
    }

    /**
     * Responsive srcset for cards.
     */
    public function getCardImageSrcsetAttribute($value): ?string
    {
        if (!empty($value) && is_string($value)) {
            return $value;
        }

        $disk = $this->imageDisk();
        $parts = [];

        $push = function (?string $path, string $size) use (&$parts, $disk) {
            if (!$path) {
                return;
            }

            try {
                $url = Storage::disk($disk)->url($path);
            } catch (\Throwable $e) {
                $url = asset('storage/' . ltrim($path, '/'));
            }

            $parts[] = "{$url} {$size}";
        };

        $push($this->image_thumb_path, '400w');
        $push($this->image_medium_path, '800w');
        $push($this->image_path, '1200w');

        return $parts ? implode(', ', $parts) : null;
    }

    /** BOM material cost per ONE unit. */
    public function getUnitMaterialCostAttribute(): float
    {
        $rows = $this->relationLoaded('recipes') ? $this->recipes : $this->recipes()->get();
        $sum = 0.0;

        foreach ($rows as $r) {
            $qty = is_numeric($r->qty) ? (float) $r->qty : 0.0;
            $unit = is_numeric($r->unit_price_snapshot) ? (float) $r->unit_price_snapshot : 0.0;
            $sum += $qty * $unit;
        }

        return round($sum, 2);
    }

    /** Effective unit cost. */
    public function getEffectiveUnitCostAttribute(): float
    {
        $declared = $this->unit_cost !== null ? (float) $this->unit_cost : null;
        return $declared !== null ? $declared : (float) $this->unit_material_cost;
    }

    /** Preferred selling price. */
    public function getPriceAttribute($value): float
    {
        $p = $value
            ?? $this->getRawOriginal('default_price')
            ?? $this->getRawOriginal('selling_price');

        return (float) ($p !== null ? $p : $this->effective_unit_cost);
    }

    /** Gross margin percentage. */
    public function getGrossMarginPctAttribute(): ?float
    {
        $cost = (float) $this->effective_unit_cost;
        $price = (float) $this->price;

        if ($price <= 0) {
            return null;
        }

        return round((($price - $cost) / $price) * 100, 2);
    }

    /** Totals pulled from related tables. */
    public function getProducedQtyKgAttribute(): float
    {
        return (float) ($this->productions()->sum('quantity') ?? 0);
    }

    public function getSoldQtyKgAttribute(): float
    {
        // Every sale-creation path writes the same value into both quantity_kg and quantity,
        // so summing both and adding them (the old formula) double-counted every sale. This
        // also silently understated available_stock_kg (produced - sold), which depends on it.
        $sum = $this->sales()
            ->selectRaw('SUM(COALESCE(quantity_kg, quantity, 0)) as s')
            ->value('s');

        return (float) ($sum ?? 0);
    }

    public function getAvailableStockKgAttribute(): float
    {
        $available = $this->produced_qty_kg - $this->sold_qty_kg;
        return $available > 0 ? (float) $available : 0.0;
    }

    public function getIsVariantAttribute(): bool
    {
        return !empty($this->parent_id);
    }

    public function getBaseNameAttribute(): ?string
    {
        return $this->parent?->product_name;
    }

    /**
     * Human friendly type name for base or variant.
     */
    public function getTypeNameAttribute(): string
    {
        $childName = trim((string) ($this->product_name ?? ''));
        $parentName = trim((string) ($this->parent?->product_name ?? ''));
        $category = trim((string) ($this->category ?? ''));

        if (!$this->is_variant) {
            return $category !== '' ? $category : 'Base';
        }

        if ($childName !== '' && $parentName !== '') {
            if (stripos($childName, $parentName) !== false) {
                $type = trim(preg_replace('/\s+/', ' ', str_ireplace($parentName, '', $childName)));
                if ($type !== '') {
                    return $type;
                }
            }
        }

        if (preg_match('/[-–]\s*(.+)$/u', $childName, $m)) {
            return trim($m[1]);
        }

        if (preg_match('/\(([^)]+)\)/', $childName, $m)) {
            return trim($m[1]);
        }

        if ($category !== '') {
            return $category;
        }

        return $childName !== '' ? $childName : 'Variant';
    }

    /**
     * Searchable type keywords.
     */
    public function getTypeKeywordsAttribute(): string
    {
        $parts = [
            mb_strtolower($this->type_name),
            mb_strtolower($this->product_name ?? ''),
            mb_strtolower($this->parent?->product_name ?? ''),
            mb_strtolower($this->category ?? ''),
        ];

        return trim(preg_replace('/\s+/', ' ', implode(' ', array_filter($parts))));
    }

    /** Convenience: compute an expiry date from a given production date. */
    public function computeExpiryFrom(\DateTimeInterface|string|null $productionDate): ?string
    {
        if (!$productionDate || !$this->shelf_life_days) {
            return null;
        }

        $c = \Carbon\Carbon::make($productionDate) ?? \Carbon\Carbon::parse($productionDate);
        return $c->addDays((int) $this->shelf_life_days)->toDateString();
    }

    /* ---------- Production snapshot accessors ----------- */

    public function getLatestBatchNumberAttribute(): ?string
    {
        $snap = $this->getRawOriginal('latest_batch_number');
        if ($snap !== null) {
            return $snap;
        }

        $latest = $this->relationLoaded('latestProduction')
            ? $this->latestProduction
            : $this->latestProduction()->first();

        return $latest?->batch_number;
    }

    public function getLatestProductionDateAttribute(): ?string
    {
        $snap = $this->getRawOriginal('latest_production_date');
        if ($snap !== null) {
            return $snap;
        }

        $latest = $this->relationLoaded('latestProduction')
            ? $this->latestProduction
            : $this->latestProduction()->first();

        return optional($latest?->production_date)?->toDateString();
    }

    public function getLatestExpirationDateAttribute(): ?string
    {
        $snap = $this->getRawOriginal('latest_expiration_date');
        if ($snap !== null) {
            return $snap;
        }

        $latest = $this->relationLoaded('latestProduction')
            ? $this->latestProduction
            : $this->latestProduction()->first();

        return optional($latest?->expiration_date)?->toDateString();
    }

    public function getLatestUnitPricePackAttribute(): ?float
    {
        $snap = $this->getRawOriginal('latest_unit_price_pack');
        if ($snap !== null) {
            return (float) $snap;
        }

        $latest = $this->relationLoaded('latestProduction')
            ? $this->latestProduction
            : $this->latestProduction()->first();

        return $latest?->unit_price_pack !== null ? (float) $latest->unit_price_pack : null;
    }

    public function getLatestUnitPriceBagAttribute(): ?float
    {
        $snap = $this->getRawOriginal('latest_unit_price_bag');
        if ($snap !== null) {
            return (float) $snap;
        }

        $latest = $this->relationLoaded('latestProduction')
            ? $this->latestProduction
            : $this->latestProduction()->first();

        return $latest?->unit_price_bag !== null ? (float) $latest->unit_price_bag : null;
    }

    public function getLatestAvailablePackAttribute(): ?int
    {
        $snap = $this->getRawOriginal('latest_available_pack');
        if ($snap !== null) {
            return (int) $snap;
        }

        $latest = $this->relationLoaded('latestProduction')
            ? $this->latestProduction
            : $this->latestProduction()->first();

        return $latest?->available_pack !== null ? (int) $latest->available_pack : null;
    }

    public function getLatestAvailableBagAttribute(): ?int
    {
        $snap = $this->getRawOriginal('latest_available_bag');
        if ($snap !== null) {
            return (int) $snap;
        }

        $latest = $this->relationLoaded('latestProduction')
            ? $this->latestProduction
            : $this->latestProduction()->first();

        return $latest?->available_bag !== null ? (int) $latest->available_bag : null;
    }

    public function getTotalAvailablePackAttribute(): int
    {
        $snap = $this->getRawOriginal('total_available_pack');
        if ($snap !== null) {
            return (int) $snap;
        }

        return (int) $this->productions()->sum('available_pack');
    }

    public function getTotalAvailableBagAttribute(): int
    {
        $snap = $this->getRawOriginal('total_available_bag');
        if ($snap !== null) {
            return (int) $snap;
        }

        return (int) $this->productions()->sum('available_bag');
    }

    /* ----------------------------------------------------------------------
     | Upload helper for controllers (Intervention v3)
     * ---------------------------------------------------------------------*/
    public function setImageFromUpload(UploadedFile $file): void
    {
        $disk = $this->imageDisk() ?: 'public';

        try {
            if (!class_exists(Image::class)) {
                throw new \RuntimeException('Intervention Image facade not available');
            }

            $productId = $this->id ?: 'tmp';
            $baseName = Str::slug($this->product_name ?: 'product');
            $base = "products/{$productId}/{$baseName}";

            $img = Image::read($file->getRealPath())->orient();
            $master = (clone $img)->scaleDown(1600, 1600);

            $w1200 = (clone $master)->scaleDown(1200, 1200);
            $w800 = (clone $master)->scaleDown(800, 800);
            $w400 = (clone $master)->scaleDown(400, 400);

            $path1200 = "{$base}-1200.webp";
            $path800 = "{$base}-800.webp";
            $path400 = "{$base}-400.webp";

            Storage::disk($disk)->put($path1200, (string) $w1200->toWebp(quality: 80));
            Storage::disk($disk)->put($path800, (string) $w800->toWebp(quality: 80));
            Storage::disk($disk)->put($path400, (string) $w400->toWebp(quality: 80));

            $url1200 = Storage::disk($disk)->url($path1200);
            $url800 = Storage::disk($disk)->url($path800);
            $url400 = Storage::disk($disk)->url($path400);

            $srcset = "{$url400} 400w, {$url800} 800w, {$url1200} 1200w";

            $this->image_disk = $disk;
            $this->image_path = $path1200;
            $this->image_medium_path = $path800;
            $this->image_thumb_path = $path400;

            $this->image_url = $url1200;
            $this->card_image_url = $url800;
            $this->card_image_srcset = $srcset;
            $this->image_original_url = $url1200;

        } catch (\Throwable $e) {

            // Fallback — ALWAYS WORKS
            $productId = $this->id ?: 'tmp';
            $baseDir = "products/{$productId}";
            $baseName = Str::slug($this->product_name ?: 'product');
            $ext = $file->getClientOriginalExtension() ?: 'jpg';
            $filename = "{$baseName}.{$ext}";
            $path = "{$baseDir}/{$filename}";

            Storage::disk($disk)->putFileAs($baseDir, $file, $filename);

            $url = Storage::disk($disk)->url($path);

            $this->image_disk = $disk;
            $this->image_path = $path;
            $this->image_url = $url;
            $this->card_image_url = $url;
            $this->card_image_srcset = null;
        }
    }



    /**
     * Replace image_path and delete the previous file.
     */
    public function replaceImagePath(?string $newPath): void
    {
        $old = $this->getOriginal('image_path');
        $disk = $this->imageDisk();

        $this->image_path = $newPath;

        if ($old && $old !== $newPath && Storage::disk($disk)->exists($old)) {
            Storage::disk($disk)->delete($old);
        }
    }

    /* ----------------------------------------------------------------------
     | Model events
     * ---------------------------------------------------------------------*/
    protected static function booted()
    {
        static::saving(function (self $m) {
            $has = fn(string $c) => Schema::hasColumn($m->getTable(), $c);

            if ($has('quantity') && $m->quantity === null) {
                $m->quantity = 0;
            }

            if ($has('forecasted_demand') && $m->forecasted_demand === null) {
                $m->forecasted_demand = 0;
            }

            if ($has('stock_status') && empty($m->stock_status)) {
                $m->stock_status = 'in_stock';
            }
        });

        static::updating(function (self $model) {
            if (Schema::hasColumn($model->getTable(), 'image_path') && $model->isDirty('image_path')) {
                $old = $model->getOriginal('image_path');
                $disk = $model->imageDisk();

                if ($old && Storage::disk($disk)->exists($old)) {
                    Storage::disk($disk)->delete($old);
                }
            }
        });

        static::forceDeleted(function (self $model) {
            if (
                Schema::hasColumn($model->getTable(), 'image_path')
                && $model->image_path
            ) {
                $disk = $model->imageDisk();
                if (Storage::disk($disk)->exists($model->image_path)) {
                    Storage::disk($disk)->delete($model->image_path);
                }
            }
        });
    }

    /* ----------------------------------------------------------------------
     | Small helper for scopes
     * ---------------------------------------------------------------------*/
    protected static function has(string $column): bool
    {
        return Schema::hasColumn((new static)->getTable(), $column);
    }

    /* ----------------------------------------------------------------------
     | Query scopes
     * ---------------------------------------------------------------------*/
    public function scopeSearch($q, ?string $term)
    {
        if (!$term) {
            return $q;
        }

        $s = trim($term);

        return $q->where(function ($qq) use ($s) {
            $qq->where('product_name', 'like', "%{$s}%");

            if (self::has('product_code')) {
                $qq->orWhere('product_code', 'like', "%{$s}%");
            }

            if (self::has('category')) {
                $qq->orWhere('category', 'like', "%{$s}%");
            }

            if (self::has('name')) {
                $qq->orWhere('name', 'like', "%{$s}%");
            }
        });
    }

    public function scopeCategory($q, ?string $category)
    {
        if (!$category) {
            return $q;
        }

        return $q->where('category', $category);
    }

    /** If status exists, use it, else fall back to stock_status. */
    public function scopeStatus($q, $status)
    {
        if (empty($status)) {
            return $q;
        }

        $col = self::has('status')
            ? 'status'
            : (self::has('stock_status') ? 'stock_status' : null);

        if (!$col) {
            return $q;
        }

        return is_array($status)
            ? $q->whereIn($col, $status)
            : $q->where($col, $status);
    }

    public function scopeSorted($q, ?string $sort)
    {
        $map = [
            'name_asc' => ['product_name', 'asc'],
            'name_desc' => ['product_name', 'desc'],
            'category' => ['category', 'asc'],
            'stock_desc' => ['quantity', 'desc'],
            'stock_asc' => ['quantity', 'asc'],
            'cost_desc' => ['unit_cost', 'desc'],
            'cost_asc' => ['unit_cost', 'asc'],
            'updated_desc' => ['updated_at', 'desc'],
            'recent' => ['updated_at', 'desc'],
        ];

        if (!$sort || !isset($map[$sort])) {
            return $q->orderBy('product_name', 'asc');
        }

        [$col, $dir] = $map[$sort];

        return $q->orderBy($col, $dir);
    }

    public function scopeRoots($q)
    {
        return $q->whereNull('parent_id');
    }

    public function scopeVariantsOf($q, int $parentId)
    {
        return $q->where('parent_id', $parentId);
    }

    /**
     * Production snapshot without N+1.
     */
    public function scopeWithLatestProductionSnapshot($q)
    {
        $q->addSelect([
            'latest_batch_number' => Production::select('batch_number')
                ->whereColumn('productions.product_id', 'products.id')
                ->orderByDesc('production_date')
                ->orderByDesc('id')
                ->limit(1),

            'latest_production_date' => Production::select('production_date')
                ->whereColumn('productions.product_id', 'products.id')
                ->orderByDesc('production_date')
                ->orderByDesc('id')
                ->limit(1),

            'latest_expiration_date' => Production::select('expiration_date')
                ->whereColumn('productions.product_id', 'products.id')
                ->orderByDesc('production_date')
                ->orderByDesc('id')
                ->limit(1),

            'latest_unit_price_pack' => Production::select('unit_price_pack')
                ->whereColumn('productions.product_id', 'products.id')
                ->orderByDesc('production_date')
                ->orderByDesc('id')
                ->limit(1),

            'latest_unit_price_bag' => Production::select('unit_price_bag')
                ->whereColumn('productions.product_id', 'products.id')
                ->orderByDesc('production_date')
                ->orderByDesc('id')
                ->limit(1),

            'latest_available_pack' => Production::select('available_pack')
                ->whereColumn('productions.product_id', 'products.id')
                ->orderByDesc('production_date')
                ->orderByDesc('id')
                ->limit(1),

            'latest_available_bag' => Production::select('available_bag')
                ->whereColumn('productions.product_id', 'products.id')
                ->orderByDesc('production_date')
                ->orderByDesc('id')
                ->limit(1),

            'total_available_pack' => Production::selectRaw('COALESCE(SUM(available_pack),0)')
                ->whereColumn('productions.product_id', 'products.id'),

            'total_available_bag' => Production::selectRaw('COALESCE(SUM(available_bag),0)')
                ->whereColumn('productions.product_id', 'products.id'),
        ]);

        return $q;
    }

    /* ----------------------------------------------------------------------
     | Convenience
     * ---------------------------------------------------------------------*/
    public function totalSold(): float
    {
        return $this->sold_qty_kg;
    }

    public function remainingStock(): float
    {
        return $this->available_stock_kg;
    }

    /**
     * Base storage path for this product's images using a clean product name.
     */
    protected function imageBasePath(): string
    {
        $productId = $this->id ?: 'tmp';
        $baseName = Str::slug($this->product_name ?: 'product');

        return "products/{$productId}/{$baseName}";
    }

}
