<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SubjectTeacherAssignmentsSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('subject_teacher_assignments')) {
            $this->command?->warn('subject_teacher_assignments table not found. Run migrations first.');
            return;
        }

        if (!Schema::hasTable('users')) {
            $this->command?->warn('users table not found.');
            return;
        }

        // Pick one subject teacher user (adjust this logic if needed)
        $subjectTeacher = DB::table('users')
            ->where('role', 'subject_teacher')
            ->orderBy('id')
            ->first();

        if (!$subjectTeacher) {
            $this->command?->warn('No user with role=subject_teacher found. Create/update one user first.');
            return;
        }

        $userId = (int) $subjectTeacher->id;

        $rows = [
            [
                'user_id' => $userId,
                'subject' => 'Mathematics',
                'class_level' => 'Form 3',
                'stream' => 'North',
                'is_active' => true,
            ],
            [
                'user_id' => $userId,
                'subject' => 'English',
                'class_level' => 'Form 3',
                'stream' => 'North',
                'is_active' => true,
            ],
            [
                // blank stream => all streams in class for this subject
                'user_id' => $userId,
                'subject' => 'Biology',
                'class_level' => 'Form 3',
                'stream' => null,
                'is_active' => true,
            ],
        ];

        foreach ($rows as $row) {
            $exists = DB::table('subject_teacher_assignments')
                ->where('user_id', $row['user_id'])
                ->where('subject', $row['subject'])
                ->where('class_level', $row['class_level'])
                ->whereRaw('COALESCE(stream, "") = ?', [$row['stream'] ?? ''])
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('subject_teacher_assignments')->insert([
                'user_id' => $row['user_id'],
                'subject' => $row['subject'],
                'class_level' => $row['class_level'],
                'stream' => $row['stream'],
                'is_active' => $row['is_active'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command?->info("Subject teacher assignments seeded for user_id={$userId}.");
    }
}