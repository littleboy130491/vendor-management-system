<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class PurchaseOrder extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;
    
    protected $fillable = [
        'po_number', 'contract_id', 'vendor_id', 'issued_by', 'total_amount',
        'status', 'issued_date', 'expected_delivery_date', 'actual_delivery_date',
        'notes', 'delivery_address'
    ];
    
    protected $casts = [
        'total_amount' => 'decimal:2',
        'issued_date' => 'date',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'delivery_address' => 'array'
    ];
    
    // Relationships
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
    
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
    
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
    
    public function items(): HasMany
    {
        return $this->hasMany(POItem::class);
    }
    
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
    
    // Business Logic Methods
    public function generatePONumber(): string
    {
        $year = now()->year;
        $sequence = PurchaseOrder::whereYear('created_at', $year)->count() + 1;
        return "PO-{$year}-" . str_pad($sequence, 5, '0', STR_PAD_LEFT);
    }
    
    public function calculateTotal(): void
    {
        $total = $this->items()->sum(DB::raw('quantity * unit_price'));
        $this->update(['total_amount' => $total]);
    }
}
