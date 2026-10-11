<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    protected $fillable = [
        'company_id',
        'key',
        'value',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Ambil nilai setting.
     * Mengutamakan setting spesifik company_id, lalu fallback ke global setting (company_id = null).
     */
    public static function get(string $key, $default = null, $companyId = 'auto')
    {
        if ($companyId === 'auto') {
            $companyId = auth()->check() ? auth()->user()->company_id : null;
        }

        // 1. Cek setting spesifik perusahaan jika ada companyId
        if ($companyId) {
            $setting = static::where('company_id', $companyId)->where('key', $key)->first();
            if ($setting !== null) {
                return $setting->value;
            }
        }

        // 2. Fallback ke setting global (company_id = null)
        $global = static::whereNull('company_id')->where('key', $key)->first();
        if ($global !== null) {
            return $global->value;
        }

        return $default;
    }

    /**
     * Simpan nilai setting.
     * Jika companyId = 'auto', gunakan company_id user yang sedang login jika ada.
     */
    public static function set(string $key, $value, $companyId = 'auto'): void
    {
        if ($companyId === 'auto') {
            $companyId = auth()->check() ? auth()->user()->company_id : null;
        }

        static::updateOrCreate(
            ['company_id' => $companyId, 'key' => $key],
            ['value' => (string) $value]
        );
    }

    /**
     * Cek apakah setting bernilai boolean true.
     */
    public static function isTrue(string $key, bool $default = false, $companyId = 'auto'): bool
    {
        $val = static::get($key, $default ? '1' : '0', $companyId);
        return in_array($val, ['1', 1, true, 'true', 'on', 'yes'], true);
    }
}
