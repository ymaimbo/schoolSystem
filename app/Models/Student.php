<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes, BelongsToSchool;

    protected $fillable = [
        'school_id', 'fee_profile_id', 'admission_no', 'first_name', 'last_name',
        'parent_name', 'parent_phone', 'gender', 'class_level', 'stream', 'status', 'form_level'
    ];

    protected $appends = ['full_name'];

    public function feeAccount(): HasOne
    {
        return $this->hasOne(StudentFeeAccount::class);
    }

    public function feePayments(): HasMany
    {
        return $this->hasMany(StudentFeePayment::class);
    }

    public function feeProfile(): BelongsTo
    {
        return $this->belongsTo(FeeProfile::class, 'fee_profile_id');
    }

    /**
     * Accessor for full name - required by the statement template.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}