<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_can_be_created_with_required_fields(): void
    {
        $vendor = Vendor::factory()->create();
        
        $invoice = Invoice::create([
            'invoice_number' => 'INV-001',
            'vendor_id' => $vendor->id,
            'amount' => 1500.00,
            'status' => 'submitted',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'submitted_at' => now()
        ]);
        
        $this->assertDatabaseHas('invoices', [
            'invoice_number' => 'INV-001',
            'vendor_id' => $vendor->id,
            'amount' => 1500.00,
            'status' => 'submitted'
        ]);
    }

    public function test_invoice_belongs_to_vendor(): void
    {
        $vendor = Vendor::factory()->create();
        
        $invoice = Invoice::factory()->create([
            'vendor_id' => $vendor->id
        ]);
        
        $this->assertInstanceOf(Vendor::class, $invoice->vendor);
        $this->assertEquals($vendor->id, $invoice->vendor->id);
    }

    public function test_invoice_belongs_to_purchase_order(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        $po = PurchaseOrder::factory()->create([
            'vendor_id' => $vendor->id,
            'issued_by' => $user->id
        ]);
        
        $invoice = Invoice::factory()->create([
            'vendor_id' => $vendor->id,
            'purchase_order_id' => $po->id
        ]);
        
        $this->assertInstanceOf(PurchaseOrder::class, $invoice->purchaseOrder);
        $this->assertEquals($po->id, $invoice->purchaseOrder->id);
    }

    public function test_invoice_belongs_to_approver(): void
    {
        $vendor = Vendor::factory()->create();
        $approver = User::factory()->create();
        
        $invoice = Invoice::factory()->create([
            'vendor_id' => $vendor->id,
            'approved_by' => $approver->id,
            'approved_at' => now()
        ]);
        
        $this->assertInstanceOf(User::class, $invoice->approver);
        $this->assertEquals($approver->id, $invoice->approver->id);
    }

    public function test_invoice_has_many_payments(): void
    {
        $vendor = Vendor::factory()->create();
        $invoice = Invoice::factory()->create([
            'vendor_id' => $vendor->id
        ]);
        
        $user = User::factory()->create();
        $payment1 = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'processed_by' => $user->id
        ]);
        $payment2 = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'processed_by' => $user->id
        ]);
        
        $this->assertCount(2, $invoice->payments);
        $this->assertTrue($invoice->payments->contains($payment1));
        $this->assertTrue($invoice->payments->contains($payment2));
    }

    public function test_status_color_attribute_returns_correct_colors(): void
    {
        $vendor = Vendor::factory()->create();
        
        $testCases = [
            'submitted' => 'warning',
            'under_review' => 'info',
            'approved' => 'success',
            'rejected' => 'danger',
            'paid' => 'primary',
            'disputed' => 'danger'
        ];
        
        foreach ($testCases as $status => $expectedColor) {
            $invoice = Invoice::factory()->create([
                'vendor_id' => $vendor->id,
                'status' => $status
            ]);
            
            $this->assertEquals($expectedColor, $invoice->status_color);
        }
    }

    public function test_is_overdue_returns_true_for_overdue_unpaid_invoice(): void
    {
        $vendor = Vendor::factory()->create();
        
        $invoice = Invoice::factory()->create([
            'vendor_id' => $vendor->id,
            'due_date' => now()->subDay()->toDateString(),
            'status' => 'approved'
        ]);
        
        $this->assertTrue($invoice->isOverdue());
    }

    public function test_is_overdue_returns_false_for_paid_invoice(): void
    {
        $vendor = Vendor::factory()->create();
        
        $invoice = Invoice::factory()->create([
            'vendor_id' => $vendor->id,
            'due_date' => now()->subDay()->toDateString(),
            'status' => 'paid'
        ]);
        
        $this->assertFalse($invoice->isOverdue());
    }

    public function test_is_overdue_returns_false_for_future_due_date(): void
    {
        $vendor = Vendor::factory()->create();
        
        $invoice = Invoice::factory()->create([
            'vendor_id' => $vendor->id,
            'due_date' => now()->addDay()->toDateString(),
            'status' => 'approved'
        ]);
        
        $this->assertFalse($invoice->isOverdue());
    }

    public function test_total_paid_attribute_calculates_correctly(): void
    {
        $vendor = Vendor::factory()->create();
        $user = User::factory()->create();
        $invoice = Invoice::factory()->create([
            'vendor_id' => $vendor->id,
            'amount' => 1000.00
        ]);
        
        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 300.00,
            'processed_by' => $user->id
        ]);
        
        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 250.00,
            'processed_by' => $user->id
        ]);
        
        $this->assertEquals(550.00, $invoice->total_paid);
    }

    public function test_remaining_balance_attribute_calculates_correctly(): void
    {
        $vendor = Vendor::factory()->create();
        $user = User::factory()->create();
        $invoice = Invoice::factory()->create([
            'vendor_id' => $vendor->id,
            'amount' => 1000.00
        ]);
        
        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 300.00,
            'processed_by' => $user->id
        ]);
        
        $this->assertEquals(700.00, $invoice->remaining_balance);
    }

    public function test_invoice_casts_dates_correctly(): void
    {
        $vendor = Vendor::factory()->create();
        
        $invoice = Invoice::factory()->create([
            'vendor_id' => $vendor->id,
            'invoice_date' => '2025-01-01',
            'due_date' => '2025-01-31',
            'submitted_at' => '2025-01-01 10:00:00',
            'approved_at' => '2025-01-05 14:30:00'
        ]);
        
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $invoice->invoice_date);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $invoice->due_date);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $invoice->submitted_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $invoice->approved_at);
    }
}
