<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    public const ROLE_ADMIN     = 'admin';
    public const ROLE_STAFF     = 'staff';
    public const ROLE_MANAGER   = 'manager';
    public const ROLE_SALES     = 'sales';
    public const ROLE_INVENTORY = 'inventory';

    public const KNOWN_ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_STAFF,
        self::ROLE_MANAGER,
        self::ROLE_SALES,
        self::ROLE_INVENTORY,
    ];

    protected $attributes = [
        'role' => self::ROLE_STAFF,
    ];

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

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'deleted_at'        => 'datetime',
    ];

    /**
     * Allow overriding the table via .env:
     *   AUTH_TABLE=app_users   (fallback: users)
     */
    public function getTable()
    {
        return env('AUTH_TABLE', 'users');
    }

    /* Relationships */
    public function employee()
    {
        return $this->hasOne(Employee::class, 'user_id');
    }

    public function approvedAllocations()
    {
        return $this->hasMany(BatchAllocation::class, 'approved_by');
    }

    /* Normalizers */
    public function setRoleAttribute($value): void
    {
        $this->attributes['role'] = is_string($value) ? strtolower(trim($value)) : $value;
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

    /* Role helpers */
    public function hasRole(string $role): bool
    {
        return strtolower($this->role ?? '') === strtolower($role);
    }

    public function hasAnyRole(array $roles): bool
    {
        $current = strtolower($this->role ?? '');
        foreach ($roles as $r) {
            if ($current === strtolower($r)) return true;
        }
        return false;
    }

    public function getIsAdminAttribute(): bool   { return $this->hasRole(self::ROLE_ADMIN); }
    public function getIsStaffAttribute(): bool   { return $this->hasRole(self::ROLE_STAFF); }
    public function getIsManagerAttribute(): bool { return $this->hasRole(self::ROLE_MANAGER); }

    public function canApproveAllocations(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    /* Scopes */
    public function scopeRole($q, string $role)
    {
        return $q->where($this->getTable().'.role', strtolower($role));
    }

    public function scopeRoleIn($q, array $roles)
    {
        $lower = array_map(fn ($r) => strtolower($r), $roles);
        return $q->whereIn($this->getTable().'.role', $lower);
    }

    public function scopeSearch($q, ?string $term)
    {
        if (!$term) return $q;
        $t = strtolower(trim($term));
        $table = $this->getTable();

        return $q->where(function ($w) use ($t, $table) {
            $w->whereRaw("LOWER({$table}.name)  LIKE ?", ["%{$t}%"])
              ->orWhereRaw("LOWER({$table}.email) LIKE ?", ["%{$t}%"]);
        });
    }

    /* Accessors */
    public function getDisplayNameAttribute(): string
    {
        $emp = $this->relationLoaded('employee') ? $this->employee : $this->employee()->first();
        if ($emp && ($emp->first_name || $emp->last_name)) {
            return trim(($emp->first_name ?? '') . ' ' . ($emp->last_name ?? ''));
        }
        return (string) ($this->name ?? $this->email);
    }
}
