<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FeeProfileVoteheadPriority;
use App\Models\FeeStructure;
use App\Models\Student;
use App\Models\StudentFeeAccount;
use App\Models\StudentFeeAllocation;
use App\Models\StudentFeeLedger;
use App\Models\StudentFeePayment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class FeeAllocationService
{
    private ?string $cachedLedgerTermDefault = null;

    // ─────────────────────────────────────────────────────────────────────
    //  Ledger Sync
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Synchronize ledger rows for an account to match its assigned fee structure.
     * Preserves ledgers that have payment history (Gap 2 fix).
     */
    public function syncLedgersFromStructure(StudentFeeAccount $account, int $feeStructureId): void
    {
        $structure = FeeStructure::query()
            ->withoutGlobalScope('school')
            ->with(['lines' => fn ($q) => $q->withoutGlobalScope('school')->orderBy('sort_order')->orderBy('id')])
            ->find($feeStructureId);

        if (!$structure) {
            return;
        }

        $lines       = $structure->lines ?? collect();
        $studentId   = $this->resolveAccountStudentId($account);
        $termDefault = $this->defaultLedgerTerm();
        $schoolId    = (int) $account->school_id;

        $existingLedgers = StudentFeeLedger::query()
            ->withoutGlobalScope('school')
            ->where('student_fee_account_id', $account->id)
            ->get();

        $validLineIds = [];

        foreach ($lines as $line) {
            $voteHeadId = (int) $line->vote_head_id;
            if ($voteHeadId <= 0) continue;

            $validLineIds[] = (int) $line->id;
            $expected = $line->expectedParentAmount();

            $ledger = $existingLedgers->first(function (StudentFeeLedger $l) use ($line, $termDefault) {
                return (int) $l->fee_structure_line_id === (int) $line->id
                    && $l->source === 'parent'
                    && $l->term === $termDefault;
            });

            if ($ledger) {
                $ledger->update([
                    'expected_amount'        => $expected,
                    'parent_amount'          => $expected,
                    'govt_capitation_amount'  => (float) ($line->govt_capitation_amount ?? 0),
                ]);
                $this->recomputeLedgerBalance($ledger);
            } else {
                StudentFeeLedger::query()->create([
                    'school_id'              => $schoolId,
                    'student_id'             => $studentId,
                    'student_fee_account_id' => $account->id,
                    'fee_structure_line_id'  => $line->id,
                    'vote_head_id'           => $voteHeadId,
                    'source'                 => 'parent',
                    'term'                   => $termDefault,
                    'expected_amount'        => $expected,
                    'parent_amount'          => $expected,
                    'parent_balance'         => $expected,
                    'outstanding_amount'     => $expected,
                    'paid_amount'            => 0,
                    'balance_amount'         => $expected,
                    'govt_capitation_amount'  => (float) ($line->govt_capitation_amount ?? 0),
                    'status'                 => 'pending',
                ]);
            }
        }

        $staleLedgers = $existingLedgers->filter(
            fn ($l) => !in_array((int) $l->fee_structure_line_id, $validLineIds, true)
        );

        foreach ($staleLedgers as $stale) {
            $allocatedTotal = $this->sumActiveAllocations($stale);
            if ($allocatedTotal <= 0) {
                $stale->delete();
            } else {
                $stale->update([
                    'expected_amount'    => $allocatedTotal,
                    'parent_amount'      => $allocatedTotal,
                    'parent_balance'     => 0,
                    'outstanding_amount' => 0,
                    'paid_amount'        => $allocatedTotal,
                    'balance_amount'     => 0,
                    'status'             => 'cleared',
                ]);
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Allocation Planning (Gap 5 fix: internal locking)
    // ─────────────────────────────────────────────────────────────────────

    public function allocateAcrossVoteHeadLedgers(
        StudentFeeAccount $account,
        float $paymentAmount,
        Student $student,
        array $priorityConstraints = []
    ): array {
        $amount = round(max($paymentAmount, 0), 2);
        if ($amount <= 0) {
            return ['allocations' => [], 'allocated_total' => 0.0, 'unallocated' => 0.0, 'priority_source' => 'none'];
        }

        StudentFeeAccount::query()->where('id', $account->id)->lockForUpdate()->first();

        $openLedgers = StudentFeeLedger::query()
            ->withoutGlobalScope('school')
            ->with('feeStructureLine:id,sort_order')
            ->where('student_fee_account_id', $account->id)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->filter(fn (StudentFeeLedger $ledger) => $this->ledgerOutstanding($ledger) > 0)
            ->values();

        if ($openLedgers->isEmpty()) {
            return ['allocations' => [], 'allocated_total' => 0.0, 'unallocated' => $amount, 'priority_source' => 'none'];
        }

        [$orderedLedgers, $prioritySource] = $this->orderLedgersByPriority($openLedgers, (int)$account->school_id, $student, $priorityConstraints);

        $remaining = $amount;
        $allocations = [];
        $order = 1;

        foreach ($orderedLedgers as $ledger) {
            if ($remaining <= 0) break;
            $outstanding = $this->ledgerOutstanding($ledger);
            if ($outstanding <= 0) continue;

            $allocated = round(min($remaining, $outstanding), 2);
            if ($allocated <= 0) continue;

            $allocations[] = [
                'student_fee_ledger_id' => (int) $ledger->id,
                'vote_head_id'          => (int) $ledger->vote_head_id,
                'allocated_amount'      => $allocated,
                'amount'                => $allocated,
                'allocation_order'      => $order,
            ];
            $remaining = round($remaining - $allocated, 2);
            $order++;
        }

        return [
            'allocations'     => $allocations,
            'allocated_total' => round($amount - $remaining, 2),
            'unallocated'     => round(max($remaining, 0), 2),
            'priority_source' => $prioritySource,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Allocation Application
    // ─────────────────────────────────────────────────────────────────────

    public function applyAllocationsToPayment(StudentFeePayment $payment, array $allocations, int $schoolId, ?int $recordedBy = null): void
    {
        foreach ($allocations as $row) {
            StudentFeeAllocation::query()->create([
                'school_id'              => $schoolId,
                'student_fee_payment_id' => $payment->id,
                'student_fee_ledger_id'  => (int)$row['student_fee_ledger_id'],
                'vote_head_id'           => (int)$row['vote_head_id'],
                'allocated_amount'       => (float)$row['allocated_amount'],
                'amount'                 => (float)$row['allocated_amount'],
                'allocation_order'       => max((int)$row['allocation_order'], 1),
                'status'                 => 'active',
            ]);
        }

        $ledgerIds = collect($allocations)->pluck('student_fee_ledger_id')->unique();
        foreach ($ledgerIds as $lid) {
            $ledger = StudentFeeLedger::query()->withoutGlobalScope('school')->find($lid);
            if ($ledger) $this->recomputeLedgerBalance($ledger);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Audited Reversal (Gap 6 fix)
    // ─────────────────────────────────────────────────────────────────────

    public function reversePaymentAllocations(StudentFeePayment $payment, int $schoolId): void
    {
        $allocations = StudentFeeAllocation::query()->withoutGlobalScope('school')->where('student_fee_payment_id', $payment->id)->get();
        if ($allocations->isEmpty()) return;

        $ledgerIds = $allocations->pluck('student_fee_ledger_id')->unique()->sort()->values()->toArray();
        StudentFeeLedger::query()->withoutGlobalScope('school')->whereIn('id', $ledgerIds)->orderBy('id')->lockForUpdate()->get();

        foreach ($allocations as $allocation) {
            if (($allocation->status ?? 'active') === 'reversed') continue;
            $allocation->update(['status' => 'reversed']);
        }

        foreach ($ledgerIds as $lid) {
            $ledger = StudentFeeLedger::query()->withoutGlobalScope('school')->find($lid);
            if ($ledger) $this->recomputeLedgerBalance($ledger);
        }
    }

    public function getPaymentBreakdown(StudentFeePayment $payment, bool $includeReversed = false): Collection
    {
        $query = StudentFeeAllocation::query()->withoutGlobalScope('school')
            ->with(['voteHead:id,code,name', 'ledger:id,vote_head_id', 'ledger.voteHead:id,code,name'])
            ->where('student_fee_payment_id', $payment->id);

        if (!$includeReversed) {
            $query->where(function ($q) { $q->whereNull('status')->orWhere('status', '!=', 'reversed'); });
        }

        return $query->orderBy('allocation_order')->orderBy('id')->get()->map(fn ($row) => (object) [
            'id' => $row->id, 'vote_head_id' => $row->vote_head_id,
            'vote_head_code' => $row->voteHead?->code ?? $row->ledger?->voteHead?->code,
            'vote_head_name' => $row->voteHead?->name ?? $row->ledger?->voteHead?->name ?? 'Unspecified Votehead',
            'allocated_amount' => (float)($row->allocated_amount ?? $row->amount ?? 0),
            'amount' => (float)($row->allocated_amount ?? $row->amount ?? 0),
            'allocation_order' => (int)$row->allocation_order, 'status' => $row->status ?? 'active',
        ]);
    }

    public function recomputeLedgerBalance(StudentFeeLedger $ledger): void
    {
        $allocated = $this->sumActiveAllocations($ledger);
        $expected  = (float) ($ledger->parent_amount ?: $ledger->expected_amount ?: 0);
        $remaining = round(max($expected - $allocated, 0), 2);

        $status = 'pending';
        if ($allocated > 0 && $allocated >= $expected) $status = 'cleared';
        elseif ($allocated > 0) $status = 'partial';

        $ledger->update(['paid_amount' => round($allocated, 2), 'parent_balance' => $remaining, 'outstanding_amount' => $remaining, 'balance_amount' => $remaining, 'status' => $status]);
    }

    private function orderLedgersByPriority(Collection $ledgers, int $schoolId, Student $student, array $priorityConstraints = []): array
    {
        $vhIdsInLedgers = $ledgers->pluck('vote_head_id')->map(fn($v)=>(int)$v)->unique();
        $manualIds = collect($priorityConstraints['vote_head_ids'] ?? [])->map(fn($id)=>(int)$id)->filter(fn($id)=>$id>0 && $vhIdsInLedgers->contains($id))->unique()->values();
        if ($manualIds->isNotEmpty()) return [$this->sortLedgersByVoteHeadSequence($ledgers, $manualIds), 'manual'];

        if (!empty($student->fee_profile_id)) {
            $profileIds = FeeProfileVoteheadPriority::query()->where('school_id', $schoolId)->where('fee_profile_id', (int)$student->fee_profile_id)->where('is_active', true)->orderBy('priority_order')->orderBy('id')->pluck('vote_head_id')->map(fn($id)=>(int)$id)->filter(fn($id)=>$vhIdsInLedgers->contains($id))->unique()->values();
            if ($profileIds->isNotEmpty()) return [$this->sortLedgersByVoteHeadSequence($ledgers, $profileIds), 'profile'];
        }

        $ordered = $ledgers->sort(fn($a, $b) => ($a->feeStructureLine?->sort_order ?? PHP_INT_MAX) === ($b->feeStructureLine?->sort_order ?? PHP_INT_MAX) ? (int)$a->id <=> (int)$b->id : ($a->feeStructureLine?->sort_order ?? PHP_INT_MAX) <=> ($b->feeStructureLine?->sort_order ?? PHP_INT_MAX))->values();
        return [$ordered, 'structure'];
    }

    private function sortLedgersByVoteHeadSequence(Collection $ledgers, Collection $voteHeadOrder): Collection
    {
        $pos = $voteHeadOrder->values()->flip();
        return $ledgers->sort(fn($a, $b) => $pos->get((int)$a->vote_head_id, 999) === $pos->get((int)$b->vote_head_id, 999) ? (int)$a->id <=> (int)$b->id : $pos->get((int)$a->vote_head_id, 999) <=> $pos->get((int)$b->vote_head_id, 999))->values();
    }

    private function sumActiveAllocations(StudentFeeLedger $ledger): float
    {
        $q = DB::table('student_fee_allocations')->where('student_fee_ledger_id', $ledger->id);
        if (Schema::hasColumn('student_fee_allocations', 'status')) $q->where(fn($q) => $q->whereNull('status')->orWhere('status', '!=', 'reversed'));
        return (float)$q->sum(Schema::hasColumn('student_fee_allocations', 'allocated_amount') ? 'allocated_amount' : 'amount');
    }

    private function ledgerOutstanding(StudentFeeLedger $ledger): float
    {
        return round(max((float)($ledger->parent_balance ?? (($ledger->parent_amount ?: $ledger->expected_amount ?: 0) - $this->sumActiveAllocations($ledger))), 0), 2);
    }

    private function defaultLedgerTerm(): string
    {
        if ($this->cachedLedgerTermDefault) return $this->cachedLedgerTermDefault;
        $fallback = 'ANNUAL';
        try {
            $row = DB::selectOne("SHOW COLUMNS FROM `student_fee_ledgers` WHERE Field = 'term'");
            if ($row && !empty($row->Default)) return $this->cachedLedgerTermDefault = (string)$row->Default;
        } catch (\Throwable $e) {}
        return $this->cachedLedgerTermDefault = $fallback;
    }

    private function resolveAccountStudentId(StudentFeeAccount $account): int
    {
        if (!$account->student_id) throw ValidationException::withMessages(['student_fee_account_id' => 'Account has no student_id.']);
        return (int)$account->student_id;
    }
}