<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DisciplineCase;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DisciplineDepartmentController extends Controller
{
    public function index(Request $request): Response
    {
        $status = trim((string) $request->get('status', ''));
        $subjectType = trim((string) $request->get('subject_type', ''));

        $cases = DisciplineCase::query()
            ->with(['student:id,admission_no,first_name,last_name,class_level,stream', 'handler:id,name'])
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($subjectType !== '', fn ($q) => $q->where('subject_type', $subjectType))
            ->latest('reported_on')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $students = Student::query()
            ->select(['id', 'admission_no', 'first_name', 'last_name', 'class_level', 'stream'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return Inertia::render('Admin/Discipline/Index', [
            'cases' => $cases,
            'students' => $students,
            'filters' => [
                'status' => $status,
                'subject_type' => $subjectType,
            ],
            'statusOptions' => ['pending', 'ongoing', 'resolved'],
            'subjectTypeOptions' => ['student', 'worker'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject_type' => ['required', 'in:student,worker'],
            'student_id' => ['nullable', 'exists:students,id'],
            'worker_name' => ['nullable', 'string', 'max:255'],
            'worker_department' => ['nullable', 'string', 'max:255'],
            'case_title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'status' => ['required', 'in:pending,ongoing,resolved'],
            'reported_on' => ['required', 'date'],
            'action_taken' => ['nullable', 'string'],
            'next_step' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['subject_type'] === 'student' && empty($data['student_id'])) {
            return back()->withErrors(['student_id' => 'Student is required for student discipline case.']);
        }

        if ($data['subject_type'] === 'worker' && empty($data['worker_name'])) {
            return back()->withErrors(['worker_name' => 'Worker name is required for worker discipline case.']);
        }

        $data['handled_by'] = auth()->id();

        DisciplineCase::create($data);

        return back()->with('success', 'Discipline case recorded.');
    }

    public function update(Request $request, DisciplineCase $case): RedirectResponse
    {
        $data = $request->validate([
            'subject_type' => ['required', 'in:student,worker'],
            'student_id' => ['nullable', 'exists:students,id'],
            'worker_name' => ['nullable', 'string', 'max:255'],
            'worker_department' => ['nullable', 'string', 'max:255'],
            'case_title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'status' => ['required', 'in:pending,ongoing,resolved'],
            'reported_on' => ['required', 'date'],
            'action_taken' => ['nullable', 'string'],
            'next_step' => ['nullable', 'string', 'max:255'],
        ]);

        $data['handled_by'] = auth()->id();

        $case->update($data);

        return back()->with('success', 'Discipline case updated.');
    }

    public function destroy(DisciplineCase $case): RedirectResponse
    {
        $case->delete();

        return back()->with('success', 'Discipline case deleted.');
    }
}