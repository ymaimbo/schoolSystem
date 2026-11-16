<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeStructure extends Model
{
    use HasFactory, BelongsToSchool;

    /**
     * Live schema columns (fee_structures):
     *   id, school_id, name, year (smallint NOT NULL), category (enum NOT NULL),
     *   class_level (nullable), notes (nullable), approved_by (nullable FK),
     *   approved_at (nullable timestamp), created_at, updated_at
     *
     * Intentionally omitted (do not exist on live table):
     *   description, is_active, fee_profile_id
     */
    protected $fillable = [
        'school_id',
        'name',
        'year',
        'category',
        'class_level',
        'notes',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'year'        => 'integer',
        'approved_at' => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function lines(): HasMany
    {
        return $this->hasMany(FeeStructureLine::class, 'fee_structure_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(StudentFeeAccount::class, 'fee_structure_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // -------------------------------------------------------------------------
    // Helpers used by FeeStructureController safe-update logic
    // -------------------------------------------------------------------------

    /**
     * Returns the IDs of structure lines that have at least one ledger row,
     * keyed by vote_head_id so the controller can check by votehead.
     *
     * @return \Illuminate\Support\Collection<int,int>  collection of vote_head_id values
     */
    public function ledgeredLineVoteHeadIds(): \Illuminate\Support\Collection
    {
        return \App\Models\StudentFeeLedger::query()
            ->withoutGlobalScope('school')
            ->whereIn('fee_structure_line_id', $this->lines()->pluck('id'))
            ->pluck('vote_head_id')
            ->unique()
            ->values();
    }

    /**
     * Returns line IDs that have ledger history (cannot be hard-deleted).
     */
    public function ledgeredLineIds(): \Illuminate\Support\Collection
    {
        return \App\Models\StudentFeeLedger::query()
            ->withoutGlobalScope('school')
            ->whereIn('fee_structure_line_id', $this->lines()->pluck('id'))
            ->pluck('fee_structure_line_id')
            ->unique()
            ->values();
    }
}