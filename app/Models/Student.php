<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'admission_no',
        'first_name',
        'last_name',
        'parent_name',
        'parent_phone',
        'guardian_name',
        'guardian_phone',
        'guardian_relationship',
        'contact_preference',
        'gender',
        'education_system',
        'class_level',
        'pathway',
        'entry_marks',
        'form_level',
        'stream',
        'status',
    ];

    protected $casts = [
        'form_level' => 'integer',
        'entry_marks' => 'decimal:2',
    ];

    public function examResults(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }

    public function parentMessages(): HasMany
    {
        return $this->hasMany(ParentMessage::class);
    }

    public function feeAccount(): HasOne
    {
        return $this->hasOne(StudentFeeAccount::class);
    }

    public function feePayments(): HasMany
    {
        return $this->hasMany(StudentFeePayment::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }
}