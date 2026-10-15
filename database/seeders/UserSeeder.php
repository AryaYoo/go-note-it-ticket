<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superadminEmail    = env('SUPERADMIN_EMAIL', 'superadmin@system.local');
        $superadminPassword = env('SUPERADMIN_PASSWORD', 'admin123');

        User::updateOrCreate(
            ['role' => 'superadmin'],
            [
                'name'       => 'Super Admin',
                'email'      => $superadminEmail,
                'password'   => Hash::make($superadminPassword),
                'role'       => 'superadmin',
                'company_id' => null,
                'divisi'     => null,
            ]
        );

        User::updateOrCreate(
            ['email' => 'it'],
            [
                'name'     => 'Staff IT',
                'email'    => 'it',
                'password' => Hash::make('it'),
                'role'     => 'staff',
                'divisi'   => 'IT Support',
            ]
        );

        User::updateOrCreate(
            ['email' => 'staff@gonote.id'],
            [
                'name'     => 'Staff IT',
                'email'    => 'staff@gonote.id',
                'password' => Hash::make('password'),
                'role'     => 'staff',
                'divisi'   => 'IT Support',
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@hsitoperasional.com'],
            [
                'name'     => 'Admin',
                'email'    => 'admin@hsitoperasional.com',
                'password' => Hash::make('admin'),
                'role'     => 'manager',
                'divisi'   => 'Management',
            ]
        );

        User::updateOrCreate(
            ['email' => 'manager@gonote.id'],
            [
                'name'     => 'Manager IT',
                'email'    => 'manager@gonote.id',
                'password' => Hash::make('password'),
                'role'     => 'manager',
                'divisi'   => 'IT Management',
            ]
        );
    }
}
