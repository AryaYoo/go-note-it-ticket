<?php

namespace App\Models;

use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyAnalysis extends Model
{
    /**
     * Daftarkan CompanyScope agar query MonthlyAnalysis
     * otomatis dibatasi ke company_id user yang sedang login.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope());
    }

    protected $fillable = [
        'company_id',
        'title',
        'month',
        'year',
        'content',
        'raw_data',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
