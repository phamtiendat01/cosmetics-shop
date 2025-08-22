<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Brand;

class HomeController extends Controller
{
    public function index()
    {
        // Top brands cho strip
        $topBrands = Brand::active()
            ->orderByRaw('COALESCE(sort_order,999999), name')
            ->take(12)
            ->get(['id', 'name', 'slug', 'logo']);

        // Sản phẩm gợi ý + mới (đã có UI grid + carousel)
        $baseSelect = ['id', 'name', 'slug', 'image', 'brand_id', 'category_id'];
        $with = [
            'brand:id,name,slug',
            'category:id,name,slug',
        ];

        $suggested = Product::active()
            ->with($with)
            ->withCount('variants')                                // để Quick-Add xác định 1 biến thể
            ->with(['variants' => fn($q) => $q->oldest()->limit(1)]) // lấy 1 variant đầu cho quick-add
            ->withMin('variants as min_price', 'price')
            ->withMin('variants as min_compare_at_price', 'compare_at_price')
            ->latest('id')->take(12)->get($baseSelect);

        $newProducts = Product::active()
            ->with($with)->withCount('variants')->with(['variants' => fn($q) => $q->oldest()->limit(1)])
            ->withMin('variants as min_price', 'price')
            ->withMin('variants as min_compare_at_price', 'compare_at_price')
            ->latest('id')->take(20)->get($baseSelect);

        // Hero slides: nếu bạn đã có bảng banners thì lấy từ đó; chưa có thì để null và UI hiển thị placeholder
        $heroSlides = []; // hoặc Banner::active()->where('placement','home-hero')->orderBy('sort_order')->get();

        return view('home.index', compact('topBrands', 'suggested', 'newProducts', 'heroSlides'));
    }
}
