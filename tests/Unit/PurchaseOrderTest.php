<?php

namespace Tests\Unit;

use App\Models\Contract;
use App\Models\POItem;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_order_can_be_created_with_required_fields(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        
        $po = PurchaseOrder::create([
            'po_number' => 'PO-2025-00001',
            'vendor_id' => $vendor->id,
            'issued_by' => $user->id,
            'total_amount' => 1000.00,
            'status' => 'draft',
            'issued_date' => now()->toDateString()
        ]);
        
        $this->assertDatabaseHas('purchase_orders', [
            'po_number' => 'PO-2025-00001',
            'vendor_id' => $vendor->id,
            'total_amount' => 1000.00,
            'status' => 'draft'
        ]);
    }

    public function test_purchase_order_belongs_to_vendor(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        
        $po = PurchaseOrder::factory()->create([
            'vendor_id' => $vendor->id,
            'issued_by' => $user->id
        ]);
        
        $this->assertInstanceOf(Vendor::class, $po->vendor);
        $this->assertEquals($vendor->id, $po->vendor->id);
    }

    public function test_purchase_order_belongs_to_contract(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        $contract = Contract::factory()->create([
            'vendor_id' => $vendor->id,
            'created_by' => $user->id
        ]);
        
        $po = PurchaseOrder::factory()->create([
            'vendor_id' => $vendor->id,
            'contract_id' => $contract->id,
            'issued_by' => $user->id
        ]);
        
        $this->assertInstanceOf(Contract::class, $po->contract);
        $this->assertEquals($contract->id, $po->contract->id);
    }

    public function test_purchase_order_belongs_to_issuer(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        
        $po = PurchaseOrder::factory()->create([
            'vendor_id' => $vendor->id,
            'issued_by' => $user->id
        ]);
        
        $this->assertInstanceOf(User::class, $po->issuer);
        $this->assertEquals($user->id, $po->issuer->id);
    }

    public function test_purchase_order_has_many_items(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        $po = PurchaseOrder::factory()->create([
            'vendor_id' => $vendor->id,
            'issued_by' => $user->id
        ]);
        
        $item1 = POItem::factory()->create(['purchase_order_id' => $po->id]);
        $item2 = POItem::factory()->create(['purchase_order_id' => $po->id]);
        
        $this->assertCount(2, $po->items);
        $this->assertTrue($po->items->contains($item1));
        $this->assertTrue($po->items->contains($item2));
    }

    public function test_generate_po_number_creates_correct_format(): void
    {
        $po = new PurchaseOrder();
        $poNumber = $po->generatePONumber();
        
        $currentYear = now()->year;
        $this->assertStringStartsWith("PO-{$currentYear}-", $poNumber);
        $this->assertMatchesRegularExpression('/^PO-\d{4}-\d{5}$/', $poNumber);
    }

    public function test_calculate_total_updates_total_amount_from_items(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        $po = PurchaseOrder::factory()->create([
            'vendor_id' => $vendor->id,
            'issued_by' => $user->id,
            'total_amount' => 0
        ]);
        
        // Create items with known amounts
        POItem::factory()->create([
            'purchase_order_id' => $po->id,
            'quantity' => 2,
            'unit_price' => 100.00,
            'line_total' => 200.00
        ]);
        
        POItem::factory()->create([
            'purchase_order_id' => $po->id,
            'quantity' => 3,
            'unit_price' => 50.00,
            'line_total' => 150.00
        ]);
        
        $po->calculateTotal();
        $po->refresh();
        
        $this->assertEquals(350.00, $po->total_amount);
    }

    public function test_purchase_order_casts_dates_correctly(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        
        $po = PurchaseOrder::factory()->create([
            'vendor_id' => $vendor->id,
            'issued_by' => $user->id,
            'issued_date' => '2025-01-01',
            'expected_delivery_date' => '2025-01-15',
            'actual_delivery_date' => '2025-01-14'
        ]);
        
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $po->issued_date);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $po->expected_delivery_date);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $po->actual_delivery_date);
    }

    public function test_purchase_order_casts_delivery_address_as_array(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        
        $address = [
            'street' => '123 Main St',
            'city' => 'Anytown',
            'state' => 'CA',
            'zip' => '12345'
        ];
        
        $po = PurchaseOrder::factory()->create([
            'vendor_id' => $vendor->id,
            'issued_by' => $user->id,
            'delivery_address' => $address
        ]);
        
        $this->assertEquals($address, $po->delivery_address);
    }
}
