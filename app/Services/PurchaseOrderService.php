<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\POItem;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Facades\LogActivity;

class PurchaseOrderService
{
    public function createPO(array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data) {
            $po = PurchaseOrder::create([
                'po_number' => $this->generatePONumber(),
                'contract_id' => $data['contract_id'] ?? null,
                'vendor_id' => $data['vendor_id'],
                'issued_by' => auth()->id(),
                'issued_date' => $data['issued_date'] ?? now()->toDateString(),
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'delivery_address' => $data['delivery_address'] ?? null,
                'status' => 'draft',
                'total_amount' => 0 // Will be calculated after items are added
            ]);
            
            // Create PO items
            foreach ($data['items'] as $item) {
                $po->items()->create([
                    'item_name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $item['quantity'] * $item['unit_price'],
                    'unit_of_measure' => $item['unit_of_measure'] ?? null
                ]);
            }
            
            $po->calculateTotal();
            
            activity('po_created')
                ->performedOn($po)
                ->causedBy(auth()->user())
                ->log('Purchase Order created: ' . $po->po_number);
                
            return $po;
        });
    }
    
    public function approvePO(PurchaseOrder $po): void
    {
        DB::transaction(function () use ($po) {
            $po->update(['status' => 'approved']);
            
            // Send PO to vendor
            $this->sendPOToVendor($po);
            
            activity('po_approved')
                ->performedOn($po)
                ->causedBy(auth()->user())
                ->log('Purchase Order approved and sent to vendor');
        });
    }
    
    public function acknowledgePO(PurchaseOrder $po): void
    {
        $po->update(['status' => 'acknowledged']);
        
        activity('po_acknowledged')
            ->performedOn($po)
            ->causedBy(auth()->user())
            ->log('Purchase Order acknowledged by vendor');
    }
    
    public function markDelivered(PurchaseOrder $po, string $actualDeliveryDate = null): void
    {
        $po->update([
            'status' => 'delivered',
            'actual_delivery_date' => $actualDeliveryDate ?? now()->toDateString()
        ]);
        
        activity('po_delivered')
            ->performedOn($po)
            ->causedBy(auth()->user())
            ->log('Purchase Order delivered');
    }
    
    public function completePO(PurchaseOrder $po): void
    {
        $po->update(['status' => 'completed']);
        
        activity('po_completed')
            ->performedOn($po)
            ->causedBy(auth()->user())
            ->log('Purchase Order completed');
    }
    
    public function cancelPO(PurchaseOrder $po, string $reason = null): void
    {
        $po->update(['status' => 'cancelled']);
        
        activity('po_cancelled')
            ->performedOn($po)
            ->causedBy(auth()->user())
            ->log('Purchase Order cancelled: ' . ($reason ?? 'No reason provided'));
    }
    
    public function addItem(PurchaseOrder $po, array $itemData): POItem
    {
        $item = $po->items()->create([
            'item_name' => $itemData['name'],
            'description' => $itemData['description'] ?? null,
            'quantity' => $itemData['quantity'],
            'unit_price' => $itemData['unit_price'],
            'line_total' => $itemData['quantity'] * $itemData['unit_price'],
            'unit_of_measure' => $itemData['unit_of_measure'] ?? null
        ]);
        
        $po->calculateTotal();
        
        activity('po_item_added')
            ->performedOn($po)
            ->causedBy(auth()->user())
            ->log('Item added to Purchase Order: ' . $itemData['name']);
            
        return $item;
    }
    
    public function removeItem(PurchaseOrder $po, POItem $item): void
    {
        $itemName = $item->item_name;
        $item->delete();
        
        $po->calculateTotal();
        
        activity('po_item_removed')
            ->performedOn($po)
            ->causedBy(auth()->user())
            ->log('Item removed from Purchase Order: ' . $itemName);
    }
    
    protected function generatePONumber(): string
    {
        $year = now()->year;
        $sequence = PurchaseOrder::whereYear('created_at', $year)->count() + 1;
        return "PO-{$year}-" . str_pad($sequence, 5, '0', STR_PAD_LEFT);
    }
    
    protected function sendPOToVendor(PurchaseOrder $po): void
    {
        // This would typically send an email with the PO to the vendor
        // Implementation depends on your notification system
        $po->update(['status' => 'sent']);
    }
}