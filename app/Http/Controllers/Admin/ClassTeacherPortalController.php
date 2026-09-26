<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BuildsExamPagePayload;
use App\Http\Controllers\Controller;
use App\Models\ClassTeacherAssignment;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class ClassTeacherPortalController extends Controller
{
    use BuildsExamPagePayload;

    public function index(Request $request): Response
    {
        $school = app('currentSchool');
        $user = $request->user();

        $assignments = ClassTeacherAssignment::query()
            ->where('school_id', $school->id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->get(['class_level', 'stream']);

        $pairs = $this->assignmentPairs($assignments);
        $classLevels = $pairs->pluck('class_level')->unique()->values();

        $filters = [
            'year' => $request->string('year')->toString() ?: null,
            'term' => $request->string('term')->toString() ?: null,
            'status' => $request->string('status')->toString() ?: null,
            'assessment_system' => $request->string('assessment_system')->toString() ?: null,
            'class_level' => $request->string('class_level')->toString() ?: null,
        ];

        $examsQuery = Exam::query()->with(['results.student']);

        if (Schema::hasColumn('exams', 'school_id')) {
            $examsQuery->where('school_id', $school->id);
        }

        if ($classLevels->isNotEmpty() && Schema::hasColumn('exams', 'class_level')) {
            $examsQuery->whereIn('class_level', $classLevels->all());
        } else {
            $examsQuery->whereRaw('1 = 0');
        }

        if (Schema::hasColumn('exams', 'stream')) {
            $examsQuery->where(function ($scope) use ($pairs): void {
                foreach ($pairs as $pair) {
                    $scope->orWhere(function ($q) use ($pair): void {
                        $q->where('class_level', $pair['class_level']);

                        if ($pair['stream'] !== '') {
                            $q->where('stream', $pair['stream']);
                        }
                    });
                }
            });
        }

        $exams = $examsQuery
            ->when($filters['year'], fn ($q, $year) => $q->where('year', (int) $year))
            ->when($filters['term'], fn ($q, $term) => $q->where('term', $term))
            ->when($filters['status'], fn ($q, $status) => $q->where('status', $status))
            ->when($filters['assessment_system'], fn ($q, $system) => $q->where('assessment_system', $system))
            ->when($filters['class_level'], fn ($q, $classLevel) => $q->where('class_level', $classLevel))
            ->latest('exam_date')
            ->paginate(10)
            ->withQueryString();

        $studentsQuery = Student::query();

        if (Schema::hasColumn('students', 'school_id')) {
            $studentsQuery->where('school_id', $school->id);
        }

        if ($classLevels->isNotEmpty() && Schema::hasColumn('students', 'class_level')) {
            $studentsQuery->whereIn('class_level', $classLevels->all());
        } else {
            $studentsQuery->whereRaw('1 = 0');
        }

        if (Schema::hasColumn('students', 'stream')) {
            $studentsQuery->where(function ($scope) use ($pairs): void {
                foreach ($pairs as $pair) {
                    $scope->orWhere(function ($q) use ($pair): void {
                        $q->where('class_level', $pair['class_level']);

                        if ($pair['stream'] !== '') {
                            $q->where('stream', $pair['stream']);
                        }
                    });
                }
            });
        }

        $students = $studentsQuery
            ->select([
                'id',
                'admission_no',
                'first_name',
                'last_name',
                'education_system',
                'class_level',
                'stream',
                'pathway',
            ])
            ->orderBy('first_name')
            ->get();

        return Inertia::render('Admin/Exams/Index', $this->examPagePayload(
            $exams,
            $students,
            $filters,
            'class_teacher',
            [
                'canCreateExams' => false,
                'canDeleteExams' => false,
                'canUpdateAnyResult' => false,
                'canUpdateClassResults' => true,
                'canUpdateSubjectResults' => false,
            ],
            $pairs->values()->all(),
            []
        ));
    }

    public function discipline(Request $request): Response
    {
        return Inertia::render('Admin/ClassRoom/Discipline', [
            'role' => 'class_teacher',
            'classAssignments' => ClassTeacherAssignment::query()
                ->where('school_id', app('currentSchool')->id)
                ->where('user_id', $request->user()->id)
                ->where('is_active', true)
                ->get(['class_level', 'stream']),
            'message' => 'Discipline module placeholder. You can wire this page to your existing discipline records.',
        ]);
    }

    public function storeExamMarks(Request $request, Exam $exam): RedirectResponse
    {
        $school = app('currentSchool');
        $user = $request->user();

        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'grading_system' => ['required', 'in:844,CBC'],
            'score' => ['nullable', 'numeric', 'min:0'],
            'grade' => ['nullable', 'string', 'max:16'],
            'points' => ['nullable', 'numeric', 'min:0'],
            'cbc_level' => ['nullable', 'in:BE,AE,ME,EE'],
            'cbc_comment' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
        ]);

        $assignmentScope = ClassTeacherAssignment::query()
            ->where('school_id', $school->id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->get(['class_level', 'stream']);

        if ($assignmentScope->isEmpty()) {
            return back()->withErrors(['exam' => 'You are not assigned to any active class.']);
        }

        if (Schema::hasColumn('exams', 'school_id') && (int) $exam->school_id !== (int) $school->id) {
            abort(404);
        }

        $student = Student::query()->findOrFail($validated['student_id']);

        if (Schema::hasColumn('students', 'school_id') && (int) $student->school_id !== (int) $school->id) {
            abort(404);
        }

        $isStudentInScope = $assignmentScope->contains(function (ClassTeacherAssignment $assignment) use ($student): bool {
            $classMatches = (string) $assignment->class_level === (string) ($student->class_level ?? '');

            if (! Schema::hasColumn('students', 'stream')) {
                return $classMatches;
            }

            $assignmentStream = (string) ($assignment->stream ?? '');
            if ($assignmentStream === '') {
                return $classMatches;
            }

            return $classMatches && $assignmentStream === (string) ($student->stream ?? '');
        });

        if (! $isStudentInScope) {
            return back()->withErrors(['exam' => 'You can only enter marks for students in your assigned class.']);
        }

        if (Schema::hasColumn('exams', 'class_level')) {
            $isExamClassInScope = $assignmentScope->contains(function (ClassTeacherAssignment $assignment) use ($exam): bool {
                $classMatches = (string) $assignment->class_level === (string) ($exam->class_level ?? '');

                if (! Schema::hasColumn('exams', 'stream')) {
                    return $classMatches;
                }

                $assignmentStream = (string) ($assignment->stream ?? '');
                if ($assignmentStream === '') {
                    return $classMatches;
                }

                return $classMatches && $assignmentStream === (string) ($exam->stream ?? '');
            });

            if (! $isExamClassInScope) {
                return back()->withErrors(['exam' => 'You can only submit marks for exams in your assigned class.']);
            }
        }

        if ($validated['grading_system'] === '844') {
            $score = (float) ($validated['score'] ?? 0);
            $validated['grade'] = $validated['grade'] ?: $this->grade844($score);
            $validated['points'] = $validated['points'] !== null && $validated['points'] !== ''
                ? (float) $validated['points']
                : $this->points844((string) $validated['grade']);
            $validated['cbc_level'] = null;
            $validated['cbc_comment'] = null;
        } else {
            $validated['grade'] = null;
            $validated['points'] = null;
            $validated['score'] = $validated['score'] !== '' ? $validated['score'] : null;
        }

        $payload = [
            'exam_id' => $exam->id,
            'student_id' => $validated['student_id'],
            'score' => $validated['score'] ?? null,
            'grade' => $validated['grade'] ?? null,
            'points' => $validated['points'] ?? null,
            'grading_system' => $validated['grading_system'],
            'cbc_level' => $validated['cbc_level'] ?? null,
            'cbc_comment' => $validated['cbc_comment'] ?? null,
            'remarks' => $validated['remarks'] ?? null,
        ];

        if (Schema::hasColumn('exam_results', 'school_id')) {
            $payload['school_id'] = $school->id;
        }

        ExamResult::query()->updateOrCreate(
            [
                'exam_id' => $exam->id,
                'student_id' => $validated['student_id'],
            ],
            $payload
        );

        return back()->with('success', 'Student marks saved successfully.');
    }

    /**
     * @param Collection<int, ClassTeacherAssignment> $assignments
     * @return Collection<int, array{class_level:string,stream:string}>
     */
    private function assignmentPairs(Collection $assignments): Collection
    {
        return $assignments
            ->map(fn (ClassTeacherAssignment $assignment) => [
                'class_level' => (string) $assignment->class_level,
                'stream' => (string) ($assignment->stream ?? ''),
            ])
            ->unique(fn (array $pair) => trim($pair['class_level']) . '|' . trim($pair['stream']))
            ->values();
    }

    private function grade844(float $score): string
    {
        return match (true) {
            $score >= 80 => 'A',
            $score >= 75 => 'A-',
            $score >= 70 => 'B+',
            $score >= 65 => 'B',
            $score >= 60 => 'B-',
            $score >= 55 => 'C+',
            $score >= 50 => 'C',
            $score >= 45 => 'C-',
            $score >= 40 => 'D+',
            $score >= 35 => 'D',
            $score >= 30 => 'D-',
            default => 'E',
        };
    }

    private function points844(string $grade): float
    {
        return match ($grade) {
            'A' => 12,
            'A-' => 11,
            'B+' => 10,
            'B' => 9,
            'B-' => 8,
            'C+' => 7,
            'C' => 6,
            'C-' => 5,
            'D+' => 4,
            'D' => 3,
            'D-' => 2,
            default => 1,
        };
    }
}