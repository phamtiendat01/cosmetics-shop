<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $perms = [
            'view dashboard',
            'manage products',
            'manage orders',
            'manage customers',
            'manage categories',
            'manage brands',
            'manage coupons',
            'manage banners',
            'manage homepage',
            'manage settings',
            'manage roles',
        ];
        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }

        $super = Role::firstOrCreate(['name' => 'super-admin']);
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $staff = Role::firstOrCreate(['name' => 'staff']);
        Role::firstOrCreate(['name' => 'customer']); // chỉ để phân loại, không cấp quyền admin

        $super->syncPermissions(Permission::all());
        $admin->syncPermissions([
            'view dashboard',
            'manage products',
            'manage orders',
            'manage customers',
            'manage categories',
            'manage brands',
            'manage coupons',
            'manage banners',
            'manage homepage',
            'manage settings',
        ]);
        $staff->syncPermissions(['view dashboard', 'manage orders', 'manage customers']);
    }
}
