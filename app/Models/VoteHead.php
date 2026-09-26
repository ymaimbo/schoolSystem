<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VoteHead extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'code',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function feeStructureLines(): HasMany
    {
        return $this->hasMany(FeeStructureLine::class);
    }

    public function ledgers(): HasMany
    {
        return $this->hasMany(StudentFeeLedger::class);
    }
}