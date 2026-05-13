<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'term',
        'year',
        'exam_date',
        'max_score',
        'status',
        'assessment_system', // 844 | CBC | HYBRID
        'class_level',
        'pathway',
    ];

    protected $casts = [
        'year' => 'integer',
        'exam_date' => 'date',
        'max_score' => 'decimal:2',
    ];

    public function results(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }
}