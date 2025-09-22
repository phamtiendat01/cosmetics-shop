<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BotSeedSeeder extends Seeder
{
    public function run(): void
    {
        // Aliases mẫu (bạn thêm tiếp tuỳ sản phẩm thật có trong DB)
        $aliases = [
            // ['product_slug','alias']
            ['effaclar-purifying-foaming-gel', 'effaclar gel rửa mặt'],
            ['effaclar-purifying-foaming-gel', 'gel rửa mặt la roche'],
            ['vichy-mineral-89', 'mineral 89'],
            ['vichy-mineral-89', 'serum mineral 89'],
        ];
        $now = now();
        foreach ($aliases as [$slug, $alias]) {
            $pid = DB::table('products')->where('slug', $slug)->value('id');
            if (!$pid) continue;
            DB::table('bot_aliases')->updateOrInsert(
                ['product_id' => $pid, 'alias_norm' => Str::slug($alias)],
                ['alias' => $alias, 'weight' => 2, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        // FAQ mẫu
        $faqs = [
            [
                'pattern' => '(phí ship|giao hàng|vận chuyển)',
                'answer_md' => "Bọn mình giao **1–3 ngày nội thành**, **3–5 ngày liên tỉnh**. Miễn phí từ **499k**. Có xem đơn tại mục *Tra cứu đơn* nhen ✨"
            ],
            [
                'pattern' => '(đổi trả|hoàn tiền)',
                'answer_md' => "Bạn được **đổi trả trong 7 ngày** nếu còn tem/hóa đơn, sản phẩm chưa mở nắp. Liên hệ hotline 1900 1234 để được duyệt nhanh 💖"
            ],
            [
                'pattern' => '(thanh toán|COD|vnpay|momo)',
                'answer_md' => "Hỗ trợ **COD**, chuyển khoản, **VNPAY** và **MOMO**. Mã giảm giá nhập ở bước thanh toán 🎁"
            ],
        ];
        foreach ($faqs as $f) {
            DB::table('bot_faqs')->updateOrInsert(
                ['pattern' => $f['pattern']],
                ['answer_md' => $f['answer_md'], 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }
}
