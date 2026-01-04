<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Reservation extends Model
{
    use SoftDeletes;

    protected $table = 'reservations';

    /* ----------------------------------------------------------------------
     |  STATUS ENUM
     |-----------------------------------------------------------------------*/

    public const STATUS_RESERVED  = 'reserved';
    public const STATUS_CONVERTED = 'converted';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED   = 'expired';

    public const STATUSES = [
        self::STATUS_RESERVED,
        self::STATUS_CONVERTED,
        self::STATUS_CANCELLED,
        self::STATUS_EXPIRED,
    ];

    /* ----------------------------------------------------------------------
     |  UNIT TYPES (ONLY PACK / BAG – NO KG HERE)
     |-----------------------------------------------------------------------*/

    public const UNIT_PACK = 'pack';
    public const UNIT_BAG  = 'bag';

    public const UNIT_TYPES = [
        self::UNIT_PACK,
        self::UNIT_BAG,
    ];

    /* ----------------------------------------------------------------------
     |  FILLABLE + CASTS
     |-----------------------------------------------------------------------*/

    protected $fillable = [
        'reserved_date',    // ex: 2024-12-01
        'product_id',       // ex: Bologna product ID
        'production_id',    // optional: link to specific batch if needed
        'units',            // 4 units (per pack / per bag)
        'unit_type',        // pack | bag
        'customer_name',
        'reference_code',
        'notes',
        'status',           // reserved | converted | cancelled | expired
        'sale_id',          // link to Sale when converted
        'user_id',          // who created the reservation
    ];

    protected $casts = [
        'reserved_date' => 'date',
        'product_id'    => 'integer',
        'production_id' => 'integer',
        'sale_id'       => 'integer',
        'units'         => 'integer',
        'user_id'       => 'integer',
    ];

    /* ----------------------------------------------------------------------
     |  RELATIONSHIPS
     |-----------------------------------------------------------------------*/

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function production()
    {
        return $this->belongsTo(Production::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /* ----------------------------------------------------------------------
     |  SCOPES
     |-----------------------------------------------------------------------*/

    public function scopeForDate($q, $date)
    {
        return $q->whereDate('reserved_date', Carbon::parse($date)->toDateString());
    }

    public function scopeBetweenDates($q, $start, $end)
    {
        $s = Carbon::parse($start)->toDateString();
        $e = Carbon::parse($end)->toDateString();

        return $q->whereBetween('reserved_date', [$s, $e]);
    }

    public function scopeActive($q)
    {
        return $q->where('status', self::STATUS_RESERVED);
    }

    public function scopeStatus($q, string $status)
    {
        $status = strtolower(trim($status));

        if (! in_array($status, self::STATUSES, true)) {
            return $q;
        }

        return $q->where('status', $status);
    }

    /* ----------------------------------------------------------------------
     |  MUTATORS
     |-----------------------------------------------------------------------*/

    public function setUnitTypeAttribute($value): void
    {
        $val = strtolower(trim((string) $value));

        if (! in_array($val, self::UNIT_TYPES, true)) {
            $val = self::UNIT_PACK; // default to pack
        }

        $this->attributes['unit_type'] = $val;
    }

    public function setStatusAttribute($value): void
    {
        $val = strtolower(trim((string) $value));

        if (! in_array($val, self::STATUSES, true)) {
            $val = self::STATUS_RESERVED;
        }

        $this->attributes['status'] = $val;
    }

    /* ----------------------------------------------------------------------
     |  HELPERS
     |-----------------------------------------------------------------------*/

    public function isReserved(): bool
    {
        return $this->status === self::STATUS_RESERVED;
    }

    public function isConverted(): bool
    {
        return $this->status === self::STATUS_CONVERTED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    /**
     * Mark this reservation as converted and attach the Sale record.
     * Call this AFTER you save the Sale.
     */
    public function markAsConverted(\App\Models\Sale $sale): void
    {
        $this->sale_id = $sale->id;
        $this->status  = self::STATUS_CONVERTED;
        $this->save();
    }

    /**
     * Helper: base payload when creating a Sale from this Reservation.
     * You can merge pricing / amounts in your controller.
     *
     * Example use in controller:
     *   $data = $reservation->toSalePayload();
     *   $data['unit_price'] = $product->default_price;
     *   $data['total_price'] = $data['unit_price'] * $data['quantity'];
     *   $sale = Sale::create($data);
     */
    public function toSalePayload(): array
    {
        return [
            'product_id'    => $this->product_id,
            'production_id' => $this->production_id,   // optional batch link
            // Adjust this key depending on your sales table:
            // If your Sales table uses "date", rename this to 'date'
            // If it uses "order_date", keep as is.
            'order_date'    => $this->reserved_date,
            'quantity'      => $this->units,           // packs/bags count
            'unit_type'     => $this->unit_type,       // pack | bag
            'customer_name' => $this->customer_name,
            'notes'         => $this->notes,
        ];
    }
}
