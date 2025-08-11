<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use App\Policies\VendorPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_view_any_requires_permission()
    {
        $user = User::factory()->create();
        $policy = app(VendorPolicy::class);

        $this->assertFalse($policy->viewAny($user));

        $user->givePermissionTo('view_vendors');
        $this->assertTrue($policy->viewAny($user));
    }

    public function test_vendor_self_view_and_update_rules()
    {
        $vendorOwner = User::factory()->create();
        $otherUser = User::factory()->create();

        $vendor = Vendor::factory()->create(['user_id' => $vendorOwner->id, 'status' => 'active']);
        $policy = app(VendorPolicy::class);

        $this->assertTrue($policy->view($vendorOwner, $vendor));
        $this->assertFalse($policy->view($otherUser, $vendor));

        // Update rules: owner can update if not blacklisted
        $this->assertTrue($policy->update($vendorOwner, $vendor));
        $vendor->status = 'blacklisted';
        $this->assertFalse($policy->update($vendorOwner, $vendor));
    }
}