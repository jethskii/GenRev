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
        'parent_product_id',     // parent (e.g., Skinless Longganisa)
        'product_id',            // child/variant product id
        'product_name_snapshot', // per-order type/variant label captured at order time
        'batch_number',
        'forecasted_demand',
        'current_inventory',
        'unit_price_pack',
        'unit_price_bag',
        'available_pack',        // counts UI
        'available_bag',         // counts UI
        'production_date',
        'expiration_date',
        'quantity',
        'image_disk',
        'image_path',
        'image_medium_path',
        'image_thumb_path',
        'remarks',               // NEW: free-text notes for the batch
        // 'archived_reason',    // include only if the column exists
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
        // 'archived_reason'  => 'string',
    ];

    /** Virtuals for UI */
    protected $appends = [
        'remaining_qty',
        'is_expired',
        'days_to_expiry',
        'image_url',
        'image_srcset',
        'type_name',       // derived per-order
        'type_keywords',   // for client-side search
        'purge_at',        // computed: deleted_at + TTL days
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
                return $q->orderBy('batch_number')->orderByDesc('id');
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
            } catch (\Throwable $e) { /* fall through */ }
        }

        if (!$this->deleted_at) return null;
        $ttl = (int) config('app.archive_ttl_days', 7);
        return Carbon::parse($this->deleted_at)->copy()->addDays(max(1, $ttl))->toDateTimeString();
    }

    public function getImageUrlAttribute(): string
    {
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
            mb_strtolower($this->batch_number ?: ''),
            mb_strtolower($this->remarks ?? ''), // UPDATED: use remarks, not notes
        ];
        return trim(preg_replace('/\s+/', ' ', implode(' ', array_filter($parts))));
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

    public function setRemarksAttribute($value): void
    {
        if (is_null($value)) {
            $this->attributes['remarks'] = null;
            return;
        }
        $norm = trim((string)$value);
        // soft clamp – DB column still enforces the real max
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
