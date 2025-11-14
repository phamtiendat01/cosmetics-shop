<?php

namespace App\Services\Bot;

use App\Models\BotTool;
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
        if (!isset($this->toolRegistry[$toolName])) {
            return null;
        }
        
        $tool = $this->toolRegistry[$toolName];
        $handlerClass = $tool->handler_class;
        
        if (!class_exists($handlerClass)) {
            Log::warning("Tool handler class not found: {$handlerClass}");
            return null;
        }
        
        $handler = app($handlerClass);
        
        if (!method_exists($handler, 'execute')) {
            Log::warning("Tool handler missing execute method: {$handlerClass}");
            return null;
        }
        
        return $handler->execute($message, $context);
    }

    /**
     * Get tools cho intent
     */
    private function getToolsForIntent(string $intent): array
    {
        $mapping = [
            'product_search' => ['searchProducts'],
            'product_info' => ['getProductInfo'],
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

