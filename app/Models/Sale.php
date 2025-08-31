<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\App;
use App\Services\InventoryService;

class Sale extends Model
{
    use SoftDeletes;

    protected $table = 'sales';

    protected $fillable = [
        // New schema
        'product_id',
        'production_id',
        'order_number',
        'order_date',
        'quantity_kg',
        'unit_price',
        'total_price',
        'status',
        'customer_name',
        'notes',

        // Optional timeline fields used by controllers (if columns exist)
        'production_date',
        'expiration_date',

        // Legacy (kept for backward compatibility)
        'invoice_number',
        'product',
        'date',
        'quantity',
        'price',
        'total',
    ];

    protected $casts = [
        // New
        'order_date'     => 'date',
        'quantity_kg'    => 'decimal:3',
        'unit_price'     => 'decimal:2',
        'total_price'    => 'decimal:2',

        // Optional columns
        'production_date'=> 'date',
        'expiration_date'=> 'date',

        // Legacy
        'date'           => 'date',
        'quantity'       => 'decimal:3',
        'price'          => 'decimal:2',
        'total'          => 'decimal:2',
    ];

    /* ---------------- Relationships ---------------- */

    // Avoid name clash with legacy string column "product"
    public function productRef()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function production()
    {
        return $this->belongsTo(Production::class);
    }

    public function allocations()
    {
        return $this->hasMany(BatchAllocation::class);
    }

    /* ---------------- Unified Accessors ---------------- */

    /** Quantity (kg) regardless of schema */
    public function qtyKg(): float
    {
        return (float) ($this->quantity_kg ?? $this->quantity ?? 0);
    }

    /** Unit price regardless of schema */
    public function unitPriceValue(): float
    {
        return (float) ($this->unit_price ?? $this->price ?? 0);
    }

    /** Total value (computed if not stored) */
    public function totalValue(): float
    {
        if (!is_null($this->total_price ?? null)) {
            return (float) $this->total_price;
        }
        if (!is_null($this->total ?? null)) {
            return (float) $this->total;
        }
        return round($this->qtyKg() * $this->unitPriceValue(), 2);
    }

    /** Display product: prefer legacy string, fallback to relation */
    public function getDisplayProductAttribute(): string
    {
        if (!empty($this->product)) {
            return (string) $this->product;
        }
        return optional($this->productRef)->product_name ?? '';
    }

    /** Unified sale date: prefer new order_date, then legacy date */
    public function getSaleDateAttribute(): ?\Illuminate\Support\Carbon
    {
        return $this->order_date ?? $this->date ?? null;
    }

    /* ---------------- Mutators (normalize totals) ---------------- */

    public function setQuantityKgAttribute($value): void
    {
        $this->attributes['quantity_kg'] = is_null($value) ? null : (float) $value;
        // Auto-sync total_price if using new schema
        if (array_key_exists('unit_price', $this->attributes) && !is_null($this->attributes['unit_price'])) {
            $this->attributes['total_price'] = round(($this->attributes['quantity_kg'] ?? 0) * ($this->attributes['unit_price'] ?? 0), 2);
        }
    }
    public function audits()
    {
    return $this->hasMany(\App\Models\SaleAudit::class);
    }

    public function setUnitPriceAttribute($value): void
    {
        $this->attributes['unit_price'] = is_null($value) ? null : (float) $value;
        if (array_key_exists('quantity_kg', $this->attributes) && !is_null($this->attributes['quantity_kg'])) {
            $this->attributes['total_price'] = round(($this->attributes['quantity_kg'] ?? 0) * ($this->attributes['unit_price'] ?? 0), 2);
        }
    }

    public function setQuantityAttribute($value): void
    {
        $this->attributes['quantity'] = is_null($value) ? null : (float) $value;
        if (array_key_exists('price', $this->attributes) && !is_null($this->attributes['price'])) {
            $this->attributes['total'] = round(($this->attributes['quantity'] ?? 0) * ($this->attributes['price'] ?? 0), 2);
        }
    }

    public function setPriceAttribute($value): void
    {
        $this->attributes['price'] = is_null($value) ? null : (float) $value;
        if (array_key_exists('quantity', $this->attributes) && !is_null($this->attributes['quantity'])) {
            $this->attributes['total'] = round(($this->attributes['quantity'] ?? 0) * ($this->attributes['price'] ?? 0), 2);
        }
    }

    /* ---------------- Inventory Side-Effects ---------------- */

    protected static function booted()
    {
        // Before create: ensure a total exists (new or legacy)
        static::creating(function (self $m) {
            if (is_null($m->total_price) && is_null($m->total)) {
                $m->total_price = round(
                    ($m->quantity_kg ?? $m->quantity ?? 0) * ($m->unit_price ?? $m->price ?? 0),
                    2
                );
            }
        });

        // After created → apply sale impact
        static::created(function (self $m) {
            App::make(InventoryService::class)->applySale($m);
        });

        // On update: revert old sale impact first if core fields changed
        static::updating(function (self $m) {
            $dirty = array_intersect(
                array_keys($m->getDirty()),
                ['product_id','production_id','quantity_kg','quantity']
            );
            if (!empty($dirty)) {
                $orig = (new self())->forceFill($m->getOriginal());
                App::make(InventoryService::class)->undoSale($orig);
            }
        });

        static::updated(function (self $m) {
            if ($m->wasChanged(['product_id','production_id','quantity_kg','quantity'])) {
                App::make(InventoryService::class)->applySale($m);
            }
        });

        // Soft delete: return inventory
        static::deleted(function (self $m) {
            App::make(InventoryService::class)->undoSale($m);
        });

        // Restore: deduct again
        static::restored(function (self $m) {
            App::make(InventoryService::class)->applySale($m);
        });

        // Always keep cached product balance in sync
        static::saved(function (self $m) {
            if ($m->product_id) {
                App::make(InventoryService::class)->recomputeProductBalance((int) $m->product_id);
            }
        });
    }
}
