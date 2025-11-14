<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ShippingVoucherController extends Controller
{
    /**
     * Áp mã vận chuyển vào phiên checkout (session).
     * Yêu cầu: mã phải thuộc ví của user, còn hiệu lực, thoả điều kiện đơn tối thiểu.
     * Trả về: discount áp vào phí ship và phí ship sau khi trừ.
     */
    public function apply(Request $r)
    {
        $r->validate([
            'code'         => 'required|string|max:64',
            'subtotal'     => 'nullable|integer',
            'shipping_fee' => 'nullable|integer',
        ]);

        $code   = strtoupper(trim($r->input('code')));
        $userId = (int) auth()->id();

        // Lấy items để tính subtotal (fallback nếu FE không gửi lên)
        $items = (array) session('cart.items', []);
        $subtotal = (int) $r->input('subtotal', 0);
        if ($subtotal <= 0) {
            foreach ($items as $it) {
                $subtotal += (int)($it['price'] ?? 0) * (int)($it['qty'] ?? 1);
            }
        }

        // Phí ship hiện tại (sau khi chọn địa chỉ)
        $shippingFee = (int) $r->input('shipping_fee', (int) session('cart.shipping_fee', 0));

        // Mã phải nằm trong ví người dùng (user_shipping_vouchers) và join tới shipping_vouchers
        $row = DB::table('user_shipping_vouchers as usv')
            ->join('shipping_vouchers as sv', 'sv.id', '=', 'usv.shipping_voucher_id')
            ->leftJoin('shipping_voucher_usages as u', function ($j) use ($userId) {
                $j->on('u.shipping_voucher_id', '=', 'sv.id')
                    ->on('u.user_id', '=', 'usv.user_id');
            })
            ->where('usv.user_id', $userId)
            ->where('usv.code', $code)
            ->selectRaw('usv.id as user_sv_id, usv.times,
                         sv.id, sv.code, sv.title, sv.discount_type, sv.amount, sv.max_discount,
                         sv.min_order, sv.start_at, sv.end_at, sv.is_active,
                         COUNT(u.id) as used_count')
            ->groupBy(
                'usv.id',
                'usv.times',
                'sv.id',
                'sv.code',
                'sv.title',
                'sv.discount_type',
                'sv.amount',
                'sv.max_discount',
                'sv.min_order',
                'sv.start_at',
                'sv.end_at',
                'sv.is_active'
            )
            ->first();

        if (!$row) {
            return response()->json(['ok' => false, 'message' => 'Mã không thuộc ví của bạn.'], 422);
        }

        if ((int)$row->is_active !== 1) {
            return response()->json(['ok' => false, 'message' => 'Mã đang tạm ngưng.'], 422);
        }
        $now = Carbon::now();
        if ($row->start_at && Carbon::parse($row->start_at)->isFuture()) {
            return response()->json(['ok' => false, 'message' => 'Mã chưa tới ngày áp dụng.'], 422);
        }
        if ($row->end_at && Carbon::parse($row->end_at)->isPast()) {
            return response()->json(['ok' => false, 'message' => 'Mã đã hết hạn.'], 422);
        }

        // Điều kiện đơn tối thiểu
        $minOrder = (int) ($row->min_order ?? 0);
        if ($minOrder > 0 && $subtotal < $minOrder) {
            return response()->json([
                'ok' => false,
                'message' => 'Đơn tối thiểu ' . number_format($minOrder, 0, ',', '.') . 'đ để áp mã.'
            ], 422);
        }

        // Tính mức giảm trên PHÍ SHIP
        $discount = 0;
        $type     = strtolower((string)$row->discount_type);
        $amount   = (float) $row->amount;

        if ($shippingFee > 0) {
            if ($type === 'percent') {
                $discount = (int) round($shippingFee * $amount / 100);
            } else { // fixed
                $discount = (int) round($amount);
            }
            if (!is_null($row->max_discount)) {
                $discount = min($discount, (int) $row->max_discount);
            }
            $discount = max(0, min($discount, $shippingFee));
        }

        // Lưu session để CheckoutController hiển thị/ghi đơn
        session([
            'applied_ship' => [
                'code'               => $code,
                'shipping_voucher_id' => (int) $row->id,
                'user_voucher_id'    => (int) $row->user_sv_id,
                'discount'           => (int) $discount,
                'title'              => (string) $row->title,
                'type'               => $type,
                'amount'             => $amount,
            ]
        ]);
        session()->save();

        return response()->json([
            'ok'          => true,
            'code'        => $code,
            'discount'    => (int) $discount,
            'after_fee'   => max(0, $shippingFee - (int)$discount),
            'title'       => (string) $row->title,
        ]);
    }

    /** Bỏ mã vận chuyển đang áp */
    public function remove(Request $r)
    {
        session()->forget('applied_ship');
        session()->save();

        return response()->json(['ok' => true]);
    }
    public function mine(Request $r)
    {
        $userId = auth()->id();

        $rows = \DB::table('user_shipping_vouchers as usv')
            ->join('shipping_vouchers as sv', 'sv.id', '=', 'usv.shipping_voucher_id')
            ->leftJoin('shipping_voucher_usages as u', function ($j) use ($userId) {
                $j->on('u.shipping_voucher_id', '=', 'sv.id')
                    ->on('u.user_id', '=', 'usv.user_id');
            })
            ->where('usv.user_id', $userId)
            ->selectRaw("
            sv.id, sv.code, sv.title, sv.discount_type, sv.amount, sv.max_discount,
            sv.min_order, sv.start_at, sv.end_at, sv.is_active,
            MAX(usv.id)  as last_id,
            SUM(usv.times) as times,      -- tổng số lượt (hoặc dùng MAX nếu bạn muốn)
            COUNT(u.id)   as used_count
        ")
            ->groupBy(
                'sv.id',
                'sv.code',
                'sv.title',
                'sv.discount_type',
                'sv.amount',
                'sv.max_discount',
                'sv.min_order',
                'sv.start_at',
                'sv.end_at',
                'sv.is_active'
            )
            ->orderByDesc('last_id')   // ⟵ thay vì usv.id
            ->get();

        // subtotal để check min_order
        $subtotal = (int) $r->query('subtotal', 0);
        if ($subtotal <= 0) {
            foreach ((array) session('cart.items', []) as $it) {
                $subtotal += (int)($it['price'] ?? 0) * (int)($it['qty'] ?? 1);
            }
        }

        $now = \Carbon\Carbon::now();

        $data = $rows->map(function ($v) use ($now, $subtotal) {
            $isPercent = strtolower($v->discount_type ?? '') === 'percent';
            $valueTxt  = $isPercent
                ? rtrim(rtrim(number_format($v->amount, 2), '0'), '.') . '%'
                : number_format((int)$v->amount, 0, ',', '.') . 'đ';
            $maxTxt    = $v->max_discount ? ('Tối đa ' . number_format((int)$v->max_discount, 0, ',', '.') . 'đ') : null;

            $left   = max(0, (int)$v->times - (int)$v->used_count);
            $usable = true;
            $reason = null;

            if ((int)$v->is_active !== 1) {
                $usable = false;
                $reason = 'Tạm ngưng';
            }
            if ($usable && $v->start_at && $now->lt($v->start_at)) {
                $usable = false;
                $reason = 'Chưa hiệu lực';
            }
            if ($usable && $v->end_at   && $now->gt($v->end_at)) {
                $usable = false;
                $reason = 'Hết hạn';
            }
            if ($usable && $left <= 0) {
                $usable = false;
                $reason = 'Đã dùng hết';
            }
            if ($usable && (int)$v->min_order > 0 && $subtotal < (int)$v->min_order) {
                $usable = false;
                $reason = 'Đơn tối thiểu ' . number_format((int)$v->min_order, 0, ',', '.') . 'đ';
            }

            return [
                'id'            => (int) $v->id,
                'code'          => (string) $v->code,
                'discount_text' => ($isPercent ? 'Giảm ' : 'Trừ ') . $valueTxt . ($maxTxt ? ' • ' . $maxTxt : ''),
                'min_order'     => (int) ($v->min_order ?? 0),
                'expires_at'    => $v->end_at ? \Carbon\Carbon::parse($v->end_at)->format('d/m/Y H:i') : null,
                'usable'        => $usable,
                'reason'        => $reason,
                'left'          => $left,
            ];
        });

        return response()->json(['data' => $data]);
    }
}
