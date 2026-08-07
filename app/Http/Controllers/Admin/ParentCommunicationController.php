<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ParentMessage;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ParentCommunicationController extends Controller
{
    public function index(): Response
    {
        $students = Student::query()
            ->select([
                'id',
                'admission_no',
                'first_name',
                'last_name',
                'parent_name',
                'parent_phone',
                'guardian_name',
                'guardian_phone',
                'contact_preference',
            ])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $exams = Exam::query()
            ->select(['id', 'title', 'term', 'year'])
            ->latest('year')
            ->latest('id')
            ->get();

        $latestMessages = ParentMessage::query()
            ->with(['student:id,admission_no,first_name,last_name', 'sender:id,name'])
            ->latest('sent_at')
            ->take(30)
            ->get();

        return Inertia::render('Admin/Communications/Index', [
            'students' => $students,
            'exams' => $exams,
            'latestMessages' => $latestMessages,
        ]);
    }

    public function sendNotice(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:students,id'],
            'message' => ['required', 'string', 'max:1200'],
        ]);

        $students = Student::whereIn('id', $data['student_ids'])->get();

        foreach ($students as $student) {
            [$recipientName, $recipientPhone] = $this->recipient($student);

            if (!$recipientPhone) {
                continue;
            }

            ParentMessage::create([
                'student_id' => $student->id,
                'recipient_name' => $recipientName,
                'recipient_phone' => $recipientPhone,
                'message_type' => 'notice',
                'message_body' => $data['message'],
                'status' => 'sent',
                'sent_at' => now(),
                'sent_by' => auth()->id(),
                'context' => null,
            ]);

            Log::info('Parent notice sent', [
                'student_id' => $student->id,
                'phone' => $recipientPhone,
            ]);
        }

        return back()->with('success', 'Notice messages sent to selected parents/guardians.');
    }

    public function sendResults(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:students,id'],
            'exam_id' => ['nullable', 'integer', 'exists:exams,id'],
        ]);

        $students = Student::with(['examResults' => function ($query) use ($data) {
            if (!empty($data['exam_id'])) {
                $query->where('exam_id', $data['exam_id']);
            }
            $query->latest('id');
        }, 'examResults.exam'])
            ->whereIn('id', $data['student_ids'])
            ->get();

        foreach ($students as $student) {
            $result = $student->examResults->first();

            if (!$result) {
                continue;
            }

            [$recipientName, $recipientPhone] = $this->recipient($student);

            if (!$recipientPhone) {
                continue;
            }

            $examTitle = $result->exam?->title ?? 'Assessment';
            $score = $result->score ?? '-';
            $grade = $result->grade ?? $result->cbc_level ?? 'N/A';

            $message = "Vigurungani: Result update for {$student->full_name}. Exam: {$examTitle}. Score: {$score}. Grade/Level: {$grade}.";

            ParentMessage::create([
                'student_id' => $student->id,
                'recipient_name' => $recipientName,
                'recipient_phone' => $recipientPhone,
                'message_type' => 'result',
                'message_body' => $message,
                'status' => 'sent',
                'sent_at' => now(),
                'sent_by' => auth()->id(),
                'context' => [
                    'exam_id' => $result->exam_id,
                    'exam_result_id' => $result->id,
                ],
            ]);

            Log::info('Parent result message sent', [
                'student_id' => $student->id,
                'phone' => $recipientPhone,
                'exam_id' => $result->exam_id,
            ]);
        }

        return back()->with('success', 'Result messages sent for selected students.');
    }

    private function recipient(Student $student): array
    {
        $preference = strtolower((string) $student->contact_preference);

        if ($preference === 'guardian' && !empty($student->guardian_phone)) {
            return [$student->guardian_name ?: 'Guardian', $student->guardian_phone];
        }

        return [$student->parent_name ?: 'Parent', $student->parent_phone];
    }
}