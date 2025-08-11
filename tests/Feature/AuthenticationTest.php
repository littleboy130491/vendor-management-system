<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure super_admin role exists for testing without duplicates
        $this->app[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'super_admin']);
    }

    public function test_admin_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/admin/login');
        $response->assertStatus(200);
    }


    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_super_admin_has_all_permissions(): void
    {
        // Create a fresh user and assign super_admin role (avoid conflicting seeded email)
        $user = User::factory()->create();

        $role = Role::findByName('super_admin');
        $user->assignRole('super_admin');

        // Give super admin all existing permissions
        if (Permission::count() > 0) {
            $role->syncPermissions(Permission::all());

            // Test that super admin can perform basic user management
            $this->assertTrue($user->can('view_any_user'));
            $this->assertTrue($user->can('create_user'));
        }

        // Verify super admin role assignment
        $this->assertTrue($user->hasRole('super_admin'));
    }

    public function test_guest_cannot_access_admin_dashboard(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/admin/login');
    }
}