<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'product_name',
        'category',
        'production_date',
        'quantity',
        'stock_status',
        'unit_cost',
        'selling_price',
        'forecasted_demand',
        'image',              // storage path
        'shelf_life_days',
        'default_price',
    ];

    protected $dates = ['deleted_at'];

    protected $casts = [
        'production_date'   => 'date',
        'quantity'          => 'decimal:3',
        'unit_cost'         => 'decimal:2',
        'selling_price'     => 'decimal:2',
        'default_price'     => 'decimal:2',
        'forecasted_demand' => 'decimal:3',
        'shelf_life_days'   => 'integer',
    ];

    // expose computed fields (add unit_material_cost)
    protected $appends = [
        'price',
        'produced_qty_kg',
        'sold_qty_kg',
        'available_stock_kg',
        'image_url',
        'unit_material_cost',
    ];

    /* ---------------- Relationships ---------------- */
    public function productions(){ return $this->hasMany(Production::class); }
    public function sales(){ return $this->hasMany(Sale::class); }

    // recipe/BOM rows
    public function recipes()
    {
        return $this->hasMany(ProductRecipe::class);
    }

    /* ---------------- Accessors ---------------- */

    // URL for <img>
    public function getImageUrlAttribute(): string
    {
        if (!empty($this->image)) {
            return asset('storage/' . ltrim($this->image, '/'));
        }
        return asset('images/default-product.png');
    }

    public function getPriceAttribute(): float
    {
        return (float) ($this->selling_price ?? $this->unit_cost ?? 0);
    }

    // Sum of qty * unit_price from the recipe (cost to make ONE unit)
    public function getUnitMaterialCostAttribute(): float
    {
        $rows = $this->relationLoaded('recipes') ? $this->recipes : $this->recipes()->get();
        $sum  = $rows->sum(fn ($r) => (float)$r->qty * (float)$r->unit_price_snapshot);
        return round($sum, 2);
    }

    public function getProducedQtyKgAttribute(): float
    {
        return (float) ($this->productions()->sum('quantity') ?? 0);
    }

    public function getSoldQtyKgAttribute(): float
    {
        return (float) ($this->sales()->sum('quantity') ?? 0);
    }

    public function getAvailableStockKgAttribute(): float
    {
        $available = $this->produced_qty_kg - $this->sold_qty_kg;
        return $available > 0 ? (float) $available : 0.0;
    }

    /* ---------------- Upload helpers ---------------- */

    public function setImageFromUpload(UploadedFile $file): void
    {
        $path = $file->store('products', 'public');
        $this->replaceImagePath($path);
    }

    public function replaceImagePath(?string $newPath): void
    {
        $old = $this->getOriginal('image');
        $this->image = $newPath;

        if ($old && $old !== $newPath && Storage::disk('public')->exists($old)) {
            Storage::disk('public')->delete($old);
        }
    }

    /* ---------------- Model events ---------------- */

    protected static function booted()
    {
        static::updating(function (self $model) {
            if ($model->isDirty('image')) {
                $old = $model->getOriginal('image');
                if ($old && Storage::disk('public')->exists($old)) {
                    Storage::disk('public')->delete($old);
                }
            }
        });

        static::forceDeleted(function (self $model) {
            if ($model->image && Storage::disk('public')->exists($model->image)) {
                Storage::disk('public')->delete($model->image);
            }
        });
    }

    /* ---------------- Convenience ---------------- */
    public function totalSold(): float      { return $this->sold_qty_kg; }
    public function remainingStock(): float { return $this->available_stock_kg; }
}
