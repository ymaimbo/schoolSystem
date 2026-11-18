<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Exam;
use App\Models\Student;
use App\Models\ClassTeacherAssignment;
use App\Models\SubjectTeacherAssignment;

class ExamResultPolicy
{
    public function mutateResult(User $user, Exam $exam, Student $student): bool
    {
        $role = $user->role;

        // 1. Principal, Deputy, and Dean have absolute power
        if (in_array($role, ['principal', 'deputy_principal', 'dean'])) {
            return true;
        }

        // 2. Class Teacher: Must be assigned to the student's class AND the exam must be for that class
        if ($role === 'class_teacher') {
            return ClassTeacherAssignment::where('user_id', $user->id)
                ->where('class_level', $student->class_level)
                ->where('stream', $student->stream)
                ->where('is_active', true)
                ->exists();
        }

        // 3. Subject Teacher: Must be assigned to the specific subject in that specific class
        if ($role === 'subject_teacher') {
            return SubjectTeacherAssignment::where('teacher_id', $user->id)
                ->where('class_level', $student->class_level)
                ->where('stream', $student->stream)
                ->where('subject', $exam->subject)
                ->where('is_active', true)
                ->exists();
        }

        return false;
    }
}