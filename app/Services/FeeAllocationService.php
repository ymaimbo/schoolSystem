<?php

namespace App\Services;

use App\Models\StudentFeeAccount;
use App\Models\StudentFeePayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FeeAllocationService
{
    public function syncAccountLedgers(StudentFeeAccount $account): void
    {
        if (!$account->fee_structure_id) {
            return;
        }

        $hasPayments = DB::table('student_fee_payments')
            ->where('student_fee_account_id', $account->id)
            ->exists();

        if ($hasPayments) {
            throw ValidationException::withMessages([
                'fee_structure_id' => 'Cannot rebuild vote-head ledger after payments exist.',
            ]);
        }

        DB::transaction(function () use ($account) {
            DB::table('student_fee_ledgers')
                ->where('student_fee_account_id', $account->id)
                ->delete();

            $lines = DB::table('fee_structure_lines')
                ->where('fee_structure_id', $account->fee_structure_id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            $parentExpectedTotal = 0.0;
            $rows = [];

            foreach ($lines as $line) {
                $termMap = [
                    'T1' => (float) $line->term1_amount,
                    'T2' => (float) $line->term2_amount,
                    'T3' => (float) $line->term3_amount,
                ];

                $parentTermSum = 0.0;
                foreach ($termMap as $term => $amt) {
                    if ($amt <= 0) {
                        continue;
                    }

                    $parentTermSum += $amt;
                    $parentExpectedTotal += $amt;

                    $rows[] = $this->ledgerRow(
                        studentId: $account->student_id,
                        accountId: $account->id,
                        lineId: $line->id,
                        voteHeadId: $line->vote_head_id,
                        source: 'parent',
                        term: $term,
                        expected: $amt
                    );
                }

                $parentTotal = (float) $line->parent_total_amount;
                $remainder = max(0, $parentTotal - $parentTermSum);

                if ($remainder > 0) {
                    $parentExpectedTotal += $remainder;

                    $rows[] = $this->ledgerRow(
                        studentId: $account->student_id,
                        accountId: $account->id,
                        lineId: $line->id,
                        voteHeadId: $line->vote_head_id,
                        source: 'parent',
                        term: 'ANNUAL',
                        expected: $remainder
                    );
                }

                $capitation = (float) $line->govt_capitation_amount;
                if ($capitation > 0) {
                    $rows[] = $this->ledgerRow(
                        studentId: $account->student_id,
                        accountId: $account->id,
                        lineId: $line->id,
                        voteHeadId: $line->vote_head_id,
                        source: 'capitation',
                        term: 'ANNUAL',
                        expected: $capitation
                    );
                }
            }

            if (!empty($rows)) {
                DB::table('student_fee_ledgers')->insert($rows);
            }

            DB::table('student_fee_accounts')
                ->where('id', $account->id)
                ->update([
                    'total_fee_due' => $parentExpectedTotal,
                    'updated_at' => now(),
                ]);
        });
    }

    public function allocateParentPayment(StudentFeePayment $payment): void
    {
        $this->allocatePayment($payment, 'parent');
    }

    /**
     * NEW: Allocate parent payment using accountant-defined vote-head priorities
     * Example: [{vote_head_id: 11, amount: 1500}, {vote_head_id: 2, amount: 500}]
     */
    public function allocateParentPaymentWithPriorities(StudentFeePayment $payment, array $priorityAllocations): void
    {
        DB::transaction(function () use ($payment, $priorityAllocations) {
            $normalized = collect($priorityAllocations)
                ->map(function ($row) {
                    return [
                        'vote_head_id' => (int) ($row['vote_head_id'] ?? 0),
                        'amount' => (float) ($row['amount'] ?? 0),
                    ];
                })
                ->filter(fn ($row) => $row['vote_head_id'] > 0 && $row['amount'] > 0)
                ->groupBy('vote_head_id')
                ->map(fn ($rows, $voteHeadId) => [
                    'vote_head_id' => (int) $voteHeadId,
                    'amount' => (float) $rows->sum('amount'),
                ])
                ->values();

            if ($normalized->isEmpty()) {
                throw ValidationException::withMessages([
                    'priority_allocations' => 'Priority allocations are required.',
                ]);
            }

            $sum = (float) $normalized->sum('amount');
            if (round($sum, 2) !== round((float) $payment->amount, 2)) {
                throw ValidationException::withMessages([
                    'priority_allocations' => 'Priority allocation total must exactly match payment amount.',
                ]);
            }

            $order = 1;

            foreach ($normalized as $item) {
                $this->allocateToVoteHead(
                    payment: $payment,
                    voteHeadId: (int) $item['vote_head_id'],
                    targetAmount: (float) $item['amount'],
                    orderCounter: $order
                );
            }
        });
    }

    public function allocatePayment(StudentFeePayment $payment, string $source): void
    {
        if (!in_array($source, ['parent', 'capitation'], true)) {
            throw ValidationException::withMessages([
                'source' => 'Invalid allocation source.',
            ]);
        }

        DB::transaction(function () use ($payment, $source) {
            $remaining = (float) $payment->amount;

            $ledgers = DB::table('student_fee_ledgers')
                ->where('student_fee_account_id', $payment->student_fee_account_id)
                ->where('source', $source)
                ->where('balance_amount', '>', 0)
                ->orderByRaw("CASE term WHEN 'T1' THEN 1 WHEN 'T2' THEN 2 WHEN 'T3' THEN 3 ELSE 4 END")
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($ledgers->isEmpty()) {
                throw ValidationException::withMessages([
                    'amount' => "No {$source} vote-head balances available for allocation.",
                ]);
            }

            $order = 1;

            foreach ($ledgers as $ledger) {
                if ($remaining <= 0) break;

                $balance = (float) $ledger->balance_amount;
                if ($balance <= 0) continue;

                $alloc = min($remaining, $balance);
                if ($alloc <= 0) continue;

                $this->applyAllocationToLedger($payment->id, $ledger, $alloc, $order);

                $remaining -= $alloc;
                $order++;
            }

            if ($remaining > 0.00001) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment amount exceeds outstanding balances.',
                ]);
            }
        });
    }

    public function buildAccountVoteHeadBreakdown(int $accountId): array
    {
        $rows = DB::table('student_fee_ledgers as l')
            ->join('vote_heads as v', 'v.id', '=', 'l.vote_head_id')
            ->where('l.student_fee_account_id', $accountId)
            ->select([
                'l.vote_head_id',
                'v.code as vote_code',
                'v.name as vote_name',
                'l.source',
                'l.term',
                'l.expected_amount',
                'l.paid_amount',
                'l.balance_amount',
            ])
            ->orderBy('v.code')
            ->get();

        $grouped = [];
        $totals = [
            'parent_expected' => 0.0,
            'parent_paid' => 0.0,
            'parent_balance' => 0.0,
            'capitation_expected' => 0.0,
            'capitation_paid' => 0.0,
            'capitation_balance' => 0.0,
            't1_expected' => 0.0, 't1_paid' => 0.0, 't1_balance' => 0.0,
            't2_expected' => 0.0, 't2_paid' => 0.0, 't2_balance' => 0.0,
            't3_expected' => 0.0, 't3_paid' => 0.0, 't3_balance' => 0.0,
            'annual_expected' => 0.0, 'annual_paid' => 0.0, 'annual_balance' => 0.0,
        ];

        foreach ($rows as $r) {
            $key = (string) $r->vote_head_id;
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'vote_head_id' => $r->vote_head_id,
                    'vote_code' => $r->vote_code,
                    'vote_name' => $r->vote_name,
                    'parent_expected' => 0.0, 'parent_paid' => 0.0, 'parent_balance' => 0.0,
                    'capitation_expected' => 0.0, 'capitation_paid' => 0.0, 'capitation_balance' => 0.0,
                    't1_expected' => 0.0, 't1_paid' => 0.0, 't1_balance' => 0.0,
                    't2_expected' => 0.0, 't2_paid' => 0.0, 't2_balance' => 0.0,
                    't3_expected' => 0.0, 't3_paid' => 0.0, 't3_balance' => 0.0,
                    'annual_expected' => 0.0, 'annual_paid' => 0.0, 'annual_balance' => 0.0,
                ];
            }

            $expected = (float) $r->expected_amount;
            $paid = (float) $r->paid_amount;
            $balance = (float) $r->balance_amount;

            if ($r->source === 'parent') {
                $grouped[$key]['parent_expected'] += $expected;
                $grouped[$key]['parent_paid'] += $paid;
                $grouped[$key]['parent_balance'] += $balance;

                $totals['parent_expected'] += $expected;
                $totals['parent_paid'] += $paid;
                $totals['parent_balance'] += $balance;
            } else {
                $grouped[$key]['capitation_expected'] += $expected;
                $grouped[$key]['capitation_paid'] += $paid;
                $grouped[$key]['capitation_balance'] += $balance;

                $totals['capitation_expected'] += $expected;
                $totals['capitation_paid'] += $paid;
                $totals['capitation_balance'] += $balance;
            }

            $termKey = match ($r->term) {
                'T1' => 't1',
                'T2' => 't2',
                'T3' => 't3',
                default => 'annual',
            };

            $grouped[$key]["{$termKey}_expected"] += $expected;
            $grouped[$key]["{$termKey}_paid"] += $paid;
            $grouped[$key]["{$termKey}_balance"] += $balance;

            $totals["{$termKey}_expected"] += $expected;
            $totals["{$termKey}_paid"] += $paid;
            $totals["{$termKey}_balance"] += $balance;
        }

        return [
            'lines' => array_values($grouped),
            'totals' => $totals,
        ];
    }

    private function allocateToVoteHead(StudentFeePayment $payment, int $voteHeadId, float $targetAmount, int &$orderCounter): void
    {
        $remaining = $targetAmount;

        $ledgers = DB::table('student_fee_ledgers')
            ->where('student_fee_account_id', $payment->student_fee_account_id)
            ->where('source', 'parent')
            ->where('vote_head_id', $voteHeadId)
            ->where('balance_amount', '>', 0)
            ->orderByRaw("CASE term WHEN 'T1' THEN 1 WHEN 'T2' THEN 2 WHEN 'T3' THEN 3 ELSE 4 END")
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($ledgers->isEmpty()) {
            throw ValidationException::withMessages([
                'priority_allocations' => "Selected vote head (ID {$voteHeadId}) has no outstanding parent balance.",
            ]);
        }

        foreach ($ledgers as $ledger) {
            if ($remaining <= 0) {
                break;
            }

            $balance = (float) $ledger->balance_amount;
            if ($balance <= 0) {
                continue;
            }

            $alloc = min($remaining, $balance);
            if ($alloc <= 0) {
                continue;
            }

            $this->applyAllocationToLedger($payment->id, $ledger, $alloc, $orderCounter);
            $orderCounter++;
            $remaining -= $alloc;
        }

        if ($remaining > 0.00001) {
            throw ValidationException::withMessages([
                'priority_allocations' => "Allocated amount for vote head ID {$voteHeadId} exceeds its outstanding balance.",
            ]);
        }
    }

    private function applyAllocationToLedger(int $paymentId, object $ledger, float $alloc, int $order): void
    {
        $newPaid = (float) $ledger->paid_amount + $alloc;
        $newBalance = (float) $ledger->expected_amount - $newPaid;
        $newStatus = $newBalance <= 0 ? 'cleared' : 'partial';

        DB::table('student_fee_ledgers')
            ->where('id', $ledger->id)
            ->update([
                'paid_amount' => $newPaid,
                'balance_amount' => $newBalance,
                'status' => $newStatus,
                'updated_at' => now(),
            ]);

        DB::table('student_fee_allocations')->insert([
            'student_fee_payment_id' => $paymentId,
            'student_fee_ledger_id' => $ledger->id,
            'allocated_amount' => $alloc,
            'allocation_order' => $order,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function ledgerRow(
        int $studentId,
        int $accountId,
        int $lineId,
        int $voteHeadId,
        string $source,
        string $term,
        float $expected
    ): array {
        return [
            'student_id' => $studentId,
            'student_fee_account_id' => $accountId,
            'fee_structure_line_id' => $lineId,
            'vote_head_id' => $voteHeadId,
            'source' => $source,
            'term' => $term,
            'expected_amount' => $expected,
            'paid_amount' => 0,
            'balance_amount' => $expected,
            'status' => $expected <= 0 ? 'cleared' : 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}