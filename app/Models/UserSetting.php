<?php

// app/Models/UserSetting.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    protected $table = 'user_settings';

    protected $fillable = ['user_id', 'appearance'];

    protected $casts = [
        'appearance' => 'array',
    ];

    public static function defaults(): array
    {
        return [
            'appearance' => [
                'theme'      => 'light',
                'accent'     => '#3b82f6',
                'font_style' => 'default',
            ],
        ];
    }

    /** Get (or create-in-memory) settings array for a user */
    public static function forUser(int $userId): self
    {
        return static::firstOrNew(['user_id' => $userId]);
    }

    /** Get appearance for a user with defaults merged */
    public static function appearanceFor(int $userId): array
    {
        $row = static::forUser($userId);
        return array_replace_recursive(static::defaults()['appearance'], $row->appearance ?? []);
    }

    /** Save appearance for a user */
    public static function putAppearance(int $userId, array $appearance): self
    {
        $row = static::firstOrCreate(['user_id' => $userId]);
        $row->appearance = array_replace_recursive(static::appearanceFor($userId), $appearance);
        $row->save();

        return $row;
    }

    /** Reset appearance to defaults */
    public static function resetAppearance(int $userId): self
    {
        $row = static::firstOrCreate(['user_id' => $userId]);
        $row->appearance = static::defaults()['appearance'];
        $row->save();

        return $row;
    }
}
