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
        'products.view',
        'products.create',
        'products.update',
        'products.deactivate',
        'products.import',
        'categories.view',
        'categories.manage',
        'brands.view',
        'brands.manage',
        'units.view',
        'units.manage',
        'inventory.view',
        'inventory.adjust',
        'inventory.opening',
        'inventory.damage',
        'inventory.loss',
        'inventory.count',
        'inventory.history',
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
                'products.view',
                'products.create',
                'products.update',
                'products.deactivate',
                'products.import',
                'categories.view',
                'categories.manage',
                'brands.view',
                'brands.manage',
                'units.view',
                'units.manage',
                'inventory.view',
                'inventory.adjust',
                'inventory.opening',
                'inventory.damage',
                'inventory.loss',
                'inventory.count',
                'inventory.history',
            ],
            'manager' => [
                'companies.view',
                'branches.view',
                'warehouses.view',
                'users.view',
                'products.view',
                'products.create',
                'products.update',
                'categories.view',
                'brands.view',
                'units.view',
                'inventory.view',
                'inventory.adjust',
                'inventory.opening',
                'inventory.damage',
                'inventory.loss',
                'inventory.count',
                'inventory.history',
            ],
            'cashier' => [
                'branches.view',
                'warehouses.view',
                'products.view',
                'inventory.view',
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
