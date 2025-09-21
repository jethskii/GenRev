<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /** Simple role constants (keep in sync across code & validation) */
    public const ROLE_ADMIN     = 'admin';
    public const ROLE_SALES     = 'sales';
    public const ROLE_INVENTORY = 'inventory';

    /**
     * Mass-assignable attributes.
     *
     * Ensure your users table has:
     *   $table->string('role', 40)->nullable()->index();
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * Attributes hidden from arrays / JSON.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Attribute casting.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed', // Laravel handles hashing on set
        ];
    }

    /* -----------------------------------------------------------------
     | Relationships
     * ----------------------------------------------------------------*/

    /**
     * Linked employee profile created at registration.
     */
    public function employee()
    {
        return $this->hasOne(\App\Models\Employee::class);
    }

    /**
     * Approvals on batch allocations (approved_by FK).
     * Keep if you’re using this relation elsewhere.
     */
    public function approvedAllocations()
    {
        return $this->hasMany(\App\Models\BatchAllocation::class, 'approved_by');
    }

    /* -----------------------------------------------------------------
     | Role helpers
     * ----------------------------------------------------------------*/

    /** Normalize role on set (lowercase). */
    public function setRoleAttribute($value): void
    {
        $this->attributes['role'] = is_string($value) ? strtolower($value) : $value;
    }

    /** Exact role check (case-insensitive). */
    public function hasRole(string $role): bool
    {
        return strtolower($this->role ?? '') === strtolower($role);
    }

    /** Check against any of multiple roles. */
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

    /** Quick admin flag (for @can / blade conditionals). */
    public function getIsAdminAttribute(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    /** Can this user approve FIFO overrides? */
    public function canApproveAllocations(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    /* -----------------------------------------------------------------
     | Scopes
     * ----------------------------------------------------------------*/

    /** Filter by a single role. */
    public function scopeRole($q, string $role)
    {
        return $q->where('role', strtolower($role));
    }

    /** Filter by any of the given roles. */
    public function scopeRoleIn($q, array $roles)
    {
        $lower = array_map(fn($r) => strtolower($r), $roles);
        return $q->whereIn('role', $lower);
    }

    /* -----------------------------------------------------------------
     | Accessors
     * ----------------------------------------------------------------*/

    /**
     * A nicer display name (prefers related Employee first/last if present).
     */
    public function getDisplayNameAttribute(): string
    {
        $emp = $this->relationLoaded('employee') ? $this->employee : $this->employee()->first();
        if ($emp && ($emp->first_name || $emp->last_name)) {
            return trim(($emp->first_name ?? '') . ' ' . ($emp->last_name ?? ''));
        }
        return (string) ($this->name ?? $this->email);
    }
}
