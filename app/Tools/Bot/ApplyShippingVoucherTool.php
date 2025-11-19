<?php

namespace App\Tools\Bot;

use App\Http\Controllers\ShippingVoucherController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * ApplyShippingVoucherTool - Áp mã vận chuyển
 */
class ApplyShippingVoucherTool
{
    public function execute(string $message, array $context): ?array
    {
        if (!auth()->check()) {
            return [
                'success' => false,
                'requires_auth' => true,
                'message' => 'Bạn cần đăng nhập để áp mã vận chuyển.',
            ];
        }

        // Extract voucher code từ message
        $code = $this->extractVoucherCode($message, $context);

        if (!$code) {
            return [
                'success' => false,
                'message' => 'Mình không tìm thấy mã vận chuyển trong tin nhắn. Bạn có thể nói lại mã không?',
            ];
        }

        try {
            // ✅ Lấy subtotal và shipping_fee từ session
            // ✅ Cart items trong session chỉ có product_id, variant_id, qty - KHÔNG có price
            // Cần query database để lấy giá (giống như GetShippingVouchersTool)
            $items = session('cart.items', []);
            $subtotal = 0;
            if (!empty($items)) {
                $pids = collect($items)->pluck('product_id')->unique()->values()->all();
                $products = \App\Models\Product::whereIn('id', $pids)
                    ->with(['variants' => function ($q) {
                        $q->select('id', 'product_id', 'price');
                    }])
                    ->get()
                    ->keyBy('id');

                foreach ($items as $it) {
                    $product = $products->get((int)($it['product_id'] ?? 0));
                    if (!$product) continue;

                    $variantId = $it['variant_id'] ?? null;
                    $qty = (int)($it['qty'] ?? 1);

                    if ($variantId) {
                        $variant = $product->variants->firstWhere('id', $variantId);
                        $price = $variant ? (int)$variant->price : (int)($product->variants->min('price') ?? $product->price ?? 0);
                    } else {
                        $price = (int)($product->variants->min('price') ?? $product->price ?? 0);
                    }

                    $subtotal += $price * $qty;
                }
            }

            $shippingFee = (int)session('cart.shipping_fee', 0);

            // ✅ Log để debug
            Log::info('ApplyShippingVoucherTool: Calculated subtotal', [
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'items_count' => count($items),
                'code' => $code,
            ]);

            // Gọi ShippingVoucherController::apply
            $request = \Illuminate\Http\Request::create('/shipping-voucher/apply', 'POST', [
                'code' => $code,
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
            ]);
            $request->setUserResolver(fn() => auth()->user());

            $controller = app(ShippingVoucherController::class);
            $response = $controller->apply($request);
            $result = $response->getData(true);

            if (!($result['ok'] ?? false)) {
                return [
                    'success' => false,
                    'message' => $result['message'] ?? 'Không thể áp dụng mã vận chuyển này.',
                ];
            }

            return [
                'success' => true,
                'code' => strtoupper(trim($code)),
                'discount' => (int)($result['discount'] ?? 0),
                'after_fee' => (int)($result['after_fee'] ?? 0),
                'title' => $result['title'] ?? '',
                'message' => "Đã áp dụng mã vận chuyển **{$code}** thành công! Giảm " . number_format($result['discount'] ?? 0, 0, ',', '.') . '₫ phí ship.',
            ];
        } catch (\Throwable $e) {
            Log::error('ApplyShippingVoucherTool failed', [
                'error' => $e->getMessage(),
                'code' => $code,
            ]);

            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi áp dụng mã vận chuyển. Vui lòng thử lại!',
            ];
        }
    }

    /**
     * Extract voucher code từ message hoặc context
     */
    private function extractVoucherCode(string $message, array $context): ?string
    {
        $lower = Str::lower(trim($message));

        // Check nếu user nói "không", "không có", "bỏ qua", "skip"
        if (preg_match('/\b(không|không có|bỏ qua|skip|không cần|thôi)\b/u', $lower)) {
            return null; // User không muốn áp mã
        }

        // ✅ Lấy vouchers từ nhiều nguồn
        $vouchers = [];
        // 1. Từ checkout_data trong context
        if (!empty($context['checkout_data']['available_shipping_vouchers'])) {
            $vouchers = $context['checkout_data']['available_shipping_vouchers'];
        }
        // 2. Từ tools_result nếu có (khi được gọi từ ResponseGenerator)
        if (empty($vouchers) && !empty($context['tools_result']['getShippingVouchers']['vouchers'])) {
            $vouchers = $context['tools_result']['getShippingVouchers']['vouchers'];
        }
        // 3. Nếu vẫn không có, thử lấy từ session hoặc query lại
        if (empty($vouchers)) {
            try {
                $vouchersTool = app(\App\Tools\Bot\GetShippingVouchersTool::class);
                $vouchersResult = $vouchersTool->execute('', $context);
                if (!empty($vouchersResult['vouchers'])) {
                    $vouchers = $vouchersResult['vouchers'];
                }
            } catch (\Throwable $e) {
                Log::warning('ApplyShippingVoucherTool: Failed to get vouchers', ['error' => $e->getMessage()]);
            }
        }

        // ✅ Ưu tiên: Extract code từ index (số 1, số 2, thứ 1, thứ 2...)
        if (preg_match('/\b(số|thứ)\s*(\d+)\b/u', $lower, $m)) {
            $index = (int)$m[2] - 1; // Convert to 0-based
            if (!empty($vouchers) && isset($vouchers[$index])) {
                $code = $vouchers[$index]['code'] ?? null;
                if ($code) {
                    Log::info('ApplyShippingVoucherTool: Extracted code from index', [
                        'index' => $index + 1,
                        'code' => $code,
                    ]);
                    return $code;
                }
            }
        }

        // ✅ Extract code từ message (pattern: mã X, code X, áp mã X)
        if (preg_match('/\b(?:mã|code)\s+([A-Z0-9]{3,20})\b/u', Str::upper($message), $m)) {
            $code = $m[1];
            // Verify code có trong vouchers
            if (!empty($vouchers)) {
                foreach ($vouchers as $voucher) {
                    if (strtoupper($voucher['code'] ?? '') === $code) {
                        Log::info('ApplyShippingVoucherTool: Extracted code from message', ['code' => $code]);
                        return $code;
                    }
                }
            } else {
                // Nếu không có vouchers để verify, vẫn trả về code (có thể là code hợp lệ)
                Log::info('ApplyShippingVoucherTool: Extracted code from message (no vouchers to verify)', ['code' => $code]);
                return $code;
            }
        }

        // ✅ Extract code trực tiếp (nếu là code format và có trong vouchers)
        if (preg_match('/\b([A-Z0-9]{3,20})\b/u', Str::upper($message), $m)) {
            $code = $m[1];
            if (!empty($vouchers)) {
                foreach ($vouchers as $voucher) {
                    if (strtoupper($voucher['code'] ?? '') === $code) {
                        Log::info('ApplyShippingVoucherTool: Extracted code directly', ['code' => $code]);
                        return $code;
                    }
                }
            }
        }

        Log::warning('ApplyShippingVoucherTool: Could not extract voucher code', [
            'message' => $message,
            'vouchers_count' => count($vouchers),
        ]);

        return null;
    }
}

