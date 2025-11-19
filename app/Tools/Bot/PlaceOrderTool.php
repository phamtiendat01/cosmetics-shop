<?php

namespace App\Tools\Bot;

use App\Http\Controllers\CheckoutController;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * PlaceOrderTool - Đặt hàng (tạo order)
 */
class PlaceOrderTool
{
    public function execute(string $message, array $context): ?array
    {
        if (!auth()->check()) {
            return [
                'success' => false,
                'requires_auth' => true,
                'message' => 'Bạn cần đăng nhập để đặt hàng.',
            ];
        }

        // Extract thông tin từ context hoặc message
        $checkoutData = $context['checkout_data'] ?? [];
        $address = $this->getAddress($checkoutData);
        $paymentMethod = $this->extractPaymentMethod($message, $context);

        if (!$address) {
            return [
                'success' => false,
                'message' => 'Mình cần địa chỉ giao hàng để đặt hàng. Bạn có thể cho mình biết địa chỉ không?',
            ];
        }

        if (!$paymentMethod) {
            return [
                'success' => false,
                'message' => 'Bạn chưa chọn phương thức thanh toán. Bạn muốn thanh toán bằng cách nào?',
            ];
        }

        try {
            // Build request data
            $requestData = [
                'name' => $address['name'],
                'phone' => $address['phone'],
                'email' => auth()->user()->email,
                'address' => $address['line1'],
                'district' => $address['district'],
                'city' => $address['province'],
                'payment_method' => $paymentMethod,
                'note' => 'Đặt hàng qua chatbot',
            ];

            // Nếu chọn WALLET, check số dư
            if ($paymentMethod === 'WALLET') {
                $wallet = \App\Models\Wallet::firstOrCreate(
                    ['user_id' => auth()->id()],
                    ['balance' => 0]
                );
                
                // Lấy tổng tiền cần thanh toán từ session
                $items = session('cart.items', []);
                $subtotal = 0;
                foreach ($items as $it) {
                    // Cần lấy price từ product/variant thực tế
                    $product = \App\Models\Product::find($it['product_id'] ?? 0);
                    if ($product) {
                        $variantId = $it['variant_id'] ?? null;
                        if ($variantId) {
                            $variant = \App\Models\ProductVariant::find($variantId);
                            $price = $variant ? (int)$variant->price : (int)($product->variants->min('price') ?? $product->price ?? 0);
                        } else {
                            $price = (int)($product->variants->min('price') ?? $product->price ?? 0);
                        }
                        $subtotal += $price * (int)($it['qty'] ?? 1);
                    }
                }
                $appliedCoupon = session('applied_coupon', []);
                $discount = (int)($appliedCoupon['discount'] ?? 0);
                $shippingFee = (int)(session('cart.shipping_fee', 0));
                $appliedShip = session('applied_ship', []);
                $shipDiscount = (int)($appliedShip['discount'] ?? 0);
                $grandTotal = max(0, $subtotal - $discount + $shippingFee - $shipDiscount);

                if ($wallet->balance < $grandTotal) {
                    return [
                        'success' => false,
                        'message' => "Số dư ví Cosme của bạn không đủ. Số dư hiện tại: " . number_format($wallet->balance, 0, ',', '.') . '₫. Tổng đơn: ' . number_format($grandTotal, 0, ',', '.') . '₫',
                    ];
                }

                $requestData['wallet_use'] = true;
                $requestData['wallet_amount'] = $grandTotal;
            }

            // Tạo request
            $request = \Illuminate\Http\Request::create('/checkout/place', 'POST', $requestData);
            $request->setUserResolver(fn() => auth()->user());

            // Gọi CheckoutController::place
            $controller = app(CheckoutController::class);
            $couponService = app(\App\Services\CouponService::class);
            $paymentService = app(\App\Services\Payments\PaymentService::class);
            
            $response = $controller->place($request, $couponService, $paymentService);
            $result = $response->getData(true);

            Log::info('PlaceOrderTool: CheckoutController response', [
                'ok' => $result['ok'] ?? false,
                'order_code' => $result['order_code'] ?? null,
                'order_id' => $result['order_id'] ?? null,
                'redirect_url' => $result['redirect_url'] ?? null,
                'method' => $result['method'] ?? null,
                'payment_method' => $paymentMethod,
            ]);

            if (!($result['ok'] ?? false)) {
                return [
                    'success' => false,
                    'message' => $result['message'] ?? 'Không thể đặt hàng. Vui lòng thử lại!',
                ];
            }

            $orderCode = $result['order_code'] ?? null;
            $orderId = $result['order_id'] ?? null;
            $redirectUrl = $result['redirect_url'] ?? null;
            
            Log::info('PlaceOrderTool: Extracted data', [
                'order_code' => $orderCode,
                'order_id' => $orderId,
                'redirect_url' => $redirectUrl,
                'payment_method' => $paymentMethod,
            ]);

            // Clear checkout state
            if (!empty($context['conversation_id'])) {
                $conversation = \App\Models\BotConversation::find($context['conversation_id']);
                if ($conversation) {
                    $stateManager = app(\App\Services\Bot\CheckoutStateManager::class);
                    $stateManager->reset($conversation);
                }
            }

            // Clear cart
            session()->forget('cart.items');
            session()->forget('applied_coupon');
            session()->forget('applied_ship');
            session()->save();

            // Build message với redirect URL nếu có
            $message = "🎉 **Đặt hàng thành công!**\n\n" .
                "Mã đơn hàng: **{$orderCode}**\n" .
                "Phương thức thanh toán: **{$this->formatPaymentMethod($paymentMethod)}**\n\n";

            // Nếu có redirect_url (VietQR, MoMo, VNPay) → thêm link thanh toán
            if ($redirectUrl && in_array($paymentMethod, ['VIETQR', 'MOMO', 'VNPAY'])) {
                $message .= "👉 **Vui lòng thanh toán tại đây:**\n" .
                    "🔗 {$redirectUrl}\n\n" .
                    "Sau khi thanh toán thành công, đơn hàng của bạn sẽ được xử lý ngay.\n\n";
            }

            $message .= "Cảm ơn bạn đã mua sắm tại Cosme House! Đơn hàng của bạn đang được xử lý. " .
                "Bạn sẽ nhận được thông báo khi đơn hàng được xác nhận và giao đi.\n\n" .
                "Chúc bạn một ngày tốt lành! 😊";

            return [
                'success' => true,
                'order_code' => $orderCode,
                'order_id' => $orderId,
                'payment_method' => $paymentMethod,
                'redirect_url' => $redirectUrl,
                'message' => $message,
            ];
        } catch (\Throwable $e) {
            Log::error('PlaceOrderTool failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi đặt hàng. Vui lòng thử lại hoặc liên hệ bộ phận hỗ trợ!',
            ];
        }
    }

    /**
     * Get address từ checkout data
     */
    private function getAddress(array $checkoutData): ?array
    {
        if (!empty($checkoutData['selected_address_id'])) {
            $address = \App\Models\UserAddress::find($checkoutData['selected_address_id']);
            if ($address && $address->user_id === auth()->id()) {
                return [
                    'id' => $address->id,
                    'name' => $address->name,
                    'phone' => $address->phone,
                    'line1' => $address->line1,
                    'line2' => $address->line2,
                    'ward' => $address->ward,
                    'district' => $address->district,
                    'province' => $address->province,
                ];
            }
        }

        // Lấy địa chỉ mặc định
        $defaultAddress = \App\Models\UserAddress::where('user_id', auth()->id())
            ->where('is_default_shipping', true)
            ->first();

        if ($defaultAddress) {
            return [
                'id' => $defaultAddress->id,
                'name' => $defaultAddress->name,
                'phone' => $defaultAddress->phone,
                'line1' => $defaultAddress->line1,
                'line2' => $defaultAddress->line2,
                'ward' => $defaultAddress->ward,
                'district' => $defaultAddress->district,
                'province' => $defaultAddress->province,
            ];
        }

        return null;
    }

    /**
     * Extract payment method từ message
     */
    private function extractPaymentMethod(string $message, array $context): ?string
    {
        $lower = Str::lower(trim($message));

        // Check các phương thức thanh toán
        if (preg_match('/\b(cod|thanh toán khi nhận|nhận hàng)\b/u', $lower)) {
            return 'COD';
        }
        if (preg_match('/\b(vietqr|qr|chuyển khoản)\b/u', $lower)) {
            return 'VIETQR';
        }
        if (preg_match('/\b(momo|momo wallet)\b/u', $lower)) {
            return 'MOMO';
        }
        if (preg_match('/\b(vnpay|vn pay)\b/u', $lower)) {
            return 'VNPAY';
        }
        if (preg_match('/\b(ví cosme|wallet|cosme wallet|ví)\b/u', $lower)) {
            return 'WALLET';
        }

        // Check nếu có trong context
        if (!empty($context['checkout_data']['selected_payment_method'])) {
            return $context['checkout_data']['selected_payment_method'];
        }

        // Check nếu user chọn theo index
        if (preg_match('/\b(số|thứ|phương thức)\s+(\d+)\b/u', $lower, $m)) {
            $index = (int)$m[2] - 1;
            $methods = $context['checkout_data']['available_payment_methods'] ?? [];
            if (isset($methods[$index])) {
                return $methods[$index]['code'] ?? null;
            }
        }

        return null;
    }

    /**
     * Format payment method name
     */
    private function formatPaymentMethod(string $method): string
    {
        $map = [
            'COD' => 'COD (Thanh toán khi nhận hàng)',
            'VIETQR' => 'Chuyển khoản VietQR',
            'MOMO' => 'MoMo',
            'VNPAY' => 'VNPay',
            'WALLET' => 'Ví Cosme',
        ];

        return $map[$method] ?? $method;
    }
}

