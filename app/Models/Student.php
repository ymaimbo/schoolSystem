<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
}