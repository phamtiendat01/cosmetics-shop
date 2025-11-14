<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;


class CouponController extends Controller
{
    public function index(Request $r)
    {
        $uid = $r->user()->id;

        $hasTimes     = Schema::hasColumn('user_coupons', 'times');
        $hasCouponId  = Schema::hasColumn('user_coupons', 'coupon_id');
        $hasCouponCode = Schema::hasColumn('user_coupons', 'coupon_code');

        $q = DB::table('user_coupons as uc')
            ->join('coupons as c', function ($j) use ($hasCouponId, $hasCouponCode) {
                if ($hasCouponId)  $j->on('c.id', '=', 'uc.coupon_id');
                if ($hasCouponCode) $j->on('c.code', '=', 'uc.coupon_code');
            })
            ->where('uc.user_id', $uid)
            ->select('c.*');

        if ($hasTimes) {
            // dùng times của user_coupons, chỉ lấy cái > 0
            $q->addSelect('uc.times')->where('uc.times', '>', 0);
        } else {
            // không có times -> mỗi dòng tương ứng 1 lượt
            $q->addSelect(DB::raw('COUNT(*) as times'))
                ->groupBy(
                    'c.id',
                    'c.code',
                    'c.name',
                    'c.description',
                    'c.discount_type',
                    'c.discount_value',
                    'c.max_discount',
                    'c.min_order_total',
                    'c.starts_at',
                    'c.ends_at',
                    'c.is_active'
                );
        }

        $coupons = $q->orderBy('c.starts_at', 'desc')->get();
        return view('account.coupons.index', compact('coupons'));
    }
}
