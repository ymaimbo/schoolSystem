<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeeStructure;
use App\Models\FeeStructureLine;
use App\Models\Student;
use App\Models\StudentFeeAccount;
use App\Models\StudentFeeLedger;
use App\Models\StudentFeePayment;
use App\Models\VoteHead;
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
    public function index(Request $request): Response
    {
        $schoolId = $this->currentSchoolId();
        $filters = [
            'search' => (string) $request->string('search'),
            'class_level' => (string) $request->string('class_level'),
        ];

        $studentColumns = $this->studentSelectColumns();
        $students = Student::query()
            ->where('school_id', $schoolId)
            ->whereNull('deleted_at')
            ->select($studentColumns)
            ->orderBy('first_name')
            ->limit(2000)
            ->get();

        $feeStructures = FeeStructure::query()
            ->where('school_id', $schoolId)
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        $voteHeads = VoteHead::query()
            ->where(function ($q) {
                if (Schema::hasColumn('vote_heads', 'is_active')) {
                    $q->where('is_active', true);
                }
            })
            ->orderBy('name')
            ->get();

        $accountsRaw = StudentFeeAccount::query()
            ->where('school_id', $schoolId)
            ->with([
                'student',
                'payments' => fn ($q) => $q->orderByDesc('paid_at')->orderByDesc('id'),
                'ledgers',
            ])
            ->get();

        $accounts = $accountsRaw->map(function (StudentFeeAccount $account) {
            $student = $account->student;

            $studentName = trim(
                (string) ($student->name ?? '') ?: (($student->first_name ?? '') . ' ' . ($student->last_name ?? ''))
            );

            $admission = $student->admission_no
                ?? $student->admission_number
                ?? $student->adm_no
                ?? null;

            $expected = (float) $account->ledgers->sum(function ($ledger) {
                if (isset($ledger->parent_amount)) return (float) $ledger->parent_amount;
                if (isset($ledger->expected_amount)) return (float) $ledger->expected_amount;
                return 0;
            });

            $paid = (float) $account->payments->sum('amount');
            $balance = max($expected - $paid, 0);
            $credit = max($paid - $expected, 0);

            return [
                'id' => $account->id,
                'student_id' => $account->student_id,
                'student_name' => $studentName,
                'admission_no' => $admission,
                'class_level' => $student->class_level ?? null,
                'stream' => $student->stream ?? null,
                'fee_structure_id' => $account->fee_structure_id ?? null,
                'expected_amount' => round($expected, 2),
                'total_paid' => round($paid, 2),
                'balance' => round($balance, 2),
                'credit_balance' => round($credit, 2),
                'total_fee_due' => round($balance - $credit, 2),
            ];
        })->values();

        $filteredAccounts = $accounts->filter(function ($row) use ($filters) {
            $q = strtolower(trim($filters['search']));
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
            ->limit(200)
            ->get()
            ->map(function (StudentFeePayment $payment) {
                $student = $payment->student;
                $studentName = trim(
                    (string) ($student->name ?? '') ?: (($student->first_name ?? '') . ' ' . ($student->last_name ?? ''))
                );

                return [
                    'id' => $payment->id,
                    'student_id' => $payment->student_id ?? $payment->account?->student_id,
                    'student_name' => $studentName,
                    'amount' => (float) $payment->amount,
                    'payment_method' => $payment->method ?? $payment->payment_method ?? null,
                    'method' => $payment->method ?? $payment->payment_method ?? null,
                    'organization_name' => $payment->organization_name ?? null,
                    'organization_id' => $payment->reference ?? $payment->organization_id ?? null,
                    'receipt_no' => $payment->receipt_no ?? null,
                    'paid_at' => optional($payment->paid_at)->toDateString() ?? null,
                    'created_at' => optional($payment->created_at)?->toDateTimeString(),
                ];
            })
            ->values();

        $summary = [
            'students_count' => $students->count(),
            'accounts_count' => $filteredAccounts->count(),
            'total_billed' => round((float) $filteredAccounts->sum('expected_amount'), 2),
            'total_paid' => round((float) $filteredAccounts->sum('total_paid'), 2),
            'total_balance' => round((float) $filteredAccounts->sum('balance'), 2),
            'total_credit' => round((float) $filteredAccounts->sum('credit_balance'), 2),
        ];

        return Inertia::render('Admin/Finance/StudentFinance/Index', [
            'summary' => $summary,
            'students' => $students,
            'voteHeads' => $voteHeads,
            'feeProfiles' => [],
            'accounts' => $filteredAccounts,
            'recentPayments' => $payments->take(20)->values(),
            'payments' => $payments,
            'feeStructures' => $feeStructures,
            'structures' => $feeStructures,
            'fee_structures' => $feeStructures,
            'paymentMethods' => ['cash', 'bank', 'mpesa', 'cheque', 'transfer'],
            'filters' => $filters,
        ]);
    }

    public function storeAccount(Request $request): RedirectResponse
    {
        $schoolId = $this->currentSchoolId();

        $validated = $request->validate([
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'fee_structure_id' => ['nullable', 'integer', Rule::exists('fee_structures', 'id')],
            'total_fee_due' => ['nullable', 'numeric'],
            'sponsor_org_name' => ['nullable', 'string', 'max:255'],
            'sponsor_org_id' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $student = Student::query()
            ->where('school_id', $schoolId)
            ->whereNull('deleted_at')
            ->findOrFail($validated['student_id']);

        if (!empty($validated['fee_structure_id'])) {
            FeeStructure::query()
                ->where('school_id', $schoolId)
                ->findOrFail($validated['fee_structure_id']);
        }

        DB::transaction(function () use ($schoolId, $student, $validated): void {
            $account = StudentFeeAccount::query()->firstOrNew([
                'school_id' => $schoolId,
                'student_id' => $student->id,
            ]);

            if (Schema::hasColumn('student_fee_accounts', 'fee_structure_id')) {
                $account->fee_structure_id = $validated['fee_structure_id'] ?? null;
            }

            if (Schema::hasColumn('student_fee_accounts', 'total_fee_due')) {
                $account->total_fee_due = (float) ($validated['total_fee_due'] ?? 0);
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
                $this->syncLedgersFromFeeStructure($account, (int) $validated['fee_structure_id']);
                $this->recomputeLedgersFromPayments($account, []);
                $this->recomputeAccountTotalFeeDue($account);
            }
        });

        return back()->with('success', 'Student finance account saved successfully.');
    }

    public function storePayment(Request $request): RedirectResponse
    {
        $schoolId = $this->currentSchoolId();

        $validated = $request->validate([
            'student_fee_account_id' => ['nullable', 'integer', Rule::exists('student_fee_accounts', 'id')],
            'method' => ['nullable', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:255'],

            'student_id' => ['nullable', 'integer', Rule::exists('students', 'id')],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'organization_name' => ['nullable', 'string', 'max:255'],
            'organization_id' => ['nullable', 'string', 'max:255'],

            'amount' => ['required', 'numeric', 'gt:0'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'priority_vote_head_ids' => ['nullable', 'array'],
            'priority_vote_head_ids.*' => ['integer'],
        ]);

        DB::transaction(function () use ($schoolId, $validated): void {
            $account = $this->resolveAccountForPayment($schoolId, $validated);
            $studentId = $this->resolveLedgerStudentId($account);

            $method = $validated['method'] ?? $validated['payment_method'] ?? 'cash';
            $reference = $validated['reference'] ?? $validated['organization_id'] ?? $validated['organization_name'] ?? null;

            $paymentPayload = [
                'school_id' => $schoolId,
                'student_fee_account_id' => $account->id,
                'amount' => (float) $validated['amount'],
                'paid_at' => !empty($validated['paid_at']) ? Carbon::parse($validated['paid_at']) : now(),
                'notes' => $validated['notes'] ?? null,
            ];

            if (Schema::hasColumn('student_fee_payments', 'student_id')) {
                $paymentPayload['student_id'] = $studentId;
            }

            if (Schema::hasColumn('student_fee_payments', 'method')) {
                $paymentPayload['method'] = $method;
            } elseif (Schema::hasColumn('student_fee_payments', 'payment_method')) {
                $paymentPayload['payment_method'] = $method;
            }

            if (Schema::hasColumn('student_fee_payments', 'reference')) {
                $paymentPayload['reference'] = $reference;
            } elseif (Schema::hasColumn('student_fee_payments', 'organization_id')) {
                $paymentPayload['organization_id'] = $reference;
            }

            if (Schema::hasColumn('student_fee_payments', 'organization_name')) {
                $paymentPayload['organization_name'] = $validated['organization_name'] ?? null;
            }

            if (Schema::hasColumn('student_fee_payments', 'received_by')) {
                $paymentPayload['received_by'] = Auth::id();
            }

            StudentFeePayment::query()->create($paymentPayload);

            $priorityVoteHeadIds = collect($validated['priority_vote_head_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->values()
                ->all();

            $this->recomputeLedgersFromPayments($account, $priorityVoteHeadIds);
            $this->recomputeAccountTotalFeeDue($account);
        });

        return back()->with('success', 'Payment recorded successfully.');
    }

    public function destroyPayment(Request $request, StudentFeePayment $payment): RedirectResponse
    {
        $schoolId = $this->currentSchoolId();
        abort_unless((int) $payment->school_id === $schoolId, HttpResponse::HTTP_FORBIDDEN);

        DB::transaction(function () use ($payment, $schoolId): void {
            $account = StudentFeeAccount::query()
                ->where('school_id', $schoolId)
                ->find($payment->student_fee_account_id);

            $payment->delete();

            if ($account) {
                $this->recomputeLedgersFromPayments($account, []);
                $this->recomputeAccountTotalFeeDue($account);
            }
        });

        return back()->with('success', 'Payment deleted and balances recalculated.');
    }

    public function printReceipt(Request $request, StudentFeePayment $payment)
    {
        $schoolId = $this->currentSchoolId();
        abort_unless((int) $payment->school_id === $schoolId, HttpResponse::HTTP_FORBIDDEN);

        $payment->loadMissing(['student', 'account.student', 'account.feeStructure']);

        if (view()->exists('admin.student-finance.receipt')) {
            return response()->view('admin.student-finance.receipt', compact('payment'));
        }

        return response()->json([
            'message' => 'Receipt view not found. Create resources/views/admin/student-finance/receipt.blade.php',
            'payment' => $payment,
        ]);
    }

    public function voteheadReport(Request $request): JsonResponse|Response
    {
        $schoolId = $this->currentSchoolId();

        [$from, $to] = $this->resolvePeriodDates(
            (string) $request->input('period', 'month'),
            $request->input('from'),
            $request->input('to')
        );

        $rows = collect();
        if (Schema::hasTable('student_fee_allocations')) {
            $amountCol = Schema::hasColumn('student_fee_allocations', 'allocated_amount') ? 'allocated_amount' : 'amount';

            $query = DB::table('student_fee_allocations as a')
                ->join('student_fee_ledgers as l', 'l.id', '=', 'a.student_fee_ledger_id')
                ->leftJoin('vote_heads as vh', 'vh.id', '=', 'l.vote_head_id')
                ->where('l.school_id', $schoolId)
                ->whereBetween('a.created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);

            $voteHeadId = $request->input('vote_head_id');
            if (!empty($voteHeadId)) {
                $query->where('l.vote_head_id', (int) $voteHeadId);
            }

            $rows = $query
                ->selectRaw('l.vote_head_id as vote_head_id')
                ->selectRaw('COALESCE(vh.name, "Unassigned") as vote_head_name')
                ->selectRaw('NULL as fee_profile_id')
                ->selectRaw('NULL as fee_profile_name')
                ->selectRaw('NULL as fee_profile_code')
                ->selectRaw("SUM(COALESCE(a.{$amountCol},0)) as total_collected")
                ->groupBy('l.vote_head_id', 'vh.name')
                ->orderBy('vote_head_name')
                ->get()
                ->map(fn ($r) => [
                    'vote_head_id' => $r->vote_head_id,
                    'vote_head_name' => $r->vote_head_name,
                    'fee_profile_id' => $r->fee_profile_id,
                    'fee_profile_name' => $r->fee_profile_name,
                    'fee_profile_code' => $r->fee_profile_code,
                    'total_collected' => round((float) $r->total_collected, 2),
                ])
                ->values();
        }

        if ($rows->isEmpty()) {
            $rows = VoteHead::query()
                ->orderBy('name')
                ->get()
                ->map(fn ($vh) => [
                    'vote_head_id' => $vh->id,
                    'vote_head_name' => $vh->name,
                    'fee_profile_id' => null,
                    'fee_profile_name' => null,
                    'fee_profile_code' => null,
                    'total_collected' => 0.0,
                ])->values();
        }

        $response = [
            'period' => (string) $request->input('period', 'month'),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'grand_total' => round((float) $rows->sum('total_collected'), 2),
            'rows' => $rows,
        ];

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($response);
        }

        return Inertia::render('Admin/Finance/StudentFinance/VoteheadReport', $response);
    }

    private function syncLedgersFromFeeStructure(StudentFeeAccount $account, int $feeStructureId): void
    {
        $studentId = $this->resolveLedgerStudentId($account);

        $lines = FeeStructureLine::query()
            ->where('fee_structure_id', $feeStructureId)
            ->get();

        foreach ($lines as $line) {
            $term = $this->resolveLineTerm($line);
            $parentAmount = $this->resolveLineParentAmount($line);

            $match = [
                'school_id' => $account->school_id,
                'student_id' => $studentId,
                'student_fee_account_id' => $account->id,
                'fee_structure_line_id' => $line->id,
                'vote_head_id' => $line->vote_head_id,
                'term' => $term,
            ];

            if (Schema::hasColumn('student_fee_ledgers', 'source')) {
                $match['source'] = 'parent';
            }

            $values = [];
            if (Schema::hasColumn('student_fee_ledgers', 'parent_amount')) {
                $values['parent_amount'] = $parentAmount;
            }
            if (Schema::hasColumn('student_fee_ledgers', 'parent_balance')) {
                $values['parent_balance'] = $parentAmount;
            }
            if (Schema::hasColumn('student_fee_ledgers', 'govt_capitation_amount')) {
                $values['govt_capitation_amount'] = 0;
            }

            StudentFeeLedger::query()->updateOrCreate($match, $values);
        }
    }

    private function recomputeLedgersFromPayments(StudentFeeAccount $account, array $priorityVoteHeadIds = []): void
    {
        $paymentsTotal = (float) StudentFeePayment::query()
            ->where('school_id', $account->school_id)
            ->where('student_fee_account_id', $account->id)
            ->sum('amount');

        $ledgers = StudentFeeLedger::query()
            ->where('school_id', $account->school_id)
            ->where('student_fee_account_id', $account->id)
            ->get();

        if ($ledgers->isEmpty()) return;

        $priorityMap = collect($priorityVoteHeadIds)->values()->flip();

        $termRank = fn ($t) => match (strtoupper((string) $t)) {
            'T1' => 1, 'T2' => 2, 'T3' => 3, 'ANNUAL' => 4, default => 99
        };

        $ordered = $ledgers->sort(function ($a, $b) use ($priorityMap, $termRank) {
            $ap = $priorityMap->has((int) $a->vote_head_id) ? (int) $priorityMap[(int) $a->vote_head_id] : 9999;
            $bp = $priorityMap->has((int) $b->vote_head_id) ? (int) $priorityMap[(int) $b->vote_head_id] : 9999;

            if ($ap !== $bp) return $ap <=> $bp;

            $at = $termRank($a->term);
            $bt = $termRank($b->term);
            if ($at !== $bt) return $at <=> $bt;

            return (int) $a->id <=> (int) $b->id;
        })->values();

        foreach ($ordered as $ledger) {
            $base = (float) ($ledger->parent_amount ?? 0);
            if (Schema::hasColumn('student_fee_ledgers', 'parent_balance')) {
                $ledger->parent_balance = $base;
                $ledger->save();
            }
        }

        $remaining = $paymentsTotal;
        foreach ($ordered as $ledger) {
            if ($remaining <= 0) break;

            $currentBalance = (float) ($ledger->parent_balance ?? 0);
            if ($currentBalance <= 0) continue;

            $applied = min($currentBalance, $remaining);
            $ledger->parent_balance = round($currentBalance - $applied, 2);
            $ledger->save();

            $remaining = round($remaining - $applied, 2);
        }
    }

    private function recomputeAccountTotalFeeDue(StudentFeeAccount $account): void
    {
        if (!Schema::hasColumn('student_fee_accounts', 'total_fee_due')) return;

        $expected = (float) StudentFeeLedger::query()
            ->where('school_id', $account->school_id)
            ->where('student_fee_account_id', $account->id)
            ->sum('parent_amount');

        $paid = (float) StudentFeePayment::query()
            ->where('school_id', $account->school_id)
            ->where('student_fee_account_id', $account->id)
            ->sum('amount');

        $account->total_fee_due = round($expected - $paid, 2);
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

    private function resolveLineParentAmount(object $line): float
    {
        $amount = $line->parent_amount
            ?? $line->amount
            ?? (($line->term1_amount ?? 0) + ($line->term2_amount ?? 0) + ($line->term3_amount ?? 0))
            ?? 0;

        return round(max((float) $amount, 0), 2);
    }

    private function resolveLineTerm(object $line): string
    {
        $term = strtoupper((string) ($line->term ?? 'ANNUAL'));
        return in_array($term, ['T1', 'T2', 'T3', 'ANNUAL'], true) ? $term : 'ANNUAL';
    }

    private function studentSelectColumns(): array
    {
        $columns = ['id', 'school_id', 'first_name', 'last_name'];

        if (Schema::hasColumn('students', 'admission_no')) $columns[] = 'admission_no';
        if (Schema::hasColumn('students', 'admission_number')) $columns[] = 'admission_number';
        if (Schema::hasColumn('students', 'adm_no')) $columns[] = 'adm_no';
        if (Schema::hasColumn('students', 'class_level')) $columns[] = 'class_level';
        if (Schema::hasColumn('students', 'stream')) $columns[] = 'stream';
        if (Schema::hasColumn('students', 'deleted_at')) $columns[] = 'deleted_at';

        return array_values(array_unique($columns));
    }

    private function resolvePeriodDates(string $period, $fromInput, $toInput): array
    {
        $now = now();

        return match ($period) {
            'day' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'custom' => [
                $fromInput ? Carbon::parse($fromInput)->startOfDay() : $now->copy()->startOfMonth(),
                $toInput ? Carbon::parse($toInput)->endOfDay() : $now->copy()->endOfMonth(),
            ],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }

    private function currentSchoolId(): int
    {
        $user = Auth::user();

        $schoolId = $user->school_id
            ?? $user->current_school_id
            ?? null;

        if (!$schoolId) {
            abort(HttpResponse::HTTP_UNPROCESSABLE_ENTITY, 'No school context found for this user.');
        }

        return (int) $schoolId;
    }
}