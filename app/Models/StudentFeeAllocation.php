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
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
        'amount' => 'decimal:2',
        'allocation_order' => 'integer',
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
}