<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'staff_type',
        'role_category',
        'department',
        'phone',
        'email',
        'employment_status',
        'is_on_duty',
        'duty_date',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'is_on_duty' => 'boolean',
        'duty_date' => 'date',
    ];

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}