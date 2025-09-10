<?php
// app/Services/GeminiTools.php

namespace App\Services;

class GeminiTools
{
    public static function declarations(): array
    {
        return [
            [
                'name' => 'searchProducts',
                'description' => 'Tìm sản phẩm theo từ khoá + filter.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string'],
                        'skin_types' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'concerns' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'price_min' => ['type' => 'number'],
                        'price_max' => ['type' => 'number'],
                        'category_slug' => ['type' => 'string'],
                        'brand_slug' => ['type' => 'string'],
                        'limit' => ['type' => 'integer']
                    ]
                ]
            ],
            [
                'name' => 'pickProducts',
                'description' => 'Gợi ý một số sản phẩm bất kỳ (featured/mới/còn hàng).',
                'parameters' => [
                    'type' => 'object',
                    'properties' => ['limit' => ['type' => 'integer']],
                ],
            ],
            [
                'name' => 'resolveProduct',
                'description' => 'Map tên/slug tự nhiên -> {id,slug,name}.',
                'parameters' => ['type' => 'object', 'properties' => ['query' => ['type' => 'string']], 'required' => ['query']]
            ],
            [
                'name' => 'getProductInfo',
                'description' => 'Lấy chi tiết 1 sản phẩm.',
                'parameters' => ['type' => 'object', 'properties' => ['slugOrId' => ['type' => 'string']], 'required' => ['slugOrId']]
            ],
            [
                'name' => 'checkAvailability',
                'description' => 'Kiểm tra còn hàng theo slug/id.',
                'parameters' => ['type' => 'object', 'properties' => ['slugOrId' => ['type' => 'string']], 'required' => ['slugOrId']]
            ],
            [
                'name' => 'compareProducts',
                'description' => 'So sánh 2–3 sản phẩm.',
                'parameters' => ['type' => 'object', 'properties' => ['idsOrSlugs' => ['type' => 'array', 'items' => ['type' => 'string']]], 'required' => ['idsOrSlugs']]
            ],
            [
                'name' => 'getOrderStatus',
                'description' => 'Tra tình trạng đơn.',
                'parameters' => ['type' => 'object', 'properties' => ['code' => ['type' => 'string']], 'required' => ['code']]
            ],
            [
                'name' => 'validateCoupon',
                'description' => 'Kiểm tra mã giảm giá.',
                'parameters' => ['type' => 'object', 'properties' => [
                    'code' => ['type' => 'string'],
                    'cart' => ['type' => 'array', 'items' => ['type' => 'object']],
                    'subtotal' => ['type' => 'number']
                ], 'required' => ['code']]
            ],
            [
                'name' => 'getPolicy',
                'description' => 'Lấy policy (shipping|returns|payment|privacy|warranty).',
                'parameters' => ['type' => 'object', 'properties' => ['topic' => ['type' => 'string']], 'required' => ['topic']]
            ],
        ];
    }
}
