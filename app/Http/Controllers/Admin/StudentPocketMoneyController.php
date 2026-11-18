<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Student;
use App\Models\StudentPocketMoneyAccount;
use App\Models\StudentPocketMoneyTransaction;
use App\Services\PocketMoneyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class StudentPocketMoneyController extends Controller
{
    public function __construct(
        private readonly PocketMoneyService $pocketMoneyService
    ) {
    }

    public function index(Request $request): Response
    {
        $schoolId = $this->currentSchoolId();

        $filters = [
            'search' => (string) $request->input('search', ''),
            'class_level' => (string) $request->input('class_level', ''),
            'type' => (string) $request->input('type', ''),
            'status' => (string) $request->input('status', ''),
            'from' => (string) $request->input('from', ''),
            'to' => (string) $request->input('to', ''),
        ];

        $students = Student::query()
            ->where('school_id', $schoolId)
            ->orderBy('first_name')
            ->get(['id', 'school_id', 'admission_no', 'first_name', 'last_name', 'class_level', 'stream']);

        $accounts = StudentPocketMoneyAccount::query()
            ->where('school_id', $schoolId)
            ->with(['student:id,admission_no,first_name,last_name,class_level,stream'])
            ->withCount('transactions')
            ->orderByDesc('id')
            ->get()
            ->map(function (StudentPocketMoneyAccount $account) {
                return [
                    'id' => $account->id,
                    'student_id' => $account->student_id,
                    'student_name' => $account->student?->full_name ?? trim(($account->student?->first_name ?? '') . ' ' . ($account->student?->last_name ?? '')),
                    'admission_no' => $account->student?->admission_no,
                    'class_level' => $account->student?->class_level,
                    'stream' => $account->student?->stream,
                    'opening_balance' => round((float) ($account->opening_balance ?? 0), 2),
                    'current_balance' => round((float) ($account->current_balance ?? 0), 2),
                    'total_deposited' => round((float) $account->total_deposited, 2),
                    'total_withdrawn' => round((float) $account->total_withdrawn, 2),
                    'transactions_count' => $account->transactions_count,
                ];
            })
            ->values();

        $transactionQuery = StudentPocketMoneyTransaction::query()
            ->where('school_id', $schoolId)
            ->with([
                'student:id,admission_no,first_name,last_name,class_level,stream',
                'account:id,student_id,current_balance',
                'recorder:id,name',
                'reverser:id,name',
            ])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');

        if ($filters['type'] !== '') {
            $transactionQuery->where('type', $filters['type']);
        }
        if ($filters['status'] !== '' && Schema::hasColumn('student_pocket_money_transactions', 'status')) {
            $transactionQuery->where('status', $filters['status']);
        }
        if ($filters['from'] !== '') {
            $transactionQuery->whereDate('transaction_date', '>=', $filters['from']);
        }
        if ($filters['to'] !== '') {
            $transactionQuery->whereDate('transaction_date', '<=', $filters['to']);
        }

        $transactions = $transactionQuery
            ->limit(300)
            ->get()
            ->map(function (StudentPocketMoneyTransaction $transaction) {
                return [
                    'id' => $transaction->id,
                    'student_id' => $transaction->student_id,
                    'student_name' => $transaction->student?->full_name ?? trim(($transaction->student?->first_name ?? '') . ' ' . ($transaction->student?->last_name ?? '')),
                    'admission_no' => $transaction->student?->admission_no,
                    'class_level' => $transaction->student?->class_level,
                    'stream' => $transaction->student?->stream,
                    'account_id' => $transaction->student_pocket_money_account_id,
                    'transaction_no' => $transaction->transaction_no,
                    'type' => $transaction->type,
                    'amount' => round((float) $transaction->amount, 2),
                    'payment_method' => $transaction->payment_method,
                    'reference_no' => $transaction->reference_no,
                    'depositor_name' => $transaction->depositor_name,
                    'depositor_phone' => $transaction->depositor_phone,
                    'transaction_date' => optional($transaction->transaction_date)->toDateString(),
                    'status' => $transaction->status ?? 'active',
                    'notes' => $transaction->notes,
                    'recorded_by_name' => $transaction->recorder?->name,
                    'reversed_by_name' => $transaction->reverser?->name,
                    'reversed_at' => optional($transaction->reversed_at)?->toDateTimeString(),
                    'reversal_reason' => $transaction->reversal_reason,
                ];
            })
            ->values();

        $today = now()->toDateString();
        $todayMovement = $this->pocketMoneyService->activeNetMovementForDay($schoolId, $today);

        $summary = [
            'students_count' => $students->count(),
            'accounts_count' => $accounts->count(),
            'total_held' => round((float) $accounts->sum('current_balance'), 2),
            'deposits_today' => $todayMovement['deposits'],
            'withdrawals_today' => $todayMovement['withdrawals'],
            'net_today' => $todayMovement['net'],
        ];

        return Inertia::render('Admin/Finance/PocketMoney/Index', [
            'summary' => $summary,
            'students' => $students,
            'accounts' => $accounts,
            'transactions' => $transactions,
            'filters' => $filters,
            'paymentMethods' => ['cash', 'bank', 'mpesa', 'cheque', 'transfer'],
            'transactionTypes' => ['deposit', 'withdrawal'],
        ]);
    }

    public function storeAccount(Request $request): RedirectResponse
    {
        $schoolId = $this->currentSchoolId();

        $validated = $request->validate([
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'opening_balance' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        $student = Student::query()
            ->where('school_id', $schoolId)
            ->findOrFail($validated['student_id']);

        DB::transaction(function () use ($student, $schoolId, $validated) {
            $account = $this->pocketMoneyService->ensureAccount(
                student: $student,
                schoolId: $schoolId,
                openingBalance: (float) ($validated['opening_balance'] ?? 0),
                notes: $validated['notes'] ?? null,
            );

            if (Schema::hasColumn('student_pocket_money_accounts', 'opening_balance')) {
                $account->opening_balance = (float) ($validated['opening_balance'] ?? 0);
            }
            if (Schema::hasColumn('student_pocket_money_accounts', 'notes')) {
                $account->notes = $validated['notes'] ?? null;
            }
            $account->save();

            $this->pocketMoneyService->recomputeAccountBalance($account);
        });

        return redirect()
            ->route('admin.pocket-money.index')
            ->with('success', 'Pocket money account saved successfully.');
    }

    public function storeTransaction(Request $request): RedirectResponse
    {
        $schoolId = $this->currentSchoolId();

        $validated = $request->validate([
            'student_pocket_money_account_id' => ['nullable', 'integer', Rule::exists('student_pocket_money_accounts', 'id')],
            'student_id' => ['nullable', 'integer', Rule::exists('students', 'id')],
            'type' => ['required', Rule::in(['deposit', 'withdrawal'])],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['nullable', 'string', 'max:32'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'depositor_name' => ['nullable', 'string', 'max:255'],
            'depositor_phone' => ['nullable', 'string', 'max:32'],
            'transaction_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($schoolId, $validated) {
            $account = $this->resolveAccountForTransaction($schoolId, $validated);

            $account = StudentPocketMoneyAccount::query()
                ->where('id', $account->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($validated['type'] === 'withdrawal') {
                $this->pocketMoneyService->validateWithdrawal($account, (float) $validated['amount']);
            }

            $payload = [
                'school_id' => $schoolId,
                'student_id' => $account->student_id,
                'student_pocket_money_account_id' => $account->id,
                'type' => $validated['type'],
                'amount' => (float) $validated['amount'],
                'transaction_date' => $validated['transaction_date'] ?? now()->toDateString(),
                'status' => 'active',
            ];

            foreach (['payment_method', 'reference_no', 'depositor_name', 'depositor_phone', 'notes'] as $field) {
                if (Schema::hasColumn('student_pocket_money_transactions', $field)) {
                    $payload[$field] = $validated[$field] ?? null;
                }
            }

            if (Schema::hasColumn('student_pocket_money_transactions', 'recorded_by')) {
                $payload['recorded_by'] = Auth::id();
            }

            /** @var StudentPocketMoneyTransaction $transaction */
            $transaction = StudentPocketMoneyTransaction::query()->create($payload);

            if (Schema::hasColumn('student_pocket_money_transactions', 'transaction_no')) {
                $transaction->update([
                    'transaction_no' => $this->pocketMoneyService->generateTransactionNumber($transaction),
                ]);
            }

            $this->pocketMoneyService->recomputeAccountBalance($account);
        });

        return redirect()
            ->route('admin.pocket-money.index')
            ->with('success', 'Pocket money transaction recorded successfully.');
    }

    public function reverseTransaction(Request $request, StudentPocketMoneyTransaction $transaction): RedirectResponse
    {
        $schoolId = $this->currentSchoolId();
        abort_unless((int) $transaction->school_id === $schoolId, HttpResponse::HTTP_FORBIDDEN);

        DB::transaction(function () use ($transaction, $request) {
            $account = StudentPocketMoneyAccount::query()
                ->where('id', $transaction->student_pocket_money_account_id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->pocketMoneyService->reverseTransaction(
                transaction: $transaction,
                reversedBy: Auth::id(),
                reason: $request->input('reason', 'User requested reversal'),
            );

            if (Schema::hasTable('activity_logs')) {
                try {
                    ActivityLog::create([
                        'user_id' => Auth::id(),
                        'module' => 'Pocket Money',
                        'action' => 'transaction_reversal',
                        'record_type' => 'StudentPocketMoneyTransaction',
                        'record_id' => $transaction->id,
                        'old_values' => json_encode([
                            'status' => 'active',
                            'type' => $transaction->type,
                            'amount' => (float) $transaction->amount,
                        ]),
                        'new_values' => json_encode([
                            'status' => 'reversed',
                            'reason' => $request->input('reason', 'User requested reversal'),
                        ]),
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Pocket money reversal activity log failed', [
                        'transaction_id' => $transaction->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->pocketMoneyService->recomputeAccountBalance($account);
        });

        return redirect()
            ->route('admin.pocket-money.index')
            ->with('success', 'Pocket money transaction reversed successfully.');
    }

    public function printStatement(Request $request, StudentPocketMoneyAccount $account)
    {
        $schoolId = $this->currentSchoolId();
        abort_unless((int) $account->school_id === $schoolId, HttpResponse::HTTP_FORBIDDEN);

        $account->loadMissing([
            'student',
            'transactions' => fn ($q) => $q->orderBy('transaction_date')->orderBy('id'),
        ]);

        $school = app()->bound('currentSchool') ? app('currentSchool') : null;
        $schoolInfo = [
            'name' => $school->name ?? config('app.name', 'School'),
            'code' => $school->code ?? 'N/A',
            'location' => $school->location ?? 'N/A',
            'logo' => null,
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

        return response()->view('admin.finance.pocket_money_statement', [
            'school' => $schoolInfo,
            'student' => $account->student,
            'account' => $account,
            'transactions' => $account->transactions,
            'openingBalance' => round((float) ($account->opening_balance ?? 0), 2),
            'currentBalance' => round((float) ($account->current_balance ?? 0), 2),
        ]);
    }

    private function resolveAccountForTransaction(int $schoolId, array $validated): StudentPocketMoneyAccount
    {
        $accountId = $validated['student_pocket_money_account_id'] ?? null;
        if (!empty($accountId)) {
            $account = StudentPocketMoneyAccount::query()
                ->where('school_id', $schoolId)
                ->find($accountId);
            if ($account) {
                return $account;
            }
        }

        $studentId = $validated['student_id'] ?? null;
        if (!empty($studentId)) {
            $account = StudentPocketMoneyAccount::query()
                ->where('school_id', $schoolId)
                ->where('student_id', $studentId)
                ->first();
            if ($account) {
                return $account;
            }

            $student = Student::query()
                ->where('school_id', $schoolId)
                ->findOrFail($studentId);

            return $this->pocketMoneyService->ensureAccount($student, $schoolId);
        }

        throw \Illuminate\Validation\ValidationException::withMessages([
            'student_id' => 'No student pocket money account found. Select a valid student or account.',
        ]);
    }

    private function currentSchoolId(): int
    {
        $school = app()->bound('currentSchool') ? app('currentSchool') : null;
        if ($school && $school->id) {
            return (int) $school->id;
        }

        $user = Auth::user();
        $schoolId = $user->school_id ?? $user->current_school_id ?? null;

        if (!$schoolId) {
            abort(HttpResponse::HTTP_UNPROCESSABLE_ENTITY, 'No school context found for this user.');
        }

        return (int) $schoolId;
    }
}