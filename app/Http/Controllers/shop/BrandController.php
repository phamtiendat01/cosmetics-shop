<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\{Brand, Product};

class BrandController extends Controller
{
    public function show(string $slug)
    {
        $brand = Brand::where('slug', $slug)->firstOrFail();

        $q = Product::active()->where('brand_id', $brand->id)
            ->with(['brand:id,name,slug', 'category:id,name,slug'])
            ->withCount('variants')->with(['variants' => fn($x) => $x->oldest()->limit(1)])
            ->withMin('variants as min_price', 'price')
            ->withMax('variants as max_price', 'price')
            ->withMin('variants as min_compare_at_price', 'compare_at_price')
            ->withAvg('approvedReviews as avg_rating', 'rating')      // <<<
            ->withCount('approvedReviews as reviews_count');           // <<<

        match (request('sort')) {
            'price_asc'  => $q->orderBy('min_price'),
            'price_desc' => $q->orderByDesc('max_price'),
            default      => $q->latest('id'),
        };

        $products = $q->paginate(24)->withQueryString();
        return view('brand.show', compact('brand', 'products'));
    }
}
