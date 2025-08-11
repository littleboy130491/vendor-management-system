<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorCategory;
use App\Notifications\VendorRegistrationAdminNotification;
use App\Notifications\VendorRegistrationReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class VendorSelfRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create super admin for notifications
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
    }

    public function test_vendor_registration_form_can_be_rendered()
    {
        $category = VendorCategory::factory()->create(['status' => 'active']);

        $response = $this->get(route('vendor-registration.create'));

        $response->assertOk();
        $response->assertSee('Vendor Registration');
        $response->assertSee($category->name);
    }

    public function test_vendor_can_self_register()
    {
        Notification::fake();
        
        $category = VendorCategory::factory()->create(['status' => 'active']);

        $vendorData = [
            'company_name' => 'Test Company',
            'category_id' => $category->id,
            'contact_name' => 'John Doe',
            'contact_email' => 'john@testcompany.com',
            'contact_phone' => '1234567890',
            'address' => '123 Test St',
            'company_description' => 'We test things',
            'tax_id' => 'TAX-12345',
            'terms_accepted' => true,
        ];

        $response = $this->post(route('vendor-registration.store'), $vendorData);

        $response->assertRedirect(route('vendor-registration.success'));
        $response->assertSessionHas('message');

        $this->assertDatabaseHas('vendors', [
            'company_name' => 'Test Company',
            'contact_email' => 'john@testcompany.com',
            'status' => 'pending',
            'company_description' => 'We test things',
        ]);

        // Check notifications were sent
        Notification::assertSentTo(
            [User::role('super_admin')->first()],
            VendorRegistrationAdminNotification::class
        );

        Notification::assertSentOnDemand(VendorRegistrationReceived::class);
    }

    public function test_registration_requires_valid_data()
    {
        $response = $this->post(route('vendor-registration.store'), []);

        $response->assertSessionHasErrors([
            'company_name',
            'category_id',
            'contact_name',
            'contact_email',
            'terms_accepted'
        ]);
    }

    public function test_duplicate_company_name_rejected()
    {
        $category = VendorCategory::factory()->create();
        Vendor::factory()->create([
            'company_name' => 'Existing Company',
            'category_id' => $category->id,
        ]);

        $response = $this->post(route('vendor-registration.store'), [
            'company_name' => 'Existing Company',
            'category_id' => $category->id,
            'contact_name' => 'John Doe',
            'contact_email' => 'new@email.com',
            'terms_accepted' => true,
        ]);

        $response->assertSessionHasErrors(['company_name']);
    }

    public function test_duplicate_contact_email_rejected()
    {
        $category = VendorCategory::factory()->create();
        Vendor::factory()->create([
            'contact_email' => 'existing@email.com',
            'category_id' => $category->id,
        ]);

        $response = $this->post(route('vendor-registration.store'), [
            'company_name' => 'New Company',
            'category_id' => $category->id,
            'contact_name' => 'John Doe',
            'contact_email' => 'existing@email.com',
            'terms_accepted' => true,
        ]);

        $response->assertSessionHasErrors(['contact_email']);
    }

    public function test_duplicate_tax_id_rejected()
    {
        $category = VendorCategory::factory()->create();
        Vendor::factory()->create([
            'tax_id' => 'TAX-EXISTING',
            'category_id' => $category->id,
        ]);

        $response = $this->post(route('vendor-registration.store'), [
            'company_name' => 'New Company',
            'category_id' => $category->id,
            'contact_name' => 'John Doe',
            'contact_email' => 'new@email.com',
            'tax_id' => 'TAX-EXISTING',
            'terms_accepted' => true,
        ]);

        $response->assertSessionHasErrors(['tax_id']);
    }

    public function test_terms_acceptance_required()
    {
        $category = VendorCategory::factory()->create();

        $response = $this->post(route('vendor-registration.store'), [
            'company_name' => 'Test Company',
            'category_id' => $category->id,
            'contact_name' => 'John Doe',
            'contact_email' => 'john@testcompany.com',
            // terms_accepted not provided
        ]);

        $response->assertSessionHasErrors(['terms_accepted']);
    }

    public function test_invalid_category_rejected()
    {
        $response = $this->post(route('vendor-registration.store'), [
            'company_name' => 'Test Company',
            'category_id' => 999999, // Non-existent category
            'contact_name' => 'John Doe',
            'contact_email' => 'john@testcompany.com',
            'terms_accepted' => true,
        ]);

        $response->assertSessionHasErrors(['category_id']);
    }

    public function test_registration_success_page_can_be_rendered()
    {
        $response = $this->get(route('vendor-registration.success'));

        $response->assertOk();
        $response->assertSee('Registration Successful!');
        $response->assertSee('What happens next?');
    }

    public function test_only_active_categories_shown_in_form()
    {
        $activeCategory = VendorCategory::factory()->create(['status' => 'active']);
        $inactiveCategory = VendorCategory::factory()->create(['status' => 'inactive']);

        $response = $this->get(route('vendor-registration.create'));

        $response->assertSee($activeCategory->name);
        $response->assertDontSee($inactiveCategory->name);
    }

    public function test_vendor_created_with_pending_status()
    {
        $category = VendorCategory::factory()->create();

        $this->post(route('vendor-registration.store'), [
            'company_name' => 'Test Company',
            'category_id' => $category->id,
            'contact_name' => 'John Doe',
            'contact_email' => 'john@testcompany.com',
            'terms_accepted' => true,
        ]);

        $vendor = Vendor::where('company_name', 'Test Company')->first();
        $this->assertEquals('pending', $vendor->status);
    }
}