<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

use App\Models\Production;
use App\Models\Sale;
use App\Models\ProductRecipe;

class Product extends Model
{
    use SoftDeletes;

    protected $table = 'products';

    protected $primaryKey = 'id';
    public $incrementing  = true;
    protected $keyType    = 'int';

    /** Columns expected from controllers + schema (includes parent_id) */
    protected $fillable = [
        // Parent/Variant
        'parent_id',

        // Identity
        'product_code',
        'product_name',
        'category',

        // Pricing / costs
        'unit_cost',
        'price',
        'default_price',
        'last_cost_date',

        // Inventory / demand
        'quantity',
        'forecasted_demand',
        'current_inventory',

        // Shelf life / quality
        'shelf_life_days',
        'temp_requirements',
        'storage_zone',

        // Ops / scheduling
        'yield_rate',
        'standard_batch_size',
        'lead_time_days',
        'min_run_qty',
        'max_run_qty',
        'line_constraints',

        // Status / unit
        'status',
        'stock_status',
        'unit',

        // Media (legacy + new)
        'image_disk',
        'image_path',
        'image_medium_path',
        'image_thumb_path',
        'image_url',           // large / reference
        'card_image_url',      // mid-size for cards
        'card_image_srcset',   // responsive set
        'image_original_url',  // optional original URL

        // Legacy/optional
        'production_date',
    ];

    protected $casts = [
        'id'                  => 'integer',
        'parent_id'           => 'integer',

        'deleted_at'          => 'datetime',
        'created_at'          => 'datetime',
        'updated_at'          => 'datetime',
        'last_cost_date'      => 'date',
        'production_date'     => 'date',

        'quantity'            => 'float',
        'current_inventory'   => 'float',
        'unit_cost'           => 'float',
        'price'               => 'float',
        'default_price'       => 'float',
        'forecasted_demand'   => 'float',

        'shelf_life_days'     => 'integer',
        'yield_rate'          => 'float',
        'standard_batch_size' => 'float',
        'lead_time_days'      => 'integer',
        'min_run_qty'         => 'float',
        'max_run_qty'         => 'float',

        'line_constraints'    => 'array',
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
    public function parent()      { return $this->belongsTo(Product::class, 'parent_id'); }
    public function children()    { return $this->hasMany(Product::class, 'parent_id'); }
    public function variants()    { return $this->children(); }
    public function productions() { return $this->hasMany(Production::class, 'product_id'); }
    public function sales()       { return $this->hasMany(Sale::class, 'product_id'); }
    public function recipes()     { return $this->hasMany(ProductRecipe::class)->with('material'); }

    /**
     * Latest production record (no N+1 when eager loaded).
     */
    public function latestProduction()
    {
        return $this->hasOne(Production::class, 'product_id')
                    ->ofMany('production_date', 'max');
    }

    /* ----------------------------------------------------------------------
     | Accessors / Mutators
     * ---------------------------------------------------------------------*/
    protected function displayName(): Attribute
    {
        return Attribute::get(function () {
            $raw = $this->getAttributes(); // safe plain array
            return Arr::get($raw, 'name', $this->product_name);
        });
    }

    protected function lineConstraints(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                if (is_array($value)) return $value;
                if (is_string($value) && $value !== '') {
                    $decoded = json_decode($value, true);
                    return is_array($decoded) ? $decoded : $value;
                }
                return $value;
            }
        );
    }

    /** Virtual "image" maps to image_path (for older views/forms) */
    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image_path,
            set: fn ($value) => ['image_path' => $value],
        );
    }

    /**
     * Public URL for images; prefer DB `image_url` if present,
     * else derive from legacy image_path, else default placeholder.
     */
    public function getImageUrlAttribute($value): string
    {
        // Prefer the stored URL if present
        if (!empty($value)) {
            return (string) $value;
        }

        // Derive URL from stored path on configured disk
        $path = $this->image_path ?: null;
        if ($path) {
            $disk = $this->imageDisk();
            try {
                return Storage::disk($disk)->url(ltrim($path, '/'));
            } catch (\Throwable $e) {
                // Fallback if disk url() fails
                return asset('storage/' . ltrim($path, '/'));
            }
        }

        // If we have a thumb, use that
        if ($this->image_thumb_url !== null) {
            return $this->image_thumb_url;
        }

        // Last resort: placeholder
        return asset('images/default-product.png');
    }

    /**
     * Small helper: which disk to use for images.
     */
    protected function imageDisk(): string
    {
        return $this->image_disk ?: config('filesystems.default', 'public');
    }

    /**
     * Small helper: thumbnail URL (400px) for cards.
     */
    public function getImageThumbUrlAttribute(): ?string
    {
        $disk = $this->imageDisk();

        try {
            if ($this->image_thumb_path) {
                return Storage::disk($disk)->url($this->image_thumb_path);
            }

            if ($this->image_medium_path) {
                return Storage::disk($disk)->url($this->image_medium_path);
            }

            if ($this->image_path) {
                return Storage::disk($disk)->url($this->image_path);
            }
        } catch (\Throwable $e) {
            // ignore, fall through
        }

        return null;
    }

    /**
     * Main image URL that the product cards should use.
     * Prefer the explicit stored card_image_url (set by upload),
     * then the thumb, then the generic image_url, then default.
     */
    public function getCardImageUrlAttribute($value): string
    {
        if (!empty($value)) {
            return (string) $value;
        }

        return $this->image_thumb_url
            ?? $this->getImageUrlAttribute($this->attributes['image_url'] ?? null)
            ?? asset('images/default-product.png');
    }

    /**
     * Responsive srcset for cards.
     * Prefer stored card_image_srcset (upload),
     * else compose from *path columns.
     */
    public function getCardImageSrcsetAttribute($value): ?string
    {
        if (!empty($value)) {
            return (string) $value;
        }

        $disk  = $this->imageDisk();
        $parts = [];

        $push = function (?string $path, string $size) use (&$parts, $disk) {
            if (!$path) return;
            try {
                $url = Storage::disk($disk)->url($path);
            } catch (\Throwable $e) {
                $url = asset('storage/' . ltrim($path, '/'));
            }
            $parts[] = "{$url} {$size}";
        };

        $push($this->image_thumb_path,  '400w');
        $push($this->image_medium_path, '800w');
        $push($this->image_path,        '1200w');

        return $parts ? implode(', ', $parts) : null;
    }

    /** BOM material cost per ONE unit (sum of qty * snapshot unit price) */
    public function getUnitMaterialCostAttribute(): float
    {
        $rows = $this->relationLoaded('recipes') ? $this->recipes : $this->recipes()->get();
        $sum  = 0.0;
        foreach ($rows as $r) {
            $qty  = is_numeric($r->qty) ? (float) $r->qty : 0.0;
            $unit = is_numeric($r->unit_price_snapshot) ? (float) $r->unit_price_snapshot : 0.0;
            $sum += $qty * $unit;
        }
        return round($sum, 2);
    }

    /** Effective unit cost: prefer declared unit_cost; else fallback to BOM cost */
    public function getEffectiveUnitCostAttribute(): float
    {
        $declared = $this->unit_cost !== null ? (float) $this->unit_cost : null;
        return $declared !== null ? $declared : (float) $this->unit_material_cost;
    }

    /** Preferred selling price for UI/Quick Sale */
    public function getPriceAttribute($value): float
    {
        $p = $value
            ?? $this->getRawOriginal('default_price')
            ?? $this->getRawOriginal('selling_price'); // legacy

        return (float) ($p !== null ? $p : $this->effective_unit_cost);
    }

    /** Gross margin percentage (price vs effective unit cost) */
    public function getGrossMarginPctAttribute(): ?float
    {
        $cost  = (float) $this->effective_unit_cost;
        $price = (float) $this->price;
        if ($price <= 0) return null;
        return round((($price - $cost) / $price) * 100, 2);
    }

    /** Totals pulled from related tables (for KPIs) */
    public function getProducedQtyKgAttribute(): float
    {
        return (float) ($this->productions()->sum('quantity') ?? 0);
    }

    public function getSoldQtyKgAttribute(): float
    {
        $sum = $this->sales()
            ->selectRaw('COALESCE(SUM(quantity_kg),0) + COALESCE(SUM(quantity),0) as s')
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
     * Human-friendly type name for a product or variant,
     * used on product-level views (not per-batch).
     */
    public function getTypeNameAttribute(): string
    {
        $childName  = trim((string) ($this->product_name ?? ''));
        $parentName = trim((string) ($this->parent?->product_name ?? ''));
        $category   = trim((string) ($this->category ?? ''));

        // Base product: prefer category, else "Base"
        if (!$this->is_variant) {
            return $category !== '' ? $category : 'Base';
        }

        // Variant logic
        if ($childName !== '' && $parentName !== '') {
            // If child contains parent, strip parent name to get just the type
            if (stripos($childName, $parentName) !== false) {
                $type = trim(preg_replace('/\s+/', ' ', str_ireplace($parentName, '', $childName)));
                if ($type !== '') return $type;
            }
        }

        // Try to parse patterns like "Product - Type" or "Product (Type)"
        if (preg_match('/[-–]\s*(.+)$/u', $childName, $m)) {
            return trim($m[1]);
        }
        if (preg_match('/\(([^)]+)\)/', $childName, $m)) {
            return trim($m[1]);
        }

        // Fallbacks
        if ($category !== '') return $category;
        return $childName !== '' ? $childName : 'Variant';
    }

    /**
     * Searchable type keywords for this product.
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

    /** Convenience: compute an expiry date from a given production date */
    public function computeExpiryFrom(\DateTimeInterface|string|null $productionDate): ?string
    {
        if (!$productionDate || !$this->shelf_life_days) return null;
        $c = \Carbon\Carbon::make($productionDate) ?? \Carbon\Carbon::parse($productionDate);
        return $c->addDays((int) $this->shelf_life_days)->toDateString();
    }

    /* ---------- Production snapshot accessors ----------- */

    public function getLatestBatchNumberAttribute(): ?string
    {
        $snap = $this->getRawOriginal('latest_batch_number');
        if ($snap !== null) return $snap;

        $latest = $this->relationLoaded('latestProduction')
            ? $this->latestProduction
            : $this->latestProduction()->first();

        return $latest?->batch_number;
    }

    public function getLatestProductionDateAttribute(): ?string
    {
        $snap = $this->getRawOriginal('latest_production_date');
        if ($snap !== null) return $snap;

        $latest = $this->relationLoaded('latestProduction')
            ? $this->latestProduction
            : $this->latestProduction()->first();

        return optional($latest?->production_date)?->toDateString();
    }

    public function getLatestExpirationDateAttribute(): ?string
    {
        $snap = $this->getRawOriginal('latest_expiration_date');
        if ($snap !== null) return $snap;

        $latest = $this->relationLoaded('latestProduction')
            ? $this->latestProduction
            : $this->latestProduction()->first();

        return optional($latest?->expiration_date)?->toDateString();
    }

    public function getLatestUnitPricePackAttribute(): ?float
    {
        $snap = $this->getRawOriginal('latest_unit_price_pack');
        if ($snap !== null) return (float) $snap;

        $latest = $this->relationLoaded('latestProduction')
            ? $this->latestProduction
            : $this->latestProduction()->first();

        return $latest?->unit_price_pack !== null ? (float) $latest->unit_price_pack : null;
    }

    public function getLatestUnitPriceBagAttribute(): ?float
    {
        $snap = $this->getRawOriginal('latest_unit_price_bag');
        if ($snap !== null) return (float) $snap;

        $latest = $this->relationLoaded('latestProduction')
            ? $this->latestProduction
            : $this->latestProduction()->first();

        return $latest?->unit_price_bag !== null ? (float) $latest->unit_price_bag : null;
    }

    public function getLatestAvailablePackAttribute(): ?int
    {
        $snap = $this->getRawOriginal('latest_available_pack');
        if ($snap !== null) return (int) $snap;

        $latest = $this->relationLoaded('latestProduction')
            ? $this->latestProduction
            : $this->latestProduction()->first();

        return $latest?->available_pack !== null ? (int) $latest->available_pack : null;
    }

    public function getLatestAvailableBagAttribute(): ?int
    {
        $snap = $this->getRawOriginal('latest_available_bag');
        if ($snap !== null) return (int) $snap;

        $latest = $this->relationLoaded('latestProduction')
            ? $this->latestProduction
            : $this->latestProduction()->first();

        return $latest?->available_bag !== null ? (int) $latest->available_bag : null;
    }

    public function getTotalAvailablePackAttribute(): int
    {
        $snap = $this->getRawOriginal('total_available_pack');
        if ($snap !== null) return (int) $snap;

        return (int) $this->productions()->sum('available_pack');
    }

    public function getTotalAvailableBagAttribute(): int
    {
        $snap = $this->getRawOriginal('total_available_bag');
        if ($snap !== null) return (int) $snap;

        return (int) $this->productions()->sum('available_bag');
    }

    /* ----------------------------------------------------------------------
     | Upload helpers (1200/800/400 WebP pipeline)
     * ---------------------------------------------------------------------*/
    public function setImageFromUpload(UploadedFile $file): void
    {
        $disk = 'public';

        try {
            $productId = $this->id ?: 'tmp';
            $uuid = (string) Str::uuid();
            $base = "products/{$productId}/{$uuid}";

            // read + auto-orient
            $img = Image::read($file->getRealPath())->orientate();

            // cap master to 1600 for performance
            $master = (clone $img)->scaleDown(1600, 1600);

            $w1200 = (clone $master)->scaleDown(1200, 1200);
            $w800  = (clone $master)->scaleDown(800, 800);
            $w400  = (clone $master)->scaleDown(400, 400);

            $path1200 = "{$base}-1200.webp";
            $path800  = "{$base}-800.webp";
            $path400  = "{$base}-400.webp";

            Storage::disk($disk)->put($path1200, (string) $w1200->toWebp(80), 'public');
            Storage::disk($disk)->put($path800,  (string) $w800->toWebp(80),  'public');
            Storage::disk($disk)->put($path400,  (string) $w400->toWebp(80),  'public');

            $url1200 = Storage::disk($disk)->url($path1200);
            $url800  = Storage::disk($disk)->url($path800);
            $url400  = Storage::disk($disk)->url($path400);

            $srcset = "{$url400} 400w, {$url800} 800w, {$url1200} 1200w";

            // Persist paths + primary URLs
            $this->image_disk        = $disk;
            $this->image_path        = $path1200;
            $this->image_medium_path = $path800;
            $this->image_thumb_path  = $path400;

            $this->image_url          = $url1200; // large reference
            $this->card_image_url     = $url800;  // mid-size for cards
            $this->card_image_srcset  = $srcset;
            $this->image_original_url = $url1200;

        } catch (\Throwable $e) {
            Log::warning('Product::setImageFromUpload failed', [
                'product_id' => $this->id,
                'error'      => $e->getMessage(),
            ]);

            // Fallback to simple single-file storage to avoid hard failure
            $path = $file->store('products', $disk);
            $url  = Storage::disk($disk)->url($path);

            $this->image_disk        = $disk;
            $this->image_path        = $path;
            $this->image_url         = $url;
            $this->card_image_url    = $url;
            $this->card_image_srcset = null;
        }
    }

    /**
     * Replace image_path and delete the previous file.
     * (Leave medium/thumb cleanup to future if you want.)
     */
    public function replaceImagePath(?string $newPath): void
    {
        $old = $this->getOriginal('image_path');
        $this->image_path = $newPath;

        if ($old && $old !== $newPath && Storage::disk('public')->exists($old)) {
            Storage::disk('public')->delete($old);
        }
    }

    /* ----------------------------------------------------------------------
     | Model events
     * ---------------------------------------------------------------------*/
    protected static function booted()
    {
        static::saving(function (self $m) {
            $has = fn (string $c) => Schema::hasColumn($m->getTable(), $c);

            if ($has('quantity') && $m->quantity === null)                   $m->quantity = 0;
            if ($has('forecasted_demand') && $m->forecasted_demand === null) $m->forecasted_demand = 0;
            if ($has('stock_status') && empty($m->stock_status))             $m->stock_status = 'in_stock';
        });

        static::updating(function (self $model) {
            if (Schema::hasColumn($model->getTable(), 'image_path') && $model->isDirty('image_path')) {
                $old = $model->getOriginal('image_path');
                if ($old && Storage::disk('public')->exists($old)) {
                    Storage::disk('public')->delete($old);
                }
            }
        });

        static::forceDeleted(function (self $model) {
            if (
                Schema::hasColumn($model->getTable(), 'image_path') &&
                $model->image_path &&
                Storage::disk('public')->exists($model->image_path)
            ) {
                Storage::disk('public')->delete($model->image_path);
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
        if (!$term) return $q;

        $s = trim($term);

        return $q->where(function ($qq) use ($s) {
            // Base: product_name always
            $qq->where('product_name', 'like', "%{$s}%");

            // Optional: product_code only if column exists
            if (self::has('product_code')) {
                $qq->orWhere('product_code', 'like', "%{$s}%");
            }

            // Optional: category if present
            if (self::has('category')) {
                $qq->orWhere('category', 'like', "%{$s}%");
            }

            // Optional legacy "name" column
            if (self::has('name')) {
                $qq->orWhere('name', 'like', "%{$s}%");
            }
        });
    }

    public function scopeCategory($q, ?string $category)
    {
        if (!$category) return $q;
        return $q->where('category', $category);
    }

    /** If 'status' exists, use it; else fall back to 'stock_status'. */
    public function scopeStatus($q, $status)
    {
        if (empty($status)) return $q;

        $col = self::has('status') ? 'status' : (self::has('stock_status') ? 'stock_status' : null);
        if (!$col) return $q;

        return is_array($status)
            ? $q->whereIn($col, $status)
            : $q->where($col, $status);
    }

    public function scopeSorted($q, ?string $sort)
    {
        $map = [
            'name_asc'     => ['product_name', 'asc'],
            'name_desc'    => ['product_name', 'desc'],
            'category'     => ['category', 'asc'],
            'stock_desc'   => ['quantity', 'desc'],
            'stock_asc'    => ['quantity', 'asc'],
            'cost_desc'    => ['unit_cost', 'desc'],
            'cost_asc'     => ['unit_cost', 'asc'],
            'updated_desc' => ['updated_at', 'desc'],
            'recent'       => ['updated_at', 'desc'],
        ];

        if (!$sort || !isset($map[$sort])) {
            return $q->orderBy('product_name', 'asc');
        }

        [$col, $dir] = $map[$sort];
        return $q->orderBy($col, $dir);
    }

    /** Only base/parent products (no parent) */
    public function scopeRoots($q) { return $q->whereNull('parent_id'); }

    /** Only variants of a specific parent */
    public function scopeVariantsOf($q, int $parentId) { return $q->where('parent_id', $parentId); }

    /**
     * PRODUCTION SNAPSHOT (zero N+1):
     */
    public function scopeWithLatestProductionSnapshot($q)
    {
        $q->addSelect([
            // Batch number
            'latest_batch_number' => Production::select('batch_number')
                ->whereColumn('productions.product_id', 'products.id')
                ->orderByDesc('production_date')->orderByDesc('id')->limit(1),

            // Dates
            'latest_production_date' => Production::select('production_date')
                ->whereColumn('productions.product_id', 'products.id')
                ->orderByDesc('production_date')->orderByDesc('id')->limit(1),
            'latest_expiration_date' => Production::select('expiration_date')
                ->whereColumn('productions.product_id', 'products.id')
                ->orderByDesc('production_date')->orderByDesc('id')->limit(1),

            // Prices
            'latest_unit_price_pack' => Production::select('unit_price_pack')
                ->whereColumn('productions.product_id', 'products.id')
                ->orderByDesc('production_date')->orderByDesc('id')->limit(1),
            'latest_unit_price_bag' => Production::select('unit_price_bag')
                ->whereColumn('productions.product_id', 'products.id')
                ->orderByDesc('production_date')->orderByDesc('id')->limit(1),

            // Availability from latest batch
            'latest_available_pack' => Production::select('available_pack')
                ->whereColumn('productions.product_id', 'products.id')
                ->orderByDesc('production_date')->orderByDesc('id')->limit(1),
            'latest_available_bag' => Production::select('available_bag')
                ->whereColumn('productions.product_id', 'products.id')
                ->orderByDesc('production_date')->orderByDesc('id')->limit(1),

            // Rollups
            'total_available_pack' => Production::selectRaw('COALESCE(SUM(available_pack),0)')
                ->whereColumn('productions.product_id', 'products.id'),
            'total_available_bag'  => Production::selectRaw('COALESCE(SUM(available_bag),0)')
                ->whereColumn('productions.product_id', 'products.id'),
        ]);

        return $q;
    }

    /* ----------------------------------------------------------------------
     | Convenience
     * ---------------------------------------------------------------------*/
    public function totalSold(): float      { return $this->sold_qty_kg; }
    public function remainingStock(): float { return $this->available_stock_kg; }
}
