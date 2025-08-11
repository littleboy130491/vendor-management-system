<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractRenewal extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'contract_id', 'renewal_date', 'new_end_date', 'notes',
        'new_value', 'updated_terms', 'renewed_by'
    ];
    
    protected $casts = [
        'renewal_date' => 'date',
        'new_end_date' => 'date',
        'new_value' => 'decimal:2',
        'updated_terms' => 'array'
    ];
    
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
    
    public function renewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'renewed_by');
    }
}
