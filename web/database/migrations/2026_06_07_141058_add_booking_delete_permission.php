<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::firstOrCreate([
            'name' => 'booking.delete',
            'guard_name' => 'web',
        ]);

        $admin = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        $admin?->givePermissionTo($permission);
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $admin = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        $admin?->revokePermissionTo('booking.delete');

        Permission::where('name', 'booking.delete')
            ->where('guard_name', 'web')
            ->delete();
    }
};
