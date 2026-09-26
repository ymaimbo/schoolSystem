<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BuildsExamPagePayload;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class SubjectTeacherPortalController extends Controller
{
    use BuildsExamPagePayload;

    public function index(Request $request): Response
    {
        $school = app('currentSchool');
        $user = $request->user();

        $assignments = $this->subjectAssignments((int) $school->id, (int) $user->id);

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

        $this->applyExamScopeFromAssignments($examsQuery, $assignments);

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

        $this->applyStudentScopeFromAssignments($studentsQuery, $assignments);

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
            'subject_teacher',
            [
                'canCreateExams' => false,
                'canDeleteExams' => false,
                'canUpdateAnyResult' => false,
                'canUpdateClassResults' => false,
                'canUpdateSubjectResults' => true,
            ],
            [],
            $assignments->values()->all()
        ));
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

        $assignments = $this->subjectAssignments((int) $school->id, (int) $user->id);

        if ($assignments->isEmpty()) {
            return back()->withErrors(['exam' => 'You are not assigned to any class/subject combination.']);
        }

        if (Schema::hasColumn('exams', 'school_id') && (int) $exam->school_id !== (int) $school->id) {
            abort(404);
        }

        $student = Student::query()->findOrFail($validated['student_id']);

        if (Schema::hasColumn('students', 'school_id') && (int) $student->school_id !== (int) $school->id) {
            abort(404);
        }

        if (
            Schema::hasColumn('exams', 'class_level') &&
            Schema::hasColumn('students', 'class_level') &&
            (string) $student->class_level !== (string) $exam->class_level
        ) {
            return back()->withErrors(['exam' => 'Student does not belong to the exam class.']);
        }

        if (
            Schema::hasColumn('exams', 'stream') &&
            Schema::hasColumn('students', 'stream') &&
            (string) ($student->stream ?? '') !== (string) ($exam->stream ?? '')
        ) {
            return back()->withErrors(['exam' => 'Student stream does not match exam stream.']);
        }

        if (! $this->isAssignmentAllowedForExamAndStudent($assignments, $exam, $student)) {
            return back()->withErrors(['exam' => 'You can only enter marks for your assigned subject in your assigned class.']);
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

        return back()->with('success', 'Subject marks saved successfully.');
    }

    public function discipline(Request $request): Response
    {
        return Inertia::render('Admin/ClassRoom/Discipline', [
            'role' => 'subject_teacher',
            'subjectAssignments' => $this->subjectAssignments((int) app('currentSchool')->id, (int) $request->user()->id)->values(),
            'message' => 'Discipline view placeholder for subject teacher.',
        ]);
    }

    private function applyExamScopeFromAssignments(Builder $query, Collection $assignments): void
    {
        if ($assignments->isEmpty()) {
            $query->whereRaw('1 = 0');
            return;
        }

        if (! Schema::hasColumn('exams', 'class_level')) {
            $query->whereRaw('1 = 0');
            return;
        }

        $hasExamStream = Schema::hasColumn('exams', 'stream');
        $examSubjectColumn = $this->detectExamSubjectColumn();

        $query->where(function ($scope) use ($assignments, $hasExamStream, $examSubjectColumn): void {
            foreach ($assignments as $assignment) {
                $scope->orWhere(function ($q) use ($assignment, $hasExamStream, $examSubjectColumn): void {
                    $q->where('class_level', $assignment['class_level']);

                    if ($hasExamStream && $assignment['stream'] !== '') {
                        $q->where('stream', $assignment['stream']);
                    }

                    if ($examSubjectColumn !== null && $assignment['subject_token'] !== null) {
                        $q->where($examSubjectColumn, $assignment['subject_token']);
                    }
                });
            }
        });
    }

    private function applyStudentScopeFromAssignments(Builder $query, Collection $assignments): void
    {
        if ($assignments->isEmpty()) {
            $query->whereRaw('1 = 0');
            return;
        }

        if (! Schema::hasColumn('students', 'class_level')) {
            $query->whereRaw('1 = 0');
            return;
        }

        $hasStudentStream = Schema::hasColumn('students', 'stream');

        $query->where(function ($scope) use ($assignments, $hasStudentStream): void {
            foreach ($assignments as $assignment) {
                $scope->orWhere(function ($q) use ($assignment, $hasStudentStream): void {
                    $q->where('class_level', $assignment['class_level']);

                    if ($hasStudentStream && $assignment['stream'] !== '') {
                        $q->where('stream', $assignment['stream']);
                    }
                });
            }
        });
    }

    private function isAssignmentAllowedForExamAndStudent(Collection $assignments, Exam $exam, Student $student): bool
    {
        $examSubjectToken = $this->resolveExamSubjectToken($exam);
        $examHasSubject = $examSubjectToken !== null;

        return $assignments->contains(function (array $assignment) use ($exam, $student, $examHasSubject, $examSubjectToken): bool {
            $classMatchesStudent = (string) $assignment['class_level'] === (string) ($student->class_level ?? '');
            if (! $classMatchesStudent) {
                return false;
            }

            $assignmentStream = (string) ($assignment['stream'] ?? '');
            if ($assignmentStream !== '') {
                if ((string) ($student->stream ?? '') !== $assignmentStream) {
                    return false;
                }

                if (Schema::hasColumn('exams', 'stream') && (string) ($exam->stream ?? '') !== $assignmentStream) {
                    return false;
                }
            }

            if (Schema::hasColumn('exams', 'class_level') && (string) ($exam->class_level ?? '') !== (string) $assignment['class_level']) {
                return false;
            }

            if (! $examHasSubject) {
                return true;
            }

            return $assignment['subject_token'] !== null
                && (string) $assignment['subject_token'] === (string) $examSubjectToken;
        });
    }

    private function detectExamSubjectColumn(): ?string
    {
        foreach (['subject_id', 'subject_code', 'subject_name', 'subject'] as $column) {
            if (Schema::hasColumn('exams', $column)) {
                return $column;
            }
        }

        return null;
    }

    private function resolveExamSubjectToken(Exam $exam): string|int|null
    {
        $column = $this->detectExamSubjectColumn();

        if (! $column) {
            return null;
        }

        $value = $exam->{$column} ?? null;
        return ($value === null || $value === '') ? null : $value;
    }

    private function subjectAssignments(int $schoolId, int $userId): Collection
    {
        $table = $this->detectSubjectAssignmentTable();

        if (! $table) {
            return collect();
        }

        $query = DB::table($table);

        if (Schema::hasColumn($table, 'school_id')) {
            $query->where('school_id', $schoolId);
        }

        if (Schema::hasColumn($table, 'user_id')) {
            $query->where('user_id', $userId);
        } elseif (Schema::hasColumn($table, 'teacher_id')) {
            $query->where('teacher_id', $userId);
        } else {
            return collect();
        }

        if (Schema::hasColumn($table, 'is_active')) {
            $query->where('is_active', true);
        }

        return $query->get()
            ->map(function ($row) {
                $subjectToken = null;

                foreach (['subject_id', 'subject_code', 'subject_name', 'subject'] as $column) {
                    if (property_exists($row, $column) && $row->{$column} !== null && $row->{$column} !== '') {
                        $subjectToken = $row->{$column};
                        break;
                    }
                }

                return [
                    'class_level' => (string) ($row->class_level ?? ''),
                    'stream' => (string) ($row->stream ?? ''),
                    'subject_token' => $subjectToken,
                ];
            })
            ->filter(fn (array $item) => $item['class_level'] !== '')
            ->unique(fn (array $item) => $item['class_level'] . '|' . $item['stream'] . '|' . (string) ($item['subject_token'] ?? ''))
            ->values();
    }

    private function detectSubjectAssignmentTable(): ?string
    {
        foreach ([
            'subject_teacher_assignments',
            'teacher_subject_assignments',
            'teacher_subjects',
            'subject_teacher_mappings',
        ] as $table) {
            if (Schema::hasTable($table)) {
                return $table;
            }
        }

        return null;
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