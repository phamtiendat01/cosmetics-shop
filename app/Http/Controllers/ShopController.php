<?php

namespace App\Http\Controllers;

use App\Models\Product;

class ShopController extends Controller
{
    public function index()
    {
        $qStr = trim(request('q', ''));
        $q = Product::active()
            ->with(['brand:id,name,slug', 'category:id,name,slug'])
            ->withCount('variants')->with(['variants' => fn($x) => $x->oldest()->limit(1)])
            ->withMin('variants as min_price', 'price')
            ->withMax('variants as max_price', 'price')
            ->withMin('variants as min_compare_at_price', 'compare_at_price')
            ->withAvg('approvedReviews as avg_rating', 'rating')
            ->withCount('approvedReviews as reviews_count');

        if ($qStr !== '') {
            $q->where(function ($qq) use ($qStr) {
                $qq->where('name', 'like', '%' . $qStr . '%')
                    ->orWhereHas('brand', fn($b) => $b->where('name', 'like', '%' . $qStr . '%'))
                    ->orWhereHas('category', fn($c) => $c->where('name', 'like', '%' . $qStr . '%'));
            });
        }

        // filters giống Category
        if ($min = request('min')) $q->whereHas('variants', fn($qq) => $qq->where('price', '>=', (int)$min));
        if ($max = request('max')) $q->whereHas('variants', fn($qq) => $qq->where('price', '<=', (int)$max));
        if ($brand = request('brand_id')) $q->where('brand_id', $brand);
        if ($brandIds = request('brand_ids')) $q->whereIn('brand_id', (array)$brandIds);
        if ($rating = request('rating')) $q->having('avg_rating', '>=', (int)$rating);
        if (request()->boolean('in_stock')) $q->whereHas('variants.inventory', fn($qq) => $qq->where('qty_in_stock', '>', 0));

        match (request('sort')) {
            'price_asc' => $q->orderBy('min_price'),
            'price_desc' => $q->orderByDesc('max_price'),
            default => $q->latest('id'),
        };

        $products = $q->paginate(24)->withQueryString();
        return view('shop.index', ['products' => $products, 'q' => $qStr]);
    }
}
