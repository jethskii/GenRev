<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DemandEvent extends Model
{
    use HasFactory;

    // -----------------------------------------------------------------
    // Constants for types / statuses (keeps things clean and safe)
    // -----------------------------------------------------------------
    public const TYPE_RESERVATION = 'reservation';
    public const TYPE_HOLIDAY     = 'holiday';
    public const TYPE_PROMO       = 'promo';
    public const TYPE_OTHER       = 'other';

    public const STATUS_PLANNED   = 'planned';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_FULFILLED = 'fulfilled';

    // -----------------------------------------------------------------
    // Mass-assignable fields
    // -----------------------------------------------------------------
    protected $fillable = [
        'title',
        'event_type',
        'product_id',
        'start_date',
        'end_date',
        'reserved_qty',
        'unit_type',
        'status',
        'notes',
    ];

    // -----------------------------------------------------------------
    // Casting
    // -----------------------------------------------------------------
    protected $casts = [
        'start_date'   => 'date',
        'end_date'     => 'date',
        'reserved_qty' => 'float',
    ];

    // -----------------------------------------------------------------
    // Extra computed attributes (useful both in API + Blade)
    // -----------------------------------------------------------------
    protected $appends = [
        'is_holiday',
        'is_reservation',
        'is_promo',
    ];

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // -----------------------------------------------------------------
    // Accessors
    // -----------------------------------------------------------------
    public function getIsHolidayAttribute(): bool
    {
        return $this->event_type === self::TYPE_HOLIDAY;
    }

    public function getIsReservationAttribute(): bool
    {
        return $this->event_type === self::TYPE_RESERVATION;
    }

    public function getIsPromoAttribute(): bool
    {
        return $this->event_type === self::TYPE_PROMO;
    }

    // -----------------------------------------------------------------
    // Simple scopes (optional helpers)
    // -----------------------------------------------------------------
    public function scopeActive($query)
    {
        return $query->where('status', '!=', self::STATUS_CANCELLED);
    }

    public function scopeInRange($query, $startDate, $endDate)
    {
        return $query
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate);
    }

    public function scopeHolidays($query)
    {
        return $query->where('event_type', self::TYPE_HOLIDAY);
    }

    public function scopePromos($query)
    {
        return $query->where('event_type', self::TYPE_PROMO);
    }

    public function scopeReservations($query)
    {
        return $query->where('event_type', self::TYPE_RESERVATION);
    }
}
