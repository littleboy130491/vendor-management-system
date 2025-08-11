<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create basic roles and permissions for testing
        $this->app[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        Role::firstOrCreate(['name' => 'super_admin']);
        Role::firstOrCreate(['name' => 'procurement_officer']);
        Role::firstOrCreate(['name' => 'finance_officer']);
        Role::firstOrCreate(['name' => 'vendor']);
        
        Permission::firstOrCreate(['name' => 'view_any_user']);
        Permission::firstOrCreate(['name' => 'create_user']);
    }

    public function test_super_admin_role_exists(): void
    {
        $role = Role::where('name', 'super_admin')->first();
        $this->assertNotNull($role);
    }

    public function test_user_can_be_assigned_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        
        $this->assertTrue($user->hasRole('super_admin'));
    }

    public function test_user_can_have_permission_through_role(): void
    {
        $user = User::factory()->create();
        $role = Role::findByName('super_admin');
        $role->givePermissionTo('view_any_user');
        
        $user->assignRole('super_admin');
        
        $this->assertTrue($user->can('view_any_user'));
    }

    public function test_all_required_roles_exist(): void
    {
        $requiredRoles = ['super_admin', 'procurement_officer', 'finance_officer', 'vendor'];
        
        foreach ($requiredRoles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            $this->assertNotNull($role, "Role {$roleName} should exist");
        }
    }
}