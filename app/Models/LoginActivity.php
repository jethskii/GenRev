<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginActivity extends Model
{
    use HasFactory;

    protected $table = 'login_activities';

    protected $fillable = [
        'user_id',
        'email',
        'ip_address',
        'user_agent',
        'login_at',
        'logout_at',
        'succeeded',
        'reason',      // 👈 new column
    ];

    protected $casts = [
        'login_at'  => 'datetime',
        'logout_at' => 'datetime',
        'succeeded' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
