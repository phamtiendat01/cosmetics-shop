<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SpinController extends Controller
{
    const MAX_SPINS_PER_DAY = 3;

    /** GET /spin/config
     *  Trả: danh sách lát bánh (để FE vẽ), số lượt còn lại hôm nay
     */
    public function config(Request $req)
    {
        $user = $req->user();
        abort_unless($user, 401, 'Vui lòng đăng nhập để tiếp tục');

        $todayCount = DB::table('spin_logs')
            ->where('user_id', $user->id)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        $slices = DB::table('wheel_slices')
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get(['id', 'label', 'type', 'coupon_id', 'weight', 'stock']);

        return response()->json([
            'ok' => true,
            'max_per_day' => self::MAX_SPINS_PER_DAY,
            'remaining'   => max(0, self::MAX_SPINS_PER_DAY - $todayCount),
            'slices'      => $slices,
        ]);
    }

    /** POST /spin
     *  Random có trọng số (server-side), giới hạn 3 lượt/ngày.
     *  Trả về id lát bánh để FE tính góc dừng, và coupon_code nếu trúng.
     */
    public function spin(Request $req)
    {
        $user = $req->user();
        abort_unless($user, 401, 'Vui lòng đăng nhập để quay');

        $todayCount = DB::table('spin_logs')
            ->where('user_id', $user->id)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        if ($todayCount >= self::MAX_SPINS_PER_DAY) {
            return response()->json(['ok' => false, 'message' => 'Bạn đã dùng hết 3 lượt hôm nay'], 429);
        }

        // Lấy lát bánh đang bật và còn stock (nếu có)
        $slices = DB::table('wheel_slices')
            ->where('is_active', 1)
            ->where(function ($q) {
                $q->whereNull('stock')->orWhere('stock', '>', 0);
            })
            ->orderBy('sort_order')
            ->get(['id', 'label', 'type', 'coupon_id', 'weight', 'stock']);

        if ($slices->isEmpty()) {
            return response()->json(['ok' => false, 'message' => 'Vòng quay chưa được cấu hình'], 503);
        }

        // Random theo trọng số
        $total = max(1, $slices->sum('weight'));
        $r = random_int(1, $total);
        $acc = 0;
        $picked = null;
        foreach ($slices as $s) {
            $acc += $s->weight;
            if ($r <= $acc) {
                $picked = $s;
                break;
            }
        }

        // Transaction: trừ stock (nếu có) + ghi log
        $logId = DB::transaction(function () use ($picked, $user) {
            if (!is_null($picked->stock)) {
                $affected = DB::table('wheel_slices')
                    ->where('id', $picked->id)
                    ->where('stock', '>', 0)
                    ->decrement('stock', 1);
                if ($affected === 0) { // Vừa hết hàng
                    $picked->type = 'none';
                    $picked->coupon_id = null;
                }
            }

            $couponCode = null;
            if ($picked->type === 'coupon' && $picked->coupon_id) {
                $coupon = DB::table('coupons')
                    ->where('id', $picked->coupon_id)
                    ->where('is_active', 1)
                    ->where(function ($q) {
                        $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                    })
                    ->first(['id', 'code']);
                $couponCode = $coupon?->code;
                // Nếu admin vô hiệu hóa ngay lúc quay, coi như hụt
                if (!$couponCode) {
                    $picked->type = 'none';
                    $picked->coupon_id = null;
                }
            }

            return DB::table('spin_logs')->insertGetId([
                'user_id'        => $user->id,
                'wheel_slice_id' => $picked->id,
                'coupon_id'      => $picked->coupon_id,
                'coupon_code'    => $couponCode,
                'meta'           => json_encode(['ip' => request()->ip(), 'ua' => request()->userAgent()]),
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        });

        $log = DB::table('spin_logs')->where('id', $logId)->first(['wheel_slice_id', 'coupon_code']);

        return response()->json([
            'ok' => true,
            'log_id'        => $logId,
            'wheel_slice_id' => $log->wheel_slice_id,
            'coupon_code'   => $log->coupon_code, // FE hiện popup + nút "Lưu mã"
        ]);
    }

    /** POST /spin/save
     *  Lưu coupon của lượt quay vừa rồi vào bảng user_coupons (nút "Lưu mã")
     */
    public function save(Request $req)
    {
        $user = $req->user();
        abort_unless($user, 401, 'Vui lòng đăng nhập');

        $data = $req->validate(['log_id' => ['required', 'integer']]);

        $result = \DB::transaction(function () use ($user, $data) {
            // Khóa bản ghi lượt quay để chống double-save
            $log = \DB::table('spin_logs')
                ->where('id', $data['log_id'])
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first(['id', 'coupon_id', 'coupon_code', 'saved_at']);

            if (!$log || !$log->coupon_id || !$log->coupon_code) {
                return ['ok' => false, 'message' => 'Không có mã để lưu', 'already' => false];
            }

            // Nếu đã lưu trước đó -> idempotent
            if (!is_null($log->saved_at)) {
                return ['ok' => true, 'message' => 'Mã này đã được lưu trước đó', 'already' => true];
            }

            // Tăng times nếu đã có, ngược lại tạo mới times=1
            $affected = \DB::table('user_coupons')
                ->where('user_id', $user->id)
                ->where('coupon_id', $log->coupon_id)
                ->increment('times', 1, [
                    'source'     => 'spin',
                    'saved_at'   => now(),
                    'updated_at' => now(),
                ]);

            if ($affected === 0) {
                \DB::table('user_coupons')->insert([
                    'user_id'    => $user->id,
                    'coupon_id'  => $log->coupon_id,
                    'source'     => 'spin',
                    'times'      => 1,
                    'saved_at'   => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Đánh dấu log đã lưu (idempotent ở tầng DB)
            \DB::table('spin_logs')->where('id', $log->id)->update(['saved_at' => now()]);

            return ['ok' => true, 'message' => 'Đã lưu mã vào tài khoản', 'already' => false];
        });

        $status = $result['ok'] ? 200 : 400;
        return response()->json($result, $status);
    }
}
