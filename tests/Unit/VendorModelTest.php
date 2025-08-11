<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorCategory;
use App\Models\VendorReview;
use App\Models\VendorWarning;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_has_category_relationship()
    {
        $category = VendorCategory::factory()->create();
        $vendor = Vendor::factory()->create(['category_id' => $category->id]);

        $this->assertInstanceOf(VendorCategory::class, $vendor->category);
        $this->assertEquals($category->id, $vendor->category->id);
    }

    public function test_vendor_has_user_relationship()
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $vendor->user);
        $this->assertEquals($user->id, $vendor->user->id);
    }

    public function test_vendor_has_reviews_relationship()
    {
        $vendor = Vendor::factory()->create();
        $review = VendorReview::factory()->create(['vendor_id' => $vendor->id]);

        $this->assertTrue($vendor->reviews->contains($review));
    }

    public function test_vendor_has_warnings_relationship()
    {
        $vendor = Vendor::factory()->create();
        $warning = VendorWarning::factory()->create(['vendor_id' => $vendor->id]);

        $this->assertTrue($vendor->warnings->contains($warning));
    }

    public function test_vendor_status_defaults_to_pending()
    {
        $vendor = Vendor::factory()->create();
        $this->assertEquals('pending', $vendor->status);
    }

    public function test_vendor_rating_average_defaults_to_zero()
    {
        $vendor = Vendor::factory()->create();
        $this->assertEquals(0.00, $vendor->rating_average);
    }

    public function test_vendor_slug_is_generated_from_company_name()
    {
        $vendor = Vendor::factory()->create(['company_name' => 'Test Company Inc']);
        $this->assertEquals('test-company-inc', $vendor->slug);
    }

    public function test_vendor_active_scope()
    {
        Vendor::factory()->create(['status' => 'active']);
        Vendor::factory()->create(['status' => 'pending']);
        Vendor::factory()->create(['status' => 'suspended']);

        $activeVendors = Vendor::active()->get();
        $this->assertCount(1, $activeVendors);
        $this->assertEquals('active', $activeVendors->first()->status);
    }

    public function test_vendor_pending_scope()
    {
        Vendor::factory()->create(['status' => 'active']);
        Vendor::factory()->create(['status' => 'pending']);
        Vendor::factory()->create(['status' => 'suspended']);

        $pendingVendors = Vendor::pending()->get();
        $this->assertCount(1, $pendingVendors);
        $this->assertEquals('pending', $pendingVendors->first()->status);
    }

    public function test_vendor_update_rating_average()
    {
        $vendor = Vendor::factory()->create();
        $reviewer = User::factory()->create();

        // Create reviews with different ratings
        VendorReview::factory()->create([
            'vendor_id' => $vendor->id,
            'reviewer_id' => $reviewer->id,
            'rating_quality' => 5,
            'rating_timeliness' => 4,
            'rating_communication' => 3
        ]);

        VendorReview::factory()->create([
            'vendor_id' => $vendor->id,
            'reviewer_id' => $reviewer->id,
            'rating_quality' => 3,
            'rating_timeliness' => 2,
            'rating_communication' => 1
        ]);

        $vendor->updateRatingAverage();
        
        // (4 + 2) / 2 = 3.00
        $this->assertEquals(3.00, $vendor->fresh()->rating_average);
    }
}