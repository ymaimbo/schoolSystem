<?php
// app/Http/Controllers/Admin/StudentController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function index(Request $request): Response
    {
        $schoolId = $this->currentSchoolId();
        $filters = $request->only(['search', 'education_system', 'class_level', 'status']);

        $students = Student::query()
            ->where('school_id', $schoolId)
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('admission_no', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('parent_phone', 'like', "%{$search}%");
                });
            })
            ->when($filters['education_system'] ?? null, fn ($query, $system) => $query->where('education_system', $system))
            ->when($filters['class_level'] ?? null, fn ($query, $level) => $query->where('class_level', $level))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('id') // show newest first so newly added record is visible immediately
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Students/Index', [
            'students' => $students,
            'filters' => $filters,
            'stats' => [
                'total' => Student::where('school_id', $schoolId)->count(),
                'active' => Student::where('school_id', $schoolId)->where('status', 'active')->count(),
                'inactive' => Student::where('school_id', $schoolId)->whereNot('status', 'active')->count(),
                'transferred' => Student::where('school_id', $schoolId)->where('status', 'transferred')->count(),
                'male' => Student::where('school_id', $schoolId)->where('gender', 'Male')->count(),
                'female' => Student::where('school_id', $schoolId)->where('gender', 'Female')->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = $this->currentSchoolId();

        $validated = $request->validate([
            'admission_no' => [
                'required',
                'string',
                'max:255',
                Rule::unique('students', 'admission_no')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],

            // UI currently does not always send these values, so keep them optional
            'parent_name' => ['nullable', 'string', 'max:255'],
            'parent_phone' => ['nullable', 'string', 'max:32'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_phone' => ['nullable', 'string', 'max:32'],
            'guardian_relationship' => ['nullable', 'string', 'max:255'],
            'contact_preference' => ['nullable', Rule::in(['parent', 'guardian'])],

            'gender' => ['required', Rule::in(['Male', 'Female'])],
            'education_system' => ['required', Rule::in(['8-4-4', 'CBC'])],
            'class_level' => ['required', 'string', 'max:20'],
            'pathway' => ['nullable', 'string', 'max:100'],
            'entry_marks' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'stream' => ['nullable', 'string', 'max:32'],
            'status' => ['required', Rule::in(['active', 'alumni', 'transferred'])],
        ]);

        $validated['school_id'] = $schoolId;
        $validated['entry_marks'] = $validated['entry_marks'] ?? 0;
        $validated['contact_preference'] = $validated['contact_preference'] ?? 'parent';
        $validated['form_level'] = $this->deriveFormLevel($validated['education_system'], $validated['class_level']);

        if ($validated['education_system'] === '8-4-4') {
            $validated['pathway'] = null;
        }

        Student::create($validated);

        // Redirect to index without stale query/pagination state
        return redirect()
            ->route('admin.students.index')
            ->with('success', 'Student created successfully.');
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $schoolId = $this->currentSchoolId();
        abort_if((int) $student->school_id !== $schoolId, 404);

        $validated = $request->validate([
            'admission_no' => [
                'required',
                'string',
                'max:255',
                Rule::unique('students', 'admission_no')
                    ->where(fn ($q) => $q->where('school_id', $schoolId))
                    ->ignore($student->id),
            ],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'parent_name' => ['nullable', 'string', 'max:255'],
            'parent_phone' => ['nullable', 'string', 'max:32'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_phone' => ['nullable', 'string', 'max:32'],
            'guardian_relationship' => ['nullable', 'string', 'max:255'],
            'contact_preference' => ['nullable', Rule::in(['parent', 'guardian'])],

            'gender' => ['required', Rule::in(['Male', 'Female'])],
            'education_system' => ['required', Rule::in(['8-4-4', 'CBC'])],
            'class_level' => ['required', 'string', 'max:20'],
            'pathway' => ['nullable', 'string', 'max:100'],
            'entry_marks' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'stream' => ['nullable', 'string', 'max:32'],
            'status' => ['required', Rule::in(['active', 'alumni', 'transferred'])],
        ]);

        $validated['entry_marks'] = $validated['entry_marks'] ?? 0;
        $validated['contact_preference'] = $validated['contact_preference'] ?? 'parent';
        $validated['form_level'] = $this->deriveFormLevel($validated['education_system'], $validated['class_level']);

        if ($validated['education_system'] === '8-4-4') {
            $validated['pathway'] = null;
        }

        $student->update($validated);

        return back()->with('success', 'Student updated successfully.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $schoolId = $this->currentSchoolId();
        abort_if((int) $student->school_id !== $schoolId, 404);

        $student->delete();

        return back()->with('success', 'Student removed successfully.');
    }

    private function deriveFormLevel(string $system, string $classLevel): int
    {
        $level = strtolower(trim($classLevel));

        if ($system === '8-4-4') {
            return match ($level) {
                'form 1' => 1,
                'form 2' => 2,
                'form 3' => 3,
                'form 4' => 4,
                default => 1,
            };
        }

        return match ($level) {
            'grade 10' => 1,
            'grade 11' => 2,
            'grade 12' => 3,
            default => 1,
        };
    }

    private function currentSchoolId(): int
    {
        $school = app('currentSchool');
        abort_if(!$school || !isset($school->id), 404, 'No school context found.');
        return (int) $school->id;
    }
}