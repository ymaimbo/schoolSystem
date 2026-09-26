<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VerifyExamScope extends Command
{
    protected $signature = 'exams:verify-scope
                            {--school_id= : Optional school id scope}
                            {--details : Show detailed rows for each failed check}';

    protected $description = 'Verify exam subject completeness, class/stream integrity, and subject-teacher assignment coverage safely';

    public function handle(): int
    {
        $schoolId = $this->option('school_id');
        $details = (bool) $this->option('details');

        $this->info('Running exam scope verification...');
        $this->line('Scope: ' . ($schoolId !== null && $schoolId !== '' ? $schoolId : 'ALL_SCHOOLS'));
        $this->newLine();

        if (!Schema::hasTable('exams')) {
            $this->error("Table 'exams' not found. Cannot run verification.");
            return self::FAILURE;
        }

        // Exams schema flags
        $hasExamSchoolId   = Schema::hasColumn('exams', 'school_id');
        $hasExamSubject    = Schema::hasColumn('exams', 'subject');
        $hasExamClassLevel = Schema::hasColumn('exams', 'class_level');
        $hasExamStream     = Schema::hasColumn('exams', 'stream');

        // Assignment schema flags
        $hasAssignmentsTable     = Schema::hasTable('subject_teacher_assignments');
        $hasAssignSchoolId       = $hasAssignmentsTable && Schema::hasColumn('subject_teacher_assignments', 'school_id');
        $hasAssignSubject        = $hasAssignmentsTable && Schema::hasColumn('subject_teacher_assignments', 'subject');
        $hasAssignClassLevel     = $hasAssignmentsTable && Schema::hasColumn('subject_teacher_assignments', 'class_level');
        $hasAssignStream         = $hasAssignmentsTable && Schema::hasColumn('subject_teacher_assignments', 'stream');
        $hasAssignIsActive       = $hasAssignmentsTable && Schema::hasColumn('subject_teacher_assignments', 'is_active');

        if (($schoolId !== null && $schoolId !== '') && !$hasExamSchoolId) {
            $this->warn("Option --school_id ignored for exams: column 'exams.school_id' does not exist.");
        }

        // Base exam query
        $examBase = DB::table('exams as e');
        if (($schoolId !== null && $schoolId !== '') && $hasExamSchoolId) {
            $examBase->where('e.school_id', $schoolId);
        }

        $totalExams = (clone $examBase)->count();

        $metrics = [];

        // 1) Subject completeness
        $missingSubjectCount = null;
        if ($hasExamSubject) {
            $missingSubjectCount = (clone $examBase)
                ->whereRaw("TRIM(COALESCE(e.subject, '')) = ''")
                ->count();

            $metrics[] = ['Metric' => 'Exams missing subject', 'Value' => $missingSubjectCount];
        } else {
            $metrics[] = ['Metric' => 'Exams missing subject', 'Value' => 'SKIPPED (missing exams.subject)'];
        }

        // 2) class_level / stream integrity
        $missingClassLevelCount = null;
        $missingStreamWithClassCount = null;

        if ($hasExamClassLevel) {
            $missingClassLevelCount = (clone $examBase)
                ->whereRaw("TRIM(COALESCE(e.class_level, '')) = ''")
                ->count();
            $metrics[] = ['Metric' => 'Exams missing class_level', 'Value' => $missingClassLevelCount];
        } else {
            $metrics[] = ['Metric' => 'Exams missing class_level', 'Value' => 'SKIPPED (missing exams.class_level)'];
        }

        if ($hasExamClassLevel && $hasExamStream) {
            $missingStreamWithClassCount = (clone $examBase)
                ->whereRaw("TRIM(COALESCE(e.class_level, '')) <> ''")
                ->whereRaw("TRIM(COALESCE(e.stream, '')) = ''")
                ->count();
            $metrics[] = ['Metric' => 'Exams missing stream when class_level exists', 'Value' => $missingStreamWithClassCount];
        } elseif (!$hasExamStream) {
            $metrics[] = ['Metric' => 'Exams missing stream when class_level exists', 'Value' => 'SKIPPED (missing exams.stream)'];
        } else {
            $metrics[] = ['Metric' => 'Exams missing stream when class_level exists', 'Value' => 'SKIPPED (missing exams.class_level)'];
        }

        // 3) subject-teacher assignment coverage
        $uncoveredCount = null;
        $coverageRunnable =
            $hasAssignmentsTable &&
            $hasExamSubject && $hasExamClassLevel &&
            $hasAssignSubject && $hasAssignClassLevel;

        if ($coverageRunnable) {
            $uncoveredQuery = (clone $examBase)->whereNotExists(function ($sub) use (
                $schoolId,
                $hasExamSchoolId,
                $hasAssignSchoolId,
                $hasAssignIsActive,
                $hasAssignStream,
                $hasExamStream
            ) {
                $sub->selectRaw('1')
                    ->from('subject_teacher_assignments as a')
                    ->whereRaw("LOWER(TRIM(COALESCE(a.subject, ''))) = LOWER(TRIM(COALESCE(e.subject, '')))")
                    ->whereRaw("LOWER(TRIM(COALESCE(a.class_level, ''))) = LOWER(TRIM(COALESCE(e.class_level, '')))");

                // school scope in assignments (only when both sides support school_id and option provided)
                if (($schoolId !== null && $schoolId !== '') && $hasExamSchoolId && $hasAssignSchoolId) {
                    $sub->where('a.school_id', $schoolId);
                }

                // active assignment filter if column exists
                if ($hasAssignIsActive) {
                    $sub->where('a.is_active', true);
                }

                // stream matching rule:
                // exact stream OR assignment stream blank (wildcard)
                if ($hasAssignStream && $hasExamStream) {
                    $sub->whereRaw("
                        (
                            LOWER(TRIM(COALESCE(a.stream, ''))) = LOWER(TRIM(COALESCE(e.stream, '')))
                            OR TRIM(COALESCE(a.stream, '')) = ''
                        )
                    ");
                }
            });

            $uncoveredCount = (clone $uncoveredQuery)->count();
            $metrics[] = ['Metric' => 'Exams without subject-teacher assignment coverage', 'Value' => $uncoveredCount];
        } else {
            $missing = [];
            if (!$hasAssignmentsTable) $missing[] = 'missing table subject_teacher_assignments';
            if (!$hasExamSubject) $missing[] = 'missing exams.subject';
            if (!$hasExamClassLevel) $missing[] = 'missing exams.class_level';
            if (!$hasAssignSubject) $missing[] = 'missing subject_teacher_assignments.subject';
            if (!$hasAssignClassLevel) $missing[] = 'missing subject_teacher_assignments.class_level';

            $metrics[] = [
                'Metric' => 'Exams without subject-teacher assignment coverage',
                'Value'  => 'SKIPPED (' . implode(', ', $missing) . ')',
            ];
        }

        array_unshift($metrics, ['Metric' => 'Total exams in scope', 'Value' => $totalExams]);

        $this->table(['Metric', 'Value'], $metrics);

        // -------------------
        // DETAILS MODE
        // -------------------
        if ($details) {
            $this->newLine();
            $this->info('Detailed Output');

            // A) Missing subject rows
            if ($hasExamSubject) {
                $rows = (clone $examBase)
                    ->select(
                        'e.id',
                        DB::raw("COALESCE(e.title, '-') as title"),
                        DB::raw("COALESCE(e.term, '-') as term"),
                        DB::raw("COALESCE(CAST(e.year as char), '-') as year"),
                        DB::raw($hasExamClassLevel ? "COALESCE(e.class_level, '-') as class_level" : "'N/A' as class_level"),
                        DB::raw($hasExamStream ? "COALESCE(e.stream, '-') as stream" : "'N/A' as stream"),
                        DB::raw("COALESCE(e.subject, '-') as subject"),
                        DB::raw("COALESCE(e.status, '-') as status")
                    )
                    ->whereRaw("TRIM(COALESCE(e.subject, '')) = ''")
                    ->orderByDesc('e.id')
                    ->limit(200)
                    ->get()
                    ->map(fn ($r) => (array) $r)
                    ->all();

                $this->newLine();
                $this->warn('Rows: exams missing subject');
                if (count($rows)) {
                    $this->table(['id', 'title', 'term', 'year', 'class_level', 'stream', 'subject', 'status'], $rows);
                } else {
                    $this->line('None ✅');
                }
            } else {
                $this->warn("Rows: exams missing subject - SKIPPED (missing exams.subject)");
            }

            // B) Class/stream integrity rows
            if ($hasExamClassLevel || $hasExamStream) {
                $q = (clone $examBase)
                    ->select(
                        'e.id',
                        DB::raw("COALESCE(e.title, '-') as title"),
                        DB::raw("COALESCE(e.term, '-') as term"),
                        DB::raw("COALESCE(CAST(e.year as char), '-') as year"),
                        DB::raw($hasExamClassLevel ? "COALESCE(e.class_level, '-') as class_level" : "'N/A' as class_level"),
                        DB::raw($hasExamStream ? "COALESCE(e.stream, '-') as stream" : "'N/A' as stream"),
                        DB::raw($hasExamSubject ? "COALESCE(e.subject, '-') as subject" : "'N/A' as subject"),
                        DB::raw("COALESCE(e.status, '-') as status")
                    );

                $q->where(function ($w) use ($hasExamClassLevel, $hasExamStream) {
                    if ($hasExamClassLevel) {
                        $w->orWhereRaw("TRIM(COALESCE(e.class_level, '')) = ''");
                    }
                    if ($hasExamClassLevel && $hasExamStream) {
                        $w->orWhere(function ($w2) {
                            $w2->whereRaw("TRIM(COALESCE(e.class_level, '')) <> ''")
                               ->whereRaw("TRIM(COALESCE(e.stream, '')) = ''");
                        });
                    }
                });

                $rows = $q->orderByDesc('e.id')->limit(200)->get()->map(fn ($r) => (array) $r)->all();

                $this->newLine();
                $this->warn('Rows: class/stream integrity issues');
                if (count($rows)) {
                    $this->table(['id', 'title', 'term', 'year', 'class_level', 'stream', 'subject', 'status'], $rows);
                } else {
                    $this->line('None ✅');
                }
            } else {
                $this->warn('Rows: class/stream integrity issues - SKIPPED (missing exams.class_level and exams.stream)');
            }

            // C) Uncovered assignment rows
            if ($coverageRunnable) {
                $rows = (clone $examBase)
                    ->whereNotExists(function ($sub) use (
                        $schoolId,
                        $hasExamSchoolId,
                        $hasAssignSchoolId,
                        $hasAssignIsActive,
                        $hasAssignStream,
                        $hasExamStream
                    ) {
                        $sub->selectRaw('1')
                            ->from('subject_teacher_assignments as a')
                            ->whereRaw("LOWER(TRIM(COALESCE(a.subject, ''))) = LOWER(TRIM(COALESCE(e.subject, '')))")
                            ->whereRaw("LOWER(TRIM(COALESCE(a.class_level, ''))) = LOWER(TRIM(COALESCE(e.class_level, '')))");

                        if (($schoolId !== null && $schoolId !== '') && $hasExamSchoolId && $hasAssignSchoolId) {
                            $sub->where('a.school_id', $schoolId);
                        }

                        if ($hasAssignIsActive) {
                            $sub->where('a.is_active', true);
                        }

                        if ($hasAssignStream && $hasExamStream) {
                            $sub->whereRaw("
                                (
                                    LOWER(TRIM(COALESCE(a.stream, ''))) = LOWER(TRIM(COALESCE(e.stream, '')))
                                    OR TRIM(COALESCE(a.stream, '')) = ''
                                )
                            ");
                        }
                    })
                    ->select(
                        'e.id',
                        DB::raw("COALESCE(e.title, '-') as title"),
                        DB::raw("COALESCE(e.term, '-') as term"),
                        DB::raw("COALESCE(CAST(e.year as char), '-') as year"),
                        DB::raw("COALESCE(e.class_level, '-') as class_level"),
                        DB::raw($hasExamStream ? "COALESCE(e.stream, '-') as stream" : "'N/A' as stream"),
                        DB::raw("COALESCE(e.subject, '-') as subject"),
                        DB::raw("COALESCE(e.status, '-') as status")
                    )
                    ->orderByDesc('e.id')
                    ->limit(200)
                    ->get()
                    ->map(fn ($r) => (array) $r)
                    ->all();

                $this->newLine();
                $this->warn('Rows: exams without assignment coverage');
                if (count($rows)) {
                    $this->table(['id', 'title', 'term', 'year', 'class_level', 'stream', 'subject', 'status'], $rows);
                } else {
                    $this->line('None ✅');
                }
            } else {
                $this->warn('Rows: exams without assignment coverage - SKIPPED (required schema not present)');
            }
        }

        $this->newLine();
        $this->info('Verification complete.');
        return self::SUCCESS;
    }
}