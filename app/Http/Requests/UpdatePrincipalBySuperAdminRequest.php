<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePrincipalBySuperAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && $user->role === 'super_admin';
    }

    public function rules(): array
    {
        $principal = $this->route('principal');

        return [
            'name' => ['required', 'string', 'max:160'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($principal?->id),
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }
}