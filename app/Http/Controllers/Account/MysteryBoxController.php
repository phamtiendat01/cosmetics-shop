<?php

namespace App\Http\Controllers\Account;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class MysteryBoxController extends Controller
{
    const MAX_PLAYS_PER_DAY = 2; // 2 lượt/ngày

    public function index()
    {
        return view('game.mystery');
    }

    /** GET /mystery/config
     *  Trả lượt còn lại + danh sách 9 ô để FE render (ẩn logic kết quả)
     */
    public function config(Request $req)
    {
        $user = $req->user();
        abort_unless($user, 401, 'Vui lòng đăng nhập');

        $playsToday = DB::table('shipgame_logs')
            ->where('user_id', $user->id)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        // 9 “ô” hiển thị (server trả id & emoji; logic trúng ẩn hoàn toàn trong /mystery/play)
        $boxes = collect(range(1, 9))->map(fn($i) => [
            'id'    => $i,
            'emoji' => ['📦', '🎁', '🚚', '🛵', '📦', '🎁', '🚚', '📦', '🎁'][$i - 1] ?? '📦',
            'hint'  => 'Chọn để mở quà',
        ]);

        return response()->json([
            'ok'        => true,
            'remaining' => max(0, self::MAX_PLAYS_PER_DAY - $playsToday),
            'boxes'     => $boxes,
        ]);
    }
    public function save(Request $req, int $logId)
    {
        $user = $req->user();
        abort_unless($user, 401, 'Vui lòng đăng nhập');

        $log = DB::table('shipgame_logs')
            ->where('id', $logId)
            ->where('user_id', $user->id)
            ->first();

        if (!$log || $log->result_type !== 'voucher' || !$log->voucher_code) {
            return response()->json(['ok' => false, 'message' => 'Không có mã hợp lệ để lưu'], 400);
        }

        if ($log->saved_at) {
            return response()->json(['ok' => true, 'already' => true, 'message' => 'Mã đã được lưu trước đó']);
        }

        // Ghi vào ví user: user_shipping_vouchers
        DB::transaction(function () use ($log, $user) {
            $affected = DB::table('user_shipping_vouchers')
                ->where('user_id', $user->id)
                ->where('shipping_voucher_id', $log->shipping_voucher_id)
                ->where('code', $log->voucher_code)
                ->increment('times', 1, [
                    'source'     => 'game',
                    'saved_at'   => now(),
                    'updated_at' => now(),
                ]);

            if ($affected === 0) {
                DB::table('user_shipping_vouchers')->insert([
                    'user_id'             => $user->id,
                    'shipping_voucher_id' => $log->shipping_voucher_id,
                    'code'                => $log->voucher_code,
                    'source'              => 'game',
                    'times'               => 1,
                    'saved_at'            => now(),
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
            }

            DB::table('shipgame_logs')->where('id', $log->id)->update(['saved_at' => now()]);
        });

        return response()->json(['ok' => true, 'already' => false, 'message' => 'Đã lưu mã vào ví của bạn']);
    }


    /** POST /mystery/play
     *  Chọn ngẫu nhiên (server-side) 1 trong 9 ô; khoảng 3 ô trúng (mã vận chuyển đang active), còn lại hụt.
     *  Lưu log để giới hạn lượt/ngày.
     */
    public function play(Request $req)
    {
        $user = $req->user();
        abort_unless($user, 401, 'Vui lòng đăng nhập');

        // Giới hạn lượt/ngày
        $playsToday = DB::table('shipgame_logs')
            ->where('user_id', $user->id)
            ->whereDate('created_at', now()->toDateString())
            ->count();
        if ($playsToday >= self::MAX_PLAYS_PER_DAY) {
            return response()->json(['ok' => false, 'message' => 'Bạn đã hết lượt hôm nay'], 429);
        }

        // === LẤY MÃ TỪ ADMIN (shipping_vouchers) ===
        // Chỉ chọn cột chắc chắn có; KHÔNG chọn min_subtotal để tránh lỗi
        $q = DB::table('shipping_vouchers')
            ->where('is_active', 1)
            ->where(function ($q) {
                $q->whereNull('start_at')->orWhere('start_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', now());
            });

        // Nếu có cột priority thì ưu tiên, còn không thì sort theo id
        if (\Illuminate\Support\Facades\Schema::hasColumn('shipping_vouchers', 'priority')) {
            $q->orderByDesc('priority')->orderByDesc('id');
        } else {
            $q->orderByDesc('id');
        }

        $vouchers = $q->limit(3)->get([
            'id',
            'code',
            'title',
            'discount_type',
            'amount',
            'max_discount'
        ]);

        // Tạo 9 ô: 3 ô voucher + 6 ô none
        $grid = array_fill(0, 9, ['type' => 'none', 'box_no' => 0, 'voucher_id' => null, 'voucher_code' => null]);
        foreach (range(0, 8) as $i) $grid[$i]['box_no'] = $i + 1;

        foreach ($vouchers as $i => $v) {
            if ($i > 2) break;
            $grid[$i]['type'] = 'voucher';
            $grid[$i]['voucher_id']   = (int)$v->id;
            $grid[$i]['voucher_code'] = strtoupper((string)$v->code);
        }
        shuffle($grid);

        // Chọn 1 ô ngẫu nhiên
        $picked = $grid[random_int(0, 8)];

        // Ghi log
        $logId = DB::table('shipgame_logs')->insertGetId([
            'user_id'             => $user->id,
            'box_no'              => $picked['box_no'],
            'result_type'         => $picked['type'],
            'shipping_voucher_id' => $picked['voucher_id'],
            'voucher_code'        => $picked['voucher_code'],
            'meta'                => json_encode(['ip' => request()->ip(), 'ua' => request()->userAgent()]),
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        return response()->json([
            'ok'           => true,
            'log_id'       => $logId,
            'box_id'       => $picked['box_no'],
            'result_type'  => $picked['type'],
            'voucher_code' => $picked['voucher_code'],
            'message'      => $picked['type'] === 'voucher'
                ? 'Chúc mừng! Bạn trúng mã vận chuyển từ Admin'
                : 'Hụt rồi, thử lại nhé!',
        ]);
    }
}
