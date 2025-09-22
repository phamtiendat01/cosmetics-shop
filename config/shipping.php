<?php

return [
    // Toạ độ cửa hàng (có thể đặt trong .env)
    'shop_lat' => env('SHOP_LAT', 21.046333), // ví dụ trường đại học tài nguyên và môi trường hà nội 
    'shop_lng' => env('SHOP_LNG', 105.762444),

    // Hệ số “đường thực tế”
    'road_factor' => env('ROAD_FACTOR', 1.2), // 20% dài hơn đường chim bay

    // Ngưỡng miễn phí theo giá trị đơn
    'free_threshold_amount' => env('FREE_SHIP_THRESHOLD', 499000),

    // Bậc phí theo khoảng cách (km)
    'tiers' => [
        ['max_km' => 3,   'fee' => 0],
        ['max_km' => 7,   'fee' => 15000],
        ['max_km' => 15,  'fee' => 30000],
        ['max_km' => 30,  'fee' => 45000],
        ['max_km' => 9999, 'fee' => 60000], // >30km
    ],
];
