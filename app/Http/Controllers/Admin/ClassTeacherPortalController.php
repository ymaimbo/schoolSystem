<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BuildsExamPagePayload;
use App\Http\Controllers\Controller;
use App\Models\ClassTeacherAssignment;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Student;
use App\Services\GradingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class ClassTeacherPortalController extends Controller
{
    use BuildsExamPagePayload;

    public function __construct(
        private readonly GradingService $gradingService
    ) {}

    public function index(Request $request): Response
    {
        $school = app('currentSchool');
        $user = $request->user();

        $assignments = ClassTeacherAssignment::where('school_id', $school->id)
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

        $examsQuery = Exam::with(['results.student'])->where('school_id', $school->id);

        if ($classLevels->isNotEmpty()) {
            $examsQuery->where(function ($scope) use ($pairs) {
                foreach ($pairs as $pair) {
                    $scope->orWhere(function ($q) use ($pair) {
                        $q->where('class_level', $pair['class_level']);
                        if ($pair['stream'] !== '') $q->where('stream', $pair['stream']);
                    });
                }
            });
        } else {
            $examsQuery->whereRaw('1 = 0');
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

        $studentsQuery = Student::where('school_id', $school->id);

        if ($classLevels->isNotEmpty()) {
            $studentsQuery->where(function ($scope) use ($pairs) {
                foreach ($pairs as $pair) {
                    $scope->orWhere(function ($q) use ($pair) {
                        $q->where('class_level', $pair['class_level']);
                        if ($pair['stream'] !== '') $q->where('stream', $pair['stream']);
                    });
                }
            });
        } else {
            $studentsQuery->whereRaw('1 = 0');
        }

        $students = $studentsQuery->orderBy('first_name')->get();

        return Inertia::render('Admin/Exams/Index', $this->examPagePayload(
            $exams, $students, $filters, 'class_teacher',
            ['canCreateExams' => false, 'canDeleteExams' => false, 'canUpdateAnyResult' => false, 'canUpdateClassResults' => true, 'canUpdateSubjectResults' => false],
            $pairs->values()->all(), []
        ));
    }

    public function storeExamMarks(Request $request, Exam $exam): RedirectResponse
    {
        $v = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'grading_system' => ['nullable', 'string', 'max:20'],
            'score' => ['nullable', 'numeric', 'min:0'],
            'grade' => ['nullable', 'string', 'max:16'],
            'points' => ['nullable', 'numeric', 'min:0'],
            'cbc_level' => ['nullable', 'in:BE,AE,ME,EE'],
            'cbc_comment' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
        ]);

        $student = Student::findOrFail($v['student_id']);

        if ($request->user()->cannot('mutateResult', [ExamResult::class, $exam, $student])) {
            abort(403, 'You can only enter marks for students in your assigned class.');
        }

        $system = $v['grading_system'] ?? $exam->assessment_system;
        $payload = $this->gradingService->formatResultData($v, $system);
        $payload['remarks'] = $v['remarks'] ?? null;
        if (Schema::hasColumn('exam_results', 'updated_by')) {
            $payload['updated_by'] = $request->user()->id;
        }

        ExamResult::updateOrCreate(
            ['exam_id' => $exam->id, 'student_id' => $student->id],
            $payload
        );

        return back()->with('success', 'Student marks saved successfully.');
    }

    public function discipline(Request $request): Response
    {
        return Inertia::render('Admin/ClassRoom/Discipline', [
            'role' => 'class_teacher',
        ]);
    }

    private function assignmentPairs(Collection $assignments): Collection
    {
        return $assignments->map(fn ($a) => [
            'class_level' => (string) $a->class_level,
            'stream' => (string) ($a->stream ?? ''),
        ])->unique(fn ($p) => trim($p['class_level']) . '|' . trim($p['stream']))->values();
    }
}