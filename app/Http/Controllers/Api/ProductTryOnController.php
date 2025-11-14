<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\TryOnAsset;
use App\Models\TryOnSession;
use Illuminate\Http\Request;

class ProductTryOnController extends Controller
{
    // GET /api/products/{product}/tryon/shades
    public function shades(Product $product)
    {
        // lấy các biến thể bật try-on
        $variants = $product->variants()
            ->where('tryon_enabled', true)
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['id', 'name', 'shade_name', 'shade_hex', 'tryon_effect', 'tryon_alpha']);

        return response()->json([
            'ok' => true,
            'product_id' => $product->id,
            'variants' => $variants,
        ]);
    }

    // GET /api/tryon/assets?effect=lipstick
    public function assets(Request $r)
    {
        $effect = $r->query('effect');
        $q = TryOnAsset::query()->where('is_active', true);
        if ($effect) $q->where('effect', $effect);

        $assets = $q->orderBy('id')->get(['id', 'effect', 'title', 'mask_url', 'config']);
        return response()->json(['ok' => true, 'assets' => $assets]);
    }

    // POST /api/tryon/sessions
    // body: { product_id, product_variant_id?, effect?, shade_hex?, match_score?, context? }
    public function storeSession(Request $r)
    {
        $data = $r->validate([
            'product_id'         => 'required|integer|exists:products,id',
            'product_variant_id' => 'nullable|integer|exists:product_variants,id',
            'effect'             => 'nullable|string|max:24',
            'shade_hex'          => 'nullable|string|max:12',
            'match_score'        => 'nullable|numeric',
            'context'            => 'nullable|array',
        ]);

        $data['user_id'] = auth()->id();

        $session = TryOnSession::create($data);

        return response()->json(['ok' => true, 'id' => $session->id]);
    }
}
