<?php
return [
    'bin' => env('VIETQR_BIN', env('VIETQR_BANK_BIN')),
    'account' => env('VIETQR_ACCOUNT', env('VIETQR_ACCOUNT_NO')),
    'name' => env('VIETQR_NAME', env('VIETQR_ACCOUNT_NAME')),
    'txn_api' => env('VIETQR_TXN_API'),
    'expire_minutes' => env('VIETQR_EXPIRE_MINUTES', 15),
];
