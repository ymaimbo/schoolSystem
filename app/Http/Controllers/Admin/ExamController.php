<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class ExamController extends Controller
{
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

        // UI listing scope (write authorization still enforced by policy in mutation endpoints)
        if ($role === 'class_teacher') {
            $this->applyClassScopeToExamQuery($query, $classAssignments);
        } elseif ($role === 'subject_teacher') {
            $this->applySubjectScopeToExamQuery($query, $subjectAssignments);
        }

        $exams = $query
            ->with(['results.student'])
            ->paginate(20)
            ->withQueryString();

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
        ];

        if (Schema::hasColumn('exams', 'stream')) {
            $rules['stream'] = ['nullable', 'string', 'max:120'];
        }

        // Keep subject included for objective
        if (Schema::hasColumn('exams', 'subject')) {
            $rules['subject'] = ['nullable', 'string', 'max:120'];
        }

        if (Schema::hasColumn('exams', 'pathway')) {
            $rules['pathway'] = ['nullable', 'string', 'max:120'];
        }

        $v = $request->validate($rules);

        if (Schema::hasColumn('exams', 'subject') && empty($v['subject'])) {
            $v['subject'] = 'General';
        }

        if (Schema::hasColumn('exams', 'school_id') && !is_null($request->user()->school_id)) {
            $v['school_id'] = $request->user()->school_id;
        }

        Exam::query()->create($v);

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
        ];

        if (Schema::hasColumn('exams', 'stream')) {
            $rules['stream'] = ['nullable', 'string', 'max:120'];
        }

        if (Schema::hasColumn('exams', 'subject')) {
            $rules['subject'] = ['nullable', 'string', 'max:120'];
        }

        if (Schema::hasColumn('exams', 'pathway')) {
            $rules['pathway'] = ['nullable', 'string', 'max:120'];
        }

        $v = $request->validate($rules);

        if (Schema::hasColumn('exams', 'subject') && empty($v['subject'])) {
            $v['subject'] = 'General';
        }

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

    public function storeResult(Request $request, Exam $exam): RedirectResponse
    {
        if (strtolower((string) $exam->status) === 'closed') {
            return back()->with('error', 'Closed exams cannot be edited.');
        }

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

        $student = Student::query()->findOrFail((int) $v['student_id']);

        // central policy check
        $this->authorize('mutateResult', [$exam, $student]);

        $payload = [
            'grading_system' => $v['grading_system'] ?? $exam->assessment_system,
            'score' => $v['score'] ?? null,
            'grade' => $v['grade'] ?? null,
            'points' => $v['points'] ?? null,
            'cbc_level' => $v['cbc_level'] ?? null,
            'cbc_comment' => $v['cbc_comment'] ?? null,
            'remarks' => $v['remarks'] ?? null,
        ];

        if (Schema::hasColumn('exam_results', 'updated_by')) {
            $payload['updated_by'] = $request->user()->id;
        }

        ExamResult::query()->updateOrCreate(
            [
                'exam_id' => $exam->id,
                'student_id' => $student->id,
            ],
            $payload
        );

        return back()->with('success', 'Result saved successfully.');
    }

    public function updateResult(Request $request, Exam $exam, ExamResult $examResult): RedirectResponse
    {
        abort_if((int) $examResult->exam_id !== (int) $exam->id, 404);

        if (strtolower((string) $exam->status) === 'closed') {
            return back()->with('error', 'Closed exams cannot be edited.');
        }

        $student = Student::query()->findOrFail((int) $examResult->student_id);

        // central policy check
        $this->authorize('mutateResult', [$exam, $student]);

        $v = $request->validate([
            'grading_system' => ['nullable', 'string', 'max:20'],
            'score' => ['nullable', 'numeric', 'min:0'],
            'grade' => ['nullable', 'string', 'max:10'],
            'points' => ['nullable', 'numeric', 'min:0'],
            'cbc_level' => ['nullable', 'string', 'max:20'],
            'cbc_comment' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
        ]);

        $payload = [
            'grading_system' => $v['grading_system'] ?? $exam->assessment_system,
            'score' => $v['score'] ?? null,
            'grade' => $v['grade'] ?? null,
            'points' => $v['points'] ?? null,
            'cbc_level' => $v['cbc_level'] ?? null,
            'cbc_comment' => $v['cbc_comment'] ?? null,
            'remarks' => $v['remarks'] ?? null,
        ];

        if (Schema::hasColumn('exam_results', 'updated_by')) {
            $payload['updated_by'] = $request->user()->id;
        }

        $examResult->update($payload);

        return back()->with('success', 'Result updated successfully.');
    }

    public function destroyResult(Request $request, Exam $exam, ExamResult $examResult): RedirectResponse
    {
        abort_if((int) $examResult->exam_id !== (int) $exam->id, 404);

        if (strtolower((string) $exam->status) === 'closed') {
            return back()->with('error', 'Closed exams cannot be edited.');
        }

        $student = Student::query()->findOrFail((int) $examResult->student_id);

        // central policy check
        $this->authorize('mutateResult', [$exam, $student]);

        $examResult->delete();

        return back()->with('success', 'Result deleted successfully.');
    }

    public function storeMissingResults(Request $request, Exam $exam): RedirectResponse
    {
        if (strtolower((string) $exam->status) === 'closed') {
            return back()->with('error', 'Closed exams cannot be edited.');
        }

        $v = $request->validate([
            'results' => ['required', 'array', 'min:1'],
            'results.*.student_id' => ['required', 'integer', 'exists:students,id'],
            'results.*.grading_system' => ['nullable', 'string', 'max:20'],
            'results.*.score' => ['nullable', 'numeric', 'min:0'],
            'results.*.grade' => ['nullable', 'string', 'max:10'],
            'results.*.points' => ['nullable', 'numeric', 'min:0'],
            'results.*.cbc_level' => ['nullable', 'string', 'max:20'],
            'results.*.cbc_comment' => ['nullable', 'string'],
            'results.*.remarks' => ['nullable', 'string'],
        ]);

        foreach ($v['results'] as $row) {
            $student = Student::query()->findOrFail((int) $row['student_id']);

            // central policy check
            $this->authorize('mutateResult', [$exam, $student]);

            $payload = [
                'grading_system' => $row['grading_system'] ?? $exam->assessment_system,
                'score' => $row['score'] ?? null,
                'grade' => $row['grade'] ?? null,
                'points' => $row['points'] ?? null,
                'cbc_level' => $row['cbc_level'] ?? null,
                'cbc_comment' => $row['cbc_comment'] ?? null,
                'remarks' => $row['remarks'] ?? null,
            ];

            if (Schema::hasColumn('exam_results', 'updated_by')) {
                $payload['updated_by'] = $request->user()->id;
            }

            ExamResult::query()->updateOrCreate(
                [
                    'exam_id' => $exam->id,
                    'student_id' => $student->id,
                ],
                $payload
            );
        }

        return back()->with('success', 'Missing results saved successfully.');
    }

    // ---------------------- helpers ----------------------

    protected function classTeacherAssignmentsFor(int $userId): Collection
    {
        if (!Schema::hasTable('class_teacher_assignments')) {
            return collect();
        }

        $teacherColumn = $this->resolveTeacherColumn('class_teacher_assignments');
        if (!$teacherColumn) {
            return collect();
        }

        $q = DB::table('class_teacher_assignments')->where($teacherColumn, $userId);

        if (Schema::hasColumn('class_teacher_assignments', 'is_active')) {
            $q->where('is_active', 1);
        }

        $select = ['id', 'class_level'];
        if (Schema::hasColumn('class_teacher_assignments', 'stream')) {
            $select[] = 'stream';
        }

        return $q->get($select)->map(fn ($r) => [
            'id' => $r->id ?? null,
            'class_level' => $r->class_level ?? null,
            'stream' => $r->stream ?? null,
        ]);
    }

    protected function subjectTeacherAssignmentsFor(int $userId): Collection
    {
        if (!Schema::hasTable('subject_teacher_assignments')) {
            return collect();
        }

        $teacherColumn = $this->resolveTeacherColumn('subject_teacher_assignments');
        if (!$teacherColumn) {
            return collect();
        }

        $q = DB::table('subject_teacher_assignments')->where($teacherColumn, $userId);

        if (Schema::hasColumn('subject_teacher_assignments', 'is_active')) {
            $q->where('is_active', 1);
        }

        $select = ['id', 'subject', 'class_level'];
        if (Schema::hasColumn('subject_teacher_assignments', 'stream')) {
            $select[] = 'stream';
        }

        return $q->get($select)->map(fn ($r) => [
            'id' => $r->id ?? null,
            'subject' => $r->subject ?? null,
            'class_level' => $r->class_level ?? null,
            'stream' => $r->stream ?? null,
        ]);
    }

    /**
     * IMPORTANT FIX:
     * auto-detect teacher key so we don't crash on unknown 'teacher_id'
     */
    protected function resolveTeacherColumn(string $table): ?string
    {
        foreach (['teacher_id', 'user_id', 'staff_id', 'class_teacher_id'] as $candidate) {
            if (Schema::hasColumn($table, $candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    protected function applyClassScopeToExamQuery($query, Collection $assignments): void
    {
        if ($assignments->isEmpty() || !Schema::hasColumn('exams', 'class_level')) {
            $query->whereRaw('1=0');
            return;
        }

        $query->where(function ($outer) use ($assignments) {
            foreach ($assignments as $a) {
                $class = trim((string) ($a['class_level'] ?? ''));
                $stream = trim((string) ($a['stream'] ?? ''));
                if ($class === '') continue;

                $outer->orWhere(function ($inner) use ($class, $stream) {
                    $inner->where('class_level', $class);
                    if (Schema::hasColumn('exams', 'stream') && $stream !== '') {
                        $inner->where('stream', $stream);
                    }
                });
            }
        });
    }

    protected function applySubjectScopeToExamQuery($query, Collection $assignments): void
    {
        if ($assignments->isEmpty() || !Schema::hasColumn('exams', 'class_level') || !Schema::hasColumn('exams', 'subject')) {
            $query->whereRaw('1=0');
            return;
        }

        $query->where(function ($outer) use ($assignments) {
            foreach ($assignments as $a) {
                $class = trim((string) ($a['class_level'] ?? ''));
                $subject = trim((string) ($a['subject'] ?? ''));
                $stream = trim((string) ($a['stream'] ?? ''));
                if ($class === '' || $subject === '') continue;

                $outer->orWhere(function ($inner) use ($class, $subject, $stream) {
                    $inner->where('class_level', $class)->where('subject', $subject);
                    if (Schema::hasColumn('exams', 'stream') && $stream !== '') {
                        $inner->where('stream', $stream);
                    }
                });
            }
        });
    }

    protected function studentsForScope(string $role, Collection $classAssignments, Collection $subjectAssignments, $schoolId): Collection
    {
        $q = Student::query();

        if (Schema::hasColumn('students', 'school_id') && !is_null($schoolId)) {
            $q->where('school_id', $schoolId);
        }

        if ($role === 'class_teacher' && !$classAssignments->isEmpty()) {
            $q->where(function ($outer) use ($classAssignments) {
                foreach ($classAssignments as $a) {
                    $class = trim((string) ($a['class_level'] ?? ''));
                    $stream = trim((string) ($a['stream'] ?? ''));
                    if ($class === '') continue;

                    $outer->orWhere(function ($inner) use ($class, $stream) {
                        $inner->where('class_level', $class);
                        if (Schema::hasColumn('students', 'stream') && $stream !== '') {
                            $inner->where('stream', $stream);
                        }
                    });
                }
            });
        }

        if ($role === 'subject_teacher' && !$subjectAssignments->isEmpty()) {
            $q->where(function ($outer) use ($subjectAssignments) {
                foreach ($subjectAssignments as $a) {
                    $class = trim((string) ($a['class_level'] ?? ''));
                    $stream = trim((string) ($a['stream'] ?? ''));
                    if ($class === '') continue;

                    $outer->orWhere(function ($inner) use ($class, $stream) {
                        $inner->where('class_level', $class);
                        if (Schema::hasColumn('students', 'stream') && $stream !== '') {
                            $inner->where('stream', $stream);
                        }
                    });
                }
            });
        }

        return $q->select(['id', 'admission_no', 'first_name', 'last_name', 'class_level', 'stream'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    protected function resolvePermissions(string $role): array
    {
        if (in_array($role, ['principal', 'deputy_principal', 'dean'], true)) {
            return [
                'canCreateExams' => true,
                'canDeleteExams' => true,
                'canUpdateAnyResult' => true,
                'canUpdateClassResults' => true,
                'canUpdateSubjectResults' => true,
            ];
        }

        if ($role === 'class_teacher') {
            return [
                'canCreateExams' => false,
                'canDeleteExams' => false,
                'canUpdateAnyResult' => false,
                'canUpdateClassResults' => true,
                'canUpdateSubjectResults' => false,
            ];
        }

        if ($role === 'subject_teacher') {
            return [
                'canCreateExams' => false,
                'canDeleteExams' => false,
                'canUpdateAnyResult' => false,
                'canUpdateClassResults' => false,
                'canUpdateSubjectResults' => true,
            ];
        }

        return [
            'canCreateExams' => false,
            'canDeleteExams' => false,
            'canUpdateAnyResult' => false,
            'canUpdateClassResults' => false,
            'canUpdateSubjectResults' => false,
        ];
    }

    protected function normalizeExamPaginator($paginator): array
    {
        $raw = $paginator->toArray();

        $raw['data'] = collect($raw['data'] ?? [])->map(function ($exam) {
            $results = collect($exam['results'] ?? [])->map(function ($r) {
                return [
                    'id' => $r['id'] ?? null,
                    'exam_id' => $r['exam_id'] ?? null,
                    'student_id' => $r['student_id'] ?? null,
                    'grading_system' => $r['grading_system'] ?? null,
                    'score' => $r['score'] ?? null,
                    'grade' => $r['grade'] ?? null,
                    'points' => $r['points'] ?? null,
                    'cbc_level' => $r['cbc_level'] ?? null,
                    'cbc_comment' => $r['cbc_comment'] ?? null,
                    'remarks' => $r['remarks'] ?? null,
                    'updated_at' => $r['updated_at'] ?? null,
                    'edited_by' => $r['updated_by'] ?? null,
                    'student' => [
                        'id' => data_get($r, 'student.id'),
                        'admission_no' => data_get($r, 'student.admission_no'),
                        'first_name' => data_get($r, 'student.first_name'),
                        'last_name' => data_get($r, 'student.last_name'),
                        'class_level' => data_get($r, 'student.class_level'),
                        'stream' => data_get($r, 'student.stream'),
                    ],
                ];
            })->values()->all();

            return [
                'id' => $exam['id'] ?? null,
                'title' => $exam['title'] ?? null,
                'term' => $exam['term'] ?? null,
                'year' => $exam['year'] ?? null,
                'exam_date' => $exam['exam_date'] ?? null,
                'max_score' => $exam['max_score'] ?? null,
                'status' => $exam['status'] ?? null,
                'assessment_system' => $exam['assessment_system'] ?? null,
                'class_level' => $exam['class_level'] ?? null,
                'stream' => $exam['stream'] ?? null,
                'subject' => $exam['subject'] ?? null,
                'pathway' => $exam['pathway'] ?? null,
                'results' => $results,
            ];
        })->values()->all();

        return $raw;
    }

    protected function normalizeRole(string $role): string
    {
        return str_replace([' ', '-'], '_', strtolower(trim($role)));
    }
}