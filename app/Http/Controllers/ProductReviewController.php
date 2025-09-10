<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    public function store(Request $req, Product $product)
    {
        $data = $req->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'content' => 'required|string|min:10',
            'user_name' => 'nullable|string|max:100',
        ]);

        $review = new ProductReview($data);
        if ($req->user()) {
            $review->user_id = $req->user()->id;
            $review->user_name = $review->user_name ?: $req->user()->name;
        }
        $product->reviews()->save($review);

        return back()->with('ok', 'Đã gửi đánh giá!')->withFragment('desc');
    }
}
