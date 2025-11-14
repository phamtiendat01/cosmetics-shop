<?php

namespace App\Tools\Bot;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * PolicyTool - Lấy thông tin chính sách
 */
class PolicyTool
{
    public function execute(string $message, array $context): ?array
    {
        $topic = $this->extractTopic($message);
        
        if (!$topic) {
            return null;
        }
        
        $key = 'policy.' . $this->formatTopic($topic);
        
        // Tìm trong settings
        if (Schema::hasTable('settings')) {
            $setting = DB::table('settings')->where('key', $key)->first();
            if ($setting) {
                return [
                    'found' => true,
                    'topic' => $topic,
                    'content' => (string)$setting->value,
                ];
            }
        }
        
        // Fallback content
        $fallback = $this->getFallbackContent($topic);
        
        return [
            'found' => true,
            'topic' => $topic,
            'content' => $fallback,
        ];
    }
    
    private function extractTopic(string $message): ?string
    {
        $lower = Str::lower($message);
        
        if (Str::contains($lower, ['phí ship', 'vận chuyển', 'giao hàng', 'shipping'])) {
            return 'shipping';
        }
        
        if (Str::contains($lower, ['đổi trả', 'hoàn tiền', 'return', 'refund'])) {
            return 'return';
        }
        
        if (Str::contains($lower, ['thanh toán', 'payment', 'cod', 'vnpay', 'momo'])) {
            return 'payment';
        }
        
        if (Str::contains($lower, ['bảo hành', 'warranty'])) {
            return 'warranty';
        }
        
        return null;
    }
    
    private function formatTopic(string $topic): string
    {
        $map = [
            'ship' => 'shipping',
            'vận chuyển' => 'shipping',
            'giao hàng' => 'shipping',
            'đổi trả' => 'return',
            'hoàn tiền' => 'refund',
            'bảo hành' => 'warranty',
            'thanh toán' => 'payment',
        ];
        
        return $map[$topic] ?? Str::slug($topic);
    }
    
    private function getFallbackContent(string $topic): string
    {
        $contents = [
            'shipping' => "**Phí vận chuyển:**\n- Nội thành: 30.000₫\n- Ngoại thành: 50.000₫\n- Miễn phí ship cho đơn hàng từ 500.000₫\n- Thời gian giao hàng: 2-5 ngày làm việc",
            'return' => "**Chính sách đổi trả:**\n- Đổi trả trong vòng 7 ngày kể từ ngày nhận hàng\n- Sản phẩm phải còn nguyên seal, chưa sử dụng\n- Hoàn tiền 100% nếu sản phẩm lỗi từ nhà sản xuất",
            'payment' => "**Phương thức thanh toán:**\n- COD (Thanh toán khi nhận hàng)\n- VNPay\n- Momo\n- VietQR\n- Chuyển khoản ngân hàng",
            'warranty' => "**Chính sách bảo hành:**\n- Bảo hành 1 đổi 1 trong vòng 30 ngày nếu sản phẩm lỗi\n- Liên hệ hotline hoặc email để được hỗ trợ",
        ];
        
        return $contents[$topic] ?? 'Thông tin đang được cập nhật. Vui lòng liên hệ bộ phận hỗ trợ để biết thêm chi tiết.';
    }
}

