<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Casts\Attribute;

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

    /** Columns expected from controller + your schema */
    protected $fillable = [
        // Identity
        'product_code',
        'product_name',
        'category',

        // Pricing / costs
        'unit_cost',
        'price',            // prefer this if it exists
        'default_price',    // controller may pass this
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

        // Status / unit (controller uses these)
        'status',           // enum: active, inactive, pending, on_sale
        'unit',             // e.g. kg, pcs, lt

        // Media
        'image_disk',
        'image_path',
        'image_medium_path',
        'image_thumb_path',
        'image_url',

        // Legacy/optional
        'production_date',
        'stock_status',     // enum('in_stock','out_of_stock','low_stock') if present
    ];

    protected $casts = [
        'id'                  => 'integer',
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

    protected $appends = [
        'effective_unit_cost',
        'gross_margin_pct',
        'produced_qty_kg',
        'sold_qty_kg',
        'available_stock_kg',
        'image_url',         // accessor respects existing DB value
        'unit_material_cost',
        'display_name',
    ];

    /* ----------------------------------------------------------------------
     | Relationships
     * ---------------------------------------------------------------------*/
    public function productions() { return $this->hasMany(Production::class); }
    public function sales()       { return $this->hasMany(Sale::class); }
    public function recipes()     { return $this->hasMany(ProductRecipe::class)->with('material'); }

    /* ----------------------------------------------------------------------
     | Accessors / Mutators
     * ---------------------------------------------------------------------*/

    protected function displayName(): Attribute
    {
        return Attribute::get(function () {
            $raw = $this->getAttributes();
            return $raw['name'] ?? $this->product_name;
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

    /** Public URL for images; prefer DB `image_url` if present */
    public function getImageUrlAttribute(): string
    {
        $raw = $this->getAttributes();
        if (!empty($raw['image_url'])) {
            return (string)$raw['image_url'];
        }

        $path = $this->image_path ?: null;
        if ($path) {
            $disk = $this->image_disk ?: config('filesystems.default', 'public');
            try {
                return Storage::disk($disk)->url(ltrim($path, '/'));
            } catch (\Throwable $e) {
                return asset('storage/' . ltrim($path, '/'));
            }
        }
        return asset('images/default-product.png');
    }

    /** BOM material cost per ONE unit (sum of qty * snapshot unit price) */
    public function getUnitMaterialCostAttribute(): float
    {
        $rows = $this->relationLoaded('recipes') ? $this->recipes : $this->recipes()->get();
        $sum  = $rows->sum(fn ($r) => (float)($r->qty ?? 0) * (float)($r->unit_price_snapshot ?? 0));
        return round((float)$sum, 2);
    }

    /** Effective unit cost: prefer declared unit_cost; else fallback to BOM cost */
    public function getEffectiveUnitCostAttribute(): float
    {
        $declared = $this->unit_cost !== null ? (float)$this->unit_cost : null;
        return $declared !== null ? $declared : (float)$this->unit_material_cost;
    }

    /** Preferred selling price for UI/Quick Sale */
    public function getPriceAttribute(): float
    {
        $raw = $this->getAttributes();
        $p = $raw['price'] ?? $this->attributes['price'] ?? null;      // real column if present
        $p = $p ?? ($this->attributes['default_price'] ?? null);        // controller fallback
        $p = $p ?? ($this->attributes['selling_price'] ?? null);        // legacy (optional)
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
        return $available > 0 ? (float)$available : 0.0;
    }

    /** Convenience: compute an expiry date from a given production date */
    public function computeExpiryFrom(\DateTimeInterface|string|null $productionDate): ?string
    {
        if (!$productionDate || !$this->shelf_life_days) return null;
        $c = \Carbon\Carbon::make($productionDate) ?? \Carbon\Carbon::parse($productionDate);
        return $c->addDays((int)$this->shelf_life_days)->toDateString();
    }

    /* ----------------------------------------------------------------------
     | Upload helpers
     * ---------------------------------------------------------------------*/
    public function setImageFromUpload(UploadedFile $file): void
    {
        $path = $file->store('products', 'public');
        $this->replaceImagePath($path);
    }

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
     | Small helper for scopes (column exists?)
     * ---------------------------------------------------------------------*/
    protected static function has(string $column): bool
    {
        return Schema::hasColumn((new static)->getTable(), $column);
    }

    /* ----------------------------------------------------------------------
     | Query scopes (match controller chain)
     * ---------------------------------------------------------------------*/
    public function scopeSearch($q, ?string $term)
    {
        if (!$term) return $q;
        $s = trim($term);
        return $q->where(function ($qq) use ($s) {
            $qq->where('product_name', 'like', "%{$s}%")
               ->orWhere('product_code', 'like', "%{$s}%")
               ->orWhere('category', 'like', "%{$s}%")
               ->orWhere('name', 'like', "%{$s}%"); // optional 'name' column
        });
    }

    public function scopeCategory($q, ?string $category)
    {
        if (!$category) return $q;
        return $q->where('category', $category);
    }

    /**
     * Controller calls ->status(...).
     * If 'status' column exists, use it; else fall back to 'stock_status'.
     * Accepts string or array.
     */
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

    /* ----------------------------------------------------------------------
     | Convenience
     * ---------------------------------------------------------------------*/
    public function totalSold(): float      { return $this->sold_qty_kg; }
    public function remainingStock(): float { return $this->available_stock_kg; }
}
