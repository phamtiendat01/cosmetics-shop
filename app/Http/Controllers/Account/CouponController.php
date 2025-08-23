<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CouponController extends Controller
{
    // GET /account/coupons – mã khả dụng (đang active & trong hạn)
    public function index()
    {
        $now = now();
        $coupons = DB::table('coupons')
            ->where('is_active', 1)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->orderBy('id', 'desc')
            ->limit(50)
            ->get(['id', 'code', 'name', 'discount_type', 'discount_value', 'max_discount', 'min_order_total', 'applied_to', 'applies_to_ids', 'starts_at', 'ends_at']);
        return response()->json(['coupons' => $coupons]);
    }

    // GET /account/coupons/history – mã đã dùng của tôi
    public function history()
    {
        $userId = Auth::id();
        $history = DB::table('coupon_redemptions as cr')
            ->leftJoin('orders as o', 'o.id', '=', 'cr.order_id')
            ->where('cr.user_id', $userId)
            ->orderByDesc('cr.redeemed_at')
            ->get(['cr.code_snapshot', 'cr.discount_amount', 'cr.redeemed_at', 'o.code as order_code']);
        return response()->json(['history' => $history]);
    }
}
