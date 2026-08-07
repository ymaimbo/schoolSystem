<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Normalize + validate role before saving.
     */
    public function setRoleAttribute($value): void
    {
        $normalized = str_replace([' ', '-'], '_', strtolower(trim((string) $value)));

        if (!in_array($normalized, self::ALLOWED_ROLES, true)) {
            throw ValidationException::withMessages([
                'role' => 'Invalid role selected.',
            ]);
        }

        $this->attributes['role'] = $normalized;
    }
}