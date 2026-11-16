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

    /**
     * Live schema columns (student_fee_accounts):
     *   id, school_id, student_id (unique), fee_structure_id (nullable FK),
     *   total_fee_due decimal(12,2) NOT NULL default 0,
     *   opening_balance decimal(12,2) NOT NULL default 0  ← NEW (Migration 1)
     *   sponsor_org_name varchar(255) nullable,
     *   sponsor_org_id varchar(255) nullable,
     *   notes text nullable,
     *   created_at, updated_at
     */
    protected $fillable = [
        'school_id',
        'student_id',
        'fee_structure_id',
        'total_fee_due',
        'opening_balance',
        'sponsor_org_name',
        'sponsor_org_id',
        'notes',
    ];

    protected $casts = [
        'total_fee_due'   => 'decimal:2',
        'opening_balance' => 'decimal:2',
    ];

    protected $appends = [
        'client_balance',
    ];

    // ── Relationships ────────────────────────────────────────────────────

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

    // ── Accessors ────────────────────────────────────────────────────────

    public function getAccountBalanceAttribute(): float
    {
        $due = (float) ($this->total_fee_due ?? 0);
        return max($due, 0);
    }

    public function getClientBalanceAttribute(): float
    {
        $due = (float) ($this->total_fee_due ?? 0);
        return $due > 0 ? $due : 0;
    }
}