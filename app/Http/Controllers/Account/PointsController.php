<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\UserPoint;
use App\Models\PointTransaction;
use App\Services\PointsService;

class PointsController extends Controller
{
    public function index(Request $r)
    {
        $u = $r->user();
        $balance = optional(UserPoint::find($u->id))->balance ?? 0;

        $history = PointTransaction::where('user_id', $u->id)
            ->orderByDesc('id')->limit(50)->get();

        // Tổng điểm earn đã xác nhận trong 30 ngày gần nhất
        $earnLast30 = (int) PointTransaction::where('user_id', $u->id)
            ->where('type', 'earn')->where('status', 'confirmed')
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('delta');

        // Tổng điểm đã đổi (burn) – xác nhận
        $burnTotal = (int) abs(PointTransaction::where('user_id', $u->id)
            ->where('type', 'burn')->where('status', 'confirmed')
            ->sum('delta'));

        return view('account.points.index', compact('balance', 'history', 'earnLast30', 'burnTotal'));
    }

    public function redeem(Request $r)
    {
        $u = $r->user();
        $data = $r->validate(['points' => 'required|integer|min:1']);
        $points = (int) $data['points'];

        // Tối thiểu đổi
        if ($points < 100) {
            return back()->with('error', 'Tối thiểu đổi 100 xu.');
        }

        // 1 xu = 10 VND
        $discountVnd = $points * 10;
        $minTotal    = max(100000, (int) round($discountVnd / 0.3)); // giới hạn ~30% đơn
        $code        = 'PTS-' . Str::upper(Str::random(8));

        try {
            DB::transaction(function () use ($u, $points, $discountVnd, $minTotal, $code) {
                // 1) Trừ điểm (ném lỗi nếu không đủ)
                PointsService::burn($u->id, $points, null, ['reason' => 'redeem']);

                // 2) Chuẩn bị dữ liệu coupon theo đúng schema hiện có
                $now  = now();
                $data = [
                    'code'        => $code,
                    'name'        => 'Đổi điểm - ' . number_format($discountVnd) . 'đ',
                    'description' => 'Mã được tạo từ xu tích điểm',
                    'is_active'   => 1,
                    'usage_limit' => 1,
                    'starts_at'   => $now,
                    // cột "ends_at" hoặc "expires_at" tuỳ schema
                ];

                // Kiểu giảm giá: schema A (discount_type/value) hoặc schema B (type/value)
                if (Schema::hasColumn('coupons', 'discount_type')) {
                    $data['discount_type']  = 'fixed';
                    $data['discount_value'] = $discountVnd;
                    if (Schema::hasColumn('coupons', 'max_discount')) {
                        $data['max_discount'] = null;
                    }
                } else {
                    // fallback schema B
                    $data['type']  = 'fixed';
                    $data['value'] = $discountVnd;
                }

                // Đơn tối thiểu: "min_order_total" (A) hoặc "min_total" (B)
                if (Schema::hasColumn('coupons', 'min_order_total')) {
                    $data['min_order_total'] = $minTotal;
                } elseif (Schema::hasColumn('coupons', 'min_total')) {
                    $data['min_total'] = $minTotal;
                }

                // Phạm vi áp dụng: "applied_to" (A) hoặc "scope" (B)
                if (Schema::hasColumn('coupons', 'applied_to')) {
                    $data['applied_to'] = 'order';
                    if (Schema::hasColumn('coupons', 'applies_to_ids')) {
                        $data['applies_to_ids'] = null;
                    }
                } elseif (Schema::hasColumn('coupons', 'scope')) {
                    $data['scope'] = 'order';
                    if (Schema::hasColumn('coupons', 'keys')) {
                        $data['keys'] = json_encode([]);
                    }
                }

                // Stack/First order flags: schema A hoặc B
                if (Schema::hasColumn('coupons', 'is_stackable')) {
                    $data['is_stackable'] = 0;
                } elseif (Schema::hasColumn('coupons', 'is_stack_with_free_ship')) {
                    $data['is_stack_with_free_ship'] = 0;
                }

                if (Schema::hasColumn('coupons', 'first_order_only')) {
                    $data['first_order_only'] = 0;
                } elseif (Schema::hasColumn('coupons', 'is_first_order_only')) {
                    $data['is_first_order_only'] = 0;
                }

                // Hạn dùng: "ends_at" (A) hoặc "expires_at" (B)
                if (Schema::hasColumn('coupons', 'ends_at')) {
                    $data['ends_at'] = $now->copy()->addDays(14);
                } elseif (Schema::hasColumn('coupons', 'expires_at')) {
                    $data['expires_at'] = $now->copy()->addDays(14);
                }

                // Sort/order nếu có
                if (Schema::hasColumn('coupons', 'sort_order')) {
                    $data['sort_order'] = 0;
                }

                // Per-user limit nếu có
                if (Schema::hasColumn('coupons', 'usage_limit_per_user')) {
                    $data['usage_limit_per_user'] = 1;
                }

                // timestamps
                $data['created_at'] = $now;
                $data['updated_at'] = $now;

                // 3) Tạo coupon
                $couponId = DB::table('coupons')->insertGetId($data);

                // 4) Sinh mã vào coupon_codes
                $codeRow = [
                    'coupon_id'  => $couponId,
                    'code'       => $code,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                if (Schema::hasColumn('coupon_codes', 'is_used')) {
                    $codeRow['is_used'] = 0;
                }
                if (Schema::hasColumn('coupon_codes', 'used_by')) {
                    $codeRow['used_by'] = null;
                }
                if (Schema::hasColumn('coupon_codes', 'used_at')) {
                    $codeRow['used_at'] = null;
                }
                DB::table('coupon_codes')->insert($codeRow);

                // 5) (Tuỳ schema) Gắn vào “Mã giảm giá của tôi”
                if (Schema::hasTable('user_coupons')) {
                    $uc = [
                        'user_id'    => $u->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    if (Schema::hasColumn('user_coupons', 'coupon_id')) {
                        $uc['coupon_id'] = $couponId;
                    } elseif (Schema::hasColumn('user_coupons', 'coupon_code')) {
                        $uc['coupon_code'] = $code;
                    }
                    if (Schema::hasColumn('user_coupons', 'source')) {
                        $uc['source'] = 'points';
                    }
                    if (Schema::hasColumn('user_coupons', 'times')) {
                        $uc['times'] = 1;
                    }
                    if (Schema::hasColumn('user_coupons', 'saved_at')) {
                        $uc['saved_at'] = $now;
                    }
                    DB::table('user_coupons')->insert($uc);
                }
            });
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('ok', "Đã tạo mã {$code}. Vào mục “Mã giảm giá” để sử dụng.");
    }
}
