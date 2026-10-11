<?php

namespace App\Models;

use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentationChat extends Model
{
    use HasFactory;

    /**
     * Daftarkan CompanyScope agar query pesan dokumentasi
     * dibatasi secara otomatis ke company_id user yang sedang login.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope());
    }

    protected $fillable = [
        'company_id',
        'conversation_id',
        'user_id',
        'question',
        'answer',
        'citations',
        'images',
    ];

    protected $casts = [
        'citations' => 'array',
        'images'    => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(DocumentationConversation::class, 'conversation_id');
    }
}
