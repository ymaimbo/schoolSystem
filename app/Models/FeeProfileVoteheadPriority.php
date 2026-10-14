<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeProfileVoteheadPriority extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'fee_profile_id',
        'vote_head_id',
        'priority_order',
        'is_required',
        'is_active',
    ];

    protected $casts = [
        'priority_order' => 'integer',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function feeProfile(): BelongsTo
    {
        return $this->belongsTo(FeeProfile::class);
    }

    public function voteHead(): BelongsTo
    {
        return $this->belongsTo(VoteHead::class);
    }
}