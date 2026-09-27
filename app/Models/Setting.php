<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public static function set(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => (string) $value]);
    }

    public static function isTrue(string $key, bool $default = false): bool
    {
        $val = static::get($key, $default ? '1' : '0');
        return in_array($val, ['1', 1, true, 'true', 'on', 'yes'], true);
    }
}
