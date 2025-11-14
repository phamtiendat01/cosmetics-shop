<?php

namespace Database\Seeders;

use App\Models\BotTool;
use Illuminate\Database\Seeder;

class BotToolsQuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $questions = [
            // ========== SHIPPING (Vận chuyển) ==========
            [
                'name' => 'shipping_fee',
                'display_name' => 'Phí vận chuyển',
                'question' => 'Phí ship bao nhiêu?',
                'answer' => "**Phí vận chuyển:**\n\n- ✅ **Miễn phí ship** cho đơn hàng từ 500.000₫\n- 💰 **Phí ship 30.000₫** cho đơn hàng dưới 500.000₫\n- 🚚 **Giao hàng toàn quốc** trong 2-5 ngày làm việc\n- ⚡ **Giao hàng nhanh** (1-2 ngày) với phí bổ sung\n\nBạn có thể kiểm tra phí ship chính xác khi đặt hàng nhé!",
                'category' => 'shipping',
                'order' => 1,
                'icon' => '🚚',
                'description' => 'Thông tin về phí vận chuyển',
                'parameters_schema' => [],
                'handler_class' => '',
                'is_active' => true,
            ],
            [
                'name' => 'shipping_time',
                'display_name' => 'Thời gian giao hàng',
                'question' => 'Giao hàng trong bao lâu?',
                'answer' => "**Thời gian giao hàng:**\n\n- 📦 **Giao hàng tiêu chuẩn:** 2-5 ngày làm việc\n- ⚡ **Giao hàng nhanh:** 1-2 ngày làm việc (có phí bổ sung)\n- 🏠 **Giao hàng tại nhà** hoặc điểm nhận hàng gần nhất\n- 📍 **Áp dụng toàn quốc**, kể cả vùng sâu vùng xa\n\nThời gian giao hàng có thể thay đổi tùy theo địa chỉ và tình hình thời tiết. Bạn sẽ nhận được thông báo khi đơn hàng được giao!",
                'category' => 'shipping',
                'order' => 2,
                'icon' => '⏰',
                'description' => 'Thông tin về thời gian giao hàng',
                'parameters_schema' => [],
                'handler_class' => '',
                'is_active' => true,
            ],
            
            // ========== RETURN (Đổi trả) ==========
            [
                'name' => 'return_policy',
                'display_name' => 'Chính sách đổi trả',
                'question' => 'Có được đổi trả không?',
                'answer' => "**Chính sách đổi trả:**\n\n- ✅ **Đổi/trả trong 7 ngày** kể từ ngày nhận hàng\n- 📦 **Sản phẩm còn nguyên seal**, chưa sử dụng\n- 🎁 **Còn đầy đủ bao bì**, hóa đơn\n- 💰 **Miễn phí đổi trả** nếu lỗi từ phía shop\n- 🔄 **Đổi size/màu** miễn phí (nếu có)\n\n**Lưu ý:** Sản phẩm đã mở seal hoặc sử dụng sẽ không được đổi trả. Liên hệ hotline để được hỗ trợ nhanh nhất!",
                'category' => 'return',
                'order' => 1,
                'icon' => '🔄',
                'description' => 'Chính sách đổi trả sản phẩm',
                'parameters_schema' => [],
                'handler_class' => '',
                'is_active' => true,
            ],
            [
                'name' => 'warranty',
                'display_name' => 'Bảo hành sản phẩm',
                'question' => 'Sản phẩm có bảo hành không?',
                'answer' => "**Chính sách bảo hành:**\n\n- ✅ **Bảo hành chính hãng** từ nhà sản xuất\n- 📅 **Thời hạn bảo hành** tùy theo từng sản phẩm (thường 12-24 tháng)\n- 🛡️ **Bảo hành lỗi kỹ thuật** hoàn toàn miễn phí\n- 📞 **Liên hệ hotline** để được hỗ trợ bảo hành\n\nTất cả sản phẩm tại Cosme House đều là hàng chính hãng, có đầy đủ giấy tờ và được bảo hành theo chính sách của nhà sản xuất!",
                'category' => 'return',
                'order' => 2,
                'icon' => '🛡️',
                'description' => 'Chính sách bảo hành sản phẩm',
                'parameters_schema' => [],
                'handler_class' => '',
                'is_active' => true,
            ],
            
            // ========== PRODUCT (Sản phẩm) ==========
            [
                'name' => 'product_oily_skin',
                'display_name' => 'Tư vấn cho da dầu',
                'question' => 'Sản phẩm nào phù hợp da dầu?',
                'answer' => "**Gợi ý sản phẩm cho da dầu:**\n\n- 🧴 **Sữa rửa mặt:** Chọn loại gel/foam, không chứa dầu, có salicylic acid\n- 💧 **Serum:** Niacinamide, hyaluronic acid, retinol (ban đêm)\n- 🧴 **Kem dưỡng:** Dạng gel hoặc lotion nhẹ, không gây bít tắc lỗ chân lông\n- ☀️ **Kem chống nắng:** Dạng gel, không nhờn, SPF 30-50\n\n**Lưu ý:** Tránh các sản phẩm chứa dầu, dạng cream đặc. Ưu tiên sản phẩm \"oil-free\" và \"non-comedogenic\".\n\nBạn muốn mình tìm sản phẩm cụ thể cho da dầu không?",
                'category' => 'product',
                'order' => 1,
                'icon' => '💧',
                'description' => 'Tư vấn sản phẩm cho da dầu',
                'parameters_schema' => [],
                'handler_class' => '',
                'is_active' => true,
            ],
            [
                'name' => 'product_dry_skin',
                'display_name' => 'Tư vấn cho da khô',
                'question' => 'Sản phẩm nào phù hợp da khô?',
                'answer' => "**Gợi ý sản phẩm cho da khô:**\n\n- 🧴 **Sữa rửa mặt:** Dạng sữa hoặc cream, không tạo bọt, có ceramides\n- 💧 **Serum:** Hyaluronic acid, niacinamide, vitamin C\n- 🧴 **Kem dưỡng:** Dạng cream đậm đặc, chứa ceramides, squalane, shea butter\n- ☀️ **Kem chống nắng:** Dạng cream, có khả năng dưỡng ẩm\n\n**Lưu ý:** Ưu tiên sản phẩm chứa ceramides, hyaluronic acid, và các thành phần dưỡng ẩm. Tránh sản phẩm có cồn hoặc tẩy da chết quá mạnh.\n\nBạn muốn mình tìm sản phẩm cụ thể cho da khô không?",
                'category' => 'product',
                'order' => 2,
                'icon' => '🌿',
                'description' => 'Tư vấn sản phẩm cho da khô',
                'parameters_schema' => [],
                'handler_class' => '',
                'is_active' => true,
            ],
            [
                'name' => 'product_acne',
                'display_name' => 'Tư vấn cho da mụn',
                'question' => 'Sản phẩm nào trị mụn tốt?',
                'answer' => "**Gợi ý sản phẩm cho da mụn:**\n\n- 🧴 **Sữa rửa mặt:** Có salicylic acid (BHA), benzoyl peroxide, hoặc tea tree oil\n- 💧 **Serum:** Niacinamide, salicylic acid, retinol (ban đêm)\n- 🧴 **Kem dưỡng:** Dạng gel nhẹ, không gây bít tắc, có niacinamide\n- ☀️ **Kem chống nắng:** Dạng gel, không nhờn, SPF 30-50 (quan trọng!)\n\n**Lưu ý:**\n- Tránh sản phẩm chứa dầu và dạng cream đặc\n- Sử dụng retinol ban đêm, ban ngày nhớ dùng chống nắng\n- Patch test trước khi dùng sản phẩm mới\n\nBạn muốn mình tìm sản phẩm cụ thể cho da mụn không?",
                'category' => 'product',
                'order' => 3,
                'icon' => '✨',
                'description' => 'Tư vấn sản phẩm cho da mụn',
                'parameters_schema' => [],
                'handler_class' => '',
                'is_active' => true,
            ],
            [
                'name' => 'product_sensitive',
                'display_name' => 'Tư vấn cho da nhạy cảm',
                'question' => 'Sản phẩm nào an toàn cho da nhạy cảm?',
                'answer' => "**Gợi ý sản phẩm cho da nhạy cảm:**\n\n- 🧴 **Sữa rửa mặt:** Dạng sữa nhẹ, không chứa hương liệu, có ceramides\n- 💧 **Serum:** Niacinamide, hyaluronic acid, centella asiatica (cica)\n- 🧴 **Kem dưỡng:** Dạng cream dịu nhẹ, chứa ceramides, không chứa hương liệu\n- ☀️ **Kem chống nắng:** Dạng vật lý (mineral), không chứa hóa chất\n\n**Lưu ý:**\n- Ưu tiên sản phẩm \"fragrance-free\" và \"hypoallergenic\"\n- Tránh sản phẩm có retinol, AHA/BHA mạnh\n- Patch test kỹ trước khi dùng\n\nBạn muốn mình tìm sản phẩm cụ thể cho da nhạy cảm không?",
                'category' => 'product',
                'order' => 4,
                'icon' => '🌸',
                'description' => 'Tư vấn sản phẩm cho da nhạy cảm',
                'parameters_schema' => [],
                'handler_class' => '',
                'is_active' => true,
            ],
            
            // ========== PAYMENT (Thanh toán) ==========
            [
                'name' => 'payment_methods',
                'display_name' => 'Phương thức thanh toán',
                'question' => 'Có những cách thanh toán nào?',
                'answer' => "**Phương thức thanh toán:**\n\n- 💵 **COD (Thanh toán khi nhận hàng)** - Phổ biến nhất\n- 🏦 **Chuyển khoản ngân hàng** - Nhanh chóng, an toàn\n- 📱 **Ví điện tử:** MoMo, ZaloPay, ShopeePay\n- 💳 **Thẻ tín dụng/ghi nợ** - Visa, Mastercard\n- 🎁 **Thanh toán trả góp** - Hỗ trợ trả góp 0% lãi suất\n\n**Lưu ý:**\n- Thanh toán online được giảm thêm 2-5% giá trị đơn hàng\n- COD có phí thu hộ 0-30.000₫ tùy đơn hàng\n\nBạn muốn thanh toán bằng cách nào?",
                'category' => 'payment',
                'order' => 1,
                'icon' => '💳',
                'description' => 'Các phương thức thanh toán',
                'parameters_schema' => [],
                'handler_class' => '',
                'is_active' => true,
            ],
            [
                'name' => 'coupon_discount',
                'display_name' => 'Mã giảm giá',
                'question' => 'Có mã giảm giá không?',
                'answer' => "**Mã giảm giá & Khuyến mãi:**\n\n- 🎁 **Giảm 5-10%** cho đơn hàng đầu tiên\n- 💰 **Giảm 15-20%** cho đơn từ 1.000.000₫\n- 🎉 **Flash sale** hàng tuần với giá cực sốc\n- 📧 **Đăng ký nhận tin** để nhận mã giảm giá độc quyền\n- 🎊 **Sinh nhật khách hàng** - Giảm 20% trong tháng sinh nhật\n\n**Cách sử dụng:**\nNhập mã giảm giá tại bước thanh toán. Mã sẽ được áp dụng tự động!\n\nBạn muốn xem các mã giảm giá hiện có không?",
                'category' => 'payment',
                'order' => 2,
                'icon' => '🎁',
                'description' => 'Thông tin về mã giảm giá',
                'parameters_schema' => [],
                'handler_class' => '',
                'is_active' => true,
            ],
            
            // ========== GENERAL (Chung) ==========
            [
                'name' => 'order_tracking',
                'display_name' => 'Tra cứu đơn hàng',
                'question' => 'Làm sao để tra cứu đơn hàng?',
                'answer' => "**Cách tra cứu đơn hàng:**\n\n- 📱 **Nhập mã đơn hàng** (VD: #DH123456) vào ô tìm kiếm\n- 📞 **Gọi hotline** với số điện thoại đặt hàng\n- 💬 **Chat với CSKH** và cung cấp mã đơn hoặc số điện thoại\n- 📧 **Email** mã đơn đến support@cosmehouse.com\n\n**Thông tin bạn sẽ nhận được:**\n- Trạng thái đơn hàng (đã xác nhận, đang giao, đã giao...)\n- Thời gian giao hàng dự kiến\n- Địa chỉ giao hàng\n- Phương thức thanh toán\n\nBạn có mã đơn hàng hoặc số điện thoại đặt hàng không?",
                'category' => 'general',
                'order' => 1,
                'icon' => '📦',
                'description' => 'Hướng dẫn tra cứu đơn hàng',
                'parameters_schema' => [],
                'handler_class' => '',
                'is_active' => true,
            ],
            [
                'name' => 'contact_support',
                'display_name' => 'Liên hệ hỗ trợ',
                'question' => 'Làm sao liên hệ với shop?',
                'answer' => "**Thông tin liên hệ:**\n\n- 📞 **Hotline:** 1900-xxxx (8:00 - 22:00 hàng ngày)\n- 💬 **Chat trực tuyến:** Trong chat widget này\n- 📧 **Email:** support@cosmehouse.com\n- 📍 **Địa chỉ cửa hàng:** [Địa chỉ cửa hàng]\n- 🌐 **Website:** www.cosmehouse.com\n- 📱 **Facebook:** facebook.com/cosmehouse\n- 📸 **Instagram:** @cosmehouse\n\n**Thời gian hỗ trợ:**\n- Chat/Email: 24/7\n- Hotline: 8:00 - 22:00 hàng ngày\n- Cửa hàng: 9:00 - 21:00 hàng ngày\n\nMình có thể giúp gì thêm cho bạn không?",
                'category' => 'general',
                'order' => 2,
                'icon' => '📞',
                'description' => 'Thông tin liên hệ hỗ trợ',
                'parameters_schema' => [],
                'handler_class' => '',
                'is_active' => true,
            ],
        ];

        foreach ($questions as $question) {
            BotTool::updateOrCreate(
                ['name' => $question['name']],
                $question
            );
        }

        $this->command->info('✅ Đã tạo ' . count($questions) . ' câu hỏi tự động cho chatbot!');
    }
}
