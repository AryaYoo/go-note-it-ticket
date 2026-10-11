<?php

namespace App\Models;

use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Daftarkan CompanyScope agar setiap query Ticket
     * otomatis dibatasi ke company_id user yang sedang login.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope());
    }

    protected $fillable = [
        'company_id',
        'nomor_tiket',
        'tanggal_kejadian',
        'nama_pelapor',
        'divisi_toko',
        'kategori',
        'prioritas',
        'deskripsi_kendala',
        'dampak_operasional',
        'lampiran_gambar',
        'pic',
        'status',
        'tindakan_dilakukan',
        'tanggal_penyelesaian',
        'solusi_diberikan',
        'root_cause',
        'user_id',
    ];

    protected $casts = [
        'tanggal_kejadian'     => 'date',
        'tanggal_penyelesaian' => 'date',
        'lampiran_gambar'      => 'array', // JSON array of paths
    ];

    // Relasi
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Scopes filter
    public function scopeFilterStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }

    public function scopeFilterKategori(Builder $query, ?string $kategori): Builder
    {
        return $kategori ? $query->where('kategori', $kategori) : $query;
    }

    public function scopeFilterPrioritas(Builder $query, ?string $prioritas): Builder
    {
        return $prioritas ? $query->where('prioritas', $prioritas) : $query;
    }

    public function scopeFilterTanggal(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->whereDate('tanggal_kejadian', '>=', $from);
        }
        if ($to) {
            $query->whereDate('tanggal_kejadian', '<=', $to);
        }
        return $query;
    }

    public function scopeFilterSearch(Builder $query, ?string $search): Builder
    {
        return $search
            ? $query->where(function ($q) use ($search) {
                $q->where('nomor_tiket', 'like', "%{$search}%")
                  ->orWhere('nama_pelapor', 'like', "%{$search}%")
                  ->orWhere('divisi_toko', 'like', "%{$search}%")
                  ->orWhere('deskripsi_kendala', 'like', "%{$search}%");
            })
            : $query;
    }

    // Helper labels
    public function statusBadgeClass(): string
    {
        return match($this->status) {
            'Open'     => 'badge-open',
            'Closed'   => 'badge-closed',
            'Eskalasi' => 'badge-eskalasi',
            default    => 'badge-open',
        };
    }

    public function prioritasBadgeClass(): string
    {
        return match($this->prioritas) {
            'Low'    => 'badge-low',
            'Medium' => 'badge-medium',
            'High'   => 'badge-high',
            default  => 'badge-low',
        };
    }

    public function histories()
    {
        return $this->hasMany(TicketHistory::class)->latest();
    }
}
