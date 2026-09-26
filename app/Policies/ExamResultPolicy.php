<?php

namespace App\Policies;

use App\Models\Exam;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExamResultPolicy
{
    /**
     * Final objective:
     * - principal/deputy_principal/dean => full result mutation rights
     * - class_teacher => any subject, only assigned class/stream
     * - subject_teacher => only assigned subject + class/stream
     *
     * Hardened for current schema:
     * - class_teacher_assignments uses user_id
     * - subject_teacher_assignments uses teacher_id
     */
    public function mutateResult(User $user, Exam $exam, Student $student): bool
    {
        $role = $this->normalizeRole((string) ($user->role ?? ''));

        // 1) Full-access roles
        if (in_array($role, ['principal', 'deputy_principal', 'dean'], true)) {
            return true;
        }

        // 2) Basic exam/student consistency (where columns exist)
        if (!$this->studentMatchesExamScope($exam, $student)) {
            return false;
        }

        // 3) Scoped roles
        if ($role === 'class_teacher') {
            return $this->classTeacherCanMutate($user, $exam);
        }

        if ($role === 'subject_teacher') {
            return $this->subjectTeacherCanMutate($user, $exam);
        }

        return false;
    }

    /**
     * Optional explicit exam action gates.
     * Full roles allowed; others denied.
     */
    public function createExam(User $user): bool
    {
        return in_array($this->normalizeRole((string) $user->role), ['principal', 'deputy_principal', 'dean'], true);
    }

    public function updateExam(User $user, Exam $exam): bool
    {
        return in_array($this->normalizeRole((string) $user->role), ['principal', 'deputy_principal', 'dean'], true);
    }

    public function deleteExam(User $user, Exam $exam): bool
    {
        return in_array($this->normalizeRole((string) $user->role), ['principal', 'deputy_principal', 'dean'], true);
    }

    private function classTeacherCanMutate(User $user, Exam $exam): bool
    {
        if (!Schema::hasTable('class_teacher_assignments')) {
            return false;
        }

        if (!Schema::hasColumn('class_teacher_assignments', 'user_id')) {
            // Hardened to your current schema expectation
            return false;
        }

        if (!Schema::hasColumn('exams', 'class_level')) {
            return false;
        }

        $examClass = trim((string) ($exam->class_level ?? ''));
        $examStream = Schema::hasColumn('exams', 'stream') ? trim((string) ($exam->stream ?? '')) : '';

        if ($examClass === '') {
            return false;
        }

        $q = DB::table('class_teacher_assignments')
            ->where('user_id', $user->id);

        if (Schema::hasColumn('class_teacher_assignments', 'is_active')) {
            $q->where('is_active', true);
        }

        // Optional school guard if both sides have school_id
        if (
            Schema::hasColumn('class_teacher_assignments', 'school_id') &&
            Schema::hasColumn('users', 'school_id') &&
            !is_null($user->school_id)
        ) {
            $q->where('school_id', $user->school_id);
        }

        $q->whereRaw('LOWER(TRIM(COALESCE(class_level, ""))) = LOWER(?)', [$examClass]);

        if (Schema::hasColumn('class_teacher_assignments', 'stream') && Schema::hasColumn('exams', 'stream')) {
            // exact stream OR assignment stream blank => all streams
            $q->where(function ($w) use ($examStream) {
                $w->whereRaw('LOWER(TRIM(COALESCE(stream, ""))) = LOWER(?)', [$examStream])
                    ->orWhereRaw('TRIM(COALESCE(stream, "")) = ""');
            });
        }

        return $q->exists();
    }

    private function subjectTeacherCanMutate(User $user, Exam $exam): bool
    {
        if (!Schema::hasTable('subject_teacher_assignments')) {
            return false;
        }

        if (!Schema::hasColumn('subject_teacher_assignments', 'teacher_id')) {
            // Hardened to your current schema expectation
            return false;
        }

        if (!Schema::hasColumn('exams', 'subject') || !Schema::hasColumn('exams', 'class_level')) {
            return false;
        }

        $examSubject = trim((string) ($exam->subject ?? ''));
        $examClass = trim((string) ($exam->class_level ?? ''));
        $examStream = Schema::hasColumn('exams', 'stream') ? trim((string) ($exam->stream ?? '')) : '';

        if ($examSubject === '' || $examClass === '') {
            return false;
        }

        $q = DB::table('subject_teacher_assignments')
            ->where('teacher_id', $user->id);

        if (Schema::hasColumn('subject_teacher_assignments', 'is_active')) {
            $q->where('is_active', true);
        }

        // Optional school guard if both sides have school_id
        if (
            Schema::hasColumn('subject_teacher_assignments', 'school_id') &&
            Schema::hasColumn('users', 'school_id') &&
            !is_null($user->school_id)
        ) {
            $q->where('school_id', $user->school_id);
        }

        $q->whereRaw('LOWER(TRIM(COALESCE(subject, ""))) = LOWER(?)', [$examSubject])
          ->whereRaw('LOWER(TRIM(COALESCE(class_level, ""))) = LOWER(?)', [$examClass]);

        if (Schema::hasColumn('subject_teacher_assignments', 'stream') && Schema::hasColumn('exams', 'stream')) {
            // exact stream OR assignment stream blank => all streams
            $q->where(function ($w) use ($examStream) {
                $w->whereRaw('LOWER(TRIM(COALESCE(stream, ""))) = LOWER(?)', [$examStream])
                  ->orWhereRaw('TRIM(COALESCE(stream, "")) = ""');
            });
        }

        return $q->exists();
    }

    private function studentMatchesExamScope(Exam $exam, Student $student): bool
    {
        // class must match where columns exist
        if (Schema::hasColumn('exams', 'class_level') && Schema::hasColumn('students', 'class_level')) {
            $examClass = trim((string) ($exam->class_level ?? ''));
            $studentClass = trim((string) ($student->class_level ?? ''));

            if ($examClass !== '' && strcasecmp($examClass, $studentClass) !== 0) {
                return false;
            }
        }

        // if exam stream specified, student stream must match
        if (Schema::hasColumn('exams', 'stream') && Schema::hasColumn('students', 'stream')) {
            $examStream = trim((string) ($exam->stream ?? ''));
            $studentStream = trim((string) ($student->stream ?? ''));

            if ($examStream !== '' && strcasecmp($examStream, $studentStream) !== 0) {
                return false;
            }
        }

        return true;
    }

    private function normalizeRole(string $role): string
    {
        return str_replace([' ', '-'], '_', strtolower(trim($role)));
    }
}