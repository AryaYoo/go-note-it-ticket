<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
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
