<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            VigurunganiSchoolSeeder::class,
            SchoolOperationsSeeder::class,
            VoteHeadSeeder::class,
            FeeStructureSeeder::class,
            SuperAdminUserSeeder::class,

            // NEW: ensure demo role users exist first
            CreateDemoTeacherUsersSeeder::class,
            ExamScopeAssignmentSeeder::class,
            AutoScopeAssignmentsFromDataSeeder::class,
        ]);
    }
}