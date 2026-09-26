<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateSchoolBySuperAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && $user->role === 'super_admin';
    }

    protected function prepareForValidation(): void
    {
        $name = (string) $this->input('name', '');
        $slug = (string) $this->input('slug', '');

        $this->merge([
            'slug' => $slug !== '' ? Str::slug($slug) : Str::slug($name),
            'type' => $this->filled('type') ? strtolower((string) $this->input('type')) : null,
            'status' => $this->filled('status') ? strtolower((string) $this->input('status')) : null,
        ]);
    }

    public function rules(): array
    {
        $school = $this->route('school');

        return [
            'name' => ['required', 'string', 'max:160'],
            'slug' => [
                'required',
                'string',
                'max:180',
                'alpha_dash',
                Rule::unique('schools', 'slug')->ignore($school?->id),
            ],
            'location' => ['nullable', 'string', 'max:255'],
            'county' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', 'string', Rule::in(['day', 'boarding', 'mixed'])],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive', 'pending'])],
            'note' => ['nullable', 'string', 'max:1000'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('schools', 'code')->ignore($school?->id),
            ],
            'logo_path' => ['nullable', 'string', 'max:255'],
            'hero_image_path' => ['nullable', 'string', 'max:255'],
        ];
    }
}