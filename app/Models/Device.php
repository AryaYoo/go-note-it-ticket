<?php

namespace App\Models;

use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope());
    }

    protected $fillable = [
        'company_id',
        'store_id',
        'type',
        'alias',
        'model',
        'serial_number',
        'channel_count',
        'camera_count',
        'modem_notes',
        'is_active',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'channel_count' => 'integer',
        'camera_count'  => 'integer',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function dailyCheckItems()
    {
        return $this->hasMany(DailyCheckItem::class);
    }

    public function tickets()
    {
        return $this->belongsToMany(Ticket::class, 'ticket_devices');
    }

    /** Label display: "Alias (Model)" atau fallback ke type */
    public function getDisplayNameAttribute(): string
    {
        $name = $this->alias ?: $this->type;
        return $this->model ? "{$name} — {$this->model}" : $name;
    }
}
