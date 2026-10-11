<?php

namespace App\Models;

use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentationConversation extends Model
{
    use HasFactory;

    /**
     * Daftarkan CompanyScope agar query percakapan dokumentasi
     * dibatasi secara otomatis ke company_id user yang sedang login.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope());
    }

    protected $fillable = [
        'company_id',
        'user_id',
        'title',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(DocumentationChat::class, 'conversation_id')->orderBy('created_at', 'asc');
    }
}
