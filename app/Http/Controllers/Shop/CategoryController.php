<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\{Category, Product};

class CategoryController extends Controller
{
    public function show(string $slug)
    {
        $cat = Category::where('slug', $slug)->with('parent')->firstOrFail();

        $q = Product::active()
            ->where('category_id', $cat->id)
            ->with(['brand:id,name,slug', 'category:id,name,slug'])
            ->withCount('variants')
            ->with(['variants' => fn($x) => $x->oldest()->limit(1)])
            ->withMin('variants as min_price', 'price')
            ->withMax('variants as max_price', 'price')
            ->withMin('variants as min_compare_at_price', 'compare_at_price')
            ->withAvg('approvedReviews as avg_rating', 'rating')      // <<<
            ->withCount('approvedReviews as reviews_count')           // <<<
            ->withAvg('approvedReviews as avg_rating', 'rating')
            ->withCount('approvedReviews as reviews_count');

        // FILTERS
        if ($brand = request('brand_id')) {
            $q->where('brand_id', $brand);
        }
        if ($brandIds = request('brand_ids')) {
            $q->whereIn('brand_id', (array)$brandIds);
        }
        if ($min = request('min')) {
            $q->whereHas('variants', fn($qq) => $qq->where('price', '>=', (int)$min));
        }
        if ($max = request('max')) {
            $q->whereHas('variants', fn($qq) => $qq->where('price', '<=', (int)$max));
        }
        if ($rating = request('rating')) {
            $q->having('avg_rating', '>=', (int)$rating); // dùng having vì là select tính toán
        }
        if (request()->boolean('in_stock')) {
            $q->whereHas('variants.inventory', fn($qq) => $qq->where('qty_in_stock', '>', 0));
        }

        // SORT
        match (request('sort')) {
            'price_asc'  => $q->orderBy('min_price'),
            'price_desc' => $q->orderByDesc('max_price'),
            default      => $q->latest('id'),
        };

        $products = $q->paginate(24)->withQueryString();

        return view('category.show', compact('cat', 'products'));
    }
}
