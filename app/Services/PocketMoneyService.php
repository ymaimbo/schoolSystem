<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Student;
use App\Models\StudentPocketMoneyAccount;
use App\Models\StudentPocketMoneyTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class PocketMoneyService
{
    /**
     * Ensure a student has a pocket money account.
     * Creates one if it doesn't exist.
     */
    public function ensureAccount(Student $student, int $schoolId, float $openingBalance = 0, ?string $notes = null): StudentPocketMoneyAccount
    {
        $account = StudentPocketMoneyAccount::query()->firstOrNew([
            'school_id' => $schoolId,
            'student_id' => $student->id,
        ]);

        if (!$account->exists) {
            $account->opening_balance = round($openingBalance, 2);
            $account->current_balance = round($openingBalance, 2);
            $account->notes = $notes;
            $account->save();
        }

        return $account;
    }

    /**
     * Deterministically recompute the account balance from active transactions.
     * This is the single source of truth for the student's wallet balance.
     */
    public function recomputeAccountBalance(StudentPocketMoneyAccount $account): void
    {
        $opening = (float) ($account->opening_balance ?? 0);

        $query = StudentPocketMoneyTransaction::query()
            ->withoutGlobalScope('school')
            ->where('student_pocket_money_account_id', $account->id);

        // Ignore reversed transactions
        if (Schema::hasColumn('student_pocket_money_transactions', 'status')) {
            $query->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'reversed');
            });
        }

        $transactions = $query->get(['type', 'amount']);

        $deposits = (float) $transactions->where('type', 'deposit')->sum('amount');
        $withdrawals = (float) $transactions->where('type', 'withdrawal')->sum('amount');

        // New balance = Opening + All Active Deposits - All Active Withdrawals
        $account->current_balance = round($opening + $deposits - $withdrawals, 2);
        $account->save();
    }

    /**
     * Prevent students from withdrawing more than they have.
     */
    public function validateWithdrawal(StudentPocketMoneyAccount $account, float $amount): void
    {
        $balance = round((float) ($account->current_balance ?? 0), 2);
        if ($amount > $balance) {
            throw ValidationException::withMessages([
                'amount' => 'Insufficient funds. Withdrawal exceeds available pocket money balance (' . number_format($balance, 2) . ').',
            ]);
        }
    }

    /**
     * Generate a professional sequence number for bursar records.
     */
    public function generateTransactionNumber(StudentPocketMoneyTransaction $transaction): string
    {
        $year = now()->year;
        return 'PMT-' . $year . '-' . str_pad((string) $transaction->id, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Reverse a transaction (Deposit or Withdrawal).
     * Marks the row reversed and recomputes the account balance.
     */
    public function reverseTransaction(StudentPocketMoneyTransaction $transaction, ?int $reversedBy = null, ?string $reason = null): void
    {
        if (($transaction->status ?? 'active') === 'reversed') {
            return;
        }

        $payload = ['status' => 'reversed'];

        if (Schema::hasColumn('student_pocket_money_transactions', 'reversed_at')) {
            $payload['reversed_at'] = now();
        }
        if (Schema::hasColumn('student_pocket_money_transactions', 'reversed_by')) {
            $payload['reversed_by'] = $reversedBy;
        }
        if (Schema::hasColumn('student_pocket_money_transactions', 'reversal_reason')) {
            $payload['reversal_reason'] = $reason ?: 'User requested reversal';
        }

        $transaction->update($payload);

        // Recompute the parent account balance
        $account = StudentPocketMoneyAccount::query()
            ->withoutGlobalScope('school')
            ->lockForUpdate()
            ->find($transaction->student_pocket_money_account_id);

        if ($account) {
            $this->recomputeAccountBalance($account);
        }
    }

    /**
     * Get net movement for a specific day (used for Bursar dashboard).
     */
    public function activeNetMovementForDay(int $schoolId, string $date): array
    {
        $query = DB::table('student_pocket_money_transactions')
            ->where('school_id', $schoolId)
            ->whereDate('transaction_date', $date);

        if (Schema::hasColumn('student_pocket_money_transactions', 'status')) {
            $query->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'reversed');
            });
        }

        $rows = $query->get(['type', 'amount']);

        $deposits = (float) $rows->where('type', 'deposit')->sum('amount');
        $withdrawals = (float) $rows->where('type', 'withdrawal')->sum('amount');

        return [
            'deposits' => round($deposits, 2),
            'withdrawals' => round($withdrawals, 2),
            'net' => round($deposits - $withdrawals, 2),
        ];
    }
}