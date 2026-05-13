<?php

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
        $filters = $request->only(['search', 'education_system', 'class_level', 'status']);

        $students = Student::query()
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
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Students/Index', [
            'students' => $students,
            'filters' => $filters,
            'stats' => [
                'total' => Student::count(),
                'active' => Student::where('status', 'active')->count(),
                'alumni' => Student::where('status', 'alumni')->count(),
                'transferred' => Student::where('status', 'transferred')->count(),
                'cbc' => Student::where('education_system', 'CBC')->count(),
                'legacy_844' => Student::where('education_system', '8-4-4')->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'admission_no' => ['required', 'string', 'max:255', 'unique:students,admission_no'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'parent_name' => ['nullable', 'string', 'max:255'],
            'parent_phone' => ['required', 'string', 'max:32'],
            'gender' => ['required', 'in:Male,Female'],
            'education_system' => ['required', 'in:8-4-4,CBC'],
            'class_level' => ['required', 'string', 'max:20'],
            'pathway' => ['nullable', 'string', 'max:100'],
            'entry_marks' => ['required', 'numeric', 'min:0', 'max:500'],
            'stream' => ['nullable', 'string', 'max:32'],
            'status' => ['required', 'in:active,alumni,transferred'],
        ]);

        $validated['form_level'] = $this->deriveFormLevel($validated['education_system'], $validated['class_level']);

        if ($validated['education_system'] === '8-4-4') {
            $validated['pathway'] = null;
        }

        Student::create($validated);

        return back()->with('success', 'Student created successfully.');
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'admission_no' => [
                'required',
                'string',
                'max:255',
                Rule::unique('students', 'admission_no')->ignore($student->id),
            ],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'parent_name' => ['nullable', 'string', 'max:255'],
            'parent_phone' => ['required', 'string', 'max:32'],
            'gender' => ['required', 'in:Male,Female'],
            'education_system' => ['required', 'in:8-4-4,CBC'],
            'class_level' => ['required', 'string', 'max:20'],
            'pathway' => ['nullable', 'string', 'max:100'],
            'entry_marks' => ['required', 'numeric', 'min:0', 'max:500'],
            'stream' => ['nullable', 'string', 'max:32'],
            'status' => ['required', 'in:active,alumni,transferred'],
        ]);

        $validated['form_level'] = $this->deriveFormLevel($validated['education_system'], $validated['class_level']);

        if ($validated['education_system'] === '8-4-4') {
            $validated['pathway'] = null;
        }

        $student->update($validated);

        return back()->with('success', 'Student updated successfully.');
    }

    public function destroy(Student $student): RedirectResponse
    {
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
}