<?php

namespace App\Services\Bot;

use App\Models\BotTool;
use App\Models\BotIntent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * ToolExecutor - Execute tools/functions dựa trên intent
 */
class ToolExecutor
{
    private array $toolRegistry = [];

    public function __construct()
    {
        $this->loadTools();
    }

    /**
     * Execute tools cho intent
     *
     * @param string $intent
     * @param string $message
     * @param array $context
     * @return array {tool_name => result}
     */
    public function execute(string $intent, string $message, array $context): array
    {
        $results = [];

        // Map intent -> tools
        $toolsToExecute = $this->getToolsForIntent($intent);

        foreach ($toolsToExecute as $toolName) {
            try {
                $result = $this->executeTool($toolName, $message, $context);
                if ($result !== null) {
                    $results[$toolName] = $result;
                }
            } catch (\Throwable $e) {
                Log::warning("Tool execution failed: {$toolName}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Execute một tool cụ thể
     */
    private function executeTool(string $toolName, string $message, array $context)
    {
        // Hardcoded tool handlers (fallback nếu không có trong DB)
        $hardcodedHandlers = [
            'searchProducts' => \App\Tools\Bot\ProductSearchTool::class,
            'getProductInfo' => \App\Tools\Bot\ProductInfoTool::class,
            'pickProducts' => \App\Tools\Bot\PickProductsTool::class,
            'getPolicy' => \App\Tools\Bot\PolicyTool::class,
            'getOrderStatus' => \App\Tools\Bot\OrderTrackingTool::class,
            'validateCoupon' => \App\Tools\Bot\CouponTool::class,
            'addToCart' => \App\Tools\Bot\AddToCartTool::class,
            'getUserCoupons' => \App\Tools\Bot\GetUserCouponsTool::class,
            'applyCoupon' => \App\Tools\Bot\ApplyCouponTool::class,
            'getUserAddresses' => \App\Tools\Bot\GetUserAddressesTool::class,
            'calculateShipping' => \App\Tools\Bot\CalculateShippingTool::class,
            'getShippingVouchers' => \App\Tools\Bot\GetShippingVouchersTool::class,
            'applyShippingVoucher' => \App\Tools\Bot\ApplyShippingVoucherTool::class,
            'getPaymentMethods' => \App\Tools\Bot\GetPaymentMethodsTool::class,
            'placeOrder' => \App\Tools\Bot\PlaceOrderTool::class,
        ];

        // Nếu tool có trong registry (từ DB)
        if (isset($this->toolRegistry[$toolName])) {
            $tool = $this->toolRegistry[$toolName];
            $handlerClass = $tool->handler_class;

            // Nếu DB không có handler_class, dùng hardcoded
            if (empty($handlerClass) && isset($hardcodedHandlers[$toolName])) {
                $handlerClass = $hardcodedHandlers[$toolName];
            }
        }
        // Nếu tool không có trong registry, thử hardcoded
        elseif (isset($hardcodedHandlers[$toolName])) {
            $handlerClass = $hardcodedHandlers[$toolName];
        }
        else {
            Log::warning("Tool not found: {$toolName}");
            return null;
        }

        if (!class_exists($handlerClass)) {
            Log::warning("Tool handler class not found: {$handlerClass}");
            return null;
        }

        $handler = app($handlerClass);

        if (!method_exists($handler, 'execute')) {
            Log::warning("Tool handler missing execute method: {$handlerClass}");
            return null;
        }

        // ✅ QUAN TRỌNG: Đảm bảo context có đầy đủ checkout_data
        // Nếu đang trong checkout flow, load thêm checkout data
        if (!empty($context['checkout_state'])) {
            try {
                $conversationId = $context['conversation_id'] ?? null;
                if ($conversationId) {
                    $conversation = \App\Models\BotConversation::find($conversationId);
                    if ($conversation) {
                        $stateManager = app(\App\Services\Bot\CheckoutStateManager::class);
                        $checkoutData = $stateManager->getData($conversation);
                        // Merge checkout_data vào context
                        $context['checkout_data'] = array_merge(
                            $context['checkout_data'] ?? [],
                            $checkoutData
                        );
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('ToolExecutor: Failed to load checkout data', ['error' => $e->getMessage()]);
            }
        }

        return $handler->execute($message, $context);
    }

    /**
     * Get tools cho intent
     * Ưu tiên: Database config > Hardcoded mapping
     */
    private function getToolsForIntent(string $intent): array
    {
        // 1. Load intent từ database và check config['tools']
        $intentModel = Cache::remember("bot.intent.{$intent}", 300, function () use ($intent) {
            return \App\Models\BotIntent::where('name', $intent)
                ->where('is_active', true)
                ->first();
        });

        // 2. Nếu intent có config['tools'] → dùng config
        if ($intentModel && !empty($intentModel->config['tools']) && is_array($intentModel->config['tools'])) {
            $tools = $intentModel->config['tools'];
            // Validate tools exist
            $validTools = [];
            foreach ($tools as $toolName) {
                if (isset($this->toolRegistry[$toolName])) {
                    $validTools[] = $toolName;
                } else {
                    Log::warning("Tool not found in registry: {$toolName}", [
                        'intent' => $intent,
                    ]);
                }
            }
            if (!empty($validTools)) {
                return $validTools;
            }
        }

        // 3. Fallback về hardcoded mapping (như hiện tại)
        $mapping = [
            'product_search' => ['searchProducts'],
            'product_info' => ['getProductInfo'],
            'product_usage_inquiry' => ['getProductInfo'], // Hỏi về công dụng sản phẩm
            'product_recommendation' => ['searchProducts', 'pickProducts'],
            'product_comparison' => ['searchProducts', 'getProductInfo'],
            'ingredient_inquiry' => ['getProductInfo', 'searchProducts'],
            'usage_inquiry' => ['getProductInfo'],
            'skin_concern_consultation' => ['searchProducts', 'pickProducts'],
            'price_inquiry' => ['getProductInfo', 'searchProducts'],
            'review_inquiry' => ['getProductInfo'],
            'routine_suggestion' => ['searchProducts'],
            'order_tracking' => ['getOrderStatus'],
            'shipping_policy' => ['getPolicy'],
            'add_to_cart' => ['addToCart'], // ✅ Đặt hàng
            'checkout_init' => ['getUserCoupons'], // Bắt đầu checkout → hỏi coupon
            'checkout_coupon_response' => ['getUserCoupons'], // User trả lời về coupon
            'checkout_apply_coupon' => ['applyCoupon'], // Áp mã giảm giá
            'checkout_skip_coupon' => [], // Bỏ qua coupon
            'checkout_select_address' => ['getUserAddresses', 'calculateShipping'], // Chọn địa chỉ và tính ship
            'checkout_shipping_voucher_response' => ['getShippingVouchers'], // User trả lời về shipping voucher
            'checkout_apply_shipping_voucher' => ['applyShippingVoucher'], // Áp mã vận chuyển
            'checkout_skip_shipping_voucher' => [], // Bỏ qua shipping voucher
                   'checkout_select_payment' => ['getPaymentMethods', 'placeOrder'], // Chọn payment và đặt hàng
            'return_policy' => ['getPolicy'],
            'payment_policy' => ['getPolicy'],
            'coupon_check' => ['validateCoupon'],
        ];

        return $mapping[$intent] ?? [];
    }

    /**
     * Load tools từ database
     */
    private function loadTools(): void
    {
        $tools = Cache::remember('bot.tools.active', 300, function () {
            return BotTool::active()->get();
        });

        foreach ($tools as $tool) {
            $this->toolRegistry[$tool->name] = $tool;
        }
    }

    /**
     * Get all tool declarations cho LLM
     */
    public function getToolDeclarations(): array
    {
        return array_map(
            fn($tool) => $tool->toFunctionDeclaration(),
            $this->toolRegistry
        );
    }
}

