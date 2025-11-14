<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CouponService;
use App\Services\Payments\PaymentService;
use App\Services\WalletService;
use App\Models\Wallet;
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
        abort_if(($cart['subtotal'] ?? 0) <= 0, 40);

        $addresses      = collect();
        $selected       = null;
        $profile        = ['name' => null, 'email' => null, 'phone' => null];
        $walletBalance  = 0; // ✅ luôn có giá trị mặc định

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

                $shipApplied = (array) session('applied_ship', []);
                if (!empty($cart['shipping_fee']) && !empty($shipApplied['discount'])) {
                    $cart['ship_discount'] = min((int)$cart['shipping_fee'], (int)$shipApplied['discount']);
                    $cart['shipping_fee']  = max(0, (int)$cart['shipping_fee'] - (int)$cart['ship_discount']);
                }
            }

            // ✅ Lấy số dư ví KHÔNG phụ thuộc địa chỉ
            $walletBalance = (int) optional(
                Wallet::firstOrCreate(['user_id' => $u->id], ['balance' => 0])
            )->balance;
        }

        return view('checkout.index', compact(
            'cart',
            'addresses',
            'selected',
            'profile',
            'walletBalance'
        ));
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
            // cho phép WALLET (trả toàn bộ bằng ví)
            'payment_method' => 'required|in:COD,VIETQR,MOMO,VNPAY,WALLET',
            'note'           => 'nullable|string',
        ]);

        // Tham số ví từ FE
        $walletUse = $r->boolean('wallet_use');
        $walletAmt = max(0, (int) $r->input('wallet_amount', 0));

        $applied = (array) session('applied_coupon', []);
        $keys    = (array) ($applied['keys'] ?? null);

        $cart = $this->buildCart($keys);
        if (($cart['subtotal'] ?? 0) <= 0) {
            return response()->json(['ok' => false, 'message' => 'Giỏ hàng trống'], 422);
        }

        // ===== Recompute coupon
        $discount  = 0;
        $coupon    = null;
        $appliedOk = false;

        if (!empty($applied['coupon_id'])) {
            $coupon = \App\Models\Coupon::find((int) $applied['coupon_id']);
            if ($coupon) {
                $res = $couponSvc->compute($cart, $coupon);
                if (($res['ok'] ?? false) && (int)($res['discount'] ?? 0) > 0) {
                    $discount  = (int) $res['discount'];
                    $appliedOk = true;
                } else {
                    session()->forget('applied_coupon');
                    $coupon = null;
                }
            }
        }

        $couponCode = null;
        $couponId   = null;
        if ($appliedOk && !empty($applied['code'])) {
            $couponCode = strtoupper(trim((string) $applied['code']));
            $couponId   = $coupon?->id ?: (int) ($applied['coupon_id'] ?? 0) ?: null;
        }

        // ===== Shipping fee & ship voucher
        $subtotal    = (int) ($cart['subtotal'] ?? 0);
        $shippingFee = (int) ($cart['shipping_fee'] ?? (int) session('cart.shipping_fee', 0));

        $shipDisc        = 0;
        $shipApplied     = (array) session('applied_ship', []);
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
                        'applied_ship',
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

        // ===== Tạo đơn
        /** @var Order $order */
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
                'user_id'          => auth()->id(),
                'code'             => $this->genCode(),
                'status'           => 'pending',
                'payment_status'   => 'unpaid',
                'payment_method'   => $method,
                'customer_name'    => (string) $r->input('name'),
                'customer_phone'   => (string) $r->input('phone'),
                'customer_email'   => $r->input('email'),
                'shipping_address' => [
                    'line1'    => $r->input('address'),
                    'district' => $r->input('district'),
                    'city'     => $r->input('city'),
                ],
                'subtotal'        => (float) $subtotal,
                'discount_total'  => (float) $discount,
                'shipping_fee'    => (float) $shippingFee,
                'tax_total'       => 0.00,
                'grand_total'     => (float) $grand,   // tổng trước khi trừ ví
                'notes'           => $r->input('note'),
                'placed_at'       => now(),
            ];

            if ($couponCode) {
                if (Schema::hasColumn('orders', 'coupon_code')) $orderData['coupon_code'] = $couponCode;
                if (Schema::hasColumn('orders', 'coupon_id'))   $orderData['coupon_id']   = $couponId;
            }
            if ($shipAppliedOk && $shipVoucherCode) {
                if (Schema::hasColumn('orders', 'shipping_voucher_code')) $orderData['shipping_voucher_code'] = $shipVoucherCode;
                if (Schema::hasColumn('orders', 'shipping_voucher_id'))   $orderData['shipping_voucher_id']   = $shipVoucherId;
            }

            if (Schema::hasColumn('orders', 'meta')) {
                $meta = [
                    'coupon_codes' => $couponCode ? [$couponCode] : [],
                    'applied_keys' => array_values((array)($applied['keys'] ?? [])),
                ];
                if ($shipAppliedOk && $shipVoucherCode) {
                    $meta['shipping_voucher_code'] = $shipVoucherCode;
                }
                if (!empty($shipApplied)) {
                    $meta['shipping_voucher'] = $shipApplied;
                }
                $orderData['meta'] = $meta;
            }

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

                if ($appliedOk && $couponId && Schema::hasTable('coupon_usages')) {
                    DB::table('coupon_usages')->insert([
                        'user_id'    => auth()->id(),
                        'order_id'   => $order->id,
                        'coupon_id'  => $couponId,
                        'code'       => $couponCode,
                        'discount'   => (int) $discount,
                        'used_at'    => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            if ($shipAppliedOk && Schema::hasTable('shipping_voucher_usages')) {
                DB::table('shipping_voucher_usages')->insert([
                    'user_id'             => auth()->id(),
                    'order_id'            => $order->id,
                    'shipping_voucher_id' => $shipVoucherId,
                    'code'                => $shipVoucherCode,
                    'order_code'          => $order->code,
                    'discount'            => (int) $shipDisc,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
            }

            return $order;
        });

        // ===== Áp dụng VÍ COSME cho đơn vừa tạo (idempotent, gắn tham chiếu order)
        $payable = (int) $order->grand_total; // tổng trước khi trừ ví

        if (auth()->check() && $walletUse && $walletAmt > 0) {
            $wallet = Wallet::firstOrCreate(['user_id' => auth()->id()], ['balance' => 0]);

            // clamp số dùng ví theo số dư và số phải trả
            $use = max(0, min($walletAmt, (int) $wallet->balance, $payable));

            if ($use > 0) {
                $tx = WalletService::debitForOrder($wallet, $use, $order->id);
                $payable -= $use;

                // lưu lại wallet_used + original_grand_total vào meta (nếu có cột meta)
                $updates = ['grand_total' => $payable];
                if (Schema::hasColumn('orders', 'meta')) {
                    $meta = (array) ($order->meta ?? []);
                    $meta['original_grand_total'] = $meta['original_grand_total'] ?? (int) $order->getOriginal('grand_total');
                    $meta['wallet_used']          = (int) (($meta['wallet_used'] ?? 0) + $use);
                    $meta['wallet_tx_id']         = $tx?->id ?? null;
                    $updates['meta']              = $meta;
                }
                $order->update($updates);
                $order->refresh();
            }
        }

        // Nếu chọn WALLET mà sau trừ ví vẫn còn phải trả -> báo lỗi
        if ($method === 'WALLET' && (int) $order->grand_total > 0) {
            return response()->json([
                'ok'      => false,
                'message' => 'Số dư ví không đủ để thanh toán toàn bộ. Vui lòng chọn phương thức khác.',
            ], 422);
        }

        // Nếu sau khi trừ ví còn 0 => chốt đơn thanh toán bằng ví
        if ((int) $order->grand_total === 0) {
            $order->update([
                'payment_method' => 'WALLET',
                'payment_status' => 'paid',
                'status'         => 'processing', // tuỳ flow của bạn
            ]);

            // dọn giỏ
            session()->forget([
                'cart',
                'cart.items',
                'cart.shipping_fee',
                'applied_coupon',
                'applied_ship_voucher',
                'applied_shipping_voucher',
                'applied_ship',
            ]);
            session()->save();

            return response()->json([
                'ok'           => true,
                'order_id'     => $order->id,
                'order_code'   => $order->code,
                'method'       => 'WALLET',
                'redirect_url' => route('account.orders.show', $order->id),
            ]);
        }

        // ===== Còn phải trả > 0 → gọi cổng cho phần còn lại (order->grand_total đã là số còn phải trả)
        if ($method === 'VIETQR') {
            return response()->json([
                'ok'           => true,
                'order_id'     => $order->id,
                'order_code'   => $order->code,
                'method'       => 'VIETQR',
                'redirect_url' => route('checkout.vietqr.payos', $order->id),
            ]);
        }

        $result = $paySvc->initiate($method, $order);
        if (!($result['ok'] ?? false)) {
            return response()->json([
                'ok'      => false,
                'message' => $result['message'] ?? 'Không khởi tạo thanh toán được.',
            ], 422);
        }

        if ($method === 'COD') {
            session()->forget([
                'cart',
                'cart.items',
                'cart.shipping_fee',
                'applied_coupon',
                'applied_ship_voucher',
                'applied_shipping_voucher',
                'applied_ship',
            ]);
            session()->save();
        }

        $redirect = $result['redirect_url']
            ?? ($method === 'VNPAY' || $method === 'MOMO'
                ? route('account.orders.index')
                : route('account.orders.show', $order->id));

        $payload = collect($result)->except(['ok'])->all();
        $payload['redirect_url'] = $redirect;
        $payload['method']       = $method;

        return response()->json([
            'ok'         => true,
            'order_id'   => $order->id,
            'order_code' => $order->code,
        ] + $payload);
    }


    /**
     * (Tuỳ chọn) Cho chatbot gọi mini-checkout trực tiếp.
     */
    public static function placeOrder(array $payload): array
    {
        if (!session()->has('cart.items')) {
            return ['ok' => false, 'message' => 'Giỏ hàng trống'];
        }

        $req = \Illuminate\Http\Request::create('/checkout', 'POST', [
            'name'           => $payload['name'] ?? '',
            'phone'          => $payload['phone'] ?? '',
            'email'          => $payload['email'] ?? null,
            'address'        => $payload['address'] ?? '',
            'district'       => null,
            'city'           => $payload['city'] ?? '',
            'payment_method' => $payload['payment_method'] ?? 'COD',
            'note'           => $payload['note'] ?? null,
        ]);

        $resp = app(self::class)->place(
            $req,
            app(\App\Services\CouponService::class),
            app(\App\Services\Payments\PaymentService::class)
        );

        return $resp->getData(true);
    }

    private function genCode(): string
    {
        return 'CH-' . now()->format('ymd') . '-' . strtoupper(Str::random(5));
    }
}
