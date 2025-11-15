<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable; // still fine, even if not used as auth guard
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int|null                     $user_id
 * @property string|null                  $first_name
 * @property string|null                  $last_name
 * @property string|null                  $position
 * @property string|null                  $email
 * @property string|null                  $username
 * @property string|null                  $status
 * @property string|null                  $avatar_path
 * @property string|null                  $phone
 * @property \Carbon\CarbonImmutable|null $hire_date
 * @property-read string                  $avatar_url
 * @property-read string                  $full_name
 * @property-read string                  $initials
 * @property-read string|null             $role_label
 * @property-read \App\Models\User|null   $user
 */
class Employee extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Explicit table name.
     *
     * @var string
     */
    protected $table = 'employees';

    /**
     * Mass-assignable fields.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'position',
        'email',
        'username',
        'password',     // not currently used for login; auth uses users table
        'status',
        'avatar_path',
        'phone',
        'hire_date',
    ];

    /**
     * Default attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'active',
    ];

    /**
     * Hidden attributes for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Attribute casting.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'hire_date'         => 'date',
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

    /**
     * Always load these relationships.
     *
     * @var array<int, string>
     */
    protected $with = [
        'user',
    ];

    /**
     * Accessors to append to array / JSON.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'avatar_url',
        'full_name',
        'initials',
        'role_label',
    ];

    /* -----------------------------------------------------------------
     | Relationships
     * ----------------------------------------------------------------*/

    /**
     * Linked auth user record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /* -----------------------------------------------------------------
     | Accessors
     * ----------------------------------------------------------------*/

    /**
     * Public URL for avatar file.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar_path
            ? asset('storage/' . ltrim($this->avatar_path, '/'))
            : null;
    }

    /**
     * Concatenated full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    /**
     * Initials derived from first / last name.
     */
    public function getInitialsAttribute(): string
    {
        $f = strtoupper(substr((string) $this->first_name, 0, 1));
        $l = strtoupper(substr((string) $this->last_name, 0, 1));
        return $f . $l;
    }

    /**
     * Role label used in the UI:
     * - Prefer the employee.position
     * - Fall back to linked user's role (e.g. "masters admin")
     */
    public function getRoleLabelAttribute(): ?string
    {
        $position = trim((string) $this->position);

        if ($position !== '') {
            return $position;
        }

        if ($this->user && isset($this->user->role)) {
            return (string) $this->user->role;
        }

        return null;
    }

    /* -----------------------------------------------------------------
     | Modern normalizers (Attribute setters)
     * ----------------------------------------------------------------*/

    /**
     * Always store email in lowercase / trimmed.
     */
    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => is_string($value) ? strtolower(trim($value)) : $value
        );
    }

    /**
     * Always store username in lowercase / trimmed.
     */
    protected function username(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => is_string($value) ? strtolower(trim($value)) : $value
        );
    }

    /* -----------------------------------------------------------------
     | Scopes
     * ----------------------------------------------------------------*/

    public function scopeActive($q)
    {
        return $q->where('status', 'active');
    }

    public function scopeInactive($q)
    {
        return $q->where('status', 'inactive');
    }

    public function scopeOrdered($q)
    {
        return $q->orderBy('first_name')->orderBy('last_name');
    }

    public function scopeSearch($q, ?string $term)
    {
        $s = trim((string) $term);
        if ($s === '') {
            return $q;
        }

        return $q->where(function ($w) use ($s) {
            $w->where('first_name', 'like', "%{$s}%")
              ->orWhere('last_name',  'like', "%{$s}%")
              ->orWhere('email',      'like', "%{$s}%")
              ->orWhere('username',   'like', "%{$s}%")
              ->orWhere('position',   'like', "%{$s}%");
        });
    }

    /* -----------------------------------------------------------------
     | Helpers
     * ----------------------------------------------------------------*/

    /**
     * Central rule: can this employee actually log in?
     * (Status must be active AND linked user must be active if exists.)
     */
    public function canLogin(): bool
    {
        if (strtolower((string) $this->status) !== 'active') {
            return false;
        }

        if ($this->user
            && property_exists($this->user, 'is_active')
            && !$this->user->is_active
        ) {
            return false;
        }

        return true;
    }
}
