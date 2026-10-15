<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyCheckItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'daily_check_id',
        'device_id',
        'is_offline',
        'offline_since',
        'affected_channels',
        'result',
        'notes',
    ];

    protected $casts = [
        'is_offline'   => 'boolean',
        'offline_since' => 'datetime',
    ];

    // Nilai result yang valid
    const RESULT_FIXED          = 'fixed';          // diperbaiki cepat, tanpa tiket
    const RESULT_NO_FAULT       = 'no_fault';       // bukan kerusakan
    const RESULT_TICKET_CREATED = 'ticket_created'; // tiket dibuat

    public function dailyCheck()
    {
        return $this->belongsTo(DailyCheck::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'daily_check_item_id');
    }

    public function resultLabel(): string
    {
        return match($this->result) {
            self::RESULT_FIXED          => 'Diperbaiki Cepat',
            self::RESULT_NO_FAULT       => 'Bukan Kerusakan',
            self::RESULT_TICKET_CREATED => 'Tiket Dibuat',
            default                     => 'Belum Diproses',
        };
    }

    public function resultBadgeClass(): string
    {
        return match($this->result) {
            self::RESULT_FIXED          => 'badge-closed',
            self::RESULT_NO_FAULT       => 'badge-low',
            self::RESULT_TICKET_CREATED => 'badge-open',
            default                     => '',
        };
    }
}
