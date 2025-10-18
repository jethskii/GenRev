<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    /** -------------------------
     * Canonical roles (lowercase)
     * ------------------------- */
    public const ROLE_ADMIN     = 'admin';
    public const ROLE_SALES     = 'sales';
    public const ROLE_INVENTORY = 'inventory';

    /** Legacy/alias inputs we’ll normalize */
    public const ROLE_EMPLOYEE_ALIAS = 'employee';
    public const ROLE_STAFF_ALIAS    = 'staff';
    public const ROLE_MANAGER_ALIAS  = 'manager';

    /** Only these are stored long-term */
    public const KNOWN_ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_SALES,
        self::ROLE_INVENTORY,
    ];

    /** Defaults */
    protected $attributes = [
        'role'      => self::ROLE_SALES, // safe default
        'is_active' => true,
    ];

    /** Mass-assignable */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'website',
        'photo',
        'bio',
        'job_title',
        'alt_email',
        'email_verified_at',
    ];

    /** Hidden */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** Casts */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'deleted_at'        => 'datetime',
        'is_active'         => 'boolean',
    ];

    /**
     * Allow overriding the table via .env:
     *   AUTH_TABLE=app_users   (fallback: users)
     */
    public function getTable()
    {
        return env('AUTH_TABLE', 'users');
    }

    /* ========================
     * Relationships
     * ======================== */
    public function employee()
    {
        return $this->hasOne(Employee::class, 'user_id');
    }

    public function approvedAllocations()
    {
        return $this->hasMany(BatchAllocation::class, 'approved_by');
    }

    /* ========================
     * Normalizers / Mutators
     * ======================== */

    /**
     * Normalize role input to the canonical set:
     * - 'employee' or 'staff' => 'sales'
     * - 'manager'             => 'sales'  (safe default; avoid accidental admin grants)
     * - unknown               => keep previous or fallback to 'sales'
     */
    public function setRoleAttribute($value): void
    {
        if (!is_string($value)) {
            $this->attributes['role'] = $value;
            return;
        }

        $role = strtolower(trim($value));

        // Map aliases/legacy roles to our canonical set
        if (in_array($role, [self::ROLE_EMPLOYEE_ALIAS, self::ROLE_STAFF_ALIAS, self::ROLE_MANAGER_ALIAS], true)) {
            $role = self::ROLE_SALES;
        }

        if (!in_array($role, self::KNOWN_ROLES, true)) {
            $role = $this->attributes['role'] ?? self::ROLE_SALES;
        }

        $this->attributes['role'] = $role;
    }

    public function setEmailAttribute($value): void
    {
        $this->attributes['email'] = is_string($value) ? strtolower(trim($value)) : $value;
    }

    public function setAltEmailAttribute($value): void
    {
        $this->attributes['alt_email'] = is_string($value) ? strtolower(trim($value)) : $value;
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = is_string($value) ? trim($value) : $value;
    }

    /* ========================
     * Role helpers
     * ======================== */
    public function hasRole(string $role): bool
    {
        $current = strtolower($this->role ?? '');
        $target  = strtolower($role);

        // Accept legacy aliases on checks
        if (in_array($target, [self::ROLE_EMPLOYEE_ALIAS, self::ROLE_STAFF_ALIAS, self::ROLE_MANAGER_ALIAS], true)) {
            $target = self::ROLE_SALES;
        }

        return $current === $target;
    }

    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $r) {
            if ($this->hasRole($r)) return true;
        }
        return false;
    }

    // Convenient, camel-cased accessors for Blade/Policies
    public function getIsAdminAttribute(): bool     { return $this->hasRole(self::ROLE_ADMIN); }
    public function getIsSalesAttribute(): bool     { return $this->hasRole(self::ROLE_SALES); }
    public function getIsInventoryAttribute(): bool { return $this->hasRole(self::ROLE_INVENTORY); }

    /** Pretty label for UI */
    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN     => 'Admin',
            self::ROLE_SALES     => 'Sales',
            self::ROLE_INVENTORY => 'Inventory',
            default              => ucfirst((string) $this->role),
        };
    }

    /* ========================
     * Scopes
     * ======================== */
    public function scopeRole($q, string $role)
    {
        $role = strtolower($role);
        if (in_array($role, [self::ROLE_EMPLOYEE_ALIAS, self::ROLE_STAFF_ALIAS, self::ROLE_MANAGER_ALIAS], true)) {
            $role = self::ROLE_SALES;
        }
        return $q->where($this->getTable().'.role', $role);
    }

    public function scopeRoleIn($q, array $roles)
    {
        $lower = array_map(function ($r) {
            $r = strtolower($r);
            return in_array($r, [self::ROLE_EMPLOYEE_ALIAS, self::ROLE_STAFF_ALIAS, self::ROLE_MANAGER_ALIAS], true)
                ? self::ROLE_SALES
                : $r;
        }, $roles);

        return $q->whereIn($this->getTable().'.role', $lower);
    }

    public function scopeActive($q, bool $active = true)
    {
        return $q->where($this->getTable().'.is_active', $active);
    }

    public function scopeSearch($q, ?string $term)
    {
        if (!$term) return $q;

        $t     = strtolower(trim($term));
        $table = $this->getTable();

        return $q->where(function ($w) use ($t, $table) {
            $w->whereRaw("LOWER({$table}.name)  LIKE ?", ["%{$t}%"])
              ->orWhereRaw("LOWER({$table}.email) LIKE ?", ["%{$t}%"]);
        });
    }

    /* ========================
     * Accessors
     * ======================== */
    public function getDisplayNameAttribute(): string
    {
        $emp = $this->relationLoaded('employee') ? $this->employee : $this->employee()->first();
        if ($emp && ($emp->first_name || $emp->last_name)) {
            return trim(($emp->first_name ?? '') . ' ' . ($emp->last_name ?? ''));
        }
        return (string) ($this->name ?? $this->email);
    }
}
