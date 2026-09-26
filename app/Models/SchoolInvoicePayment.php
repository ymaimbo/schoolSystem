<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolInvoicePayment extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'school_invoice_id',
        'amount',
        'method',
        'reference_no',
        'paid_at',
        'notes',
        'received_by',
    ];

    protected $casts = [
        'school_id' => 'integer',
        'school_invoice_id' => 'integer',
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'received_by' => 'integer',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SchoolInvoice::class, 'school_invoice_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}