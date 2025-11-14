<?php

namespace App\Tools\Bot;

use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

/**
 * ProductSearchTool - Tìm kiếm sản phẩm
 */
class ProductSearchTool
{
    public function execute(string $message, array $context): ?array
    {
        $entities = $context['entities'] ?? [];
        
        // Extract query từ message
        $query = $this->extractQuery($message);
        
        // Build query - LUÔN query database thực tế
        $q = Product::query()
            ->where('is_active', 1)
            ->with(['variants' => function($v) {
                $v->where('is_active', 1);
            }])
            ->withMin('variants', 'price'); // Luôn cần price để filter/sort
        
        // Filter by product type TRƯỚC - QUAN TRỌNG: ưu tiên filter theo product_type
        // Nếu có product_type, PHẢI filter theo product_type trước khi search
        if (!empty($entities['product_type'])) {
            if (Schema::hasColumn('products', 'product_type')) {
                $q->where('product_type', $entities['product_type']);
            }
        }
        
        // Search by name - DÙNG LIKE search (không dùng FULLTEXT vì có thể không có index)
        // CHỈ search nếu không có product_type filter (vì product_type đã filter rồi)
        if ($query && strlen($query) >= 2 && empty($entities['product_type'])) {
            $q->where(function ($w) use ($query) {
                // Dùng LIKE search trực tiếp - đơn giản và luôn hoạt động
                $w->orWhere('name', 'like', "%{$query}%")
                  ->orWhere('short_desc', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            });
        }
        
        // Filter by skin types (JSON array) - CHỈ filter nếu có entities
        if (!empty($entities['skin_types'])) {
            if (Schema::hasColumn('products', 'skin_types')) {
                $q->where(function ($w) use ($entities) {
                    foreach ($entities['skin_types'] as $skin) {
                        $w->orWhereJsonContains('skin_types', $skin);
                    }
                });
            }
        }
        
        // Filter by concerns (JSON array)
        if (!empty($entities['concerns'])) {
            if (Schema::hasColumn('products', 'concerns')) {
                $q->where(function ($w) use ($entities) {
                    foreach ($entities['concerns'] as $concern) {
                        $w->orWhereJsonContains('concerns', $concern);
                    }
                });
            }
        }
        
        // Filter by ingredients (JSON array)
        if (!empty($entities['ingredients'])) {
            if (Schema::hasColumn('products', 'ingredients')) {
                $q->where(function ($w) use ($entities) {
                    foreach ($entities['ingredients'] as $ingredient) {
                        $w->orWhereJsonContains('ingredients', $ingredient);
                    }
                });
            }
        }
        
        // Filter by budget - QUAN TRỌNG: phải có withMin trước
        if (!empty($entities['budget']['min'])) {
            $q->having('variants_min_price', '>=', $entities['budget']['min']);
            if (!empty($entities['budget']['max'])) {
                $q->having('variants_min_price', '<=', $entities['budget']['max']);
            }
        }
        
        // Order và limit
        $q->orderBy('variants_min_price', 'asc')
            ->orderBy('id', 'desc') // Fallback sort
            ->limit(8);
        
        $products = $q->get();
        
        // Nếu không tìm được với filter strict, thử query lỏng hơn
        // NHƯNG: Nếu có product_type, PHẢI giữ filter product_type (không bỏ)
        if ($products->isEmpty() && ($query || !empty($entities))) {
            \Illuminate\Support\Facades\Log::info('ProductSearchTool: No products with strict filters, trying relaxed query', [
                'product_type' => $entities['product_type'] ?? null,
                'skin_types' => $entities['skin_types'] ?? [],
            ]);
            
            // Query lại với filter lỏng hơn - NHƯNG vẫn giữ product_type nếu có
            $q2 = Product::query()
                ->where('is_active', 1)
                ->with(['variants' => function($v) {
                    $v->where('is_active', 1);
                }])
                ->withMin('variants', 'price');
            
            // Nếu có product_type, PHẢI filter theo product_type (quan trọng nhất - KHÔNG BỎ)
            if (!empty($entities['product_type']) && Schema::hasColumn('products', 'product_type')) {
                $q2->where('product_type', $entities['product_type']);
            }
            
            // Bỏ filter skin_types nếu không tìm được (có thể database không có data)
            // Sau đó mới search by name/query
            if ($query && strlen($query) >= 2) {
                $q2->where(function ($w) use ($query) {
                    $w->orWhere('name', 'like', "%{$query}%")
                      ->orWhere('short_desc', 'like', "%{$query}%");
                });
            }
            
            $products = $q2->orderBy('variants_min_price', 'asc')
                ->orderBy('id', 'desc')
                ->limit(8)
                ->get();
        }
        
        // Nếu vẫn không có VÀ có product_type, thử chỉ filter theo product_type (bỏ hết filter khác)
        if ($products->isEmpty() && !empty($entities['product_type'])) {
            \Illuminate\Support\Facades\Log::info('ProductSearchTool: Still no products, trying product_type only');
            $products = Product::query()
                ->where('is_active', 1)
                ->where('product_type', $entities['product_type'])
                ->with(['variants' => function($v) {
                    $v->where('is_active', 1);
                }])
                ->withMin('variants', 'price')
                ->orderBy('variants_min_price', 'asc')
                ->orderBy('id', 'desc')
                ->limit(8)
                ->get();
        }
        
        // Nếu vẫn không có, trả về products bất kỳ (fallback cuối cùng)
        if ($products->isEmpty()) {
            \Illuminate\Support\Facades\Log::info('ProductSearchTool: Still no products, returning any active products');
            $products = Product::query()
                ->where('is_active', 1)
                ->with(['variants' => function($v) {
                    $v->where('is_active', 1);
                }])
                ->withMin('variants', 'price')
                ->orderBy('id', 'desc')
                ->limit(8)
                ->get();
        }
        
        // LOG để debug - chi tiết hơn
        \Illuminate\Support\Facades\Log::info('ProductSearchTool::execute', [
            'query' => $query,
            'entities' => $entities,
            'products_found' => $products->count(),
            'product_names' => $products->pluck('name')->toArray(),
            'product_types' => $products->pluck('product_type')->toArray(),
            'has_product_type_filter' => !empty($entities['product_type']),
            'product_type_filter' => $entities['product_type'] ?? null,
        ]);
        
        return $this->mapProducts($products);
    }
    
    private function extractQuery(string $message): ?string
    {
        // Remove common words - nhưng GIỮ LẠI từ khóa sản phẩm quan trọng
        $stopwords = ['tìm', 'cho', 'mình', 'bạn', 'giúp', 'gợi ý', 'sản phẩm', 'của', 'và', 'với'];
        
        // Giữ lại các từ khóa sản phẩm quan trọng
        $importantWords = ['serum', 'kem', 'toner', 'cleanser', 'sunscreen', 'chống nắng', 'sữa rửa mặt', 'rửa mặt', 'dưỡng ẩm', 'moisturizer', 'mask', 'mặt nạ', 'essence'];
        
        $words = explode(' ', Str::lower($message));
        $words = array_filter($words, function($w) use ($stopwords, $importantWords) {
            // Giữ lại từ quan trọng hoặc từ không phải stopword
            return (in_array($w, $importantWords) || !in_array($w, $stopwords)) && mb_strlen($w) >= 2;
        });
        
        $result = !empty($words) ? implode(' ', $words) : null;
        
        // Nếu không có query, thử extract từ message gốc
        if (!$result) {
            // Tìm các từ khóa sản phẩm trong message
            foreach ($importantWords as $keyword) {
                if (Str::contains(Str::lower($message), $keyword)) {
                    $result = $keyword;
                    break;
                }
            }
        }
        
        return $result;
    }
    
    private function mapProducts($products): array
    {
        return $products->map(function ($p) {
            $minVariant = $p->variants->where('is_active', 1)->sortBy('price')->first();
            $price = $minVariant->price ?? 0;
            $compare = $minVariant->compare_at_price ?? null;
            $discount = ($compare && $compare > 0 && $price > 0) 
                ? round((1 - $price / $compare) * 100) 
                : null;
            
            $url = app('router')->has('product.show') 
                ? route('product.show', $p->slug) 
                : url('/products/' . $p->slug);
            
            return [
                'url' => $url,
                'image' => $p->thumbnail ?? $p->image ?? asset('images/placeholder.png'),
                'name' => $p->name,
                'price_min' => (int)$price,
                'compare_at' => $compare ? (int)$compare : null,
                'discount' => $discount,
                'skin_types' => $p->skin_types ?? [],
                'concerns' => $p->concerns ?? [],
                'product_type' => $p->product_type ?? null,
            ];
        })->values()->all();
    }
}

