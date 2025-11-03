<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleChange extends Model
{
    protected $table = 'sale_changes';
    protected $fillable = ['sale_id','user_id','changes_json'];

    protected $casts = [
        'changes_json' => 'array',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
