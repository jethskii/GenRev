<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Product extends Model
{
    use SoftDeletes;

    protected $table = 'products';

    /** Mass assignable columns (must match real DB columns) */
    protected $fillable = [
        // Identity
        'product_code',
        'product_name',
        'category',
        'status',          // active|inactive|pending|on_sale
        'unit',            // kg|pcs|lt

        // Pricing / costs
        'unit_cost',       // standard/overhead cost (NOT BOM)
        'default_price',
        'last_cost_date',

        // Inventory / demand
        'quantity',
        'forecasted_demand',

        // Shelf life / quality
        'shelf_life_days',
        'temp_requirements',
        'storage_zone',    // chiller|freezer|ambient

        // Ops / scheduling
        'yield_rate',
        'standard_batch_size',
        'lead_time_days',
        'min_run_qty',
        'max_run_qty',
        'line_constraints',   // JSON

        // Media (real column)
        'image_path',

        // Legacy/optional
        'production_date',
        'selling_price',
        'stock_status',
    ];

    /** Casts (dates, decimals, arrays) */
    protected $casts = [
        'deleted_at'          => 'datetime',
        'last_cost_date'      => 'date',
        'production_date'     => 'date',

        'quantity'            => 'decimal:3',
        'unit_cost'           => 'decimal:2',
        'selling_price'       => 'decimal:2',
        'default_price'       => 'decimal:2',
        'forecasted_demand'   => 'decimal:3',

        'shelf_life_days'     => 'integer',
        'yield_rate'          => 'decimal:2',
        'standard_batch_size' => 'decimal:3',
        'lead_time_days'      => 'integer',
        'min_run_qty'         => 'decimal:3',
        'max_run_qty'         => 'decimal:3',

        'line_constraints'    => 'array',  // stored as JSON
    ];

    /** Computed attributes auto-appended to arrays/JSON */
    protected $appends = [
        'price',                 // preferred selling price
        'effective_unit_cost',   // unit_cost or BOM cost fallback
        'gross_margin_pct',      // based on price vs effective cost
        'produced_qty_kg',
        'sold_qty_kg',
        'available_stock_kg',
        'image_url',
        'unit_material_cost',    // BOM cost per 1 unit
    ];

    /* ----------------------------------------------------------------------
     | Relationships
     * ---------------------------------------------------------------------*/
    public function productions() { return $this->hasMany(Production::class); }
    public function sales()       { return $this->hasMany(Sale::class); }
    public function recipes()
{
    return $this->hasMany(ProductRecipe::class)->with('material');
}

    /* ----------------------------------------------------------------------
     | Accessors / Mutators (null-safe, view-friendly)
     * ---------------------------------------------------------------------*/

    /** Backward-compat: some blades referenced $product->name */
    protected function name(): Attribute
    {
        return Attribute::get(fn () => $this->product_name);
    }

    /** Ensure unit always returns a sensible default */
    protected function unit(): Attribute
    {
        return Attribute::get(fn ($value) => $value ?: 'kg');
    }

    /** Normalize line_constraints as JSON (accept array or JSON string) */
    protected function lineConstraints(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                if (is_array($value)) return $value;
                if (is_string($value) && $value !== '') {
                    $decoded = json_decode($value, true);
                    return is_array($decoded) ? $decoded : $value; // keep as string if bad JSON; validator should catch
                }
                return $value;
            }
        );
    }

    /**
     * LEGACY BRIDGE:
     * Some old code may still read/write $product->image (nonexistent column).
     * These accessors/mutators transparently map it to image_path.
     */
    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image_path,                      // reading "image" returns image_path
            set: fn ($value) => ['image_path' => $value],         // writing "image" stores into image_path
        );
    }

    /** Public URL for <img> tags */
    public function getImageUrlAttribute(): string
    {
        $path = $this->image_path ?: null;
        if ($path) {
            $disk = config('filesystems.default', 'public');
            try {
                return Storage::disk($disk)->url(ltrim($path, '/'));
            } catch (\Throwable $e) {
                // Fallback if the disk isn't configured for URLs
                return asset('storage/' . ltrim($path, '/'));
            }
        }
        return asset('images/default-product.png');
    }

    /**
     * BOM material cost per ONE unit (sum of qty * snapshot unit price).
     * Uses loaded relation when available to avoid N+1.
     */
    public function getUnitMaterialCostAttribute(): float
    {
        $rows = $this->relationLoaded('recipes') ? $this->recipes : $this->recipes()->get();
        $sum  = $rows->sum(fn ($r) => (float)($r->qty ?? 0) * (float)($r->unit_price_snapshot ?? 0));
        return round((float)$sum, 2);
    }

    /** Effective unit cost: prefer declared unit_cost; else fallback to BOM cost. */
    public function getEffectiveUnitCostAttribute(): float
    {
        $declared = $this->unit_cost !== null ? (float)$this->unit_cost : null;
        return $declared !== null ? $declared : (float)$this->unit_material_cost;
    }

    /** Preferred selling price for UI/Quick Sale */
    public function getPriceAttribute(): float
    {
        $price = $this->default_price ?? $this->selling_price ?? null;
        return (float) ($price !== null ? $price : $this->effective_unit_cost);
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
        // If your sales table has quantity_kg, consider COALESCE at query time.
        return (float) ($this->sales()->sum('quantity') ?? 0);
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
     | Upload helpers (safe image replacement)
     * ---------------------------------------------------------------------*/
    public function setImageFromUpload(UploadedFile $file): void
    {
        $path = $file->store('products', 'public'); // returns "products/xyz.jpg"
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
     | Model events (cleanup on update/forceDelete)
     * ---------------------------------------------------------------------*/
    protected static function booted()
    {
        static::updating(function (self $model) {
            if ($model->isDirty('image_path')) {
                $old = $model->getOriginal('image_path');
                if ($old && Storage::disk('public')->exists($old)) {
                    Storage::disk('public')->delete($old);
                }
            }
        });

        static::forceDeleted(function (self $model) {
            if ($model->image_path && Storage::disk('public')->exists($model->image_path)) {
                Storage::disk('public')->delete($model->image_path);
            }
        });
    }

    /* ----------------------------------------------------------------------
     | Query scopes (used by index filters/sorts)
     * ---------------------------------------------------------------------*/
    public function scopeSearch($q, ?string $term)
    {
        if (!$term) return $q;
        $s = trim($term);
        return $q->where(function ($qq) use ($s) {
            $qq->where('product_name', 'like', "%{$s}%")
               ->orWhere('product_code', 'like', "%{$s}%");
        });
    }

    public function scopeCategory($q, ?string $category)
    {
        if (!$category) return $q;
        return $q->where('category', $category);
    }

    public function scopeStatus($q, ?string $status)
    {
        if (!$status) return $q;
        return $q->where('status', $status);
    }

    public function scopeSorted($q, ?string $sort)
    {
        $map = [
            'name_asc'     => ['product_name', 'asc'],
            'name_desc'    => ['product_name', 'desc'],
            'stock_desc'   => ['quantity', 'desc'],
            'stock_asc'    => ['quantity', 'asc'],
            'cost_desc'    => ['unit_cost', 'desc'],
            'cost_asc'     => ['unit_cost', 'asc'],
            'updated_desc' => ['updated_at', 'desc'],
        ];
        if (!$sort || !isset($map[$sort])) {
            return $q->latest('updated_at');
        }
        [$col, $dir] = $map[$sort];
        return $q->orderBy($col, $dir);
    }

    /** Handy extras for controllers or queries */
    public function scopeActive($q)      { return $q->where('status', 'active'); }
    public function scopeHasRecipe($q)   { return $q->whereHas('recipes'); }
    public function scopeInCategory($q, ?string $c) { return $c ? $q->where('category', $c) : $q; }

    /* ----------------------------------------------------------------------
     | Convenience
     * ---------------------------------------------------------------------*/
    public function totalSold(): float      { return $this->sold_qty_kg; }
    public function remainingStock(): float { return $this->available_stock_kg; }
}
