<?php

namespace App\Models;

use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Carbon;

class Sale extends Model
{
    use SoftDeletes;

    protected $table = 'sales';

    /** Simple statuses (keep in sync with DB + UI) */
    public const STATUS_PENDING   = 'Pending';
    public const STATUS_COMPLETED = 'Completed';
    public const STATUS_CANCELLED = 'Cancelled';
    public const STATUS_PAID      = 'Paid';

    /** Useful list for validation/UI */
    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
        self::STATUS_PAID,
    ];

    /**
     * While debugging you can swap to:
     * protected $guarded = [];
     * but keep $fillable in prod for safety.
     */
    protected $fillable = [
        // Current schema columns
        'product_id',
        'production_id',
        'invoice_number',
        'order_number',
        'order_date',
        'product',          // legacy string label
        'product_name',     // (exists in your table screenshot)
        'type_label',       // <<— important for dashboard “Type”
        'quantity',
        'quantity_kg',
        'unit_price',
        'price',
        'total',
        'total_price',
        'status',
        'customer_name',
        'notes',

        // Optional timeline fields used by controllers (if columns exist)
        'production_date',
        'expiration_date',

        // Legacy date
        'date',
    ];

    protected $casts = [
        // New-ish
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
    protected $appends = [
        'display_product',
        'sale_date',
        'is_paid',
        'total_value',     // direct accessor for API use
        'invoice',         // unified invoice/order number
        'sale_type',       // <<— normalized type for dashboard
    ];

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
        return $this->hasMany(\App\Models\BatchAllocation::class);
    }

    public function audits()
    {
        return $this->hasMany(\App\Models\SaleAudit::class);
    }

    /* -------------------------------- Scopes --------------------------------- */

    public function scopeStatus($q, string $status)
    {
        return $q->where('status', $status);
    }

    public function scopePaid($q)
    {
        return $q->where('status', self::STATUS_PAID);
    }

    public function scopeOpen($q)
    {
        return $q->whereIn('status', [self::STATUS_PENDING, self::STATUS_COMPLETED]);
    }

    public function scopeDateBetween($q, ?string $from, ?string $to)
    {
        $col = Schema::hasColumn($this->getTable(), 'order_date') ? 'order_date' : 'date';
        if ($from) $q->whereDate($col, '>=', $from);
        if ($to)   $q->whereDate($col, '<=', $to);
        return $q;
    }

    public function scopeSearch($q, ?string $term)
    {
        if (!$term) return $q;
        $term = trim($term);
        return $q->where(function ($qq) use ($term) {
            $qq->where('order_number', 'like', "%{$term}%")
               ->orWhere('invoice_number', 'like', "%{$term}%")
               ->orWhere('customer_name', 'like', "%{$term}%")
               ->orWhere('product', 'like', "%{$term}%")
               ->orWhere('product_name', 'like', "%{$term}%")
               ->orWhere('type_label', 'like', "%{$term}%")
               ->orWhere('notes', 'like', "%{$term}%");
        });
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

    /** Total value (computed if not stored) – method */
    public function totalValue(): float
    {
        if (!is_null($this->total_price ?? null)) return (float) $this->total_price;
        if (!is_null($this->total ?? null))       return (float) $this->total;
        return round($this->qtyKg() * $this->unitPriceValue(), 2);
    }

    /** Total value – accessor (so it's in $appends) */
    public function getTotalValueAttribute(): float
    {
        return $this->totalValue();
    }

    /** Display product: prefer explicit product_name, then legacy string, then relation */
    public function getDisplayProductAttribute(): string
    {
        if (!empty($this->product_name)) return (string) $this->product_name;
        if (!empty($this->product))      return (string) $this->product;
        return optional($this->productRef)->product_name ?? '';
    }

    /** Unified sale date: prefer new order_date, then legacy date */
    public function getSaleDateAttribute(): ?Carbon
    {
        return $this->order_date ?? $this->date ?? null;
    }

    /** Unified invoice/order number for display */
    public function getInvoiceAttribute(): string
    {
        return $this->order_number ?: ($this->invoice_number ?: '');
    }

    /** Convenience boolean */
    public function getIsPaidAttribute(): bool
    {
        return ($this->status ?? '') === self::STATUS_PAID;
    }

    /**
     * Normalized sale type for dashboard:
     * 1) honors a selected SQL alias `sale_type` (from controller queries)
     * 2) falls back to `type_label`
     * 3) as a last resort, checks a few optional columns if they exist
     */
    public function getSaleTypeAttribute(): ?string
    {
        // 1) If the controller selected "... as sale_type", use that raw attribute.
        if (array_key_exists('sale_type', $this->attributes)) {
            $aliased = trim((string) $this->attributes['sale_type']);
            if ($aliased !== '') return $aliased;
        }

        // 2) Native column stored by forms / add order
        $val = trim((string) ($this->type_label ?? ''));
        if ($val !== '') return $val;

        // 3) Safe fallbacks (only if columns exist)
        foreach (['product_type', 'type', 'variant_name', 'variant'] as $col) {
            if (Schema::hasColumn($this->getTable(), $col)) {
                $v = trim((string) ($this->getAttribute($col) ?? ''));
                if ($v !== '') return $v;
            }
        }

        return null;
    }

    /* ------------------------------- Mutators -------------------------------- */

    public function setOrderDateAttribute($value): void
    {
        $this->attributes['order_date'] = $value ? Carbon::parse($value) : null;
    }

    public function setQuantityKgAttribute($value): void
    {
        $this->attributes['quantity_kg'] = is_null($value) ? null : (float) $value;

        if (array_key_exists('unit_price', $this->attributes) && !is_null($this->attributes['unit_price'])) {
            $computed = round(($this->attributes['quantity_kg'] ?? 0) * ($this->attributes['unit_price'] ?? 0), 2);
            if (Schema::hasColumn('sales', 'total_price')) {
                $this->attributes['total_price'] = $computed;
            }
        }

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

    /* ----------------------------- Stock Utilities ------------------------------ */

    /**
     * Returns available stock (kg) for a product, optionally narrowed to a batch/production.
     * Available = Produced - Sold (soft-deletes ignored).
     */
    public static function availableKg(int $productId, ?int $productionId = null): float
    {
        // Produced (kg): from productions.quantity
        $produced = DB::table('productions')
            ->whereNull('deleted_at')
            ->when($productionId, fn($q) => $q->where('id', $productionId))
            ->when(!$productionId, fn($q) => $q->where('product_id', $productId))
            ->sum(DB::raw('COALESCE(quantity,0)'));

        // Sold (kg): prefer normalized quantity_kg, fall back to quantity
        $sold = DB::table('sales')
            ->whereNull('deleted_at')
            ->where('product_id', $productId)
            ->when($productionId, fn($q) => $q->where('production_id', $productionId))
            ->sum(DB::raw('COALESCE(quantity_kg, quantity, 0)'));

        return (float) ($produced - $sold);
    }

    /* ------------------------- Inventory Side-Effects ------------------------- */

    protected static function booted()
    {
        static::creating(function (self $m) {
            // Defaults / normalization
            if (!filled($m->status) || !in_array($m->status, self::STATUSES, true)) {
                $m->status = self::STATUS_COMPLETED;
            }

            if (!filled($m->order_date) && filled($m->date)) {
                $m->order_date = Carbon::parse($m->date);
            }
            if (!filled($m->order_date)) {
                $m->order_date = now();
            }

            if (!filled($m->order_number) && Schema::hasColumn($m->getTable(), 'order_number')) {
                $m->order_number = static::generateInvoiceNumber();
            }
            if (!filled($m->invoice_number) && Schema::hasColumn($m->getTable(), 'invoice_number')) {
                $m->invoice_number = $m->order_number ?: static::generateInvoiceNumber();
            }

            // ⛔ Server-side guard: block save if no stock or oversell
            $requestedQty = (float) ($m->quantity_kg ?? $m->quantity ?? 0);
            $pid          = (int) ($m->product_id ?? 0);
            $prodId       = $m->production_id ? (int) $m->production_id : null;

            if ($pid > 0 && $requestedQty > 0) {
                $available = self::availableKg($pid, $prodId);

                if ($available <= 0) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Cannot add this sale because stock is currently 0 for the selected product' .
                                      ($prodId ? ' / batch.' : '.'),
                    ]);
                }

                if ($requestedQty > $available) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Requested quantity exceeds available stock. Available: ' .
                                      number_format($available, 3) . ' kg.',
                    ]);
                }
            }

            // Compute totals if missing
            $qty  = $m->quantity_kg ?? $m->quantity ?? 0;
            $unit = $m->unit_price  ?? $m->price    ?? 0;
            $computed = round((float)$qty * (float)$unit, 2);

            $hasNewTotal    = Schema::hasColumn('sales', 'total_price');
            $hasLegacyTotal = Schema::hasColumn('sales', 'total');

            if ($hasNewTotal && is_null($m->total_price)) $m->total_price = $computed;
            if ($hasLegacyTotal && is_null($m->total))     $m->total      = $computed;
        });

        static::created(function (self $m) {
            static::withInventory(fn (InventoryService $svc) => $svc->applySale($m));
        });

        static::updating(function (self $m) {
            $dirty = array_intersect(
                array_keys($m->getDirty()),
                ['product_id','production_id','quantity_kg','quantity']
            );
            if (!empty($dirty)) {
                $orig = (new self())->forceFill($m->getOriginal());
                static::withInventory(fn (InventoryService $svc) => $svc->undoSale($orig));
            }
        });

        static::updated(function (self $m) {
            if ($m->wasChanged(['product_id','production_id','quantity_kg','quantity'])) {
                static::withInventory(fn (InventoryService $svc) => $svc->applySale($m));
            }
        });

        static::deleted(function (self $m) {
            static::withInventory(fn (InventoryService $svc) => $svc->undoSale($m));
        });

        static::restored(function (self $m) {
            static::withInventory(fn (InventoryService $svc) => $svc->applySale($m));
        });

        static::saved(function (self $m) {
            if ($m->product_id) {
                static::withInventory(fn (InventoryService $svc) => $svc->recomputeProductBalance((int) $m->product_id));
            }
        });
    }

    /**
     * Generates a human-friendly unique invoice number: INV-YYYYMMDD-###.
     * Uses invoice_sequences table if present; otherwise scans sales.
     */
    public static function generateInvoiceNumber(): string
    {
        $ymd = now()->format('Ymd');
        $prefix = 'INV-' . $ymd . '-';

        if (Schema::hasTable('invoice_sequences')) {
            try {
                return DB::transaction(function () use ($ymd, $prefix) {
                    $row = DB::table('invoice_sequences')
                        ->where('date_key', $ymd)
                        ->lockForUpdate()
                        ->first();

                    if (!$row) {
                        DB::table('invoice_sequences')->insert([
                            'date_key'   => $ymd,
                            'last_seq'   => 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $seq = 1;
                    } else {
                        $seq = (int) $row->last_seq + 1;
                        DB::table('invoice_sequences')
                            ->where('date_key', $ymd)
                            ->update([
                                'last_seq'   => $seq,
                                'updated_at' => now(),
                            ]);
                    }

                    return $prefix . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
                }, 3);
            } catch (\Throwable $e) {
                // fall through
            }
        }

        $dateCol   = Schema::hasColumn('sales', 'order_date') ? 'order_date' : (Schema::hasColumn('sales', 'date') ? 'date' : null);
        $numberCol = Schema::hasColumn('sales', 'invoice_number') ? 'invoice_number' : (Schema::hasColumn('sales', 'order_number') ? 'order_number' : null);

        $maxToday = null;
        if ($dateCol && $numberCol) {
            $maxToday = static::query()
                ->whereDate($dateCol, Carbon::now()->toDateString())
                ->where($numberCol, 'like', $prefix . '%')
                ->max($numberCol);
        }

        $next = 1;
        if ($maxToday) {
            $tail = substr((string) $maxToday, strlen($prefix));
            $next = (ctype_digit($tail) ? (int) $tail : 0) + 1;
        }

        return $prefix . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    /* ----------------------------- Internal utils ---------------------------- */

    protected static function withInventory(\Closure $fn): void
    {
        if (App::bound(InventoryService::class)) {
            /** @var InventoryService $svc */
            $svc = App::make(InventoryService::class);
            $fn($svc);
        }
    }
}
