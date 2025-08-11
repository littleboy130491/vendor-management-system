<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create super admin for accessing user management
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->actingAs($admin);
    }

    public function test_user_can_be_created_with_role()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'roles' => [1], // super_admin role ID
        ];

        $user = User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => bcrypt($userData['password']),
        ]);

        $user->assignRole('super_admin');

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->assertTrue($user->hasRole('super_admin'));
    }

    public function test_user_email_can_be_verified()
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $this->assertNull($user->email_verified_at);

        $verificationTime = now();
        $user->update(['email_verified_at' => $verificationTime]);
        
        $refreshedUser = $user->fresh();
        $this->assertNotNull($refreshedUser->email_verified_at);
        $this->assertEquals($verificationTime->format('Y-m-d H:i:s'), $refreshedUser->email_verified_at->format('Y-m-d H:i:s'));
    }

    public function test_user_can_have_multiple_roles()
    {
        $user = User::factory()->create();
        
        $user->assignRole(['super_admin', 'vendor']);

        $this->assertTrue($user->hasRole('super_admin'));
        $this->assertTrue($user->hasRole('vendor'));
        $this->assertCount(2, $user->roles);
    }

    public function test_user_roles_are_displayed_correctly()
    {
        $user = User::factory()->create();
        $user->assignRole(['super_admin', 'vendor']);

        $roleNames = $user->roles->pluck('name')->toArray();

        $this->assertContains('super_admin', $roleNames);
        $this->assertContains('vendor', $roleNames);
    }
}