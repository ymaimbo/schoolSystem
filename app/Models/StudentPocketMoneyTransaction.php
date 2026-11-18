<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentPocketMoneyTransaction extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'student_id',
        'student_pocket_money_account_id',
        'transaction_no',
        'type',
        'amount',
        'payment_method',
        'reference_no',
        'depositor_name',
        'depositor_phone',
        'recorded_by',
        'approved_by',
        'transaction_date',
        'status',
        'reversed_at',
        'reversed_by',
        'reversal_reason',
        'notes',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'transaction_date' => 'date',
        'reversed_at'      => 'datetime',
    ];

    protected $appends = [
        'signed_amount',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(StudentPocketMoneyAccount::class, 'student_pocket_money_account_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function reverser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function getSignedAmountAttribute(): float
    {
        $amount = (float) ($this->amount ?? 0);

        return $this->type === 'withdrawal'
            ? -1 * round($amount, 2)
            : round($amount, 2);
    }

    public function isActive(): bool
    {
        return ($this->status ?? 'active') === 'active';
    }

    public function isReversed(): bool
    {
        return ($this->status ?? 'active') === 'reversed';
    }
}