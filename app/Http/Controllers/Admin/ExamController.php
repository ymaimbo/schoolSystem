<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExamController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['year', 'term', 'status', 'assessment_system', 'class_level']);

        $exams = Exam::query()
            ->with(['results.student'])
            ->when($filters['year'] ?? null, fn ($q, $year) => $q->where('year', $year))
            ->when($filters['term'] ?? null, fn ($q, $term) => $q->where('term', $term))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['assessment_system'] ?? null, fn ($q, $s) => $q->where('assessment_system', $s))
            ->when($filters['class_level'] ?? null, fn ($q, $l) => $q->where('class_level', $l))
            ->latest('exam_date')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Exams/Index', [
            'exams' => $exams,
            'students' => Student::query()
                ->select(['id', 'admission_no', 'first_name', 'last_name', 'education_system', 'class_level', 'pathway'])
                ->orderBy('first_name')
                ->get(),
            'filters' => $filters,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'term' => ['required', 'in:Term 1,Term 2,Term 3'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'exam_date' => ['required', 'date'],
            'max_score' => ['required', 'numeric', 'min:1'],
            'status' => ['required', 'in:draft,published,closed'],
            'assessment_system' => ['required', 'in:844,CBC,HYBRID'],
            'class_level' => ['nullable', 'string', 'max:20'],
            'pathway' => ['nullable', 'string', 'max:100'],
        ]);

        Exam::create($validated);

        return back()->with('success', 'Exam created successfully.');
    }

    public function update(Request $request, Exam $exam): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'term' => ['required', 'in:Term 1,Term 2,Term 3'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'exam_date' => ['required', 'date'],
            'max_score' => ['required', 'numeric', 'min:1'],
            'status' => ['required', 'in:draft,published,closed'],
            'assessment_system' => ['required', 'in:844,CBC,HYBRID'],
            'class_level' => ['nullable', 'string', 'max:20'],
            'pathway' => ['nullable', 'string', 'max:100'],
        ]);

        $exam->update($validated);

        return back()->with('success', 'Exam updated successfully.');
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        $exam->delete();

        return back()->with('success', 'Exam deleted successfully.');
    }

    public function storeResult(Request $request, Exam $exam): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'grading_system' => ['required', 'in:844,CBC'],
            'score' => ['nullable', 'numeric', 'min:0'],
            'grade' => ['nullable', 'string', 'max:16'],
            'points' => ['nullable', 'numeric', 'min:0'],
            'cbc_level' => ['nullable', 'in:BE,AE,ME,EE'],
            'cbc_comment' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
        ]);

        if ($validated['grading_system'] === '844') {
            $score = (float) ($validated['score'] ?? 0);
            $validated['grade'] = $validated['grade'] ?? $this->grade844($score);
            $validated['points'] = $validated['points'] ?? $this->points844($validated['grade']);
            $validated['cbc_level'] = null;
            $validated['cbc_comment'] = null;
        } else {
            // CBC
            $validated['score'] = $validated['score'] ?? null;
            $validated['grade'] = null;
            $validated['points'] = null;
        }

        ExamResult::updateOrCreate(
            ['exam_id' => $exam->id, 'student_id' => $validated['student_id']],
            [
                'grading_system' => $validated['grading_system'],
                'score' => $validated['score'] ?? 0,
                'grade' => $validated['grade'] ?? null,
                'points' => $validated['points'] ?? null,
                'cbc_level' => $validated['cbc_level'] ?? null,
                'cbc_comment' => $validated['cbc_comment'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
            ]
        );

        return back()->with('success', 'Result saved successfully.');
    }

    public function updateResult(Request $request, ExamResult $examResult): RedirectResponse
    {
        $validated = $request->validate([
            'grading_system' => ['required', 'in:844,CBC'],
            'score' => ['nullable', 'numeric', 'min:0'],
            'grade' => ['nullable', 'string', 'max:16'],
            'points' => ['nullable', 'numeric', 'min:0'],
            'cbc_level' => ['nullable', 'in:BE,AE,ME,EE'],
            'cbc_comment' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
        ]);

        if ($validated['grading_system'] === '844') {
            $score = (float) ($validated['score'] ?? 0);
            $validated['grade'] = $validated['grade'] ?? $this->grade844($score);
            $validated['points'] = $validated['points'] ?? $this->points844($validated['grade']);
            $validated['cbc_level'] = null;
            $validated['cbc_comment'] = null;
        } else {
            $validated['grade'] = null;
            $validated['points'] = null;
        }

        $examResult->update($validated);

        return back()->with('success', 'Result updated successfully.');
    }

    public function destroyResult(ExamResult $examResult): RedirectResponse
    {
        $examResult->delete();

        return back()->with('success', 'Result deleted successfully.');
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
            default => 'E',
        };
    }

    private function points844(string $grade): float
    {
        return match ($grade) {
            'A' => 12, 'A-' => 11, 'B+' => 10, 'B' => 9, 'B-' => 8,
            'C+' => 7, 'C' => 6, 'C-' => 5, 'D+' => 4, 'D' => 3, 'D-' => 2,
            default => 1,
        };
    }
}