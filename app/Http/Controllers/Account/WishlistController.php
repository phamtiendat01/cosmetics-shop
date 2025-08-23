<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class WishlistController extends Controller
{
    // GET /account/wishlist
    public function index(Request $request)
    {
        $ids = $request->session()->get('wishlist', []); // MVP: lưu session
        $variants = [];
        if ($ids) {
            $variants = DB::table('product_variants as pv')
                ->join('products as p', 'p.id', '=', 'pv.product_id')
                ->whereIn('pv.id', $ids)
                ->select('pv.id', 'pv.name as variant_name', 'pv.price', 'p.name as product_name', 'p.slug')
                ->get();
        }
        return response()->json(['items' => $variants, 'count' => count($ids)]);
    }

    // POST /account/wishlist
    public function store(Request $request)
    {
        $data = $request->validate(['variant_id' => 'required|integer']);
        $wishlist = $request->session()->get('wishlist', []);
        if (!in_array($data['variant_id'], $wishlist)) {
            $wishlist[] = $data['variant_id'];
            $request->session()->put('wishlist', $wishlist);
        }
        return $this->index($request);
    }

    // DELETE /account/wishlist/{variant_id}
    public function destroy(Request $request, $variant_id)
    {
        $wishlist = array_values(array_filter($request->session()->get('wishlist', []), fn($id) => (int)$id !== (int)$variant_id));
        $request->session()->put('wishlist', $wishlist);
        return $this->index($request);
    }
}
