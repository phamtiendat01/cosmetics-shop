<?php

namespace App\Tools\Bot;

use App\Http\Controllers\CartController;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * AddToCartTool - Thêm sản phẩm vào giỏ hàng
 * Hỗ trợ: "Tôi muốn đặt sản phẩm đầu tiên/thứ hai..." hoặc gọi tên sản phẩm
 */
class AddToCartTool
{
    public function execute(string $message, array $context): ?array
    {
        // Kiểm tra user đã đăng nhập chưa
        if (!auth()->check()) {
            return [
                'success' => false,
                'requires_auth' => true,
                'message' => 'Bạn cần đăng nhập để đặt hàng. Vui lòng đăng nhập trước nhé!',
            ];
        }

        $entities = $context['entities'] ?? [];
        $lastProducts = $context['last_products'] ?? [];
        
        // ✅ Log để debug
        Log::info('AddToCartTool: Starting', [
            'has_entities' => !empty($entities),
            'product_index' => $entities['product_index'] ?? null,
            'product_name' => $entities['product_name'] ?? null,
            'last_products_count' => count($lastProducts),
        ]);
        
        $product = null;
        $variantId = null;
        $qty = 1;

        // 1. Nếu có product_index → lấy từ last_products
        if (!empty($entities['product_index']) && !empty($lastProducts)) {
            $index = $entities['product_index'] - 1; // Convert to 0-based
            Log::info('AddToCartTool: Trying product_index', ['index' => $index, 'last_products_count' => count($lastProducts)]);
            if (isset($lastProducts[$index])) {
                $productData = $lastProducts[$index];
                $productId = $productData['id'] ?? null;
                $productSlug = $productData['slug'] ?? $productData['url'] ?? null;
                
                // ✅ Ưu tiên tìm theo ID nếu có
                if ($productId) {
                    $product = Product::find($productId);
                }
                
                // ✅ Nếu không có ID, thử tìm theo slug
                if (!$product && $productSlug) {
                    // Extract slug từ URL nếu cần
                    if (str_contains($productSlug, '/p/')) {
                        $productSlug = basename($productSlug);
                    }
                    $product = Product::where('slug', $productSlug)->first();
                }
                
                Log::info('AddToCartTool: Product found by index', ['product_id' => $product?->id, 'product_name' => $product?->name]);
            }
        }
        
        // 2. Nếu có product_name → tìm theo tên
        if (!$product && !empty($entities['product_name'])) {
            $productName = $entities['product_name'];
            Log::info('AddToCartTool: Trying product_name', ['name' => $productName]);
            $product = Product::where('name', 'like', "%{$productName}%")
                ->where('is_active', 1)
                ->first();
            Log::info('AddToCartTool: Product found by name', ['product_id' => $product?->id]);
        }
        
        // 3. Nếu không có, thử extract từ message
        if (!$product) {
            Log::info('AddToCartTool: Trying extractProductFromMessage');
            $product = $this->extractProductFromMessage($message, $lastProducts);
            Log::info('AddToCartTool: Product found by message', ['product_id' => $product?->id]);
        }
        
        // 4. ✅ Fallback: Nếu có last_products và không có product_index, lấy sản phẩm đầu tiên
        if (!$product && !empty($lastProducts) && empty($entities['product_index'])) {
            Log::info('AddToCartTool: Fallback to first product in last_products');
            $firstProduct = $lastProducts[0] ?? null;
            if ($firstProduct) {
                $productId = $firstProduct['id'] ?? null;
                $productSlug = $firstProduct['slug'] ?? $firstProduct['url'] ?? null;
                if ($productId) {
                    $product = Product::find($productId);
                } elseif ($productSlug) {
                    if (str_contains($productSlug, '/p/')) {
                        $productSlug = basename($productSlug);
                    }
                    $product = Product::where('slug', $productSlug)->first();
                }
            }
        }

        if (!$product) {
            Log::warning('AddToCartTool: Product not found', [
                'message' => $message,
                'entities' => $entities,
                'last_products_count' => count($lastProducts),
            ]);
            return [
                'success' => false,
                'message' => 'Mình không tìm thấy sản phẩm bạn muốn đặt. Bạn có thể nói rõ tên sản phẩm hoặc chọn từ danh sách mình đã gợi ý không?',
            ];
        }

        // Kiểm tra sản phẩm có active không
        if (!$product->is_active) {
            return [
                'success' => false,
                'message' => 'Sản phẩm này hiện không còn bán. Bạn có thể chọn sản phẩm khác không?',
            ];
        }

        // Nếu product có variants → lấy variant rẻ nhất
        if ($product->has_variants) {
            $variant = ProductVariant::where('product_id', $product->id)
                ->where('is_active', 1)
                ->orderBy('price')
                ->first();
            if ($variant) {
                $variantId = $variant->id;
            }
        }

        // Extract quantity nếu có
        if (preg_match('/\b(\d+)\s*(?:cái|sản phẩm|sp|item)\b/u', Str::lower($message), $m)) {
            $qty = max(1, (int)$m[1]);
        } elseif (preg_match('/\b(\d+)\b/u', Str::lower($message), $m)) {
            // Nếu chỉ có số và không phải product_index
            if (empty($entities['product_index'])) {
                $qty = max(1, (int)$m[1]);
            }
        }

        // Add to cart
        try {
            $result = CartController::addToCart($product->id, $variantId, $qty);
            
            if ($result['ok'] ?? false) {
                $cartData = $result['result'] ?? [];
                $cartCount = $cartData['count'] ?? 0;
                
                // ✅ Trigger checkout flow - BotAgent sẽ handle state transition
                return [
                    'success' => true,
                    'product_name' => $product->name,
                    'qty' => $qty,
                    'cart_count' => $cartCount,
                    'message' => "Đã thêm **{$product->name}** (x{$qty}) vào giỏ hàng! Giỏ hàng của bạn hiện có {$cartCount} sản phẩm.",
                    'trigger_checkout' => true, // Flag để BotAgent biết cần hỏi về checkout
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $result['message'] ?? 'Không thể thêm sản phẩm vào giỏ hàng. Vui lòng thử lại!',
                ];
            }
        } catch (\Throwable $e) {
            Log::error('AddToCartTool failed', [
                'error' => $e->getMessage(),
                'product_id' => $product->id,
            ]);
            
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi thêm sản phẩm vào giỏ hàng. Vui lòng thử lại!',
            ];
        }
    }

    /**
     * Extract product từ message
     */
    private function extractProductFromMessage(string $message, array $lastProducts = []): ?Product
    {
        $lower = Str::lower($message);
        
        // Thử tìm trong last_products trước
        foreach ($lastProducts as $productData) {
            $productName = $productData['name'] ?? '';
            if (!empty($productName) && Str::contains($lower, Str::lower($productName))) {
                $productSlug = $productData['slug'] ?? $productData['url'] ?? null;
                if ($productSlug) {
                    if (str_contains($productSlug, '/p/')) {
                        $productSlug = basename($productSlug);
                    }
                    $product = Product::where('slug', $productSlug)->first();
                    if ($product) {
                        return $product;
                    }
                }
            }
        }
        
        // Thử tìm trong database
        // Extract keywords từ message (loại bỏ các từ không cần thiết)
        $keywords = $this->extractKeywords($message);
        if (!empty($keywords)) {
            $product = Product::where('is_active', 1)
                ->where(function($q) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        $q->orWhere('name', 'like', "%{$keyword}%");
                    }
                })
                ->first();
            
            if ($product) {
                return $product;
            }
        }
        
        return null;
    }

    /**
     * Extract keywords từ message
     */
    private function extractKeywords(string $message): array
    {
        $lower = Str::lower(trim($message));
        
        // Loại bỏ các từ không cần thiết
        $stopWords = [
            'tôi', 'muốn', 'đặt', 'mua', 'cho', 'xem', 'tìm', 'sản phẩm',
            'đầu tiên', 'thứ nhất', 'thứ hai', 'thứ ba', 'thứ tư', 'thứ năm',
            'số', 'cái', 'sp', 'product', 'item',
        ];
        
        foreach ($stopWords as $stopWord) {
            $lower = str_replace($stopWord, '', $lower);
        }
        
        // Extract các từ có nghĩa (dài hơn 2 ký tự)
        $words = preg_split('/\s+/', trim($lower));
        $keywords = array_filter($words, fn($w) => mb_strlen($w) > 2);
        
        return array_values($keywords);
    }
}

