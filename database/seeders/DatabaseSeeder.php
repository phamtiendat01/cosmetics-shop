<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            BrandSeeder::class,
            ProductSeeder::class,
            // ProductImageSeeder::class, // nếu có
            OrderSeeder::class,
            DatabaseSeeder::class,
            CouponSeeder::class,
            AdminUserSeeder::class,
            RolesAndPermissionsSeeder::class,
            SettingSeeder::class,
            SampleOrdersSeeder::class,
            OneOrderSeeder::class,
            PaymentMethodSeeder::class,
            WheelSeeder::class,
            TryOnAssetsSeeder::class,
        ]);
    }
}
