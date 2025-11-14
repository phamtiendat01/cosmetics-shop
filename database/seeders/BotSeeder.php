<?php

namespace Database\Seeders;

use App\Models\BotIntent;
use App\Models\BotTool;
use Illuminate\Database\Seeder;

class BotSeeder extends Seeder
{
    public function run(): void
    {
        // ========== INTENTS ==========
        $intents = [
            [
                'name' => 'greeting',
                'display_name' => 'Chào hỏi',
                'description' => 'User chào hỏi bot',
                'examples' => ['xin chào', 'chào bạn', 'hello', 'hi', 'hey'],
                'handler_class' => null,
                'is_active' => true,
                'priority' => 100,
                'config' => ['keywords' => ['xin chào', 'chào', 'hello', 'hi', 'hey']],
            ],
            [
                'name' => 'product_search',
                'display_name' => 'Tìm kiếm sản phẩm',
                'description' => 'User muốn tìm sản phẩm',
                'examples' => ['tìm sản phẩm', 'cho mình xem', 'gợi ý sản phẩm', 'tìm kem dưỡng'],
                'handler_class' => null,
                'is_active' => true,
                'priority' => 90,
                'config' => ['keywords' => ['tìm', 'gợi ý', 'cho mình', 'xem sản phẩm']],
            ],
            [
                'name' => 'product_info',
                'display_name' => 'Thông tin sản phẩm',
                'description' => 'User hỏi về thông tin sản phẩm cụ thể',
                'examples' => ['giá bao nhiêu', 'thành phần', 'công dụng', 'review'],
                'handler_class' => null,
                'is_active' => true,
                'priority' => 85,
                'config' => ['keywords' => ['giá', 'thành phần', 'công dụng', 'review']],
            ],
            [
                'name' => 'product_recommendation',
                'display_name' => 'Gợi ý sản phẩm',
                'description' => 'User muốn được gợi ý sản phẩm',
                'examples' => ['tư vấn cho mình', 'gợi ý sản phẩm', 'cho mình vài món', 'recommend'],
                'handler_class' => null,
                'is_active' => true,
                'priority' => 80,
                'config' => ['keywords' => ['tư vấn', 'gợi ý', 'recommend', 'cho mình']],
            ],
            [
                'name' => 'order_tracking',
                'display_name' => 'Tra cứu đơn hàng',
                'description' => 'User muốn tra cứu đơn hàng',
                'examples' => ['đơn hàng', 'mã đơn', 'tracking', 'theo dõi đơn'],
                'handler_class' => null,
                'is_active' => true,
                'priority' => 95,
                'config' => ['keywords' => ['đơn', 'order', 'mã đơn', 'tracking']],
            ],
            [
                'name' => 'shipping_policy',
                'display_name' => 'Chính sách vận chuyển',
                'description' => 'User hỏi về phí ship, thời gian giao hàng',
                'examples' => ['phí ship', 'vận chuyển', 'giao hàng', 'shipping'],
                'handler_class' => null,
                'is_active' => true,
                'priority' => 75,
                'config' => ['keywords' => ['phí ship', 'vận chuyển', 'giao hàng']],
            ],
            [
                'name' => 'return_policy',
                'display_name' => 'Chính sách đổi trả',
                'description' => 'User hỏi về đổi trả, hoàn tiền',
                'examples' => ['đổi trả', 'hoàn tiền', 'bảo hành', 'return'],
                'handler_class' => null,
                'is_active' => true,
                'priority' => 75,
                'config' => ['keywords' => ['đổi trả', 'hoàn tiền', 'bảo hành']],
            ],
            [
                'name' => 'payment_policy',
                'display_name' => 'Chính sách thanh toán',
                'description' => 'User hỏi về phương thức thanh toán',
                'examples' => ['thanh toán', 'payment', 'cod', 'vnpay', 'momo'],
                'handler_class' => null,
                'is_active' => true,
                'priority' => 70,
                'config' => ['keywords' => ['thanh toán', 'payment', 'cod']],
            ],
            [
                'name' => 'coupon_check',
                'display_name' => 'Kiểm tra mã giảm giá',
                'description' => 'User muốn kiểm tra mã giảm giá',
                'examples' => ['mã giảm giá', 'coupon', 'voucher', 'mã khuyến mãi'],
                'handler_class' => null,
                'is_active' => true,
                'priority' => 65,
                'config' => ['keywords' => ['mã giảm', 'coupon', 'voucher']],
            ],
        ];

        foreach ($intents as $intent) {
            BotIntent::updateOrCreate(
                ['name' => $intent['name']],
                $intent
            );
        }

        // ========== TOOLS ==========
        $tools = [
            [
                'name' => 'searchProducts',
                'display_name' => 'Tìm kiếm sản phẩm',
                'description' => 'Tìm sản phẩm theo từ khoá + filter (skin_types, concerns, budget)',
                'parameters_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string', 'description' => 'Từ khoá tìm kiếm'],
                        'skin_types' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Loại da'],
                        'concerns' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Vấn đề da'],
                        'price_min' => ['type' => 'number', 'description' => 'Giá tối thiểu'],
                        'price_max' => ['type' => 'number', 'description' => 'Giá tối đa'],
                        'limit' => ['type' => 'integer', 'description' => 'Số lượng kết quả', 'default' => 8],
                    ],
                ],
                'handler_class' => \App\Tools\Bot\ProductSearchTool::class,
                'is_active' => true,
                'config' => null,
            ],
            [
                'name' => 'pickProducts',
                'display_name' => 'Gợi ý sản phẩm',
                'description' => 'Gợi ý một số sản phẩm bất kỳ (featured/mới/còn hàng)',
                'parameters_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'limit' => ['type' => 'integer', 'description' => 'Số lượng sản phẩm', 'default' => 8],
                    ],
                ],
                'handler_class' => \App\Tools\Bot\PickProductsTool::class,
                'is_active' => true,
                'config' => null,
            ],
            [
                'name' => 'getOrderStatus',
                'display_name' => 'Tra cứu đơn hàng',
                'description' => 'Lấy thông tin trạng thái đơn hàng theo mã đơn',
                'parameters_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'code' => ['type' => 'string', 'description' => 'Mã đơn hàng', 'required' => true],
                    ],
                    'required' => ['code'],
                ],
                'handler_class' => \App\Tools\Bot\OrderTrackingTool::class,
                'is_active' => true,
                'config' => null,
            ],
            [
                'name' => 'getPolicy',
                'display_name' => 'Lấy chính sách',
                'description' => 'Lấy thông tin chính sách (shipping, return, payment, warranty)',
                'parameters_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'topic' => ['type' => 'string', 'description' => 'Chủ đề chính sách', 'required' => true],
                    ],
                    'required' => ['topic'],
                ],
                'handler_class' => \App\Tools\Bot\PolicyTool::class,
                'is_active' => true,
                'config' => null,
            ],
            [
                'name' => 'getProductInfo',
                'display_name' => 'Thông tin sản phẩm',
                'description' => 'Lấy chi tiết thông tin một sản phẩm theo slug hoặc ID',
                'parameters_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'slugOrId' => ['type' => 'string', 'description' => 'Slug hoặc ID sản phẩm', 'required' => true],
                    ],
                    'required' => ['slugOrId'],
                ],
                'handler_class' => \App\Tools\Bot\ProductInfoTool::class,
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

        $this->command->info('✅ Đã seed ' . count($intents) . ' intents và ' . count($tools) . ' tools!');
    }
}
