<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class StaffMember extends Model
{
    use HasFactory, BelongsToSchool;

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
        'sort_order' => 'integer',
        'is_leadership' => 'boolean',
    ];

    public function setRoleAttribute($value): void
    {
        $normalized = str_replace([' ', '-'], '_', strtolower(trim((string) $value)));

        if (! in_array($normalized, User::ALLOWED_ROLES, true)) {
            throw ValidationException::withMessages([
                'role' => 'Invalid role selected.',
            ]);
        }

        $this->attributes['role'] = $normalized;
    }
}