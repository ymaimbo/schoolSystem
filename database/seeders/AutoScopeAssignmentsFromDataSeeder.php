<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AutoScopeAssignmentsFromDataSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('users')) {
            $this->command?->warn('users table missing. Seeder skipped.');
            return;
        }

        if (!Schema::hasTable('exams')) {
            $this->command?->warn('exams table missing. Seeder skipped.');
            return;
        }

        $classTeacherId = $this->findUserIdByRoleFamily(['class_teacher', 'class-teacher', 'class teacher']);
        $subjectTeacherId = $this->findUserIdByRoleFamily(['subject_teacher', 'subject-teacher', 'subject teacher']);

        if (!$classTeacherId && !$subjectTeacherId) {
            $this->command?->warn('No class_teacher or subject_teacher user found. Nothing to seed.');
            return;
        }

        // Pull real scope candidates from exams first (preferred source)
        $examScopes = $this->collectExamScopes();
        if ($examScopes->isEmpty()) {
            // fallback: build from students if exams are empty
            $examScopes = $this->collectScopesFromStudents();
        }

        if ($examScopes->isEmpty()) {
            $this->command?->warn('No real class/stream/subject scope data found from exams/students.');
            return;
        }

        $classCount = 0;
        $subjectCount = 0;

        // Seed class teacher assignments from real class/stream combos
        if ($classTeacherId) {
            $classCount = $this->seedClassTeacherAssignments($classTeacherId, $examScopes);
        } else {
            $this->command?->warn('class_teacher user missing; class assignments skipped.');
        }

        // Seed subject teacher assignments from real subject+class/stream combos
        if ($subjectTeacherId) {
            $subjectCount = $this->seedSubjectTeacherAssignments($subjectTeacherId, $examScopes);
        } else {
            $this->command?->warn('subject_teacher user missing; subject assignments skipped.');
        }

        $this->command?->info("AutoScopeAssignmentsFromDataSeeder done. class={$classCount}, subject={$subjectCount}");
    }

    /**
     * Collect real scopes from exams table.
     */
    protected function collectExamScopes(): Collection
    {
        $q = DB::table('exams');

        if (!Schema::hasColumn('exams', 'class_level')) {
            return collect();
        }

        $select = ['class_level'];

        if (Schema::hasColumn('exams', 'stream')) {
            $select[] = 'stream';
        }
        if (Schema::hasColumn('exams', 'subject')) {
            $select[] = 'subject';
        }
        if (Schema::hasColumn('exams', 'school_id')) {
            $select[] = 'school_id';
        }

        $rows = $q->select($select)->get();

        return $rows
            ->map(function ($r) {
                return [
                    'class_level' => trim((string) ($r->class_level ?? '')),
                    'stream' => isset($r->stream) ? trim((string) $r->stream) : '',
                    'subject' => isset($r->subject) ? trim((string) $r->subject) : '',
                    'school_id' => $r->school_id ?? null,
                ];
            })
            ->filter(fn ($x) => $x['class_level'] !== '')
            ->unique(fn ($x) => implode('|', [$x['class_level'], $x['stream'], $x['subject'], (string) $x['school_id']]))
            ->values();
    }

    /**
     * Fallback scopes from students if exams are sparse.
     */
    protected function collectScopesFromStudents(): Collection
    {
        if (!Schema::hasTable('students') || !Schema::hasColumn('students', 'class_level')) {
            return collect();
        }

        $select = ['class_level'];
        if (Schema::hasColumn('students', 'stream')) {
            $select[] = 'stream';
        }
        if (Schema::hasColumn('students', 'school_id')) {
            $select[] = 'school_id';
        }

        $rows = DB::table('students')->select($select)->get();

        return $rows
            ->map(function ($r) {
                return [
                    'class_level' => trim((string) ($r->class_level ?? '')),
                    'stream' => isset($r->stream) ? trim((string) $r->stream) : '',
                    'subject' => '', // unknown from students fallback
                    'school_id' => $r->school_id ?? null,
                ];
            })
            ->filter(fn ($x) => $x['class_level'] !== '')
            ->unique(fn ($x) => implode('|', [$x['class_level'], $x['stream'], (string) $x['school_id']]))
            ->values();
    }

    protected function seedClassTeacherAssignments(int $userId, Collection $scopes): int
    {
        if (!Schema::hasTable('class_teacher_assignments')) {
            $this->command?->warn('class_teacher_assignments table missing. class assignment seed skipped.');
            return 0;
        }

        $teacherCol = $this->resolveTeacherColumn('class_teacher_assignments');
        if (!$teacherCol) {
            $this->command?->warn('No teacher key column found on class_teacher_assignments.');
            return 0;
        }

        $inserted = 0;

        foreach ($scopes as $scope) {
            $class = $scope['class_level'];
            $stream = $scope['stream'];
            $schoolId = $scope['school_id'];

            $existsQ = DB::table('class_teacher_assignments')
                ->where($teacherCol, $userId)
                ->where('class_level', $class);

            if (Schema::hasColumn('class_teacher_assignments', 'stream')) {
                $existsQ->whereRaw('COALESCE(stream, "") = ?', [$stream]);
            }

            if (Schema::hasColumn('class_teacher_assignments', 'school_id')) {
                $existsQ->where('school_id', $schoolId);
            }

            if ($existsQ->exists()) {
                continue;
            }

            $payload = [
                $teacherCol => $userId,
                'class_level' => $class,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('class_teacher_assignments', 'stream')) {
                $payload['stream'] = $stream !== '' ? $stream : null;
            }
            if (Schema::hasColumn('class_teacher_assignments', 'is_active')) {
                $payload['is_active'] = true;
            }
            if (Schema::hasColumn('class_teacher_assignments', 'school_id')) {
                $payload['school_id'] = $schoolId;
            }

            DB::table('class_teacher_assignments')->insert($payload);
            $inserted++;
        }

        return $inserted;
    }

    protected function seedSubjectTeacherAssignments(int $userId, Collection $scopes): int
    {
        if (!Schema::hasTable('subject_teacher_assignments')) {
            $this->command?->warn('subject_teacher_assignments table missing. subject assignment seed skipped.');
            return 0;
        }

        $teacherCol = $this->resolveTeacherColumn('subject_teacher_assignments');
        if (!$teacherCol) {
            $this->command?->warn('No teacher key column found on subject_teacher_assignments.');
            return 0;
        }

        if (!Schema::hasColumn('subject_teacher_assignments', 'subject')) {
            $this->command?->warn('subject_teacher_assignments.subject missing. subject assignment seed skipped.');
            return 0;
        }

        $inserted = 0;

        foreach ($scopes as $scope) {
            $class = $scope['class_level'];
            $stream = $scope['stream'];
            $subject = $scope['subject'] !== '' ? $scope['subject'] : 'General';
            $schoolId = $scope['school_id'];

            $existsQ = DB::table('subject_teacher_assignments')
                ->where($teacherCol, $userId)
                ->where('subject', $subject)
                ->where('class_level', $class);

            if (Schema::hasColumn('subject_teacher_assignments', 'stream')) {
                $existsQ->whereRaw('COALESCE(stream, "") = ?', [$stream]);
            }

            if (Schema::hasColumn('subject_teacher_assignments', 'school_id')) {
                $existsQ->where('school_id', $schoolId);
            }

            if ($existsQ->exists()) {
                continue;
            }

            $payload = [
                $teacherCol => $userId,
                'subject' => $subject,
                'class_level' => $class,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('subject_teacher_assignments', 'stream')) {
                $payload['stream'] = $stream !== '' ? $stream : null;
            }
            if (Schema::hasColumn('subject_teacher_assignments', 'is_active')) {
                $payload['is_active'] = true;
            }
            if (Schema::hasColumn('subject_teacher_assignments', 'school_id')) {
                $payload['school_id'] = $schoolId;
            }

            DB::table('subject_teacher_assignments')->insert($payload);
            $inserted++;
        }

        return $inserted;
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

    protected function findUserIdByRoleFamily(array $roles): ?int
    {
        $exact = DB::table('users')->whereIn('role', $roles)->orderBy('id')->first();
        if ($exact) {
            return (int) $exact->id;
        }

        $targets = array_map(fn ($r) => $this->normalizeRole($r), $roles);

        $all = DB::table('users')->select('id', 'role')->get();
        foreach ($all as $u) {
            if (in_array($this->normalizeRole((string) $u->role), $targets, true)) {
                return (int) $u->id;
            }
        }

        return null;
    }

    protected function normalizeRole(string $role): string
    {
        return str_replace([' ', '-'], '_', strtolower(trim($role)));
    }
}