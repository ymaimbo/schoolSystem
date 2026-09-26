<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStaffRecordRequest extends FormRequest
{
    private const ASSIGNABLE_ROLES = [
        'deputy_principal',
        'dean',
        'hod',
        'school_examiner',
        'class_teacher',
        'secretary',
        'accountant',
        'store_keeper',
    ];

    public function authorize(): bool
    {
        $user = $this->user();

        return $user && $user->role === 'principal';
    }

    public function rules(): array
    {
        $schoolId = (int) optional(app()->bound('currentSchool') ? app('currentSchool') : null)->id;

        return [
            'full_name' => ['required', 'string', 'max:160'],
            'staff_type' => ['nullable', 'string', 'max:80'],
            'role_category' => ['nullable', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'employment_status' => ['nullable', 'string', 'max:80'],
            'is_on_duty' => ['nullable', 'boolean'],
            'duty_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'assign_login' => ['nullable', 'boolean'],
            'login_email' => [
                'required_if:assign_login,1',
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->where(function ($query) use ($schoolId) {
                    return $query->where('school_id', $schoolId);
                }),
            ],
            'login_role' => ['required_if:assign_login,1', Rule::in(self::ASSIGNABLE_ROLES)],
            'login_password' => ['required_if:assign_login,1', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'login_email.required_if' => 'Login email is required when assigning login access.',
            'login_role.required_if' => 'Login role is required when assigning login access.',
            'login_password.required_if' => 'Login password is required when assigning login access.',
            'login_password.confirmed' => 'Login password confirmation does not match.',
        ];
    }
}