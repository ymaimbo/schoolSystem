<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── 1. Backfill fee_structure_lines.school_id from parent structure ───
        if (Schema::hasTable('fee_structure_lines') && Schema::hasColumn('fee_structure_lines', 'school_id')) {
            DB::statement("
                UPDATE fee_structure_lines l
                JOIN fee_structures s ON s.id = l.fee_structure_id
                SET l.school_id = s.school_id
                WHERE l.school_id IS NULL AND s.school_id IS NOT NULL
            ");
        }

        // ─── 2. Backfill student_fee_ledgers.school_id from parent account ───
        if (Schema::hasTable('student_fee_ledgers') && Schema::hasColumn('student_fee_ledgers', 'school_id')) {
            DB::statement("
                UPDATE student_fee_ledgers l
                JOIN student_fee_accounts a ON a.id = l.student_fee_account_id
                SET l.school_id = a.school_id
                WHERE l.school_id IS NULL AND a.school_id IS NOT NULL
            ");
        }

        // ─── 3. Backfill student_fee_payments.school_id from parent account ───
        if (Schema::hasTable('student_fee_payments') && Schema::hasColumn('student_fee_payments', 'school_id')) {
            DB::statement("
                UPDATE student_fee_payments p
                JOIN student_fee_accounts a ON a.id = p.student_fee_account_id
                SET p.school_id = a.school_id
                WHERE p.school_id IS NULL AND a.school_id IS NOT NULL
            ");
        }

        // ─── 4. Backfill student_fee_allocations.school_id from parent payment ───
        if (Schema::hasTable('student_fee_allocations') && Schema::hasColumn('student_fee_allocations', 'school_id')) {
            DB::statement("
                UPDATE student_fee_allocations al
                JOIN student_fee_payments p ON p.id = al.student_fee_payment_id
                SET al.school_id = p.school_id
                WHERE al.school_id IS NULL AND p.school_id IS NOT NULL
            ");
        }

        // ─── 5. Backfill ledger parent_amount from structure lines ───
        if (Schema::hasTable('student_fee_ledgers') && Schema::hasColumn('student_fee_ledgers', 'parent_amount')) {
            DB::statement("
                UPDATE student_fee_ledgers l
                JOIN fee_structure_lines sl ON sl.id = l.fee_structure_line_id
                SET l.parent_amount = COALESCE(
                    NULLIF(sl.parent_total_amount, 0),
                    NULLIF(sl.total_amount, 0),
                    NULLIF(sl.amount, 0),
                    0
                )
                WHERE l.parent_amount = 0
            ");

            // Sync expected_amount to match parent_amount where it was never set
            DB::statement("
                UPDATE student_fee_ledgers
                SET expected_amount = parent_amount
                WHERE expected_amount = 0 AND parent_amount > 0
            ");
        }

        // ─── 6. Recompute ledger balances from active allocations ───
        if (Schema::hasTable('student_fee_ledgers') && Schema::hasTable('student_fee_allocations')) {
            $amountCol = Schema::hasColumn('student_fee_allocations', 'allocated_amount')
                ? 'allocated_amount'
                : 'amount';

            $statusFilter = Schema::hasColumn('student_fee_allocations', 'status')
                ? "AND (al.status IS NULL OR al.status != 'reversed')"
                : '';

            DB::statement("
                UPDATE student_fee_ledgers l
                LEFT JOIN (
                    SELECT student_fee_ledger_id,
                           COALESCE(SUM(COALESCE(al.{$amountCol}, 0)), 0) as total_allocated
                    FROM student_fee_allocations al
                    WHERE 1=1 {$statusFilter}
                    GROUP BY student_fee_ledger_id
                ) agg ON agg.student_fee_ledger_id = l.id
                SET
                    l.paid_amount        = COALESCE(agg.total_allocated, 0),
                    l.parent_balance     = GREATEST(l.parent_amount - COALESCE(agg.total_allocated, 0), 0),
                    l.outstanding_amount = GREATEST(l.parent_amount - COALESCE(agg.total_allocated, 0), 0),
                    l.balance_amount     = GREATEST(l.parent_amount - COALESCE(agg.total_allocated, 0), 0),
                    l.status = CASE
                        WHEN COALESCE(agg.total_allocated, 0) >= l.parent_amount AND l.parent_amount > 0 THEN 'cleared'
                        WHEN COALESCE(agg.total_allocated, 0) > 0 THEN 'partial'
                        ELSE 'pending'
                    END
            ");
        }

        // ─── 7. Generate receipt_no for existing payments missing one ───
        if (Schema::hasTable('student_fee_payments') && Schema::hasColumn('student_fee_payments', 'receipt_no')) {
            $payments = DB::table('student_fee_payments')
                ->where(function ($q) {
                    $q->whereNull('receipt_no')->orWhere('receipt_no', '');
                })
                ->orderBy('id')
                ->get(['id', 'paid_at', 'created_at']);

            foreach ($payments as $payment) {
                $year = date('Y', strtotime($payment->paid_at ?? $payment->created_at ?? 'now'));
                $receiptNo = 'RCPT-' . $year . '-' . str_pad((string) $payment->id, 5, '0', STR_PAD_LEFT);

                DB::table('student_fee_payments')
                    ->where('id', $payment->id)
                    ->update(['receipt_no' => $receiptNo]);
            }
        }

        // ─── 8. Set existing payment rows to active status ───
        if (Schema::hasColumn('student_fee_payments', 'status')) {
            DB::table('student_fee_payments')
                ->whereNull('status')
                ->update(['status' => 'active']);
        }

        // ─── 9. Set existing allocation rows to active status ───
        if (Schema::hasColumn('student_fee_allocations', 'status')) {
            DB::table('student_fee_allocations')
                ->whereNull('status')
                ->update(['status' => 'active']);
        }

        // ─── 10. Recompute account total_fee_due including opening_balance ───
        if (Schema::hasTable('student_fee_accounts')) {
            $hasOpening = Schema::hasColumn('student_fee_accounts', 'opening_balance');

            $statusFilter = Schema::hasColumn('student_fee_payments', 'status')
                ? "AND (p.status IS NULL OR p.status != 'reversed')"
                : '';

            $openingCol = $hasOpening ? 'a.opening_balance' : '0';

            DB::statement("
                UPDATE student_fee_accounts a
                LEFT JOIN (
                    SELECT student_fee_account_id,
                           COALESCE(SUM(parent_amount), 0) as total_expected
                    FROM student_fee_ledgers
                    GROUP BY student_fee_account_id
                ) le ON le.student_fee_account_id = a.id
                LEFT JOIN (
                    SELECT student_fee_account_id,
                           COALESCE(SUM(amount), 0) as total_paid
                    FROM student_fee_payments p
                    WHERE 1=1 {$statusFilter}
                    GROUP BY student_fee_account_id
                ) pa ON pa.student_fee_account_id = a.id
                SET a.total_fee_due = ROUND(
                    {$openingCol} + COALESCE(le.total_expected, 0) - COALESCE(pa.total_paid, 0),
                    2
                )
            ");
        }
    }

    public function down(): void
    {
        // Forward-only: data repairs cannot be reversed.
    }
};