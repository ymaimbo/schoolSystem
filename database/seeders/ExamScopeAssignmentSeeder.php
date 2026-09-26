<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExamScopeAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $schoolId = 1;

        if (!Schema::hasTable('users')) {
            $this->command?->warn('users table missing. Skipping ExamScopeAssignmentSeeder.');
            return;
        }

        // Resolve users by tolerant role matching
        $principalId = $this->findUserIdByRoles(['principal']);
        $deputyId = $this->findUserIdByRoles(['deputy_principal', 'deputy-principal', 'deputy principal']);
        $deanId = $this->findUserIdByRoles(['dean']);
        $classTeacherId = $this->findUserIdByRoles(['class_teacher', 'class-teacher', 'class teacher']);
        $subjectTeacherId = $this->findUserIdByRoles(['subject_teacher', 'subject-teacher', 'subject teacher']);

        // Canonicalize role labels (safe if user exists)
        $this->setRoleIfFound($principalId, 'principal');
        $this->setRoleIfFound($deputyId, 'deputy_principal');
        $this->setRoleIfFound($deanId, 'dean');
        $this->setRoleIfFound($classTeacherId, 'class_teacher');
        $this->setRoleIfFound($subjectTeacherId, 'subject_teacher');

        // Apply school_id if supported
        if (Schema::hasColumn('users', 'school_id')) {
            foreach ([$principalId, $deputyId, $deanId, $classTeacherId, $subjectTeacherId] as $uid) {
                if ($uid) {
                    DB::table('users')->where('id', $uid)->update(['school_id' => $schoolId]);
                }
            }
        }

        // -------------------------
        // CLASS TEACHER ASSIGNMENT
        // -------------------------
        if (!Schema::hasTable('class_teacher_assignments')) {
            $this->command?->warn('class_teacher_assignments table missing. Skipping class assignment seed.');
        } elseif (!$classTeacherId) {
            $this->command?->warn('No class_teacher user found. Skipping class assignment seed.');
        } else {
            $teacherCol = $this->resolveTeacherColumn('class_teacher_assignments');

            if (!$teacherCol) {
                $this->command?->warn('No teacher key found in class_teacher_assignments. Skipping class assignment seed.');
            } else {
                $exists = DB::table('class_teacher_assignments')
                    ->where($teacherCol, $classTeacherId)
                    ->where('class_level', 'Form 3')
                    ->when(
                        Schema::hasColumn('class_teacher_assignments', 'stream'),
                        fn ($q) => $q->where('stream', 'North')
                    )
                    ->exists();

                if (!$exists) {
                    $payload = [
                        $teacherCol => $classTeacherId,
                        'class_level' => 'Form 3',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if (Schema::hasColumn('class_teacher_assignments', 'stream')) {
                        $payload['stream'] = 'North';
                    }
                    if (Schema::hasColumn('class_teacher_assignments', 'is_active')) {
                        $payload['is_active'] = true;
                    }
                    if (Schema::hasColumn('class_teacher_assignments', 'school_id')) {
                        $payload['school_id'] = $schoolId;
                    }

                    DB::table('class_teacher_assignments')->insert($payload);
                    $this->command?->info("Inserted class_teacher_assignment ({$teacherCol}={$classTeacherId}).");
                } else {
                    $this->command?->line('Class teacher assignment already exists. Skipped.');
                }
            }
        }

        // --------------------------
        // SUBJECT TEACHER ASSIGNMENT
        // --------------------------
        if (!Schema::hasTable('subject_teacher_assignments')) {
            $this->command?->warn('subject_teacher_assignments table missing. Skipping subject assignment seed.');
        } elseif (!$subjectTeacherId) {
            $this->command?->warn('No subject_teacher user found. Skipping subject assignment seed.');
        } else {
            $teacherCol = $this->resolveTeacherColumn('subject_teacher_assignments');

            if (!$teacherCol) {
                $this->command?->warn('No teacher key found in subject_teacher_assignments. Skipping subject assignment seed.');
            } else {
                $records = [
                    ['subject' => 'Mathematics', 'class_level' => 'Form 3', 'stream' => 'North'],
                    ['subject' => 'English', 'class_level' => 'Form 3', 'stream' => 'North'],
                    ['subject' => 'Biology', 'class_level' => 'Form 3', 'stream' => null], // null stream => all streams
                ];

                foreach ($records as $row) {
                    $exists = DB::table('subject_teacher_assignments')
                        ->where($teacherCol, $subjectTeacherId)
                        ->where('subject', $row['subject'])
                        ->where('class_level', $row['class_level'])
                        ->when(
                            Schema::hasColumn('subject_teacher_assignments', 'stream'),
                            fn ($q) => $q->whereRaw('COALESCE(stream, "") = ?', [$row['stream'] ?? ''])
                        )
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    $payload = [
                        $teacherCol => $subjectTeacherId,
                        'subject' => $row['subject'],
                        'class_level' => $row['class_level'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if (Schema::hasColumn('subject_teacher_assignments', 'stream')) {
                        $payload['stream'] = $row['stream'];
                    }
                    if (Schema::hasColumn('subject_teacher_assignments', 'is_active')) {
                        $payload['is_active'] = true;
                    }
                    if (Schema::hasColumn('subject_teacher_assignments', 'school_id')) {
                        $payload['school_id'] = $schoolId;
                    }

                    DB::table('subject_teacher_assignments')->insert($payload);
                }

                $this->command?->info("Seeded subject_teacher_assignments ({$teacherCol}={$subjectTeacherId}).");
            }
        }

        // ------------------
        // OPTIONAL EXAM SEED
        // ------------------
        if (Schema::hasTable('exams')) {
            // Ensure subject is always set if column exists
            if (Schema::hasColumn('exams', 'subject')) {
                DB::table('exams')
                    ->whereNull('subject')
                    ->orWhere('subject', '')
                    ->update(['subject' => 'General']);
            }

            $sampleExams = [
                [
                    'title' => 'Mid-Term Assessment',
                    'term' => 'Term 1',
                    'year' => 2026,
                    'exam_date' => now()->toDateString(),
                    'max_score' => 100,
                    'status' => 'published',
                    'assessment_system' => '844',
                    'class_level' => 'Form 3',
                    'stream' => 'North',
                    'subject' => 'Mathematics',
                ],
                [
                    'title' => 'End-Term Examination',
                    'term' => 'Term 1',
                    'year' => 2026,
                    'exam_date' => now()->toDateString(),
                    'max_score' => 100,
                    'status' => 'draft',
                    'assessment_system' => '844',
                    'class_level' => 'Form 3',
                    'stream' => 'North',
                    'subject' => 'English',
                ],
            ];

            foreach ($sampleExams as $exam) {
                $exists = DB::table('exams')
                    ->where('title', $exam['title'])
                    ->where('term', $exam['term'])
                    ->where('year', $exam['year'])
                    ->exists();

                if ($exists) {
                    continue;
                }

                $payload = [
                    'title' => $exam['title'],
                    'term' => $exam['term'],
                    'year' => $exam['year'],
                    'exam_date' => $exam['exam_date'],
                    'max_score' => $exam['max_score'],
                    'status' => $exam['status'],
                    'assessment_system' => $exam['assessment_system'],
                    'class_level' => $exam['class_level'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (Schema::hasColumn('exams', 'stream')) {
                    $payload['stream'] = $exam['stream'];
                }
                if (Schema::hasColumn('exams', 'subject')) {
                    $payload['subject'] = $exam['subject'];
                }
                if (Schema::hasColumn('exams', 'school_id')) {
                    $payload['school_id'] = $schoolId;
                }

                DB::table('exams')->insert($payload);
            }

            $this->command?->info('Sample exams seeded/verified.');
        } else {
            $this->command?->warn('exams table missing. Skipping sample exam seed.');
        }

        $this->command?->info('ExamScopeAssignmentSeeder completed safely.');
    }

    protected function resolveTeacherColumn(string $table): ?string
    {
        foreach (['teacher_id', 'user_id', 'staff_id', 'class_teacher_id'] as $candidate) {
            if (Schema::hasColumn($table, $candidate)) {
                return $candidate;
            }
        }
        return null;
    }

    protected function findUserIdByRoles(array $roles): ?int
    {
        // exact first
        $exact = DB::table('users')->whereIn('role', $roles)->orderBy('id')->first();
        if ($exact) {
            return (int) $exact->id;
        }

        // normalized fallback
        $normalizedTargets = array_map(fn ($r) => $this->normalizeRole($r), $roles);
        $all = DB::table('users')->select('id', 'role')->get();

        foreach ($all as $u) {
            if (in_array($this->normalizeRole((string) $u->role), $normalizedTargets, true)) {
                return (int) $u->id;
            }
        }

        return null;
    }

    protected function normalizeRole(string $role): string
    {
        return str_replace([' ', '-'], '_', strtolower(trim($role)));
    }

    protected function setRoleIfFound(?int $id, string $canonicalRole): void
    {
        if (!$id) {
            return;
        }

        DB::table('users')
            ->where('id', $id)
            ->update(['role' => $canonicalRole, 'updated_at' => now()]);
    }
}