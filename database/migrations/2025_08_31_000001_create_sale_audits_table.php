<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleAudit extends Model
{
    protected $fillable = ['sale_id','user_id','changes'];
    protected $casts = [
        'changes' => 'array',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
