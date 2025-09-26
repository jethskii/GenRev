<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use App\Services\InventoryService;

class Sale extends Model
{
    use SoftDeletes;

    protected $table = 'sales';

    /**
     * While debugging you can swap to:
     * protected $guarded = [];
     * but keep $fillable in prod for safety.
     */
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
        'order_date'      => 'date',
        'quantity_kg'     => 'decimal:3',
        'unit_price'      => 'decimal:2',
        'total_price'     => 'decimal:2',

        // Optional
        'production_date' => 'date',
        'expiration_date' => 'date',

        // Legacy
        'date'            => 'date',
        'quantity'        => 'decimal:3',
        'price'           => 'decimal:2',
        'total'           => 'decimal:2',
    ];

    // Make these derived fields visible in JSON (API responses)
    protected $appends = ['display_product', 'sale_date'];

    /* ----------------------------- Relationships ----------------------------- */

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
        // If you have a concrete BatchAllocation model, you can add a "use" at top.
        return $this->hasMany(\App\Models\BatchAllocation::class);
    }

    public function audits()
    {
        return $this->hasMany(\App\Models\SaleAudit::class);
    }

    /* ---------------------------- Unified Accessors --------------------------- */

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
        if (!is_null($this->total_price ?? null)) return (float) $this->total_price;
        if (!is_null($this->total ?? null))       return (float) $this->total;
        return round($this->qtyKg() * $this->unitPriceValue(), 2);
    }

    /** Display product: prefer legacy string, fallback to relation */
    public function getDisplayProductAttribute(): string
    {
        if (!empty($this->product)) return (string) $this->product;
        return optional($this->productRef)->product_name ?? '';
    }

    /** Unified sale date: prefer new order_date, then legacy date */
    public function getSaleDateAttribute(): ?\Illuminate\Support\Carbon
    {
        return $this->order_date ?? $this->date ?? null;
    }

    /* ------------------------------- Mutators -------------------------------- */

    public function setQuantityKgAttribute($value): void
    {
        $this->attributes['quantity_kg'] = is_null($value) ? null : (float) $value;

        // Keep new total in sync if columns exist
        if (array_key_exists('unit_price', $this->attributes) && !is_null($this->attributes['unit_price'])) {
            $computed = round(($this->attributes['quantity_kg'] ?? 0) * ($this->attributes['unit_price'] ?? 0), 2);
            if (Schema::hasColumn('sales', 'total_price')) {
                $this->attributes['total_price'] = $computed;
            }
        }

        // If legacy price is present, keep legacy total in sync too
        if (array_key_exists('price', $this->attributes) && !is_null($this->attributes['price'])) {
            $computedLegacy = round(($this->attributes['quantity_kg'] ?? $this->attributes['quantity'] ?? 0) * ($this->attributes['price'] ?? 0), 2);
            if (Schema::hasColumn('sales', 'total')) {
                $this->attributes['total'] = $computedLegacy;
            }
        }
    }

    public function setUnitPriceAttribute($value): void
    {
        $this->attributes['unit_price'] = is_null($value) ? null : (float) $value;

        if (array_key_exists('quantity_kg', $this->attributes) && !is_null($this->attributes['quantity_kg'])) {
            $computed = round(($this->attributes['quantity_kg'] ?? 0) * ($this->attributes['unit_price'] ?? 0), 2);
            if (Schema::hasColumn('sales', 'total_price')) {
                $this->attributes['total_price'] = $computed;
            }
        }
    }

    public function setQuantityAttribute($value): void
    {
        $this->attributes['quantity'] = is_null($value) ? null : (float) $value;

        if (array_key_exists('price', $this->attributes) && !is_null($this->attributes['price'])) {
            $computed = round(($this->attributes['quantity'] ?? 0) * ($this->attributes['price'] ?? 0), 2);
            if (Schema::hasColumn('sales', 'total')) {
                $this->attributes['total'] = $computed;
            }
        }

        // If only "quantity" was sent but you have quantity_kg column, mirror it
        if (Schema::hasColumn('sales', 'quantity_kg') && !isset($this->attributes['quantity_kg'])) {
            $this->attributes['quantity_kg'] = $this->attributes['quantity'];
        }
    }

    public function setPriceAttribute($value): void
    {
        $this->attributes['price'] = is_null($value) ? null : (float) $value;

        if (array_key_exists('quantity', $this->attributes) && !is_null($this->attributes['quantity'])) {
            $computed = round(($this->attributes['quantity'] ?? 0) * ($this->attributes['price'] ?? 0), 2);
            if (Schema::hasColumn('sales', 'total')) {
                $this->attributes['total'] = $computed;
            }
        }
    }

    /* ------------------------- Inventory Side-Effects ------------------------- */

    protected static function booted()
    {
        // Before create: ensure a total exists (write to whichever columns are present)
        static::creating(function (self $m) {
            $qty  = $m->quantity_kg ?? $m->quantity ?? 0;
            $unit = $m->unit_price  ?? $m->price    ?? 0;
            $computed = round((float)$qty * (float)$unit, 2);

            $hasNewTotal    = Schema::hasColumn('sales', 'total_price');
            $hasLegacyTotal = Schema::hasColumn('sales', 'total');

            if ($hasNewTotal && is_null($m->total_price)) $m->total_price = $computed;
            if ($hasLegacyTotal && is_null($m->total))     $m->total      = $computed;
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
