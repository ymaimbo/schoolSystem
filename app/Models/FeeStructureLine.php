<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeStructureLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'fee_structure_id',
        'vote_head_id',
        'term',
        'amount',
        'parent_amount',
        'term1_amount',
        'term2_amount',
        'term3_amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'parent_amount' => 'decimal:2',
        'term1_amount' => 'decimal:2',
        'term2_amount' => 'decimal:2',
        'term3_amount' => 'decimal:2',
    ];

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class, 'fee_structure_id');
    }

    public function voteHead(): BelongsTo
    {
        return $this->belongsTo(VoteHead::class, 'vote_head_id');
    }
}