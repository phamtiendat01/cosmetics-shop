<?php

namespace App\Services\Bot;

use App\Services\Bot\RAGService;

/**
 * ResponseGenerator - Format response đẹp
 * Thêm suggestions, product cards, etc
 */
class ResponseGenerator
{
    public function __construct(
        private RAGService $ragService
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
        // Extract products từ tools result
        $products = $this->extractProducts($toolsResult);
        
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
        if (empty($products) && !empty($content)) {
            try {
                $ragResults = $this->ragService->retrieve($content, $context, 4);
                if (!empty($ragResults['products'])) {
                    $products = array_slice($ragResults['products'], 0, 4);
                }
            } catch (\Throwable $e) {
                // Silent fail - continue without RAG products
                \Illuminate\Support\Facades\Log::warning('ResponseGenerator: RAG retrieve failed', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        $response = [
            'reply' => $this->formatContent($content),
            'products' => $products,
            'suggestions' => $this->generateSuggestions($intent, $context, $products),
        ];
        
        return $response;
    }

    /**
     * Format content (markdown -> HTML)
     */
    private function formatContent(string $content): string
    {
        // Simple markdown to HTML
        $content = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $content);
        $content = preg_replace('/\*(.+?)\*/u', '<em>$1</em>', $content);
        $content = nl2br($content);
        
        return $content;
    }

    /**
     * Extract products từ tools result
     */
    private function extractProducts(array $toolsResult): array
    {
        $products = [];
        
        foreach ($toolsResult as $toolName => $result) {
            if ($result === null) {
                continue;
            }
            
            if (!is_array($result)) {
                continue;
            }
            
            // searchProducts, pickProducts trả về array of products (indexed array)
            // Check nếu result là indexed array (có key 0 và là array)
            if (isset($result[0]) && is_array($result[0])) {
                // Đây là array of products
                foreach ($result as $index => $product) {
                    if (!is_array($product)) {
                        continue;
                    }
                    
                    // Check có name hoặc url
                    if (isset($product['url']) || isset($product['name'])) {
                        try {
                            $formatted = $this->formatProduct($product);
                            if (!empty($formatted['name']) && $formatted['name'] !== 'Sản phẩm') {
                                $products[] = $formatted;
                            }
                        } catch (\Throwable $e) {
                            \Illuminate\Support\Facades\Log::warning("ResponseGenerator: formatProduct failed", [
                                'tool' => $toolName,
                                'index' => $index,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            }
            // getProductInfo trả về single product (associative array, không có key 0)
            elseif (isset($result['url']) || isset($result['slug']) || isset($result['name'])) {
                try {
                    $formatted = $this->formatProduct($result);
                    if (!empty($formatted['name']) && $formatted['name'] !== 'Sản phẩm') {
                        $products[] = $formatted;
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("ResponseGenerator: formatProduct failed (single)", [
                        'tool' => $toolName,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
        
        // Limit to 8 products
        return array_slice($products, 0, 8);
    }

    /**
     * Format product data
     */
    private function formatProduct(array $product): array
    {
        // Build URL safely
        $url = '/p/' . ($product['slug'] ?? $product['id'] ?? '');
        if (isset($product['url']) && !empty($product['url'])) {
            $url = $product['url'];
        } elseif (isset($product['slug'])) {
            try {
                $url = route('product.show', $product['slug']);
            } catch (\Throwable $e) {
                $url = '/p/' . $product['slug'];
            }
        }
        
        // Build image URL safely
        $image = asset('images/placeholder.png');
        if (isset($product['image']) && !empty($product['image'])) {
            $img = $product['image'];
            if (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) {
                $image = $img;
            } elseif (str_starts_with($img, 'storage/') || str_starts_with($img, '/storage/')) {
                $image = asset(ltrim($img, '/'));
            } elseif (str_starts_with($img, 'products/') || str_starts_with($img, '/products/')) {
                $image = asset('storage/' . ltrim($img, '/'));
            } else {
                $image = asset('storage/' . ltrim($img, '/'));
            }
        }
        
        return [
            'url' => $url,
            'image' => $image,
            'name' => $product['name'] ?? 'Sản phẩm',
            'price_min' => (int)($product['price_min'] ?? $product['price'] ?? 0),
            'compare_at' => isset($product['compare_at']) ? (int)$product['compare_at'] : null,
            'discount' => isset($product['discount']) ? (int)$product['discount'] : null,
        ];
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
