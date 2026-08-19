<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentFeeAccount;
use App\Models\StudentFeePayment;
use App\Services\FeeAllocationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StudentFinanceController extends Controller
{
    public function index(Request $request, FeeAllocationService $allocationService): Response
    {
        $searchStudent = trim((string) $request->get('search_student', ''));
        $searchReceipt = trim((string) $request->get('search_receipt', ''));

        $students = Student::query()
            ->select(['id', 'admission_no', 'first_name', 'last_name', 'class_level', 'stream'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $accounts = StudentFeeAccount::query()
            ->with(['student:id,admission_no,first_name,last_name,class_level,stream'])
            ->withSum('payments as paid_total', 'amount')
            ->when($searchStudent !== '', function ($q) use ($searchStudent) {
                $q->whereHas('student', function ($s) use ($searchStudent) {
                    $s->where('admission_no', 'like', "%{$searchStudent}%")
                        ->orWhere('first_name', 'like', "%{$searchStudent}%")
                        ->orWhere('last_name', 'like', "%{$searchStudent}%");
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $voteHeadsEnabled = $this->canUseVoteHeads();

        // AUTO-HEAL: ensure ledgers + historical allocations exist
        if ($voteHeadsEnabled) {
            $accounts->getCollection()->each(function (StudentFeeAccount $account) use ($allocationService) {
                $this->ensureVoteHeadDataForAccount($account, $allocationService);
            });
        }

        $accounts->setCollection(
            $accounts->getCollection()->map(function ($account) use ($allocationService, $voteHeadsEnabled) {
                $due = (float) ($account->total_fee_due ?? 0);
                $paid = (float) ($account->paid_total ?? 0);

                return [
                    'id' => $account->id,
                    'student' => $account->student,
                    'fee_structure_id' => $account->fee_structure_id ?? null,
                    'total_fee_due' => $due,
                    'paid_total' => $paid,
                    'balance' => $due - $paid,
                    'sponsor_org_name' => $account->sponsor_org_name,
                    'sponsor_org_id' => $account->sponsor_org_id,
                    'notes' => $account->notes,
                    'vote_breakdown' => $voteHeadsEnabled
                        ? $this->safeVoteBreakdown($allocationService, (int) $account->id)
                        : $this->emptyVoteBreakdown(),
                ];
            })
        );

        $recentPayments = StudentFeePayment::query()
            ->with([
                'student:id,admission_no,first_name,last_name',
                'account:id,total_fee_due,student_id',
            ])
            ->when($searchReceipt !== '', fn ($q) => $q->where('receipt_no', 'like', "%{$searchReceipt}%"))
            ->latest('paid_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $feeStructures = Schema::hasTable('fee_structures')
            ? DB::table('fee_structures')
                ->select(['id', 'name', 'year', 'category', 'class_level'])
                ->orderByDesc('year')
                ->orderBy('name')
                ->get()
            : collect();

        $voteHeads = Schema::hasTable('vote_heads')
            ? DB::table('vote_heads')
                ->where('is_active', true)
                ->orderBy('code')
                ->get(['id', 'code', 'name'])
            : collect();

        return Inertia::render('Admin/Finance/Students', [
            'students' => $students,
            'accounts' => $accounts,
            'recentPayments' => $recentPayments,
            'filters' => [
                'search_student' => $searchStudent,
                'search_receipt' => $searchReceipt,
            ],
            'paymentMethods' => ['cash', 'bank', 'mpesa', 'cheque', 'organization'],
            'feeStructures' => $feeStructures,
            'voteHeads' => $voteHeads,
            'voteHeadsEnabled' => $voteHeadsEnabled,
        ]);
    }

    public function upsertAccount(Request $request, FeeAllocationService $allocationService): RedirectResponse
    {
        $hasFeeStructures = Schema::hasTable('fee_structures');
        $hasFeeStructureColumn = Schema::hasColumn('student_fee_accounts', 'fee_structure_id');

        $rules = [
            'student_id' => ['required', 'exists:students,id'],
            'total_fee_due' => ['required', 'numeric', 'min:0'],
            'sponsor_org_name' => ['nullable', 'string', 'max:255'],
            'sponsor_org_id' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'fee_structure_id' => $hasFeeStructures ? ['nullable', 'exists:fee_structures,id'] : ['nullable'],
        ];

        $data = $request->validate($rules);

        $payload = [
            'total_fee_due' => $data['total_fee_due'],
            'sponsor_org_name' => $data['sponsor_org_name'] ?? null,
            'sponsor_org_id' => $data['sponsor_org_id'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];

        if ($hasFeeStructureColumn) {
            $payload['fee_structure_id'] = $data['fee_structure_id'] ?? null;
        }

        $account = StudentFeeAccount::updateOrCreate(
            ['student_id' => $data['student_id']],
            $payload
        );

        if (!empty($data['fee_structure_id']) && $this->canUseVoteHeads() && $hasFeeStructureColumn) {
            try {
                $allocationService->syncAccountLedgers($account);
            } catch (ValidationException $e) {
                // If payments already existed, recover by auto-bootstrap + backfill allocations
                $this->ensureVoteHeadDataForAccount($account, $allocationService);
            }

            return redirect()
                ->route('admin.student-finance.index')
                ->with('success', 'Student fee account saved and vote-head ledger synced.');
        }

        if (!empty($data['fee_structure_id']) && !$this->canUseVoteHeads()) {
            return redirect()
                ->route('admin.student-finance.index')
                ->with('warning', 'Account saved. Vote-head tables are not fully ready yet, ledger not synced.');
        }

        return redirect()
            ->route('admin.student-finance.index')
            ->with('success', 'Student fee account saved.');
    }

    public function storePayment(Request $request, FeeAllocationService $allocationService): RedirectResponse
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

            'use_priority_allocations' => ['nullable', 'boolean'],
            'priority_allocations' => ['nullable', 'array'],
            'priority_allocations.*.vote_head_id' => ['required_with:priority_allocations', 'integer'],
            'priority_allocations.*.amount' => ['required_with:priority_allocations', 'numeric', 'min:0.01'],
        ]);

        if ($data['payment_method'] === 'organization' && empty($data['organization_name'])) {
            throw ValidationException::withMessages([
                'organization_name' => 'Organization name is required for organization payment.',
            ]);
        }

        $account = StudentFeeAccount::firstOrCreate(
            ['student_id' => $data['student_id']],
            ['total_fee_due' => 0]
        );

        $usePriority = (bool) ($data['use_priority_allocations'] ?? false);
        $priorityAllocations = $data['priority_allocations'] ?? [];

        if ($usePriority) {
            if (!$this->canUseVoteHeads()) {
                throw ValidationException::withMessages([
                    'priority_allocations' => 'Vote-head allocation is not available until vote-head tables are ready.',
                ]);
            }

            if (!Schema::hasTable('vote_heads')) {
                throw ValidationException::withMessages([
                    'priority_allocations' => 'Vote heads table is missing.',
                ]);
            }

            if (empty($priorityAllocations)) {
                throw ValidationException::withMessages([
                    'priority_allocations' => 'Please provide vote-head allocation rows.',
                ]);
            }

            $validVoteHeadIds = DB::table('vote_heads')->pluck('id')->map(fn ($i) => (int) $i)->all();
            foreach ($priorityAllocations as $row) {
                $vh = (int) ($row['vote_head_id'] ?? 0);
                if (!in_array($vh, $validVoteHeadIds, true)) {
                    throw ValidationException::withMessages([
                        'priority_allocations' => "Invalid vote head selected (ID {$vh}).",
                    ]);
                }
            }

            $sum = (float) collect($priorityAllocations)->sum(fn ($r) => (float) ($r['amount'] ?? 0));
            if (round($sum, 2) !== round((float) $data['amount'], 2)) {
                throw ValidationException::withMessages([
                    'priority_allocations' => 'Sum of vote-head allocations must equal payment amount.',
                ]);
            }
        }

        $receiptNo = $data['receipt_no'] ?: $this->generateRunningNumber(StudentFeePayment::class, 'receipt_no', 'RCPT');

        $allocationWarning = null;

        DB::transaction(function () use ($data, $account, $receiptNo, $allocationService, $usePriority, $priorityAllocations, &$allocationWarning) {
            $payment = StudentFeePayment::create([
                'student_id' => $data['student_id'],
                'student_fee_account_id' => $account->id,
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'organization_name' => $data['organization_name'] ?? null,
                'organization_id' => $data['organization_id'] ?? null,
                'receipt_no' => $receiptNo,
                'paid_at' => $data['paid_at'],
                'recorded_by' => auth()->id(),
                'notes' => $data['notes'] ?? null,
            ]);

            if ($this->canUseVoteHeads()) {
                // ensure ledger exists before allocation
                $this->ensureVoteHeadDataForAccount($account, $allocationService);

                if (!empty($account->fee_structure_id)) {
                    try {
                        if ($usePriority) {
                            $allocationService->allocateParentPaymentWithPriorities($payment, $priorityAllocations);
                        } else {
                            $allocationService->allocateParentPayment($payment);
                        }
                    } catch (ValidationException $e) {
                        $allocationWarning = $e->getMessage();
                    }
                }
            }
        });

        if ($allocationWarning) {
            return redirect()
                ->route('admin.student-finance.index')
                ->with('warning', "Payment recorded (Receipt: {$receiptNo}), but allocation warning: {$allocationWarning}");
        }

        return redirect()
            ->route('admin.student-finance.index')
            ->with('success', "Payment recorded. Receipt No: {$receiptNo}");
    }

    public function destroyPayment(StudentFeePayment $payment): RedirectResponse
    {
        $payment->delete();

        return redirect()
            ->route('admin.student-finance.index')
            ->with('success', 'Payment entry deleted.');
    }

    public function printReceipt(StudentFeePayment $payment)
    {
        $payment->load(['student', 'account']);

        $school = $this->schoolInfo();

        $totalPaid = (float) StudentFeePayment::query()
            ->where('student_fee_account_id', $payment->student_fee_account_id)
            ->sum('amount');

        $due = (float) ($payment->account?->total_fee_due ?? 0);
        $balance = $due - $totalPaid;

        $allocations = collect();
        if (Schema::hasTable('student_fee_allocations') && Schema::hasTable('student_fee_ledgers') && Schema::hasTable('vote_heads')) {
            $allocations = DB::table('student_fee_allocations as a')
                ->join('student_fee_ledgers as l', 'l.id', '=', 'a.student_fee_ledger_id')
                ->join('vote_heads as v', 'v.id', '=', 'l.vote_head_id')
                ->where('a.student_fee_payment_id', $payment->id)
                ->select([
                    'a.allocated_amount',
                    'a.allocation_order',
                    'l.term',
                    'l.source',
                    'v.code as vote_code',
                    'v.name as vote_name',
                ])
                ->orderBy('a.allocation_order')
                ->get();
        }

        return view('admin.finance.print-receipt', [
            'school' => $school,
            'payment' => $payment,
            'due' => $due,
            'paidTotal' => $totalPaid,
            'balance' => $balance,
            'allocations' => $allocations,
        ]);
    }

    public function printStudentStatement(Student $student)
    {
        $account = StudentFeeAccount::query()
            ->with(['payments' => fn ($q) => $q->orderBy('paid_at')->orderBy('id')])
            ->where('student_id', $student->id)
            ->first();

        $school = $this->schoolInfo();
        $due = (float) ($account?->total_fee_due ?? 0);
        $paidTotal = (float) ($account?->payments?->sum('amount') ?? 0);
        $balance = $due - $paidTotal;

        return view('admin.finance.print-statement', [
            'school' => $school,
            'student' => $student,
            'account' => $account,
            'due' => $due,
            'paidTotal' => $paidTotal,
            'balance' => $balance,
        ]);
    }

    private function ensureVoteHeadDataForAccount(StudentFeeAccount $account, FeeAllocationService $allocationService): void
    {
        if (!$this->canUseVoteHeads()) {
            return;
        }

        if (empty($account->fee_structure_id)) {
            return;
        }

        // 1) Create ledgers if missing
        $hasLedgers = DB::table('student_fee_ledgers')
            ->where('student_fee_account_id', $account->id)
            ->exists();

        if (!$hasLedgers) {
            $this->bootstrapLedgersFromStructure($account);
        }

        // 2) Allocate historical payments that have no allocations yet
        $payments = StudentFeePayment::query()
            ->where('student_fee_account_id', $account->id)
            ->orderBy('paid_at')
            ->orderBy('id')
            ->get();

        foreach ($payments as $payment) {
            $hasAlloc = DB::table('student_fee_allocations')
                ->where('student_fee_payment_id', $payment->id)
                ->exists();

            if ($hasAlloc) {
                continue;
            }

            try {
                $allocationService->allocateParentPayment($payment);
            } catch (\Throwable $e) {
                // keep silent to avoid blocking page load
            }
        }
    }

    private function bootstrapLedgersFromStructure(StudentFeeAccount $account): void
    {
        if (!$account->fee_structure_id) {
            return;
        }

        $lines = DB::table('fee_structure_lines')
            ->where('fee_structure_id', $account->fee_structure_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($lines->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($account, $lines) {
            $rows = [];
            $parentExpectedTotal = 0.0;

            foreach ($lines as $line) {
                $termMap = [
                    'T1' => (float) $line->term1_amount,
                    'T2' => (float) $line->term2_amount,
                    'T3' => (float) $line->term3_amount,
                ];

                $parentTermSum = 0.0;

                foreach ($termMap as $term => $amt) {
                    if ($amt <= 0) continue;

                    $parentTermSum += $amt;
                    $parentExpectedTotal += $amt;

                    $rows[] = [
                        'student_id' => $account->student_id,
                        'student_fee_account_id' => $account->id,
                        'fee_structure_line_id' => $line->id,
                        'vote_head_id' => $line->vote_head_id,
                        'source' => 'parent',
                        'term' => $term,
                        'expected_amount' => $amt,
                        'paid_amount' => 0,
                        'balance_amount' => $amt,
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                $parentTotal = (float) $line->parent_total_amount;
                $remainder = max(0, $parentTotal - $parentTermSum);

                if ($remainder > 0) {
                    $parentExpectedTotal += $remainder;

                    $rows[] = [
                        'student_id' => $account->student_id,
                        'student_fee_account_id' => $account->id,
                        'fee_structure_line_id' => $line->id,
                        'vote_head_id' => $line->vote_head_id,
                        'source' => 'parent',
                        'term' => 'ANNUAL',
                        'expected_amount' => $remainder,
                        'paid_amount' => 0,
                        'balance_amount' => $remainder,
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                $cap = (float) $line->govt_capitation_amount;
                if ($cap > 0) {
                    $rows[] = [
                        'student_id' => $account->student_id,
                        'student_fee_account_id' => $account->id,
                        'fee_structure_line_id' => $line->id,
                        'vote_head_id' => $line->vote_head_id,
                        'source' => 'capitation',
                        'term' => 'ANNUAL',
                        'expected_amount' => $cap,
                        'paid_amount' => 0,
                        'balance_amount' => $cap,
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (!empty($rows)) {
                DB::table('student_fee_ledgers')->insert($rows);
            }

            // keep parent due aligned to structure
            DB::table('student_fee_accounts')
                ->where('id', $account->id)
                ->update([
                    'total_fee_due' => $parentExpectedTotal,
                    'updated_at' => now(),
                ]);
        });
    }

    private function canUseVoteHeads(): bool
    {
        return Schema::hasTable('vote_heads')
            && Schema::hasTable('fee_structures')
            && Schema::hasTable('fee_structure_lines')
            && Schema::hasTable('student_fee_ledgers')
            && Schema::hasTable('student_fee_allocations');
    }

    private function safeVoteBreakdown(FeeAllocationService $allocationService, int $accountId): array
    {
        try {
            return $allocationService->buildAccountVoteHeadBreakdown($accountId);
        } catch (\Throwable $e) {
            return $this->emptyVoteBreakdown();
        }
    }

    private function emptyVoteBreakdown(): array
    {
        return [
            'lines' => [],
            'totals' => [
                'parent_expected' => 0.0,
                'parent_paid' => 0.0,
                'parent_balance' => 0.0,
                'capitation_expected' => 0.0,
                'capitation_paid' => 0.0,
                'capitation_balance' => 0.0,
                't1_expected' => 0.0,
                't1_paid' => 0.0,
                't1_balance' => 0.0,
                't2_expected' => 0.0,
                't2_paid' => 0.0,
                't2_balance' => 0.0,
                't3_expected' => 0.0,
                't3_paid' => 0.0,
                't3_balance' => 0.0,
                'annual_expected' => 0.0,
                'annual_paid' => 0.0,
                'annual_balance' => 0.0,
            ],
        ];
    }

    private function schoolInfo(): array
    {
        $school = class_exists(School::class) ? School::query()->first() : null;

        return [
            'name' => $school?->name ?? config('app.name', 'Vigurungani Senior School'),
            'code' => $school?->code ?? '2109104',
            'location' => $school?->location ?? 'Kinango Constituency, Kwale County, Kenya',
            'logo' => '/images/logo.png',
        ];
    }

    private function generateRunningNumber(string $modelClass, string $column, string $prefix): string
    {
        $date = now()->format('Ymd');
        $base = "{$prefix}/{$date}";

        $latest = $modelClass::query()
            ->where($column, 'like', "{$base}%")
            ->orderByDesc($column)
            ->value($column);

        $next = 1;
        if ($latest && Str::contains($latest, $base)) {
            $lastDigits = (int) substr($latest, -4);
            $next = $lastDigits + 1;
        }

        return $base . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}