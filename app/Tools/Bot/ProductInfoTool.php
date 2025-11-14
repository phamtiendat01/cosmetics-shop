<?php

namespace App\Tools\Bot;

use App\Models\Product;
use Illuminate\Support\Str;

/**
 * ProductInfoTool - Lấy thông tin chi tiết sản phẩm
 */
class ProductInfoTool
{
    public function execute(string $message, array $context): ?array
    {
        // Extract product name/slug từ message
        $slugOrId = $this->extractProductIdentifier($message, $context);
        
        if (!$slugOrId) {
            return null;
        }
        
        $product = Product::query()
            ->when(is_numeric($slugOrId), 
                fn($q) => $q->where('id', (int)$slugOrId),
                fn($q) => $q->where('slug', $slugOrId)
            )
            ->with(['variants' => fn($v) => $v->where('is_active', 1)->select('id', 'product_id', 'price', 'compare_at_price')])
            ->where('is_active', 1)
            ->first([
                'id', 'name', 'slug', 'short_desc', 'description', 'thumbnail', 'image',
                'skin_types', 'concerns', 'ingredients', 'benefits', 'usage_instructions',
                'product_type', 'texture', 'spf', 'fragrance_free', 'cruelty_free', 'vegan'
            ]);
        
        if (!$product) {
            return [
                'found' => false,
                'message' => 'Không tìm thấy sản phẩm',
            ];
        }
        
        $minPrice = optional($product->variants)->min('price') ?? 0;
        $maxPrice = optional($product->variants)->max('price') ?? 0;
        $image = $product->thumbnail ?? $product->image ?? asset('images/placeholder.png');
        
        return [
            'found' => true,
            'id' => $product->id,
            'slug' => $product->slug,
            'name' => $product->name,
            'price_min' => (int)$minPrice,
            'price_max' => $maxPrice > $minPrice ? (int)$maxPrice : null,
            'image' => $image,
            'short_desc' => $product->short_desc ?? '',
            'description' => mb_strimwidth(strip_tags($product->description ?? ''), 0, 500, '…', 'UTF-8'),
            'skin_types' => $product->skin_types ?? [],
            'concerns' => $product->concerns ?? [],
            'ingredients' => $product->ingredients ?? [],
            'benefits' => $product->benefits ?? '',
            'usage_instructions' => $product->usage_instructions ?? '',
            'product_type' => $product->product_type ?? null,
            'texture' => $product->texture ?? null,
            'spf' => $product->spf ?? null,
            'fragrance_free' => $product->fragrance_free ?? false,
            'cruelty_free' => $product->cruelty_free ?? false,
            'vegan' => $product->vegan ?? false,
            'url' => app('router')->has('product.show') 
                ? route('product.show', $product->slug) 
                : url('/products/' . $product->slug),
        ];
    }
    
    private function extractProductIdentifier(string $message, array $context): ?string
    {
        // Check context có last_product không
        if (!empty($context['entities']['last_product'])) {
            return $context['entities']['last_product'];
        }
        
        // Extract từ message (đơn giản - có thể cải thiện)
        $words = explode(' ', Str::lower($message));
        $stopwords = ['sản phẩm', 'món', 'cái', 'loại', 'cho', 'mình', 'bạn', 'giá', 'bao nhiêu'];
        $words = array_filter($words, fn($w) => !in_array($w, $stopwords) && mb_strlen($w) >= 3);
        
        if (empty($words)) {
            return null;
        }
        
        // Tìm product theo name
        $query = implode(' ', $words);
        $product = Product::where('is_active', 1)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('slug', 'like', "%{$query}%");
            })
            ->first(['id', 'slug']);
        
        return $product?->slug ?? $product?->id;
    }
}

