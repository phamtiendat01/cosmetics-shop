<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ShipVoucherController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        $rows = DB::table('user_shipping_vouchers as usv')
            ->join('shipping_vouchers as sv', 'sv.id', '=', 'usv.shipping_voucher_id')
            ->leftJoin('shipping_voucher_usages as u', function ($j) {
                $j->on('u.shipping_voucher_id', '=', 'sv.id')->on('u.user_id', '=', 'usv.user_id');
            })
            ->where('usv.user_id', $userId)
            ->selectRaw('usv.id, usv.code, usv.saved_at, usv.times,
                         sv.title, sv.discount_type, sv.amount, sv.max_discount, sv.min_order,
                         sv.start_at, sv.end_at, sv.regions, sv.carriers, sv.is_active,
                         COUNT(u.id) as used_count')
            ->groupBy('usv.id', 'usv.code', 'usv.saved_at', 'usv.times', 'sv.title', 'sv.discount_type', 'sv.amount', 'sv.max_discount', 'sv.min_order', 'sv.start_at', 'sv.end_at', 'sv.regions', 'sv.carriers', 'sv.is_active')
            ->orderByDesc('usv.saved_at')
            ->get();

        return view('account.ship-vouchers', [
            'items' => $rows,
            'now'   => now(),
        ]);
    }
}
