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

        // 1. Super Admin
        User::updateOrCreate(
            ['email' => $superadminEmail],
            [
                'name'       => 'Super Admin',
                'email'      => $superadminEmail,
                'password'   => Hash::make($superadminPassword),
                'role'       => 'superadmin',
                'company_id' => null,
                'manager_id' => null,
                'divisi'     => null,
            ]
        );

        // 2. Manager IT (Company 1)
        $managerIt = User::updateOrCreate(
            ['email' => 'managerit@gmail.com'],
            [
                'name'       => 'Manager IT',
                'email'      => 'managerit@gmail.com',
                'password'   => Hash::make('password'),
                'role'       => 'manager',
                'divisi'     => 'IT Support',
                'company_id' => 1,
                'manager_id' => null,
            ]
        );

        // 3. Staff IT (Company 1, manager_id = Manager IT)
        User::updateOrCreate(
            ['email' => 'it'],
            [
                'name'       => 'Staff IT Operasional',
                'email'      => 'it',
                'password'   => Hash::make('it'),
                'role'       => 'staff',
                'divisi'     => 'IT Support',
                'company_id' => 1,
                'manager_id' => $managerIt->id,
            ]
        );

        // 4. Manager GA (Company 2)
        $managerGa = User::updateOrCreate(
            ['email' => 'managerga@gmail.com'],
            [
                'name'       => 'Manager GA',
                'email'      => 'managerga@gmail.com',
                'password'   => Hash::make('password'),
                'role'       => 'manager',
                'divisi'     => 'General Affairs',
                'company_id' => 2,
                'manager_id' => null,
            ]
        );

        // 5. Staff GA (Company 2, manager_id = Manager GA)
        User::updateOrCreate(
            ['email' => 'ga'],
            [
                'name'       => 'Staff CCTV & GA',
                'email'      => 'ga',
                'password'   => Hash::make('ga'),
                'role'       => 'staff',
                'divisi'     => 'General Affairs',
                'company_id' => 2,
                'manager_id' => $managerGa->id,
            ]
        );

        // 6. Alias Akun Demo untuk Kemudahan Login & Kompatibilitas
        User::updateOrCreate(
            ['email' => 'staff@gonote.id'],
            [
                'name'       => 'Demo Staff IT',
                'email'      => 'staff@gonote.id',
                'password'   => Hash::make('password'),
                'role'       => 'staff',
                'divisi'     => 'IT Support',
                'company_id' => 1,
                'manager_id' => $managerIt->id,
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@hsitoperasional.com'],
            [
                'name'       => 'Demo Admin IT',
                'email'      => 'admin@hsitoperasional.com',
                'password'   => Hash::make('admin'),
                'role'       => 'manager',
                'divisi'     => 'IT Management',
                'company_id' => 1,
                'manager_id' => null,
            ]
        );

        // 7. Seed Default Notulen Rules untuk Company 2 (General Affairs)
        \App\Models\Setting::updateOrCreate(
            ['company_id' => 2, 'key' => 'gemini_priority_rules'],
            ['value' => \App\Services\GeminiService::getDefaultPriorityRules(2)]
        );
        \App\Models\Setting::updateOrCreate(
            ['company_id' => 2, 'key' => 'gemini_status_rules'],
            ['value' => \App\Services\GeminiService::getDefaultStatusRules(2)]
        );
        \App\Models\Setting::updateOrCreate(
            ['company_id' => 2, 'key' => 'gemini_action_rules'],
            ['value' => \App\Services\GeminiService::getDefaultActionRules(2)]
        );
    }
}
