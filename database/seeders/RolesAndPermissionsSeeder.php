<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'booking.view',
            'booking.manage',
            'queue.view',
            'queue.manage',
            'transaction.view',
            'transaction.manage',
            'payment.manage',
            'report.view',
            'catalog.manage',
            'settings.manage',
            'user.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $owner = Role::query()->firstOrCreate(['name' => 'owner', 'guard_name' => 'web']);
        $admin = Role::query()->firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $cashier = Role::query()->firstOrCreate(['name' => 'cashier', 'guard_name' => 'web']);
        $viewer = Role::query()->firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);

        $owner->syncPermissions($permissions);
        $admin->syncPermissions([
            'booking.view',
            'booking.manage',
            'queue.view',
            'queue.manage',
            'transaction.view',
            'transaction.manage',
            'payment.manage',
            'report.view',
            'catalog.manage',
            'settings.manage',
        ]);
        $cashier->syncPermissions([
            'booking.view',
            'booking.manage',
            'queue.view',
            'queue.manage',
            'transaction.view',
            'transaction.manage',
            'payment.manage',
        ]);
        $viewer->syncPermissions(['report.view', 'booking.view', 'queue.view', 'transaction.view']);

        $ownerUser = User::query()->firstOrCreate(
            ['email' => 'owner@readytopict.test'],
            [
                'name' => 'Owner Ready To Pict',
                'phone' => '081111111111',
                'password' => 'password',
                'is_active' => true,
            ]
        );

        $adminUser = User::query()->firstOrCreate(
            ['email' => 'admin@readytopict.test'],
            [
                'name' => 'Admin Ready To Pict',
                'phone' => '082222222222',
                'password' => 'password',
                'is_active' => true,
            ]
        );

        $cashierUser = User::query()->firstOrCreate(
            ['email' => 'cashier@readytopict.test'],
            [
                'name' => 'Cashier Ready To Pict',
                'phone' => '083333333333',
                'password' => 'password',
                'is_active' => true,
            ]
        );

        $ownerUser->syncRoles([$owner]);
        $adminUser->syncRoles([$admin]);
        $cashierUser->syncRoles([$cashier]);
    }
}
