<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MemberTierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('member_tiers')->upsert([
            ['code' => 'member', 'name' => 'Member', 'min_spend_year' => 0, 'point_multiplier' => 1.00, 'monthly_ship_quota' => 0, 'auto_coupon_code' => null, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'silver', 'name' => 'Silver', 'min_spend_year' => 5_000_000, 'point_multiplier' => 1.25, 'monthly_ship_quota' => 1, 'auto_coupon_code' => 'SIL10', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'gold', 'name' => 'Gold', 'min_spend_year' => 12_000_000, 'point_multiplier' => 1.50, 'monthly_ship_quota' => 2, 'auto_coupon_code' => 'GOLD15', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'platinum', 'name' => 'Platinum', 'min_spend_year' => 25_000_000, 'point_multiplier' => 2.00, 'monthly_ship_quota' => 4, 'auto_coupon_code' => 'VIP100', 'created_at' => now(), 'updated_at' => now()],
        ], ['code'], ['name', 'min_spend_year', 'point_multiplier', 'monthly_ship_quota', 'auto_coupon_code', 'updated_at']);
    }
}
