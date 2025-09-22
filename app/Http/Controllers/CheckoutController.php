<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CouponService;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    use \App\Http\Controllers\Traits\BuildsCart;

    public function show()
    {
        $cart = $this->buildCart();
        abort_if(($cart['subtotal'] ?? 0) <= 0, 404);

        $addresses = collect();
        $selected  = null;
        $profile   = ['name' => null, 'email' => null, 'phone' => null];

        if (auth()->check()) {
            $u = auth()->user();
            $profile = ['name' => $u->name, 'email' => $u->email, 'phone' => $u->phone];

            $addresses = \App\Models\UserAddress::query()
                ->where('user_id', $u->id)
                ->orderByDesc('is_default_shipping')
                ->orderByDesc('id')
                ->get();

            $selected = $addresses->first();

            if ($selected) {
                $q = \App\Services\Shipping\DistanceEstimator::estimateFee(
                    $selected->lat,
                    $selected->lng,
                    (int)($cart['subtotal'] ?? 0)
                );
                $cart['shipping_fee'] = (int) ($q['fee'] ?? 0);
                session(['cart.shipping_fee' => (int) ($q['fee'] ?? 0)]);
                // Nếu có áp mã vận chuyển trong session → trừ vào shipping_fee
                $shipApplied = (array) session('applied_ship', []);
                if (!empty($cart['shipping_fee']) && !empty($shipApplied['discount'])) {
                    $cart['ship_discount'] = min((int)$cart['shipping_fee'], (int)$shipApplied['discount']);
                    $cart['shipping_fee']  = max(0, (int)$cart['shipping_fee'] - (int)$cart['ship_discount']);
                }
            }
        }

        return view('checkout.index', compact('cart', 'addresses', 'selected', 'profile'));
    }

    public function place(Request $r, CouponService $couponSvc, PaymentService $paySvc)
    {
        $r->validate([
            'name'           => 'required|string|max:255',
            'phone'          => 'required|string|max:32',
            'email'          => 'nullable|email',
            'address'        => 'required|string|max:255',
            'district'       => 'nullable|string|max:255',
            'city'           => 'required|string|max:255',
            'payment_method' => 'required|in:COD,VIETQR,MOMO,VNPAY',
            'note'           => 'nullable|string',
        ]);

        // Mã đã áp ở bước trước (được lưu trong session)
        $applied = (array) session('applied_coupon', []);
        $keys    = (array) ($applied['keys'] ?? null);

        $cart = $this->buildCart($keys);
        if (($cart['subtotal'] ?? 0) <= 0) {
            return response()->json(['ok' => false, 'message' => 'Giỏ hàng trống'], 422);
        }

        // TÁI TÍNH coupon ở BE để đảm bảo hợp lệ
        $discount = 0;
        $coupon   = null;
        $appliedOk = false;

        if (!empty($applied['coupon_id'])) {
            $coupon = \App\Models\Coupon::find((int) $applied['coupon_id']);
            if ($coupon) {
                $res = $couponSvc->compute($cart, $coupon);
                if (($res['ok'] ?? false) && (int)($res['discount'] ?? 0) > 0) {
                    $discount = (int) $res['discount'];
                    $appliedOk = true;
                } else {
                    // Không còn hợp lệ -> bỏ
                    session()->forget('applied_coupon');
                    $coupon = null;
                }
            }
        }

        // Chuẩn hoá coupon để ghi vào orders (CHỈ khi áp thành công)
        $couponCode = null;
        $couponId   = null;
        if ($appliedOk && !empty($applied['code'])) {
            $couponCode = strtoupper(trim((string) $applied['code']));
            $couponId   = $coupon?->id ?: (int) ($applied['coupon_id'] ?? 0) ?: null;
        }

        // Tính phí ship từ session và áp ship voucher (nếu có)
        $subtotal    = (int) ($cart['subtotal'] ?? 0);
        $shippingFee = (int) ($cart['shipping_fee'] ?? (int) session('cart.shipping_fee', 0));

        $shipDisc        = 0;
        $shipApplied     = (array) session('applied_ship', []); // snapshot lưu trong session khi áp mã ship
        $shipAppliedOk   = false;
        $shipVoucherCode = null;
        $shipVoucherId   = null;

        if (!empty($shipApplied)) {
            $shipVoucher = DB::table('shipping_vouchers')
                ->when(!empty($shipApplied['shipping_voucher_id']), fn($q) => $q->where('id', (int)$shipApplied['shipping_voucher_id']))
                ->when(
                    empty($shipApplied['shipping_voucher_id']) && !empty($shipApplied['code']),
                    fn($q) => $q->where('code', strtoupper(trim((string)$shipApplied['code'])))
                )
                ->first();

            if ($shipVoucher) {
                $nowOk  = (empty($shipVoucher->start_at) || now()->gte($shipVoucher->start_at))
                    && (empty($shipVoucher->end_at)   || now()->lte($shipVoucher->end_at));
                $active = (int)($shipVoucher->is_active ?? 1) === 1;
                $minReq = (int)($shipVoucher->min_subtotal ?? $shipVoucher->min_order ?? 0);
                $minOk  = $subtotal >= $minReq;

                $ownedOk = true;
                if (Schema::hasTable('user_shipping_vouchers')) {
                    $ownedOk = DB::table('user_shipping_vouchers')
                        ->where('user_id', auth()->id())
                        ->where('shipping_voucher_id', $shipVoucher->id)
                        ->when(!empty($shipApplied['code']), fn($q) => $q->where('code', strtoupper(trim((string)$shipApplied['code']))))
                        ->exists();

                    if (!$ownedOk && Schema::hasColumn('shipping_vouchers', 'is_public')) {
                        $ownedOk = (int)($shipVoucher->is_public ?? 0) === 1;
                    }
                }

                if ($nowOk && $active && $minOk && $ownedOk) {
                    $type = ($shipVoucher->discount_type ?? 'fixed');
                    $amt  = (int) ($shipVoucher->amount ?? 0);
                    if ($type === 'percent') {
                        $shipDisc = (int) floor($shippingFee * $amt / 100);
                        $maxCap   = (int) ($shipVoucher->max_discount ?? 0);
                        if ($maxCap > 0) $shipDisc = min($shipDisc, $maxCap);
                    } else {
                        $shipDisc = $amt;
                    }
                    $shipDisc        = max(0, min($shipDisc, $shippingFee));
                    $shipAppliedOk   = $shipDisc > 0;
                    $shipVoucherCode = strtoupper(trim((string)($shipApplied['code'] ?? $shipVoucher->code)));
                    $shipVoucherId   = (int) $shipVoucher->id;
                } else {
                    session()->forget([
                        'cart',
                        'cart.items',
                        'cart.shipping_fee',
                        'applied_coupon',
                        'applied_ship_voucher',
                        'applied_shipping_voucher',
                        'applied_ship'
                    ]);
                    session()->save();
                }
            }
        }

        if ($shipAppliedOk) {
            $shippingFee = max(0, $shippingFee - $shipDisc);
        }

        $grand  = max(0, $subtotal - $discount + $shippingFee);
        $method = strtoupper($r->input('payment_method'));

        // ========== TẠO ĐƠN ==========
        $order = DB::transaction(function () use (
            $r,
            $cart,
            $subtotal,
            $discount,
            $shippingFee,
            $grand,
            $method,
            $couponCode,
            $couponId,
            $applied,
            $appliedOk,
            $shipAppliedOk,
            $shipVoucherCode,
            $shipVoucherId,
            $shipDisc,
            $shipApplied
        ) {
            $orderData = [
                'user_id'         => auth()->id(),
                'code'            => $this->genCode(),
                'status'          => 'pending',
                'payment_status'  => 'unpaid',
                'payment_method'  => $method,
                'customer_name'   => (string) $r->input('name'),
                'customer_phone'  => (string) $r->input('phone'),
                'customer_email'  => $r->input('email'),
                'shipping_address' => [
                    'line1'    => $r->input('address'),
                    'district' => $r->input('district'),
                    'city'     => $r->input('city'),
                ],
                'subtotal'        => (float) $subtotal,
                'discount_total'  => (float) $discount,
                'shipping_fee'    => (float) $shippingFee,
                'tax_total'       => 0.00,
                'grand_total'     => (float) $grand,
                'notes'           => $r->input('note'),
                'placed_at'       => now(),
            ];

            // Ghi coupon (nếu có)
            if ($couponCode) {
                if (Schema::hasColumn('orders', 'coupon_code')) $orderData['coupon_code'] = $couponCode;
                if (Schema::hasColumn('orders', 'coupon_id'))   $orderData['coupon_id']   = $couponId;
            }
            // Ghi ship voucher (nếu có)
            if ($shipAppliedOk && $shipVoucherCode) {
                if (Schema::hasColumn('orders', 'shipping_voucher_code')) $orderData['shipping_voucher_code'] = $shipVoucherCode;
                if (Schema::hasColumn('orders', 'shipping_voucher_id'))   $orderData['shipping_voucher_id']   = $shipVoucherId;
            }

            // Meta snapshot (nếu bảng có cột meta JSON)
            if (Schema::hasColumn('orders', 'meta')) {
                $meta = [
                    'coupon_codes' => $couponCode ? [$couponCode] : [],
                    'applied_keys' => array_values((array)($applied['keys'] ?? [])),
                ];
                if ($shipAppliedOk && $shipVoucherCode) {
                    $meta['shipping_voucher_code'] = $shipVoucherCode;
                }
                if (!empty($shipApplied)) {
                    $meta['shipping_voucher'] = $shipApplied; // snapshot FE trả về lúc áp
                }
                $orderData['meta'] = $meta;
            }

            /** @var Order $order */
            $order = Order::create($orderData);

            foreach ($cart['items'] as $it) {
                $pid = (int) ($it['product_id'] ?? 0);
                if ($pid <= 0) continue;

                $p  = Product::find($pid);
                $pv = !empty($it['variant_id']) ? ProductVariant::find((int)$it['variant_id']) : null;

                OrderItem::create([
                    'order_id'              => $order->id,
                    'product_id'            => $pid,
                    'product_variant_id'    => $pv?->id,
                    'product_name_snapshot' => $p?->name ?? ('SP #' . $pid),
                    'variant_name_snapshot' => $pv?->name,
                    'unit_price'            => (float) ($it['price'] ?? 0),
                    'qty'                   => max(1, (int) ($it['qty'] ?? 1)),
                    'line_total'            => (float) ((int) ($it['price'] ?? 0) * max(1, (int) ($it['qty'] ?? 1))),
                ]);
                // Ghi nhận usage của coupon (nếu có áp và có bảng)
                if ($appliedOk && $couponId && Schema::hasTable('coupon_usages')) {
                    DB::table('coupon_usages')->insert([
                        'user_id'   => auth()->id(),
                        'order_id'  => $order->id,
                        'coupon_id' => $couponId,
                        'code'      => $couponCode,
                        'discount'  => (int) $discount,
                        'used_at'   => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Ghi usage ship voucher (chỉ khi có bảng & cột phù hợp)
            if ($shipAppliedOk && Schema::hasTable('shipping_voucher_usages')) {
                DB::table('shipping_voucher_usages')->insert([
                    'user_id'             => auth()->id(),
                    'order_id'            => $order->id,
                    'shipping_voucher_id' => $shipVoucherId,
                    'code'                => $shipVoucherCode,       // cột code (đã có)
                    'order_code'          => $order->code,           // cột order_code (đã có)
                    'discount'            => (int) $shipDisc,        // mức giảm phí ship
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
            }

            return $order;
        });


        // GỌI cổng thanh toán
        $result = $paySvc->initiate($method, $order);
        if (!($result['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'message' => $result['message'] ?? 'Không khởi tạo thanh toán được.',
            ], 422);
        }

        // COD → dọn giỏ và mã đã áp
        if ($method === 'COD') {
            session()->forget(['cart', 'cart.items', 'cart.shipping_fee', 'applied_coupon', 'applied_ship_voucher', 'applied_shipping_voucher', 'applied_ship']); // 🆕 clear thêm ship
            session()->save();
        }

        // Fallback redirect
        $redirect = $result['redirect_url']
            ?? ($method === 'VIETQR'
                ? route('payment.vietqr.show', $order)
                : route('account.orders.show', $order));

        $payload = collect($result)->except(['ok'])->all();
        $payload['redirect_url'] = $redirect;
        $payload['method']       = $method;

        return response()->json([
            'ok'         => true,
            'order_id'   => $order->id,
            'order_code' => $order->code,
        ] + $payload);
    }


    private function genCode(): string
    {
        return 'CH-' . now()->format('ymd') . '-' . strtoupper(Str::random(5));
    }
}
