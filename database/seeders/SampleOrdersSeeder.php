<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SampleOrdersSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Lấy sẵn 2-3 sản phẩm; nếu thiếu thì tạo tạm
            $products = DB::table('products')->select('id', 'name')
                ->inRandomOrder()->limit(3)->get();

            if ($products->count() < 2) {
                // đảm bảo có 1 category để tạo product (vì category_id NOT NULL)
                $catId = DB::table('categories')->value('id');
                if (!$catId) {
                    $catId = DB::table('categories')->insertGetId([
                        'name'       => 'Mặc định',
                        'slug'       => Str::slug('Mặc định') . '-' . Str::random(4),
                        'is_active'  => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // tạo tối thiểu 2 sản phẩm
                for ($i = 1; $i <= 2; $i++) {
                    $pid = DB::table('products')->insertGetId([
                        'category_id' => $catId,
                        'brand_id'    => null,
                        'name'        => 'SP Demo ' . $i,
                        'slug'        => Str::slug('sp demo ' . $i) . '-' . Str::random(4),
                        'thumbnail'   => null,
                        'image'       => null,
                        'is_active'   => 1,
                        'has_variants' => 0,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                    $products->push((object)['id' => $pid, 'name' => 'SP Demo ' . $i]);
                }
            }

            // Helper chọn 1 sản phẩm ngẫu nhiên
            $pick = function () use ($products) {
                return $products[random_int(0, $products->count() - 1)];
            };

            // ===== ĐƠN 1: COD / paid / completed =====
            $items1 = [
                [
                    'product'   => $pick(),
                    'unit_price' => 149000,
                    'qty' => 1,
                ],
                [
                    'product'   => $pick(),
                    'unit_price' => 99000,
                    'qty' => 2,
                ],
            ];
            $this->createOrder(
                code: $this->code(),
                customerName: 'Khách 1',
                customerEmail: 'user1@example.com',
                customerPhone: '0900000001',
                paymentMethod: 'COD',
                paymentStatus: 'paid',
                status: 'completed',        // tùy enum của bạn, nếu không có 'completed' đổi sang 'confirmed'
                shippingMethod: 'VNPOST',
                shippingFee: 30000,
                placedAt: Carbon::now()->subDays(1),
                items: $items1
            );

            // ===== ĐƠN 2: MOMO / unpaid / pending =====
            $items2 = [
                [
                    'product'   => $pick(),
                    'unit_price' => 259000,
                    'qty' => 1,
                ],
            ];
            $this->createOrder(
                code: $this->code(),
                customerName: 'Khách 2',
                customerEmail: 'user2@example.com',
                customerPhone: '0900000002',
                paymentMethod: 'MOMO',
                paymentStatus: 'unpaid',
                status: 'pending',
                shippingMethod: 'GHTK',
                shippingFee: 22000,
                placedAt: Carbon::now()->subHours(6),
                items: $items2
            );
        });
    }

    private function code(): string
    {
        return '#DH' . Str::upper(Str::random(6));
    }

    /**
     * Tạo order + items và tính tiền
     *
     * @param array<array{product:object, unit_price:int|float, qty:int}> $items
     */
    private function createOrder(
        string $code,
        string $customerName,
        string $customerEmail,
        string $customerPhone,
        string $paymentMethod,     // 'COD' | 'VNPAY' | 'MOMO' | 'BANK'
        string $paymentStatus,     // 'unpaid' | 'paid' | 'failed' | 'refunded'
        string $status,            // ví dụ: 'pending' | 'confirmed' | 'processing' | 'completed' ...
        string $shippingMethod,
        int|float $shippingFee,
        Carbon $placedAt,
        array $items
    ): void {
        $subtotal = 0;

        foreach ($items as $it) {
            $subtotal += ($it['unit_price'] * $it['qty']);
        }

        $discount  = 0;
        $tax       = 0;
        $grand     = $subtotal + $shippingFee + $tax - $discount;

        $orderId = DB::table('orders')->insertGetId([
            'user_id'         => null,
            'code'            => $code,
            'status'          => $status,
            'payment_status'  => $paymentStatus,
            'payment_method'  => $paymentMethod,
            'customer_name'   => $customerName,
            'customer_email'  => $customerEmail,
            'customer_phone'  => $customerPhone,
            'shipping_address' => json_encode([
                'address'  => '123 Đường Demo',
                'ward'     => 'Phường A',
                'district' => 'Quận B',
                'province' => 'Hà Nội',
            ], JSON_UNESCAPED_UNICODE),
            'shipping_method' => $shippingMethod,
            'tracking_no'     => null,
            'tags'            => null,
            'notes'           => null,
            'subtotal'        => $subtotal,
            'discount_total'  => $discount,
            'shipping_fee'    => $shippingFee,
            'tax_total'       => $tax,
            'grand_total'     => $grand,
            'placed_at'       => $placedAt,
            'created_at'      => $placedAt,
            'updated_at'      => Carbon::now(),
        ]);

        foreach ($items as $it) {
            $lineTotal = $it['unit_price'] * $it['qty'];
            DB::table('order_items')->insert([
                'order_id'               => $orderId,
                'product_id'             => $it['product']->id,
                'product_variant_id'     => null,
                'product_name_snapshot'  => $it['product']->name,
                'variant_name_snapshot'  => null,
                'unit_price'             => $it['unit_price'],
                'qty'                    => $it['qty'],
                'line_total'             => $lineTotal,
                'created_at'             => $placedAt,
                'updated_at'             => Carbon::now(),
            ]);
        }
    }
}
