<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolInvoice extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'reference_no',
        'item',
        'category',
        'period_start',
        'period_end',
        'due_date',
        'invoiced_amount',
        'balance_amount',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'school_id' => 'integer',
        'invoiced_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
        'due_date' => 'date',
        'created_by' => 'integer',
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(SchoolInvoicePayment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}