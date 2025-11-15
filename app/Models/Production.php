<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
        'parent_product_id',
        'product_id',
        'product_name_snapshot',
        'batch_number',
        'forecasted_demand',
        'current_inventory',
        'unit_price_pack',
        'unit_price_bag',
        'available_pack',
        'available_bag',
        'production_date',
        'expiration_date',
        'quantity',
        // Optional per-batch media (kept for backward-compat)
        'image_disk',
        'image_path',
        'image_medium_path',
        'image_thumb_path',
        'remarks',
        'archived_reason',
        // 'archived_reason',
    ];

    /** Casts */
    protected $casts = [
        'id'                => 'integer',
        'parent_product_id' => 'integer',
        'product_id'        => 'integer',
        'production_date'   => 'date',
        'expiration_date'   => 'date',
        'forecasted_demand' => 'float',
        'unit_price_pack'   => 'float',
        'unit_price_bag'    => 'float',
        'current_inventory' => 'integer',
        'quantity'          => 'integer',
        'available_pack'    => 'integer',
        'available_bag'     => 'integer',
        'remarks'           => 'string',
        'deleted_at'        => 'datetime',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
        'archived_reason'  => 'string',
        // 'archived_reason'  => 'string',
    ];

    /** Virtuals for UI */
    protected $appends = [
        'remaining_qty',
        'is_expired',
        'days_to_expiry',
        'image_url',
        'image_srcset',
        'type_name',
        'type_keywords',
        'purge_at',
        'batch_label',
    ];

    /* ========================== Relationships ========================== */

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function parentProduct()
    {
        return $this->belongsTo(Product::class, 'parent_product_id');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    /* ============================== Scopes ============================== */

    public function scopeArchived($q)
    {
        return $q->onlyTrashed();
    }

    public function scopeVisible($q)
    {
        $table = $this->getTable();
        return $q->whereNull("$table.deleted_at");
    }

    public function scopeNotArchived($q)
    {
        return $this->scopeVisible($q);
    }

    public function scopeByProduct($q, int $productId)
    {
        return $q->where('product_id', $productId);
    }

    public function scopeByParent($q, int $parentId)
    {
        return $q->where('parent_product_id', $parentId);
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

    public function scopeSearchArchived($q, ?string $term)
    {
        $s = trim((string)$term);
        if ($s === '') return $q;

        return $q->where(function($qq) use ($s) {
            $qq->where('batch_number', 'like', "%{$s}%")
               ->orWhere('product_name_snapshot', 'like', "%{$s}%")
               ->orWhere('remarks', 'like', "%{$s}%")
               ->orWhere('archived_reason', 'like', "%{$s}%")
               ->orWhereHas('product', function($qp) use ($s){
                    $qp->where('product_name','like',"%{$s}%");
               })
               ->orWhereHas('parentProduct', function($qp) use ($s){
                    $qp->where('product_name','like',"%{$s}%");
               });
        });
    }

    /**
     * @param string $sort One of: deleted_at|date|product|batch|qty
     */
    public function scopeSortArchived($q, string $sort = 'deleted_at')
    {
        switch ($sort) {
            case 'date':
                return $q->orderByDesc('production_date')->orderByDesc('id');

            case 'product':
                return $q->leftJoin('products','products.id','=','productions.product_id')
                         ->orderBy('products.product_name')
                         ->orderByDesc('productions.id')
                         ->select('productions.*');

            case 'batch':
                // Numeric-friendly order: puts 2 before 10
                return $q->orderBy(DB::raw('CAST(batch_number AS UNSIGNED)'))->orderBy('id');

            case 'qty':
                return $q->orderByDesc('quantity')->orderByDesc('id');

            case 'deleted_at':
            default:
                return $q->orderByDesc('deleted_at')->orderByDesc('id');
        }
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

    /**
     * Purge-at timestamp used by the Archived UI.
     */
    public function getPurgeAtAttribute(): ?string
    {
        if (array_key_exists('purge_at', $this->attributes) && !empty($this->attributes['purge_at'])) {
            try {
                return Carbon::parse($this->attributes['purge_at'])->toDateTimeString();
            } catch (\Throwable $e) { /* ignore parse fail */ }
        }

        if (!$this->deleted_at) return null;

        $ttl = (int) config('app.archive_ttl_days', 30);
        return Carbon::parse($this->deleted_at)
                    ->copy()
                    ->addDays(max(1, $ttl))
                    ->toDateTimeString();
    }
 /* Archived Reason label for UI */
public function getArchivedReasonLabelAttrubute(): string
{
    return match ($this->archived_reason) {
        'from_sale' => 'From Sales',
        'manual'    => 'Manual',
        'expired'   => 'Expired',
        default     => 'Unknown',
    };
}

    /* * Prefer product’s processed card image; then batch image; else default.*/
    public function getImageUrlAttribute(): string
    {
        // Prefer product-derived fields created by the controller’s image pipeline.
        if ($this->product) {
            $primary = $this->product->card_image_url ?: $this->product->image_url;
            if (!empty($primary)) {
                return (string) $primary;
            }
        }

        // Fallback to batch-level stored paths (legacy support).
        if (!empty($this->image_path)) {
            $disk = $this->image_disk ?: 'public';
            try {
                return Storage::disk($disk)->url(ltrim($this->image_path, '/'));
            } catch (\Throwable $e) {
                return asset('storage/' . ltrim($this->image_path, '/'));
            }
        }

        return asset('images/default-product.png');
    }

    /**
     * Prefer product’s responsive srcset; else compose from batch image sizes.
     */
    public function getImageSrcsetAttribute(): ?string
    {
        // If product has a srcset (set by controller), use that.
        if ($this->product && !empty($this->product->card_image_srcset)) {
            return (string) $this->product->card_image_srcset;
        }

        // Compose from batch-level stored sizes (legacy).
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

        // Map legacy sizes to width hints; adjust if your stored sizes differ.
        $push($this->image_thumb_path,  '150w');
        $push($this->image_medium_path, '600w');

        return $parts ? implode(', ', $parts) : null;
    }

    /**
     * Human-friendly "Type" derived from per-order snapshot and parent.
     */
    public function getTypeNameAttribute(): string
    {
        $childName  = trim((string)($this->product_name_snapshot ?: $this->product?->product_name ?: ''));
        $parentName = trim((string)($this->parentProduct?->product_name ?: ''));

        if ($childName !== '') {
            if ($parentName !== '' && stripos($childName, $parentName) !== false) {
                $type = trim(preg_replace('/\s+/', ' ', str_ireplace($parentName, '', $childName)));
                if ($type !== '') return $type;
            }
            if ($parentName === '' || strcasecmp($childName, $parentName) !== 0) {
                return $childName;
            }
        }

        return 'Base';
    }

    public function getTypeKeywordsAttribute(): string
    {
        $parts = [
            mb_strtolower($this->type_name),
            mb_strtolower($this->product_name_snapshot ?: $this->product?->product_name ?: ''),
            mb_strtolower($this->parentProduct?->product_name ?: ''),
            mb_strtolower((string)($this->batch_number ?? '')),
            mb_strtolower($this->remarks ?? ''),
        ];
        return trim(preg_replace('/\s+/', ' ', implode(' ', array_filter($parts))));
    }

    /** Pretty label for UI like "BATCH #5" */
    public function getBatchLabelAttribute(): string
    {
        return 'BATCH #'.(int)($this->batch_number ?? 0);
    }

    /* ============================ Mutators ============================= */

    /**
     * Normalize batch_number to a clean numeric string when possible.
     */
    public function setBatchNumberAttribute($value): void
    {
        if (is_null($value)) {
            $this->attributes['batch_number'] = null;
            return;
        }
        $raw = trim((string)$value);

        // Prefer numeric extraction (controller generates pure ints now)
        if ($raw !== '' && preg_match('/(\d+)/', $raw, $m)) {
            $this->attributes['batch_number'] = (string)((int)$m[1]);
            return;
        }

        // Fallback: sanitized string (legacy)
        $norm = preg_replace('/\s+/', ' ', $raw);
        $this->attributes['batch_number'] = mb_strtoupper($norm);
    }

    public function setRemarksAttribute($value): void
    {
        if (is_null($value)) {
            $this->attributes['remarks'] = null;
            return;
        }
        $norm = trim((string)$value);
        $this->attributes['remarks'] = mb_substr($norm, 0, 500);
    }

    /* =========================== Model Events ========================== */

    protected static function booted(): void
    {
        static::saving(function (self $m) {
            // numeric coercions & clamps
            $m->quantity          = is_numeric($m->quantity) ? (int)$m->quantity : 0;
            $m->current_inventory = is_numeric($m->current_inventory) ? (int)$m->current_inventory : null;
            $m->forecasted_demand = is_numeric($m->forecasted_demand) ? (float)$m->forecasted_demand : 0.0;
            $m->unit_price_pack   = is_numeric($m->unit_price_pack) ? max(0.0, (float)$m->unit_price_pack) : 0.0;
            $m->unit_price_bag    = is_numeric($m->unit_price_bag)  ? max(0.0, (float)$m->unit_price_bag)  : 0.0;

            // availability fields
            $m->available_pack = is_numeric($m->available_pack) ? max(0, (int)$m->available_pack) : 0;
            $m->available_bag  = is_numeric($m->available_bag)  ? max(0, (int)$m->available_bag)  : 0;

            // defaults
            if ($m->exists === false && ($m->current_inventory === null || $m->current_inventory === '')) {
                $m->current_inventory = (int) $m->quantity;
            }
            if (empty($m->image_disk)) {
                $m->image_disk = 'public';
            }
            if (empty($m->production_date)) {
                $m->production_date = Carbon::today();
            }

            // ensure parent_product_id
            if (empty($m->parent_product_id) && !empty($m->product_id)) {
                if ($m->relationLoaded('product') && $m->product) {
                    $m->parent_product_id = (int) ($m->product->parent_id ?: $m->product_id);
                } else {
                    $parentId = (int) (Product::whereKey($m->product_id)->value('parent_id') ?: $m->product_id);
                    $m->parent_product_id = $parentId;
                }
            }

            // snapshot label (type)
            if (empty($m->product_name_snapshot)) {
                $cat = null; $pname = null;
                if ($m->relationLoaded('product') && $m->product) {
                    $cat   = trim((string)($m->product->category ?? ''));
                    $pname = trim((string)($m->product->product_name ?? ''));
                } elseif (!empty($m->product_id)) {
                    $prod  = Product::select('category','product_name')->find($m->product_id);
                    $cat   = trim((string)($prod->category ?? ''));
                    $pname = trim((string)($prod->product_name ?? ''));
                }
                $m->product_name_snapshot = $cat !== '' ? $cat : ($pname ?: 'Base');
            }

            // expiration auto-calc
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

        $recompute = function (self $m) {
            if ($m->product_id) {
                $productId = (int) $m->product_id;

                if (App::bound(\App\Services\InventoryService::class)) {
                    App::make(\App\Services\InventoryService::class)
                        ->recomputeProductBalance($productId);
                } else {
                    $m->recomputeProductBalanceInternal($productId);
                }
            }
        };

        static::saved($recompute);
        static::deleted($recompute);
        static::restored($recompute);
    }

    /**
     * Local fallback recompute if no service is bound.
     * Connected to Production + Sale, ignoring archived rows.
     */
    protected function recomputeProductBalanceInternal(int $productId): void
    {
        // only count non-archived (not soft-deleted) batches
        $produced = (float) static::query()
            ->where('product_id', $productId)
            ->whereNull('deleted_at')
            ->sum('quantity');

        $sold = (float) Sale::query()
            ->where('product_id', $productId)
            ->whereNull('deleted_at')
            ->selectRaw('COALESCE(SUM(quantity_kg),0) + COALESCE(SUM(quantity),0) as s')
            ->value('s');

        $balance  = max(0.0, $produced - $sold);

        $latestProdDate = static::query()
            ->where('product_id', $productId)
            ->whereNull('deleted_at')
            ->max('production_date');

        $data = [
            'quantity'     => $balance,
            'stock_status' => $balance > 0 ? 'in_stock' : 'out_of_stock',
        ];

        // don't push NULL into production_date if DB column is NOT NULL
        if (!is_null($latestProdDate)) {
            $data['production_date'] = $latestProdDate;
        }

        Product::whereKey($productId)->update($data);
    }

    /* ============================ Convenience ============================ */

    public static function archiveById(int $id): bool
    {
        $row = static::find($id);
        if (!$row) return false;
        return (bool) $row->delete();
    }

    public static function visibleQuery()
    {
        return static::query()->visible();
    }
}
