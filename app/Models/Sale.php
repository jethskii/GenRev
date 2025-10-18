<?php

namespace App\Models;

use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
    protected $appends = [
        'display_product',
        'sale_date',
        'is_paid',
        'total_value',     // add a direct accessor for API use
        'invoice',         // unified invoice/order number
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
        // If you have a concrete BatchAllocation model, confirm the FK ('sale_id') matches your schema.
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

    /* ------------------------------- Mutators -------------------------------- */

    public function setOrderDateAttribute($value): void
    {
        // Accept strings/Carbon and normalize
        $this->attributes['order_date'] = $value ? Carbon::parse($value) : null;
    }

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
        // Before create: defaults + ensure a total exists + invoice/order number
        static::creating(function (self $m) {
            // Default status
            if (!filled($m->status) || !in_array($m->status, self::STATUSES, true)) {
                $m->status = self::STATUS_COMPLETED;
            }

            // Default date
            if (!filled($m->order_date) && filled($m->date)) {
                $m->order_date = Carbon::parse($m->date);
            }
            if (!filled($m->order_date)) {
                $m->order_date = now();
            }

            // Generate readable numbers if missing
            if (!filled($m->order_number) && Schema::hasColumn($m->getTable(), 'order_number')) {
                $m->order_number = static::generateInvoiceNumber();
            }
            if (!filled($m->invoice_number) && Schema::hasColumn($m->getTable(), 'invoice_number')) {
                $m->invoice_number = $m->order_number ?: static::generateInvoiceNumber();
            }

            // Ensure totals exist (write to whichever columns are present)
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
            static::withInventory(fn (InventoryService $svc) => $svc->applySale($m));
        });

        // On update: revert old sale impact first if core fields changed
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

        // Soft delete: return inventory
        static::deleted(function (self $m) {
            static::withInventory(fn (InventoryService $svc) => $svc->undoSale($m));
        });

        // Restore: deduct again
        static::restored(function (self $m) {
            static::withInventory(fn (InventoryService $svc) => $svc->applySale($m));
        });

        // Always keep cached product balance in sync
        static::saved(function (self $m) {
            if ($m->product_id) {
                static::withInventory(fn (InventoryService $svc) => $svc->recomputeProductBalance((int) $m->product_id));
            }
        });
    }

    /**
     * Generates a human-friendly unique invoice number:
     * INV-YYYYMMDD-### (sequence per day, with DB-safe increment).
     * If "invoice_sequences" does not exist, falls back to scanning "sales".
     */
    public static function generateInvoiceNumber(): string
    {
        $ymd = now()->format('Ymd');
        $prefix = 'INV-' . $ymd . '-';

        // Use dedicated sequence table if available
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
                // fall through to MAX()-based fallback
            }
        }

        // Fallback: scan existing sales for today and increment the tail
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

    /**
     * Run a closure if InventoryService is bound; prevents errors in tests or
     * environments where inventory logic is not registered.
     */
    protected static function withInventory(\Closure $fn): void
    {
        if (App::bound(InventoryService::class)) {
            /** @var InventoryService $svc */
            $svc = App::make(InventoryService::class);
            $fn($svc);
        }
    }
}
