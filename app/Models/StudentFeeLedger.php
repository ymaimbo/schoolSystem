<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentFeeLedger extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'student_id',
        'student_fee_account_id',
        'fee_structure_line_id',
        'vote_head_id',
        'source',
        'term',
        'term1_amount',
        'term2_amount',
        'term3_amount',
        'parent_amount',
        'parent_balance',
        'govt_capitation_amount',
        'notes',
    ];

    protected $casts = [
        'term1_amount' => 'decimal:2',
        'term2_amount' => 'decimal:2',
        'term3_amount' => 'decimal:2',
        'parent_amount' => 'decimal:2',
        'parent_balance' => 'decimal:2',
        'govt_capitation_amount' => 'decimal:2',
    ];

    protected $appends = [
        'expected_amount',
        'allocated_amount',
        'outstanding_amount',
    ];

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

    public function allocations(): HasMany
    {
        return $this->hasMany(StudentFeeAllocation::class, 'student_fee_ledger_id');
    }

    public function getExpectedAmountAttribute(): float
    {
        if ($this->parent_balance !== null) {
            return round(max((float) $this->parent_balance, 0), 2);
        }

        return round(max((float) ($this->parent_amount ?? 0), 0), 2);
    }

    public function getAllocatedAmountAttribute(): float
    {
        if ($this->relationLoaded('allocations')) {
            $sum = $this->allocations->sum(function ($allocation) {
                return (float) ($allocation->allocated_amount ?? $allocation->amount ?? 0);
            });

            return round(max($sum, 0), 2);
        }

        $sum = $this->allocations()
            ->get()
            ->sum(function ($allocation) {
                return (float) ($allocation->allocated_amount ?? $allocation->amount ?? 0);
            });

        return round(max($sum, 0), 2);
    }

    public function getOutstandingAmountAttribute(): float
    {
        $outstanding = $this->expected_amount - $this->allocated_amount;
        return round(max($outstanding, 0), 2);
    }
}