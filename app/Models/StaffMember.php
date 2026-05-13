<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'role',
        'department',
        'bio',
        'image_path',
        'sort_order',
        'is_leadership',
    ];

    protected $casts = [
        'is_leadership' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}