<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisciplineCase extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_type',
        'student_id',
        'worker_name',
        'worker_department',
        'case_title',
        'description',
        'status',
        'reported_on',
        'action_taken',
        'next_step',
        'handled_by',
    ];

    protected $casts = [
        'reported_on' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}