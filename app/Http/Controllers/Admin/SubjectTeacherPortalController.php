<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BuildsExamPagePayload;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Student;
use App\Models\SubjectTeacherAssignment;
use App\Services\GradingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class SubjectTeacherPortalController extends Controller
{
    use BuildsExamPagePayload;

    public function __construct(
        private readonly GradingService $gradingService
    ) {}

    public function index(Request $request): Response
    {
        $school = app('currentSchool');
        $user = $request->user();

        $assignments = SubjectTeacherAssignment::where('school_id', $school->id)
            ->where('teacher_id', $user->id)
            ->where('is_active', true)
            ->get();

        $filters = [
            'year' => $request->string('year')->toString() ?: null,
            'term' => $request->string('term')->toString() ?: null,
            'status' => $request->string('status')->toString() ?: null,
            'assessment_system' => $request->string('assessment_system')->toString() ?: null,
            'class_level' => $request->string('class_level')->toString() ?: null,
        ];

        $examsQuery = Exam::with(['results.student'])->where('school_id', $school->id);

        if ($assignments->isNotEmpty()) {
            $examsQuery->where(function ($scope) use ($assignments) {
                foreach ($assignments as $a) {
                    $scope->orWhere(function ($q) use ($a) {
                        $q->where('class_level', $a->class_level)
                          ->where('subject', $a->subject);
                        if (!empty($a->stream)) $q->where('stream', $a->stream);
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

        if ($assignments->isNotEmpty()) {
            $studentsQuery->where(function ($scope) use ($assignments) {
                foreach ($assignments as $a) {
                    $scope->orWhere(function ($q) use ($a) {
                        $q->where('class_level', $a->class_level);
                        if (!empty($a->stream)) $q->where('stream', $a->stream);
                    });
                }
            });
        } else {
            $studentsQuery->whereRaw('1 = 0');
        }

        $students = $studentsQuery->orderBy('first_name')->get();

        return Inertia::render('Admin/Exams/Index', $this->examPagePayload(
            $exams, $students, $filters, 'subject_teacher',
            ['canCreateExams' => false, 'canDeleteExams' => false, 'canUpdateAnyResult' => false, 'canUpdateClassResults' => false, 'canUpdateSubjectResults' => true],
            [], $assignments->toArray()
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
            abort(403, 'You can only enter marks for your assigned subject in your assigned class.');
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

        return back()->with('success', 'Subject marks saved successfully.');
    }

    public function discipline(Request $request): Response
    {
        return Inertia::render('Admin/ClassRoom/Discipline', [
            'role' => 'subject_teacher',
        ]);
    }
}