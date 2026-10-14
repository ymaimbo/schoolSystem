<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentFeeAllocation extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'student_fee_payment_id',
        'student_fee_ledger_id',
        'vote_head_id',
        'allocated_amount',
        'amount',
        'allocation_order',
        'recorded_by',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
        'amount' => 'decimal:2',
        'allocation_order' => 'integer',
    ];

    protected $appends = [
        'applied_amount',
        'vote_head_name',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(StudentFeePayment::class, 'student_fee_payment_id');
    }

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(StudentFeeLedger::class, 'student_fee_ledger_id');
    }

    public function voteHead(): BelongsTo
    {
        return $this->belongsTo(VoteHead::class);
    }

    public function getAppliedAmountAttribute(): float
    {
        $allocated = $this->attributes['allocated_amount'] ?? null;
        $legacy = $this->attributes['amount'] ?? null;
        return round((float) ($allocated ?? $legacy ?? 0), 2);
    }

    public function getVoteHeadNameAttribute(): ?string
    {
        if ($this->relationLoaded('voteHead') && $this->voteHead) {
            return $this->voteHead->name;
        }

        if ($this->relationLoaded('ledger') && $this->ledger?->relationLoaded('voteHead') && $this->ledger->voteHead) {
            return $this->ledger->voteHead->name;
        }

        return null;
    }
}