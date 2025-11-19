<?php

namespace App\Tools\Bot;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * GetShippingVouchersTool - Lấy danh sách mã vận chuyển của user
 */
class GetShippingVouchersTool
{
    public function execute(string $message, array $context): ?array
    {
        if (!auth()->check()) {
            return [
                'success' => false,
                'requires_auth' => true,
                'message' => 'Bạn cần đăng nhập để xem mã vận chuyển.',
            ];
        }

        $userId = auth()->id();
        $now = Carbon::now();

        // Lấy subtotal để check min_order
        // ✅ Cart items trong session chỉ có product_id, variant_id, qty - KHÔNG có price
        // Cần query database để lấy giá
        $items = session('cart.items', []);
        $subtotal = 0;
        if (!empty($items)) {
            $pids = collect($items)->pluck('product_id')->unique()->values()->all();
            $products = \App\Models\Product::whereIn('id', $pids)
                ->with(['variants' => function ($q) {
                    $q->select('id', 'product_id', 'price');
                }])
                ->get()
                ->keyBy('id');

            foreach ($items as $it) {
                $product = $products->get((int)($it['product_id'] ?? 0));
                if (!$product) continue;

                $variantId = $it['variant_id'] ?? null;
                $qty = (int)($it['qty'] ?? 1);

                if ($variantId) {
                    $variant = $product->variants->firstWhere('id', $variantId);
                    $price = $variant ? (int)$variant->price : (int)($product->variants->min('price') ?? $product->price ?? 0);
                } else {
                    $price = (int)($product->variants->min('price') ?? $product->price ?? 0);
                }

                $subtotal += $price * $qty;
            }
        }

        // ✅ Log để debug
        Log::info('GetShippingVouchersTool: Calculated subtotal', [
            'user_id' => $userId,
            'subtotal' => $subtotal,
            'items_count' => count($items),
        ]);

        try {
            // Lấy shipping vouchers từ user_shipping_vouchers (tương tự ShippingVoucherController::mine)
            $rows = DB::table('user_shipping_vouchers as usv')
                ->join('shipping_vouchers as sv', 'sv.id', '=', 'usv.shipping_voucher_id')
                ->leftJoin('shipping_voucher_usages as u', function ($j) use ($userId) {
                    $j->on('u.shipping_voucher_id', '=', 'sv.id')
                      ->on('u.user_id', '=', DB::raw($userId));
                })
                ->where('usv.user_id', $userId)
                ->selectRaw("
                    sv.id, sv.code, sv.title, sv.discount_type, sv.amount, sv.max_discount,
                    sv.min_order, sv.start_at, sv.end_at, sv.is_active,
                    MAX(usv.id) as last_id,
                    SUM(usv.times) as times,
                    COUNT(u.id) as used_count
                ")
                ->groupBy(
                    'sv.id', 'sv.code', 'sv.title', 'sv.discount_type', 'sv.amount',
                    'sv.max_discount', 'sv.min_order', 'sv.start_at', 'sv.end_at', 'sv.is_active'
                )
                ->orderByDesc('last_id')
                ->get();

            $vouchers = [];
            foreach ($rows as $v) {
                $isPercent = strtolower($v->discount_type ?? '') === 'percent';
                $valueTxt = $isPercent
                    ? rtrim(rtrim(number_format($v->amount, 2), '0'), '.') . '%'
                    : number_format((int)$v->amount, 0, ',', '.') . 'đ';
                $maxTxt = $v->max_discount ? ('Tối đa ' . number_format((int)$v->max_discount, 0, ',', '.') . 'đ') : null;

                $active = (int)($v->is_active ?? 1) === 1
                    && (empty($v->start_at) || Carbon::parse($v->start_at)->lte($now))
                    && (empty($v->end_at) || Carbon::parse($v->end_at)->gte($now));

                $minOk = empty($v->min_order) || $subtotal >= (int)$v->min_order;
                $remain = max(0, (int)($v->times ?? 0) - (int)$v->used_count);

                $usable = $active && $minOk && ($v->times === null || $remain > 0);

                $vouchers[] = [
                    'id' => (int)$v->id,
                    'code' => strtoupper($v->code),
                    'title' => $v->title,
                    'discount_text' => 'Giảm ' . $valueTxt . ($maxTxt ? ' • ' . $maxTxt : ''),
                    'min_order' => (int)($v->min_order ?? 0),
                    'expires_at' => $v->end_at ? Carbon::parse($v->end_at)->format('d/m/Y H:i') : null,
                    'usable' => $usable,
                    'reason' => !$active ? 'Hết hạn' : (!$minOk ? 'Chưa đạt đơn tối thiểu' : (($v->times !== null && $remain <= 0) ? 'Đã dùng hết' : null)),
                ];
            }

            // Chỉ lấy vouchers có thể dùng được
            $usableVouchers = array_values(array_filter($vouchers, fn($v) => $v['usable']));

            // ✅ Log để debug
            Log::info('GetShippingVouchersTool: Returning vouchers', [
                'user_id' => $userId,
                'vouchers_count' => count($usableVouchers),
                'first_voucher_code' => $usableVouchers[0]['code'] ?? null,
                'first_voucher_discount_text' => $usableVouchers[0]['discount_text'] ?? null,
            ]);

            return [
                'success' => true,
                'vouchers' => $usableVouchers,
                'total' => count($usableVouchers),
                'message' => count($usableVouchers) > 0
                    ? 'Bạn có ' . count($usableVouchers) . ' mã vận chuyển có thể sử dụng.'
                    : 'Bạn chưa có mã vận chuyển nào.',
            ];
        } catch (\Throwable $e) {
            Log::error('GetShippingVouchersTool failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
            ]);

            return [
                'success' => false,
                'message' => 'Không thể lấy danh sách mã vận chuyển. Vui lòng thử lại!',
            ];
        }
    }
}

