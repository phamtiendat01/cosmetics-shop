<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Models\Coupon;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Product;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $catIds     = Category::query()->pluck('id')->all();
        $brandIds   = Brand::query()->pluck('id')->all();
        $productIds = Product::query()->pluck('id')->all();

        $pick = function (array $ids, int $take) {
            if (empty($ids)) return [];
            shuffle($ids);
            return array_slice($ids, 0, min($take, count($ids)));
        };

        $coupons = [
            [
                'code'                   => 'WELCOME10',
                'name'                   => 'Chào thành viên mới 10%',
                'description'            => 'Giảm 10% cho đơn đầu tiên, tối đa 50K.',
                'discount_type'          => 'percent',
                'discount_value'         => 10,
                'max_discount'           => 50_000,
                'min_order_total'        => 300_000,
                'applied_to'             => 'order',
                'applies_to_ids'         => [],
                'is_stackable'           => false,
                'first_order_only'       => true,
                'is_active'              => true,
                'usage_limit'            => 1000,
                'usage_limit_per_user'   => 1,
                'starts_at'              => $now->copy()->subDays(7),
                'ends_at'                => $now->copy()->addDays(30),
            ],
            [
                'code'                   => 'SUMMER20',
                'name'                   => 'Summer Sale 20% danh mục',
                'description'            => '20% cho một số danh mục hè, tối đa 120K.',
                'discount_type'          => 'percent',
                'discount_value'         => 20,
                'max_discount'           => 120_000,
                'min_order_total'        => 500_000,
                'applied_to'             => 'category',
                'applies_to_ids'         => $pick($catIds, 3),
                'is_stackable'           => false,
                'first_order_only'       => false,
                'is_active'              => true,
                'usage_limit'            => 500,
                'usage_limit_per_user'   => 3,
                'starts_at'              => $now->copy()->subDays(3),
                'ends_at'                => $now->copy()->addDays(15),
            ],
            [
                'code'                   => 'BRAND100K',
                'name'                   => 'Giảm 100K theo thương hiệu',
                'description'            => 'Áp dụng vài thương hiệu; đơn từ 800K.',
                'discount_type'          => 'fixed',
                'discount_value'         => 100_000,
                'max_discount'           => null,
                'min_order_total'        => 800_000,
                'applied_to'             => 'brand',
                'applies_to_ids'         => $pick($brandIds, 2),
                'is_stackable'           => false,
                'first_order_only'       => false,
                'is_active'              => true,
                'usage_limit'            => 800,
                'usage_limit_per_user'   => 2,
                'starts_at'              => $now,
                'ends_at'                => $now->copy()->addDays(20),
            ],
            [
                'code'                   => 'FLASH50',
                'name'                   => 'Flash Sale 50% sản phẩm chọn',
                'description'            => '50% cho 1 số sản phẩm, tối đa 150K.',
                'discount_type'          => 'percent',
                'discount_value'         => 50,
                'max_discount'           => 150_000,
                'min_order_total'        => 0,
                'applied_to'             => 'product',
                'applies_to_ids'         => $pick($productIds, 5),
                'is_stackable'           => false,
                'first_order_only'       => false,
                'is_active'              => true,
                'usage_limit'            => 200,
                'usage_limit_per_user'   => 1,
                'starts_at'              => $now,
                'ends_at'                => $now->copy()->addDays(3),
            ],
            [
                'code'                   => 'FREESHIP50',
                'name'                   => 'Trợ phí ship 50K',
                'description'            => 'Giảm thẳng 50K vào tổng đơn.',
                'discount_type'          => 'fixed',
                'discount_value'         => 50_000,
                'max_discount'           => null,
                'min_order_total'        => 300_000,
                'applied_to'             => 'order',
                'applies_to_ids'         => [],
                'is_stackable'           => true,
                'first_order_only'       => false,
                'is_active'              => true,
                'usage_limit'            => null,
                'usage_limit_per_user'   => null,
                'starts_at'              => $now->copy()->subDays(2),
                'ends_at'                => $now->copy()->addDays(45),
            ],
            [
                'code'                   => 'PAYDAY30',
                'name'                   => 'Siêu sale ngày lương 30%',
                'description'            => '30% toàn đơn, tối đa 200K.',
                'discount_type'          => 'percent',
                'discount_value'         => 30,
                'max_discount'           => 200_000,
                'min_order_total'        => 400_000,
                'applied_to'             => 'order',
                'applies_to_ids'         => [],
                'is_stackable'           => false,
                'first_order_only'       => false,
                'is_active'              => true,
                'usage_limit'            => 500,
                'usage_limit_per_user'   => 2,
                'starts_at'              => $now->copy()->subDays(2),
                'ends_at'                => $now->copy()->addDay(),
            ],
            [
                'code'                   => 'NEWBRAND15',
                'name'                   => 'Thương hiệu mới -15%',
                'description'            => 'Ưu đãi 15% cho thương hiệu mới về.',
                'discount_type'          => 'percent',
                'discount_value'         => 15,
                'max_discount'           => 100_000,
                'min_order_total'        => 200_000,
                'applied_to'             => 'brand',
                'applies_to_ids'         => $pick($brandIds, 3),
                'is_stackable'           => false,
                'first_order_only'       => false,
                'is_active'              => true,
                'usage_limit'            => 300,
                'usage_limit_per_user'   => 3,
                'starts_at'              => $now->copy()->subDay(),
                'ends_at'                => $now->copy()->addDays(60),
            ],
            [
                'code'                   => 'CLEARANCE40',
                'name'                   => 'Xả kho 40%',
                'description'            => 'Áp dụng một số sản phẩm tồn kho.',
                'discount_type'          => 'percent',
                'discount_value'         => 40,
                'max_discount'           => 120_000,
                'min_order_total'        => 0,
                'applied_to'             => 'product',
                'applies_to_ids'         => $pick($productIds, 8),
                'is_stackable'           => false,
                'first_order_only'       => false,
                'is_active'              => true,
                'usage_limit'            => 400,
                'usage_limit_per_user'   => 2,
                'starts_at'              => $now,
                'ends_at'                => $now->copy()->addDays(10),
            ],
            [
                'code'                   => 'VIP100',
                'name'                   => 'VIP -100K cho đơn từ 1.2tr',
                'description'            => 'Ưu đãi khách hàng VIP: giảm thẳng 100K.',
                'discount_type'          => 'fixed',
                'discount_value'         => 100_000,
                'max_discount'           => null,
                'min_order_total'        => 1_200_000,
                'applied_to'             => 'order',
                'applies_to_ids'         => [],
                'is_stackable'           => true,
                'first_order_only'       => false,
                'is_active'              => true,
                'usage_limit'            => 300,
                'usage_limit_per_user'   => 1,
                'starts_at'              => $now->copy()->subDays(5),
                'ends_at'                => $now->copy()->addDays(25),
            ],
            [
                'code'                   => 'EXPIRED10',
                'name'                   => 'Hết hạn 10% (mẫu)',
                'description'            => 'Mã ví dụ cho trạng thái “hết hạn”.',
                'discount_type'          => 'percent',
                'discount_value'         => 10,
                'max_discount'           => 80_000,
                'min_order_total'        => 200_000,
                'applied_to'             => 'order',
                'applies_to_ids'         => [],
                'is_stackable'           => false,
                'first_order_only'       => false,
                'is_active'              => true,
                'usage_limit'            => 100,
                'usage_limit_per_user'   => 1,
                'starts_at'              => $now->copy()->subDays(10),
                'ends_at'                => $now->copy()->subDay(),
            ],
        ];

        // Fallback nếu thiếu dữ liệu liên quan
        foreach ($coupons as &$c) {
            if ($c['applied_to'] === 'category' && empty($c['applies_to_ids'])) {
                $c['applied_to'] = 'order';
                $c['applies_to_ids'] = [];
            }
            if ($c['applied_to'] === 'brand' && empty($c['applies_to_ids'])) {
                $c['applied_to'] = 'order';
                $c['applies_to_ids'] = [];
            }
            if ($c['applied_to'] === 'product' && empty($c['applies_to_ids'])) {
                $c['applied_to'] = 'order';
                $c['applies_to_ids'] = [];
            }
        }
        unset($c);

        foreach ($coupons as $c) {
            // tránh trùng code
            Coupon::firstOrCreate(['code' => $c['code']], $c);
        }
    }
}
