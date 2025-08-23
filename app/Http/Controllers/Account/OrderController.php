<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    // GET /account/orders
    public function index(Request $request)
    {
        $userId = Auth::id();

        $orders = DB::table('orders')
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->orderByDesc('placed_at')
            ->select('id', 'code', 'status', 'payment_status', 'payment_method', 'grand_total', 'placed_at')
            ->limit(50)
            ->get();

        // tuỳ bạn: trả về view('account.orders.index', compact('orders'))
        return response()->json(['orders' => $orders]);
    }

    // GET /account/orders/{id}
    public function show($id)
    {
        $order = DB::table('orders')->where('id', $id)->first();
        if (!$order) return abort(404);

        $items = DB::table('order_items')
            ->where('order_id', $id)
            ->select('product_name_snapshot', 'variant_name_snapshot', 'unit_price', 'qty', 'line_total')
            ->get();

        return response()->json(['order' => $order, 'items' => $items]);
    }

    // POST /account/orders/{id}/cancel
    public function cancel($id)
    {
        $order = DB::table('orders')->where('id', $id)->first();
        if (!$order) return response()->json(['message' => 'Không tìm thấy đơn'], 404);
        if (!in_array($order->status, ['pending', 'confirmed', 'processing'])) {
            return response()->json(['message' => 'Đơn không thể hủy ở trạng thái hiện tại'], 422);
        }
        DB::table('orders')->where('id', $id)->update(['status' => 'cancelled', 'updated_at' => now()]);
        DB::table('order_events')->insert([
            'order_id' => $id,
            'type'     => 'status_changed',
            'old'      => json_encode(['status' => $order->status]),
            'new'      => json_encode(['status' => 'cancelled']),
            'meta'     => json_encode(['by' => 'customer']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['status' => 'ok']);
    }
}
