<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentFeePayment extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'student_id',
        'student_fee_account_id',
        'amount',
        'method',
        'payment_method',
        'reference',
        'organization_name',
        'organization_id',
        'receipt_no',
        'paid_at',
        'notes',
        'received_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(StudentFeeAccount::class, 'student_fee_account_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}