<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StudentFeeLedger extends Model
{
    use HasFactory, BelongsToSchool;

    /**
     * Live schema columns (student_fee_ledgers):
     *   id, school_id, student_id, student_fee_account_id,
     *   fee_structure_line_id (NOT NULL FK → lines),
     *   vote_head_id (NOT NULL FK → vote_heads),
     *   source  enum('parent','capitation') NOT NULL  — no default
     *   term    enum('T1','T2','T3','ANNUAL') NOT NULL DEFAULT 'ANNUAL'
     *   expected_amount   decimal NOT NULL default 0   ← real DB column
     *   outstanding_amount decimal nullable            ← real DB column
     *   priority_order int NOT NULL default 999
     *   paid_amount    decimal NOT NULL default 0
     *   balance_amount decimal NOT NULL default 0
     *   status  enum('pending','partial','cleared') NOT NULL DEFAULT 'pending'
     *   parent_amount          decimal NOT NULL default 0  (added Oct-12)
     *   parent_balance         decimal NOT NULL default 0  (added Oct-12)
     *   govt_capitation_amount decimal NOT NULL default 0  (added Oct-12)
     *   notes text nullable                               (added Oct-12)
     *   created_at, updated_at
     */
    protected $fillable = [
        'school_id',
        'student_id',
        'student_fee_account_id',
        'fee_structure_line_id',
        'vote_head_id',
        'source',
        'term',
        'expected_amount',
        'outstanding_amount',
        'priority_order',
        'paid_amount',
        'balance_amount',
        'status',
        'parent_amount',
        'parent_balance',
        'govt_capitation_amount',
        'notes',
    ];

    protected $casts = [
        'expected_amount'        => 'decimal:2',
        'outstanding_amount'     => 'decimal:2',
        'paid_amount'            => 'decimal:2',
        'balance_amount'         => 'decimal:2',
        'parent_amount'          => 'decimal:2',
        'parent_balance'         => 'decimal:2',
        'govt_capitation_amount' => 'decimal:2',
        'priority_order'         => 'integer',
    ];

    /**
     * Computed helpers exposed as appends — named differently from DB columns
     * to avoid shadowing the real DB values (the original double-subtraction bug).
     */
    protected $appends = [
        'computed_expected',
        'computed_outstanding',
        'allocated_total',
    ];

    // ── Relationships ────────────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(StudentFeeAccount::class, 'student_fee_account_id');
    }

    public function feeStructureLine(): BelongsTo
    {
        return $this->belongsTo(FeeStructureLine::class, 'fee_structure_line_id');
    }

    public function voteHead(): BelongsTo
    {
        return $this->belongsTo(VoteHead::class);
    }

    /** All allocations (including reversed). */
    public function allocations(): HasMany
    {
        return $this->hasMany(StudentFeeAllocation::class, 'student_fee_ledger_id');
    }

    /**
     * Active allocations only (excludes reversed).
     * Guarded: if the status column doesn't exist yet (pre-migration),
     * falls back to returning all rows.
     */
    public function activeAllocations(): HasMany
    {
        $query = $this->hasMany(StudentFeeAllocation::class, 'student_fee_ledger_id');

        if ($this->allocationTableHasStatus()) {
            $query->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'reversed');
            });
        }

        return $query;
    }

    // ── Computed Accessors (no shadowing) ─────────────────────────────────

    /**
     * What the parent is expected to pay for this ledger line.
     * Source of truth: parent_amount (backfilled by data-repair migration).
     */
    public function getComputedExpectedAttribute(): float
    {
        $parentAmount = (float) ($this->attributes['parent_amount'] ?? 0);
        if ($parentAmount > 0) {
            return round($parentAmount, 2);
        }

        $dbExpected = (float) ($this->attributes['expected_amount'] ?? 0);
        return round(max($dbExpected, 0), 2);
    }

    /**
     * How much of this ledger line is still unpaid.
     * Source of truth: parent_balance (maintained by recomputeLedgerBalance).
     */
    public function getComputedOutstandingAttribute(): float
    {
        $parentBalance = isset($this->attributes['parent_balance'])
            ? (float) $this->attributes['parent_balance']
            : null;

        if ($parentBalance !== null) {
            return round(max($parentBalance, 0), 2);
        }

        // Fallback for pre-backfill rows
        return round(max($this->computed_expected - $this->allocated_total, 0), 2);
    }

    /**
     * Sum of active (non-reversed) allocation amounts against this ledger.
     * Uses loaded relation when available (avoids N+1), raw DB query otherwise.
     * FIX: safe regardless of whether status column exists.
     */
    public function getAllocatedTotalAttribute(): float
    {
        // If relations are loaded, filter in PHP (safe even without status column)
        if ($this->relationLoaded('allocations')) {
            return round($this->allocations
                ->filter(fn ($a) => !isset($a->status) || $a->status !== 'reversed')
                ->sum(fn ($a) => (float) ($a->allocated_amount ?? $a->amount ?? 0)), 2);
        }

        // Raw DB query — safe regardless of status column existence
        $query = DB::table('student_fee_allocations')
            ->where('student_fee_ledger_id', $this->id);

        if ($this->allocationTableHasStatus()) {
            $query->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'reversed');
            });
        }

        $col = Schema::hasColumn('student_fee_allocations', 'allocated_amount')
            ? 'allocated_amount'
            : 'amount';

        return round((float) $query->sum($col), 2);
    }

    // ── Service Helpers ──────────────────────────────────────────────────

    /**
     * Outstanding balance for the allocation service.
     */
    public function outstandingForAllocation(): float
    {
        return $this->computed_outstanding;
    }

    // ── Internal ─────────────────────────────────────────────────────────

    private static ?bool $cachedAllocationHasStatus = null;

    private function allocationTableHasStatus(): bool
    {
        if (self::$cachedAllocationHasStatus === null) {
            self::$cachedAllocationHasStatus = Schema::hasColumn('student_fee_allocations', 'status');
        }
        return self::$cachedAllocationHasStatus;
    }
}