<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'divisi',
        'company_id',
        'manager_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isStaff(): bool
    {
        return in_array($this->role, ['staff', 'user']);
    }

    /**
     * Relasi ke perusahaan tempat user ini bernaung.
     * Superadmin tidak memiliki perusahaan (company_id = null).
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /** Manager dari staff ini */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /** Staff yang dikelola oleh manager ini */
    public function staff()
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    /** Semua user_id dalam tim (manager beserta seluruh stafnya) */
    public function teamUserIds(): array
    {
        if ($this->isManager()) {
            return $this->staff()->pluck('id')->push($this->id)->toArray();
        }

        if ($this->manager_id) {
            $manager = $this->manager ?? User::find($this->manager_id);
            if ($manager) {
                return $manager->teamUserIds();
            }
        }

        return [$this->id];
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
