<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Carbon\Carbon;

class OneOrderSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Lấy 1-2 sản phẩm có sẵn; nếu không có thì tạo nhanh kèm 1 category
            $products = DB::table('products')->select('id', 'name')->inRandomOrder()->limit(2)->get();

            if ($products->count() === 0) {
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
                $pid = DB::table('products')->insertGetId([
                    'category_id'  => $catId,
                    'brand_id'     => null,
                    'name'         => 'SP Demo 1',
                    'slug'         => Str::slug('sp demo 1') . '-' . Str::random(4),
                    'thumbnail'    => null,
                    'image'        => null,
                    'is_active'    => 1,
                    'has_variants' => 0,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
                $products = collect([(object)['id' => $pid, 'name' => 'SP Demo 1']]);
            }

            $pick = fn() => $products[random_int(0, $products->count() - 1)];

            // ===== Tạo 1 đơn mẫu: COD / paid / completed =====
            $placedAt = Carbon::now()->subMinutes(10);

            $items = [
                ['product' => $pick(), 'unit_price' => 199000, 'qty' => 1],
            ];
            $shippingFee = 25000;

            // Tính tiền
            $subtotal = array_reduce($items, fn($c, $it) => $c + $it['unit_price'] * $it['qty'], 0);
            $discount = 0;
            $tax = 0;
            $grand = $subtotal + $shippingFee + $tax - $discount;

            // Build order chỉ với cột tồn tại
            $order = [];
            $set = function (string $col, $val) use (&$order) {
                if (Schema::hasColumn('orders', $col)) $order[$col] = $val;
            };

            $set('user_id',          null);
            $set('code',             '#DH' . Str::upper(Str::random(6)));
            $set('status',           Schema::hasColumn('orders', 'status') ? 'completed' : null);
            $set('payment_status',   'paid');                   // unpaid|paid|failed|refunded
            $set('payment_method',   'COD');                    // COD|VNPAY|MOMO|BANK...
            $set('customer_name',    'Khách Test');
            $set('customer_email',   'test@example.com');
            $set('customer_phone',   '0900000000');
            $set('shipping_address', json_encode([
                'address' => '123 Đường Demo',
                'ward' => 'Phường A',
                'district' => 'Quận B',
                'province' => 'Hà Nội'
            ], JSON_UNESCAPED_UNICODE));
            $set('shipping_method',  'GHTK');
            $set('tracking_no',      null);
            $set('tags',             null);
            $set('notes',            'Đơn hàng seed để test dashboard');
            $set('subtotal',         $subtotal);
            $set('discount_total',   $discount);
            $set('shipping_fee',     $shippingFee);
            $set('tax_total',        $tax);
            $set('grand_total',      $grand);
            $set('placed_at',        $placedAt);
            $set('created_at',       $placedAt);
            $set('updated_at',       Carbon::now());

            $orderId = DB::table('orders')->insertGetId($order);

            // Items
            foreach ($items as $it) {
                $lineTotal = $it['unit_price'] * $it['qty'];
                $data = [];
                $iset = function (string $col, $val) use (&$data) {
                    if (Schema::hasColumn('order_items', $col)) $data[$col] = $val;
                };
                $iset('order_id',              $orderId);
                $iset('product_id',            $it['product']->id);
                $iset('product_variant_id',    null);
                $iset('product_name_snapshot', $it['product']->name);
                $iset('variant_name_snapshot', null);
                $iset('unit_price',            $it['unit_price']);
                $iset('qty',                   $it['qty']);
                $iset('line_total',            $lineTotal);
                $iset('created_at',            $placedAt);
                $iset('updated_at',            Carbon::now());

                DB::table('order_items')->insert($data);
            }
        });
    }
}
