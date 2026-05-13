<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Validation\ValidationException;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_PRINCIPAL = 'principal';
    public const ROLE_DEPUTY_PRINCIPAL = 'deputy_principal';
    public const ROLE_HOD = 'hod';
    public const ROLE_ACCOUNTANT = 'accountant';
    public const ROLE_STORE_KEEPER = 'store_keeper';
    public const ROLE_SECRETARY = 'secretary';

    // Backward compatibility alias
    public const ROLE_BURSAR = self::ROLE_ACCOUNTANT;

    public const ROLES = [
        self::ROLE_PRINCIPAL,
        self::ROLE_DEPUTY_PRINCIPAL,
        self::ROLE_HOD,
        self::ROLE_ACCOUNTANT,
        self::ROLE_STORE_KEEPER,
        self::ROLE_SECRETARY,
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'department',
        'departments',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'departments' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (User $user) {
            $role = strtolower(trim((string) $user->role));

            if (! in_array($role, self::ROLES, true)) {
                throw ValidationException::withMessages([
                    'role' => 'Invalid role selected.',
                ]);
            }

            $user->role = $role;
        });
    }
}