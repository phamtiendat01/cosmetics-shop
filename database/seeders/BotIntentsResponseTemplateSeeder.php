<?php

namespace Database\Seeders;

use App\Models\BotIntent;
use Illuminate\Database\Seeder;

class BotIntentsResponseTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Response templates mẫu cho các intent phổ biến
        $templates = [
            'product_search' => "Xin chào! Mình hiểu bạn đang tìm sản phẩm phù hợp.\n\n{if_has_entities}Dựa vào thông tin bạn cung cấp:\n- Loại da: {skin_types}\n- Ngân sách: {budget}\n- Vấn đề da: {concerns}\n{endif}\n\n{if_has_products}Mình gợi ý cho bạn {product_count} sản phẩm phù hợp:\n{products_list}\n\nBạn muốn xem chi tiết sản phẩm nào không? 😊{endif}\n\n{if_no_products}Để mình tư vấn chính xác hơn, bạn có thể cho mình biết:\n- Loại da của bạn (dầu, khô, hỗn hợp, nhạy cảm)\n- Ngân sách bạn muốn chi (VD: 300-500k)\n- Vấn đề da bạn đang gặp (mụn, thâm, lỗ chân lông...){endif}",
            
            'shipping_policy' => "**Phí vận chuyển:**\n\n- Miễn phí ship cho đơn từ 500.000₫\n- Phí ship 30.000₫ cho đơn dưới 500.000₫\n- Giao hàng toàn quốc trong 2-5 ngày làm việc\n\nBạn có câu hỏi gì khác về vận chuyển không? 😊",
            
            'return_policy' => "**Chính sách đổi trả:**\n\n- Đổi trả miễn phí trong vòng 7 ngày kể từ ngày nhận hàng\n- Sản phẩm phải còn nguyên seal, chưa sử dụng\n- Chúng tôi sẽ hoàn tiền 100% nếu sản phẩm lỗi từ nhà sản xuất\n\nBạn có câu hỏi gì khác về đổi trả không? 😊",
            
            'payment_policy' => "**Phương thức thanh toán:**\n\n- Thanh toán khi nhận hàng (COD)\n- Chuyển khoản ngân hàng\n- Ví điện tử (MoMo, ZaloPay)\n- Thẻ tín dụng/ghi nợ\n\nTất cả giao dịch đều được bảo mật an toàn!\n\nBạn có câu hỏi gì khác về thanh toán không? 😊",
            
            'order_tracking' => "Để tra cứu đơn hàng, bạn có thể:\n\n1. Nhập mã đơn hàng vào ô tìm kiếm\n2. Hoặc cung cấp số điện thoại đã đặt hàng\n\nBạn có mã đơn hàng hoặc số điện thoại không? Mình sẽ giúp bạn tra cứu ngay! 😊",
            
            'greeting' => "Xin chào! 👋 Mình là CosmeBot, trợ lý tư vấn mỹ phẩm của Cosme House.\n\nMình có thể giúp bạn:\n- Tìm sản phẩm phù hợp với loại da và ngân sách\n- Tra cứu đơn hàng\n- Hỏi về chính sách (ship, đổi trả, thanh toán)\n\nBạn cần mình hỗ trợ gì hôm nay? 😊",
            
            'product_info' => "Mình sẽ cung cấp thông tin chi tiết về sản phẩm cho bạn!\n\n{if_has_products}{products_list}\n\nBạn muốn biết thêm thông tin gì về sản phẩm này không? 😊{endif}\n\n{if_no_products}Bạn muốn tìm hiểu về sản phẩm nào? Hãy cho mình biết tên sản phẩm nhé!{endif}",
            
            'price_inquiry' => "Mình sẽ kiểm tra giá sản phẩm cho bạn!\n\n{if_has_products}{products_list}\n\nBạn có muốn đặt hàng sản phẩm này không? 😊{endif}\n\n{if_no_products}Bạn muốn biết giá của sản phẩm nào? Hãy cho mình biết tên sản phẩm nhé!{endif}",
        ];

        foreach ($templates as $intentName => $template) {
            $intent = BotIntent::where('name', $intentName)->first();
            if ($intent) {
                $config = $intent->config ?? [];
                $config['response_template'] = $template;
                $intent->update(['config' => $config]);
                $this->command->info("✅ Đã thêm response template cho intent: {$intentName}");
            }
        }
    }
}

