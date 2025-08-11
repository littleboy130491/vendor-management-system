<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $procurementOfficer = Role::firstOrCreate(['name' => 'procurement_officer']);
        $financeOfficer = Role::firstOrCreate(['name' => 'finance_officer']);
        $vendor = Role::firstOrCreate(['name' => 'vendor']);

        // Create permissions
        $permissions = [
            // Vendor Management
            'view_any_user',
            'view_user',
            'create_user',
            'update_user',
            'delete_user',
            'delete_any_user',
            
            // Shield role/permission management
            'view_shield::role',
            'view_any_shield::role',
            'create_shield::role',
            'update_shield::role',
            'delete_shield::role',
            'delete_any_shield::role',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign all permissions to super_admin
        $superAdmin->givePermissionTo(Permission::all());

        // Assign specific permissions to other roles
        $procurementOfficer->givePermissionTo([
            'view_any_user',
            'view_user',
            'create_user',
            'update_user',
        ]);

        $financeOfficer->givePermissionTo([
            'view_any_user',
            'view_user',
        ]);

        // Create super admin user
        $user = User::firstOrCreate(
            ['email' => 'webmaster.imajiner@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('L!ttleboy13Li'),
            ]
        );

        if (!$user->hasRole('super_admin')) {
            $user->assignRole('super_admin');
        }
        
        $this->command->info('Roles and permissions seeded successfully!');
        $this->command->info('Super Admin created with email: webmaster.imajiner@gmail.com');
    }
}