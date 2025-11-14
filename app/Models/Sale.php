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

    /** Statuses */
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

    /** Mass-assignable fields */
    protected $fillable = [
        // schema columns (new + legacy)
        'product_id',
        'production_id',
        'invoice_number',
        'order_number',
        'order_date',
        'product',          // legacy string
        'product_name',     // preferred display
        'type_label',
        'unit_type',        // kg | pack | bag  <-- IMPORTANT for mode
        'quantity',
        'quantity_kg',
        'unit_price',
        'price',
        'total',
        'total_price',
        'status',
        'customer_name',
        'notes',

        // optional timeline fields (if present)
        'production_date',
        'expiration_date',

        // legacy date
        'date',
    ];

    protected $casts = [
        'order_date'      => 'date',
        'quantity_kg'     => 'decimal:3',
        'unit_price'      => 'decimal:2',
        'total_price'     => 'decimal:2',

        'production_date' => 'date',
        'expiration_date' => 'date',

        'date'            => 'date',
        'quantity'        => 'decimal:3',
        'price'           => 'decimal:2',
        'total'           => 'decimal:2',

        'unit_type'       => 'string', // kg | pack | bag
    ];

    protected $appends = [
        'display_product',
        'sale_date',
        'is_paid',
        'total_value',
        'invoice',
        'sale_type',
    ];

    /* ----------------------------- Relationships ----------------------------- */

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

    /** Qty in kg-equivalent, when applicable */
    public function qtyKg(): float
    {
        return (float) ($this->quantity_kg ?? $this->quantity ?? 0);
    }

    /** Unit price numeric */
    public function unitPriceValue(): float
    {
        return (float) ($this->unit_price ?? $this->price ?? 0);
    }

    /** Unified total value */
    public function totalValue(): float
    {
        if (!is_null($this->total_price ?? null)) return (float) $this->total_price;
        if (!is_null($this->total ?? null))       return (float) $this->total;
        return round($this->qtyKg() * $this->unitPriceValue(), 2);
    }

    public function getTotalValueAttribute(): float
    {
        return $this->totalValue();
    }

    public function getDisplayProductAttribute(): string
    {
        if (!empty($this->product_name)) return (string) $this->product_name;
        if (!empty($this->product))      return (string) $this->product;
        return optional($this->productRef)->product_name ?? '';
    }

    public function getSaleDateAttribute(): ?Carbon
    {
        return $this->order_date ?? $this->date ?? null;
    }

    public function getInvoiceAttribute(): string
    {
        return $this->order_number ?: ($this->invoice_number ?: '');
    }

    public function getIsPaidAttribute(): bool
    {
        return ($this->status ?? '') === self::STATUS_PAID;
    }

    /**
     * Prefer explicit sale_type attribute; fallback to type_label or legacy columns.
     */
    public function getSaleTypeAttribute(): ?string
    {
        if (array_key_exists('sale_type', $this->attributes)) {
            $aliased = trim((string) $this->attributes['sale_type']);
            if ($aliased !== '') return $aliased;
        }

        $val = trim((string) ($this->type_label ?? ''));
        if ($val !== '') return $val;

        foreach (['product_type', 'type', 'variant_name', 'variant'] as $col) {
            if (Schema::hasColumn($this->getTable(), $col)) {
                $v = trim((string) ($this->getAttribute($col) ?? ''));
                if ($v !== '') return $v;
            }
        }

        return null;
    }

    /* -------------------------------- Mutators -------------------------------- */

    public function setOrderDateAttribute($value): void
    {
        $this->attributes['order_date'] = $value ? Carbon::parse($value) : null;
    }

    public function setQuantityKgAttribute($value): void
    {
        $this->attributes['quantity_kg'] = is_null($value) ? null : (float) $value;
        $this->recomputeTotalsIntoAttributes();
    }

    public function setUnitPriceAttribute($value): void
    {
        $this->attributes['unit_price'] = is_null($value) ? null : (float) $value;
        $this->recomputeTotalsIntoAttributes();
    }

    public function setQuantityAttribute($value): void
    {
        $this->attributes['quantity'] = is_null($value) ? null : (float) $value;

        // Mirror into quantity_kg if column exists and incoming kg is not set.
        if (Schema::hasColumn('sales', 'quantity_kg') && !isset($this->attributes['quantity_kg'])) {
            $this->attributes['quantity_kg'] = $this->attributes['quantity'];
        }
        $this->recomputeTotalsIntoAttributes();
    }

    public function setPriceAttribute($value): void
    {
        $this->attributes['price'] = is_null($value) ? null : (float) $value;
        $this->recomputeTotalsIntoAttributes();
    }

    protected function recomputeTotalsIntoAttributes(): void
    {
        if (Schema::hasColumn('sales', 'total_price')
            && array_key_exists('quantity_kg', $this->attributes)
            && array_key_exists('unit_price', $this->attributes)
            && !is_null($this->attributes['quantity_kg'])
            && !is_null($this->attributes['unit_price'])) {

            $this->attributes['total_price'] = round(
                (float)$this->attributes['quantity_kg'] * (float)$this->attributes['unit_price'], 2
            );
        }

        if (Schema::hasColumn('sales', 'total')
            && array_key_exists('quantity', $this->attributes)
            && array_key_exists('price', $this->attributes)
            && !is_null($this->attributes['quantity'])
            && !is_null($this->attributes['price'])) {

            $this->attributes['total'] = round(
                (float)$this->attributes['quantity'] * (float)$this->attributes['price'], 2
            );
        }
    }

    /* ----------------------------- Stock Utilities --------------------------- */

    /**
     * Normalize selling mode from unit_type column (preferred): kg | pack | bag (default kg).
     * This is what connects your Sale to Production batches (current_inventory / available_pack / available_bag).
     */
    protected function resolveMode(): string
    {
        $col = Schema::hasColumn($this->getTable(), 'unit_type')
            ? 'unit_type'
            : (Schema::hasColumn($this->getTable(), 'unit') ? 'unit' : null);

        $raw = $col ? ($this->{$col} ?? null) : null;
        $t   = strtolower(trim((string) $raw));

        return in_array($t, ['kg','pack','bag'], true) ? $t : 'kg';
    }

    /**
     * Numeric amount the user is requesting (kg for kg-mode; units for pack/bag).
     * Uses quantity_kg for kg mode and quantity for pack/bag.
     */
    protected function requestedAmount(): float
    {
        $mode = $this->resolveMode();
        if ($mode === 'kg') {
            return (float) ($this->quantity_kg ?? $this->quantity ?? 0);
        }

        // packs/bags treated as whole units
        return (float) (int) round((float) ($this->quantity ?? 0));
    }

    /**
     * How many are available for the chosen mode and optional target batch.
     * - For pack/bag: sums available_pack / available_bag from Production
     * - For kg: uses total produced - total sold (per product or per batch)
     */
    public static function availableForMode(int $productId, ?int $productionId, string $mode): float
    {
        $q = DB::table('productions')->whereNull('deleted_at');

        if ($mode === 'pack') {
            $q = $productionId ? $q->where('id', $productionId) : $q->where('product_id', $productId);
            return (float) $q->sum(DB::raw('COALESCE(available_pack,0)'));
        }

        if ($mode === 'bag') {
            $q = $productionId ? $q->where('id', $productionId) : $q->where('product_id', $productId);
            return (float) $q->sum(DB::raw('COALESCE(available_bag,0)'));
        }

        // kg default: produced - sold (in sync with your ProductionController logic)
        return self::availableKg($productId, $productionId);
    }

    /**
     * Available by kg using produced - sold (supports per-batch or per-product).
     * Works with ProductionController::createBatchAndRecompute where:
     *   quantity = original produced amount
     *   current_inventory is decremented per sale
     */
    public static function availableKg(int $productId, ?int $productionId = null): float
    {
        $produced = DB::table('productions')
            ->whereNull('deleted_at')
            ->when($productionId, fn($q) => $q->where('id', $productionId))
            ->when(!$productionId, fn($q) => $q->where('product_id', $productId))
            ->sum(DB::raw('COALESCE(quantity,0)'));

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
        // ---------- CREATE ----------
        static::creating(function (self $m) {
            // Normalize status
            if (!filled($m->status) || !in_array($m->status, self::STATUSES, true)) {
                $m->status = self::STATUS_COMPLETED;
            }

            // Normalize order_date
            if (!filled($m->order_date) && filled($m->date)) {
                $m->order_date = Carbon::parse($m->date);
            }
            if (!filled($m->order_date)) {
                $m->order_date = now();
            }

            // Generate invoice/order numbers if needed
            if (!filled($m->order_number) && Schema::hasColumn($m->getTable(), 'order_number')) {
                $m->order_number = static::generateInvoiceNumber();
            }
            if (!filled($m->invoice_number) && Schema::hasColumn($m->getTable(), 'invoice_number')) {
                $m->invoice_number = $m->order_number ?: static::generateInvoiceNumber();
            }

            // Guard: stock must exist, cannot oversell (per product or per-batch)
            $requestedQty = $m->requestedAmount();
            $pid    = (int) ($m->product_id ?? 0);
            $prodId = $m->production_id ? (int) $m->production_id : null;
            $mode   = $m->resolveMode();

            if ($pid > 0 && $requestedQty > 0) {
                $available = self::availableForMode($pid, $prodId, $mode);

                if ($available <= 0) {
                    throw ValidationException::withMessages([
                        'quantity' => 'No available stock for the selected product' . ($prodId ? ' / batch.' : '.'),
                    ]);
                }

                if ($requestedQty > $available) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Requested amount exceeds available ' . $mode . ' stock. Available: ' .
                                      number_format($available, 3),
                    ]);
                }
            }

            // Ensure totals present
            $m->recomputeTotalsIntoAttributes();
        });

        // After save: apply allocation & recompute product balance
        static::created(function (self $m) {
            // For now we always use the built-in allocation / deduction logic
            if (!static::applyViaService($m)) {
                $m->allocateAndDeduct();
            }
        });

        // ---------- UPDATE ----------
        static::updating(function (self $m) {
            // If qty/batch/product/mode changes, revert old effect first
            $dirty = array_intersect(
                array_keys($m->getDirty()),
                [
                    'product_id',
                    'production_id',
                    'quantity_kg',
                    'quantity',
                    'unit_price',
                    'price',
                    'status',
                    'type_label',
                    'sale_type',
                    'unit_type', // make sure mode changes revert correctly
                ]
            );

            if (!empty($dirty)) {
                $orig = (new self())->forceFill($m->getOriginal());
                if (!static::undoViaService($orig)) {
                    $orig->releaseAllocations();
                }
            }

            $m->recomputeTotalsIntoAttributes();
        });

        static::updated(function (self $m) {
            if ($m->wasChanged([
                'product_id',
                'production_id',
                'quantity_kg',
                'quantity',
                'unit_price',
                'price',
                'status',
                'type_label',
                'sale_type',
                'unit_type',
            ])) {
                if (!static::applyViaService($m)) {
                    $m->allocateAndDeduct();
                }
            }
        });

        // ---------- DELETE / RESTORE ----------
        static::deleted(function (self $m) {
            if (!static::undoViaService($m)) {
                $m->releaseAllocations();
            }
        });

        static::restored(function (self $m) {
            if (!static::applyViaService($m)) {
                $m->allocateAndDeduct();
            }
        });

        // ---------- RECOMPUTE PRODUCT BALANCE ----------
        static::saved(function (self $m) {
            if ($m->product_id) {
                if (App::bound(InventoryService::class)) {
                    App::make(InventoryService::class)->recomputeProductBalance((int) $m->product_id);
                } else {
                    // Fallback: recompute from productions vs sales, consistent with ProductionController
                    $produced = (float) DB::table('productions')
                        ->whereNull('deleted_at')
                        ->where('product_id', $m->product_id)
                        ->sum(DB::raw('COALESCE(quantity,0)'));

                    $sold = (float) DB::table('sales')
                        ->whereNull('deleted_at')
                        ->where('product_id', $m->product_id)
                        ->sum(DB::raw('COALESCE(quantity_kg, quantity, 0)'));

                    $balance = max(0.0, $produced - $sold);

                    $latestProdDate = DB::table('productions')
                        ->whereNull('deleted_at')
                        ->where('product_id', $m->product_id)
                        ->max('production_date');

                    \App\Models\Product::whereKey($m->product_id)->update([
                        'quantity'        => $balance,
                        'stock_status'    => $balance > 0 ? 'in_stock' : 'out_of_stock',
                        'production_date' => $latestProdDate,
                    ]);
                }
            }
        });
    }

    /** Try to use InventoryService::applySale, returns true if used and handled */
    protected static function applyViaService(self $m): bool
    {
        // If you later want a central InventoryService, implement applySale($sale): bool
        // and return true only when it actually handles the allocation/deduction.
        if (App::bound(InventoryService::class)) {
            /** @var InventoryService $svc */
            $svc = App::make(InventoryService::class);
            $handled = $svc->applySale($m);
            return (bool) $handled;
        }
        return false; // fallback to local allocation logic
    }

    /** Try to use InventoryService::undoSale, returns true if used and handled */
    protected static function undoViaService(self $m): bool
    {
        if (App::bound(InventoryService::class)) {
            /** @var InventoryService $svc */
            $svc = App::make(InventoryService::class);
            $handled = $svc->undoSale($m);
            return (bool) $handled;
        }
        return false; // fallback to local release logic
    }

    /* ----------------------- Allocation + Audit (local) ----------------------- */

    /**
     * This is the heart of batch connection:
     * - Uses production_id when provided (user picked a batch)
     * - Falls back to FIFO across batches for that product
     * - Deducts from available_pack / available_bag / current_inventory accordingly
     */
    public function allocateAndDeduct(): void
    {
        $mode = $this->resolveMode();
        $req  = $this->requestedAmount();
        if ($req <= 0 || !$this->product_id) return;

        DB::transaction(function () use ($mode, $req) {
            $remaining = $req;

            $deductFromProd = function (\App\Models\Production $p, float $take) use ($mode) {
                if ($mode === 'pack') {
                    $avail = (float) ($p->available_pack ?? 0);
                    $take  = min($take, $avail);
                    if ($take > 0) {
                        $this->recordAllocation($p->id, $mode, $take);
                        $p->available_pack = max(0, $avail - $take);
                        $p->save();
                        $this->audit("Deducted {$take} pack(s) from batch {$p->batch_number} (Production #{$p->id}).");
                    }
                    return $take;
                } elseif ($mode === 'bag') {
                    $avail = (float) ($p->available_bag ?? 0);
                    $take  = min($take, $avail);
                    if ($take > 0) {
                        $this->recordAllocation($p->id, $mode, $take);
                        $p->available_bag = max(0, $avail - $take);
                        $p->save();
                        $this->audit("Deducted {$take} bag(s) from batch {$p->batch_number} (Production #{$p->id}).");
                    }
                    return $take;
                }

                // default kg allocation uses current_inventory
                $availKg = (float) ($p->current_inventory ?? 0);
                $takeKg  = min($take, $availKg);
                if ($takeKg > 0) {
                    $this->recordAllocation($p->id, 'kg', $takeKg);
                    $p->current_inventory = max(0, $availKg - $takeKg);
                    $p->save();
                    $this->audit("FIFO deduct {$takeKg} kg from batch {$p->batch_number} (Production #{$p->id}).");
                }
                return $takeKg;
            };

            // Specific batch first (if user selected a batch)
            if ($this->production_id) {
                $p = \App\Models\Production::lockForUpdate()->find($this->production_id);
                if ($p && !$p->deleted_at) {
                    $taken = $deductFromProd($p, $remaining);
                    $remaining -= $taken;
                }
            }

            // Then FIFO across other batches for this product (freshest first)
            if ($remaining > 0) {
                $batches = \App\Models\Production::query()
                    ->whereNull('deleted_at')
                    ->where('product_id', $this->product_id)
                    ->orderByDesc('production_date')
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->get(['id','batch_number','current_inventory','available_pack','available_bag']);

                foreach ($batches as $p) {
                    if ($remaining <= 0) break;
                    $taken = $deductFromProd($p, $remaining);
                    $remaining -= $taken;
                }
            }

            if ($remaining > 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Insufficient stock while allocating (concurrency). Please retry.',
                ]);
            }
        });
    }

    /**
     * Undo all allocations for this sale (used when sale is deleted/updated).
     */
    public function releaseAllocations(): void
    {
        DB::transaction(function () {
            $rows = $this->allocations()->lockForUpdate()->get();

            foreach ($rows as $alloc) {
                /** @var \App\Models\BatchAllocation $alloc */
                $p = \App\Models\Production::lockForUpdate()->find($alloc->production_id);
                if (!$p || $p->deleted_at) continue;

                if ($alloc->mode === 'pack') {
                    $p->available_pack = (float) ($p->available_pack ?? 0) + (float) $alloc->quantity_value;
                    $p->save();
                    $this->audit("Returned {$alloc->quantity_value} pack(s) to batch {$p->batch_number} (Production #{$p->id}).");
                } elseif ($alloc->mode === 'bag') {
                    $p->available_bag = (float) ($p->available_bag ?? 0) + (float) $alloc->quantity_value;
                    $p->save();
                    $this->audit("Returned {$alloc->quantity_value} bag(s) to batch {$p->batch_number} (Production #{$p->id}).");
                } else { // kg
                    $p->current_inventory = (float) ($p->current_inventory ?? 0) + (float) $alloc->quantity_value;
                    $p->save();
                    $this->audit("Reverted {$alloc->quantity_value} kg back to batch {$p->batch_number} (Production #{$p->id}).");
                }
            }

            $this->allocations()->delete();
        });
    }

    protected function recordAllocation(int $productionId, string $mode, float $qty): void
    {
        $this->allocations()->create([
            'production_id'  => $productionId,
            'mode'           => $mode,
            'quantity_value' => $qty,
        ]);
    }

    public function audit(string $message): void
    {
        $this->audits()->create([
            'message' => $message,
            'at'      => now(),
        ]);
    }

    /* ----------------------------- Invoicing utils ---------------------------- */

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
}
