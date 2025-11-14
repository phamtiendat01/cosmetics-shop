<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;

class WishlistController extends Controller
{
    // Trang danh sách yêu thích (render Blade)
    public function index(Request $request)
    {
        $ids = array_values(array_unique(array_map('intval', (array) $request->session()->get('wishlist', []))));
        $products = !empty($ids)
            ? Product::whereIn('id', $ids)->where('is_active', 1)->get()
            : collect();

        return view('account.wishlist', compact('products'));
    }

    // API: thêm / bỏ yêu thích (SESSION JSON)
    public function toggle(Request $request)
    {
        $id = (int) $request->integer('product_id');

        $wishlist = collect((array) $request->session()->get('wishlist', []))
            ->map(fn($v) => (int) $v)
            ->unique()
            ->values();

        $action = 'added';
        if ($wishlist->contains($id)) {
            $wishlist = $wishlist->reject(fn($v) => $v === $id)->values();
            $action = 'removed';
        } else {
            if ($id > 0) {
                $wishlist = $wishlist->push($id)->unique()->values();
            }
        }

        $request->session()->put('wishlist', $wishlist->all());

        return response()->json([
            'ok'    => true,
            'action' => $action,
            'id'    => $id,
            'count' => $wishlist->count(),
        ]);
    }

    // API: lấy số lượng
    public function count(Request $request)
    {
        return response()->json([
            'ok'    => true,
            'count' => count((array) $request->session()->get('wishlist', [])),
        ]);
    }
}
