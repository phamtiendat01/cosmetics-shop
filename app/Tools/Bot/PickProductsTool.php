<?php

namespace App\Tools\Bot;

use App\Models\Product;
use Illuminate\Support\Facades\Schema;

/**
 * PickProductsTool - Gợi ý sản phẩm ngẫu nhiên/featured
 */
class PickProductsTool
{
    public function execute(string $message, array $context): ?array
    {
        $q = Product::query()
            ->where('is_active', 1)
            ->with(['variants' => fn($v) => $v->where('is_active', 1)]);
        
        // Check stock
        $vCols = Schema::getColumnListing('product_variants');
        $qtyCol = null;
        foreach (['stock', 'quantity', 'qty', 'inventory', 'inventory_qty'] as $c) {
            if (in_array($c, $vCols)) {
                $qtyCol = $c;
                break;
            }
        }
        if ($qtyCol) {
            $q->whereHas('variants', fn($v) => $v->where($qtyCol, '>', 0));
        }
        
        // Order by featured/sold/views
        if (in_array('is_featured', Schema::getColumnListing('products'))) {
            $q->orderByDesc('is_featured');
        }
        if (in_array('sold_count', Schema::getColumnListing('products'))) {
            $q->orderByDesc('sold_count');
        }
        if (in_array('views', Schema::getColumnListing('products'))) {
            $q->orderByDesc('views');
        }
        
        $q->withMin('variants', 'price')
            ->orderBy('variants_min_price')
            ->limit(8);
        
        $products = $q->get();
        
        if ($products->isEmpty()) {
            $products = Product::query()
                ->where('is_active', 1)
                ->inRandomOrder()
                ->limit(8)
                ->get();
        }
        
        return $this->mapProducts($products);
    }
    
    private function mapProducts($products): array
    {
        return $products->map(function ($p) {
            $minVariant = optional($p->variants)->sortBy('price')->first();
            $price = $minVariant->price ?? 0;
            
            $url = app('router')->has('product.show') 
                ? route('product.show', $p->slug) 
                : url('/products/' . $p->slug);
            
            return [
                'url' => $url,
                'image' => $p->thumbnail ?? $p->image ?? asset('images/placeholder.png'),
                'name' => $p->name,
                'price_min' => (int)$price,
                'compare_at' => null,
                'discount' => null,
            ];
        })->values()->all();
    }
}

