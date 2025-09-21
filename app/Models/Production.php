<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\App;

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
        'image_path',
    ];

    /**
     * Casts:
     * - Laravel stores DECIMAL as strings; we keep them as decimals for precision
     *   and convert to float only in computed accessors when needed.
     */
    protected $casts = [
        'production_date'   => 'date',
        'expiration_date'   => 'date',
        'forecasted_demand' => 'decimal:3',
        'current_inventory' => 'decimal:3',
        'unit_cost'         => 'decimal:2',
        'quantity'          => 'decimal:3',
        'deleted_at'        => 'datetime',
    ];

    protected $appends = [
        'remaining_qty',
        'is_expired',
        'days_to_expiry',
        'image_url',
    ];

    /* ========================== Relationships ========================== */

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    /* ============================== Scopes ============================== */

    public function scopeByProduct($q, int $productId)
    {
        return $q->where('product_id', $productId);
    }

    public function scopeFreshFirst($q)
    {
        return $q->orderByDesc('production_date')->orderByDesc('id');
    }

    public function scopeLowStock($q, float $threshold = 5.0)
    {
        return $q->where('current_inventory', '<=', $threshold);
    }

    /* ============================ Accessors ============================ */

    public function getRemainingQtyAttribute(): float
    {
        return (float) ($this->current_inventory ?? 0);
    }

    public function getIsExpiredAttribute(): bool
    {
        if (!$this->expiration_date) return false;
        return Carbon::today()->greaterThan(
            Carbon::parse($this->expiration_date)->startOfDay()
        );
    }

    public function getDaysToExpiryAttribute(): ?int
    {
        if (!$this->expiration_date) return null;

        return Carbon::today()->startOfDay()->diffInDays(
            Carbon::parse($this->expiration_date)->startOfDay(),
            false // negative if past
        );
    }

    public function getImageUrlAttribute(): string
    {
        if (!empty($this->image_path)) {
            // Expecting images stored via storage:link
            return asset('storage/' . ltrim($this->image_path, '/'));
        }
        return $this->product?->image_url ?? asset('images/default-product.png');
    }

    /* ============================ Mutators ============================= */

    public function setBatchNumberAttribute($value): void
    {
        if (is_null($value)) {
            $this->attributes['batch_number'] = null;
            return;
        }
        $norm = preg_replace('/\s+/', ' ', trim((string) $value));
        $this->attributes['batch_number'] = mb_strtoupper($norm);
    }

    /* =========================== Model Events ========================== */

    protected static function booted(): void
    {
        // Normalize/sanitize before save
        static::saving(function (self $m) {
            // Clamp negatives to 0
            if ($m->current_inventory !== null && (float) $m->current_inventory < 0) {
                $m->current_inventory = 0.0;
            }
            if ($m->quantity !== null && (float) $m->quantity < 0) {
                $m->quantity = 0.0;
            }
            if ($m->unit_cost !== null && (float) $m->unit_cost < 0) {
                $m->unit_cost = 0.00;
            }

            // On create, if current_inventory is missing, default to quantity
            if ($m->exists === false && ($m->current_inventory === null || $m->current_inventory === '')) {
                $m->current_inventory = (float) ($m->quantity ?? 0.0);
            }

            // Auto-calc expiration if missing but we know production_date and have shelf life
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

        // After any change, recompute product balances
        $recompute = function (self $m) {
            if (App::bound(\App\Services\InventoryService::class)) {
                App::make(\App\Services\InventoryService::class)
                    ->recomputeProductBalance((int) $m->product_id);
            } else {
                $m->recomputeProductBalanceInternal((int) $m->product_id);
            }
        };

        static::saved($recompute);
        static::deleted($recompute);
        static::restored($recompute);
    }

    /**
     * Local fallback recompute if no service is bound.
     */
    protected function recomputeProductBalanceInternal(int $productId): void
    {
        $produced = (float) static::where('product_id', $productId)->sum('quantity');
        $sold     = (float) Sale::where('product_id', $productId)->sum('quantity');
        $balance  = max(0.0, $produced - $sold);
        $latestProdDate = static::where('product_id', $productId)->max('production_date');

        Product::whereKey($productId)->update([
            'quantity'        => $balance,
            'stock_status'    => $balance > 0 ? 'in_stock' : 'out_of_stock',
            'production_date' => $latestProdDate,
        ]);
    }
}
