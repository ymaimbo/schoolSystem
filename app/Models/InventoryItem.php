<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'item_name',
        'category',
        'quantity',
        'unit',
        'reorder_level',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'reorder_level' => 'decimal:2',
    ];
}