<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Validation\ValidationException;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Allowed system roles.
     */
    public const ALLOWED_ROLES = [
        'super_admin',      // global platform admin
        'school_admin',     // admin scoped to one school
        'principal',
        'deputy_principal',
        'dean',
        'hod',
        'school_examiner',
        'class_teacher',
        'secretary',
        'accountant',
        'store_keeper',
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'school_id',
        'name',
        'email',
        'password',
        'role',
        'department',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'school_id' => 'integer',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Normalize and validate role before persisting.
     */
    public function setRoleAttribute($value): void
    {
        $normalized = str_replace([' ', '-'], '_', strtolower(trim((string) $value)));

        if (! in_array($normalized, self::ALLOWED_ROLES, true)) {
            throw ValidationException::withMessages([
                'role' => 'Invalid role selected.',
            ]);
        }

        $this->attributes['role'] = $normalized;
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}