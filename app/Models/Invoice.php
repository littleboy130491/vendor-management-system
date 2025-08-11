<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Invoice extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;
    
    protected $fillable = [
        'invoice_number', 'vendor_id', 'purchase_order_id', 'amount',
        'status', 'invoice_date', 'due_date', 'submitted_at',
        'approved_at', 'approved_by', 'rejection_reason', 'notes'
    ];
    
    protected $casts = [
        'amount' => 'decimal:2',
        'invoice_date' => 'date',
        'due_date' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime'
    ];
    
    // Relationships
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
    
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
    
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
    
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
    
    // Accessors
    protected function statusColor(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->status) {
                'submitted' => 'warning',
                'under_review' => 'info',
                'approved' => 'success',
                'rejected' => 'danger',
                'paid' => 'primary',
                'disputed' => 'danger',
                default => 'gray'
            }
        );
    }
    
    public function isOverdue(): bool
    {
        return $this->due_date < now()->toDateString() && $this->status !== 'paid';
    }
    
    protected function totalPaid(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->payments()->sum('amount')
        );
    }
    
    protected function remainingBalance(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->amount - $this->total_paid
        );
    }
}
