<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\FeeProfile;
use App\Models\FeeStructure;
use App\Models\Student;
use App\Models\StudentFeeAccount;
use App\Models\StudentFeeLedger;
use App\Models\StudentFeePayment;
use App\Models\VoteHead;
use App\Services\FeeAllocationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class StudentFinanceController extends Controller
{
    public function __construct(
        private readonly FeeAllocationService $allocationService
    ) {
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Index
    // ─────────────────────────────────────────────────────────────────────

    public function index(Request $request): Response
    {
        $schoolId = $this->currentSchoolId();

        $filters = [
            'search'      => (string) $request->input('search', ''),
            'class_level' => (string) $request->input('class_level', ''),
        ];

        $studentColumns = $this->studentSelectColumns();

        $students = Student::query()
            ->where('school_id', $schoolId)
            ->select($studentColumns)
            ->orderBy('first_name')
            ->limit(3000)
            ->get();

        $feeStructures = FeeStructure::query()
            ->where('school_id', $schoolId)
            ->with('lines')
            ->orderBy('name')
            ->get()
            ->map(function ($s) {
                $s->total_amount = $s->lines->sum(fn ($l) => $l->expectedParentAmount());
                return $s;
            });

        $voteHeads = VoteHead::query()
            ->when(
                Schema::hasColumn('vote_heads', 'is_active'),
                fn ($q) => $q->where('is_active', true)
            )
            ->orderBy('name')
            ->get();

        // FIX: populate fee profiles (Gap 8 — dropdown was permanently empty)
        $feeProfiles = [];
        if (Schema::hasTable('fee_profiles')) {
            $feeProfiles = FeeProfile::query()
                ->where('school_id', $schoolId)
                ->when(
                    Schema::hasColumn('fee_profiles', 'is_active'),
                    fn ($q) => $q->where('is_active', true)
                )
                ->orderBy('name')
                ->get(['id', 'code', 'name']);
        }

        $accountsRaw = StudentFeeAccount::query()
            ->where('school_id', $schoolId)
            ->with([
                'student' => fn ($q) => $q->select($studentColumns),
                'payments' => fn ($q) => $q->orderByDesc('paid_at')->orderByDesc('id'),
                'ledgers',
            ])
            ->orderByDesc('id')
            ->get();

        $hasOpeningBalance = Schema::hasColumn('student_fee_accounts', 'opening_balance');
        $hasPaymentStatus  = Schema::hasColumn('student_fee_payments', 'status');

        $accounts = $accountsRaw->map(function (StudentFeeAccount $account) use ($hasOpeningBalance, $hasPaymentStatus) {
            $student = $account->student;

            $studentName = trim(
                (string) ($student->name ?? '') ?: (($student->first_name ?? '') . ' ' . ($student->last_name ?? ''))
            );

            $admission = $student->admission_no
                ?? $student->admission_number
                ?? $student->adm_no
                ?? null;

            $openingBalance = $hasOpeningBalance
                ? (float) ($account->opening_balance ?? 0)
                : 0.0;

            $expected = (float) $account->ledgers->sum(fn ($l) => $l->computed_expected);
            $totalExpected = $expected + $openingBalance;

            // Only count active payments
            $activePayments = $account->payments;
            if ($hasPaymentStatus) {
                $activePayments = $activePayments->filter(function ($p) {
                    return !isset($p->status) || $p->status !== 'reversed';
                });
            }
            $paid = (float) $activePayments->sum('amount');

            $balance = max($totalExpected - $paid, 0);
            $credit  = max($paid - $totalExpected, 0);

            return [
                'id'               => $account->id,
                'student_id'       => $account->student_id,
                'student_name'     => $studentName,
                'admission_no'     => $admission,
                'class_level'      => $student->class_level ?? null,
                'stream'           => $student->stream ?? null,
                'fee_structure_id' => $account->fee_structure_id ?? null,
                'expected_amount'  => round($totalExpected, 2),
                'total_paid'       => round($paid, 2),
                'balance'          => round($balance, 2),
                'credit_balance'   => round($credit, 2),
                'total_fee_due'    => round($totalExpected - $paid, 2),
            ];
        })->values();

        $accountsFiltered = $accounts->filter(function ($row) use ($filters) {
            $q     = strtolower(trim($filters['search']));
            $level = trim($filters['class_level']);

            $matchesSearch = $q === ''
                || str_contains(strtolower((string) ($row['student_name'] ?? '')), $q)
                || str_contains(strtolower((string) ($row['admission_no'] ?? '')), $q);

            $matchesClass = $level === '' || (string) ($row['class_level'] ?? '') === $level;

            return $matchesSearch && $matchesClass;
        })->values();

        $payments = StudentFeePayment::query()
            ->where('school_id', $schoolId)
            ->with(['student', 'account'])
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->limit(300)
            ->get()
            ->map(function (StudentFeePayment $payment) {
                $student = $payment->student ?? $payment->account?->student;

                $studentName = trim(
                    (string) ($student->name ?? '') ?: (($student->first_name ?? '') . ' ' . ($student->last_name ?? ''))
                );

                return [
                    'id'                => $payment->id,
                    'student_id'        => $payment->student_id ?? $payment->account?->student_id,
                    'student_name'      => $studentName,
                    'amount'            => (float) $payment->amount,
                    'payment_method'    => $payment->payment_method ?? null,
                    'method'            => $payment->payment_method ?? null,
                    'organization_name' => $payment->organization_name ?? null,
                    'organization_id'   => $payment->organization_id ?? null,
                    'receipt_no'        => $payment->receipt_no ?? null,
                    'paid_at'           => optional($payment->paid_at)->toDateString() ?? null,
                    'created_at'        => optional($payment->created_at)?->toDateTimeString(),
                    'status'            => $payment->status ?? 'active',
                ];
            })
            ->values();

        $summary = [
            'students_count' => $students->count(),
            'accounts_count' => $accountsFiltered->count(),
            'total_billed'   => round((float) $accountsFiltered->sum('expected_amount'), 2),
            'total_paid'     => round((float) $accountsFiltered->sum('total_paid'), 2),
            'total_balance'  => round((float) $accountsFiltered->sum('balance'), 2),
            'total_credit'   => round((float) $accountsFiltered->sum('credit_balance'), 2),
        ];

        return Inertia::render('Admin/Finance/StudentFinance/Index', [
            'summary'        => $summary,
            'students'       => $students,
            'voteHeads'      => $voteHeads,
            'feeProfiles'    => $feeProfiles,
            'accounts'       => $accountsFiltered,
            'recentPayments' => $payments->take(30)->values(),
            'payments'       => $payments,
            'feeStructures'  => $feeStructures,
            'structures'     => $feeStructures,
            'fee_structures' => $feeStructures,
            'paymentMethods' => ['cash', 'bank', 'mpesa', 'cheque', 'transfer'],
            'filters'        => $filters,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Account Upsert (Schema-guarded — safe before and after migration)
    // ─────────────────────────────────────────────────────────────────────

    public function storeAccount(Request $request): RedirectResponse
    {
        $schoolId = $this->currentSchoolId();

        $validated = $request->validate([
            'student_id'       => ['required', 'integer', Rule::exists('students', 'id')],
            'fee_structure_id' => ['nullable', 'integer', Rule::exists('fee_structures', 'id')],
            'opening_balance'  => ['nullable', 'numeric'],
            'total_fee_due'    => ['nullable', 'numeric'],
            'sponsor_org_name' => ['nullable', 'string', 'max:255'],
            'sponsor_org_id'   => ['nullable', 'string', 'max:255'],
            'notes'            => ['nullable', 'string'],
        ]);

        $student = Student::query()
            ->where('school_id', $schoolId)
            ->findOrFail($validated['student_id']);

        DB::transaction(function () use ($schoolId, $student, $validated): void {
            $account = StudentFeeAccount::query()->firstOrNew([
                'school_id'  => $schoolId,
                'student_id' => $student->id,
            ]);

            // Schema-guarded assignments: prevents crash if migrations haven't run yet
            if (Schema::hasColumn('student_fee_accounts', 'fee_structure_id')) {
                $account->fee_structure_id = $validated['fee_structure_id'] ?? null;
            }

            if (Schema::hasColumn('student_fee_accounts', 'opening_balance')) {
                $account->opening_balance = (float) ($validated['opening_balance'] ?? 0);
            }

            if (Schema::hasColumn('student_fee_accounts', 'sponsor_org_name')) {
                $account->sponsor_org_name = $validated['sponsor_org_name'] ?? null;
            }

            if (Schema::hasColumn('student_fee_accounts', 'sponsor_org_id')) {
                $account->sponsor_org_id = $validated['sponsor_org_id'] ?? null;
            }

            if (Schema::hasColumn('student_fee_accounts', 'notes')) {
                $account->notes = $validated['notes'] ?? null;
            }

            $account->save();

            if (!empty($validated['fee_structure_id'])) {
                $this->allocationService->syncLedgersFromStructure($account, (int) $validated['fee_structure_id']);
            }

            $this->recomputeAccountTotalFeeDue($account);
        });

        return redirect()
            ->route('admin.student-finance.index')
            ->with('success', 'Student fee account saved successfully.');
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Payment Recording (Schema-guarded)
    // ─────────────────────────────────────────────────────────────────────

    public function storePayment(Request $request): RedirectResponse
    {
        $schoolId = $this->currentSchoolId();

        $validated = $request->validate([
            'student_fee_account_id'   => ['nullable', 'integer', Rule::exists('student_fee_accounts', 'id')],
            'student_id'               => ['nullable', 'integer', Rule::exists('students', 'id')],
            'amount'                   => ['required', 'numeric', 'gt:0'],
            'method'                   => ['nullable', 'string', 'max:50'],
            'payment_method'           => ['nullable', 'string', 'max:50'],
            'organization_name'        => ['nullable', 'string', 'max:255'],
            'organization_id'          => ['nullable', 'string', 'max:255'],
            'reference'                => ['nullable', 'string', 'max:255'],
            'paid_at'                  => ['nullable', 'date'],
            'notes'                    => ['nullable', 'string'],
            'priority_vote_head_ids'   => ['nullable', 'array'],
            'priority_vote_head_ids.*' => ['integer'],
        ]);

        DB::transaction(function () use ($schoolId, $validated): void {
            $account   = $this->resolveAccountForPayment($schoolId, $validated);
            $studentId = $this->resolveLedgerStudentId($account);

            $method = $validated['method'] ?? $validated['payment_method'] ?? 'cash';

            // Build payload with schema guards
            $paymentPayload = [
                'school_id'              => $schoolId,
                'student_fee_account_id' => $account->id,
                'amount'                 => (float) $validated['amount'],
                'paid_at'                => !empty($validated['paid_at']) ? Carbon::parse($validated['paid_at']) : now(),
            ];

            if (Schema::hasColumn('student_fee_payments', 'student_id')) {
                $paymentPayload['student_id'] = $studentId;
            }

            if (Schema::hasColumn('student_fee_payments', 'payment_method')) {
                $paymentPayload['payment_method'] = $method;
            }

            if (Schema::hasColumn('student_fee_payments', 'organization_name')) {
                $paymentPayload['organization_name'] = $validated['organization_name'] ?? null;
            }

            if (Schema::hasColumn('student_fee_payments', 'organization_id')) {
                $paymentPayload['organization_id'] = $validated['organization_id'] ?? $validated['reference'] ?? null;
            }

            if (Schema::hasColumn('student_fee_payments', 'recorded_by')) {
                $paymentPayload['recorded_by'] = Auth::id();
            }

            if (Schema::hasColumn('student_fee_payments', 'notes')) {
                $paymentPayload['notes'] = $validated['notes'] ?? null;
            }

            if (Schema::hasColumn('student_fee_payments', 'status')) {
                $paymentPayload['status'] = 'active';
            }

            /** @var StudentFeePayment $payment */
            $payment = StudentFeePayment::query()->create($paymentPayload);

            // FIX: generate receipt number from auto-increment ID (collision-proof)
            if (Schema::hasColumn('student_fee_payments', 'receipt_no')) {
                $year      = now()->year;
                $receiptNo = 'RCPT-' . $year . '-' . str_pad((string) $payment->id, 5, '0', STR_PAD_LEFT);
                $payment->update(['receipt_no' => $receiptNo]);
            }

            // The service handles all locking internally (Gap 5 fix)
            $student = Student::query()->findOrFail($studentId);

            $plan = $this->allocationService->allocateAcrossVoteHeadLedgers(
                account: $account,
                paymentAmount: (float) $validated['amount'],
                student: $student,
                priorityConstraints: [
                    'vote_head_ids' => $validated['priority_vote_head_ids'] ?? [],
                ]
            );

            if (!empty($plan['allocations'])) {
                $this->allocationService->applyAllocationsToPayment(
                    payment: $payment,
                    allocations: $plan['allocations'],
                    schoolId: $schoolId,
                    recordedBy: Auth::id()
                );
            }

            $this->recomputeAccountTotalFeeDue($account);
        });

        return redirect()
            ->route('admin.student-finance.index')
            ->with('success', 'Payment recorded successfully.');
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Payment Reversal (Gap 6 fix + Activity Log)
    // ─────────────────────────────────────────────────────────────────────

    public function destroyPayment(Request $request, StudentFeePayment $payment): RedirectResponse
    {
        $schoolId = $this->currentSchoolId();
        abort_unless((int) $payment->school_id === $schoolId, HttpResponse::HTTP_FORBIDDEN);

        $canReverse = Schema::hasColumn('student_fee_payments', 'status');

        DB::transaction(function () use ($payment, $schoolId, $request, $canReverse): void {
            $account = StudentFeeAccount::query()
                ->where('school_id', $schoolId)
                ->lockForUpdate()
                ->find($payment->student_fee_account_id);

            $this->allocationService->reversePaymentAllocations($payment, $schoolId);

            if ($canReverse) {
                $updatePayload = ['status' => 'reversed'];

                if (Schema::hasColumn('student_fee_payments', 'reversed_at')) {
                    $updatePayload['reversed_at'] = now();
                }

                if (Schema::hasColumn('student_fee_payments', 'reversed_by')) {
                    $updatePayload['reversed_by'] = Auth::id();
                }

                if (Schema::hasColumn('student_fee_payments', 'reversal_reason')) {
                    $updatePayload['reversal_reason'] = $request->input('reason', 'User requested reversal');
                }

                $payment->update($updatePayload);
            } else {
                // Pre-migration fallback: hard delete (last resort)
                $payment->delete();
            }

            // Activity Log Integration
            if (Schema::hasTable('activity_logs')) {
                try {
                    ActivityLog::create([
                        'user_id'     => Auth::id(),
                        'module'      => 'Finance',
                        'action'      => 'payment_reversal',
                        'record_type' => 'StudentFeePayment',
                        'record_id'   => $payment->id,
                        'old_values'  => json_encode([
                            'status' => 'active',
                            'amount' => (float) $payment->amount,
                        ]),
                        'new_values'  => json_encode([
                            'status'  => 'reversed',
                            'reason'  => $request->input('reason', 'User requested reversal'),
                            'reversed_by' => Auth::id(),
                        ]),
                        'ip_address'  => $request->ip(),
                        'user_agent'  => $request->userAgent(),
                    ]);
                } catch (\Throwable $e) {
                    // Don't block the reversal if logging fails
                    \Illuminate\Support\Facades\Log::warning('Activity log write failed during payment reversal', [
                        'payment_id' => $payment->id,
                        'error'      => $e->getMessage(),
                    ]);
                }
            }

            if ($account) {
                $this->recomputeAccountTotalFeeDue($account);
            }
        });

        $msg = $canReverse
            ? 'Payment reversed and balances recalculated.'
            : 'Payment deleted and balances recalculated.';

        return redirect()
            ->route('admin.student-finance.index')
            ->with('success', $msg);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Receipt (Gap 7 fix)
    // ─────────────────────────────────────────────────────────────────────

    public function printReceipt(Request $request, StudentFeePayment $payment)
    {
        $schoolId = $this->currentSchoolId();
        abort_unless((int) $payment->school_id === $schoolId, HttpResponse::HTTP_FORBIDDEN);

        $payment->loadMissing(['student', 'account.student', 'account.feeStructure', 'recorder']);

        // Show historical allocations on receipt even if reversed
        $breakdown      = $this->allocationService->getPaymentBreakdown($payment, includeReversed: true);
        $totalAllocated = $breakdown->sum('amount');
        $unallocated    = max((float) $payment->amount - $totalAllocated, 0);

        if (view()->exists('admin.finance.print_receipt')) {
            return response()->view('admin.finance.print_receipt', [
                'payment'        => $payment,
                'student'        => $payment->student ?? $payment->account?->student,
                'breakdown'      => $breakdown,
                'totalAllocated' => $totalAllocated,
                'unallocated'    => $unallocated,
            ]);
        }

        return response()->json([
            'message' => 'Receipt view missing at resources/views/admin/finance/print_receipt.blade.php',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Student Financial Statement
    // ─────────────────────────────────────────────────────────────────────

    public function printStatement(Request $request, StudentFeeAccount $account)
    {
        $schoolId = $this->currentSchoolId();
        abort_unless((int) $account->school_id === $schoolId, HttpResponse::HTTP_FORBIDDEN);

        $account->loadMissing([
            'student',
            'ledgers',
            'payments' => fn ($q) => $q->when(
                Schema::hasColumn('student_fee_payments', 'status'),
                fn ($q2) => $q2->where(function ($q3) {
                    $q3->whereNull('status')->orWhere('status', '!=', 'reversed');
                })
            )->orderBy('paid_at'),
        ]);

        $school = app()->bound('currentSchool') ? app('currentSchool') : null;

        $schoolInfo = [
            'name'     => $school->name ?? config('app.name', 'School'),
            'code'     => $school->code ?? 'N/A',
            'location' => $school->location ?? 'N/A',
            'logo'     => null,
        ];

        if (!empty($school?->logo_path)) {
            try {
                $schoolInfo['logo'] = \Illuminate\Support\Facades\Storage::url($school->logo_path);
            } catch (\Throwable $e) {
                $schoolInfo['logo'] = null;
            }
        }

        if (empty($schoolInfo['logo']) && file_exists(public_path('images/school-logo.png'))) {
            $schoolInfo['logo'] = asset('images/school-logo.png');
        }

        $hasOpeningBalance = Schema::hasColumn('student_fee_accounts', 'opening_balance');
        $opening = $hasOpeningBalance ? (float) ($account->opening_balance ?? 0) : 0.0;

        $expected  = (float) $account->ledgers->sum(fn ($l) => $l->computed_expected) + $opening;
        $paidTotal = (float) $account->payments->sum('amount');
        $balance   = round($expected - $paidTotal, 2);

        if (view()->exists('admin.finance.print-statement')) {
            return response()->view('admin.finance.print-statement', [
                'school'    => $schoolInfo,
                'student'   => $account->student,
                'account'   => $account,
                'due'       => round($expected, 2),
                'paidTotal' => round($paidTotal, 2),
                'balance'   => $balance,
            ]);
        }

        return response()->json([
            'message' => 'Statement view missing at resources/views/admin/finance/print-statement.blade.php',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Votehead Report (Gap 8 fix)
    // ─────────────────────────────────────────────────────────────────────

    public function voteheadReport(Request $request): JsonResponse|Response
    {
        $schoolId = $this->currentSchoolId();

        [$from, $to] = $this->resolvePeriodDates(
            (string) $request->input('period', 'month'),
            $request->input('from'),
            $request->input('to')
        );

        $rows = collect();

        if (Schema::hasTable('student_fee_allocations') && Schema::hasTable('student_fee_payments')) {
            $amountCol = Schema::hasColumn('student_fee_allocations', 'allocated_amount')
                ? 'allocated_amount'
                : 'amount';

            $query = DB::table('student_fee_allocations as a')
                ->join('student_fee_ledgers as l', 'l.id', '=', 'a.student_fee_ledger_id')
                ->join('student_fee_payments as p', 'p.id', '=', 'a.student_fee_payment_id')
                ->leftJoin('students as s', 's.id', '=', 'p.student_id')
                ->leftJoin('fee_profiles as fp', 'fp.id', '=', 's.fee_profile_id')
                ->leftJoin('vote_heads as vh', 'vh.id', '=', 'l.vote_head_id')
                ->where('l.school_id', $schoolId);

            // Filter by payment date
            $query->whereBetween('p.paid_at', [
                $from->copy()->startOfDay(),
                $to->copy()->endOfDay(),
            ]);

            // Ignore reversed payments and allocations
            if (Schema::hasColumn('student_fee_allocations', 'status')) {
                $query->where(function ($q) {
                    $q->whereNull('a.status')->orWhere('a.status', '!=', 'reversed');
                });
            }
            if (Schema::hasColumn('student_fee_payments', 'status')) {
                $query->where(function ($q) {
                    $q->whereNull('p.status')->orWhere('p.status', '!=', 'reversed');
                });
            }

            $voteHeadId = $request->input('vote_head_id');
            if (!empty($voteHeadId)) {
                $query->where('l.vote_head_id', (int) $voteHeadId);
            }

            $profileId = $request->input('fee_profile_id');
            if (!empty($profileId)) {
                $query->where('s.fee_profile_id', (int) $profileId);
            }

            $rows = $query
                ->selectRaw('l.vote_head_id')
                ->selectRaw('COALESCE(vh.name, "Unassigned") as vote_head_name')
                ->selectRaw('s.fee_profile_id')
                ->selectRaw('fp.name as fee_profile_name')
                ->selectRaw('fp.code as fee_profile_code')
                ->selectRaw("SUM(COALESCE(a.{$amountCol},0)) as total_collected")
                ->groupBy('l.vote_head_id', 'vh.name', 's.fee_profile_id', 'fp.name', 'fp.code')
                ->orderBy('vote_head_name')
                ->get()
                ->map(fn ($r) => [
                    'vote_head_id'     => $r->vote_head_id,
                    'vote_head_name'   => $r->vote_head_name,
                    'fee_profile_id'   => $r->fee_profile_id,
                    'fee_profile_name' => $r->fee_profile_name,
                    'fee_profile_code' => $r->fee_profile_code,
                    'total_collected'  => round((float) $r->total_collected, 2),
                ])
                ->values();
        }

        $response = [
            'period'      => (string) $request->input('period', 'month'),
            'from'        => $from->toDateString(),
            'to'          => $to->toDateString(),
            'grand_total' => round((float) $rows->sum('total_collected'), 2),
            'rows'        => $rows,
        ];

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($response);
        }

        return Inertia::render('Admin/Finance/StudentFinance/VoteheadReport', $response);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Private Helpers
    // ─────────────────────────────────────────────────────────────────────

    private function recomputeAccountTotalFeeDue(StudentFeeAccount $account): void
    {
        if (!Schema::hasColumn('student_fee_accounts', 'total_fee_due')) {
            return;
        }

        $account->refresh();

        $ledgerRows = StudentFeeLedger::query()
            ->withoutGlobalScope('school')
            ->where('student_fee_account_id', $account->id)
            ->get();

        $hasOpening = Schema::hasColumn('student_fee_accounts', 'opening_balance');
        $opening    = $hasOpening ? (float) ($account->opening_balance ?? 0) : 0.0;

        $expected = (float) $ledgerRows->sum(fn ($l) => $l->computed_expected);

        $paid = (float) StudentFeePayment::query()
            ->withoutGlobalScope('school')
            ->where('student_fee_account_id', $account->id)
            ->where(function ($q) {
                if (Schema::hasColumn('student_fee_payments', 'status')) {
                    $q->whereNull('status')->orWhere('status', '!=', 'reversed');
                }
            })
            ->sum('amount');

        $account->total_fee_due = round($opening + $expected - $paid, 2);
        $account->save();
    }

    private function resolveAccountForPayment(int $schoolId, array $validated): StudentFeeAccount
    {
        $accountId = $validated['student_fee_account_id'] ?? null;
        if (!empty($accountId)) {
            $account = StudentFeeAccount::query()
                ->where('school_id', $schoolId)
                ->find($accountId);
            if ($account) return $account;
        }

        $studentId = $validated['student_id'] ?? null;
        if (!empty($studentId)) {
            $account = StudentFeeAccount::query()
                ->where('school_id', $schoolId)
                ->where('student_id', $studentId)
                ->first();
            if ($account) return $account;
        }

        throw ValidationException::withMessages([
            'student_fee_account_id' => 'No student fee account found. Create/update account first.',
        ]);
    }

    private function resolveLedgerStudentId(StudentFeeAccount $account): int
    {
        $studentId = $account->student_id ?? $account->student?->id;

        if (!$studentId) {
            throw ValidationException::withMessages([
                'student_fee_account_id' => 'Selected account has no linked student (student_id).',
            ]);
        }

        return (int) $studentId;
    }

    private function studentSelectColumns(): array
    {
        $columns = ['id'];
        foreach (['school_id', 'first_name', 'last_name', 'admission_no', 'class_level', 'stream'] as $col) {
            if (Schema::hasColumn('students', $col)) {
                $columns[] = $col;
            }
        }
        return array_values(array_unique($columns));
    }

    private function resolvePeriodDates(string $period, $fromInput, $toInput): array
    {
        $now = now();
        return match ($period) {
            'day'    => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'week'   => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'custom' => [
                $fromInput ? Carbon::parse($fromInput)->startOfDay() : $now->copy()->startOfMonth(),
                $toInput   ? Carbon::parse($toInput)->endOfDay()     : $now->copy()->endOfMonth(),
            ],
            default  => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }

    private function currentSchoolId(): int
    {
        $school = app()->bound('currentSchool') ? app('currentSchool') : null;
        if ($school && $school->id) {
            return (int) $school->id;
        }

        $user     = Auth::user();
        $schoolId = $user->school_id ?? $user->current_school_id ?? null;

        if (!$schoolId) {
            abort(HttpResponse::HTTP_UNPROCESSABLE_ENTITY, 'No school context found for this user.');
        }

        return (int) $schoolId;
    }
}