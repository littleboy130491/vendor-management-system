<?php

namespace Tests\Unit;

use App\Models\Contract;
use App\Models\User;
use App\Models\Vendor;
use App\Models\RFQ;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_contract_can_be_created_with_required_fields(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        
        $contract = Contract::create([
            'contract_number' => 'CON-2025-0001',
            'vendor_id' => $vendor->id,
            'title' => 'Test Contract',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'draft',
            'created_by' => $user->id
        ]);
        
        $this->assertDatabaseHas('contracts', [
            'contract_number' => 'CON-2025-0001',
            'vendor_id' => $vendor->id,
            'title' => 'Test Contract',
            'status' => 'draft'
        ]);
    }

    public function test_contract_belongs_to_vendor(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        
        $contract = Contract::factory()->create([
            'vendor_id' => $vendor->id,
            'created_by' => $user->id
        ]);
        
        $this->assertInstanceOf(Vendor::class, $contract->vendor);
        $this->assertEquals($vendor->id, $contract->vendor->id);
    }

    public function test_contract_belongs_to_rfq(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        $rfq = RFQ::factory()->create();
        
        $contract = Contract::factory()->create([
            'vendor_id' => $vendor->id,
            'rfq_id' => $rfq->id,
            'created_by' => $user->id
        ]);
        
        $this->assertInstanceOf(RFQ::class, $contract->rfq);
        $this->assertEquals($rfq->id, $contract->rfq->id);
    }

    public function test_contract_belongs_to_creator(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        
        $contract = Contract::factory()->create([
            'vendor_id' => $vendor->id,
            'created_by' => $user->id
        ]);
        
        $this->assertInstanceOf(User::class, $contract->creator);
        $this->assertEquals($user->id, $contract->creator->id);
    }

    public function test_is_active_returns_true_for_active_current_contract(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        
        $contract = Contract::factory()->create([
            'vendor_id' => $vendor->id,
            'created_by' => $user->id,
            'status' => 'active',
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addMonth()->toDateString()
        ]);
        
        $this->assertTrue($contract->isActive());
    }

    public function test_is_active_returns_false_for_expired_contract(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        
        $contract = Contract::factory()->create([
            'vendor_id' => $vendor->id,
            'created_by' => $user->id,
            'status' => 'active',
            'start_date' => now()->subMonths(2)->toDateString(),
            'end_date' => now()->subDay()->toDateString()
        ]);
        
        $this->assertFalse($contract->isActive());
    }

    public function test_is_active_returns_false_for_future_contract(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        
        $contract = Contract::factory()->create([
            'vendor_id' => $vendor->id,
            'created_by' => $user->id,
            'status' => 'active',
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addMonth()->toDateString()
        ]);
        
        $this->assertFalse($contract->isActive());
    }

    public function test_is_expiring_soon_returns_true_for_contract_expiring_within_30_days(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        
        $contract = Contract::factory()->create([
            'vendor_id' => $vendor->id,
            'created_by' => $user->id,
            'end_date' => now()->addDays(15)->toDateString()
        ]);
        
        $this->assertTrue($contract->isExpiringSoon());
    }

    public function test_is_expiring_soon_returns_false_for_contract_expiring_later(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        
        $contract = Contract::factory()->create([
            'vendor_id' => $vendor->id,
            'created_by' => $user->id,
            'end_date' => now()->addDays(45)->toDateString()
        ]);
        
        $this->assertFalse($contract->isExpiringSoon());
    }

    public function test_is_expiring_soon_accepts_custom_days_parameter(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        
        $contract = Contract::factory()->create([
            'vendor_id' => $vendor->id,
            'created_by' => $user->id,
            'end_date' => now()->addDays(45)->toDateString()
        ]);
        
        $this->assertTrue($contract->isExpiringSoon(60));
    }

    public function test_generate_contract_number_creates_correct_format(): void
    {
        $contract = new Contract();
        $contractNumber = $contract->generateContractNumber();
        
        $currentYear = now()->year;
        $this->assertStringStartsWith("CON-{$currentYear}-", $contractNumber);
        $this->assertMatchesRegularExpression('/^CON-\d{4}-\d{4}$/', $contractNumber);
    }

    public function test_contract_casts_dates_correctly(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        
        $contract = Contract::factory()->create([
            'vendor_id' => $vendor->id,
            'created_by' => $user->id,
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31'
        ]);
        
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $contract->start_date);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $contract->end_date);
    }

    public function test_contract_casts_json_fields_correctly(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        
        $deliverables = ['Deliverable 1', 'Deliverable 2'];
        $paymentTerms = ['net_30' => true, 'early_discount' => 2];
        
        $contract = Contract::factory()->create([
            'vendor_id' => $vendor->id,
            'created_by' => $user->id,
            'deliverables' => $deliverables,
            'payment_terms' => $paymentTerms
        ]);
        
        $this->assertEquals($deliverables, $contract->deliverables);
        $this->assertEquals($paymentTerms, $contract->payment_terms);
    }
}
