<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Hash;

class Employee extends Model
{
    use HasFactory;

    /**
     * Allow mass assignment for fields you actually write from forms/controllers.
     * (If your table also has phone/hire_date, they’re included below.)
     */
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'position',
        'email',
        'username',
        'password',     // only if you keep a separate employee password (optional)
        'status',
        'avatar_path',
        'phone',        // optional column (present in your DB per screenshot)
        'hire_date',    // optional column (present in your DB per screenshot)
    ];

    /**
     * Hide sensitive data when serializing to arrays/JSON.
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Casts for clean typing & dates.
     */
    protected $casts = [
        'hire_date' => 'date',
    ];

    /**
     * Computed attributes automatically appended.
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

    /** Public URL for the stored avatar (works with `php artisan storage:link`). */
    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar_path ? asset('storage/' . ltrim($this->avatar_path, '/')) : null;
    }

    /** Convenience: "First Last". */
    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    /** Convenience: initials like "JM". */
    public function getInitialsAttribute(): string
    {
        $f = strtoupper(substr((string) $this->first_name, 0, 1));
        $l = strtoupper(substr((string) $this->last_name, 0, 1));
        return $f . $l;
    }

    /* -----------------------------------------------------------------
     | Mutators (normalizers)
     * ----------------------------------------------------------------*/

    /** Store email in lowercase for uniqueness & matching. */
    public function setEmailAttribute($value): void
    {
        $this->attributes['email'] = is_string($value) ? strtolower(trim($value)) : $value;
    }

    /** Store username in lowercase for uniqueness & matching. */
    public function setUsernameAttribute($value): void
    {
        $this->attributes['username'] = is_string($value) ? strtolower(trim($value)) : $value;
    }

    /**
     * Optional: hash employee.password if you keep a separate credential.
     * Safe-guards against double-hashing by checking bcrypt prefix.
     * (If your controller already hashes, you can remove this method.)
     */
    public function setPasswordAttribute($value): void
    {
        if (empty($value)) {
            $this->attributes['password'] = null;
            return;
        }
        $val = (string) $value;
        $this->attributes['password'] = str_starts_with($val, '$2y$') ? $val : Hash::make($val);
    }

    /* -----------------------------------------------------------------
     | Scopes (handy query helpers)
     * ----------------------------------------------------------------*/

    public function scopeActive($q)
    {
        return $q->where('status', 'active');
    }

    public function scopeInactive($q)
    {
        return $q->where('status', 'inactive');
    }

    /** Natural alphabetical order (first_name, then last_name). */
    public function scopeOrdered($q)
    {
        return $q->orderBy('first_name')->orderBy('last_name');
    }

    /** Reusable search scope (matches your controller search fields). */
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
