<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    /* =========================================================================
     |  Canonical Roles (stored in DB as lowercase, spaced as declared here)
     |  ========================================================================= */
    public const ROLE_MASTERSADMIN      = 'masters admin';
    public const ROLE_PRODUCTIONMANAGER = 'production manager';
    public const ROLE_SALES             = 'sales';
    public const ROLE_INVENTORY         = 'inventory';

    /** Aliases that normalize to non-admin staff (sales) */
    public const ROLE_EMPLOYEE_ALIAS = 'employee';
    public const ROLE_STAFF_ALIAS    = 'staff';
    public const ROLE_MANAGER_ALIAS  = 'manager';

    /** Admin-like aliases that should normalize to Masters Admin */
    public const ADMIN_ALIASES = [
        'admin',
        'administrator',
        'superadmin',
        'super administrator',
        'master admin',
        'masters admin',
        'owner',
    ];

    /** Only these should be persisted long-term */
    public const KNOWN_ROLES = [
        self::ROLE_MASTERSADMIN,
        self::ROLE_PRODUCTIONMANAGER,
        self::ROLE_SALES,
        self::ROLE_INVENTORY,
    ];

    /** Pretty labels for UI */
    public const ROLE_LABELS = [
        self::ROLE_MASTERSADMIN      => 'Masters Admin',
        self::ROLE_PRODUCTIONMANAGER => 'Production Manager',
        self::ROLE_SALES             => 'Sales',
        self::ROLE_INVENTORY         => 'Inventory',
    ];

    /**
     * Centralized sidebar/feature allowlist per role.
     * Keep keys in sync with your route names and Blade checks.
     */
    public const MODULE_MAP = [
        self::ROLE_MASTERSADMIN      => ['dashboard', 'materials', 'production', 'sales', 'inventory', 'products', 'reports', 'settings', 'employee', 'users'],
        self::ROLE_PRODUCTIONMANAGER => ['dashboard', 'production', 'products', 'settings'],
        self::ROLE_SALES             => ['dashboard', 'sales', 'settings'],
        self::ROLE_INVENTORY         => ['dashboard', 'inventory', 'materials', 'settings'],
    ];

    /* =========================================================================
     |  Attributes / Fillable / Hidden / Casts
     |  ========================================================================= */

    /** Sensible defaults */
    protected $attributes = [
        'role'      => self::ROLE_SALES, // safe, minimal default
        'is_active' => true,
    ];

    /** Mass-assignable fields */
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
        'email_verified_at', // keep if you intentionally set this manually
    ];

    /** Hidden */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** Casts */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'deleted_at'        => 'datetime',
        'is_active'         => 'boolean',
        'password'          => 'hashed',
        'role'              => 'string', // accessor returns canonical form
    ];

    /**
     * Allow overriding the table via .env:
     *   AUTH_TABLE=app_users   (fallback: users)
     */
    public function getTable(): string
    {
        return env('AUTH_TABLE', 'users');
    }

    /* =========================================================================
     |  Relationships
     |  ========================================================================= */

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class, 'user_id');
    }

    public function approvedAllocations(): HasMany
    {
        return $this->hasMany(BatchAllocation::class, 'approved_by');
    }

    /* =========================================================================
     |  Mutators / Accessors (Normalizers)
     |  ========================================================================= */

    /**
     * Canonicalize incoming role values.
     * - empty => keep current or fallback to sales
     * - employee/staff/manager => sales
     * - any *admin* variant => masters admin
     * - unknown => keep previous or fallback to sales
     */
    public function setRoleAttribute($value): void
    {
        if (!is_string($value) || trim($value) === '') {
            $this->attributes['role'] = $this->attributes['role'] ?? self::ROLE_SALES;
            return;
        }

        $role = self::normalizeRole((string) $value);

        // persist only known roles
        if (!in_array($role, self::KNOWN_ROLES, true)) {
            $role = $this->attributes['role'] ?? self::ROLE_SALES;
        }

        $this->attributes['role'] = $role;
    }

    /**
     * Read accessor guarantees role is canonical.
     */
    public function getRoleAttribute($value): ?string
    {
        if (!is_string($value)) {
            return $value;
        }

        $normalized = self::normalizeRole($value);

        return in_array($normalized, self::KNOWN_ROLES, true)
            ? $normalized
            : self::ROLE_SALES;
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

    /* =========================================================================
     |  Role Helpers (for Blade/Policies)
     |  ========================================================================= */

    public function hasRole(string $role): bool
    {
        return (string) ($this->role ?? '') === self::normalizeRole($role);
    }

    /** Accepts variadics or array: hasAnyRole('sales','inventory') */
    public function hasAnyRole(string|array ...$roles): bool
    {
        $list = is_array($roles[0] ?? null) ? $roles[0] : $roles;
        foreach ($list as $r) {
            if ($this->hasRole((string) $r)) {
                return true;
            }
        }
        return false;
    }

    // Readable flags for Blade
    public function getIsMastersAdminAttribute(): bool      { return $this->hasRole(self::ROLE_MASTERSADMIN); }
    public function getIsProductionManagerAttribute(): bool { return $this->hasRole(self::ROLE_PRODUCTIONMANAGER); }
    public function getIsSalesAttribute(): bool             { return $this->hasRole(self::ROLE_SALES); }
    public function getIsInventoryAttribute(): bool         { return $this->hasRole(self::ROLE_INVENTORY); }

    /** Human label for UI */
    public function getRoleLabelAttribute(): string
    {
        $role = (string) ($this->role ?? self::ROLE_SALES);
        return self::ROLE_LABELS[$role] ?? Str::headline($role);
    }

    /** Sidebar/feature allowlist (never empty) */
    public function allowedModules(): array
    {
        $role = (string) ($this->role ?? self::ROLE_SALES);
        $list = self::MODULE_MAP[$role] ?? [];

        return $list ?: ['dashboard', 'settings'];
    }

    public function canAccessModule(string $module): bool
    {
        return in_array($module, $this->allowedModules(), true);
    }

    /** Helper: check multiple modules */
    public function canAccessModules(string ...$modules): bool
    {
        $allowed = $this->allowedModules();
        foreach ($modules as $m) {
            if (!in_array($m, $allowed, true)) {
                return false;
            }
        }
        return true;
    }

    /** Aliases for readability in policies */
    public function roleIs(string $role): bool
    {
        return $this->hasRole($role);
    }

    public function roleIn(array $roles): bool
    {
        return $this->hasAnyRole($roles);
    }

    /* =========================================================================
     |  Query Scopes
     |  ========================================================================= */

    public function scopeRole($q, string $role)
    {
        $r     = self::normalizeRole($role);
        $table = $q->getModel()->getTable();

        return $q->where("{$table}.role", $r);
    }

    public function scopeRoleIn($q, array $roles)
    {
        $table = $q->getModel()->getTable();
        $lower = array_map(fn ($r) => self::normalizeRole((string) $r), $roles);
        $lower = array_values(array_intersect($lower, self::KNOWN_ROLES)); // filter unknowns

        return empty($lower)
            ? $q->whereRaw('1=0') // nothing matches if caller passed only unknowns
            : $q->whereIn("{$table}.role", $lower);
    }

    public function scopeActive($q, bool $active = true)
    {
        $table = $q->getModel()->getTable();
        return $q->where("{$table}.is_active", $active);
    }

    public function scopeSearch($q, ?string $term)
    {
        if (!$term) {
            return $q;
        }

        $t     = strtolower(trim($term));
        $table = $q->getModel()->getTable();

        return $q->where(function ($w) use ($t, $table) {
            $w->whereRaw("LOWER({$table}.name) LIKE ?", ["%{$t}%"])
              ->orWhereRaw("LOWER({$table}.email) LIKE ?", ["%{$t}%"]);
        });
    }

    /* =========================================================================
     |  Presentation Accessors
     |  ========================================================================= */

    public function getDisplayNameAttribute(): string
    {
        $emp = $this->relationLoaded('employee') ? $this->employee : $this->employee()->first();
        if ($emp && ($emp->first_name || $emp->last_name)) {
            return trim(($emp->first_name ?? '') . ' ' . ($emp->last_name ?? ''));
        }
        return (string) ($this->name ?? $this->email);
    }

    public function getInitialsAttribute(): string
    {
        $name = trim((string) ($this->display_name ?? $this->name ?? ''));
        if ($name === '') {
            return strtoupper(substr((string) $this->email, 0, 1));
        }

        $parts = preg_split('/\s+/', $name);
        $ini   = strtoupper(substr($parts[0] ?? '', 0, 1) . substr($parts[1] ?? '', 0, 1));

        return $ini ?: 'U';
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->photo ? (string) $this->photo : null;
    }

    /* =========================================================================
     |  Internal Role Normalizer (single source of truth)
     |  ========================================================================= */

    private static function normalizeRole(string $value): string
    {
        $rawLower = strtolower(trim($value));                      // "Masters Admin" -> "masters admin"
        $norm     = preg_replace('/[^a-z]/', '', $rawLower);       // -> "mastersadmin"

        // Admin family
        $adminNorms = array_map(
            fn ($v) => preg_replace('/[^a-z]/', '', strtolower($v)),
            self::ADMIN_ALIASES
        );
        if (in_array($norm, $adminNorms, true) || str_contains($norm, 'admin')) {
            return self::ROLE_MASTERSADMIN;
        }

        // Staff-like collapse to sales
        $staffNorms = [
            preg_replace('/[^a-z]/', '', self::ROLE_EMPLOYEE_ALIAS),
            preg_replace('/[^a-z]/', '', self::ROLE_STAFF_ALIAS),
            preg_replace('/[^a-z]/', '', self::ROLE_MANAGER_ALIAS),
        ];
        if (in_array($norm, $staffNorms, true)) {
            return self::ROLE_SALES;
        }

        // Exact matches
        return match ($norm) {
            'mastersadmin'      => self::ROLE_MASTERSADMIN,
            'productionmanager' => self::ROLE_PRODUCTIONMANAGER,
            'sales'             => self::ROLE_SALES,
            'inventory'         => self::ROLE_INVENTORY,
            default             => $rawLower !== '' ? $rawLower : self::ROLE_SALES,
        };
    }
}
