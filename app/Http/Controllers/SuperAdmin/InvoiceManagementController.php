<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSchoolInvoiceRequest;
use App\Http\Requests\UpdateSchoolInvoiceRequest;
use App\Models\School;
use App\Models\SchoolInvoice;
use App\Support\SyncsSchoolBillingAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceManagementController extends Controller
{
    use SyncsSchoolBillingAccess;

    public function index(): Response
    {
        $schools = School::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $invoices = SchoolInvoice::query()
            ->with('school:id,name,slug')
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('SuperAdmin/Billing/Index', [
            'schools' => $schools,
            'invoices' => $invoices,
        ]);
    }

    public function store(StoreSchoolInvoiceRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $request): void {
            $invoice = SchoolInvoice::query()->create([
                'school_id' => $data['school_id'],
                'reference_no' => $data['reference_no'],
                'item' => $data['item'],
                'category' => $data['category'],
                'period_start' => $data['period_start'] ?? null,
                'period_end' => $data['period_end'] ?? null,
                'due_date' => $data['due_date'],
                'invoiced_amount' => $data['invoiced_amount'],
                'balance_amount' => $data['invoiced_amount'],
                'status' => 'unpaid',
                'notes' => $data['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            $school = School::query()->findOrFail((int) $invoice->school_id);

            $this->syncSchoolBillingAccessFromInvoices(
                school: $school,
                changedBy: $request->user()?->id,
                reason: 'invoice_created'
            );
        });

        return back()->with('success', 'Invoice created successfully.');
    }

    public function edit(SchoolInvoice $invoice): Response
    {
        $schools = School::query()->orderBy('name')->get(['id', 'name', 'slug']);

        return Inertia::render('SuperAdmin/Billing/Edit', [
            'schools' => $schools,
            'invoice' => $invoice->only([
                'id',
                'school_id',
                'reference_no',
                'item',
                'category',
                'period_start',
                'period_end',
                'due_date',
                'invoiced_amount',
                'balance_amount',
                'status',
                'notes',
            ]),
        ]);
    }

    public function update(UpdateSchoolInvoiceRequest $request, SchoolInvoice $invoice): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $request, $invoice): void {
            $invoicedAmount = (float) $data['invoiced_amount'];

            $balanceAmount = array_key_exists('balance_amount', $data)
                ? (float) $data['balance_amount']
                : min((float) $invoice->balance_amount, $invoicedAmount);

            $balanceAmount = max(0.0, min($balanceAmount, $invoicedAmount));

            $status = $this->resolveInvoiceStatus($balanceAmount, $data['due_date'] ?? null);

            $invoice->update([
                'school_id' => $data['school_id'],
                'reference_no' => $data['reference_no'],
                'item' => $data['item'],
                'category' => $data['category'],
                'period_start' => $data['period_start'] ?? null,
                'period_end' => $data['period_end'] ?? null,
                'due_date' => $data['due_date'],
                'invoiced_amount' => $invoicedAmount,
                'balance_amount' => $balanceAmount,
                'status' => $status,
                'notes' => $data['notes'] ?? null,
            ]);

            $school = School::query()->findOrFail((int) $invoice->school_id);

            $this->syncSchoolBillingAccessFromInvoices(
                school: $school,
                changedBy: $request->user()?->id,
                reason: 'invoice_updated'
            );
        });

        return redirect()
            ->route('superadmin.billing.index')
            ->with('success', 'Invoice updated successfully.');
    }
}