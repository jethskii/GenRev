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
        // new schema
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
        // legacy (kept for backward compatibility)
        'invoice_number',
        'product',
        'date',
        'quantity',
        'price',
        'total',
    ];

    protected $casts = [
        'order_date'   => 'date',
        'quantity_kg'  => 'decimal:3',
        'unit_price'   => 'decimal:2',
        'total_price'  => 'decimal:2',
        // legacy
        'date'         => 'date',
        'quantity'     => 'decimal:3',
        'price'        => 'decimal:2',
        'total'        => 'decimal:2',
    ];

    /* ---------------- Relationships ---------------- */

    // Prevent clash with legacy "product" column
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

    /** Quantity in kg regardless of schema */
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

    /* ---------------- Inventory Side-Effects ---------------- */

    protected static function booted()
    {
        // Before create: auto compute total if not set
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
