<?php

namespace Database\Seeders;

use App\Models\BotTool;
use Illuminate\Database\Seeder;

class BotToolsSeeder extends Seeder
{
    public function run(): void
    {
        $tools = [
            [
                'name' => 'searchProducts',
                'display_name' => 'Tìm kiếm sản phẩm',
                'description' => 'Tìm kiếm sản phẩm dựa trên từ khóa, loại da, ngân sách, vấn đề da',
                'parameters_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string', 'description' => 'Từ khóa tìm kiếm'],
                        'skin_types' => ['type' => 'array', 'description' => 'Loại da'],
                        'concerns' => ['type' => 'array', 'description' => 'Vấn đề da'],
                        'budget_min' => ['type' => 'number', 'description' => 'Ngân sách tối thiểu'],
                        'budget_max' => ['type' => 'number', 'description' => 'Ngân sách tối đa'],
                    ],
                ],
                'handler_class' => \App\Tools\Bot\ProductSearchTool::class,
                'is_active' => true,
                'config' => null,
            ],
            [
                'name' => 'pickProducts',
                'display_name' => 'Gợi ý sản phẩm',
                'description' => 'Gợi ý sản phẩm nổi bật, bán chạy',
                'parameters_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'limit' => ['type' => 'number', 'description' => 'Số lượng sản phẩm'],
                    ],
                ],
                'handler_class' => \App\Tools\Bot\PickProductsTool::class,
                'is_active' => true,
                'config' => null,
            ],
            [
                'name' => 'getProductInfo',
                'display_name' => 'Thông tin sản phẩm',
                'description' => 'Lấy thông tin chi tiết của sản phẩm',
                'parameters_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'product_id' => ['type' => 'string', 'description' => 'ID hoặc slug sản phẩm'],
                    ],
                ],
                'handler_class' => \App\Tools\Bot\ProductInfoTool::class,
                'is_active' => true,
                'config' => null,
            ],
            [
                'name' => 'getOrderStatus',
                'display_name' => 'Tra cứu đơn hàng',
                'description' => 'Tra cứu trạng thái đơn hàng',
                'parameters_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'order_code' => ['type' => 'string', 'description' => 'Mã đơn hàng'],
                        'phone' => ['type' => 'string', 'description' => 'Số điện thoại'],
                    ],
                ],
                'handler_class' => \App\Tools\Bot\OrderTrackingTool::class,
                'is_active' => true,
                'config' => null,
            ],
            [
                'name' => 'getPolicy',
                'display_name' => 'Chính sách',
                'description' => 'Lấy thông tin chính sách (ship, đổi trả, thanh toán)',
                'parameters_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'policy_type' => ['type' => 'string', 'description' => 'Loại chính sách: shipping, return, payment'],
                    ],
                ],
                'handler_class' => \App\Tools\Bot\PolicyTool::class,
                'is_active' => true,
                'config' => null,
            ],
        ];

        foreach ($tools as $tool) {
            BotTool::updateOrCreate(
                ['name' => $tool['name']],
                $tool
            );
        }

        $this->command->info('✅ Đã seed ' . count($tools) . ' tools vào database!');
    }
}
