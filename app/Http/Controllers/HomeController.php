<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        $pool = Product::query()
            ->with(['brand:id,name,slug', 'category:id,name,slug'])
            ->with(['variants:id,product_id,price,compare_at_price'])
            ->withMin('variants as min_price', 'price')
            ->withMin('variants as min_compare', 'compare_at_price')

            // ⭐ lấy trung bình sao & số lượt chỉ từ ratedReviews (đã xử lý approved/rating null)
            ->withAvg('ratedReviews as avg_rating', 'rating')
            ->withCount('ratedReviews as reviews_count')

            ->orderByDesc('id')
            ->get();

        $calcDiscount = function ($p) {
            $price = (float) ($p->min_price ?? 0);
            $cmp   = (float) ($p->min_compare ?? 0);
            if ($cmp > 0 && $cmp > $price) {
                return (int) round(100 * ($cmp - $price) / $cmp);
            }
            return 0;
        };
        $pool->each(fn($p) => $p->discount_percent = $calcDiscount($p));

        $flashSale  = $pool->filter(fn($p) => $p->discount_percent >= 50)->take(12)->values();
        $excluded   = $flashSale->pluck('id')->all();

        $suggested  = $pool->reject(fn($p) => in_array($p->id, $excluded))
            ->filter(fn($p) => $p->discount_percent >= 30 && $p->discount_percent < 50)
            ->take(12)->values();
        $excluded   = array_merge($excluded, $suggested->pluck('id')->all());

        $newProducts = $pool->reject(fn($p) => in_array($p->id, $excluded))
            ->filter(fn($p) => $p->discount_percent < 30)
            ->take(20)->values();

        $banners = Banner::visibleNow()
            ->where('position', 'hero')
            ->orderByRaw('COALESCE(sort_order, 999999), id DESC')
            ->get(['id', 'title', 'image', 'mobile_image', 'url', 'open_in_new_tab']);

        $topBrands = Brand::query()
            ->where('is_active', 1)
            ->orderByRaw('COALESCE(sort_order, 999999), name')
            ->take(12)
            ->get(['id', 'name', 'slug', 'logo']);

        return view('home.index', compact('flashSale', 'suggested', 'newProducts', 'banners', 'topBrands'));
    }
}
