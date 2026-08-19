<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeStructureLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'fee_structure_id',
        'vote_head_id',
        'govt_capitation_amount',
        'parent_total_amount',
        'term1_amount',
        'term2_amount',
        'term3_amount',
        'total_amount',
        'sort_order',
    ];

    protected $casts = [
        'govt_capitation_amount' => 'decimal:2',
        'parent_total_amount' => 'decimal:2',
        'term1_amount' => 'decimal:2',
        'term2_amount' => 'decimal:2',
        'term3_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function structure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class, 'fee_structure_id');
    }

    public function voteHead(): BelongsTo
    {
        return $this->belongsTo(VoteHead::class);
    }

    public function ledgers(): HasMany
    {
        return $this->hasMany(StudentFeeLedger::class);
    }
}