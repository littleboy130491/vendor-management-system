<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Contract extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;
    
    protected $fillable = [
        'contract_number', 'vendor_id', 'rfq_id', 'title', 'description',
        'start_date', 'end_date', 'status', 'terms', 'contract_value',
        'deliverables', 'payment_terms', 'created_by'
    ];
    
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'contract_value' => 'decimal:2',
        'deliverables' => 'array',
        'payment_terms' => 'array'
    ];
    
    // Relationships
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
    
    public function rfq(): BelongsTo
    {
        return $this->belongsTo(RFQ::class);
    }
    
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    public function renewals(): HasMany
    {
        return $this->hasMany(ContractRenewal::class);
    }
    
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }
    
    // Business Logic Methods
    public function isActive(): bool
    {
        return $this->status === 'active' 
            && $this->start_date <= now()->toDateString()
            && $this->end_date >= now()->toDateString();
    }
    
    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->end_date <= now()->addDays($days)->toDateString();
    }
    
    public function generateContractNumber(): string
    {
        $year = now()->year;
        $sequence = Contract::whereYear('created_at', $year)->count() + 1;
        return "CON-{$year}-" . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}
