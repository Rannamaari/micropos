<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    private const PERMISSIONS = [
        'companies.view',
        'companies.manage',
        'branches.view',
        'branches.manage',
        'warehouses.view',
        'warehouses.manage',
        'users.view',
        'users.create',
        'users.update',
        'users.deactivate',
        'roles.view',
        'roles.manage',
    ];

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permissionName) {
            Permission::query()->updateOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                []
            );
        }

        $roles = [
            'super-admin' => self::PERMISSIONS,
            'admin' => [
                'companies.view',
                'branches.view',
                'branches.manage',
                'warehouses.view',
                'warehouses.manage',
                'users.view',
                'users.create',
                'users.update',
                'users.deactivate',
                'roles.view',
            ],
            'manager' => [
                'companies.view',
                'branches.view',
                'warehouses.view',
                'users.view',
            ],
            'cashier' => [
                'branches.view',
                'warehouses.view',
            ],
        ];

        foreach ($roles as $roleName => $permissions) {
            $role = Role::query()->updateOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
                []
            );

            $role->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
