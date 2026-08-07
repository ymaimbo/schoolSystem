<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StaffRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StaffDirectoryController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->get('search', ''));
        $staffType = trim((string) $request->get('staff_type', ''));
        $roleCategory = trim((string) $request->get('role_category', ''));

        $records = StaffRecord::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('full_name', 'like', "%{$search}%")
                        ->orWhere('department', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($staffType !== '', fn ($q) => $q->where('staff_type', $staffType))
            ->when($roleCategory !== '', fn ($q) => $q->where('role_category', $roleCategory))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Staff/Index', [
            'records' => $records,
            'filters' => [
                'search' => $search,
                'staff_type' => $staffType,
                'role_category' => $roleCategory,
            ],
            'staffTypes' => ['teacher', 'worker'],
            'roleCategories' => ['hod', 'class_teacher', 'teacher_on_duty', 'teacher', 'worker'],
            'statusOptions' => ['active', 'inactive'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'staff_type' => ['required', 'in:teacher,worker'],
            'role_category' => ['required', 'in:hod,class_teacher,teacher_on_duty,teacher,worker'],
            'department' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'employment_status' => ['required', 'in:active,inactive'],
            'is_on_duty' => ['nullable', 'boolean'],
            'duty_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['is_on_duty'] = (bool) ($data['is_on_duty'] ?? false);
        $data['recorded_by'] = auth()->id();

        StaffRecord::create($data);

        return back()->with('success', 'Staff record added.');
    }

    public function update(Request $request, StaffRecord $staffRecord): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'staff_type' => ['required', 'in:teacher,worker'],
            'role_category' => ['required', 'in:hod,class_teacher,teacher_on_duty,teacher,worker'],
            'department' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'employment_status' => ['required', 'in:active,inactive'],
            'is_on_duty' => ['nullable', 'boolean'],
            'duty_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['is_on_duty'] = (bool) ($data['is_on_duty'] ?? false);
        $staffRecord->update($data);

        return back()->with('success', 'Staff record updated.');
    }

    public function destroy(StaffRecord $staffRecord): RedirectResponse
    {
        $staffRecord->delete();

        return back()->with('success', 'Staff record deleted.');
    }
}