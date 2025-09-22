<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Services\CouponService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CouponController extends Controller
{
    /**
     * Áp mã giảm giá cho giỏ hàng hiện tại.
     * FE có thể gửi kèm mảng keys (các item đang được chọn).
     */
    public function apply(Request $r, CouponService $svc)
    {
        $r->validate([
            'code' => 'required|string',
            'keys' => 'sometimes|array',
        ]);
        abort_unless(auth()->check(), 403);

        $code = strtoupper(trim($r->input('code')));
        $keysReq = collect($r->input('keys', []))
            ->map(fn($k) => (string) $k)
            ->filter()
            ->values()
            ->all();

        $c = DB::table('coupons')->whereRaw('UPPER(code)=?', [$code])->first();
        if (!$c) {
            return response()->json(['ok' => false, 'message' => 'Mã không tồn tại.'], 422);
        }

        // 1) Quyền sở hữu: phải có trong user_coupons (trừ khi là public)
        $ownedOk = true;
        $userCouponRow = null;
        if (Schema::hasTable('user_coupons')) {
            $userCouponRow = DB::table('user_coupons')
                ->where('user_id', auth()->id())
                ->where('coupon_id', $c->id)
                ->first();

            $ownedOk = (bool) $userCouponRow;

            if (!$ownedOk && Schema::hasColumn('coupons', 'is_public')) {
                $ownedOk = (int) ($c->is_public ?? 0) === 1;
            }
        }
        if (!$ownedOk) {
            return response()->json(['ok' => false, 'message' => 'Mã không thuộc về bạn.'], 422);
        }

        // 2) Trạng thái & thời gian hiệu lực
        $nowOk  = (empty($c->starts_at) || now()->gte($c->starts_at))
            && (empty($c->ends_at)   || now()->lte($c->ends_at));
        $active = (int)($c->is_active ?? 1) === 1;
        if (!$nowOk || !$active) {
            return response()->json(['ok' => false, 'message' => 'Mã đã hết hạn hoặc không hiệu lực.'], 422);
        }

        // 3) Giới hạn số lần dùng
        $perUserLimitCol = Schema::hasColumn('coupons', 'per_user_limit') ? 'per_user_limit'
            : (Schema::hasColumn('coupons', 'limit_per_user') ? 'limit_per_user' : null);

        $limitByCoupon = $perUserLimitCol ? (int)($c->{$perUserLimitCol} ?? 0) : 0; // 0 = không giới hạn
        $limitByWallet = (int)($userCouponRow->times ?? 0); // nếu có row trong user_coupons

        $usedCount = 0;
        if (Schema::hasTable('coupon_usages')) {
            $usedCount = DB::table('coupon_usages')
                ->where('user_id', auth()->id())
                ->where('coupon_id', $c->id)
                ->count();
        }

        // kiểm tra cả 2 ngưỡng (nếu có)
        if ($limitByCoupon > 0 && $usedCount >= $limitByCoupon) {
            return response()->json(['ok' => false, 'message' => 'Bạn đã dùng hết số lần cho mã này.'], 422);
        }
        if ($limitByWallet > 0 && $usedCount >= $limitByWallet) {
            return response()->json(['ok' => false, 'message' => 'Bạn đã dùng hết số lần trong ví.'], 422);
        }

        // 4) Tính giảm bằng service theo items đang CHỌN
        /** @var Coupon $couponModel */
        $couponModel = Coupon::find($c->id);

        $cart = $this->buildCart($keysReq ?: null); // chỉ build theo items được chọn (nếu có)
        $res  = $svc->compute($cart, $couponModel);

        if (!($res['ok'] ?? false) || (int)($res['discount'] ?? 0) <= 0) {
            return response()->json([
                'ok' => false,
                'message' => $res['message'] ?? 'Không áp được mã.',
            ], 422);
        }

        // 5) Lưu session để Checkout tái tính & ghi vào đơn
        session([
            'applied_coupon' => [
                'coupon_id' => (int) $c->id,
                'code'      => $code,
                'discount'  => (int) $res['discount'],
                // nếu service trả keys thì ưu tiên, không thì lưu keys FE gửi lên
                'keys'      => $res['keys'] ?? $keysReq,
            ],
        ]);

        return response()->json([
            'ok'       => true,
            'code'     => $code,
            'discount' => (int) $res['discount'],
            'message'  => 'Đã áp mã.',
            'keys'     => $res['keys'] ?? $keysReq,
        ]);
    }

    /**
     * Gỡ mã khỏi session.
     */
    public function remove()
    {
        session()->forget('applied_coupon');
        return response()->json(['ok' => true]);
    }

    /**
     * Danh sách mã giảm giá thuộc về user (để xổ trong giỏ hàng).
     * Trả về định dạng FE đã map sẵn (có usable & reason).
     */
    public function mine(Request $r)
    {
        abort_unless(auth()->check(), 403);
        $uid = auth()->id();

        $rows = DB::table('user_coupons as uc')
            ->join('coupons as c', 'c.id', '=', 'uc.coupon_id')
            ->leftJoin('coupon_usages as u', function ($j) {
                $j->on('u.coupon_id', '=', 'c.id')->on('u.user_id', '=', 'uc.user_id');
            })
            ->where('uc.user_id', $uid)
            ->selectRaw(
                'uc.id as user_coupon_id, uc.code as user_code, uc.times,
                 c.id as coupon_id, c.code as sys_code, c.name, c.description,
                 c.discount_type, c.discount_value, c.max_discount, c.min_order_total,
                 c.starts_at, c.ends_at, c.is_active,
                 COUNT(u.id) as used_count'
            )
            ->groupBy(
                'uc.id',
                'uc.code',
                'uc.times',
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
            )
            ->orderByDesc('uc.id')
            ->get();

        $now = now();
        $data = $rows->map(function ($x) use ($now) {
            $active   = (int)$x->is_active === 1
                && (empty($x->starts_at) || $x->starts_at <= $now)
                && (empty($x->ends_at)   || $x->ends_at   >= $now);

            $remain   = max(0, (int)($x->times ?? 0) - (int)$x->used_count); // nếu times null => không giới hạn (coi như 0 => không trừ)
            $isPct    = strtolower($x->discount_type) === 'percent';

            return [
                'id'             => (int) $x->coupon_id,
                'user_coupon_id' => (int) $x->user_coupon_id,
                'code'           => strtoupper($x->user_code ?: $x->sys_code),
                'name'           => $x->name,
                'description'    => $x->description,
                'discount_text'  => $isPct
                    ? ('Giảm ' . rtrim(rtrim((string)$x->discount_value, '0'), '.') . '%' . ($x->max_discount ? ' • Tối đa ' . number_format($x->max_discount, 0, ',', '.') . 'đ' : ''))
                    : ('Trừ ' . number_format((int)$x->discount_value, 0, ',', '.') . 'đ'),
                'min_order_total' => (int)($x->min_order_total ?? 0),
                'expires_at'     => $x->ends_at ? \Carbon\Carbon::parse($x->ends_at)->format('d/m/Y H:i') : null,
                'usable'         => $active && ($x->times === null || $remain > 0),
                'reason'         => !$active ? 'Hết hạn / Không hiệu lực' : (($x->times !== null && $remain <= 0) ? 'Đã dùng hết' : null),
                'left'           => $x->times === null ? null : $remain,
            ];
        });

        // FE của bạn đã đọc được cả "items" lẫn "data"
        return response()->json(['data' => $data]);
    }

    // ===== Helpers =====

    /**
     * Build giỏ hàng từ session.
     * @param array|null $onlyKeys Nếu truyền, chỉ lấy các items có key trong danh sách này.
     */
    private function buildCart(array $onlyKeys = null): array
    {
        // ['rowKey' => ['product_id'=>..,'variant_id'=>..,'qty'=>..], ...]
        $raw = session('cart.items', []);

        if ($onlyKeys && is_array($onlyKeys)) {
            $raw = array_intersect_key($raw, array_flip($onlyKeys));
        }

        if (empty($raw)) {
            return ['items' => [], 'subtotal' => 0, 'shipping_fee' => (int) session('cart.shipping_fee', 0)];
        }

        $pids = collect($raw)->pluck('product_id')->unique()->values()->all();

        $products = \App\Models\Product::query()
            ->whereIn('id', $pids)
            ->with(['variants' => function ($q) {
                $q->select('id', 'product_id', 'name', 'price', 'compare_at_price');
            }])
            ->get()
            ->keyBy('id');

        $items = [];
        $subtotal = 0;

        foreach ($raw as $key => $it) {
            /** @var \App\Models\Product|null $p */
            $p = $products->get((int)($it['product_id'] ?? 0));
            if (!$p) continue;

            $vid     = $it['variant_id'] ?? null;
            $variant = $vid ? $p->variants->firstWhere('id', (int)$vid) : null;

            if ($variant) {
                $price   = (int) $variant->price;
                $compare = $variant->compare_at_price ? (int)$variant->compare_at_price : null;
                $brandId = $p->brand_id ?? null;
                $catIds  = ($p->category_id ?? null) ? [(int) $p->category_id] : [];
            } else {
                $price   = (int) (optional($p->variants)->min('price') ?? ($p->price ?? 0));
                $compare = optional($p->variants)->min('compare_at_price');
                $compare = $compare ? (int)$compare : null;
                $brandId = $p->brand_id ?? null;
                $catIds  = ($p->category_id ?? null) ? [(int) $p->category_id] : [];
            }

            $qty    = (int)($it['qty'] ?? 1);
            $isSale = $compare && $compare > $price;

            $items[] = [
                'key'          => (string) $key,
                'product_id'   => (int) $p->id,
                'brand_id'     => $brandId,
                'category_ids' => $catIds,
                'qty'          => $qty,
                'price'        => $price,
                'is_sale'      => (bool) $isSale,
            ];

            $subtotal += $price * $qty;
        }

        $shipping = (int) (session('cart.shipping_fee', 0));
        return ['items' => $items, 'subtotal' => $subtotal, 'shipping_fee' => $shipping];
    }
}
