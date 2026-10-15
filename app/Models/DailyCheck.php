<?php

namespace App\Models;

use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyCheck extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope());
    }

    protected $fillable = [
        'company_id',
        'user_id',
        'check_date',
        'notes',
    ];

    protected $casts = [
        'check_date' => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(DailyCheckItem::class);
    }

    /** Jumlah device yang offline dalam sesi ini */
    public function offlineCount(): int
    {
        return $this->items()->where('is_offline', true)->count();
    }
}
