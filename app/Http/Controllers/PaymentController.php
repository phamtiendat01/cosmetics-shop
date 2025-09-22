<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Payments\PaymentService;
use App\Services\Payments\MomoGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /* ===================== MoMo ===================== */
    public function momoReturn(Request $r, PaymentService $paySvc)
    {
        $res = $paySvc->handleCallback('MOMO', $r->all());

        if (!empty($res['ok'])) {
            $this->clearCartSession();
        }

        $ordersIndex = RouteFacade::has('account.orders.index')
            ? route('account.orders.index') : url('/');

        $target = (!empty($res['order_id']) && RouteFacade::has('account.orders.show'))
            ? route('account.orders.show', $res['order_id'])
            : $ordersIndex;

        return redirect($target)->with(
            $res['ok'] ? 'success' : 'error',
            $res['ok'] ? 'Thanh toán MoMo thành công.' : ($res['message'] ?? 'Thanh toán MoMo thất bại.')
        );
    }

    public function momoIpn(Request $request, MomoGateway $momo)
    {
        $momo->handleCallback($request->all());
        return response()->json(['ok' => true]);
    }

    /* ===================== VNPay ===================== */
    public function vnpayReturn(Request $r, PaymentService $paySvc)
    {
        $res = $paySvc->handleCallback('VNPAY', $r->all());

        if (!empty($res['ok'])) {
            $this->clearCartSession();
        }

        $ordersIndex = RouteFacade::has('account.orders.index')
            ? route('account.orders.index') : url('/');

        $target = (!empty($res['order_id']) && RouteFacade::has('account.orders.show'))
            ? route('account.orders.show', $res['order_id'])
            : $ordersIndex;

        return redirect($target)->with(
            $res['ok'] ? 'success' : 'error',
            $res['ok'] ? 'Thanh toán VNPay thành công.' : ($res['message'] ?? 'Thanh toán VNPay thất bại.')
        );
    }

    /* (stub) nếu có webhook ngân hàng khác */
    public function bankWebhook(Request $r)
    {
        return response()->json(['ok' => true]);
    }

    /* ========== VietQR: trang hiển thị + polling ========== */
    public function vietqrShow(Order $order)
    {
        $payment = $order->payments()->where('method_code', 'VIETQR')->latest()->first();

        $bin    = (string) config('vietqr.bin');
        $acc    = (string) config('vietqr.account');
        $name   = (string) config('vietqr.name');
        $ref    = (string) ($order->code ?? ('ORDER-' . $order->id));
        $amount = (int) round($order->grand_total);

        $qrUrl = sprintf(
            'https://img.vietqr.io/image/%s-%s-compact2.jpg?amount=%d&addInfo=%s&accountName=%s',
            $bin,
            $acc,
            $amount,
            rawurlencode($ref),
            rawurlencode($name)
        );

        $ttlMin = (int) config('vietqr.expire_minutes', 15);
        $deadlineTs = optional(optional($payment)->meta)['expire_at'] ?? null;
        $deadlineTs = $deadlineTs
            ? \Carbon\Carbon::parse($deadlineTs)->valueOf()
            : now()->addMinutes($ttlMin)->valueOf();

        return view('payments.vietqr', [
            'order'       => $order,
            'qr_url'      => $qrUrl,
            'ref'         => $ref,
            'amount'      => $amount,
            'account'     => $acc,
            'bank'        => 'VietQR',
            'checkUrl'    => route('payment.vietqr.check', $order),
            'redirectUrl' => $this->orderShowUrl($order),
            'deadlineTs'  => $deadlineTs,
            'ttlMin'      => $ttlMin,
        ]);
    }

    public function vietqrCheck(Order $order)
    {
        // Nếu đã paid từ trước -> xoá giỏ + trả luôn
        if ($order->payment_status === 'paid') {
            $this->clearCartSession();
            return response()->json(['status' => 'paid', 'redirect' => $this->orderShowUrl($order)]);
        }

        $api = (string) config('vietqr.txn_api');
        if (!$api) return response()->json(['status' => 'pending']);

        $amount        = (int) round($order->grand_total);
        $refPlainUpper = Str::upper(preg_replace('/[^A-Z0-9]/i', '', (string)($order->code ?? '')));
        $accConfPlain  = ltrim(preg_replace('/\D+/', '', (string) config('vietqr.account')), '0');

        // gọi Apps Script có lọc sẵn
        try {
            $json = Http::timeout(10)->get($api, [
                't'      => time(),
                'ref'    => $refPlainUpper,
                'amount' => $amount,
            ])->json();
        } catch (\Throwable $e) {
            return response()->json(['status' => 'pending']);
        }

        $rows = $json['data'] ?? $json ?? [];
        if (!is_array($rows) || !count($rows)) return response()->json(['status' => 'pending']);

        // cửa sổ thời gian gắn với đơn
        $tz      = 'Asia/Ho_Chi_Minh';
        $placed  = $order->placed_at ?? $order->created_at ?? now($tz);
        $minTime = \Carbon\Carbon::parse($placed, $tz)->copy()->subMinutes(10);
        $maxTime = \Carbon\Carbon::parse($placed, $tz)->copy()->addHours(6);

        // Model Payment để check trùng toàn cục
        $paymentModel = $order->payments()->getModel();

        // helper lấy cột
        $get = function (array $row, array $names, $idx = null) {
            foreach ($names as $k) if (array_key_exists($k, $row) && $row[$k] !== '' && $row[$k] !== null) return $row[$k];
            if ($idx !== null && array_key_exists($idx, $row)) return $row[$idx];
            $lower = array_change_key_case($row, CASE_LOWER);
            foreach ($names as $k) {
                $kl = mb_strtolower($k);
                if (array_key_exists($kl, $lower) && $lower[$kl] !== '' && $lower[$kl] !== null) return $lower[$kl];
            }
            return null;
        };

        foreach (array_reverse($rows) as $r) {
            $txId   = (string) ($get($r, ['Mã GD', 'Ma GD', 'MAGD'], 0) ?? '');
            $txId   = $txId ?: (string) ($get($r, ['Mã tham chiếu', 'Ma tham chieu', 'Ma tham chiếu'], 5) ?? '');
            $desc   = (string) ($get($r, ['Mô tả', 'Mo ta', 'MOTA', 'Nội dung', 'Noi dung'], 1) ?? '');
            $rawAmt = (string) ($get($r, ['Giá trị', 'Gia tri', 'Số tiền', 'So tien'], 2) ?? '0');
            $when   = (string) ($get($r, ['Ngày diễn ra', 'Ngay dien ra', 'Thời gian', 'Thoi gian'], 3) ?? '');
            $acct   = (string) ($get($r, ['Số tài khoản', 'So tai khoan', 'Tài khoản', 'Tai khoan'], 4) ?? '');

            // 1) số tiền phải trùng
            $value = (int) preg_replace('/[^\d]/', '', $rawAmt);
            if ($value !== $amount) continue;

            // 2) BẮT BUỘC mô tả chứa mã đơn (đã chuẩn hoá)
            $descPlainUpper = Str::upper(preg_replace('/[^A-Z0-9]/i', '', $desc));
            $hasRef = Str::contains($descPlainUpper, $refPlainUpper);
            if (!$hasRef) continue;

            // 3) STK khớp (bỏ 0 đầu)
            $acctNorm = ltrim(preg_replace('/\D+/', '', $acct), '0');
            if ($accConfPlain && $acctNorm && $accConfPlain !== $acctNorm) continue;

            // 4) trong cửa sổ thời gian của đơn
            $timeOk = true;
            if ($when) {
                try {
                    $t = \Carbon\Carbon::parse($when, $tz);
                    $timeOk = $t->between($minTime, $maxTime);
                } catch (\Throwable $e) {
                    $timeOk = true;
                }
            }
            if (!$timeOk) continue;

            // chống dùng lại giao dịch (toàn cục)
            $dedup = $txId ?: sha1($descPlainUpper . '|' . $value . '|' . $acctNorm . '|' . substr($when, 0, 16));
            if ($paymentModel::where('method_code', 'VIETQR')->where('provider_ref', $dedup)->exists()) {
                continue; // đã gán cho đơn khác
            }

            // === cập nhật thành paid ===
            $payment = $order->payments()->where('method_code', 'VIETQR')->latest()->first();
            $meta = is_array($payment?->meta) ? $payment->meta : [];
            $meta['gs_tx'] = $r;

            if ($payment && $payment->status !== 'paid') {
                $payment->update([
                    'status'       => 'paid',
                    'provider_ref' => $dedup,
                    'paid_at'      => now(),
                    'meta'         => $meta,
                ]);
            } else {
                $order->payments()->create([
                    'method_code'  => 'VIETQR',
                    'amount'       => $amount,
                    'status'       => 'paid',
                    'provider_ref' => $dedup,
                    'paid_at'      => now(),
                    'meta'         => $meta,
                ]);
            }

            $upd = ['payment_status' => 'paid'];
            if (Schema::hasColumn('orders', 'order_status')) $upd['order_status'] = 'cho_xac_nhan';
            elseif (Schema::hasColumn('orders', 'status'))   $upd['status'] = 'confirmed';
            $order->update($upd);

            $this->clearCartSession();

            return response()->json(['status' => 'paid', 'redirect' => $this->orderShowUrl($order)]);
        }

        return response()->json(['status' => 'pending']);
    }

    /* ===================== Helpers ===================== */
    protected function orderShowUrl(Order $order): string
    {
        if (RouteFacade::has('account.orders.show')) {
            return route('account.orders.show', $order);
        }
        if (RouteFacade::has('account.orders.index')) {
            return route('account.orders.index');
        }
        return url('/');
    }

    protected function clearCartSession(): void
    {
        try {
            session()->forget([
                'cart',
                'cart.items',
                'cart.shipping_fee',
                'applied_coupon',
            ]);
            session()->save();
        } catch (\Throwable $e) {
        }
    }
}
