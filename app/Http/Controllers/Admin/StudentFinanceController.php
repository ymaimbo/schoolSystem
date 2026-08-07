<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentFeeAccount;
use App\Models\StudentFeePayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentFinanceController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->get('search', ''));

        $students = Student::query()
            ->select(['id', 'admission_no', 'first_name', 'last_name', 'class_level', 'stream'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('admission_no', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $accounts = StudentFeeAccount::query()
            ->with(['student:id,admission_no,first_name,last_name,class_level,stream'])
            ->withSum('payments as paid_total', 'amount')
            ->latest()
            ->paginate(20)
            ->through(function ($account) {
                $paid = (float) ($account->paid_total ?? 0);
                $due = (float) ($account->total_fee_due ?? 0);

                return [
                    'id' => $account->id,
                    'student' => $account->student,
                    'total_fee_due' => $due,
                    'paid_total' => $paid,
                    'balance' => $due - $paid,
                    'sponsor_org_name' => $account->sponsor_org_name,
                    'sponsor_org_id' => $account->sponsor_org_id,
                    'notes' => $account->notes,
                ];
            });

        $recentPayments = StudentFeePayment::query()
            ->with(['student:id,admission_no,first_name,last_name', 'recorder:id,name'])
            ->latest('paid_at')
            ->take(30)
            ->get();

        return Inertia::render('Admin/Finance/Students', [
            'students' => $students,
            'accounts' => $accounts,
            'recentPayments' => $recentPayments,
            'filters' => ['search' => $search],
            'paymentMethods' => ['cash', 'bank', 'mpesa', 'cheque', 'organization'],
        ]);
    }

    public function upsertAccount(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'total_fee_due' => ['required', 'numeric', 'min:0'],
            'sponsor_org_name' => ['nullable', 'string', 'max:255'],
            'sponsor_org_id' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        StudentFeeAccount::updateOrCreate(
            ['student_id' => $data['student_id']],
            [
                'total_fee_due' => $data['total_fee_due'],
                'sponsor_org_name' => $data['sponsor_org_name'] ?? null,
                'sponsor_org_id' => $data['sponsor_org_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]
        );

        return back()->with('success', 'Student fee account saved.');
    }

    public function storePayment(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'in:cash,bank,mpesa,cheque,organization'],
            'organization_name' => ['nullable', 'string', 'max:255'],
            'organization_id' => ['nullable', 'string', 'max:255'],
            'receipt_no' => ['nullable', 'string', 'max:255', 'unique:student_fee_payments,receipt_no'],
            'paid_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($data['payment_method'] === 'organization' && empty($data['organization_name'])) {
            return back()->withErrors([
                'organization_name' => 'Organization name is required when payment method is organization.',
            ]);
        }

        $account = StudentFeeAccount::firstOrCreate(
            ['student_id' => $data['student_id']],
            ['total_fee_due' => 0]
        );

        StudentFeePayment::create([
            'student_id' => $data['student_id'],
            'student_fee_account_id' => $account->id,
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'organization_name' => $data['organization_name'] ?? null,
            'organization_id' => $data['organization_id'] ?? null,
            'receipt_no' => $data['receipt_no'] ?? null,
            'paid_at' => $data['paid_at'],
            'recorded_by' => auth()->id(),
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'Student fee payment recorded.');
    }

    public function destroyPayment(StudentFeePayment $payment): RedirectResponse
    {
        $payment->delete();

        return back()->with('success', 'Payment entry deleted.');
    }
}