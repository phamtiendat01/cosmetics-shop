<?php

namespace App\Services\Bot;

use App\Contracts\Bot\BotServiceInterface;
use App\Models\BotConversation;
use App\Models\BotMessage;
use App\Services\Bot\IntentClassifier;
use App\Services\Bot\ContextManager;
use App\Services\Bot\ToolExecutor;
use App\Services\Bot\LLMService;
use App\Services\Bot\ResponseGenerator;
use App\Services\Bot\AnalyticsService;
use App\Services\Bot\CheckoutStateManager;
use Illuminate\Support\Facades\Log;

/**
 * BotAgent - Orchestrator chính của chatbot
 * Quản lý toàn bộ flow: Intent → Tools → LLM → Response
 * Refactored: Implement interface, tách logic thành methods nhỏ hơn
 */
class BotAgent implements BotServiceInterface
{
    public function __construct(
        private IntentClassifier $intentClassifier,
        private ContextManager $contextManager,
        private ToolExecutor $toolExecutor,
        private LLMService $llmService,
        private ResponseGenerator $responseGenerator,
        private AnalyticsService $analytics,
        private CheckoutStateManager $checkoutStateManager
    ) {}

    /**
     * Xử lý tin nhắn từ user
     *
     * @param string $message
     * @param string|null $sessionId
     * @param int|null $userId
     * @return array {reply, products, suggestions, intent, tools_used}
     */
    public function process(string $message, ?string $sessionId = null, ?int $userId = null): array
    {
        $startTime = microtime(true);

        try {
            // 1. Lấy hoặc tạo conversation
            $conversation = $this->getOrCreateConversation($sessionId, $userId);

            // 2. Load context TRƯỚC (để có entities từ messages cũ)
            try {
                $context = $this->contextManager->load($conversation);
                // ✅ Lưu last_message để handleCheckoutFlow có thể dùng
                $context['last_message'] = $message;
            } catch (\Throwable $e) {
                Log::warning('ContextManager::load failed', ['error' => $e->getMessage()]);
                $context = ['entities' => [], 'history' => [], 'last_message' => $message];
            }

            // 3. Extract entities từ message hiện tại và merge vào context
            try {
                $currentEntities = $this->contextManager->extractEntitiesFromMessage($message);
                // Merge với entities cũ (ưu tiên entities mới cho product_type, budget)
                $oldEntities = $context['entities'] ?? [];
                // QUAN TRỌNG: Budget merge - ưu tiên budget mới nếu có (kể cả khi min=0)
                $budget = $oldEntities['budget'] ?? ['min' => null, 'max' => null];
                if (!empty($currentEntities['budget'])) {
                    // Nếu có budget mới (có min hoặc max) → dùng budget mới
                    if (isset($currentEntities['budget']['min']) || isset($currentEntities['budget']['max'])) {
                        $budget = $currentEntities['budget'];
                    }
                }

                $context['entities'] = [
                    'skin_types' => array_values(array_unique(array_merge($oldEntities['skin_types'] ?? [], $currentEntities['skin_types'] ?? []))),
                    'concerns' => array_values(array_unique(array_merge($oldEntities['concerns'] ?? [], $currentEntities['concerns'] ?? []))),
                    'ingredients' => array_values(array_unique(array_merge($oldEntities['ingredients'] ?? [], $currentEntities['ingredients'] ?? []))),
                    'product_type' => $currentEntities['product_type'] ?? $oldEntities['product_type'] ?? null,
                    'budget' => $budget,
                    'name' => $currentEntities['name'] ?? $oldEntities['name'] ?? null,
                    'last_product' => $oldEntities['last_product'] ?? null,
                    'product_index' => $currentEntities['product_index'] ?? null, // Sản phẩm thứ nhất, thứ hai...
                ];

                // Nếu có product_index, map với last_products để lấy product_name
                if (!empty($currentEntities['product_index']) && !empty($context['last_products'])) {
                    $index = $currentEntities['product_index'] - 1; // Convert to 0-based
                    if (isset($context['last_products'][$index])) {
                        $context['entities']['product_name'] = $context['last_products'][$index]['name'] ?? null;
                        $context['entities']['product_slug'] = $context['last_products'][$index]['url'] ?? null;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Entity extraction failed', ['error' => $e->getMessage()]);
            }

            // 4. Lưu tin nhắn user (SAU khi extract entities)
            $userMessage = BotMessage::create([
                'conversation_id' => $conversation->id,
                'role' => 'user',
                'content' => $message,
            ]);

            // 4. Phân loại intent (với error handling)
            try {
                $intentResult = $this->intentClassifier->classify($message, $context);
                $intent = $intentResult['intent'] ?? 'unknown';
                $confidence = $intentResult['confidence'] ?? 0.0;
            } catch (\Throwable $e) {
                Log::warning('IntentClassifier::classify failed', ['error' => $e->getMessage()]);
                $intent = 'unknown';
                $confidence = 0.0;
            }

            // 5. Update context với intent (không block nếu fail)
            try {
                $this->contextManager->updateIntent($conversation, $intent, $confidence);
            } catch (\Throwable $e) {
                Log::warning('ContextManager::updateIntent failed', ['error' => $e->getMessage()]);
            }

            // 6. Execute tools nếu cần (với error handling)
            $toolsResult = [];
            if ($intent !== 'unknown' && $intent !== 'greeting') {
                try {
                    $toolsResult = $this->toolExecutor->execute($intent, $message, $context);
                    // Debug: Log toolsResult ngay sau khi execute (luôn log để debug)
                    Log::info('BotAgent: toolsResult after execute', [
                        'intent' => $intent,
                        'tools_count' => count($toolsResult),
                        'tools_keys' => array_keys($toolsResult),
                        'first_tool' => array_key_first($toolsResult),
                        'first_tool_success' => $toolsResult[array_key_first($toolsResult)]['success'] ?? 'N/A',
                        'first_tool_message' => substr($toolsResult[array_key_first($toolsResult)]['message'] ?? 'N/A', 0, 100),
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('ToolExecutor::execute failed', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    $toolsResult = [];
                }
            }

            // ✅ Auto-trigger checkout flow tools nếu cần (pass by reference để update toolsResult)
            $this->autoTriggerCheckoutTools($conversation, $intent, $toolsResult, $context);

            // ✅ Check xem ResponseGenerator có return sớm không (cho checkout intents)
            // Nếu có, skip LLM và dùng response từ ResponseGenerator
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

            $shouldSkipLLM = in_array($intent, $checkoutIntents);

            // 7. Generate response với LLM + RAG (với error handling)
            $llmUsed = false;
            $ragUsed = false;
            $llmResponse = ['content' => ''];

            if (!$shouldSkipLLM) {
                try {
                    // ✅ Lấy checkout state để log
                    $checkoutState = $this->checkoutStateManager->getState($conversation);

                    // Log trước khi gọi LLM
                    if ($this->llmService->enabled()) {
                        Log::info('BotAgent: Calling LLMService::generate', [
                            'intent' => $intent,
                            'message_length' => strlen($message),
                            'has_tools_result' => !empty($toolsResult),
                            'checkout_state' => $checkoutState,
                        ]);
                    } else {
                        Log::warning('BotAgent: LLMService disabled (no API key)');
                    }

                    $llmResponse = $this->llmService->generate(
                        message: $message,
                        intent: $intent,
                        context: $context,
                        toolsResult: $toolsResult
                    );

                    // Check nếu LLM thực sự được dùng (không phải fallback)
                    $llmUsed = $this->llmService->enabled() && !empty($llmResponse['content']);
                    $ragUsed = $this->llmService->enabled(); // RAG được gọi trong LLMService nếu enabled

                    Log::info('BotAgent: LLMService response', [
                        'llm_used' => $llmUsed,
                        'rag_used' => $ragUsed,
                        'content_length' => strlen($llmResponse['content'] ?? ''),
                        'is_fallback' => !$llmUsed,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('LLMService::generate failed', [
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]);
                    // Fallback response
                    $llmResponse = ['content' => $this->llmService->getFallbackResponse($message, $intent)];
                }
            } else {
                Log::info('BotAgent: Skipping LLM for checkout intent', ['intent' => $intent]);
            }

            // ✅ Update context với toolsResult mới (nếu có auto-trigger)
            if (!empty($toolsResult)) {
                $context['tools_result'] = $toolsResult;
            }

            // 8. Format response (với error handling)
            try {
                $response = $this->responseGenerator->generate(
                    content: $llmResponse['content'] ?? '',
                    intent: $intent,
                    toolsResult: $toolsResult,
                    context: $context
                );
            } catch (\Throwable $e) {
                Log::error('ResponseGenerator::generate failed', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
                // Ultimate fallback
                $response = [
                    'reply' => $this->llmService->getFallbackResponse($message, $intent),
                    'products' => [],
                    'suggestions' => ['Tư vấn mỹ phẩm', '/reset'],
                ];
            }

            // 9. Lưu danh sách sản phẩm đã trả về vào context (để hỏi về sản phẩm thứ nhất, thứ hai...)
            if (!empty($response['products'])) {
                $context['last_products'] = array_slice($response['products'], 0, 10); // Lưu tối đa 10 sản phẩm
            }

            // ✅ Lưu last_products vào metadata để lần sau có thể dùng
            try {
                $metadata = $conversation->metadata ?? [];
                if (!empty($context['last_products'])) {
                    $metadata['last_products'] = $context['last_products'];
                    $conversation->update(['metadata' => $metadata]);
                }
            } catch (\Throwable $e) {
                Log::warning('BotAgent: Failed to save last_products to metadata', ['error' => $e->getMessage()]);
            }

            // 10. Lưu tin nhắn assistant (không block nếu fail)
            try {
                $assistantMessage = BotMessage::create([
                    'conversation_id' => $conversation->id,
                    'role' => 'assistant',
                    'content' => $response['reply'],
                    'intent' => $intent,
                    'confidence' => $confidence,
                    'tools_used' => $toolsResult,
                    'metadata' => [
                        'products_count' => count($response['products'] ?? []),
                        'suggestions_count' => count($response['suggestions'] ?? []),
                    ],
                ]);
            } catch (\Throwable $e) {
                Log::warning('BotMessage::create failed', ['error' => $e->getMessage()]);
                $assistantMessage = null;
            }

            // 11. Update context với entities và products đã trả về (không block nếu fail)
            try {
                $this->contextManager->save($conversation, $context);
            } catch (\Throwable $e) {
                Log::warning('ContextManager::save failed', ['error' => $e->getMessage()]);
            }

            // 12. Analytics (không block nếu fail)
            try {
                $latency = (microtime(true) - $startTime) * 1000;
                $this->analytics->logInteraction($conversation, $userMessage, $assistantMessage, [
                    'intent' => $intent,
                    'confidence' => $confidence,
                    'tools_used' => array_keys($toolsResult),
                    'latency_ms' => $latency,
                ]);
            } catch (\Throwable $e) {
                // Silent fail cho analytics
                Log::warning('Analytics logging failed', ['error' => $e->getMessage()]);
            }

            // 12. Update conversation
            try {
                $conversation->touch();
            } catch (\Throwable $e) {
                // Silent fail
            }

            // 13. Thêm intent vào response
            $response['intent'] = $intent;
            $response['confidence'] = $confidence;

            return $response;

        } catch (\Throwable $e) {
            Log::error('BotAgent::process failed', [
                'message' => $message,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Fallback response với message để cố gắng trả lời
            try {
                // Try to get fallback từ LLMService
                $fallbackContent = $this->llmService->getFallbackResponse($message, 'unknown');
                return [
                    'reply' => $fallbackContent,
                    'products' => [],
                    'suggestions' => ['Tư vấn mỹ phẩm', '/reset'],
                ];
            } catch (\Throwable $e2) {
                Log::error('Fallback response also failed', [
                    'error' => $e2->getMessage(),
                ]);

                // Ultimate fallback - simple response
                $lower = mb_strtolower($message);
                $reply = "Mình hiểu bạn đang tìm kiếm thông tin! Bạn có thể:\n- **Tư vấn sản phẩm** theo loại da, ngân sách\n- **Tra cứu đơn hàng** bằng mã đơn\n- **Hỏi về chính sách** (ship, đổi trả, thanh toán)\n\nBạn muốn hỏi gì cụ thể nhỉ? 😊";

                if (preg_match('/\b(sữa rửa mặt|rửa mặt|cleanser)\b/u', $lower)) {
                    $reply = "Mình sẽ tìm sản phẩm rửa mặt phù hợp cho bạn! Bạn có thể cho mình biết:\n- **Loại da** (dầu, khô, hỗn hợp, nhạy cảm)\n- **Vấn đề da** (mụn, thâm, lỗ chân lông...)\n- **Ngân sách** (VD: 300-500k)";
                } elseif (preg_match('/\b(phí ship|ship|vận chuyển)\b/u', $lower)) {
                    $reply = "**Phí vận chuyển:**\n- Miễn phí ship cho đơn từ 500.000₫\n- Phí ship 30.000₫ cho đơn dưới 500.000₫\n- Giao hàng toàn quốc trong 2-5 ngày làm việc";
                }

                return [
                    'reply' => $reply,
                    'products' => [],
                    'suggestions' => ['Tư vấn mỹ phẩm', '/reset'],
                ];
            }
        }
    }

    /**
     * Lấy hoặc tạo conversation
     */
    private function getOrCreateConversation(?string $sessionId, ?int $userId): BotConversation
    {
        $sessionId = $sessionId ?: session()->getId();

        // Tìm conversation active gần nhất
        $conversation = BotConversation::where('session_id', $sessionId)
            ->where('status', 'active')
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->latest('updated_at')
            ->first();

        if (!$conversation) {
            $conversation = BotConversation::create([
                'session_id' => $sessionId,
                'user_id' => $userId,
                'status' => 'active',
                'metadata' => [],
                'started_at' => now(),
            ]);
        }

        return $conversation;
    }

    /**
     * Reset conversation
     */
    public function reset(?string $sessionId = null, ?int $userId = null): void
    {
        $sessionId = $sessionId ?: session()->getId();

        BotConversation::where('session_id', $sessionId)
            ->where('status', 'active')
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->update(['status' => 'completed', 'completed_at' => now()]);
    }

    /**
     * ✅ Auto-trigger checkout tools nếu cần
     */
    private function autoTriggerCheckoutTools(BotConversation $conversation, string $intent, array &$toolsResult, array $context): void
    {
        try {
            $checkoutState = $this->checkoutStateManager->getState($conversation);

            // Nếu add_to_cart thành công → tự động hỏi coupon
            if ($intent === 'add_to_cart' && !empty($toolsResult['addToCart']) && ($toolsResult['addToCart']['success'] ?? false)) {
                // Set state trước
                $this->checkoutStateManager->setState($conversation, 'cart_added', [
                    'cart_items' => session('cart.items', []),
                ]);
                // Trigger getUserCoupons
                $couponsResult = $this->toolExecutor->execute('checkout_init', '', $context);
                if (!empty($couponsResult['getUserCoupons'])) {
                    $toolsResult['getUserCoupons'] = $couponsResult['getUserCoupons'];
                    // Chuyển sang coupon_asked ngay
                    $this->checkoutStateManager->setState($conversation, 'coupon_asked', [
                        'available_coupons' => $couponsResult['getUserCoupons']['coupons'] ?? [],
                    ]);
                } else {
                    // Nếu không có coupon, vẫn chuyển sang coupon_asked để bot hỏi
                    $this->checkoutStateManager->setState($conversation, 'coupon_asked', [
                        'available_coupons' => [],
                    ]);
                }
                // Không return, tiếp tục handleCheckoutFlow
            }

            // Nếu coupon_applied → tự động hỏi address
            if ($checkoutState === 'coupon_applied' && empty($toolsResult['getUserAddresses'])) {
                $addressesResult = $this->toolExecutor->execute('checkout_select_address', '', $context);
                if (!empty($addressesResult['getUserAddresses'])) {
                    $toolsResult['getUserAddresses'] = $addressesResult['getUserAddresses'];
                }
                // Set state address_asked ngay
                $this->checkoutStateManager->setState($conversation, 'address_asked', [
                    'available_addresses' => $addressesResult['getUserAddresses']['addresses'] ?? [],
                ]);
            }

            // Nếu shipping_calculated → tự động hỏi shipping voucher
            if ($checkoutState === 'shipping_calculated' && empty($toolsResult['getShippingVouchers'])) {
                $vouchersResult = $this->toolExecutor->execute('checkout_shipping_voucher_response', '', $context);
                if (!empty($vouchersResult['getShippingVouchers'])) {
                    $toolsResult['getShippingVouchers'] = $vouchersResult['getShippingVouchers'];
                }
            }

            // Nếu shipping_voucher_applied → tự động hỏi payment method
            if ($checkoutState === 'shipping_voucher_applied' && empty($toolsResult['getPaymentMethods'])) {
                $paymentResult = $this->toolExecutor->execute('checkout_select_payment', '', $context);
                if (!empty($paymentResult['getPaymentMethods'])) {
                    $toolsResult['getPaymentMethods'] = $paymentResult['getPaymentMethods'];
                }
            }

            // Handle state transitions
            $this->handleCheckoutFlow($conversation, $intent, $toolsResult, $context);
        } catch (\Throwable $e) {
            Log::warning('BotAgent: autoTriggerCheckoutTools failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * ✅ Handle checkout flow state transitions
     */
    private function handleCheckoutFlow(BotConversation $conversation, string $intent, array $toolsResult, array $context): void
    {
        try {
            $currentState = $this->checkoutStateManager->getState($conversation);
            if (!$currentState) {
                return; // Không trong checkout flow
            }

            $lastMessage = $context['last_message'] ?? '';
            $lower = strtolower($lastMessage);

            // Handle các bước tiếp theo dựa trên intent và tools result
            switch ($currentState) {
                case 'cart_added':
                    // Sau khi hỏi coupon → chuyển sang coupon_asked
                    if (!empty($toolsResult['getUserCoupons'])) {
                        $this->checkoutStateManager->setState($conversation, 'coupon_asked', [
                            'available_coupons' => $toolsResult['getUserCoupons']['coupons'] ?? [],
                        ]);
                    }
                    break;

                case 'coupon_asked':
                    // Nếu apply coupon thành công hoặc user nói "không"
                    if (!empty($toolsResult['applyCoupon']) && ($toolsResult['applyCoupon']['success'] ?? false)) {
                        $this->checkoutStateManager->setState($conversation, 'coupon_applied', [
                            'selected_coupon' => $toolsResult['applyCoupon']['code'] ?? null,
                        ]);
                    } elseif (preg_match('/\b(không|không có|bỏ qua|skip)\b/u', $lower) || $intent === 'checkout_skip_coupon') {
                        $this->checkoutStateManager->setState($conversation, 'coupon_applied');
                    }
                    break;

                case 'coupon_applied':
                    // Chuyển sang address_asked
                    if (!empty($toolsResult['getUserAddresses'])) {
                        $this->checkoutStateManager->setState($conversation, 'address_asked', [
                            'available_addresses' => $toolsResult['getUserAddresses']['addresses'] ?? [],
                        ]);
                    }
                    break;

                case 'address_asked':
                    // Nếu calculate shipping thành công
                    if (!empty($toolsResult['calculateShipping']) && ($toolsResult['calculateShipping']['success'] ?? false)) {
                        $this->checkoutStateManager->setState($conversation, 'address_confirmed', [
                            'selected_address_id' => $toolsResult['calculateShipping']['address_id'] ?? null,
                            'shipping_fee' => $toolsResult['calculateShipping']['shipping_fee'] ?? 0,
                        ]);
                        // ✅ Chuyển sang shipping_calculated ngay để có thể hỏi shipping voucher
                        $this->checkoutStateManager->setState($conversation, 'shipping_calculated', [
                            'selected_address_id' => $toolsResult['calculateShipping']['address_id'] ?? null,
                            'shipping_fee' => $toolsResult['calculateShipping']['shipping_fee'] ?? 0,
                        ]);
                    } elseif (!empty($toolsResult['calculateShipping']) && !($toolsResult['calculateShipping']['success'] ?? false)) {
                        // ✅ Nếu calculateShipping thất bại (không có địa chỉ) → vẫn chuyển sang shipping_calculated với shipping_fee = 0
                        // Để user có thể skip shipping voucher và tiếp tục
                        $this->checkoutStateManager->setState($conversation, 'shipping_calculated', [
                            'selected_address_id' => null,
                            'shipping_fee' => 0, // Tạm thời set = 0, sẽ tính lại sau
                        ]);
                    }
                    break;

                case 'shipping_calculated':
                    // Chuyển sang shipping_voucher_asked khi có getShippingVouchers
                    if (!empty($toolsResult['getShippingVouchers'])) {
                        $this->checkoutStateManager->setState($conversation, 'shipping_voucher_asked', [
                            'available_shipping_vouchers' => $toolsResult['getShippingVouchers']['vouchers'] ?? [],
                        ]);
                    }
                    // ✅ QUAN TRỌNG: Nếu user trả lời "không", "tiếp tục" hoặc intent là checkout_skip_shipping_voucher → chuyển sang shipping_voucher_applied
                    if (preg_match('/\b(không|không có|bỏ qua|skip|không cần|thôi|không muốn|không dùng|tiếp tục|ok|được)\b/u', $lower) || $intent === 'checkout_skip_shipping_voucher') {
                        $this->checkoutStateManager->setState($conversation, 'shipping_voucher_applied');
                    }
                    break;

                case 'shipping_voucher_asked':
                    // Nếu apply shipping voucher thành công
                    if (!empty($toolsResult['applyShippingVoucher']) && ($toolsResult['applyShippingVoucher']['success'] ?? false)) {
                        $this->checkoutStateManager->setState($conversation, 'shipping_voucher_applied', [
                            'selected_shipping_voucher' => $toolsResult['applyShippingVoucher']['code'] ?? null,
                        ]);
                    }
                    // ✅ Nếu user nói "không", "tiếp tục" hoặc intent là checkout_skip_shipping_voucher
                    elseif (preg_match('/\b(không|không có|bỏ qua|skip|không cần|thôi|không muốn|không dùng|tiếp tục|ok|được)\b/u', $lower) || $intent === 'checkout_skip_shipping_voucher') {
                        $this->checkoutStateManager->setState($conversation, 'shipping_voucher_applied');
                    }
                    // ✅ Nếu user trả lời "có" và có getShippingVouchers → giữ nguyên state shipping_voucher_asked (đã hiển thị vouchers)
                    elseif ($intent === 'checkout_shipping_voucher_response' && !empty($toolsResult['getShippingVouchers'])) {
                        // State đã đúng, không cần update
                    }
                    break;

                case 'shipping_voucher_applied':
                    // Chuyển sang payment_method_asked - tự động trigger getPaymentMethods
                    if (empty($toolsResult['getPaymentMethods'])) {
                        $paymentResult = $this->toolExecutor->execute('checkout_select_payment', '', $context);
                        if (!empty($paymentResult['getPaymentMethods'])) {
                            $toolsResult['getPaymentMethods'] = $paymentResult['getPaymentMethods'];
                        }
                    }
                    // Luôn chuyển sang payment_method_asked
                    $this->checkoutStateManager->setState($conversation, 'payment_method_asked', [
                        'available_payment_methods' => $toolsResult['getPaymentMethods']['methods'] ?? [],
                    ]);
                    break;

                case 'payment_method_asked':
                    // Nếu user chọn payment method
                    if (!empty($toolsResult['placeOrder']) && ($toolsResult['placeOrder']['success'] ?? false)) {
                        $this->checkoutStateManager->setState($conversation, 'order_placed');
                        $this->checkoutStateManager->reset($conversation); // Reset sau khi đặt hàng thành công
                    } elseif (preg_match('/\b(cod|vietqr|momo|vnpay|wallet|ví cosme)\b/u', $lower)) {
                        $paymentMethod = $this->extractPaymentMethod($lastMessage);
                        if ($paymentMethod) {
                            $this->checkoutStateManager->setState($conversation, 'payment_method_selected', [
                                'selected_payment_method' => $paymentMethod,
                            ]);
                        }
                    }
                    break;

                case 'payment_method_selected':
                    // Nếu place order thành công
                    if (!empty($toolsResult['placeOrder']) && ($toolsResult['placeOrder']['success'] ?? false)) {
                        $this->checkoutStateManager->setState($conversation, 'order_placed');
                        $this->checkoutStateManager->reset($conversation); // Reset sau khi đặt hàng thành công
                    }
                    break;
            }
        } catch (\Throwable $e) {
            Log::warning('BotAgent: handleCheckoutFlow failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Extract payment method từ message
     */
    private function extractPaymentMethod(string $message): ?string
    {
        $lower = strtolower(trim($message));
        if (preg_match('/\b(cod|thanh toán khi nhận)\b/u', $lower)) return 'COD';
        if (preg_match('/\b(vietqr|qr|chuyển khoản)\b/u', $lower)) return 'VIETQR';
        if (preg_match('/\b(momo)\b/u', $lower)) return 'MOMO';
        if (preg_match('/\b(vnpay|vn pay)\b/u', $lower)) return 'VNPAY';
        if (preg_match('/\b(ví cosme|wallet|cosme wallet|ví)\b/u', $lower)) return 'WALLET';
        return null;
    }
}

