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