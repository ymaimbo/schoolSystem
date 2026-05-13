<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUsersSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'principal@vigurungani.ac.ke'],
            [
                'name' => 'School Principal',
                'role' => User::ROLE_PRINCIPAL,
                'password' => Hash::make('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'deputy@vigurungani.ac.ke'],
            [
                'name' => 'Deputy Principal',
                'role' => User::ROLE_DEPUTY_PRINCIPAL,
                'password' => Hash::make('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'hod@vigurungani.ac.ke'],
            [
                'name' => 'Head of Department',
                'role' => User::ROLE_HOD,
                'password' => Hash::make('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'accountant@vigurungani.ac.ke'],
            [
                'name' => 'School Accountant',
                'role' => User::ROLE_ACCOUNTANT,
                'password' => Hash::make('password'),
            ]
        );
    }
}