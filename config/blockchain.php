<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Blockchain Enabled
    |--------------------------------------------------------------------------
    |
    | Enable/disable blockchain functionality.
    | For demo: set to false (only use IPFS + hash)
    |
    */
    'enabled' => env('BLOCKCHAIN_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | IPFS Configuration
    |--------------------------------------------------------------------------
    */
    'ipfs_enabled' => env('IPFS_ENABLED', true),
    'ipfs_provider' => env('IPFS_PROVIDER', 'pinata'),

    'pinata' => [
        'api_key' => env('PINATA_API_KEY'),
        'secret_key' => env('PINATA_SECRET_KEY'),
    ],

    'infura' => [
        'project_id' => env('INFURA_PROJECT_ID'),
        'project_secret' => env('INFURA_PROJECT_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Blockchain Network Configuration
    |--------------------------------------------------------------------------
    */
    'network' => env('BLOCKCHAIN_NETWORK', 'polygon'),
    'rpc_url' => env('BLOCKCHAIN_RPC_URL'),
    'private_key' => env('BLOCKCHAIN_PRIVATE_KEY'),
    'contract_address' => env('BLOCKCHAIN_CONTRACT_ADDRESS'),

    /*
    |--------------------------------------------------------------------------
    | QR Code Configuration
    |--------------------------------------------------------------------------
    */
    'qr_code' => [
        'storage' => env('QR_CODE_STORAGE', 'public/qr_codes'),
        'size' => 300,
        'margin' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Verification Limits (Anti-Fraud)
    |--------------------------------------------------------------------------
    |
    | suspicious_threshold: Số lần verify để đánh dấu khả nghi (cảnh báo nhưng vẫn cho verify)
    | blocked_threshold: Số lần verify để khóa hoàn toàn (không cho verify nữa)
    | time_window_hours: Cửa sổ thời gian để đếm (ví dụ: 10 lần trong 24h)
    |
    */
    'verification' => [
        'suspicious_threshold' => env('VERIFICATION_SUSPICIOUS_THRESHOLD', 5), // 5 lần = khả nghi
        'blocked_threshold' => env('VERIFICATION_BLOCKED_THRESHOLD', 15), // 15 lần = khóa
        'time_window_hours' => env('VERIFICATION_TIME_WINDOW', 24), // 24 giờ
    ],
];
