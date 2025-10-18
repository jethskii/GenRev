<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use App\Models\Product;
use App\Models\Sale;

class Production extends Model
{
    use SoftDeletes;

    protected $table = 'productions';

    /** Primary key */
    protected $primaryKey = 'id';
    public $incrementing  = true;
    protected $keyType    = 'int';

    /** Mass assignable (mirror table columns) */
    protected $fillable = [
        'product_id',
        'batch_number',
        'forecasted_demand',
        'current_inventory',
        'unit_cost',
        'unit_price_pack',
        'unit_price_bag',
        'production_date',
        'expiration_date',
        'quantity',
        'image_disk',
        'image_path',
        'image_medium_path',
        'image_thumb_path',
    ];

    /** Casts */
    protected $casts = [
        'id'                => 'integer',
        'product_id'        => 'integer',
        'production_date'   => 'date',
        'expiration_date'   => 'date',
        'forecasted_demand' => 'float',
        'unit_cost'         => 'float',
        'unit_price_pack'   => 'float',
        'unit_price_bag'    => 'float',
        'current_inventory' => 'integer',
        'quantity'          => 'integer',
        'deleted_at'        => 'datetime',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
    ];

    /** Virtuals for UI */
    protected $appends = [
        'remaining_qty',
        'is_expired',
        'days_to_expiry',
        'image_url',
        'image_srcset',
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

    public function scopeLowStock($q, int $threshold = 5)
    {
        return $q->where('current_inventory', '<=', $threshold);
    }

    public function scopeExpired($q)
    {
        return $q->whereNotNull('expiration_date')
                 ->where('expiration_date', '<', Carbon::today()->toDateString());
    }

    /* ============================ Accessors ============================ */

    public function getRemainingQtyAttribute(): int
    {
        return (int) ($this->current_inventory ?? 0);
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
        // Prefer production image; fallback to product image; then default
        if (!empty($this->image_path)) {
            $disk = $this->image_disk ?: 'public';
            try {
                return Storage::disk($disk)->url(ltrim($this->image_path, '/'));
            } catch (\Throwable $e) {
                return asset('storage/' . ltrim($this->image_path, '/'));
            }
        }
        return $this->product?->image_url ?? asset('images/default-product.png');
    }

    public function getImageSrcsetAttribute(): ?string
    {
        $disk = $this->image_disk ?: 'public';
        $parts = [];

        $push = function (?string $path, string $size) use (&$parts, $disk) {
            if (!$path) return;
            try {
                $url = Storage::disk($disk)->url(ltrim($path, '/'));
            } catch (\Throwable $e) {
                $url = asset('storage/' . ltrim($path, '/'));
            }
            $parts[] = "{$url} {$size}";
        };

        $push($this->image_thumb_path,  '150w');
        $push($this->image_medium_path, '600w');

        return $parts ? implode(', ', $parts) : null;
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
        // Normalize & ensure NOT NULL numeric fields before save
        static::saving(function (self $m) {
            // Coerce to numbers (avoid null for NOT NULL cols)
            $m->quantity          = is_numeric($m->quantity) ? (int)$m->quantity : 0;
            $m->current_inventory = is_numeric($m->current_inventory) ? (int)$m->current_inventory : null;
            $m->unit_cost         = is_numeric($m->unit_cost) ? (float)$m->unit_cost : 0.0;
            $m->forecasted_demand = is_numeric($m->forecasted_demand) ? (float)$m->forecasted_demand : 0.0;

            // Optional price fields default to 0 if missing/negative
            $m->unit_price_pack = is_numeric($m->unit_price_pack) ? max(0.0, (float)$m->unit_price_pack) : 0.0;
            $m->unit_price_bag  = is_numeric($m->unit_price_bag)  ? max(0.0, (float)$m->unit_price_bag)  : 0.0;

            // Clamp negatives
            if ($m->quantity < 0) $m->quantity = 0;
            if (($m->current_inventory ?? 0) < 0) $m->current_inventory = 0;
            if ($m->unit_cost < 0) $m->unit_cost = 0.0;

            // On create, if current_inventory missing, default to quantity
            if ($m->exists === false && ($m->current_inventory === null || $m->current_inventory === '')) {
                $m->current_inventory = (int) $m->quantity;
            }

            // Default disk
            if (empty($m->image_disk)) {
                $m->image_disk = 'public';
            }

            // Ensure production_date (NOT NULL in DB)
            if (empty($m->production_date)) {
                $m->production_date = Carbon::today();
            }

            // Auto-calc expiration if missing but we know shelf life
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
            if ($m->product_id) {
                if (App::bound(\App\Services\InventoryService::class)) {
                    App::make(\App\Services\InventoryService::class)
                        ->recomputeProductBalance((int) $m->product_id);
                } else {
                    $m->recomputeProductBalanceInternal((int) $m->product_id);
                }
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

        // Sold: prefer new schema quantity_kg, fall back to legacy quantity
        $sold = (float) Sale::where('product_id', $productId)
            ->selectRaw('COALESCE(SUM(quantity_kg),0) + COALESCE(SUM(quantity),0) as s')
            ->value('s');

        $balance  = max(0.0, $produced - $sold);
        $latestProdDate = static::where('product_id', $productId)->max('production_date');

        Product::whereKey($productId)->update([
            'quantity'        => $balance,
            'stock_status'    => $balance > 0 ? 'in_stock' : 'out_of_stock',
            'production_date' => $latestProdDate,
        ]);
    }
}
