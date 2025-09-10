<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Order, OrderItem, Product, ProductVariant};
use Illuminate\Support\Str;
use Carbon\Carbon;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::with('variants')->get();
        if ($products->isEmpty()) return;

        // Tạo 40 đơn trong 30 ngày gần đây
        for ($i = 0; $i < 40; $i++) {
            $date = Carbon::now()->subDays(rand(0, 29))->setTime(rand(9, 21), rand(0, 59));
            $status = collect(['pending', 'confirmed', 'processing', 'shipping', 'completed'])->random();
            $paid   = in_array($status, ['processing', 'shipping', 'completed']);
            $order = Order::create([
                'code' => 'DH' . Str::upper(Str::random(8)),
                'status' => $status,
                'payment_status' => $paid ? 'paid' : 'unpaid',
                'payment_method' => collect(['COD', 'MOMO', 'VNPAY'])->random(),
                'customer_name' => 'Khách ' . $i,
                'customer_phone' => '09' . rand(10000000, 99999999),
                'shipping_address' => ['line1' => 'Số ' . rand(1, 200) . ' Đường ABC', 'district' => 'Q.1', 'city' => 'HCM'],
                'subtotal' => 0,
                'discount_total' => 0,
                'shipping_fee' => 15000,
                'tax_total' => 0,
                'grand_total' => 0,
                'placed_at' => $date,
                'created_at' => $date,
                'updated_at' => $date,
            ]);

            $itemsCount = rand(1, 3);
            $subtotal = 0;
            for ($j = 0; $j < $itemsCount; $j++) {
                $p = $products->random();
                $v = $p->variants->random();
                $qty = rand(1, 3);
                $line = $v->price * $qty;
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $p->id,
                    'product_variant_id' => $v->id,
                    'product_name_snapshot' => $p->name,
                    'variant_name_snapshot' => $v->name,
                    'unit_price' => $v->price,
                    'qty' => $qty,
                    'line_total' => $line,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
                $subtotal += $line;
            }
            $grand = $subtotal + $order->shipping_fee;
            $order->update(['subtotal' => $subtotal, 'grand_total' => $grand]);
        }
    }
}
