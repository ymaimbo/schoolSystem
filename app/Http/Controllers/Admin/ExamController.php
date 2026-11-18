<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Student;
use App\Services\GradingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class ExamController extends Controller
{
    public function __construct(
        private readonly GradingService $gradingService
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $role = $this->normalizeRole((string) ($user->role ?? ''));

        $filters = [
            'year' => $request->string('year')->toString() ?: null,
            'term' => $request->string('term')->toString() ?: null,
            'status' => $request->string('status')->toString() ?: null,
            'assessment_system' => $request->string('assessment_system')->toString() ?: null,
            'class_level' => $request->string('class_level')->toString() ?: null,
        ];

        $query = Exam::query()->latest('id');

        if (Schema::hasColumn('exams', 'school_id') && !is_null($user->school_id)) {
            $query->where('school_id', $user->school_id);
        }

        foreach (['year', 'term', 'status', 'assessment_system', 'class_level'] as $k) {
            if (!empty($filters[$k])) {
                $query->where($k, $filters[$k]);
            }
        }

        $classAssignments = $this->classTeacherAssignmentsFor((int) $user->id);
        $subjectAssignments = $this->subjectTeacherAssignmentsFor((int) $user->id);

        if ($role === 'class_teacher') {
            $this->applyClassScopeToExamQuery($query, $classAssignments);
        } elseif ($role === 'subject_teacher') {
            $this->applySubjectScopeToExamQuery($query, $subjectAssignments);
        }

        $exams = $query->with(['results.student'])->paginate(20)->withQueryString();

        return Inertia::render('Admin/Exams/Index', [
            'exams' => $this->normalizeExamPaginator($exams),
            'students' => $this->studentsForScope($role, $classAssignments, $subjectAssignments, $user->school_id)->values()->all(),
            'filters' => $filters,
            'role' => $role,
            'permissions' => $this->resolvePermissions($role),
            'classAssignments' => $classAssignments->values()->all(),
            'subjectAssignments' => $subjectAssignments->values()->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $role = $this->normalizeRole((string) ($request->user()->role ?? ''));
        abort_unless(in_array($role, ['principal', 'deputy_principal', 'dean'], true), 403);

        $rules = [
            'title' => ['required', 'string', 'max:191'],
            'term' => ['required', 'string', 'max:50'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'exam_date' => ['nullable', 'date'],
            'max_score' => ['required', 'numeric', 'min:1'],
            'status' => ['required', 'in:draft,published,closed'],
            'assessment_system' => ['required', 'in:844,cbc'],
            'class_level' => ['required', 'string', 'max:120'],
            'stream' => ['nullable', 'string', 'max:120'],
            'subject' => ['nullable', 'string', 'max:120'],
            'pathway' => ['nullable', 'string', 'max:120'],
        ];

        $v = $request->validate($rules);
        $v['subject'] = $v['subject'] ?: 'General';
        $v['school_id'] = $request->user()->school_id;

        Exam::create($v);

        return back()->with('success', 'Exam created successfully.');
    }

    public function update(Request $request, Exam $exam): RedirectResponse
    {
        $role = $this->normalizeRole((string) ($request->user()->role ?? ''));
        abort_unless(in_array($role, ['principal', 'deputy_principal', 'dean'], true), 403);

        $rules = [
            'title' => ['required', 'string', 'max:191'],
            'term' => ['required', 'string', 'max:50'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'exam_date' => ['nullable', 'date'],
            'max_score' => ['required', 'numeric', 'min:1'],
            'status' => ['required', 'in:draft,published,closed'],
            'assessment_system' => ['required', 'in:844,cbc'],
            'class_level' => ['required', 'string', 'max:120'],
            'stream' => ['nullable', 'string', 'max:120'],
            'subject' => ['nullable', 'string', 'max:120'],
            'pathway' => ['nullable', 'string', 'max:120'],
        ];

        $v = $request->validate($rules);
        $v['subject'] = $v['subject'] ?: 'General';

        $exam->update($v);

        return back()->with('success', 'Exam updated successfully.');
    }

    public function destroy(Request $request, Exam $exam): RedirectResponse
    {
        $role = $this->normalizeRole((string) ($request->user()->role ?? ''));
        abort_unless(in_array($role, ['principal', 'deputy_principal', 'dean'], true), 403);

        $exam->delete();

        return back()->with('success', 'Exam deleted successfully.');
    }

    /**
     * Handles the bulk save from the Vue UI Grid.
     */
    public function storeResult(Request $request, Exam $exam): RedirectResponse
    {
        if (strtolower((string) $exam->status) === 'closed') {
            return back()->with('error', 'Closed exams cannot be edited.');
        }

        // The Vue UI sends a single row via POST when saving the grid iteratively
        $v = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'grading_system' => ['nullable', 'string', 'max:20'],
            'score' => ['nullable', 'numeric', 'min:0'],
            'grade' => ['nullable', 'string', 'max:10'],
            'points' => ['nullable', 'numeric', 'min:0'],
            'cbc_level' => ['nullable', 'string', 'max:20'],
            'cbc_comment' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
        ]);

        $student = Student::findOrFail((int) $v['student_id']);

        // Enforce Policy
        if ($request->user()->cannot('mutateResult', [ExamResult::class, $exam, $student])) {
            abort(403, 'You are not authorized to edit results for this student.');
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

        return back()->with('success', 'Result saved successfully.');
    }

    public function destroyResult(Request $request, Exam $exam, ExamResult $examResult): RedirectResponse
    {
        abort_if((int) $examResult->exam_id !== (int) $exam->id, 404);

        if (strtolower((string) $exam->status) === 'closed') {
            return back()->with('error', 'Closed exams cannot be edited.');
        }

        $student = Student::findOrFail((int) $examResult->student_id);

        if ($request->user()->cannot('mutateResult', [ExamResult::class, $exam, $student])) {
            abort(403);
        }

        $examResult->delete();

        return back()->with('success', 'Result deleted successfully.');
    }

    // ---------------------- helpers ----------------------

    protected function classTeacherAssignmentsFor(int $userId): Collection
    {
        if (!Schema::hasTable('class_teacher_assignments')) return collect();

        return DB::table('class_teacher_assignments')
            ->where('user_id', $userId)
            ->where('is_active', 1)
            ->get(['id', 'class_level', 'stream'])
            ->map(fn ($r) => [
                'id' => $r->id,
                'class_level' => $r->class_level,
                'stream' => $r->stream ?? null,
            ]);
    }

    protected function subjectTeacherAssignmentsFor(int $userId): Collection
    {
        if (!Schema::hasTable('subject_teacher_assignments')) return collect();

        return DB::table('subject_teacher_assignments')
            ->where('teacher_id', $userId)
            ->where('is_active', 1)
            ->get(['id', 'subject', 'class_level', 'stream'])
            ->map(fn ($r) => [
                'id' => $r->id,
                'subject' => $r->subject,
                'class_level' => $r->class_level,
                'stream' => $r->stream ?? null,
            ]);
    }

    protected function applyClassScopeToExamQuery($query, Collection $assignments): void
    {
        if ($assignments->isEmpty()) {
            $query->whereRaw('1=0');
            return;
        }

        $query->where(function ($outer) use ($assignments) {
            foreach ($assignments as $a) {
                $outer->orWhere(function ($inner) use ($a) {
                    $inner->where('class_level', $a['class_level']);
                    if (!empty($a['stream'])) {
                        $inner->where('stream', $a['stream']);
                    }
                });
            }
        });
    }

    protected function applySubjectScopeToExamQuery($query, Collection $assignments): void
    {
        if ($assignments->isEmpty()) {
            $query->whereRaw('1=0');
            return;
        }

        $query->where(function ($outer) use ($assignments) {
            foreach ($assignments as $a) {
                $outer->orWhere(function ($inner) use ($a) {
                    $inner->where('class_level', $a['class_level'])
                          ->where('subject', $a['subject']);
                    if (!empty($a['stream'])) {
                        $inner->where('stream', $a['stream']);
                    }
                });
            }
        });
    }

    protected function studentsForScope(string $role, Collection $classAssignments, Collection $subjectAssignments, $schoolId): Collection
    {
        $q = Student::where('school_id', $schoolId);

        if ($role === 'class_teacher' && !$classAssignments->isEmpty()) {
            $q->where(function ($outer) use ($classAssignments) {
                foreach ($classAssignments as $a) {
                    $outer->orWhere(function ($inner) use ($a) {
                        $inner->where('class_level', $a['class_level']);
                        if (!empty($a['stream'])) $inner->where('stream', $a['stream']);
                    });
                }
            });
        }

        if ($role === 'subject_teacher' && !$subjectAssignments->isEmpty()) {
            $q->where(function ($outer) use ($subjectAssignments) {
                foreach ($subjectAssignments as $a) {
                    $outer->orWhere(function ($inner) use ($a) {
                        $inner->where('class_level', $a['class_level']);
                        if (!empty($a['stream'])) $inner->where('stream', $a['stream']);
                    });
                }
            });
        }

        return $q->select(['id', 'admission_no', 'first_name', 'last_name', 'class_level', 'stream', 'education_system'])
            ->orderBy('first_name')->get();
    }

    protected function resolvePermissions(string $role): array
    {
        $isAdmin = in_array($role, ['principal', 'deputy_principal', 'dean'], true);
        return [
            'canCreateExams' => $isAdmin,
            'canDeleteExams' => $isAdmin,
            'canUpdateAnyResult' => $isAdmin,
            'canUpdateClassResults' => $isAdmin || $role === 'class_teacher',
            'canUpdateSubjectResults' => $isAdmin || $role === 'subject_teacher',
        ];
    }

    protected function normalizeExamPaginator($paginator): array
    {
        $raw = $paginator->toArray();
        $raw['data'] = collect($raw['data'] ?? [])->map(function ($exam) {
            $results = collect($exam['results'] ?? [])->map(fn ($r) => array_merge($r, [
                'student' => [
                    'id' => data_get($r, 'student.id'),
                    'admission_no' => data_get($r, 'student.admission_no'),
                    'first_name' => data_get($r, 'student.first_name'),
                    'last_name' => data_get($r, 'student.last_name'),
                    'class_level' => data_get($r, 'student.class_level'),
                    'stream' => data_get($r, 'student.stream'),
                ]
            ]))->all();

            $exam['results'] = $results;
            return $exam;
        })->all();

        return $raw;
    }

    protected function normalizeRole(string $role): string
    {
        return str_replace([' ', '-'], '_', strtolower(trim($role)));
    }
}