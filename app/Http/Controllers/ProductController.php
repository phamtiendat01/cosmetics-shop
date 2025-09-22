<?php

namespace App\Http\Controllers;

use App\Models\Product;

class ProductController extends Controller
{
    public function show(string $slug)
    {
        $product = Product::active()
            ->where('slug', $slug)
            ->with([
                'brand:id,name,slug',
                'category:id,name,slug',
                'variants' => fn($q) => $q->active()->orderBy('price'),
                'approvedReviews'
            ])->firstOrFail();

        // related: cùng danh mục, khác id, mới nhất
        $related = Product::active()
            ->where('category_id', $product->category_id)
            ->where('id', '<>', $product->id)
            ->withMin('variants as min_price', 'price')
            ->withMin('variants as min_compare_at_price', 'compare_at_price')
            // >>> THÊM 2 DÒNG NÀY <<<
            ->withAvg('approvedReviews as avg_rating', 'rating')
            ->withCount('approvedReviews as reviews_count')
            ->latest('id')->take(12)
            ->get(['id', 'name', 'slug', 'image', 'thumbnail', 'brand_id', 'category_id']);


        return view('product.show', compact('product', 'related'));
    }
}
