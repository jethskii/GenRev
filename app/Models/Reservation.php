<?php

// app/Models/Reservation.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $fillable = [
        'reserved_date',
        'units',
        'product_id',
        'reference_code',
        'notes',
    ];

    protected $casts = [
        'reserved_date' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
