<?php

namespace App\Services\Bot\Formatters;

use Illuminate\Support\Facades\Log;

/**
 * ResponseFormatter - Format response content và products
 * Single Responsibility: Chỉ format data, không xử lý logic
 */
class ResponseFormatter
{
    /**
     * Format content (markdown -> HTML)
     */
    public function formatContent(string $content): string
    {
        if (empty($content)) {
            return '';
        }

        // Simple markdown to HTML
        $content = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $content);
        $content = preg_replace('/\*(.+?)\*/u', '<em>$1</em>', $content);
        $content = nl2br($content);

        return trim($content);
    }

    /**
     * Format product data
     */
    public function formatProduct(array $product): array
    {
        try {
            // Build URL safely
            $url = $this->buildProductUrl($product);

            // Build image URL safely
            $image = $this->buildProductImage($product);

            return [
                'id' => $product['id'] ?? null, // ✅ Thêm ID để AddToCartTool có thể dùng
                'slug' => $product['slug'] ?? null, // ✅ Thêm slug để AddToCartTool có thể dùng
                'url' => $url,
                'image' => $image,
                'name' => $product['name'] ?? 'Sản phẩm',
                'price_min' => (int)($product['price_min'] ?? $product['price'] ?? 0),
                'compare_at' => isset($product['compare_at']) ? (int)$product['compare_at'] : null,
                'discount' => isset($product['discount']) ? (int)$product['discount'] : null,
                'benefits' => $product['benefits'] ?? $product['description'] ?? '',
                'description' => $product['description'] ?? $product['short_desc'] ?? '',
                'short_desc' => $product['short_desc'] ?? '',
            ];
        } catch (\Throwable $e) {
            Log::warning('ResponseFormatter::formatProduct failed', [
                'error' => $e->getMessage(),
                'product' => $product['name'] ?? 'unknown',
            ]);

            // Return safe fallback
            return [
                'url' => '#',
                'image' => asset('images/placeholder.png'),
                'name' => $product['name'] ?? 'Sản phẩm',
                'price_min' => 0,
                'compare_at' => null,
                'discount' => null,
                'benefits' => '',
                'description' => '',
                'short_desc' => '',
            ];
        }
    }

    /**
     * Extract products từ tools result
     */
    public function extractProducts(array $toolsResult): array
    {
        $products = [];

        foreach ($toolsResult as $toolName => $result) {
            if ($result === null || !is_array($result)) {
                continue;
            }

            // Check nếu result là indexed array (array of products)
            if (isset($result[0]) && is_array($result[0])) {
                foreach ($result as $index => $product) {
                    if (!is_array($product)) {
                        continue;
                    }

                    if (isset($product['url']) || isset($product['name'])) {
                        try {
                            $formatted = $this->formatProduct($product);
                            if (!empty($formatted['name']) && $formatted['name'] !== 'Sản phẩm') {
                                $products[] = $formatted;
                            }
                        } catch (\Throwable $e) {
                            Log::warning("ResponseFormatter: formatProduct failed", [
                                'tool' => $toolName,
                                'index' => $index,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            }
            // Single product (associative array)
            elseif (isset($result['url']) || isset($result['slug']) || isset($result['name'])) {
                try {
                    $formatted = $this->formatProduct($result);
                    if (!empty($formatted['name']) && $formatted['name'] !== 'Sản phẩm') {
                        $products[] = $formatted;
                    }
                } catch (\Throwable $e) {
                    Log::warning("ResponseFormatter: formatProduct failed (single)", [
                        'tool' => $toolName,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Limit to 8 products và remove duplicates
        $uniqueProducts = [];
        $seenUrls = [];

        foreach ($products as $product) {
            $url = $product['url'] ?? '';
            if (!in_array($url, $seenUrls) && $url !== '#') {
                $uniqueProducts[] = $product;
                $seenUrls[] = $url;
            }
        }

        return array_slice($uniqueProducts, 0, 8);
    }

    /**
     * Build product URL safely
     */
    private function buildProductUrl(array $product): string
    {
        if (isset($product['url']) && !empty($product['url'])) {
            return $product['url'];
        }

        if (isset($product['slug'])) {
            try {
                if (app('router')->has('product.show')) {
                    return route('product.show', $product['slug']);
                }
            } catch (\Throwable $e) {
                // Fallback to simple URL
            }
            return '/p/' . $product['slug'];
        }

        if (isset($product['id'])) {
            return '/p/' . $product['id'];
        }

        return '#';
    }

    /**
     * Build product image URL safely
     */
    private function buildProductImage(array $product): string
    {
        $image = $product['image'] ?? $product['thumbnail'] ?? null;

        if (empty($image)) {
            return asset('images/placeholder.png');
        }

        // Already full URL
        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            return $image;
        }

        // Storage path
        if (str_starts_with($image, 'storage/') || str_starts_with($image, '/storage/')) {
            return asset(ltrim($image, '/'));
        }

        // Products path
        if (str_starts_with($image, 'products/') || str_starts_with($image, '/products/')) {
            return asset('storage/' . ltrim($image, '/'));
        }

        // Default to storage
        return asset('storage/' . ltrim($image, '/'));
    }
}

