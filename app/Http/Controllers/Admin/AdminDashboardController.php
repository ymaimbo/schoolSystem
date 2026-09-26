<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassTeacherAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __invoke(): Response
    {
        $user = auth()->user();
        $school = app('currentSchool');

        $cards = $this->buildCommonCards((int) $school->id);
        $principalClassStats = null;

        if ($user && $user->role === 'principal') {
            $principalClassStats = $this->buildPrincipalClassStats((int) $school->id);
        }

        return Inertia::render('Admin/Dashboard', [
            'role' => $user?->role ?? '',
            'cards' => $cards,
            'principalClassStats' => $principalClassStats,
        ]);
    }

    private function buildCommonCards(int $schoolId): array
    {
        $studentsCount = $this->countWhereSchool('students', $schoolId);
        $staffCount = $this->countWhereSchool('staff_records', $schoolId);
        $teachersCount = $this->countUsersByRole($schoolId, 'class_teacher');
        $examsCount = $this->countWhereSchool('exams', $schoolId);

        return [
            ['title' => 'Students', 'value' => (string) $studentsCount, 'hint' => 'Active learners'],
            ['title' => 'Staff Records', 'value' => (string) $staffCount, 'hint' => 'Teaching and non-teaching'],
            ['title' => 'Class Teachers', 'value' => (string) $teachersCount, 'hint' => 'Users with class_teacher role'],
            ['title' => 'Exams', 'value' => (string) $examsCount, 'hint' => 'Total exams created'],
        ];
    }

    private function buildPrincipalClassStats(int $schoolId): array
    {
        $assignedTeachers = 0;
        $unassignedTeachers = 0;
        $classesWithoutAssignment = 0;

        if (Schema::hasTable('users')) {
            $totalClassTeachers = User::query()
                ->where('school_id', $schoolId)
                ->where('role', 'class_teacher')
                ->count();

            if (Schema::hasTable('class_teacher_assignments')) {
                $assignedTeachers = ClassTeacherAssignment::query()
                    ->where('school_id', $schoolId)
                    ->where('is_active', true)
                    ->distinct('user_id')
                    ->count('user_id');
            }

            $unassignedTeachers = max(0, (int) $totalClassTeachers - (int) $assignedTeachers);
        }

        if (Schema::hasTable('students') && Schema::hasTable('class_teacher_assignments')) {
            $studentClassPairs = DB::table('students')
                ->where('school_id', $schoolId)
                ->whereNotNull('class_level')
                ->select('class_level', 'stream')
                ->distinct()
                ->get()
                ->map(fn ($row) => $this->classPairKey($row->class_level, $row->stream))
                ->values();

            $assignmentPairs = DB::table('class_teacher_assignments')
                ->where('school_id', $schoolId)
                ->where('is_active', true)
                ->select('class_level', 'stream')
                ->distinct()
                ->get()
                ->map(fn ($row) => $this->classPairKey($row->class_level, $row->stream))
                ->values();

            $classesWithoutAssignment = $studentClassPairs
                ->diff($assignmentPairs)
                ->count();
        }

        return [
            'assigned_class_teachers' => (int) $assignedTeachers,
            'unassigned_class_teachers' => (int) $unassignedTeachers,
            'classes_without_assignment' => (int) $classesWithoutAssignment,
        ];
    }

    private function countWhereSchool(string $table, int $schoolId): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'school_id')) {
            return 0;
        }

        return (int) DB::table($table)->where('school_id', $schoolId)->count();
    }

    private function countUsersByRole(int $schoolId, string $role): int
    {
        if (! Schema::hasTable('users')) {
            return 0;
        }

        return (int) User::query()
            ->where('school_id', $schoolId)
            ->where('role', $role)
            ->count();
    }

    private function classPairKey(?string $classLevel, ?string $stream): string
    {
        return trim((string) $classLevel).'|'.trim((string) ($stream ?? ''));
    }
}