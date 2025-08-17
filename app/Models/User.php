<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /** Simple role constants (keep in sync with your users.role values) */
    public const ROLE_ADMIN     = 'admin';
    public const ROLE_SALES     = 'sales';
    public const ROLE_INVENTORY = 'inventory';

    /**
     * The attributes that are mass assignable.
     *
     * NOTE: add 'role' to your users table (string, nullable) if not present.
     * Example migration column: $table->string('role', 40)->nullable()->index();
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role', // ← new
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    /* ----------------
     |  Relationships
     * ---------------- */

    // If you store the creator/owner of sales orders, you can wire this later:
    // public function salesOrders()
    // {
    //     return $this->hasMany(SalesOrder::class, 'user_id');
    // }

    // Approvals on batch allocations (approved_by FK)
    public function approvedAllocations()
    {
        return $this->hasMany(BatchAllocation::class, 'approved_by');
    }

    /* -------------
     |  Role Helpers
     * ------------- */

    /** Normalize role on set (lowercase) */
    public function setRoleAttribute($value): void
    {
        $this->attributes['role'] = is_string($value) ? strtolower($value) : $value;
    }

    /** Check if user has an exact role (case-insensitive) */
    public function hasRole(string $role): bool
    {
        return strtolower($this->role ?? '') === strtolower($role);
    }

    /** Check against multiple roles */
    public function hasAnyRole(array $roles): bool
    {
        $current = strtolower($this->role ?? '');
        foreach ($roles as $r) {
            if ($current === strtolower($r)) {
                return true;
            }
        }
        return false;
    }

    /** Quick admin bool */
    public function getIsAdminAttribute(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    /** Can this user approve FIFO overrides? (used by policies/controllers) */
    public function canApproveAllocations(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    /* ----------
     |  Scopes
     * ---------- */

    public function scopeRole($q, string $role)
    {
        return $q->where('role', strtolower($role));
    }
}
