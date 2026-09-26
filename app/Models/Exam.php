<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'title',
        'term',
        'year',
        'exam_date',
        'max_score',
        'status',
        'assessment_system',
        'class_level',
        'stream',
        'subject',
        'pathway',
    ];

    protected $casts = [
        'year' => 'integer',
        'max_score' => 'float',
        'exam_date' => 'date',
    ];

    /**
     * All results recorded for this exam.
     */
    public function results(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }
}