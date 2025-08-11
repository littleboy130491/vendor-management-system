<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'invoice_id', 'payment_reference', 'amount', 'method',
        'paid_date', 'processed_by', 'notes', 'bank_details'
    ];
    
    protected $casts = [
        'amount' => 'decimal:2',
        'paid_date' => 'date',
        'bank_details' => 'array'
    ];
    
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
    
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
