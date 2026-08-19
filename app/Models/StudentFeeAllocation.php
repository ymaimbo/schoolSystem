<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentFeeAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_fee_payment_id',
        'student_fee_ledger_id',
        'allocated_amount',
        'allocation_order',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
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
}