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

class FeeAllocationService
{
    private ?string $cachedLedgerTermDefault = null;

    public function syncLedgersFromStructure(StudentFeeAccount $account, int $feeStructureId): void
    {
        $structure = FeeStructure::query()
            ->with(['lines' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')])
            ->find($feeStructureId);

        if (!$structure) {
            return;
        }

        $lines = $structure->lines ?? collect();
        if ($lines->isEmpty()) {
            return;
        }

        $ledgerCols = Schema::hasTable('student_fee_ledgers')
            ? Schema::getColumnListing('student_fee_ledgers')
            : [];

        $existingLedgers = StudentFeeLedger::query()
            ->where('school_id', $account->school_id)
            ->where('student_fee_account_id', $account->id)
            ->get()
            ->keyBy(fn (StudentFeeLedger $l) => (int) $l->vote_head_id);

        foreach ($lines as $line) {
            $voteHeadId = (int) $line->vote_head_id;
            if ($voteHeadId <= 0) {
                continue;
            }

            $expected = $this->structureLineExpectedAmount($line);
            $ledger = $existingLedgers->get($voteHeadId);

            $payload = $this->onlyExistingColumns($ledgerCols, [
                'fee_structure_line_id' => $line->id,
                'term1_amount' => $line->term1_amount ?? 0,
                'term2_amount' => $line->term2_amount ?? 0,
                'term3_amount' => $line->term3_amount ?? 0,
                'parent_amount' => $expected,
                'parent_balance' => $expected,
                'govt_capitation_amount' => $line->govt_capitation_amount ?? 0,
            ]);

            if ($ledger) {
                $allocatedSoFar = $this->sumLedgerAllocations((int) $ledger->id);
                if (array_key_exists('parent_balance', $payload)) {
                    $payload['parent_balance'] = max(round($expected - $allocatedSoFar, 2), 0);
                }

                if (!empty($payload)) {
                    $ledger->update($payload);
                }

                continue;
            }

            $createPayload = $this->onlyExistingColumns($ledgerCols, array_merge([
                'school_id' => $account->school_id,
                'student_fee_account_id' => $account->id,
                'fee_structure_line_id' => $line->id,
                'vote_head_id' => $voteHeadId,
                'term' => $this->defaultLedgerTerm(),
                'notes' => null,
            ], $payload));

            StudentFeeLedger::query()->create($createPayload);
        }
    }

    /**
     * Priority precedence:
     * 1) manual override payload
     * 2) fee profile priorities
     * 3) structure line sort_order fallback
     */
    public function allocateAcrossVoteHeadLedgers(
        StudentFeeAccount $account,
        float $paymentAmount,
        Student $student,
        array $priorityConstraints = []
    ): array {
        $amount = round(max($paymentAmount, 0), 2);
        if ($amount <= 0) {
            return [
                'allocations' => [],
                'allocated_total' => 0.0,
                'unallocated' => 0.0,
                'priority_source' => 'none',
            ];
        }

        $openLedgers = StudentFeeLedger::query()
            ->with('feeStructureLine:id,sort_order')
            ->where('school_id', $account->school_id)
            ->where('student_fee_account_id', $account->id)
            ->get()
            ->map(function (StudentFeeLedger $ledger) {
                $outstanding = $this->ledgerOutstanding($ledger);
                $ledger->setAttribute('computed_outstanding', $outstanding);
                return $ledger;
            })
            ->filter(fn (StudentFeeLedger $ledger) => (float) $ledger->getAttribute('computed_outstanding') > 0)
            ->values();

        if ($openLedgers->isEmpty()) {
            return [
                'allocations' => [],
                'allocated_total' => 0.0,
                'unallocated' => $amount,
                'priority_source' => 'none',
            ];
        }

        [$orderedLedgers, $prioritySource] = $this->orderLedgersByPriority(
            ledgers: $openLedgers,
            schoolId: (int) $account->school_id,
            student: $student,
            priorityConstraints: $priorityConstraints,
        );

        $remaining = $amount;
        $allocations = [];
        $order = 1;

        /** @var StudentFeeLedger $ledger */
        foreach ($orderedLedgers as $ledger) {
            if ($remaining <= 0) {
                break;
            }

            $outstanding = round((float) $ledger->getAttribute('computed_outstanding'), 2);
            if ($outstanding <= 0) {
                continue;
            }

            $allocated = round(min($remaining, $outstanding), 2);
            if ($allocated <= 0) {
                continue;
            }

            $allocations[] = [
                'student_fee_ledger_id' => (int) $ledger->id,
                'vote_head_id' => (int) $ledger->vote_head_id,
                'allocated_amount' => $allocated,
                'amount' => $allocated,
                'allocation_order' => $order,
            ];

            $remaining = round($remaining - $allocated, 2);
            $order++;
        }

        $allocatedTotal = round($amount - $remaining, 2);

        return [
            'allocations' => $allocations,
            'allocated_total' => $allocatedTotal,
            'unallocated' => round(max($remaining, 0), 2),
            'priority_source' => $prioritySource,
        ];
    }

    public function applyAllocationsToPayment(
        StudentFeePayment $payment,
        array $allocations,
        int $schoolId,
        ?int $recordedBy = null
    ): void {
        $allocationCols = Schema::hasTable('student_fee_allocations')
            ? Schema::getColumnListing('student_fee_allocations')
            : [];

        $ledgerCols = Schema::hasTable('student_fee_ledgers')
            ? Schema::getColumnListing('student_fee_ledgers')
            : [];

        foreach ($allocations as $row) {
            $ledgerId = (int) ($row['student_fee_ledger_id'] ?? 0);
            $voteHeadId = (int) ($row['vote_head_id'] ?? 0);
            $amount = round((float) ($row['allocated_amount'] ?? $row['amount'] ?? 0), 2);
            $allocationOrder = (int) ($row['allocation_order'] ?? 0);

            if ($ledgerId <= 0 || $voteHeadId <= 0 || $amount <= 0) {
                continue;
            }

            $allocPayload = $this->onlyExistingColumns($allocationCols, [
                'school_id' => $schoolId,
                'student_fee_payment_id' => $payment->id,
                'student_fee_ledger_id' => $ledgerId,
                'vote_head_id' => $voteHeadId,
                'allocated_amount' => $amount,
                'amount' => $amount,
                'allocation_order' => $allocationOrder > 0 ? $allocationOrder : 1,
                'recorded_by' => $recordedBy,
            ]);

            StudentFeeAllocation::query()->create($allocPayload);

            $ledger = StudentFeeLedger::query()
                ->where('school_id', $schoolId)
                ->where('id', $ledgerId)
                ->lockForUpdate()
                ->first();

            if (!$ledger) {
                continue;
            }

            if (in_array('parent_balance', $ledgerCols, true)) {
                $current = $this->ledgerOutstanding($ledger);
                $next = round(max($current - $amount, 0), 2);
                $ledger->update(['parent_balance' => $next]);
            }
        }
    }

    public function reversePaymentAllocations(StudentFeePayment $payment, int $schoolId): void
    {
        $allocations = StudentFeeAllocation::query()
            ->where('school_id', $schoolId)
            ->where('student_fee_payment_id', $payment->id)
            ->orderByDesc('allocation_order')
            ->orderByDesc('id')
            ->get();

        $ledgerCols = Schema::hasTable('student_fee_ledgers')
            ? Schema::getColumnListing('student_fee_ledgers')
            : [];

        foreach ($allocations as $allocation) {
            $amount = $this->allocationAmount($allocation);
            if ($amount <= 0) {
                continue;
            }

            $ledger = StudentFeeLedger::query()
                ->where('school_id', $schoolId)
                ->where('id', $allocation->student_fee_ledger_id)
                ->lockForUpdate()
                ->first();

            if ($ledger && in_array('parent_balance', $ledgerCols, true)) {
                $current = $this->ledgerOutstanding($ledger);
                $ledger->update([
                    'parent_balance' => round($current + $amount, 2),
                ]);
            }
        }

        StudentFeeAllocation::query()
            ->where('school_id', $schoolId)
            ->where('student_fee_payment_id', $payment->id)
            ->delete();
    }

    public function getPaymentBreakdown(StudentFeePayment $payment): Collection
    {
        return StudentFeeAllocation::query()
            ->with([
                'voteHead:id,code,name',
                'ledger:id,vote_head_id',
                'ledger.voteHead:id,code,name',
            ])
            ->where('student_fee_payment_id', $payment->id)
            ->orderBy('allocation_order')
            ->orderBy('id')
            ->get()
            ->map(function (StudentFeeAllocation $row) {
                $voteHeadName = $row->voteHead?->name
                    ?? $row->ledger?->voteHead?->name
                    ?? 'Unspecified Votehead';

                $voteHeadCode = $row->voteHead?->code
                    ?? $row->ledger?->voteHead?->code
                    ?? null;

                $amount = $this->allocationAmount($row);

                return (object) [
                    'id' => $row->id,
                    'vote_head_id' => $row->vote_head_id,
                    'vote_head_code' => $voteHeadCode,
                    'vote_head_name' => $voteHeadName,
                    'allocated_amount' => $amount,
                    'amount' => $amount,
                    'allocation_order' => (int) ($row->allocation_order ?? 0),
                ];
            });
    }

    /**
     * @return array{0: \Illuminate\Support\Collection<int,StudentFeeLedger>, 1: string}
     */
    private function orderLedgersByPriority(
        Collection $ledgers,
        int $schoolId,
        Student $student,
        array $priorityConstraints = []
    ): array {
        $voteHeadIdsInLedgers = $ledgers
            ->pluck('vote_head_id')
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values();

        // 1) Manual override
        $manualIds = collect($priorityConstraints['vote_head_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values();

        $manualIds = $manualIds
            ->filter(fn ($id) => $voteHeadIdsInLedgers->contains($id))
            ->unique()
            ->values();

        if ($manualIds->isNotEmpty()) {
            $ordered = $this->sortLedgersByVoteHeadSequence($ledgers, $manualIds);
            return [$ordered, 'manual'];
        }

        // 2) Profile priority
        if (!empty($student->fee_profile_id)) {
            $profileIds = FeeProfileVoteheadPriority::query()
                ->where('school_id', $schoolId)
                ->where('fee_profile_id', (int) $student->fee_profile_id)
                ->where('is_active', true)
                ->orderBy('priority_order')
                ->orderBy('id')
                ->pluck('vote_head_id')
                ->map(fn ($id) => (int) $id)
                ->values();

            $profileIds = $profileIds
                ->filter(fn ($id) => $voteHeadIdsInLedgers->contains($id))
                ->unique()
                ->values();

            if ($profileIds->isNotEmpty()) {
                $ordered = $this->sortLedgersByVoteHeadSequence($ledgers, $profileIds);
                return [$ordered, 'profile'];
            }
        }

        // 3) Structure fallback
        $ordered = $ledgers->sort(function (StudentFeeLedger $a, StudentFeeLedger $b) {
            $sa = (int) ($a->feeStructureLine?->sort_order ?? PHP_INT_MAX);
            $sb = (int) ($b->feeStructureLine?->sort_order ?? PHP_INT_MAX);

            if ($sa === $sb) {
                return ((int) $a->id) <=> ((int) $b->id);
            }

            return $sa <=> $sb;
        })->values();

        return [$ordered, 'structure'];
    }

    private function sortLedgersByVoteHeadSequence(Collection $ledgers, Collection $voteHeadOrder): Collection
    {
        $position = $voteHeadOrder->values()->flip();

        return $ledgers
            ->sort(function (StudentFeeLedger $a, StudentFeeLedger $b) use ($position) {
                $pa = $position->get((int) $a->vote_head_id, PHP_INT_MAX);
                $pb = $position->get((int) $b->vote_head_id, PHP_INT_MAX);

                if ($pa === $pb) {
                    return ((int) $a->id) <=> ((int) $b->id);
                }

                return $pa <=> $pb;
            })
            ->values();
    }

    private function structureLineExpectedAmount($line): float
    {
        if (isset($line->payable_parent_amount)) {
            return round(max((float) $line->payable_parent_amount, 0), 2);
        }

        $parentTotal = (float) ($line->parent_total_amount ?? 0);
        if ($parentTotal > 0) {
            return round($parentTotal, 2);
        }

        $total = (float) ($line->total_amount ?? 0);
        if ($total > 0) {
            return round($total, 2);
        }

        $termSum = (float) ($line->term1_amount ?? 0)
            + (float) ($line->term2_amount ?? 0)
            + (float) ($line->term3_amount ?? 0);

        return round(max($termSum, 0), 2);
    }

    private function ledgerOutstanding(StudentFeeLedger $ledger): float
    {
        if ($ledger->parent_balance !== null) {
            return round(max((float) $ledger->parent_balance, 0), 2);
        }

        $expected = (float) ($ledger->parent_amount ?? 0);
        $allocated = $this->sumLedgerAllocations((int) $ledger->id);

        return round(max($expected - $allocated, 0), 2);
    }

    private function sumLedgerAllocations(int $ledgerId): float
    {
        if ($ledgerId <= 0) {
            return 0.0;
        }

        $rows = StudentFeeAllocation::query()
            ->where('student_fee_ledger_id', $ledgerId)
            ->get(['allocated_amount', 'amount']);

        $sum = 0.0;
        foreach ($rows as $row) {
            $sum += $this->allocationAmount($row);
        }

        return round(max($sum, 0), 2);
    }

    private function allocationAmount(StudentFeeAllocation $allocation): float
    {
        $value = $allocation->allocated_amount ?? $allocation->amount ?? 0;
        return round(max((float) $value, 0), 2);
    }

    private function onlyExistingColumns(array $tableColumns, array $payload): array
    {
        return array_intersect_key($payload, array_flip($tableColumns));
    }

    private function defaultLedgerTerm(): string
    {
        if ($this->cachedLedgerTermDefault !== null) {
            return $this->cachedLedgerTermDefault;
        }

        $fallback = 'ANNUAL';

        if (!Schema::hasTable('student_fee_ledgers') || !Schema::hasColumn('student_fee_ledgers', 'term')) {
            $this->cachedLedgerTermDefault = $fallback;
            return $this->cachedLedgerTermDefault;
        }

        $row = DB::selectOne("SHOW COLUMNS FROM `student_fee_ledgers` WHERE Field = 'term'");
        $type = (string) ($row->Type ?? '');

        if (preg_match('/^enum\\((.*)\\)$/i', $type, $matches)) {
            $values = str_getcsv($matches[1], ',', "'");
            $first = $values[0] ?? null;
            if (!empty($first)) {
                $this->cachedLedgerTermDefault = (string) $first;
                return $this->cachedLedgerTermDefault;
            }
        }

        $this->cachedLedgerTermDefault = $fallback;
        return $this->cachedLedgerTermDefault;
    }
}