<?php

return [
    // ID của shipping_vouchers làm template freeship (đang có sẵn trong DB của bạn).
    // Dùng 1 bản ghi active với điều kiện, min_order... đã set. Cron sẽ clone sang user mỗi tháng.
    'monthly_shipping_voucher_id' => env('LOYALTY_MONTHLY_SHIP_VOUCHER_ID', null),

    // tỷ lệ quy đổi điểm (hiện đang implied ~1đ / 1.000đ)
    'point_per_vnd' => 1 / 1000,

    // xác định đơn "đủ điều kiện chi tiêu" (true để trừ shipping)
    'exclude_shipping_in_spend' => true,
];
