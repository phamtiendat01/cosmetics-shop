<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            ['code' => 'COD',    'name' => 'Thanh toán khi nhận hàng (COD)', 'is_active' => true, 'sort_order' => 1],
            ['code' => 'VIETQR', 'name' => 'Chuyển khoản VietQR',            'is_active' => true, 'sort_order' => 2],
            ['code' => 'MOMO',   'name' => 'MoMo Wallet',                     'is_active' => true, 'sort_order' => 3],
            ['code' => 'VNPAY',  'name' => 'VNPay',                           'is_active' => true, 'sort_order' => 4],
        ];

        foreach ($rows as $r) {
            \Illuminate\Support\Facades\DB::table('payment_methods')
                ->updateOrInsert(['code' => $r['code']], $r);
        }
    }
}
