<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeeStructure;
use App\Models\FinanceTransaction;
use App\Models\Student;
use App\Models\StudentFeeAccount;
use App\Models\StudentFeePayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StudentFinanceController extends Controller
{
    public function index(Request $request): Response
    {
        $schoolId = $this->currentSchoolId();

        $search = trim((string) $request->input('search', ''));
        $classLevel = trim((string) $request->input('class_level', ''));

        $accounts = StudentFeeAccount::query()
            ->where('school_id', $schoolId)
            ->with([
                'student:id,admission_no,first_name,last_name,class_level,stream',
                'feeStructure:id,name,year,category,class_level',
            ])
            ->withSum('payments as total_paid', 'amount')
            ->when($search !== '', function ($q) use ($search) {
                $q->whereHas('student', function ($s) use ($search) {
                    $s->where('admission_no', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
            })
            ->when($classLevel !== '', function ($q) use ($classLevel) {
                $q->whereHas('student', fn ($s) => $s->where('class_level', $classLevel));
            })
            ->latest('id')
            ->get()
            ->map(function (StudentFeeAccount $a) {
                $paid = (float) ($a->total_paid ?? 0);
                $balance = (float) ($a->total_fee_due ?? 0);

                return [
                    'id' => $a->id,
                    'student_id' => $a->student_id,
                    'admission_no' => $a->student?->admission_no,
                    'student_name' => trim((string) (($a->student?->first_name ?? '') . ' ' . ($a->student?->last_name ?? ''))),
                    'class_level' => $a->student?->class_level,
                    'stream' => $a->student?->stream,
                    'expected_amount' => $paid + $balance, // billed = paid + current due
                    'total_paid' => $paid,
                    'balance' => $balance,
                    'total_fee_due' => $balance,
                    'fee_structure_id' => $a->fee_structure_id,
                ];
            });

        $recentPayments = StudentFeePayment::query()
            ->where('school_id', $schoolId)
            ->with(['student:id,admission_no,first_name,last_name,class_level,stream'])
            ->latest('id')
            ->limit(100)
            ->get()
            ->map(function (StudentFeePayment $p) {
                return [
                    'id' => $p->id,
                    'student_id' => $p->student_id,
                    'student_name' => trim((string) (($p->student?->first_name ?? '') . ' ' . ($p->student?->last_name ?? ''))),
                    'payment_method' => $p->payment_method,
                    'organization_name' => $p->organization_name,
                    'organization_id' => $p->organization_id,
                    'receipt_no' => $p->receipt_no,
                    'paid_at' => optional($p->paid_at)->toDateString(),
                    'amount' => (float) $p->amount,
                    'created_at' => optional($p->created_at)->toDateTimeString(),
                ];
            });

        $students = Student::query()
            ->where('school_id', $schoolId)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'admission_no', 'first_name', 'last_name', 'class_level', 'stream']);

        $feeStructures = FeeStructure::query()
            ->where('school_id', $schoolId)
            ->orderByDesc('year')
            ->orderBy('category')
            ->orderBy('class_level')
            ->get(['id', 'name', 'year', 'category', 'class_level']);

        $totalPaid = (float) StudentFeePayment::query()
            ->where('school_id', $schoolId)
            ->sum('amount');

        $totalBalance = (float) StudentFeeAccount::query()
            ->where('school_id', $schoolId)
            ->sum('total_fee_due');

        return Inertia::render('Admin/Finance/StudentFinance/Index', [
            'students' => $students,
            'accounts' => $accounts,
            'recentPayments' => $recentPayments,
            'payments' => $recentPayments,
            'feeStructures' => $feeStructures,
            'filters' => [
                'search' => $search,
                'class_level' => $classLevel,
            ],
            'summary' => [
                'students_count' => $students->count(),
                'accounts_count' => $accounts->count(),
                'total_billed' => $totalPaid + $totalBalance,
                'total_paid' => $totalPaid,
                'total_balance' => $totalBalance,
            ],
            'paymentMethods' => ['cash', 'bank', 'mpesa', 'cheque', 'transfer'],
        ]);
    }

    public function storeAccount(Request $request): RedirectResponse
    {
        $schoolId = $this->currentSchoolId();

        $validated = $request->validate([
            'student_id' => [
                'required',
                'integer',
                Rule::exists('students', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'fee_structure_id' => [
                'nullable',
                'integer',
                Rule::exists('fee_structures', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'total_fee_due' => ['required', 'numeric', 'min:0'],
            'sponsor_org_name' => ['nullable', 'string', 'max:255'],
            'sponsor_org_id' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        StudentFeeAccount::query()->updateOrCreate(
            [
                'school_id' => $schoolId,
                'student_id' => (int) $validated['student_id'],
            ],
            [
                'fee_structure_id' => $validated['fee_structure_id'] ?? null,
                'total_fee_due' => round((float) $validated['total_fee_due'], 2),
                'sponsor_org_name' => $validated['sponsor_org_name'] ?? null,
                'sponsor_org_id' => $validated['sponsor_org_id'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]
        );

        return back()->with('success', 'Student fee account saved successfully.');
    }

    public function storePayment(Request $request): RedirectResponse
    {
        $schoolId = $this->currentSchoolId();

        $validated = $request->validate([
            'student_id' => [
                'required',
                'integer',
                Rule::exists('students', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', Rule::in(['cash', 'bank', 'mpesa', 'cheque', 'transfer'])],
            'organization_name' => ['nullable', 'string', 'max:120'],
            'organization_id' => ['nullable', 'string', 'max:120'],
            'paid_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $amount = round((float) $validated['amount'], 2);

        DB::transaction(function () use ($validated, $amount, $schoolId) {
            /** @var StudentFeeAccount $account */
            $account = StudentFeeAccount::query()
                ->where('school_id', $schoolId)
                ->where('student_id', (int) $validated['student_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $outstanding = (float) $account->total_fee_due;
            if ($amount > $outstanding) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment exceeds outstanding balance of ' . number_format($outstanding, 2) . '.',
                ]);
            }

            $after = max(0, round($outstanding - $amount, 2));
            $account->update(['total_fee_due' => $after]);

            $receiptNo = $this->generateSchoolPaymentReference($schoolId);

            $payment = StudentFeePayment::query()->create([
                'school_id' => $schoolId,
                'student_id' => (int) $validated['student_id'],
                'student_fee_account_id' => $account->id,
                'amount' => $amount,
                'payment_method' => $validated['payment_method'],
                'organization_name' => $validated['organization_name'] ?? null,
                'organization_id' => $validated['organization_id'] ?? null,
                'receipt_no' => $receiptNo,
                'paid_at' => $validated['paid_at'],
                'recorded_by' => auth()->id(),
                'notes' => $validated['notes'] ?? null,
            ]);

            FinanceTransaction::query()->create([
                'school_id' => $schoolId,
                'entry_date' => $validated['paid_at'],
                'type' => 'income',
                'category' => 'student_fee',
                'description' => 'Student fee payment ' . $receiptNo,
                'amount' => $amount,
                'payment_method' => $validated['payment_method'],
                'reference_no' => $payment->organization_id ?: $receiptNo,
            ]);
        });

        return back()->with('success', 'Payment recorded successfully.');
    }

    public function destroyPayment(StudentFeePayment $payment): RedirectResponse
    {
        $schoolId = $this->currentSchoolId();
        abort_if((int) $payment->school_id !== $schoolId, 404);

        DB::transaction(function () use ($payment, $schoolId) {
            /** @var StudentFeeAccount|null $account */
            $account = StudentFeeAccount::query()
                ->where('school_id', $schoolId)
                ->where('id', $payment->student_fee_account_id)
                ->lockForUpdate()
                ->first();

            if ($account) {
                $account->update([
                    'total_fee_due' => round(((float) $account->total_fee_due + (float) $payment->amount), 2),
                ]);
            }

            FinanceTransaction::query()
                ->where('school_id', $schoolId)
                ->where(function ($q) use ($payment) {
                    $q->where('reference_no', $payment->receipt_no)
                        ->orWhere('description', 'like', '%' . $payment->receipt_no . '%');
                })
                ->delete();

            $payment->delete();
        });

        return back()->with('success', 'Payment deleted and account balance restored.');
    }

    public function printReceipt(StudentFeePayment $payment)
    {
        $schoolId = $this->currentSchoolId();
        abort_if((int) $payment->school_id !== $schoolId, 404);

        $payment->loadMissing('student:id,admission_no,first_name,last_name,class_level,stream');

        return view('admin.finance.print-receipt', [
            'payment' => $payment,
            'student' => $payment->student,
        ]);
    }

    private function currentSchoolId(): int
    {
        $school = app('currentSchool');

        if (! $school || ! isset($school->id)) {
            abort(404, 'No school context found.');
        }

        return (int) $school->id;
    }

    private function generateSchoolPaymentReference(int $schoolId): string
    {
        do {
            $candidate = 'RCPT-' . $schoolId . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        } while (StudentFeePayment::query()->where('receipt_no', $candidate)->exists());

        return $candidate;
    }
}