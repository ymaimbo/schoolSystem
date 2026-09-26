<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffRecord extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
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
        'login_user_id',
    ];

    protected $casts = [
        'school_id' => 'integer',
        'recorded_by' => 'integer',
        'login_user_id' => 'integer',
        'is_on_duty' => 'boolean',
        'duty_date' => 'date',
    ];

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function loginUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'login_user_id');
    }
}