<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Role constants (supports both your DB enum and app roles).
     * Your DB currently has: admin | staff | manager.
     * Some code also uses: admin | sales | inventory.
     */
    public const ROLE_ADMIN      = 'admin';
    public const ROLE_STAFF      = 'staff';
    public const ROLE_MANAGER    = 'manager';
    public const ROLE_SALES      = 'sales';
    public const ROLE_INVENTORY  = 'inventory';

    /** All known roles (helpers will accept any of these). */
    public const KNOWN_ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_STAFF,
        self::ROLE_MANAGER,
        self::ROLE_SALES,
        self::ROLE_INVENTORY,
    ];

    /**
     * Mass-assignable attributes.
     * Includes your profile columns visible in the DB screenshot.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'website',
        'photo',
        'bio',
        'job_title',
        'alt_email',
        'email_verified_at',
    ];

    /** Hidden attributes for arrays/JSON. */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Attribute casting.
     * - 'hashed' automatically bcrypts on set.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    /* -------------------------------------------------------
     | Relationships
     * ------------------------------------------------------*/

    /** Linked employee profile (if present). */
    public function employee()
    {
        return $this->hasOne(\App\Models\Employee::class);
    }

    /** Example relation kept from your codebase. */
    public function approvedAllocations()
    {
        return $this->hasMany(\App\Models\BatchAllocation::class, 'approved_by');
    }

    /* -------------------------------------------------------
     | Mutators / Normalizers
     * ------------------------------------------------------*/

    /** Always store role in lowercase (handles enum strings). */
    public function setRoleAttribute($value): void
    {
        $this->attributes['role'] = is_string($value) ? strtolower($value) : $value;
    }

    /** Always store email in lowercase for consistent login lookups. */
    public function setEmailAttribute($value): void
    {
        $this->attributes['email'] = is_string($value) ? strtolower(trim($value)) : $value;
    }

    /** Optionally normalize alt_email too. */
    public function setAltEmailAttribute($value): void
    {
        $this->attributes['alt_email'] = is_string($value) ? strtolower(trim($value)) : $value;
    }

    /* -------------------------------------------------------
     | Role helpers
     * ------------------------------------------------------*/

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

    /** Quick flags (for Blade conditionals, gates, etc.). */
    public function getIsAdminAttribute(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }
    public function getIsStaffAttribute(): bool
    {
        return $this->hasRole(self::ROLE_STAFF);
    }
    public function getIsManagerAttribute(): bool
    {
        return $this->hasRole(self::ROLE_MANAGER);
    }

    /** Example capability helper (treat admin as superuser). */
    public function canApproveAllocations(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    /* -------------------------------------------------------
     | Scopes
     * ------------------------------------------------------*/

    /** Filter by a single role. */
    public function scopeRole($q, string $role)
    {
        return $q->where('role', strtolower($role));
    }

    /** Filter by any of the given roles. */
    public function scopeRoleIn($q, array $roles)
    {
        $lower = array_map(fn ($r) => strtolower($r), $roles);
        return $q->whereIn('role', $lower);
    }

    /* -------------------------------------------------------
     | Accessors
     * ------------------------------------------------------*/

    /**
     * Prefer Employee name if present; fall back to user name/email.
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
