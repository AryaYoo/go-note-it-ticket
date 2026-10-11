<?php

namespace App\Models;

use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketHistory extends Model
{
    use HasFactory;

    /**
     * Daftarkan CompanyScope agar riwayat audit
     * dibatasi otomatis ke company_id user yang sedang login.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope());
    }

    protected $fillable = [
        'company_id',
        'ticket_id',
        'user_id',
        'action',
        'description',
        'changes',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
