<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    // Allow mass assignment for these fields
    protected $fillable = [
        'company_name',
        'email',
        'phone',
        'address',
    ];

    // Optional: if you're using a settings table without timestamps
    public $timestamps = false;
}
