<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentFeePayment extends Model
{
    use HasFactory, BelongsToSchool;

    /**
     * Live schema columns (student_fee_payments):
     *   id, school_id, student_id, student_fee_account_id,
     *   amount decimal(12,2) NOT NULL,
     *   payment_method varchar(32) NOT NULL,
     *   organization_name varchar(255) nullable,
     *   organization_id varchar(255) nullable,
     *   receipt_no varchar(255) nullable UNIQUE,
     *   paid_at date NOT NULL,
     *   recorded_by bigint unsigned nullable (FK → users),
     *   notes text nullable,
     *   status varchar(20) DEFAULT 'active'        ← NEW (Migration 1)
     *   reversed_at timestamp nullable              ← NEW (Migration 1)
     *   reversed_by bigint unsigned nullable (FK)   ← NEW (Migration 1)
     *   reversal_reason varchar(255) nullable       ← NEW (Migration 1)
     *   created_at, updated_at
     *
     * Intentionally omitted (do NOT exist on live table):
     *   method, reference, received_by
     */
    protected $fillable = [
        'school_id',
        'student_id',
        'student_fee_account_id',
        'amount',
        'payment_method',
        'organization_name',
        'organization_id',
        'receipt_no',
        'paid_at',
        'recorded_by',
        'notes',
        'status',
        'reversed_at',
        'reversed_by',
        'reversal_reason',
    ];

    protected $casts = [
        'amount'      => 'decimal:2',
        'paid_at'     => 'datetime',
        'reversed_at' => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────────────

    public function account(): BelongsTo
    {
        return $this->belongsTo(StudentFeeAccount::class, 'student_fee_account_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function reverser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return ($this->status ?? 'active') === 'active';
    }

    public function isReversed(): bool
    {
        return ($this->status ?? 'active') === 'reversed';
    }
}