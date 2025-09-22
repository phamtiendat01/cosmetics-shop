<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Http\Controllers\PaymentController;


Route::get('/ping', function () {
    return response()->json([
        'ok'   => true,
        'time' => now()->toDateTimeString(),
    ]);
});
Route::get('/payment/vietqr/check/{order}', [PaymentController::class, 'vietqrCheck'])
    ->name('payment.vietqr.check');
// routes/api.php
Route::get('/api/order-status', function (Request $r) {
    $code = $r->query('code');
    abort_if(!$code, 404);
    $o = Order::where('code', $code)->firstOrFail();

    return response()->json([
        'payment_status' => $o->payment_status, // unpaid | pending | paid | failed | refunded
        'status'         => $o->status,         // pending | confirmed | ...
    ]);
})->name('api.order.status');
