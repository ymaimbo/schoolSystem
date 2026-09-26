<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'yacoubmaimbo@gmail.com'],
            [
                'name' => 'Yakoub Jombo Maimbo',
                'school_id' => null,
                'password' => Hash::make('#Agma1010'),
                'role' => 'super_admin',
                'email_verified_at' => now(),
            ]
        );
    }
}