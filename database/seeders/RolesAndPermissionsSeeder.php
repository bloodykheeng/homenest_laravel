<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// php artisan db:seed --class=RolesAndPermissionsSeeder
// php artisan permission:cache-reset

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        $rolesWithPermissions = [

            'System Admin' => [
                'view user',
                'manage user',

                'manage notifications',

                'manage orders',

                'manage transactions',
            ],

            'Customer' => [],

        ];

        $this->createRolesAndPermissions($rolesWithPermissions);

        $this->command->info('Roles & Permissions Seeded Successfully.');
    }

    private function createRolesAndPermissions(array $rolesWithPermissions)
    {
        // Collect all role names and permissions from the array
        $definedRoleNames = array_keys($rolesWithPermissions);
        $definedPermissionNames = collect($rolesWithPermissions)
            ->flatten()
            ->unique()
            ->filter()
            ->values()
            ->toArray();

        // 1. Delete roles that are no longer defined
        Role::whereNotIn('name', $definedRoleNames)->each(function ($role) {
            $this->command->warn("🗑️  Deleting role: {$role->name}");
            $role->delete();
        });

        // 2. Create/update roles and their permissions
        foreach ($rolesWithPermissions as $roleName => $permissions) {
            // Create or retrieve the role
            $role = Role::firstOrCreate(['name' => $roleName]);

            // Ensure all permissions exist
            $permissionInstances = collect($permissions)->map(function ($permissionName) {
                return Permission::firstOrCreate(['name' => $permissionName]);
            });

            // Sync permissions with the role
            $role->syncPermissions($permissionInstances);

            $this->command->info("✅ Synced role: {$roleName} with " . count($permissions) . ' permissions');
        }

        // 3. Delete permissions that are no longer defined anywhere
        Permission::whereNotIn('name', $definedPermissionNames)->each(function ($permission) {
            $this->command->warn("🗑️  Deleting permission: {$permission->name}");
            $permission->delete();
        });
    }
}
