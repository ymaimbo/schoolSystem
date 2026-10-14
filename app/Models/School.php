<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'location',
        'county',
        'type',
        'status',
        'note',
        'code',
        'logo_path',
        'hero_image_path',
        'billing_cycle_amount_due',
        'billing_cycle_amount_paid',
        'billing_paid_percent',
        'billing_access_stage',
        'billing_grace_until',
        'billing_access_override',
        'billing_access_override_reason',
        'billing_restricted_at',
    ];

    protected $casts = [
        'billing_cycle_amount_due' => 'decimal:2',
        'billing_cycle_amount_paid' => 'decimal:2',
        'billing_paid_percent' => 'decimal:2',
        'billing_grace_until' => 'datetime',
        'billing_access_override' => 'boolean',
        'billing_restricted_at' => 'datetime',
    ];

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }

    public function staffMembers(): HasMany
    {
        return $this->hasMany(StaffMember::class);
    }
}