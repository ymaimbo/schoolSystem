<?php
// app/Models/Student.php

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
        'school_id',
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
        'fee_profile_id',
    ];

    protected $casts = [
        'form_level' => 'integer',
        'entry_marks' => 'decimal:2',
        'fee_profile_id' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Student $student) {
            if (!$student->school_id && app()->bound('currentSchool') && app('currentSchool')) {
                $student->school_id = (int) app('currentSchool')->id;
            }

            if ($student->entry_marks === null || $student->entry_marks === '') {
                $student->entry_marks = 0;
            }

            if (!$student->status) {
                $student->status = 'active';
            }

            if (!$student->contact_preference) {
                $student->contact_preference = 'parent';
            }
        });
    }

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

    public function feeProfile(): BelongsTo
    {
        return $this->belongsTo(FeeProfile::class, 'fee_profile_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }
}