<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorCategory;
use App\Services\VendorOnboardingService;
use App\Services\VendorRatingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_onboarding_creates_vendor_and_notifies_admins()
    {
        $category = VendorCategory::factory()->create();

        $service = app(VendorOnboardingService::class);

        $vendor = $service->createVendor([
            'company_name' => 'Acme Corp',
            'category_id' => $category->id,
            'contact_name' => 'John Doe',
            'contact_email' => 'john@example.com',
            'contact_phone' => '1234567890',
            'address' => '123 Street',
            'tax_id' => 'TX-123',
        ]);

        $this->assertDatabaseHas('vendors', [
            'company_name' => 'Acme Corp',
            'status' => 'pending',
        ]);

        $this->assertNotNull($vendor->id);
    }

    public function test_approving_vendor_sets_active_and_creates_user_if_needed()
    {
        $category = VendorCategory::factory()->create();
        $vendor = Vendor::factory()->create([
            'category_id' => $category->id,
            'status' => 'pending',
            'contact_name' => 'Jane',
            'contact_email' => 'jane@example.com',
        ]);
        $approver = User::factory()->create();

        $service = app(VendorOnboardingService::class);
        $service->approveVendor($vendor, $approver);

        $vendor->refresh();

        $this->assertEquals('active', $vendor->status);
        $this->assertNotNull($vendor->user_id);
        $this->assertTrue($vendor->user->hasRole('vendor'));
    }

    public function test_vendor_rating_service_adds_review_and_updates_average()
    {
        $vendor = Vendor::factory()->create();
        $reviewer = User::factory()->create();

        $service = app(VendorRatingService::class);
        $review = $service->addReview($vendor, $reviewer, [
            'quality' => 4,
            'timeliness' => 5,
            'communication' => 3,
            'comments' => 'Great work',
        ]);

        $this->assertDatabaseHas('vendor_reviews', [
            'id' => $review->id,
            'vendor_id' => $vendor->id,
        ]);

        $vendor->refresh();
        $this->assertEquals(4.00, $vendor->rating_average);
    }
}