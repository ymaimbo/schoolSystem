<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Timetable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TimetableController extends Controller
{
    public function index(Request $request): Response
    {
        $classLevel = $request->string('class_level')->toString();
        $day = $request->string('day_of_week')->toString();

        $items = Timetable::query()
            ->when($classLevel !== '', fn ($q) => $q->where('class_level', $classLevel))
            ->when($day !== '', fn ($q) => $q->where('day_of_week', $day))
            ->orderByRaw("FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')")
            ->orderBy('starts_at')
            ->orderBy('period_label')
            ->get();

        return Inertia::render('Admin/Timetables/Index', [
            'items' => $items,
            'filters' => [
                'class_level' => $classLevel,
                'day_of_week' => $day,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'day_of_week' => ['required', 'string', 'max:16'],
            'period_label' => ['required', 'string', 'max:32'],
            'subject' => ['required', 'string', 'max:100'],
            'teacher_name' => ['nullable', 'string', 'max:255'],
            'class_level' => ['required', 'string', 'max:32'],
            'stream' => ['nullable', 'string', 'max:32'],
            'starts_at' => ['nullable', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i', 'after:starts_at'],
            'notes' => ['nullable', 'string'],
        ]);

        $payload['created_by'] = auth()->id();
        Timetable::create($payload);

        return back()->with('success', 'Timetable slot created successfully.');
    }

    public function update(Request $request, Timetable $timetable): RedirectResponse
    {
        $payload = $request->validate([
            'day_of_week' => ['required', 'string', 'max:16'],
            'period_label' => ['required', 'string', 'max:32'],
            'subject' => ['required', 'string', 'max:100'],
            'teacher_name' => ['nullable', 'string', 'max:255'],
            'class_level' => ['required', 'string', 'max:32'],
            'stream' => ['nullable', 'string', 'max:32'],
            'starts_at' => ['nullable', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i', 'after:starts_at'],
            'notes' => ['nullable', 'string'],
        ]);

        $timetable->update($payload);

        return back()->with('success', 'Timetable slot updated.');
    }

    public function destroy(Timetable $timetable): RedirectResponse
    {
        $timetable->delete();

        return back()->with('success', 'Timetable slot deleted.');
    }
}