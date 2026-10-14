<?php

namespace App\Support;

use App\Models\School;
use App\Models\SchoolInvoice;
use Illuminate\Support\Facades\DB;

trait SyncsSchoolBillingAccess
{
    protected function syncSchoolBillingAccessFromInvoices(
        School $school,
        ?int $changedBy = null,
        string $reason = 'invoice_sync'
    ): void {
        $school->refresh();

        $oldStage = (string) ($school->billing_access_stage ?? 'locked');
        $overrideEnabled = (bool) ($school->billing_access_override ?? false);
        $overrideReason = $school->billing_access_override_reason;

        $totals = SchoolInvoice::query()
            ->where('school_id', $school->id)
            ->selectRaw('COALESCE(SUM(invoiced_amount), 0) as total_invoiced')
            ->selectRaw('COALESCE(SUM(balance_amount), 0) as total_balance')
            ->first();

        $amountDue = (float) ($totals->total_invoiced ?? 0);
        $totalBalance = (float) ($totals->total_balance ?? 0);

        $amountPaid = max(0.0, round($amountDue - $totalBalance, 2));
        if ($amountDue > 0 && $amountPaid > $amountDue) {
            $amountPaid = $amountDue;
        }

        $paidPercent = $amountDue > 0
            ? min(100.0, round(($amountPaid / $amountDue) * 100, 2))
            : 100.0;

        $newStage = $this->resolveBillingStageFromPercent($paidPercent);

        School::query()
            ->whereKey($school->id)
            ->update([
                'billing_cycle_amount_due' => $amountDue,
                'billing_cycle_amount_paid' => $amountPaid,
                'billing_paid_percent' => $paidPercent,
                'billing_access_stage' => $newStage,
                'billing_restricted_at' => $newStage === 'full' ? null : now(),
                'updated_at' => now(),
            ]);

        DB::table('school_access_events')->insert([
            'school_id' => $school->id,
            'changed_by' => $changedBy,
            'old_stage' => $oldStage,
            'new_stage' => $newStage,
            'amount_due' => $amountDue,
            'amount_paid' => $amountPaid,
            'paid_percent' => $paidPercent,
            'override_enabled' => $overrideEnabled,
            'override_reason' => $overrideReason,
            'reason' => $reason,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function resolveBillingStageFromPercent(float $percent): string
    {
        if ($percent >= 100) {
            return 'full';
        }

        if ($percent >= 50) {
            return 'half_only';
        }

        if ($percent >= 25) {
            return 'quarter_only';
        }

        return 'locked';
    }

    protected function resolveInvoiceStatus(float $balance, ?string $dueDate): string
    {
        if ($balance <= 0) {
            return 'paid';
        }

        if (! empty($dueDate) && now()->isAfter($dueDate)) {
            return 'overdue';
        }

        return 'partial';
    }
}