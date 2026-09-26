<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSchoolInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && $user->role === 'super_admin';
    }

    public function rules(): array
    {
        $invoice = $this->route('invoice');

        return [
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'reference_no' => [
                'required',
                'string',
                'max:120',
                Rule::unique('school_invoices', 'reference_no')->ignore($invoice?->id),
            ],
            'item' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(['subscription', 'sms', 'setup', 'support'])],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date', 'after_or_equal:period_start'],
            'due_date' => ['required', 'date'],
            'invoiced_amount' => ['required', 'numeric', 'min:1'],
            'balance_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['unpaid', 'partial', 'paid', 'overdue'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}