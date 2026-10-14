<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeProfile extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'code',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function voteheadPriorities(): HasMany
    {
        return $this->hasMany(FeeProfileVoteheadPriority::class)
            ->orderBy('priority_order');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}