<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model; // kept for phpdoc hints
use Illuminate\Foundation\Auth\User as Authenticatable; // auth-ready base
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Employee extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * If your table name isn't the Laravel default "employees", keep this.
     * (Shown for clarity.)
     */
    protected $table = 'employees';

    /**
     * Mass-assignable fields (adjust to your actual columns).
     */
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'position',
        'email',
        'username',
        'password',     // will be auto-hashed via casts
        'status',
        'avatar_path',
        'phone',
        'hire_date',
    ];

    /**
     * Hide sensitive data when serializing.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Type casting (uses Laravel's built-in "hashed" for passwords).
     */
    protected $casts = [
        'hire_date' => 'date',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Appended accessors.
     */
    protected $appends = [
        'avatar_url',
        'full_name',
        'initials',
    ];

    /* -----------------------------------------------------------------
     | Relationships
     * ----------------------------------------------------------------*/

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /* -----------------------------------------------------------------
     | Accessors
     * ----------------------------------------------------------------*/

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar_path
            ? asset('storage/' . ltrim($this->avatar_path, '/'))
            : null;
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    public function getInitialsAttribute(): string
    {
        $f = strtoupper(substr((string) $this->first_name, 0, 1));
        $l = strtoupper(substr((string) $this->last_name, 0, 1));
        return $f . $l;
    }

    /* -----------------------------------------------------------------
     | Modern normalizers (Attribute setters)
     * ----------------------------------------------------------------*/

    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn($value) => is_string($value) ? strtolower(trim($value)) : $value
        );
    }

    protected function username(): Attribute
    {
        return Attribute::make(
            set: fn($value) => is_string($value) ? strtolower(trim($value)) : $value
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
        if ($s === '') return $q;

        return $q->where(function ($w) use ($s) {
            $w->where('first_name', 'like', "%{$s}%")
              ->orWhere('last_name',  'like', "%{$s}%")
              ->orWhere('email',      'like', "%{$s}%")
              ->orWhere('username',   'like', "%{$s}%")
              ->orWhere('position',   'like', "%{$s}%");
        });
    }
}
