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
        // 'notes',            // include only if the column exists
        // 'archived_reason',  // optional column if you store a reason on delete
    ];

    /** Casts */
    protected $casts = [
        'id'                => 'integer',
        'parent_product_id' => 'integer',
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

    /**
     * Only archived (soft-deleted) rows.
     */
    public function scopeArchived($q)
    {
        return $q->onlyTrashed();
    }

    /**
     * Explicitly hide archived (even inside complex joins that bypass SoftDeletingScope).
     * Use: Production::visible()->get()
     */
    public function scopeVisible($q)
    {
        $table = $this->getTable();
        return $q->whereNull("$table.deleted_at");
    }

    /**
     * Alias for visible()
     */
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

    /**
     * Flexible archived search by batch/type/product/notes.
     */
    public function scopeSearchArchived($q, ?string $term)
    {
        $s = trim((string)$term);
        if ($s === '') return $q;

        return $q->where(function($qq) use ($s) {
            $qq->where('batch_number', 'like', "%{$s}%")
               ->orWhere('product_name_snapshot', 'like', "%{$s}%")
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
     * Sort helper for archived list.
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
     * Defaults to deleted_at + N days (N = config('app.archive_ttl_days', 7)).
     * If you store a real column named `purge_at`, this accessor will
     * still return that when present; otherwise it computes on the fly.
     */
    public function getPurgeAtAttribute(): ?string
    {
        // Respect real DB column if it exists at runtime
        if (array_key_exists('purge_at', $this->attributes) && !empty($this->attributes['purge_at'])) {
            try {
                return Carbon::parse($this->attributes['purge_at'])->toDateTimeString();
            } catch (\Throwable $e) {
                // fall through to computed
            }
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
     * We do NOT fall back to category here — the snapshot is the source of truth.
     */
    public function getTypeNameAttribute(): string
    {
        $childName  = trim((string)($this->product_name_snapshot ?: $this->product?->product_name ?: ''));
        $parentName = trim((string)($this->parentProduct?->product_name ?: ''));

        if ($childName !== '') {
            if ($parentName !== '' && stripos($childName, $parentName) !== false) {
                $type = trim(preg_replace('/\s+/', ' ', str_ireplace($parentName, '', $childName)));
                if ($type !== '') return $type; // e.g., "Garlic skinless"
            }
            if ($parentName === '' || strcasecmp($childName, $parentName) !== 0) {
                return $childName; // distinct variant/label
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
            mb_strtolower($this->notes ?? ''), // only if your schema has notes
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

    /* =========================== Model Events ========================== */

    protected static function booted(): void
    {
        // Normalize & ensure NOT NULL numeric fields before save
        static::saving(function (self $m) {
            // numbers
            $m->quantity          = is_numeric($m->quantity) ? (int)$m->quantity : 0;
            $m->current_inventory = is_numeric($m->current_inventory) ? (int)$m->current_inventory : null;
            $m->unit_cost         = is_numeric($m->unit_cost) ? (float)$m->unit_cost : 0.0;
            $m->forecasted_demand = is_numeric($m->forecasted_demand) ? (float)$m->forecasted_demand : 0.0;
            $m->unit_price_pack   = is_numeric($m->unit_price_pack) ? max(0.0, (float)$m->unit_price_pack) : 0.0;
            $m->unit_price_bag    = is_numeric($m->unit_price_bag)  ? max(0.0, (float)$m->unit_price_bag)  : 0.0;

            // clamps
            if ($m->quantity < 0) $m->quantity = 0;
            if (($m->current_inventory ?? 0) < 0) $m->current_inventory = 0;
            if ($m->unit_cost < 0) $m->unit_cost = 0.0;

            // default current_inventory on create
            if ($m->exists === false && ($m->current_inventory === null || $m->current_inventory === '')) {
                $m->current_inventory = (int) $m->quantity;
            }

            // image disk default
            if (empty($m->image_disk)) {
                $m->image_disk = 'public';
            }

            // production date default
            if (empty($m->production_date)) {
                $m->production_date = Carbon::today();
            }

            // **Guarantee parent_product_id** (parent of chosen variant, else itself)
            if (empty($m->parent_product_id) && !empty($m->product_id)) {
                if ($m->relationLoaded('product') && $m->product) {
                    $m->parent_product_id = (int) ($m->product->parent_id ?: $m->product_id);
                } else {
                    $parentId = (int) (Product::whereKey($m->product_id)->value('parent_id') ?: $m->product_id);
                    $m->parent_product_id = $parentId;
                }
            }

            // **Guarantee per-order snapshot** (this drives the Type column)
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
                // Prefer category (e.g. Garlic skinless). If empty, fall back to product name.
                $m->product_name_snapshot = $cat !== '' ? $cat : ($pname ?: 'Base');
            }

            // expiration auto-calc (if shelf life known)
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

        // After any change, recompute balances for the child/variant product
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

    /* ============================ Convenience ============================ */

    /**
     * Soft-delete by id with a quick guard (returns bool).
     * Handy in controllers: Production::archiveById($id);
     */
    public static function archiveById(int $id): bool
    {
        $row = static::find($id);
        if (!$row) return false;
        return (bool) $row->delete();
    }

    /**
     * Ensure a query never shows archived items, even if someone used joins or withTrashed.
     * Use: Production::query()->visible()->get();
     */
    public static function visibleQuery()
    {
        return static::query()->visible();
    }
}
