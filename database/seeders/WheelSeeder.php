<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WheelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Lấy id các coupon theo code
        $c10  = \DB::table('coupons')->where('code', 'SPIN10')->value('id');
        $c20k = \DB::table('coupons')->where('code', 'SPIN20K')->value('id');
        $c50k = \DB::table('coupons')->where('code', 'SPIN50K')->value('id');
        $c15  = \DB::table('coupons')->where('code', 'SPIN15')->value('id');

        \DB::table('wheel_slices')->insert(
            [
                ['label' => '10% tối đa 50K', 'type' => 'coupon', 'coupon_id' => $c10, 'weight' => 120, 'stock' => null, 'is_active' => 1, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['label' => '-20K', 'type' => 'coupon', 'coupon_id' => $c20k, 'weight' => 150, 'stock' => null, 'is_active' => 1, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
                ['label' => '-50K (>=300K)', 'type' => 'coupon', 'coupon_id' => $c50k, 'weight' => 80, 'stock' => null, 'is_active' => 1, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
                ['label' => '15% tối đa 120K', 'type' => 'coupon', 'coupon_id' => $c15, 'weight' => 50, 'stock' => null, 'is_active' => 1, 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
                ['label' => 'Hụt rồi :(', 'type' => 'none', 'coupon_id' => null, 'weight' => 300, 'stock' => null, 'is_active' => 1, 'sort_order' => 5, 'created_at' => now(), 'updated_at' => now()],
            ]
        );
    }
}
