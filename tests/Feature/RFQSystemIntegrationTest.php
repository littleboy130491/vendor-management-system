<?php

namespace Tests\Feature;

use App\Models\RFQ;
use App\Models\RFQResponse;
use App\Models\RFQEvaluation;
use App\Models\Vendor;
use App\Models\VendorCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RFQSystemIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_rfq_system_workflow(): void
    {
        // Create required users
        $admin = User::factory()->create(['name' => 'Admin User']);
        $evaluator = User::factory()->create(['name' => 'Evaluator']);

        // Create vendor category
        $category = VendorCategory::factory()->create([
            'name' => 'IT Services',
            'status' => 'active'
        ]);

        // Create vendors
        $vendor1 = Vendor::factory()->create([
            'company_name' => 'Tech Solutions Inc',
            'contact_email' => 'contact@techsolutions.com',
            'category_id' => $category->id,
            'status' => 'active'
        ]);

        $vendor2 = Vendor::factory()->create([
            'company_name' => 'Digital Services Ltd',
            'contact_email' => 'hello@digitalservices.com', 
            'category_id' => $category->id,
            'status' => 'active'
        ]);

        // Create RFQ
        $rfq = RFQ::factory()->create([
            'title' => 'Software Development Services',
            'status' => 'published',
            'created_by' => $admin->id,
            'budget' => 50000.00
        ]);

        // Attach vendors to RFQ
        $rfq->vendors()->attach($vendor1->id, [
            'status' => 'invited',
            'invited_at' => now()
        ]);
        $rfq->vendors()->attach($vendor2->id, [
            'status' => 'invited', 
            'invited_at' => now()
        ]);

        // Create RFQ responses
        $response1 = RFQResponse::factory()->create([
            'rfq_id' => $rfq->id,
            'vendor_id' => $vendor1->id,
            'quoted_amount' => 45000.00,
            'delivery_time_days' => 90,
            'status' => 'submitted',
            'notes' => 'We can deliver this within timeline',
            'submitted_at' => now()
        ]);

        $response2 = RFQResponse::factory()->create([
            'rfq_id' => $rfq->id,
            'vendor_id' => $vendor2->id,
            'quoted_amount' => 48000.00,
            'delivery_time_days' => 75,
            'status' => 'submitted',
            'notes' => 'Faster delivery option available',
            'submitted_at' => now()
        ]);

        // Update pivot status when response is submitted
        $rfq->vendors()->updateExistingPivot($vendor1->id, [
            'status' => 'responded',
            'responded_at' => now()
        ]);
        $rfq->vendors()->updateExistingPivot($vendor2->id, [
            'status' => 'responded', 
            'responded_at' => now()
        ]);

        // Create evaluations
        $evaluation1 = RFQEvaluation::factory()->create([
            'rfq_response_id' => $response1->id,
            'evaluator_id' => $evaluator->id,
            'criteria_scores' => [
                'technical' => 8.5,
                'commercial' => 9.0,
                'timeline' => 7.5
            ],
            'comments' => 'Strong technical proposal',
            'total_score' => 8.33
        ]);

        $evaluation2 = RFQEvaluation::factory()->create([
            'rfq_response_id' => $response2->id,
            'evaluator_id' => $evaluator->id,
            'criteria_scores' => [
                'technical' => 8.0,
                'commercial' => 7.5,
                'timeline' => 9.0
            ],
            'comments' => 'Good timeline but higher cost',
            'total_score' => 8.17
        ]);

        // Assertions
        $this->assertCount(2, $rfq->vendors);
        $this->assertCount(2, $rfq->responses);
        
        $this->assertEquals('Tech Solutions Inc', $rfq->responses->first()->vendor->company_name);
        $this->assertEquals(45000.00, $rfq->responses->first()->quoted_amount);
        $this->assertEquals(90, $rfq->responses->first()->delivery_time_days);
        
        $this->assertCount(1, $response1->evaluations);
        $this->assertEquals(8.33, $evaluation1->total_score);
        
        // Test vendor pivot relationships
        $rfqVendor = $rfq->vendors()->wherePivot('vendor_id', $vendor1->id)->first();
        $this->assertEquals('responded', $rfqVendor->pivot->status);
        $this->assertNotNull($rfqVendor->pivot->responded_at);
    }
}