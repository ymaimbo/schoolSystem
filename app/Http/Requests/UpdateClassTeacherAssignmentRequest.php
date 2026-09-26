<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClassTeacherAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && $user->role === 'principal';
    }

    public function rules(): array
    {
        $school = app()->bound('currentSchool') ? app('currentSchool') : null;
        $schoolId = $school?->id;

        $assignment = $this->route('assignment');
        $assignmentId = is_object($assignment) ? $assignment->id : $assignment;

        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) use ($schoolId) {
                    $query->where('school_id', $schoolId)->where('role', 'class_teacher');
                }),
                Rule::unique('class_teacher_assignments', 'user_id')
                    ->ignore($assignmentId)
                    ->where(function ($query) use ($schoolId) {
                        $query->where('school_id', $schoolId);
                    }),
            ],
            'class_level' => ['required', 'string', 'max:80'],
            'stream' => ['nullable', 'string', 'max:80'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}