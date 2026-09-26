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
        'expected_amount',
        'paid_amount',
        'balance_amount',
        'status',
    ];

    protected $casts = [
        'expected_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(StudentFeeAccount::class, 'student_fee_account_id');
    }

    public function structureLine(): BelongsTo
    {
        return $this->belongsTo(FeeStructureLine::class, 'fee_structure_line_id');
    }

    public function voteHead(): BelongsTo
    {
        return $this->belongsTo(VoteHead::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(StudentFeeAllocation::class);
    }
}