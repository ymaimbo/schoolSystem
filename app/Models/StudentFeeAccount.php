<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentFeeAccount extends Model
{
    use HasFactory;

    protected $fillable = [
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
        return $this->hasMany(StudentFeePayment::class);
    }

    public function ledgers(): HasMany
    {
        return $this->hasMany(StudentFeeLedger::class, 'student_fee_account_id');
    }
}