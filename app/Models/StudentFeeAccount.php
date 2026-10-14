<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentFeeAccount extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'student_id',
        'fee_structure_id',
        'total_fee_due',
        'sponsor_org_name',
        'sponsor_org_id',
        'notes',
    ];

    protected $casts = [
        'total_fee_due' => 'decimal:2',
    ];

    protected $appends = [
        'balance',
        'credit_balance',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(StudentFeePayment::class, 'student_fee_account_id');
    }

    public function ledgers(): HasMany
    {
        return $this->hasMany(StudentFeeLedger::class, 'student_fee_account_id');
    }

    public function getBalanceAttribute(): float
    {
        $due = (float) ($this->total_fee_due ?? 0);
        return $due > 0 ? $due : 0.0;
    }

    public function getCreditBalanceAttribute(): float
    {
        $due = (float) ($this->total_fee_due ?? 0);
        return $due < 0 ? abs($due) : 0.0;
    }
}