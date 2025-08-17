<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\App;
use App\Services\InventoryService;
use Carbon\Carbon;

class Production extends Model
{
    use SoftDeletes;

    protected $table = 'productions';

    protected $fillable = [
        'product_id',
        'batch_number',
        'forecasted_demand',
        'current_inventory',
        'unit_cost',
        'production_date',
        'expiration_date',
        'quantity',
    ];

    protected $casts = [
        'production_date'   => 'date',
        'expiration_date'   => 'date',
        'forecasted_demand' => 'decimal:3',
        'current_inventory' => 'decimal:3',
        'unit_cost'         => 'decimal:2',
        'quantity'          => 'decimal:3',
    ];

    /** Expose a few handy computed attributes in JSON */
    protected $appends = [
        'remaining_qty',
        'is_expired',
        'days_to_expiry',
    ];

    /* ----------------
     | Relationships
     * ---------------- */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    /* ----------------
     | Scopes
     * ---------------- */
    public function scopeByProduct($q, int $productId)
    {
        return $q->where('product_id', $productId);
    }

    /** Newest production first (then by id desc) */
    public function scopeFreshFirst($q)
    {
        return $q->orderByDesc('production_date')->orderByDesc('id');
    }

    /** Simple low stock helper (threshold in kg; default 5) */
    public function scopeLowStock($q, float $threshold = 5.0)
    {
        return $q->where('current_inventory', '<=', $threshold);
    }

    /* ----------------
     | Accessors
     * ---------------- */

    /** Remaining qty alias (kept for clarity in views) */
    public function getRemainingQtyAttribute(): float
    {
        // casts 'decimal' return strings; coerce to float for math/JSON
        return (float) ($this->current_inventory ?? 0);
    }

    public function getIsExpiredAttribute(): bool
    {
        if (!$this->expiration_date) return false;
        return Carbon::today()->greaterThan(Carbon::parse($this->expiration_date));
    }

    public function getDaysToExpiryAttribute(): ?int
    {
        if (!$this->expiration_date) return null;
        return Carbon::today()->diffInDays(Carbon::parse($this->expiration_date), false);
    }

    /* ----------------
     | Mutators (normalization)
     * ---------------- */

    public function setBatchNumberAttribute($value): void
    {
        if (is_null($value)) { $this->attributes['batch_number'] = null; return; }
        // Trim, collapse internal spaces, uppercase for consistency
        $norm = preg_replace('/\s+/', ' ', trim((string)$value));
        $this->attributes['batch_number'] = mb_strtoupper($norm);
    }

    /* ----------------
     | Model Events
     * ---------------- */
    protected static function booted()
    {
        // Before create/update: guard values & derive defaults
        static::saving(function (self $m) {
            // Clamp negative inventory/quantity to 0 (avoid accidental negatives)
            if ($m->current_inventory !== null && (float)$m->current_inventory < 0) {
                $m->current_inventory = 0;
            }
            if ($m->quantity !== null && (float)$m->quantity < 0) {
                $m->quantity = 0;
            }

            // Default current_inventory = produced quantity if not provided
            if ($m->exists === false && $m->current_inventory === null) {
                $m->current_inventory = (float) ($m->quantity ?? 0);
            }

            // Default expiration_date from product.shelf_life_days if missing
            if (empty($m->expiration_date) && !empty($m->production_date)) {
                $days = null;

                if ($m->relationLoaded('product') && $m->product) {
                    $days = (int) ($m->product->shelf_life_days ?? 0);
                } elseif (!empty($m->product_id)) {
                    $days = (int) (Product::whereKey($m->product_id)->value('shelf_life_days') ?? 0);
                }

                if ($days > 0) {
                    $m->expiration_date = Carbon::parse($m->production_date)->copy()->addDays($days);
                }
            }
        });

        // After save/delete/restore: keep product running balance in sync
        $recompute = function (self $m) {
            if (App::bound(InventoryService::class)) {
                App::make(InventoryService::class)->recomputeProductBalance((int) $m->product_id);
            }
        };

        static::saved($recompute);
        static::deleted($recompute);
        static::restored($recompute);
    }
}
