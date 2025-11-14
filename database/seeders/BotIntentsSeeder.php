<?php

namespace Database\Seeders;

use App\Models\BotIntent;
use Illuminate\Database\Seeder;

class BotIntentsSeeder extends Seeder
{
    public function run(): void
    {
        $intents = [
            [
                'name' => 'product_search',
                'display_name' => 'Tìm kiếm sản phẩm',
                'description' => 'User muốn tìm kiếm sản phẩm',
                'examples' => [
                    'tìm sữa rửa mặt',
                    'sữa rửa mặt cho da dầu',
                    'serum dưới 500k',
                    'kem chống nắng',
                    'tìm sản phẩm trị mụn',
                    'mỹ phẩm cho da nhạy cảm',
                    'sản phẩm dưỡng ẩm',
                ],
                'handler_class' => null,
                'is_active' => true,
                'priority' => 10,
                'config' => null,
            ],
            [
                'name' => 'product_recommendation',
                'display_name' => 'Gợi ý sản phẩm',
                'description' => 'User muốn được gợi ý sản phẩm',
                'examples' => [
                    'gợi ý sản phẩm',
                    'bạn có sản phẩm nào tốt không',
                    'recommend',
                    'sản phẩm nào phù hợp',
                ],
                'handler_class' => null,
                'is_active' => true,
                'priority' => 9,
                'config' => null,
            ],
            [
                'name' => 'order_tracking',
                'display_name' => 'Tra cứu đơn hàng',
                'description' => 'User muốn tra cứu đơn hàng',
                'examples' => [
                    'tra cứu đơn hàng',
                    'đơn hàng của tôi',
                    'mã đơn #DH123',
                    'order status',
                ],
                'handler_class' => null,
                'is_active' => true,
                'priority' => 8,
                'config' => null,
            ],
            [
                'name' => 'shipping_policy',
                'display_name' => 'Chính sách vận chuyển',
                'description' => 'User hỏi về phí ship, vận chuyển',
                'examples' => [
                    'phí ship',
                    'ship bao nhiêu',
                    'vận chuyển',
                    'giao hàng',
                    'shipping fee',
                ],
                'handler_class' => null,
                'is_active' => true,
                'priority' => 7,
                'config' => null,
            ],
            [
                'name' => 'return_policy',
                'display_name' => 'Chính sách đổi trả',
                'description' => 'User hỏi về đổi trả',
                'examples' => [
                    'đổi trả',
                    'hoàn tiền',
                    'return policy',
                    'bảo hành',
                ],
                'handler_class' => null,
                'is_active' => true,
                'priority' => 6,
                'config' => null,
            ],
            [
                'name' => 'greeting',
                'display_name' => 'Chào hỏi',
                'description' => 'User chào hỏi',
                'examples' => [
                    'xin chào',
                    'hello',
                    'hi',
                    'chào',
                    'alo',
                ],
                'handler_class' => null,
                'is_active' => true,
                'priority' => 5,
                'config' => null,
            ],
        ];

        foreach ($intents as $intent) {
            BotIntent::updateOrCreate(
                ['name' => $intent['name']],
                $intent
            );
        }

        $this->command->info('✅ Đã seed ' . count($intents) . ' intents vào database!');
    }
}
