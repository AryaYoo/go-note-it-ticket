<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * CompanyScope — Isolasi data per perusahaan secara otomatis.
 *
 * Cara kerja:
 * - Jika user yang login punya company_id (manager / staff), query
 *   secara otomatis ditambahkan WHERE company_id = X.
 * - Jika user adalah superadmin (company_id = null), scope tidak aktif
 *   sehingga superadmin bisa melihat semua data.
 * - Jika tidak ada user yang login, scope tidak aktif.
 */
class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // Tidak ada user yang login → skip
        if (! auth()->check()) {
            return;
        }

        $user = auth()->user();

        // Superadmin (company_id = null) → bisa lihat semua data
        if ($user->isSuperAdmin()) {
            return;
        }

        // User biasa / manager → filter berdasarkan company_id mereka
        if ($user->company_id) {
            $builder->where($model->getTable() . '.company_id', $user->company_id);
        }
    }
}
