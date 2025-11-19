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
    private $budgetFilter = null;
    
    public function execute(string $message, array $context): ?array
    {
        $entities = $context['entities'] ?? [];
        
        // Extract query từ message
        $query = $this->extractQuery($message);
        
        // Lưu budget và product_type để dùng trong mapProducts
        $this->budgetFilter = $entities['budget'] ?? null;
        $this->productTypeFilter = $entities['product_type'] ?? null;
        
        // Build query - LUÔN query database thực tế
        $q = Product::query()
            ->where('is_active', 1)
            ->with(['variants' => function($v) {
                $v->where('is_active', 1);
            }])
            ->withMin('variants', 'price'); // Luôn cần price để filter/sort
        
        // Filter by product type TRƯỚC - QUAN TRỌNG: ưu tiên filter theo product_type
        // Nếu có product_type trong DB, filter theo DB
        // Nếu không có trong DB, sẽ filter bằng name search
        $hasProductTypeInDb = false;
        if (!empty($entities['product_type'])) {
            if (Schema::hasColumn('products', 'product_type')) {
                // Thử filter theo DB trước
                $q->where('product_type', $entities['product_type']);
                $hasProductTypeInDb = true;
            }
        }
        
        // Search by name - QUAN TRỌNG: Nếu có product_type, search theo keywords của product_type đó
        // Nếu có cả query và product_type, search theo cả hai (OR logic)
        if (!empty($entities['product_type'])) {
            // Có product_type → search theo keywords của product_type đó
            $productTypeKeywords = [
                'serum' => ['serum', 'essence', 'ampoule', 'concentrate', 'booster'],
                'cleanser' => ['cleanser', 'sữa rửa mặt', 'rửa mặt', 'foam', 'gel cleanser', 'washing'],
                'moisturizer' => ['moisturizer', 'kem dưỡng', 'dưỡng ẩm', 'cream', 'lotion'],
                'sunscreen' => ['sunscreen', 'chống nắng', 'spf', 'sunblock', 'sun protection'],
                'toner' => ['toner', 'nước hoa hồng', 'lotion'],
                'mask' => ['mask', 'mặt nạ'],
                'eye_cream' => ['eye cream', 'kem mắt', 'eye'],
            ];
            
            $keywords = $productTypeKeywords[$entities['product_type']] ?? [];
            if (!empty($keywords)) {
                $q->where(function ($w) use ($keywords, $query) {
                    // Search theo keywords của product_type
                    foreach ($keywords as $keyword) {
                        $w->orWhere('name', 'like', "%{$keyword}%")
                          ->orWhere('short_desc', 'like', "%{$keyword}%")
                          ->orWhere('description', 'like', "%{$keyword}%");
                    }
                    // Nếu có query, thêm search theo query (OR với keywords)
                    if ($query && strlen($query) >= 2) {
                        $w->orWhere('name', 'like', "%{$query}%")
                          ->orWhere('short_desc', 'like', "%{$query}%")
                          ->orWhere('description', 'like', "%{$query}%");
                    }
                });
            } elseif ($query && strlen($query) >= 2) {
                // Có product_type nhưng không có keywords → search theo query
                $q->where(function ($w) use ($query) {
                    $w->orWhere('name', 'like', "%{$query}%")
                      ->orWhere('short_desc', 'like', "%{$query}%")
                      ->orWhere('description', 'like', "%{$query}%");
                });
            }
        } elseif ($query && strlen($query) >= 2) {
            // Không có product_type nhưng có query → search theo query
            $q->where(function ($w) use ($query) {
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
        
        // Filter by budget - DÙNG whereHas để filter chính xác trên variants
        // QUAN TRỌNG: Phải filter cả min và max trong cùng một whereHas để đảm bảo có variant match cả 2 điều kiện
        if (!empty($entities['budget']['min']) || !empty($entities['budget']['max'])) {
            $q->whereHas('variants', function($vq) use ($entities) {
                $vq->where('is_active', 1);
                // Nếu có min (ví dụ: "trên 500K") → chỉ filter >= min
                if (!empty($entities['budget']['min'])) {
                    $vq->where('price', '>=', $entities['budget']['min']);
                }
                // Nếu có max (ví dụ: "dưới 500K") → chỉ filter <= max
                if (!empty($entities['budget']['max'])) {
                    $vq->where('price', '<=', $entities['budget']['max']);
                }
            });
        }
        
        // Order và limit
        $q->orderBy('variants_min_price', 'asc')
            ->orderBy('id', 'desc') // Fallback sort
            ->limit(8);
        
        $products = $q->get();
        
        // Nếu không tìm được với filter strict, thử query lỏng hơn
        // NHƯNG: Nếu có product_type, PHẢI giữ filter product_type (không bỏ)
        // QUAN TRỌNG: Vẫn phải giữ budget filter nếu có
        if ($products->isEmpty() && ($query || !empty($entities))) {
            \Illuminate\Support\Facades\Log::info('ProductSearchTool: No products with strict filters, trying relaxed query', [
                'product_type' => $entities['product_type'] ?? null,
                'skin_types' => $entities['skin_types'] ?? [],
                'budget' => $entities['budget'] ?? null,
            ]);
            
            // Query lại với filter lỏng hơn - NHƯNG vẫn giữ product_type và budget nếu có
            $q2 = Product::query()
                ->where('is_active', 1)
                ->with(['variants' => function($v) {
                    $v->where('is_active', 1);
                }])
                ->withMin('variants', 'price');
            
            // Nếu có product_type, PHẢI filter theo product_type (quan trọng nhất - KHÔNG BỎ)
            $hasProductTypeInDb2 = false;
            if (!empty($entities['product_type']) && Schema::hasColumn('products', 'product_type')) {
                $q2->where('product_type', $entities['product_type']);
                $hasProductTypeInDb2 = true;
            }
            
            // QUAN TRỌNG: Vẫn phải áp dụng budget filter trong fallback query (dùng whereHas)
            if (!empty($entities['budget']['min']) || !empty($entities['budget']['max'])) {
                $q2->whereHas('variants', function($vq) use ($entities) {
                    $vq->where('is_active', 1);
                    if (!empty($entities['budget']['min'])) {
                        $vq->where('price', '>=', $entities['budget']['min']);
                    }
                    if (!empty($entities['budget']['max'])) {
                        $vq->where('price', '<=', $entities['budget']['max']);
                    }
                });
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
        
        // Nếu vẫn không có VÀ có product_type, thử chỉ filter theo product_type + budget (bỏ filter khác)
        if ($products->isEmpty() && !empty($entities['product_type'])) {
            \Illuminate\Support\Facades\Log::info('ProductSearchTool: Still no products, trying product_type + budget only');
            $q3 = Product::query()
                ->where('is_active', 1)
                ->where('product_type', $entities['product_type'])
                ->with(['variants' => function($v) {
                    $v->where('is_active', 1);
                }])
                ->withMin('variants', 'price');
            
            // Vẫn phải áp dụng budget filter (dùng whereHas)
            if (!empty($entities['budget']['min']) || !empty($entities['budget']['max'])) {
                $q3->whereHas('variants', function($vq) use ($entities) {
                    $vq->where('is_active', 1);
                    if (!empty($entities['budget']['min'])) {
                        $vq->where('price', '>=', $entities['budget']['min']);
                    }
                    if (!empty($entities['budget']['max'])) {
                        $vq->where('price', '<=', $entities['budget']['max']);
                    }
                });
            }
            
            $products = $q3->orderBy('variants_min_price', 'asc')
                ->orderBy('id', 'desc')
                ->limit(8)
                ->get();
        }
        
        // Nếu vẫn không có VÀ có budget, thử chỉ filter theo budget (fallback cuối cùng trước khi trả về bất kỳ)
        if ($products->isEmpty() && (!empty($entities['budget']['min']) || !empty($entities['budget']['max']))) {
            \Illuminate\Support\Facades\Log::info('ProductSearchTool: Still no products, trying budget only');
            $q4 = Product::query()
                ->where('is_active', 1)
                ->with(['variants' => function($v) {
                    $v->where('is_active', 1);
                }])
                ->withMin('variants', 'price');
            
            if (!empty($entities['budget']['min']) || !empty($entities['budget']['max'])) {
                $q4->whereHas('variants', function($vq) use ($entities) {
                    $vq->where('is_active', 1);
                    if (!empty($entities['budget']['min'])) {
                        $vq->where('price', '>=', $entities['budget']['min']);
                    }
                    if (!empty($entities['budget']['max'])) {
                        $vq->where('price', '<=', $entities['budget']['max']);
                    }
                });
            }
            
            $products = $q4->orderBy('variants_min_price', 'asc')
                ->orderBy('id', 'desc')
                ->limit(8)
                ->get();
        }
        
        // QUAN TRỌNG: Nếu có budget filter và không tìm được sản phẩm → trả về rỗng (không fallback)
        // Chỉ fallback khi KHÔNG có budget filter
        if ($products->isEmpty() && empty($entities['budget']['min']) && empty($entities['budget']['max'])) {
            \Illuminate\Support\Facades\Log::info('ProductSearchTool: Still no products, returning any active products (no budget filter)');
            $products = Product::query()
                ->where('is_active', 1)
                ->with(['variants' => function($v) {
                    $v->where('is_active', 1);
                }])
                ->withMin('variants', 'price')
                ->orderBy('id', 'desc')
                ->limit(8)
                ->get();
        } elseif ($products->isEmpty() && (!empty($entities['budget']['min']) || !empty($entities['budget']['max']))) {
            // Có budget filter nhưng không tìm được → trả về rỗng
            \Illuminate\Support\Facades\Log::info('ProductSearchTool: No products found matching budget filter', [
                'budget' => $entities['budget'] ?? null,
            ]);
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
            // Lấy variant có giá thấp nhất trong số các variants active
            // QUAN TRỌNG: Nếu có budget filter, chỉ lấy variant match budget
            $activeVariants = $p->variants->where('is_active', 1);
            
            // Nếu có budget filter, filter variants theo budget
            if ($this->budgetFilter) {
                $min = $this->budgetFilter['min'] ?? null;
                $max = $this->budgetFilter['max'] ?? null;
                
                $activeVariants = $activeVariants->filter(function($v) use ($min, $max) {
                    $price = $v->price ?? 0;
                    if ($min !== null && $price < $min) return false;
                    if ($max !== null && $price > $max) return false;
                    return true;
                });
            }
            
            $minVariant = $activeVariants->sortBy('price')->first();
            $price = $minVariant->price ?? 0;
            $compare = $minVariant->compare_at_price ?? null;
            $discount = ($compare && $compare > 0 && $price > 0) 
                ? round((1 - $price / $compare) * 100) 
                : null;
            
            $url = app('router')->has('product.show') 
                ? route('product.show', $p->slug) 
                : url('/products/' . $p->slug);
            
            return [
                'id' => $p->id, // ✅ Thêm ID để AddToCartTool có thể dùng
                'slug' => $p->slug, // ✅ Thêm slug để AddToCartTool có thể dùng
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
        })
        ->filter(function($product) {
            // QUAN TRỌNG: Filter lại ở đây để đảm bảo không có sản phẩm nào vượt quá budget
            if ($this->budgetFilter) {
                $price = $product['price_min'] ?? 0;
                $min = $this->budgetFilter['min'] ?? null;
                $max = $this->budgetFilter['max'] ?? null;
                
                if ($min !== null && $price < $min) return false;
                if ($max !== null && $price > $max) return false;
            }
            
            // QUAN TRỌNG: Filter theo product_type keywords nếu có
            // Nếu database không có product_type, kiểm tra name có chứa keywords không
            if ($this->productTypeFilter) {
                $productTypeKeywords = [
                    'serum' => ['serum', 'essence', 'ampoule', 'concentrate', 'booster'],
                    'cleanser' => ['cleanser', 'sữa rửa mặt', 'rửa mặt', 'foam', 'gel cleanser', 'washing'],
                    'moisturizer' => ['moisturizer', 'kem dưỡng', 'dưỡng ẩm', 'cream', 'lotion'],
                    'sunscreen' => ['sunscreen', 'chống nắng', 'spf', 'sunblock', 'sun protection'],
                    'toner' => ['toner', 'nước hoa hồng', 'lotion'],
                    'mask' => ['mask', 'mặt nạ'],
                    'eye_cream' => ['eye cream', 'kem mắt', 'eye'],
                ];
                
                $keywords = $productTypeKeywords[$this->productTypeFilter] ?? [];
                if (!empty($keywords)) {
                    $name = Str::lower($product['name'] ?? '');
                    $matches = false;
                    foreach ($keywords as $keyword) {
                        if (Str::contains($name, Str::lower($keyword))) {
                            $matches = true;
                            break;
                        }
                    }
                    if (!$matches) {
                        return false; // Loại bỏ sản phẩm không match keywords
                    }
                }
            }
            
            return true;
        })
        ->values()->all();
    }
}

