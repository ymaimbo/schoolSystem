<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClassTeacherAssignmentRequest;
use App\Http\Requests\UpdateClassTeacherAssignmentRequest;
use App\Models\ClassTeacherAssignment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class ClassTeacherAssignmentController extends Controller
{
    public function index(Request $request): Response
    {
        $school = app('currentSchool');
        $view = $request->string('view', 'all')->toString();

        $allAssignments = ClassTeacherAssignment::query()
            ->where('school_id', $school->id)
            ->with('teacher:id,name,email')
            ->orderBy('class_level')
            ->orderBy('stream')
            ->get();

        $activeAssignments = $allAssignments->where('is_active', true);
        $assignedTeacherIds = $activeAssignments->pluck('user_id')->unique()->values();

        $teachers = User::query()
            ->where('school_id', $school->id)
            ->where('role', 'class_teacher')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $unassignedTeachers = $teachers
            ->reject(fn (User $teacher) => $assignedTeacherIds->contains($teacher->id))
            ->values();

        $unassignedClasses = collect();

        if (Schema::hasTable('students')
            && Schema::hasColumn('students', 'class_level')
            && Schema::hasColumn('students', 'stream')) {
            $studentClassPairs = DB::table('students')
                ->where('school_id', $school->id)
                ->whereNotNull('class_level')
                ->select('class_level', 'stream', DB::raw('COUNT(*) as students_count'))
                ->groupBy('class_level', 'stream')
                ->get()
                ->map(fn ($row) => [
                    'class_level' => $row->class_level,
                    'stream' => $row->stream,
                    'students_count' => (int) $row->students_count,
                    'pair_key' => $this->classPairKey((string) $row->class_level, (string) ($row->stream ?? '')),
                ]);

            $activeAssignmentPairs = $activeAssignments
                ->map(fn (ClassTeacherAssignment $assignment) => $this->classPairKey(
                    (string) $assignment->class_level,
                    (string) ($assignment->stream ?? '')
                ))
                ->unique()
                ->values();

            $unassignedClasses = $studentClassPairs
                ->reject(fn (array $pair) => $activeAssignmentPairs->contains($pair['pair_key']))
                ->values()
                ->map(fn (array $pair) => [
                    'class_level' => $pair['class_level'],
                    'stream' => $pair['stream'],
                    'students_count' => $pair['students_count'],
                ]);
        }

        $assignmentsForView = match ($view) {
            'assigned' => $allAssignments->where('is_active', true)->values(),
            'all' => $allAssignments->values(),
            default => $allAssignments->values(),
        };

        return Inertia::render('Admin/ClassTeacherAssignments/Index', [
            'activeView' => $view,
            'summary' => [
                'assigned_class_teachers' => (int) $activeAssignments->pluck('user_id')->unique()->count(),
                'unassigned_class_teachers' => (int) $unassignedTeachers->count(),
                'classes_without_assignment' => (int) $unassignedClasses->count(),
            ],
            'assignments' => $assignmentsForView->map(function (ClassTeacherAssignment $assignment) {
                return [
                    'id' => $assignment->id,
                    'user_id' => $assignment->user_id,
                    'teacher_name' => optional($assignment->teacher)->name,
                    'teacher_email' => optional($assignment->teacher)->email,
                    'class_level' => $assignment->class_level,
                    'stream' => $assignment->stream,
                    'is_active' => (bool) $assignment->is_active,
                    'updated_at' => optional($assignment->updated_at)->format('Y-m-d H:i'),
                ];
            }),
            'teachers' => $teachers->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]),
            'unassignedTeachers' => $unassignedTeachers->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]),
            'unassignedClasses' => $unassignedClasses,
        ]);
    }

    public function store(StoreClassTeacherAssignmentRequest $request): RedirectResponse
    {
        $school = app('currentSchool');
        $data = $request->validated();

        ClassTeacherAssignment::query()->create([
            'school_id' => $school->id,
            'user_id' => $data['user_id'],
            'class_level' => $data['class_level'],
            'stream' => $data['stream'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return back()->with('success', 'Class teacher assignment created successfully.');
    }

    public function update(UpdateClassTeacherAssignmentRequest $request, ClassTeacherAssignment $assignment): RedirectResponse
    {
        $school = app('currentSchool');
        abort_if((int) $assignment->school_id !== (int) $school->id, 404);

        $data = $request->validated();

        $assignment->update([
            'user_id' => $data['user_id'],
            'class_level' => $data['class_level'],
            'stream' => $data['stream'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return back()->with('success', 'Class teacher assignment updated successfully.');
    }

    public function destroy(ClassTeacherAssignment $assignment): RedirectResponse
    {
        $school = app('currentSchool');
        abort_if((int) $assignment->school_id !== (int) $school->id, 404);

        $assignment->delete();

        return back()->with('success', 'Class teacher assignment removed successfully.');
    }

    private function classPairKey(string $classLevel, string $stream): string
    {
        return trim($classLevel).'|'.trim($stream);
    }
}