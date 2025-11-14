<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Review;
use App\Http\Requests\StoreItemReviewRequest;

class OrderReviewController extends Controller
{
    public function create(\App\Models\Order $order, \App\Models\OrderItem $item)
    {
        $this->authorize('create', $item); // ✅
        return view('account.reviews.create', [
            'order'   => $order,
            'item'    => $item,
            'product' => $item->product,
        ]);
    }

    public function store(\App\Http\Requests\StoreItemReviewRequest $req, \App\Models\Order $order, \App\Models\OrderItem $item)
    {
        $this->authorize('create', $item); // ✅

        \App\Models\Review::create([
            'product_id'        => $item->product_id,
            'user_id'           => $req->user()->id,
            'order_item_id'     => $item->id,
            'rating'            => (int) $req->input('rating'),
            'title'             => $req->input('title'),
            'content'           => $req->input('content'),
            'is_approved'       => false,
            'verified_purchase' => true,
        ]);

        return redirect()->route('account.orders.show', $order)
            ->with('ok', 'Đã gửi đánh giá. Cảm ơn bạn!');
    }
}
