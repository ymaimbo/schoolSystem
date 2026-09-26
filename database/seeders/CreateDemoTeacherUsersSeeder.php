<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CreateDemoTeacherUsersSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('users')) {
            $this->command?->warn('users table not found.');
            return;
        }

        $hasSchoolId = Schema::hasColumn('users', 'school_id');
        $hasName = Schema::hasColumn('users', 'name');
        $hasFirstName = Schema::hasColumn('users', 'first_name');
        $hasLastName = Schema::hasColumn('users', 'last_name');
        $hasStatus = Schema::hasColumn('users', 'status');
        $hasEmailVerifiedAt = Schema::hasColumn('users', 'email_verified_at');

        $demoUsers = [
            [
                'email' => 'principal.demo@school.local',
                'role' => 'principal',
                'name' => 'Principal Demo',
                'first_name' => 'Principal',
                'last_name' => 'Demo',
                'school_id' => 1,
            ],
            [
                'email' => 'deputy.demo@school.local',
                'role' => 'deputy_principal',
                'name' => 'Deputy Demo',
                'first_name' => 'Deputy',
                'last_name' => 'Demo',
                'school_id' => 1,
            ],
            [
                'email' => 'dean.demo@school.local',
                'role' => 'dean',
                'name' => 'Dean Demo',
                'first_name' => 'Dean',
                'last_name' => 'Demo',
                'school_id' => 1,
            ],
            [
                'email' => 'class.teacher.demo@school.local',
                'role' => 'class_teacher',
                'name' => 'Class Teacher Demo',
                'first_name' => 'Class',
                'last_name' => 'Teacher',
                'school_id' => 1,
            ],
            [
                'email' => 'subject.teacher.demo@school.local',
                'role' => 'subject_teacher',
                'name' => 'Subject Teacher Demo',
                'first_name' => 'Subject',
                'last_name' => 'Teacher',
                'school_id' => 1,
            ],
        ];

        foreach ($demoUsers as $u) {
            $existingByEmail = DB::table('users')->where('email', $u['email'])->first();

            // Build default payload
            $basePayload = [
                'role' => $u['role'],
                'updated_at' => now(),
            ];

            if ($hasName) {
                $basePayload['name'] = $u['name'];
            }
            if ($hasFirstName) {
                $basePayload['first_name'] = $u['first_name'];
            }
            if ($hasLastName) {
                $basePayload['last_name'] = $u['last_name'];
            }
            if ($hasSchoolId) {
                $basePayload['school_id'] = $u['school_id'];
            }
            if ($hasStatus) {
                $basePayload['status'] = 'active';
            }
            if ($hasEmailVerifiedAt) {
                $basePayload['email_verified_at'] = now();
            }

            if ($existingByEmail) {
                // Safe update existing row by email (never inserts duplicate)
                DB::table('users')
                    ->where('id', $existingByEmail->id)
                    ->update($basePayload);

                $this->command?->info("Updated existing user by email: {$u['email']}");
                continue;
            }

            // Create new row with password/token
            $insertPayload = array_merge($basePayload, [
                'email' => $u['email'],
                'password' => Hash::make('Password@123'),
                'remember_token' => Str::random(10),
                'created_at' => now(),
            ]);

            // In rare case DB has weird unique collisions on email aliases/casing,
            // try a deterministic fallback address.
            try {
                DB::table('users')->insert($insertPayload);
                $this->command?->info("Created demo user: {$u['email']}");
            } catch (\Throwable $e) {
                $fallbackEmail = $this->fallbackEmail($u['email']);
                $insertPayload['email'] = $fallbackEmail;

                // Double-check fallback does not exist
                if (!DB::table('users')->where('email', $fallbackEmail)->exists()) {
                    DB::table('users')->insert($insertPayload);
                    $this->command?->warn("Primary email conflict. Created with fallback email: {$fallbackEmail}");
                } else {
                    $this->command?->warn("Skipped user (email conflict unresolved): {$u['email']}");
                }
            }
        }

        $this->command?->info('CreateDemoTeacherUsersSeeder completed.');
        $this->command?->line('Default demo password (new users): Password@123');
    }

    protected function fallbackEmail(string $email): string
    {
        // principal.demo@school.local -> principal.demo+demo1@school.local
        if (!str_contains($email, '@')) {
            return $email . '+demo1';
        }

        [$local, $domain] = explode('@', $email, 2);
        return $local . '+demo1@' . $domain;
    }
}