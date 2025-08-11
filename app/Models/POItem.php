<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class POItem extends Model
{
    use HasFactory;
    
    protected $table = 'po_items';
    
    protected $fillable = [
        'purchase_order_id', 'item_name', 'description', 'quantity',
        'unit_price', 'line_total', 'unit_of_measure'
    ];
    
    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2'
    ];
    
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}
