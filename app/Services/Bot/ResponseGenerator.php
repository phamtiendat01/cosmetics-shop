<?php

namespace App\Services\Bot;

use App\Services\Bot\RAGService;
use App\Services\Bot\ResponseTemplateEngine;
use App\Services\Bot\Formatters\ResponseFormatter;
use App\Models\BotIntent;
use Illuminate\Support\Facades\Cache;

/**
 * ResponseGenerator - Format response đẹp
 * Thêm suggestions, product cards, etc
 * Refactored: Sử dụng ResponseFormatter để tách logic format
 */
class ResponseGenerator
{
    public function __construct(
        private RAGService $ragService,
        private ResponseTemplateEngine $templateEngine,
        private ResponseFormatter $formatter
    ) {}
    /**
     * Generate response
     */
    public function generate(
        string $content,
        string $intent,
        array $toolsResult = [],
        array $context = []
    ): array {
        // ✅ ƯU TIÊN: Xử lý checkout flow intents TRƯỚC
        $checkoutState = $context['checkout_state'] ?? null;

        // 1. Add to cart (thành công hoặc lỗi) - ✅ LUÔN ƯU TIÊN TRƯỚC TẤT CẢ
        if ($intent === 'add_to_cart') {
            // ✅ Nếu có addToCart tool result → LUÔN dùng nó (không cần LLM)
            if (!empty($toolsResult['addToCart'])) {
                $addToCartResult = $toolsResult['addToCart'];
                // ✅ Nếu có message (thành công hoặc lỗi) → xử lý
                if (!empty($addToCartResult['message'])) {
                    // ✅ Nếu thất bại → trả về message lỗi ngay
                    if (!($addToCartResult['success'] ?? false)) {
                        return [
                            'reply' => $addToCartResult['message'],
                            'products' => [],
                            'suggestions' => $addToCartResult['requires_auth'] ?? false ? ['Đăng nhập'] : [],
                            'intent' => $intent,
                            'tools_used' => array_keys($toolsResult),
                        ];
                    }

                    // ✅ Nếu thành công → tiếp tục flow
                    if (($addToCartResult['success'] ?? false)) {
                        // Nếu có getUserCoupons từ auto-trigger → kết hợp message
                        $finalMessage = $addToCartResult['message'];
                        if (!empty($toolsResult['getUserCoupons']) && ($toolsResult['getUserCoupons']['success'] ?? false)) {
                            $coupons = $toolsResult['getUserCoupons']['coupons'] ?? [];
                            \Illuminate\Support\Facades\Log::info('ResponseGenerator: add_to_cart - coupons', [
                                'coupons_count' => count($coupons),
                                'first_coupon' => $coupons[0] ?? null,
                                'getUserCoupons_success' => $toolsResult['getUserCoupons']['success'] ?? false,
                            ]);
                            if (!empty($coupons) && is_array($coupons) && count($coupons) > 0) {
                                $finalMessage .= "\n\nBạn có muốn áp mã giảm giá không? Mình thấy bạn có các mã sau:\n";
                                foreach ($coupons as $index => $coupon) {
                                    $discountText = $coupon['discount_text'] ?? 'Giảm giá';
                                    $finalMessage .= ($index + 1) . ". **{$coupon['code']}** - {$discountText}\n";
                                }
                                $finalMessage .= "\nBạn muốn áp mã nào? (Nói \"mã X\" hoặc \"số 1\", \"số 2\"...) Hoặc nói \"không\" nếu không muốn áp mã.";
                            } else {
                                $finalMessage .= "\n\nBạn có muốn áp mã giảm giá không? (Bạn chưa có mã giảm giá nào. Bạn có thể bỏ qua bước này.)";
                            }
                        } else {
                            \Illuminate\Support\Facades\Log::warning('ResponseGenerator: add_to_cart - không có getUserCoupons hoặc success=false', [
                                'has_getUserCoupons' => !empty($toolsResult['getUserCoupons']),
                                'getUserCoupons_success' => $toolsResult['getUserCoupons']['success'] ?? 'N/A',
                            ]);
                            $finalMessage .= "\n\nBạn có muốn áp mã giảm giá không?";
                        }

                        return [
                            'reply' => $finalMessage,
                            'products' => [],
                            'suggestions' => ['Không', 'Có'],
                            'intent' => $intent,
                            'tools_used' => array_keys($toolsResult),
                        ];
                    }
                }
            }
            // ✅ Nếu không có addToCart tool result → có thể tool chưa được execute
            // Log warning và trả về message yêu cầu thử lại
            \Illuminate\Support\Facades\Log::warning('ResponseGenerator: add_to_cart intent nhưng không có addToCart tool result', [
                'intent' => $intent,
                'toolsResult_keys' => array_keys($toolsResult),
            ]);
            return [
                'reply' => 'Xin lỗi, mình không thể thêm sản phẩm vào giỏ hàng lúc này. Bạn vui lòng thử lại hoặc nói rõ tên sản phẩm bạn muốn đặt nhé!',
                'products' => [],
                'suggestions' => ['Tư vấn mỹ phẩm', '/reset'],
                'intent' => $intent,
                'tools_used' => array_keys($toolsResult),
            ];
        }

        // 1.5. Coupon response → hiển thị coupons
        if ($intent === 'checkout_coupon_response') {
            \Illuminate\Support\Facades\Log::info('ResponseGenerator: Processing checkout_coupon_response', [
                'has_getUserCoupons' => !empty($toolsResult['getUserCoupons']),
                'checkout_state' => $checkoutState,
                'toolsResult_keys' => array_keys($toolsResult),
            ]);

            // ✅ Tự động trigger getUserCoupons nếu chưa có
            if (empty($toolsResult['getUserCoupons'])) {
                try {
                    \Illuminate\Support\Facades\Log::info('ResponseGenerator: Auto-triggering getUserCoupons for checkout_coupon_response');
                    $couponsResult = app(\App\Services\Bot\ToolExecutor::class)->execute('checkout_coupon_response', '', $context);
                    if (!empty($couponsResult['getUserCoupons'])) {
                        $toolsResult['getUserCoupons'] = $couponsResult['getUserCoupons'];
                        \Illuminate\Support\Facades\Log::info('ResponseGenerator: getUserCoupons triggered successfully', [
                            'success' => $toolsResult['getUserCoupons']['success'] ?? false,
                            'coupons_count' => count($toolsResult['getUserCoupons']['coupons'] ?? []),
                        ]);
                    } else {
                        \Illuminate\Support\Facades\Log::warning('ResponseGenerator: getUserCoupons result is empty');
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('ResponseGenerator: Failed to auto-trigger getUserCoupons', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            // ✅ Check cả success và coupons
            if (!empty($toolsResult['getUserCoupons']) && ($toolsResult['getUserCoupons']['success'] ?? false)) {
                $coupons = $toolsResult['getUserCoupons']['coupons'] ?? [];
                \Illuminate\Support\Facades\Log::info('ResponseGenerator: checkout_coupon_response - coupons', [
                    'coupons_count' => count($coupons),
                    'first_coupon' => $coupons[0] ?? null,
                    'getUserCoupons_success' => $toolsResult['getUserCoupons']['success'] ?? false,
                    'getUserCoupons_message' => $toolsResult['getUserCoupons']['message'] ?? 'N/A',
                ]);
                if (!empty($coupons) && is_array($coupons) && count($coupons) > 0) {
                    $finalMessage = "Bạn có muốn áp mã giảm giá không? Mình thấy bạn có các mã sau:\n";
                    foreach ($coupons as $index => $coupon) {
                        $discountText = $coupon['discount_text'] ?? 'Giảm giá';
                        $finalMessage .= ($index + 1) . ". **{$coupon['code']}** - {$discountText}\n";
                    }
                    $finalMessage .= "\nBạn muốn áp mã nào? (Nói \"mã X\" hoặc \"số 1\", \"số 2\"...) Hoặc nói \"không\" nếu không muốn áp mã.";

                    \Illuminate\Support\Facades\Log::info('ResponseGenerator: Returning coupons list', [
                        'reply_length' => strlen($finalMessage),
                    ]);

                    return [
                        'reply' => $finalMessage,
                        'products' => [],
                        'suggestions' => ['Không', 'Có'],
                        'intent' => $intent,
                        'tools_used' => array_keys($toolsResult),
                    ];
                } else {
                    $finalMessage = "Bạn có muốn áp mã giảm giá không? (Bạn chưa có mã giảm giá nào. Bạn có thể bỏ qua bước này.)";
                    \Illuminate\Support\Facades\Log::info('ResponseGenerator: No coupons available', [
                        'reply' => $finalMessage,
                    ]);
                    return [
                        'reply' => $finalMessage,
                        'products' => [],
                        'suggestions' => ['Không', 'Có'],
                        'intent' => $intent,
                        'tools_used' => array_keys($toolsResult),
                    ];
                }
            } else {
                \Illuminate\Support\Facades\Log::warning('ResponseGenerator: checkout_coupon_response - không có getUserCoupons hoặc success=false', [
                    'has_getUserCoupons' => !empty($toolsResult['getUserCoupons']),
                    'getUserCoupons_success' => $toolsResult['getUserCoupons']['success'] ?? 'N/A',
                    'getUserCoupons_message' => $toolsResult['getUserCoupons']['message'] ?? 'N/A',
                    'getUserCoupons_keys' => !empty($toolsResult['getUserCoupons']) ? array_keys($toolsResult['getUserCoupons']) : [],
                ]);
                // ✅ Fallback: Vẫn hỏi về coupon dù không có data
                $finalMessage = "Bạn có muốn áp mã giảm giá không?";
                return [
                    'reply' => $finalMessage,
                    'products' => [],
                    'suggestions' => ['Không', 'Có'],
                    'intent' => $intent,
                    'tools_used' => array_keys($toolsResult),
                ];
            }
        }

        // 2. Skip coupon → tự động hỏi address
        if ($intent === 'checkout_skip_coupon') {
            \Illuminate\Support\Facades\Log::info('ResponseGenerator: Processing checkout_skip_coupon', [
                'has_getUserAddresses' => !empty($toolsResult['getUserAddresses']),
            ]);

            // ✅ Tự động trigger getUserAddresses nếu chưa có (BotAgent đã trigger nhưng có thể chưa kịp)
            if (empty($toolsResult['getUserAddresses'])) {
                try {
                    $addressesResult = app(\App\Services\Bot\ToolExecutor::class)->execute('checkout_select_address', '', $context);
                    if (!empty($addressesResult['getUserAddresses'])) {
                        $toolsResult['getUserAddresses'] = $addressesResult['getUserAddresses'];
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('ResponseGenerator: Failed to auto-trigger getUserAddresses', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $finalMessage = "Đã bỏ qua bước mã giảm giá.\n\n";
            // ✅ Check cả success và addresses
            if (!empty($toolsResult['getUserAddresses']) && ($toolsResult['getUserAddresses']['success'] ?? false)) {
                $addresses = $toolsResult['getUserAddresses']['addresses'] ?? [];
                \Illuminate\Support\Facades\Log::info('ResponseGenerator: checkout_skip_coupon - addresses', [
                    'addresses_count' => count($addresses),
                    'first_address' => $addresses[0] ?? null,
                    'getUserAddresses_success' => $toolsResult['getUserAddresses']['success'] ?? false,
                ]);
                if (!empty($addresses) && is_array($addresses) && count($addresses) > 0) {
                    $finalMessage .= "Bạn muốn giao hàng đến địa chỉ nào? Mình thấy bạn có các địa chỉ sau:\n";
                    foreach ($addresses as $index => $addr) {
                        $default = ($addr['is_default_shipping'] ?? $addr['is_default'] ?? false) ? ' (Mặc định)' : '';
                        $fullAddress = $addr['full_address'] ?? ($addr['line1'] ?? '') . ', ' . ($addr['ward'] ?? '') . ', ' . ($addr['district'] ?? '') . ', ' . ($addr['province'] ?? '');
                        $finalMessage .= ($index + 1) . ". **{$addr['name']}** - {$fullAddress}{$default}\n";
                    }
                    $finalMessage .= "\nBạn muốn giao hàng đến địa chỉ nào? (Nói \"địa chỉ số 1\" hoặc \"địa chỉ thứ nhất\"...)";
                } else {
                    $finalMessage .= "Bạn muốn giao hàng đến địa chỉ nào? (Bạn chưa có địa chỉ nào. Mình sẽ hướng dẫn bạn thêm địa chỉ mới.)";
                }
            } else {
                \Illuminate\Support\Facades\Log::warning('ResponseGenerator: checkout_skip_coupon - không có getUserAddresses hoặc success=false', [
                    'toolsResult_keys' => array_keys($toolsResult),
                    'has_getUserAddresses' => !empty($toolsResult['getUserAddresses']),
                    'getUserAddresses_success' => $toolsResult['getUserAddresses']['success'] ?? 'N/A',
                ]);
                $finalMessage .= "Bạn muốn giao hàng đến địa chỉ nào?";
            }

            \Illuminate\Support\Facades\Log::info('ResponseGenerator: Returning early for checkout_skip_coupon', [
                'reply_length' => strlen($finalMessage),
            ]);

            return [
                'reply' => $finalMessage,
                'products' => [],
                'suggestions' => [],
                'intent' => $intent,
                'tools_used' => array_keys($toolsResult),
            ];
        }

        // 3. Apply coupon (thành công hoặc lỗi) → hỏi address
        if ($intent === 'checkout_apply_coupon' && !empty($toolsResult['applyCoupon'])) {
            $applyResult = $toolsResult['applyCoupon'];
            // ✅ Nếu thất bại → trả về message lỗi
            if (!($applyResult['success'] ?? false)) {
                return [
                    'reply' => $applyResult['message'] ?? 'Không thể áp dụng mã giảm giá. Vui lòng thử lại!',
                    'products' => [],
                    'suggestions' => ['Không', 'Có'],
                    'intent' => $intent,
                    'tools_used' => array_keys($toolsResult),
                ];
            }
            // ✅ Nếu thành công → tiếp tục flow
            if (($applyResult['success'] ?? false) && !empty($applyResult['message'])) {
                // ✅ Tự động trigger getUserAddresses nếu chưa có
                if (empty($toolsResult['getUserAddresses'])) {
                    try {
                        $addressesResult = app(\App\Services\Bot\ToolExecutor::class)->execute('checkout_select_address', '', $context);
                        if (!empty($addressesResult['getUserAddresses'])) {
                            $toolsResult['getUserAddresses'] = $addressesResult['getUserAddresses'];
                        }
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('ResponseGenerator: Failed to auto-trigger getUserAddresses', [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                $finalMessage = $applyResult['message'] . "\n\n";
                // ✅ Check cả success và addresses
                if (!empty($toolsResult['getUserAddresses']) && ($toolsResult['getUserAddresses']['success'] ?? false)) {
                    $addresses = $toolsResult['getUserAddresses']['addresses'] ?? [];
                    \Illuminate\Support\Facades\Log::info('ResponseGenerator: checkout_apply_coupon - addresses', [
                        'addresses_count' => count($addresses),
                        'first_address' => $addresses[0] ?? null,
                        'getUserAddresses_success' => $toolsResult['getUserAddresses']['success'] ?? false,
                    ]);
                    if (!empty($addresses) && is_array($addresses) && count($addresses) > 0) {
                        $finalMessage .= "Bạn muốn giao hàng đến địa chỉ nào? Mình thấy bạn có các địa chỉ sau:\n";
                        foreach ($addresses as $index => $addr) {
                            $default = ($addr['is_default_shipping'] ?? $addr['is_default'] ?? false) ? ' (Mặc định)' : '';
                            $fullAddress = $addr['full_address'] ?? ($addr['line1'] ?? '') . ', ' . ($addr['ward'] ?? '') . ', ' . ($addr['district'] ?? '') . ', ' . ($addr['province'] ?? '');
                            $finalMessage .= ($index + 1) . ". **{$addr['name']}** - {$fullAddress}{$default}\n";
                        }
                        $finalMessage .= "\nBạn muốn giao hàng đến địa chỉ nào? (Nói \"địa chỉ số 1\" hoặc \"địa chỉ thứ nhất\"...)";
                    } else {
                        $finalMessage .= "Bạn muốn giao hàng đến địa chỉ nào? (Bạn chưa có địa chỉ nào. Mình sẽ hướng dẫn bạn thêm địa chỉ mới.)";
                    }
                } else {
                    \Illuminate\Support\Facades\Log::warning('ResponseGenerator: checkout_apply_coupon - không có getUserAddresses hoặc success=false', [
                        'toolsResult_keys' => array_keys($toolsResult),
                        'has_getUserAddresses' => !empty($toolsResult['getUserAddresses']),
                        'getUserAddresses_success' => $toolsResult['getUserAddresses']['success'] ?? 'N/A',
                    ]);
                    $finalMessage .= "Bạn muốn giao hàng đến địa chỉ nào?";
                }

                return [
                    'reply' => $finalMessage,
                    'products' => [],
                    'suggestions' => [],
                    'intent' => $intent,
                    'tools_used' => array_keys($toolsResult),
                ];
            }
        }

        // 4. Select address → tính ship và hỏi shipping voucher
        if ($intent === 'checkout_select_address') {
            // ✅ Nếu có calculateShipping result → dùng nó
            if (!empty($toolsResult['calculateShipping'])) {
                $shippingResult = $toolsResult['calculateShipping'];
                // ✅ Nếu thất bại → vẫn hỏi shipping voucher (với shipping_fee = 0)
                if (!($shippingResult['success'] ?? false)) {
                    $errorMessage = $shippingResult['message'] ?? 'Không thể tính phí vận chuyển.';
                    // Nếu không có địa chỉ → hướng dẫn thêm địa chỉ nhưng vẫn tiếp tục flow
                    if (str_contains($errorMessage, 'địa chỉ')) {
                        $errorMessage .= "\n\nTạm thời mình sẽ tính phí ship = 0₫. Bạn có thể thêm địa chỉ sau.\n\n";
                    }

                    // ✅ Tự động trigger getShippingVouchers để hỏi shipping voucher
                    if (empty($toolsResult['getShippingVouchers'])) {
                        try {
                            $vouchersResult = app(\App\Services\Bot\ToolExecutor::class)->execute('checkout_shipping_voucher_response', '', $context);
                            if (!empty($vouchersResult['getShippingVouchers'])) {
                                $toolsResult['getShippingVouchers'] = $vouchersResult['getShippingVouchers'];
                            }
                        } catch (\Throwable $e) {
                            \Illuminate\Support\Facades\Log::warning('ResponseGenerator: Failed to auto-trigger getShippingVouchers', [
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    $finalMessage = $errorMessage;
                    if (!empty($toolsResult['getShippingVouchers']) && ($toolsResult['getShippingVouchers']['success'] ?? false)) {
                        $vouchers = $toolsResult['getShippingVouchers']['vouchers'] ?? [];
                        \Illuminate\Support\Facades\Log::info('ResponseGenerator: checkout_select_address (shipping failed) - vouchers', [
                            'vouchers_count' => count($vouchers),
                            'first_voucher' => $vouchers[0] ?? null,
                            'getShippingVouchers_success' => $toolsResult['getShippingVouchers']['success'] ?? false,
                        ]);
                        if (!empty($vouchers) && is_array($vouchers) && count($vouchers) > 0) {
                            $finalMessage .= "Bạn có muốn áp mã vận chuyển không? Mình thấy bạn có các mã sau:\n";
                            foreach ($vouchers as $index => $voucher) {
                                $discountText = $voucher['discount_text'] ?? 'Giảm phí ship';
                                $finalMessage .= ($index + 1) . ". **{$voucher['code']}** - {$discountText}\n";
                            }
                            $finalMessage .= "\nBạn muốn áp mã nào? (Nói \"mã X\" hoặc \"số 1\", \"số 2\"...) Hoặc nói \"không\" nếu không muốn áp mã.";
                        } else {
                            $finalMessage .= "Bạn có muốn áp mã vận chuyển không? (Bạn chưa có mã vận chuyển nào. Bạn có thể bỏ qua bước này.)";
                        }
                    } else {
                        \Illuminate\Support\Facades\Log::warning('ResponseGenerator: checkout_select_address (shipping failed) - không có getShippingVouchers hoặc success=false', [
                            'has_getShippingVouchers' => !empty($toolsResult['getShippingVouchers']),
                            'getShippingVouchers_success' => $toolsResult['getShippingVouchers']['success'] ?? 'N/A',
                        ]);
                        $finalMessage .= "Bạn có muốn áp mã vận chuyển không?";
                    }

                    // ✅ Lưu shipping_fee = 0 vào session để tính toán sau
                    session(['cart.shipping_fee' => 0]);

                    // ✅ Update state ngay để IntentClassifier có thể detect đúng checkout_skip_shipping_voucher
                    try {
                        $conversationId = $context['conversation_id'] ?? null;
                        if ($conversationId) {
                            $conversation = \App\Models\BotConversation::find($conversationId);
                            if ($conversation) {
                                $stateManager = app(\App\Services\Bot\CheckoutStateManager::class);
                                $stateManager->setState($conversation, 'shipping_calculated', [
                                    'selected_address_id' => null,
                                    'shipping_fee' => 0,
                                ]);
                            }
                        }
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('ResponseGenerator: Failed to update state for shipping_calculated', [
                            'error' => $e->getMessage(),
                        ]);
                    }

                    return [
                        'reply' => $finalMessage,
                        'products' => [],
                        'suggestions' => ['Không', 'Có'],
                        'intent' => $intent,
                        'tools_used' => array_keys($toolsResult),
                    ];
                }
                // ✅ Nếu thành công → tiếp tục flow
                if (($shippingResult['success'] ?? false) && !empty($shippingResult['message'])) {
                    // ✅ Tự động trigger getShippingVouchers nếu chưa có
                    if (empty($toolsResult['getShippingVouchers'])) {
                        try {
                            $vouchersResult = app(\App\Services\Bot\ToolExecutor::class)->execute('checkout_shipping_voucher_response', '', $context);
                            if (!empty($vouchersResult['getShippingVouchers'])) {
                                $toolsResult['getShippingVouchers'] = $vouchersResult['getShippingVouchers'];
                            }
                        } catch (\Throwable $e) {
                            \Illuminate\Support\Facades\Log::warning('ResponseGenerator: Failed to auto-trigger getShippingVouchers', [
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    $finalMessage = $shippingResult['message'] . "\n\n";

                    // ✅ QUAN TRỌNG: Nếu state là shipping_calculated, LUÔN trigger getShippingVouchers và hiển thị
                    // Đảm bảo rằng sau khi tính shipping, bot sẽ tự động hỏi về shipping voucher
                    if (($checkoutState === 'shipping_calculated' || $checkoutState === 'address_confirmed') && empty($toolsResult['getShippingVouchers'])) {
                        try {
                            \Illuminate\Support\Facades\Log::info('ResponseGenerator: Auto-triggering getShippingVouchers after shipping calculated', [
                                'checkout_state' => $checkoutState,
                            ]);
                            $vouchersResult = app(\App\Services\Bot\ToolExecutor::class)->execute('checkout_shipping_voucher_response', '', $context);
                            if (!empty($vouchersResult['getShippingVouchers'])) {
                                $toolsResult['getShippingVouchers'] = $vouchersResult['getShippingVouchers'];
                            }
                        } catch (\Throwable $e) {
                            \Illuminate\Support\Facades\Log::warning('ResponseGenerator: Failed to auto-trigger getShippingVouchers after shipping', [
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    if (!empty($toolsResult['getShippingVouchers']) && ($toolsResult['getShippingVouchers']['success'] ?? false)) {
                        $vouchers = $toolsResult['getShippingVouchers']['vouchers'] ?? [];
                        \Illuminate\Support\Facades\Log::info('ResponseGenerator: checkout_select_address (shipping success) - vouchers', [
                            'vouchers_count' => count($vouchers),
                            'first_voucher' => $vouchers[0] ?? null,
                            'getShippingVouchers_success' => $toolsResult['getShippingVouchers']['success'] ?? false,
                            'checkout_state' => $checkoutState,
                        ]);
                        if (!empty($vouchers) && is_array($vouchers) && count($vouchers) > 0) {
                            $finalMessage .= "Bạn có muốn áp mã vận chuyển không? Mình thấy bạn có các mã sau:\n";
                            foreach ($vouchers as $index => $voucher) {
                                $discountText = $voucher['discount_text'] ?? 'Giảm phí ship';
                                $finalMessage .= ($index + 1) . ". **{$voucher['code']}** - {$discountText}\n";
                            }
                            $finalMessage .= "\nBạn muốn áp mã nào? (Nói \"mã X\" hoặc \"số 1\", \"số 2\"...) Hoặc nói \"không\" nếu không muốn áp mã.";
                        } else {
                            $finalMessage .= "Bạn có muốn áp mã vận chuyển không? (Bạn chưa có mã vận chuyển nào. Bạn có thể bỏ qua bước này.)";
                        }
                    } else {
                        \Illuminate\Support\Facades\Log::warning('ResponseGenerator: checkout_select_address (shipping success) - không có getShippingVouchers hoặc success=false', [
                            'has_getShippingVouchers' => !empty($toolsResult['getShippingVouchers']),
                            'getShippingVouchers_success' => $toolsResult['getShippingVouchers']['success'] ?? 'N/A',
                            'checkout_state' => $checkoutState,
                        ]);
                        $finalMessage .= "Bạn có muốn áp mã vận chuyển không?";
                    }

                    return [
                        'reply' => $finalMessage,
                        'products' => [],
                        'suggestions' => ['Không', 'Có'],
                        'intent' => $intent,
                        'tools_used' => array_keys($toolsResult),
                    ];
                }
            }

            // ✅ Nếu chỉ có getUserAddresses (chưa tính ship) → hỏi lại
            if (!empty($toolsResult['getUserAddresses']) && ($toolsResult['getUserAddresses']['success'] ?? false)) {
                $addresses = $toolsResult['getUserAddresses']['addresses'] ?? [];
                \Illuminate\Support\Facades\Log::info('ResponseGenerator: checkout_select_address - addresses', [
                    'addresses_count' => count($addresses),
                    'first_address' => $addresses[0] ?? null,
                    'getUserAddresses_success' => $toolsResult['getUserAddresses']['success'] ?? false,
                ]);
                if (!empty($addresses) && is_array($addresses) && count($addresses) > 0) {
                    $finalMessage = "Bạn muốn giao hàng đến địa chỉ nào? Mình thấy bạn có các địa chỉ sau:\n";
                    foreach ($addresses as $index => $addr) {
                        $default = ($addr['is_default_shipping'] ?? $addr['is_default'] ?? false) ? ' (Mặc định)' : '';
                        $fullAddress = $addr['full_address'] ?? ($addr['line1'] ?? '') . ', ' . ($addr['ward'] ?? '') . ', ' . ($addr['district'] ?? '') . ', ' . ($addr['province'] ?? '');
                        $finalMessage .= ($index + 1) . ". **{$addr['name']}** - {$fullAddress}{$default}\n";
                    }
                    $finalMessage .= "\nBạn muốn giao hàng đến địa chỉ nào? (Nói \"địa chỉ số 1\" hoặc \"địa chỉ thứ nhất\"...)";

                    return [
                        'reply' => $finalMessage,
                        'products' => [],
                        'suggestions' => [],
                        'intent' => $intent,
                        'tools_used' => array_keys($toolsResult),
                    ];
                }
            }

            // ✅ Fallback: Nếu không có gì → yêu cầu chọn địa chỉ
            return [
                'reply' => "Bạn muốn giao hàng đến địa chỉ nào? Vui lòng chọn địa chỉ từ danh sách hoặc cung cấp địa chỉ mới.",
                'products' => [],
                'suggestions' => [],
                'intent' => $intent,
                'tools_used' => array_keys($toolsResult),
            ];
        }

        // 4.5. Shipping voucher response → hiển thị vouchers
        if ($intent === 'checkout_shipping_voucher_response') {
            \Illuminate\Support\Facades\Log::info('ResponseGenerator: Processing checkout_shipping_voucher_response', [
                'has_getShippingVouchers' => !empty($toolsResult['getShippingVouchers']),
                'checkout_state' => $checkoutState,
                'toolsResult_keys' => array_keys($toolsResult),
            ]);

            // ✅ Tự động trigger getShippingVouchers nếu chưa có
            if (empty($toolsResult['getShippingVouchers'])) {
                try {
                    \Illuminate\Support\Facades\Log::info('ResponseGenerator: Auto-triggering getShippingVouchers for checkout_shipping_voucher_response');
                    $vouchersResult = app(\App\Services\Bot\ToolExecutor::class)->execute('checkout_shipping_voucher_response', '', $context);
                    if (!empty($vouchersResult['getShippingVouchers'])) {
                        $toolsResult['getShippingVouchers'] = $vouchersResult['getShippingVouchers'];
                        \Illuminate\Support\Facades\Log::info('ResponseGenerator: getShippingVouchers triggered successfully', [
                            'success' => $toolsResult['getShippingVouchers']['success'] ?? false,
                            'vouchers_count' => count($toolsResult['getShippingVouchers']['vouchers'] ?? []),
                        ]);
                    } else {
                        \Illuminate\Support\Facades\Log::warning('ResponseGenerator: getShippingVouchers result is empty');
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('ResponseGenerator: Failed to auto-trigger getShippingVouchers', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            // ✅ Check cả success và vouchers
            if (!empty($toolsResult['getShippingVouchers']) && ($toolsResult['getShippingVouchers']['success'] ?? false)) {
                $vouchers = $toolsResult['getShippingVouchers']['vouchers'] ?? [];
                \Illuminate\Support\Facades\Log::info('ResponseGenerator: checkout_shipping_voucher_response - vouchers', [
                    'vouchers_count' => count($vouchers),
                    'first_voucher' => $vouchers[0] ?? null,
                    'getShippingVouchers_success' => $toolsResult['getShippingVouchers']['success'] ?? false,
                    'getShippingVouchers_message' => $toolsResult['getShippingVouchers']['message'] ?? 'N/A',
                ]);
                if (!empty($vouchers) && is_array($vouchers) && count($vouchers) > 0) {
                    // ✅ User đã trả lời "có" → hiển thị danh sách vouchers
                    $finalMessage = "Mình thấy bạn có các mã vận chuyển sau:\n";
                    foreach ($vouchers as $index => $voucher) {
                        $discountText = $voucher['discount_text'] ?? 'Giảm phí ship';
                        $finalMessage .= ($index + 1) . ". **{$voucher['code']}** - {$discountText}\n";
                    }
                    $finalMessage .= "\nBạn muốn áp mã nào? (Nói \"mã X\" hoặc \"số 1\", \"số 2\"...) Hoặc nói \"không\" nếu không muốn áp mã.";

                    \Illuminate\Support\Facades\Log::info('ResponseGenerator: Returning shipping vouchers list', [
                        'reply_length' => strlen($finalMessage),
                    ]);

                    return [
                        'reply' => $finalMessage,
                        'products' => [],
                        'suggestions' => ['Không', 'Số 1'],
                        'intent' => $intent,
                        'tools_used' => array_keys($toolsResult),
                    ];
                } else {
                    // ✅ Không có vouchers → tự động skip và hỏi payment
                    \Illuminate\Support\Facades\Log::info('ResponseGenerator: No shipping vouchers available, auto-skipping to payment');

                    // ✅ Tự động trigger getPaymentMethods
                    if (empty($toolsResult['getPaymentMethods'])) {
                        try {
                            $paymentResult = app(\App\Services\Bot\ToolExecutor::class)->execute('checkout_select_payment', '', $context);
                            if (!empty($paymentResult['getPaymentMethods'])) {
                                $toolsResult['getPaymentMethods'] = $paymentResult['getPaymentMethods'];
                            }
                        } catch (\Throwable $e) {
                            \Illuminate\Support\Facades\Log::warning('ResponseGenerator: Failed to auto-trigger getPaymentMethods after no vouchers', [
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    // Tính toán tổng đơn hàng
                    $cartItems = session('cart.items', []);
                    $subtotal = 0;
                    foreach ($cartItems as $item) {
                        $product = \App\Models\Product::find($item['product_id'] ?? 0);
                        if ($product) {
                            $variantId = $item['variant_id'] ?? null;
                            if ($variantId) {
                                $variant = \App\Models\ProductVariant::find($variantId);
                                $price = $variant ? (int)$variant->price : (int)($product->variants->min('price') ?? $product->price ?? 0);
                            } else {
                                $price = (int)($product->variants->min('price') ?? $product->price ?? 0);
                            }
                            $subtotal += $price * (int)($item['qty'] ?? 1);
                        }
                    }
                    $appliedCoupon = session('applied_coupon', []);
                    $discount = (int)($appliedCoupon['discount'] ?? 0);
                    $shippingFee = (int)session('cart.shipping_fee', 0);
                    $grandTotal = max(0, $subtotal - $discount + $shippingFee);

                    $finalMessage = "Bạn chưa có mã vận chuyển nào. Đã bỏ qua bước này.\n\n";
                    $finalMessage .= "**TÓM TẮT ĐƠN HÀNG:**\n";
                    $finalMessage .= "Tổng sản phẩm: " . number_format($subtotal, 0, ',', '.') . "₫\n";
                    if ($discount > 0) {
                        $finalMessage .= "Giảm giá: -" . number_format($discount, 0, ',', '.') . "₫\n";
                    }
                    $finalMessage .= "Phí vận chuyển: " . number_format($shippingFee, 0, ',', '.') . "₫\n";
                    $finalMessage .= "─────────────────────\n";
                    $finalMessage .= "**TỔNG CỘNG: " . number_format($grandTotal, 0, ',', '.') . "₫**\n\n";

                    if (!empty($toolsResult['getPaymentMethods']) && ($toolsResult['getPaymentMethods']['success'] ?? false)) {
                        $methods = $toolsResult['getPaymentMethods']['methods'] ?? [];
                        if (!empty($methods) && is_array($methods) && count($methods) > 0) {
                            $finalMessage .= "Bạn muốn thanh toán bằng phương thức nào?\n";
                            foreach ($methods as $index => $method) {
                                $label = $method['label'] ?? $method['name'] ?? 'Phương thức ' . ($index + 1);
                                $hint = !empty($method['hint']) ? " ({$method['hint']})" : '';
                                $finalMessage .= ($index + 1) . ". **{$label}**{$hint}\n";
                            }
                            $finalMessage .= "\nBạn muốn thanh toán bằng cách nào? (Nói \"COD\", \"VietQR\", \"MoMo\", \"VNPay\", \"số 1\"...)";
                        } else {
                            $finalMessage .= "Bạn muốn thanh toán bằng phương thức nào?";
                        }
                    } else {
                        $finalMessage .= "Bạn muốn thanh toán bằng phương thức nào?";
                    }

                    return [
                        'reply' => $finalMessage,
                        'products' => [],
                        'suggestions' => ['COD', 'VietQR', 'MoMo'],
                        'intent' => $intent,
                        'tools_used' => array_keys($toolsResult),
                    ];
                }
            } else {
                \Illuminate\Support\Facades\Log::warning('ResponseGenerator: checkout_shipping_voucher_response - không có getShippingVouchers hoặc success=false', [
                    'has_getShippingVouchers' => !empty($toolsResult['getShippingVouchers']),
                    'getShippingVouchers_success' => $toolsResult['getShippingVouchers']['success'] ?? 'N/A',
                    'getShippingVouchers_message' => $toolsResult['getShippingVouchers']['message'] ?? 'N/A',
                    'getShippingVouchers_keys' => !empty($toolsResult['getShippingVouchers']) ? array_keys($toolsResult['getShippingVouchers']) : [],
                ]);
                // ✅ Fallback: Tự động skip và hỏi payment
                \Illuminate\Support\Facades\Log::info('ResponseGenerator: checkout_shipping_voucher_response - fallback, auto-skipping to payment');

                // ✅ Tự động trigger getPaymentMethods
                if (empty($toolsResult['getPaymentMethods'])) {
                    try {
                        $paymentResult = app(\App\Services\Bot\ToolExecutor::class)->execute('checkout_select_payment', '', $context);
                        if (!empty($paymentResult['getPaymentMethods'])) {
                            $toolsResult['getPaymentMethods'] = $paymentResult['getPaymentMethods'];
                        }
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('ResponseGenerator: Failed to auto-trigger getPaymentMethods in fallback', [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Tính toán tổng đơn hàng
                $cartItems = session('cart.items', []);
                $subtotal = 0;
                foreach ($cartItems as $item) {
                    $product = \App\Models\Product::find($item['product_id'] ?? 0);
                    if ($product) {
                        $variantId = $item['variant_id'] ?? null;
                        if ($variantId) {
                            $variant = \App\Models\ProductVariant::find($variantId);
                            $price = $variant ? (int)$variant->price : (int)($product->variants->min('price') ?? $product->price ?? 0);
                        } else {
                            $price = (int)($product->variants->min('price') ?? $product->price ?? 0);
                        }
                        $subtotal += $price * (int)($item['qty'] ?? 1);
                    }
                }
                $appliedCoupon = session('applied_coupon', []);
                $discount = (int)($appliedCoupon['discount'] ?? 0);
                $shippingFee = (int)session('cart.shipping_fee', 0);
                $grandTotal = max(0, $subtotal - $discount + $shippingFee);

                $finalMessage = "Bạn chưa có mã vận chuyển nào. Đã bỏ qua bước này.\n\n";
                $finalMessage .= "**TÓM TẮT ĐƠN HÀNG:**\n";
                $finalMessage .= "Tổng sản phẩm: " . number_format($subtotal, 0, ',', '.') . "₫\n";
                if ($discount > 0) {
                    $finalMessage .= "Giảm giá: -" . number_format($discount, 0, ',', '.') . "₫\n";
                }
                $finalMessage .= "Phí vận chuyển: " . number_format($shippingFee, 0, ',', '.') . "₫\n";
                $finalMessage .= "─────────────────────\n";
                $finalMessage .= "**TỔNG CỘNG: " . number_format($grandTotal, 0, ',', '.') . "₫**\n\n";

                if (!empty($toolsResult['getPaymentMethods']) && ($toolsResult['getPaymentMethods']['success'] ?? false)) {
                    $methods = $toolsResult['getPaymentMethods']['methods'] ?? [];
                    if (!empty($methods) && is_array($methods) && count($methods) > 0) {
                        $finalMessage .= "Bạn muốn thanh toán bằng phương thức nào?\n";
                        foreach ($methods as $index => $method) {
                            $label = $method['label'] ?? $method['name'] ?? 'Phương thức ' . ($index + 1);
                            $hint = !empty($method['hint']) ? " ({$method['hint']})" : '';
                            $finalMessage .= ($index + 1) . ". **{$label}**{$hint}\n";
                        }
                        $finalMessage .= "\nBạn muốn thanh toán bằng cách nào? (Nói \"COD\", \"VietQR\", \"MoMo\", \"VNPay\", \"số 1\"...)";
                    } else {
                        $finalMessage .= "Bạn muốn thanh toán bằng phương thức nào?";
                    }
                } else {
                    $finalMessage .= "Bạn muốn thanh toán bằng phương thức nào?";
                }

                return [
                    'reply' => $finalMessage,
                    'products' => [],
                    'suggestions' => ['COD', 'VietQR', 'MoMo'],
                    'intent' => $intent,
                    'tools_used' => array_keys($toolsResult),
                ];
            }
        }

        // 5. Skip shipping voucher → hỏi payment
        if ($intent === 'checkout_skip_shipping_voucher') {
            // ✅ Tự động trigger getPaymentMethods nếu chưa có
            if (empty($toolsResult['getPaymentMethods'])) {
                try {
                    $paymentResult = app(\App\Services\Bot\ToolExecutor::class)->execute('checkout_select_payment', '', $context);
                    if (!empty($paymentResult['getPaymentMethods'])) {
                        $toolsResult['getPaymentMethods'] = $paymentResult['getPaymentMethods'];
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('ResponseGenerator: Failed to auto-trigger getPaymentMethods', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Tính toán tổng đơn hàng
            $cartItems = session('cart.items', []);
            $subtotal = 0;
            foreach ($cartItems as $item) {
                $product = \App\Models\Product::find($item['product_id'] ?? 0);
                if ($product) {
                    $variantId = $item['variant_id'] ?? null;
                    if ($variantId) {
                        $variant = \App\Models\ProductVariant::find($variantId);
                        $price = $variant ? (int)$variant->price : (int)($product->variants->min('price') ?? $product->price ?? 0);
                    } else {
                        $price = (int)($product->variants->min('price') ?? $product->price ?? 0);
                    }
                    $subtotal += $price * (int)($item['qty'] ?? 1);
                }
            }
            $appliedCoupon = session('applied_coupon', []);
            $discount = (int)($appliedCoupon['discount'] ?? 0);
            $shippingFee = (int)session('cart.shipping_fee', 0);
            $grandTotal = max(0, $subtotal - $discount + $shippingFee);

            $finalMessage = "Đã bỏ qua bước mã vận chuyển.\n\n";
            $finalMessage .= "**TÓM TẮT ĐƠN HÀNG:**\n";
            $finalMessage .= "Tổng sản phẩm: " . number_format($subtotal, 0, ',', '.') . "₫\n";
            if ($discount > 0) {
                $finalMessage .= "Giảm giá: -" . number_format($discount, 0, ',', '.') . "₫\n";
            }
            $finalMessage .= "Phí vận chuyển: " . number_format($shippingFee, 0, ',', '.') . "₫\n";
            $finalMessage .= "─────────────────────\n";
            $finalMessage .= "**TỔNG CỘNG: " . number_format($grandTotal, 0, ',', '.') . "₫**\n\n";

            if (!empty($toolsResult['getPaymentMethods']) && ($toolsResult['getPaymentMethods']['success'] ?? false)) {
                $methods = $toolsResult['getPaymentMethods']['methods'] ?? [];
                \Illuminate\Support\Facades\Log::info('ResponseGenerator: checkout_skip_shipping_voucher - payment methods', [
                    'methods_count' => count($methods),
                    'first_method' => $methods[0] ?? null,
                ]);
                if (!empty($methods) && is_array($methods) && count($methods) > 0) {
                    $finalMessage .= "Bạn muốn thanh toán bằng phương thức nào?\n";
                    foreach ($methods as $index => $method) {
                        $label = $method['label'] ?? $method['name'] ?? 'Phương thức ' . ($index + 1);
                        $hint = !empty($method['hint']) ? " ({$method['hint']})" : '';
                        $finalMessage .= ($index + 1) . ". **{$label}**{$hint}\n";
                    }
                    $finalMessage .= "\nBạn muốn thanh toán bằng cách nào? (Nói \"COD\", \"VietQR\", \"MoMo\", \"VNPay\", \"số 1\"...)";
                } else {
                    $finalMessage .= "Bạn muốn thanh toán bằng phương thức nào?";
                }
            } else {
                \Illuminate\Support\Facades\Log::warning('ResponseGenerator: checkout_skip_shipping_voucher - không có getPaymentMethods hoặc success=false', [
                    'has_getPaymentMethods' => !empty($toolsResult['getPaymentMethods']),
                    'getPaymentMethods_success' => $toolsResult['getPaymentMethods']['success'] ?? 'N/A',
                ]);
                $finalMessage .= "Bạn muốn thanh toán bằng phương thức nào?";
            }

            return [
                'reply' => $finalMessage,
                'products' => [],
                'suggestions' => ['COD', 'VietQR', 'MoMo'],
                'intent' => $intent,
                'tools_used' => array_keys($toolsResult),
            ];
        }

        // 6. Apply shipping voucher → hỏi payment
        if ($intent === 'checkout_apply_shipping_voucher') {
            // ✅ Nếu có applyShippingVoucher result → xử lý
            if (!empty($toolsResult['applyShippingVoucher'])) {
                $applyResult = $toolsResult['applyShippingVoucher'];
                // ✅ Nếu thất bại → trả về message lỗi
                if (!($applyResult['success'] ?? false)) {
                    return [
                        'reply' => $applyResult['message'] ?? 'Không thể áp dụng mã vận chuyển. Vui lòng thử lại!',
                        'products' => [],
                        'suggestions' => ['Không', 'Có'],
                        'intent' => $intent,
                        'tools_used' => array_keys($toolsResult),
                    ];
                }
            } else {
                // ✅ Nếu chưa có applyShippingVoucher → có thể tool chưa được execute
                \Illuminate\Support\Facades\Log::warning('ResponseGenerator: checkout_apply_shipping_voucher intent nhưng không có applyShippingVoucher tool result', [
                    'intent' => $intent,
                    'toolsResult_keys' => array_keys($toolsResult),
                ]);
                return [
                    'reply' => 'Xin lỗi, mình không thể áp dụng mã vận chuyển lúc này. Bạn vui lòng thử lại hoặc nói rõ mã bạn muốn áp dụng nhé!',
                    'products' => [],
                    'suggestions' => ['Không', 'Có'],
                    'intent' => $intent,
                    'tools_used' => array_keys($toolsResult),
                ];
            }

            // ✅ Nếu thành công → tiếp tục flow
            if (($applyResult['success'] ?? false) && !empty($applyResult['message'])) {
                // ✅ Tự động trigger getPaymentMethods nếu chưa có
                if (empty($toolsResult['getPaymentMethods'])) {
                    try {
                        $paymentResult = app(\App\Services\Bot\ToolExecutor::class)->execute('checkout_select_payment', '', $context);
                        if (!empty($paymentResult['getPaymentMethods'])) {
                            $toolsResult['getPaymentMethods'] = $paymentResult['getPaymentMethods'];
                        }
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('ResponseGenerator: Failed to auto-trigger getPaymentMethods', [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Tính toán tổng đơn hàng
                $cartItems = session('cart.items', []);
                $subtotal = 0;
                foreach ($cartItems as $item) {
                    $product = \App\Models\Product::find($item['product_id'] ?? 0);
                    if ($product) {
                        $variantId = $item['variant_id'] ?? null;
                        if ($variantId) {
                            $variant = \App\Models\ProductVariant::find($variantId);
                            $price = $variant ? (int)$variant->price : (int)($product->variants->min('price') ?? $product->price ?? 0);
                        } else {
                            $price = (int)($product->variants->min('price') ?? $product->price ?? 0);
                        }
                        $subtotal += $price * (int)($item['qty'] ?? 1);
                    }
                }
                $appliedCoupon = session('applied_coupon', []);
                $discount = (int)($appliedCoupon['discount'] ?? 0);
                $shippingFee = (int)session('cart.shipping_fee', 0);
                $appliedShip = session('applied_ship', []);
                $shipDiscount = (int)($appliedShip['discount'] ?? 0);
                $grandTotal = max(0, $subtotal - $discount + $shippingFee - $shipDiscount);

                $finalMessage = $applyResult['message'] . "\n\n";
                $finalMessage .= "**TÓM TẮT ĐƠN HÀNG:**\n";
                $finalMessage .= "Tổng sản phẩm: " . number_format($subtotal, 0, ',', '.') . "₫\n";
                if ($discount > 0) {
                    $finalMessage .= "Giảm giá: -" . number_format($discount, 0, ',', '.') . "₫\n";
                }
                $finalMessage .= "Phí vận chuyển: " . number_format($shippingFee, 0, ',', '.') . "₫\n";
                if ($shipDiscount > 0) {
                    $finalMessage .= "Giảm phí ship: -" . number_format($shipDiscount, 0, ',', '.') . "₫\n";
                }
                $finalMessage .= "─────────────────────\n";
                $finalMessage .= "**TỔNG CỘNG: " . number_format($grandTotal, 0, ',', '.') . "₫**\n\n";

                if (!empty($toolsResult['getPaymentMethods']) && ($toolsResult['getPaymentMethods']['success'] ?? false)) {
                    $methods = $toolsResult['getPaymentMethods']['methods'] ?? [];
                    \Illuminate\Support\Facades\Log::info('ResponseGenerator: checkout_apply_shipping_voucher - payment methods', [
                        'methods_count' => count($methods),
                        'first_method' => $methods[0] ?? null,
                    ]);
                    if (!empty($methods) && is_array($methods) && count($methods) > 0) {
                        $finalMessage .= "Bạn muốn thanh toán bằng phương thức nào?\n";
                        foreach ($methods as $index => $method) {
                            $label = $method['label'] ?? $method['name'] ?? 'Phương thức ' . ($index + 1);
                            $hint = !empty($method['hint']) ? " ({$method['hint']})" : '';
                            $finalMessage .= ($index + 1) . ". **{$label}**{$hint}\n";
                        }
                        $finalMessage .= "\nBạn muốn thanh toán bằng cách nào? (Nói \"COD\", \"VietQR\", \"MoMo\", \"VNPay\", \"số 1\"...)";
                    } else {
                        $finalMessage .= "Bạn muốn thanh toán bằng phương thức nào?";
                    }
                } else {
                    \Illuminate\Support\Facades\Log::warning('ResponseGenerator: checkout_apply_shipping_voucher - không có getPaymentMethods hoặc success=false', [
                        'has_getPaymentMethods' => !empty($toolsResult['getPaymentMethods']),
                        'getPaymentMethods_success' => $toolsResult['getPaymentMethods']['success'] ?? 'N/A',
                    ]);
                    $finalMessage .= "Bạn muốn thanh toán bằng phương thức nào?";
                }

                return [
                    'reply' => $finalMessage,
                    'products' => [],
                    'suggestions' => ['COD', 'VietQR', 'MoMo'],
                    'intent' => $intent,
                    'tools_used' => array_keys($toolsResult),
                ];
            }
        }

        // 7. Select payment → đặt hàng hoặc hiển thị payment methods
        if ($intent === 'checkout_select_payment') {
            // ✅ Nếu có placeOrder → đã đặt hàng thành công
            if (!empty($toolsResult['placeOrder'])) {
                $orderResult = $toolsResult['placeOrder'];
                if (($orderResult['success'] ?? false) && !empty($orderResult['message'])) {
                    $response = [
                        'reply' => $orderResult['message'],
                        'products' => [],
                        'suggestions' => ['Tư vấn mỹ phẩm', '/reset'],
                        'intent' => $intent,
                        'tools_used' => array_keys($toolsResult),
                    ];

                    // ✅ Thêm redirect_url vào meta nếu có (cho VietQR, MoMo, VNPay)
                    if (!empty($orderResult['redirect_url'])) {
                        $response['meta'] = [
                            'redirect_url' => $orderResult['redirect_url'],
                            'payment_method' => $orderResult['payment_method'] ?? null,
                            'order_code' => $orderResult['order_code'] ?? null,
                        ];
                        \Illuminate\Support\Facades\Log::info('ResponseGenerator: Added redirect_url to response', [
                            'redirect_url' => $orderResult['redirect_url'],
                            'payment_method' => $orderResult['payment_method'] ?? null,
                        ]);
                    }

                    return $response;
                }
            }

            // ✅ Nếu chưa có placeOrder → hiển thị payment methods
            // Tự động trigger getPaymentMethods nếu chưa có
            if (empty($toolsResult['getPaymentMethods'])) {
                try {
                    \Illuminate\Support\Facades\Log::info('ResponseGenerator: Auto-triggering getPaymentMethods for checkout_select_payment');
                    $paymentResult = app(\App\Services\Bot\ToolExecutor::class)->execute('checkout_select_payment', '', $context);
                    if (!empty($paymentResult['getPaymentMethods'])) {
                        $toolsResult['getPaymentMethods'] = $paymentResult['getPaymentMethods'];
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('ResponseGenerator: Failed to auto-trigger getPaymentMethods', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Tính toán tổng đơn hàng
            $cartItems = session('cart.items', []);
            $subtotal = 0;
            foreach ($cartItems as $item) {
                $product = \App\Models\Product::find($item['product_id'] ?? 0);
                if ($product) {
                    $variantId = $item['variant_id'] ?? null;
                    if ($variantId) {
                        $variant = \App\Models\ProductVariant::find($variantId);
                        $price = $variant ? (int)$variant->price : (int)($product->variants->min('price') ?? $product->price ?? 0);
                    } else {
                        $price = (int)($product->variants->min('price') ?? $product->price ?? 0);
                    }
                    $subtotal += $price * (int)($item['qty'] ?? 1);
                }
            }
            $appliedCoupon = session('applied_coupon', []);
            $discount = (int)($appliedCoupon['discount'] ?? 0);
            $shippingFee = (int)session('cart.shipping_fee', 0);
            $appliedShip = session('applied_ship', []);
            $shipDiscount = (int)($appliedShip['discount'] ?? 0);
            $grandTotal = max(0, $subtotal - $discount + $shippingFee - $shipDiscount);

            $finalMessage = "**TÓM TẮT ĐƠN HÀNG:**\n";
            $finalMessage .= "Tổng sản phẩm: " . number_format($subtotal, 0, ',', '.') . "₫\n";
            if ($discount > 0) {
                $finalMessage .= "Giảm giá: -" . number_format($discount, 0, ',', '.') . "₫\n";
            }
            $finalMessage .= "Phí vận chuyển: " . number_format($shippingFee, 0, ',', '.') . "₫\n";
            if ($shipDiscount > 0) {
                $finalMessage .= "Giảm phí ship: -" . number_format($shipDiscount, 0, ',', '.') . "₫\n";
            }
            $finalMessage .= "─────────────────────\n";
            $finalMessage .= "**TỔNG CỘNG: " . number_format($grandTotal, 0, ',', '.') . "₫**\n\n";

            if (!empty($toolsResult['getPaymentMethods']) && ($toolsResult['getPaymentMethods']['success'] ?? false)) {
                $methods = $toolsResult['getPaymentMethods']['methods'] ?? [];
                \Illuminate\Support\Facades\Log::info('ResponseGenerator: checkout_select_payment - payment methods', [
                    'methods_count' => count($methods),
                    'first_method' => $methods[0] ?? null,
                ]);
                if (!empty($methods) && is_array($methods) && count($methods) > 0) {
                    $finalMessage .= "Bạn muốn thanh toán bằng phương thức nào?\n";
                    foreach ($methods as $index => $method) {
                        $label = $method['label'] ?? $method['name'] ?? 'Phương thức ' . ($index + 1);
                        $hint = !empty($method['hint']) ? " ({$method['hint']})" : '';
                        $finalMessage .= ($index + 1) . ". **{$label}**{$hint}\n";
                    }
                    $finalMessage .= "\nBạn muốn thanh toán bằng cách nào? (Nói \"COD\", \"VietQR\", \"MoMo\", \"VNPay\", \"số 1\"...)";
                } else {
                    $finalMessage .= "Bạn muốn thanh toán bằng phương thức nào?";
                }
            } else {
                \Illuminate\Support\Facades\Log::warning('ResponseGenerator: checkout_select_payment - không có getPaymentMethods hoặc success=false', [
                    'has_getPaymentMethods' => !empty($toolsResult['getPaymentMethods']),
                    'getPaymentMethods_success' => $toolsResult['getPaymentMethods']['success'] ?? 'N/A',
                ]);
                $finalMessage .= "Bạn muốn thanh toán bằng phương thức nào?";
            }

            return [
                'reply' => $finalMessage,
                'products' => [],
                'suggestions' => ['COD', 'VietQR', 'MoMo'],
                'intent' => $intent,
                'tools_used' => array_keys($toolsResult),
            ];
        }

        // Extract products từ tools result (sử dụng Formatter)
        $products = $this->formatter->extractProducts($toolsResult);

        // Debug: Log nếu có toolsResult nhưng không có products
        if (!empty($toolsResult) && empty($products)) {
            \Illuminate\Support\Facades\Log::warning('ResponseGenerator: toolsResult có data nhưng không extract được products', [
                'toolsResult_keys' => array_keys($toolsResult),
                'first_tool_result_type' => gettype($toolsResult[array_key_first($toolsResult)] ?? null),
                'first_tool_result_count' => is_array($toolsResult[array_key_first($toolsResult)] ?? null)
                    ? count($toolsResult[array_key_first($toolsResult)])
                    : 'N/A',
            ]);
        }

        // Nếu không có products từ tools, thử retrieve từ RAG
        // ✅ LUÔN gọi RAG để có thêm context (ngay cả khi đã có products)
        $ragUsed = false;
        try {
            \Illuminate\Support\Facades\Log::info('ResponseGenerator: Calling RAGService::retrieve for products', [
                'has_products_from_tools' => !empty($products),
                'content_length' => strlen($content),
            ]);
            $ragResults = $this->ragService->retrieve($content ?: 'product search', $context, 4);
            $ragUsed = true;

                if (!empty($ragResults['products'])) {
                // Merge với products từ tools (ưu tiên tools, sau đó RAG)
                if (empty($products)) {
                    $products = array_slice($ragResults['products'], 0, 4);
                } else {
                    // Merge và loại bỏ duplicate
                    $existingSlugs = array_column($products, 'slug');
                    foreach ($ragResults['products'] as $ragProduct) {
                        if (!in_array($ragProduct['slug'] ?? '', $existingSlugs) && count($products) < 8) {
                            $products[] = $ragProduct;
                        }
                    }
                }
            }

            \Illuminate\Support\Facades\Log::info('ResponseGenerator: RAG retrieval completed', [
                'rag_used' => $ragUsed,
                'products_count' => count($products),
                'rag_products_count' => count($ragResults['products'] ?? []),
            ]);
            } catch (\Throwable $e) {
                // Silent fail - continue without RAG products
                \Illuminate\Support\Facades\Log::warning('ResponseGenerator: RAG retrieve failed', [
                    'error' => $e->getMessage(),
                ]);
        }

        // Check nếu intent có response template → sử dụng template
        $finalContent = $content;
        try {
            $intentModel = Cache::remember("bot.intent.{$intent}", 300, function () use ($intent) {
                return BotIntent::where('name', $intent)->first();
            });

            if ($intentModel && !empty($intentModel->config['response_template'])) {
                // Render template với data
                // Ensure entities are properly formatted
                $entities = $context['entities'] ?? [];
                // Nếu có product_index và last_products, lấy product cụ thể
                $selectedProduct = null;
                if (!empty($entities['product_index']) && !empty($context['last_products'])) {
                    $index = $entities['product_index'] - 1; // Convert to 0-based
                    if (isset($context['last_products'][$index])) {
                        $selectedProduct = $context['last_products'][$index];
                        // Nếu chưa có products trong response, thêm product được chọn
                        if (empty($products)) {
                            $products = [$selectedProduct];
                        }
                    }
                }

                // Extract benefits từ tool result (getProductInfo)
                $benefits = '';
                if (!empty($toolsResult['getProductInfo'])) {
                    $productInfo = $toolsResult['getProductInfo'];
                    if (is_array($productInfo)) {
                        // Ưu tiên benefits, sau đó description
                        $benefits = $productInfo['benefits'] ?? $productInfo['description'] ?? '';
                    }
                }
                // Nếu không có từ tool, thử lấy từ product đầu tiên
                if (empty($benefits) && !empty($products)) {
                    $firstProduct = $products[0] ?? [];
                    $benefits = $firstProduct['benefits'] ?? $firstProduct['description'] ?? $firstProduct['short_desc'] ?? '';
                }

                // Nếu vẫn không có benefits, thử dùng LLM để generate từ product name và description
                if (empty($benefits) && !empty($products)) {
                    $firstProduct = $products[0] ?? [];
                    $productName = $firstProduct['name'] ?? '';
                    $productDesc = $firstProduct['description'] ?? $firstProduct['short_desc'] ?? '';

                    if (!empty($productName)) {
                        try {
                            // Gọi LLM để generate benefits từ product name và description
                            $llmPrompt = "Hãy liệt kê các công dụng chính của sản phẩm mỹ phẩm sau:\n";
                            $llmPrompt .= "Tên sản phẩm: {$productName}\n";
                            if (!empty($productDesc)) {
                                $llmPrompt .= "Mô tả: {$productDesc}\n";
                            }
                            $llmPrompt .= "\nHãy trả lời ngắn gọn, mỗi công dụng một dòng, không cần giải thích dài.";

                            // Sử dụng LLMService để generate
                            $llmService = app(\App\Services\Bot\LLMService::class);
                            if ($llmService->enabled()) {
                                $llmResponse = $llmService->generate($llmPrompt, 'product_usage_inquiry', [], []);
                                $benefits = $llmResponse['content'] ?? '';
                            }
                        } catch (\Throwable $e) {
                            \Illuminate\Support\Facades\Log::warning('ResponseGenerator: LLM generate benefits failed', [
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }

                // QUAN TRỌNG: Nếu có budget filter nhưng không có sản phẩm → báo không có
                $hasBudgetFilter = !empty($entities['budget']['min']) || !empty($entities['budget']['max']);
                $noProductsWithBudget = $hasBudgetFilter && empty($products);

                $templateData = [
                    'greeting' => 'Xin chào!',
                    'intent_description' => $intentModel->display_name ?? $intent,
                    'skin_types' => $entities['skin_types'] ?? [],
                    'budget' => $entities['budget'] ?? ['min' => null, 'max' => null],
                    'concerns' => $entities['concerns'] ?? [],
                    'products' => $products,
                    'product_name' => $entities['product_name'] ?? ($selectedProduct['name'] ?? null),
                    'benefits' => $benefits, // Thêm benefits vào template data
                    'follow_up_questions' => $intentModel->config['follow_up_questions'] ?? [],
                    'no_products_found' => $noProductsWithBudget, // Flag để template biết không có sản phẩm match budget
                ];

                $renderedTemplate = $this->templateEngine->render(
                    $intentModel->config['response_template'],
                    $templateData
                );

                // ✅ KẾT HỢP: Nếu có LLM content (không phải fallback) → kết hợp với template
                // Nếu template có placeholder cho LLM content → inject vào
                // Nếu không → ưu tiên LLM content nếu nó dài hơn 50 ký tự (có nghĩa là LLM thực sự generate)
                if (!empty(trim($renderedTemplate))) {
                    // Check nếu template có {{llm_content}} hoặc {{content}} placeholder
                    if (str_contains($renderedTemplate, '{{llm_content}}') || str_contains($renderedTemplate, '{{content}}')) {
                        // Template có placeholder → inject LLM content vào
                        $finalContent = str_replace(
                            ['{{llm_content}}', '{{content}}'],
                            [$content, $content],
                            $renderedTemplate
                        );
                        \Illuminate\Support\Facades\Log::info('ResponseGenerator: Using template with LLM content injection', [
                            'intent' => $intent,
                        ]);
                    } elseif (!empty($content) && strlen($content) > 50) {
                        // LLM content có ý nghĩa (dài hơn 50 ký tự) → ưu tiên LLM, nhưng thêm template info nếu cần
                        // Nếu template có products info → append vào LLM content
                        if (!empty($products)) {
                            $finalContent = $content . "\n\n" . $renderedTemplate;
                        } else {
                            // Ưu tiên LLM content nếu nó tốt hơn
                            $finalContent = $content;
                        }
                        \Illuminate\Support\Facades\Log::info('ResponseGenerator: Using LLM content (priority over template)', [
                            'intent' => $intent,
                            'llm_content_length' => strlen($content),
                            'template_length' => strlen($renderedTemplate),
                        ]);
                    } else {
                        // LLM content ngắn hoặc rỗng → dùng template
                    $finalContent = $renderedTemplate;
                        \Illuminate\Support\Facades\Log::info('ResponseGenerator: Using template (LLM content too short)', [
                            'intent' => $intent,
                            'llm_content_length' => strlen($content),
                        ]);
                    }
                } else {
                    // Template rỗng → dùng LLM content
                    $finalContent = $content;
                }
            }
        } catch (\Throwable $e) {
            // Silent fail - continue với LLM content
            \Illuminate\Support\Facades\Log::warning('ResponseGenerator: Template rendering failed', [
                'error' => $e->getMessage(),
                'intent' => $intent,
            ]);
        }

        // ✅ QUAN TRỌNG: Nếu là checkout intent nhưng không có early return → có thể có vấn đề
        $checkoutIntents = [
            'add_to_cart',
            'checkout_skip_coupon',
            'checkout_apply_coupon',
            'checkout_coupon_response',
            'checkout_select_address',
            'checkout_shipping_voucher_response',
            'checkout_skip_shipping_voucher',
            'checkout_apply_shipping_voucher',
            'checkout_select_payment',
        ];

        if (in_array($intent, $checkoutIntents)) {
            \Illuminate\Support\Facades\Log::warning('ResponseGenerator: Checkout intent reached final return without early return!', [
                'intent' => $intent,
                'content_length' => strlen($finalContent),
                'finalContent' => substr($finalContent, 0, 200),
                'toolsResult_keys' => array_keys($toolsResult),
            ]);
            // ✅ Fallback: Nếu là checkout intent nhưng không có early return, trả về message mặc định
            if (empty($finalContent) || trim($finalContent) === '') {
                $finalContent = 'Xin lỗi, mình đang gặp sự cố kỹ thuật. Bạn vui lòng thử lại sau nhé!';
            }
        }

        $response = [
            'reply' => $this->formatter->formatContent($finalContent),
            'products' => $products,
            'suggestions' => $this->generateSuggestions($intent, $context, $products),
        ];

        return $response;
    }


    /**
     * Generate suggestions/chips - Chỉ 2 nút: Tư vấn mỹ phẩm và Reset
     */
    private function generateSuggestions(string $intent, array $context, array $products = []): array
    {
        // Luôn chỉ trả về 2 suggestions
        return ['Tư vấn mỹ phẩm', '/reset'];
    }

    /**
     * Generate error response
     */
    public function generateError(?string $message = null): array
    {
        // Nếu có message, cố gắng trả lời dựa trên fallback
        if ($message) {
            $lower = mb_strtolower($message);

            // Greeting
            if (preg_match('/\b(xin chào|chào|hello|hi|hey)\b/u', $lower)) {
                return [
                    'reply' => 'Chào bạn 👋 Mình là CosmeBot! Bạn muốn tư vấn theo **loại da**/**ngân sách** hay tìm một sản phẩm cụ thể?',
                    'products' => [],
                    'suggestions' => ['Tư vấn mỹ phẩm', '/reset'],
                ];
            }

            // Product search
            if (preg_match('/\b(tìm|search|mua|mua gì|sản phẩm|sp|product)\b/u', $lower)) {
                return [
                    'reply' => 'Mình sẽ giúp bạn tìm sản phẩm phù hợp! Bạn có thể cho mình biết:\n- **Loại da** (dầu, khô, hỗn hợp, nhạy cảm)\n- **Vấn đề da** (mụn, thâm, nám, lỗ chân lông...)\n- **Ngân sách** (VD: 300-500k)',
                    'products' => [],
                    'suggestions' => ['Tư vấn mỹ phẩm', '/reset'],
                ];
            }

            // Order tracking
            if (preg_match('/\b(đơn hàng|order|tra cứu|mã đơn|đơn)\b/u', $lower)) {
                return [
                    'reply' => 'Để tra cứu đơn hàng, bạn vui lòng cung cấp **mã đơn hàng** (VD: #DH123456) hoặc **số điện thoại** đặt hàng nhé!',
                    'products' => [],
                    'suggestions' => ['Tư vấn mỹ phẩm', '/reset'],
                ];
            }

            // Shipping
            if (preg_match('/\b(phí ship|ship|vận chuyển|giao hàng|shipping)\b/u', $lower)) {
                return [
                    'reply' => '**Phí vận chuyển:**\n- Miễn phí ship cho đơn từ 500.000₫\n- Phí ship 30.000₫ cho đơn dưới 500.000₫\n- Giao hàng toàn quốc trong 2-5 ngày làm việc',
                    'products' => [],
                    'suggestions' => ['Tư vấn mỹ phẩm', '/reset'],
                ];
            }

            // Return/Exchange
            if (preg_match('/\b(đổi|trả|hoàn|return|exchange)\b/u', $lower)) {
                return [
                    'reply' => '**Chính sách đổi trả:**\n- Đổi/trả trong 7 ngày kể từ ngày nhận hàng\n- Sản phẩm còn nguyên seal, chưa sử dụng\n- Miễn phí đổi trả nếu lỗi từ phía shop\n- Liên hệ hotline để được hỗ trợ nhanh nhất!',
                    'products' => [],
                    'suggestions' => ['Tư vấn mỹ phẩm', '/reset'],
                ];
            }
        }

        return [
            'reply' => 'Xin lỗi, mình gặp sự cố kỹ thuật. Bạn thử lại sau nhé hoặc liên hệ bộ phận hỗ trợ.',
            'products' => [],
            'suggestions' => ['Tư vấn mỹ phẩm', '/reset'],
        ];
    }
}
