<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderReturn;

class PulseController extends Controller
{
    public function counts()
    {
        // Các trạng thái coi là "cần xử lý" của Đơn
        $ordersPending = Order::query()
            ->whereIn('status', [
                'pending',
                'cho_xac_nhan',
                'confirmed',
                'da_xac_nhan',
                'processing',
                'dang_xu_ly',
            ])->count();

        // Số yêu cầu trả hàng đang "requested"
        $returnsRequested = OrderReturn::query()
            ->where('status', 'requested')
            ->count();

        return response()->json([
            'orders_pending'    => $ordersPending,
            'returns_requested' => $returnsRequested,
        ]);
    }
}
