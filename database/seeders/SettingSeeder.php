<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::setMany([
            'store.name' => 'Cosme House',
            'store.currency' => 'VND',
            'store.locale' => 'vi',
            'store.timezone' => 'Asia/Ho_Chi_Minh',
            'seo.default_title' => 'Cosme House',
            'seo.default_description' => 'Mỹ phẩm chính hãng',
            'tracking.gtag_id' => null,
            'tracking.fb_pixel_id' => null,
            'payment.cod.enabled' => 1,
            'payment.cod.fee_percent' => 0,
            'payment.vnpay.enabled' => 0,
            'payment.vnpay.sandbox' => 1,
            'payment.momo.enabled' => 0,
            'payment.momo.sandbox' => 1,
            'checkout.allow_guest' => 1,
            'order.min_total' => 0,
            'order.auto_cancel_minutes' => 0,
            'shipping.freeship_threshold' => 0,
            'shipping.unit.weight' => 'kg',
            'shipping.unit.dimension' => 'cm',
            'mail.from_name' => 'Cosme House',
            'mail.from_address' => 'no-reply@example.com',
            'smtp.host' => null,
            'smtp.port' => null,
            'smtp.username' => null,
            'smtp.password' => null,
            'smtp.encryption' => null,
            'policy.return_days' => 7,
            'policy.return_conditions' => 'Hàng còn nguyên tem, hộp…',
            'pages.about' => '',
            'pages.privacy' => '',
            'pages.terms' => '',
        ]);
    }
}
