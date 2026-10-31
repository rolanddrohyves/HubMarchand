<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = [
        'name',
        'logo_path',
        'primary_color',
        'secondary_color',
        'accent_color',
        'support_email',
        'support_phone',
        'maintenance_mode',
    ];

    protected function casts(): array
    {
        return ['maintenance_mode' => 'boolean'];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }
}
