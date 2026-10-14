<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceTransaction extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'type',
        'category',
        'amount',
        'status',
        'entry_date',
        'description',
        'source_module',
        'reference_no',
        'meta',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'entry_date' => 'date',
        'meta' => 'array',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}