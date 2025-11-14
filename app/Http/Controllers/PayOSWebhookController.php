<?php
// app/Http/Controllers/PayOSWebhookController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Services\Payments\PayOSService;

class PayOSWebhookController extends Controller
{
    public function handle(Request $request, PayOSService $payos)
    {
        $payload = json_decode($request->getContent(), true) ?? [];

        if (!$payos->verifyWebhook($payload)) {
            return response()->json(['ok' => false], 400);
        }

        $data = $payload['data'] ?? [];
        $orderCode = $data['orderCode'] ?? null;
        $status = $data['status'] ?? null; // PAID / ...
        $txId = $data['id'] ?? null;

        if (!$orderCode) return response()->json(['ok' => true]);

        // tìm OrderPayment theo meta->orderCode
        $op = OrderPayment::where('method_code', 'VIETQR')
            ->whereJsonContains('meta->orderCode', $orderCode)
            ->latest('id')->first();

        if (!$op) return response()->json(['ok' => true]);

        DB::transaction(function () use ($op, $status, $txId, $data) {
            $op->status = ($status === 'PAID') ? 'paid' : strtolower($status ?? 'pending');
            $op->provider_ref = $txId ?: $op->provider_ref;
            $meta = (array) $op->meta;
            $meta['webhook'] = $data;
            $op->meta = $meta;
            if ($status === 'PAID') $op->paid_at = now();
            $op->save();

            $order = $op->order;
            if ($order && $status === 'PAID') {
                $order->payment_status = 'paid';
                $order->save();
            }
        });

        return response()->json(['ok' => true]);
    }
}
