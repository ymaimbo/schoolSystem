<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'student_id',
        'grading_system',
        'score',
        'grade',
        'points',
        'cbc_level',
        'cbc_comment',
        'remarks',
        'updated_by',
    ];

    protected $casts = [
        'score' => 'float',
        'points' => 'float',
    ];

    /**
     * Parent exam.
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Student linked to this result row.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}