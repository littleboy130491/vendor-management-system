<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use App\Models\RFQ;
use App\Models\RFQResponse;
use App\Services\RFQService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RFQManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_invite_vendors_and_publish_rfq()
    {
        $officer = User::factory()->create();
        $rfq = \Database\Factories\RFQFactory::new()->create([
            'created_by' => $officer->id,
            'status' => 'draft',
        ]);

        $vendor1 = Vendor::factory()->active()->create();
        $vendor2 = Vendor::factory()->active()->create();

        $service = app(RFQService::class);
        $service->inviteVendors($rfq, [$vendor1->id, $vendor2->id]);

        $this->assertDatabaseHas('rfq_vendors', [
            'rfq_id' => $rfq->id,
            'vendor_id' => $vendor1->id,
            'status' => 'invited',
        ]);
        $this->assertDatabaseHas('rfq_vendors', [
            'rfq_id' => $rfq->id,
            'vendor_id' => $vendor2->id,
            'status' => 'invited',
        ]);

        $service->publishRFQ($rfq);
        $rfq->refresh();
        $this->assertEquals('published', $rfq->status);
    }

    public function test_vendor_can_submit_response_to_invited_rfq()
    {
        $rfq = \Database\Factories\RFQFactory::new()->published()->create();
        $vendor = Vendor::factory()->active()->create();

        $service = app(RFQService::class);
        $service->inviteVendors($rfq, [$vendor->id]);

        $response = $service->submitResponse($rfq, $vendor, [
            'quoted_amount' => 12345.67,
            'delivery_time_days' => 30,
            'notes' => 'We propose a 30-day delivery schedule.',
        ]);

        $this->assertInstanceOf(RFQResponse::class, $response);
        $this->assertDatabaseHas('rfq_responses', [
            'id' => $response->id,
            'rfq_id' => $rfq->id,
            'vendor_id' => $vendor->id,
            'status' => 'submitted',
        ]);

        $this->assertDatabaseHas('rfq_vendors', [
            'rfq_id' => $rfq->id,
            'vendor_id' => $vendor->id,
            'status' => 'responded',
        ]);
    }

    public function test_vendor_cannot_submit_response_if_not_invited()
    {
        $rfq = \Database\Factories\RFQFactory::new()->published()->create();
        $vendor = Vendor::factory()->active()->create();

        $this->expectException(\DomainException::class);

        $service = app(RFQService::class);
        $service->submitResponse($rfq, $vendor, [
            'quoted_amount' => 5000,
            'delivery_time_days' => 10,
        ]);
    }

    public function test_evaluation_calculates_total_score()
    {
        $rfq = \Database\Factories\RFQFactory::new()->published()->create([
            'evaluation_criteria' => [
                'weights' => ['technical' => 0.6, 'commercial' => 0.4],
            ],
        ]);
        $vendor = Vendor::factory()->active()->create();

        $service = app(RFQService::class);
        $service->inviteVendors($rfq, [$vendor->id]);
        $response = $service->submitResponse($rfq, $vendor, [
            'quoted_amount' => 10000,
            'delivery_time_days' => 14,
        ]);

        $evaluator = User::factory()->create();

        $evaluation = $service->evaluateResponse($response, $evaluator, [
            'criteria_scores' => ['technical' => 80, 'commercial' => 90],
            'comments' => 'Solid bid',
        ]);

        $this->assertEquals(84.0, round($evaluation->total_score, 1));
    }

    public function test_award_sets_winner_and_rejects_others()
    {
        $rfq = \Database\Factories\RFQFactory::new()->published()->create([
            'evaluation_criteria' => [
                'weights' => ['technical' => 0.5, 'commercial' => 0.5],
            ],
        ]);

        $vendorA = Vendor::factory()->active()->create();
        $vendorB = Vendor::factory()->active()->create();

        $service = app(RFQService::class);
        $service->inviteVendors($rfq, [$vendorA->id, $vendorB->id]);

        $respA = $service->submitResponse($rfq, $vendorA, ['quoted_amount' => 9500, 'delivery_time_days' => 15]);
        $respB = $service->submitResponse($rfq, $vendorB, ['quoted_amount' => 9000, 'delivery_time_days' => 20]);

        $evaluator = User::factory()->create();
        $service->evaluateResponse($respA, $evaluator, ['criteria_scores' => ['technical' => 85, 'commercial' => 90]]);
        $service->evaluateResponse($respB, $evaluator, ['criteria_scores' => ['technical' => 80, 'commercial' => 92]]);

        // Choose a winner (e.g., best evaluated response)
        $winner = $respA->refresh()->total_score >= $respB->refresh()->total_score ? $respA : $respB;

        $service->closeRFQ($rfq);
        $service->awardContract($rfq, $winner);

        $rfq->refresh();
        $this->assertEquals('awarded', $rfq->status);

        $this->assertEquals('accepted', $winner->refresh()->status);
        $loser = $winner->id === $respA->id ? $respB : $respA;
        $this->assertEquals('rejected', $loser->refresh()->status);
    }
}