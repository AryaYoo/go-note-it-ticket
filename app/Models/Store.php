<?php

namespace App\Models;

use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope());
    }

    protected $fillable = [
        'company_id',
        'name',
        'branch',
        'contact',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function devices()
    {
        return $this->hasMany(Device::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    /** Label untuk dropdown/display: "Nama (Cabang)" */
    public function getDisplayNameAttribute(): string
    {
        return $this->branch
            ? "{$this->name} ({$this->branch})"
            : $this->name;
    }
}
