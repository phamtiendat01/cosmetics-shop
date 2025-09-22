<?php

return [
    'momo' => [
        // Sandbox
        'endpoint'     => env('MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create'),
        'partner_code' => env('MOMO_PARTNER_CODE'),
        'access_key'   => env('MOMO_ACCESS_KEY'),
        'secret_key'   => env('MOMO_SECRET_KEY'),
    ],

    // (bạn có thể thêm 'vnpay', 'vietqr' sau – mình để riêng)
];
