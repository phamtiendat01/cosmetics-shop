<?php

namespace App\Services\Bot;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * RAGService - Retrieval Augmented Generation
 * Tìm kiếm semantic từ knowledge base (products, policies, FAQs)
 */
class RAGService
{
    /**
     * Search relevant information từ knowledge base
     * 
     * @param string $query - Câu hỏi của user
     * @param array $context - Context từ conversation
     * @param int $limit - Số lượng kết quả
     * @return array {products: [], policies: [], faqs: []}
     */
    public function retrieve(string $query, array $context = [], int $limit = 5): array
    {
        $results = [
            'products' => [],
            'policies' => [],
            'faqs' => [],
        ];
        
        try {
            // 1. Search products (semantic + keyword)
            $results['products'] = $this->searchProducts($query, $context, $limit);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('RAGService::searchProducts failed', [
                'error' => $e->getMessage(),
                'query' => $query,
            ]);
            $results['products'] = [];
        }
        
        try {
            // 2. Search policies
            $results['policies'] = $this->searchPolicies($query, $limit);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('RAGService::searchPolicies failed', [
                'error' => $e->getMessage(),
            ]);
            $results['policies'] = [];
        }
        
        try {
            // 3. Search FAQs (nếu có)
            $results['faqs'] = $this->searchFAQs($query, $limit);
        } catch (\Throwable $e) {
            // Silent fail cho FAQs
            $results['faqs'] = [];
        }
        
        return $results;
    }
    
    /**
     * Semantic search products
     */
    private function searchProducts(string $query, array $context, int $limit): array
    {
        try {
            $lowerQuery = Str::lower($query);
            $entities = $context['entities'] ?? [];
            
            // Build query - LUÔN query database thực tế
            $q = Product::query()
                ->where('is_active', 1)
                ->with(['variants' => function($v) {
                    $v->where('is_active', 1);
                }])
                ->withMin('variants', 'price');
            
            // Search trên name, description - DÙNG LIKE search (không dùng FULLTEXT)
            if (strlen($query) >= 2) {
                $q->where(function ($w) use ($query) {
                    // Dùng LIKE search trực tiếp - đơn giản và luôn hoạt động
                    $w->orWhere('name', 'like', "%{$query}%")
                      ->orWhere('short_desc', 'like', "%{$query}%")
                      ->orWhere('description', 'like', "%{$query}%");
                });
            }
            
            // Filter by skin types (JSON) - CHỈ filter nếu có entities
            if (!empty($entities['skin_types'])) {
                $q->where(function ($w) use ($entities) {
                    foreach ($entities['skin_types'] as $skin) {
                        $w->orWhereJsonContains('skin_types', $skin);
                    }
                });
            }
            
            // Filter by concerns (JSON)
            if (!empty($entities['concerns'])) {
                $q->where(function ($w) use ($entities) {
                    foreach ($entities['concerns'] as $concern) {
                        $w->orWhereJsonContains('concerns', $concern);
                    }
                });
            }
            
            // Filter by product type
            if (!empty($entities['product_type'])) {
                $q->where('product_type', $entities['product_type']);
            }
            
            // Filter by ingredients (JSON)
            if (!empty($entities['ingredients'])) {
                $q->where(function ($w) use ($entities) {
                    foreach ($entities['ingredients'] as $ingredient) {
                        $w->orWhereJsonContains('ingredients', $ingredient);
                    }
                });
            }
            
            // Filter by budget
            if (!empty($entities['budget']['min'])) {
                $q->having('variants_min_price', '>=', $entities['budget']['min']);
                if (!empty($entities['budget']['max'])) {
                    $q->having('variants_min_price', '<=', $entities['budget']['max']);
                }
            }
            
            // Semantic matching: tìm keywords trong benefits, usage_instructions
            // CHỈ thêm nếu không có query chính (để không conflict)
            if (empty($query) || strlen($query) < 2) {
                $keywords = $this->extractKeywords($query);
                if (!empty($keywords)) {
                    $q->where(function ($w) use ($keywords) {
                        foreach ($keywords as $keyword) {
                            $w->orWhere('benefits', 'like', "%{$keyword}%")
                              ->orWhere('usage_instructions', 'like', "%{$keyword}%")
                              ->orWhere('name', 'like', "%{$keyword}%");
                        }
                    });
                }
            }
            
            // Nếu không có filter nào, vẫn trả về products (không filter)
            $products = $q->orderBy('variants_min_price', 'asc')
                ->orderBy('id', 'desc') // Fallback sort
                ->limit($limit)
                ->get();
            
            // LOG để debug
            \Illuminate\Support\Facades\Log::info('RAGService::searchProducts', [
                'query' => $query,
                'entities' => $entities,
                'products_found' => $products->count(),
                'product_names' => $products->pluck('name')->toArray(),
            ]);
            
            return $this->formatProductsForRAG($products);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('RAGService::searchProducts exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [];
        }
    }
    
    /**
     * Search policies
     */
    private function searchPolicies(string $query, int $limit): array
    {
        $lowerQuery = Str::lower($query);
        $policies = [];
        
        // Shipping policy
        if (Str::contains($lowerQuery, ['ship', 'vận chuyển', 'giao hàng', 'phí', 'shipping'])) {
            $policies[] = [
                'type' => 'shipping',
                'title' => 'Chính sách vận chuyển',
                'content' => "**Phí vận chuyển:**\n- Miễn phí ship cho đơn từ 500.000₫\n- Phí ship 30.000₫ cho đơn dưới 500.000₫\n- Giao hàng toàn quốc trong 2-5 ngày làm việc\n- Hỗ trợ giao hàng nhanh (1-2 ngày) với phí bổ sung",
            ];
        }
        
        // Return/Exchange policy
        if (Str::contains($lowerQuery, ['đổi', 'trả', 'hoàn', 'return', 'exchange', 'bảo hành'])) {
            $policies[] = [
                'type' => 'return',
                'title' => 'Chính sách đổi trả',
                'content' => "**Chính sách đổi trả:**\n- Đổi/trả trong 7 ngày kể từ ngày nhận hàng\n- Sản phẩm còn nguyên seal, chưa sử dụng\n- Miễn phí đổi trả nếu lỗi từ phía shop\n- Liên hệ hotline để được hỗ trợ nhanh nhất!",
            ];
        }
        
        // Payment policy
        if (Str::contains($lowerQuery, ['thanh toán', 'payment', 'pay', 'tiền', 'cod', 'chuyển khoản'])) {
            $policies[] = [
                'type' => 'payment',
                'title' => 'Phương thức thanh toán',
                'content' => "**Phương thức thanh toán:**\n- COD (Thanh toán khi nhận hàng)\n- Chuyển khoản qua ngân hàng\n- Ví điện tử (MoMo, ZaloPay)\n- Thẻ tín dụng/ghi nợ",
            ];
        }
        
        return array_slice($policies, 0, $limit);
    }
    
    /**
     * Search FAQs (placeholder - có thể mở rộng)
     */
    private function searchFAQs(string $query, int $limit): array
    {
        // TODO: Implement FAQ search nếu có bảng FAQs
        return [];
    }
    
    /**
     * Extract keywords từ query
     */
    private function extractKeywords(string $query): array
    {
        $stopwords = ['tìm', 'cho', 'mình', 'bạn', 'giúp', 'gợi ý', 'sản phẩm', 'mua', 'của', 'và', 'với'];
        $words = explode(' ', Str::lower($query));
        $keywords = array_filter($words, fn($w) => !in_array($w, $stopwords) && mb_strlen($w) >= 2);
        
        return array_values($keywords);
    }
    
    /**
     * Format products cho RAG context
     */
    private function formatProductsForRAG($products): array
    {
        try {
            return $products->map(function ($p) {
                try {
                    $minVariant = $p->variants ? $p->variants->where('is_active', 1)->sortBy('price')->first() : null;
                    
                    // Build URL safely
                    $url = '/p/' . ($p->slug ?? $p->id);
                    try {
                        if (function_exists('route') && $p->slug) {
                            $url = route('product.show', $p->slug);
                        }
                    } catch (\Throwable $e) {
                        // Fallback to simple URL
                        $url = '/p/' . ($p->slug ?? $p->id);
                    }
                    
                    // Build image URL safely
                    $image = asset('images/placeholder.png');
                    if ($p->thumbnail) {
                        $image = str_starts_with($p->thumbnail, 'http') ? $p->thumbnail : asset('storage/' . ltrim($p->thumbnail, '/'));
                    } elseif ($p->image) {
                        $image = str_starts_with($p->image, 'http') ? $p->image : asset('storage/' . ltrim($p->image, '/'));
                    }
                    
                    return [
                        'id' => $p->id,
                        'name' => $p->name ?? 'Sản phẩm',
                        'slug' => $p->slug ?? '',
                        'url' => $url,
                        'image' => $image,
                        'price_min' => (int)($minVariant->price ?? 0),
                        'compare_at' => $minVariant && $minVariant->compare_at_price ? (int)$minVariant->compare_at_price : null,
                        'short_desc' => $p->short_desc ?? '',
                        'skin_types' => $p->skin_types ?? [],
                        'concerns' => $p->concerns ?? [],
                        'ingredients' => $p->ingredients ?? [],
                        'benefits' => $p->benefits ?? '',
                        'product_type' => $p->product_type ?? null,
                        'texture' => $p->texture ?? null,
                    ];
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('RAGService::formatProductsForRAG - product format failed', [
                        'product_id' => $p->id ?? null,
                        'error' => $e->getMessage(),
                    ]);
                    return null;
                }
            })->filter()->values()->toArray();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('RAGService::formatProductsForRAG exception', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
    
    /**
     * Build RAG context string cho LLM
     */
    public function buildContextString(array $ragResults): string
    {
        $context = [];
        
        // Products context
        if (!empty($ragResults['products'])) {
            $context[] = "**SẢN PHẨM LIÊN QUAN:**\n";
            foreach ($ragResults['products'] as $p) {
                $context[] = "- **{$p['name']}** ({$p['price_min']}₫)";
                if ($p['benefits']) {
                    $context[] = "  Công dụng: {$p['benefits']}";
                }
                if (!empty($p['skin_types'])) {
                    $context[] = "  Phù hợp: " . implode(', ', $p['skin_types']);
                }
                $context[] = "";
            }
        }
        
        // Policies context
        if (!empty($ragResults['policies'])) {
            $context[] = "**CHÍNH SÁCH:**\n";
            foreach ($ragResults['policies'] as $policy) {
                $context[] = "{$policy['title']}:\n{$policy['content']}\n";
            }
        }
        
        return implode("\n", $context);
    }
}

