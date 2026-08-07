<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SportsDepartmentRecord;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SportsDepartmentController extends Controller
{
    public function index(Request $request): Response
    {
        $sport = trim((string) $request->get('sport', ''));
        $status = trim((string) $request->get('status', ''));

        $records = SportsDepartmentRecord::query()
            ->with('student:id,admission_no,first_name,last_name,class_level,stream')
            ->when($sport !== '', fn ($q) => $q->where('sport_name', 'like', "%{$sport}%"))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $students = Student::query()
            ->select(['id', 'admission_no', 'first_name', 'last_name', 'class_level', 'stream'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return Inertia::render('Admin/Sports/Index', [
            'records' => $records,
            'students' => $students,
            'filters' => [
                'sport' => $sport,
                'status' => $status,
            ],
            'sportsOptions' => ['Basketball', 'Football', 'Volleyball', 'Athletics', 'Rugby', 'Netball'],
            'teamCategories' => ['boys', 'girls', 'mixed'],
            'statusOptions' => ['active', 'inactive'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'sport_name' => ['required', 'string', 'max:100'],
            'team_category' => ['required', 'in:boys,girls,mixed'],
            'position' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:active,inactive'],
            'notes' => ['nullable', 'string'],
        ]);

        SportsDepartmentRecord::create($data);

        return back()->with('success', 'Sports player record added.');
    }

    public function update(Request $request, SportsDepartmentRecord $record): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'sport_name' => ['required', 'string', 'max:100'],
            'team_category' => ['required', 'in:boys,girls,mixed'],
            'position' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:active,inactive'],
            'notes' => ['nullable', 'string'],
        ]);

        $record->update($data);

        return back()->with('success', 'Sports player record updated.');
    }

    public function destroy(SportsDepartmentRecord $record): RedirectResponse
    {
        $record->delete();

        return back()->with('success', 'Sports player record deleted.');
    }
}