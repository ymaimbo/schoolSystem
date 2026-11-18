<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class StudentPocketMoneyAccount extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'student_id',
        'opening_balance',
        'current_balance',
        'notes',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
    ];

    protected $appends = [
        'total_deposited',
        'total_withdrawn',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(StudentPocketMoneyTransaction::class, 'student_pocket_money_account_id');
    }

    public function activeTransactions(): HasMany
    {
        $query = $this->hasMany(StudentPocketMoneyTransaction::class, 'student_pocket_money_account_id');

        if (Schema::hasColumn('student_pocket_money_transactions', 'status')) {
            $query->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'reversed');
            });
        }

        return $query;
    }

    public function getTotalDepositedAttribute(): float
    {
        $transactions = $this->relationLoaded('transactions')
            ? $this->transactions
            : $this->activeTransactions()->get();

        return round(
            (float) $transactions
                ->filter(fn ($t) => ($t->status ?? 'active') !== 'reversed')
                ->where('type', 'deposit')
                ->sum('amount'),
            2
        );
    }

    public function getTotalWithdrawnAttribute(): float
    {
        $transactions = $this->relationLoaded('transactions')
            ? $this->transactions
            : $this->activeTransactions()->get();

        return round(
            (float) $transactions
                ->filter(fn ($t) => ($t->status ?? 'active') !== 'reversed')
                ->where('type', 'withdrawal')
                ->sum('amount'),
            2
        );
    }
}