<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Buat akun superadmin pertama.
     * Ubah email/password sesuai kebutuhan sebelum migrasi, atau
     * update langsung via panel superadmin setelah migrasi.
     */
    public function up(): void
    {
        // Cek apakah sudah ada superadmin
        $exists = DB::table('users')->where('role', 'superadmin')->exists();

        if (! $exists) {
            DB::table('users')->insert([
                'company_id' => null,  // superadmin tidak terikat perusahaan
                'name'       => 'Super Admin',
                'email'      => 'superadmin@system.local',
                'password'   => Hash::make('superadmin123'),
                'role'       => 'superadmin',
                'divisi'     => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('users')->where('email', 'superadmin@system.local')->delete();
    }
};
