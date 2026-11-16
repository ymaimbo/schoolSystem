<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeStructureLine extends Model
{
    use HasFactory, BelongsToSchool;

    /**
     * Live schema columns (fee_structure_lines):
     *   id, school_id (nullable), fee_structure_id, vote_head_id,
     *   amount (nullable — legacy, keep for read compatibility),
     *   govt_capitation_amount (decimal NOT NULL default 0),
     *   parent_total_amount   (decimal NOT NULL default 0),
     *   term1_amount          (decimal NOT NULL default 0),
     *   term2_amount          (decimal NOT NULL default 0),
     *   term3_amount          (decimal NOT NULL default 0),
     *   total_amount          (decimal NOT NULL default 0),
     *   sort_order            (int unsigned NOT NULL default 0),
     *   created_at, updated_at
     *
     * Intentionally omitted (do not exist on live table):
     *   term, parent_amount
     */
    protected $fillable = [
        'school_id',
        'fee_structure_id',
        'vote_head_id',
        'amount',                  // legacy nullable — kept for read compat
        'govt_capitation_amount',
        'parent_total_amount',
        'term1_amount',
        'term2_amount',
        'term3_amount',
        'total_amount',
        'sort_order',
    ];

    protected $casts = [
        'amount'                 => 'decimal:2',
        'govt_capitation_amount' => 'decimal:2',
        'parent_total_amount'    => 'decimal:2',
        'term1_amount'           => 'decimal:2',
        'term2_amount'           => 'decimal:2',
        'term3_amount'           => 'decimal:2',
        'total_amount'           => 'decimal:2',
        'sort_order'             => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class, 'fee_structure_id');
    }

    public function voteHead(): BelongsTo
    {
        return $this->belongsTo(VoteHead::class, 'vote_head_id');
    }

    /**
     * Ledgers created from this line.
     * Used to guard against destructive edits (Gap 2 fix).
     */
    public function ledgers(): HasMany
    {
        return $this->hasMany(StudentFeeLedger::class, 'fee_structure_line_id');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * True if any ledger row references this line.
     * A line with history must never be hard-deleted.
     */
    public function hasLedgerHistory(): bool
    {
        return $this->ledgers()->exists();
    }

    /**
     * The canonical parent-payable expected amount for this line,
     * using the same precedence as FeeAllocationService::structureLineExpectedAmount().
     */
    public function expectedParentAmount(): float
    {
        $parentTotal = (float) ($this->parent_total_amount ?? 0);
        if ($parentTotal > 0) {
            return round($parentTotal, 2);
        }

        $total = (float) ($this->total_amount ?? 0);
        if ($total > 0) {
            return round($total, 2);
        }

        $termSum = (float) ($this->term1_amount ?? 0)
            + (float) ($this->term2_amount ?? 0)
            + (float) ($this->term3_amount ?? 0);

        return round(max($termSum, 0), 2);
    }
}