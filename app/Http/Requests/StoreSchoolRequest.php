<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreSchoolRequest extends FormRequest
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
            'status' => $this->filled('status') ? strtolower((string) $this->input('status')) : 'active',
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['required', 'string', 'max:180', 'alpha_dash', Rule::unique('schools', 'slug')],
            'location' => ['nullable', 'string', 'max:255'],
            'county' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', 'string', Rule::in(['day', 'boarding', 'mixed'])],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive', 'pending'])],
            'note' => ['nullable', 'string', 'max:1000'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('schools', 'code')],
            'logo_path' => ['nullable', 'string', 'max:255'],
            'hero_image_path' => ['nullable', 'string', 'max:255'],

            // Principal (first school admin)
            'principal_name' => ['required', 'string', 'max:160'],
            'principal_email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'principal_password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'principal_password.confirmed' => 'The principal password confirmation does not match.',
            'slug.alpha_dash' => 'The slug may only contain letters, numbers, dashes and underscores.',
        ];
    }
}