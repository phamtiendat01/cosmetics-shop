<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class UpdateProductTypes extends Command
{
    protected $signature = 'products:update-types {--force : Overwrite existing product_type}';
    protected $description = 'Tự động cập nhật product_type cho tất cả sản phẩm dựa trên tên và mô tả';

    public function handle()
    {
        $force = $this->option('force');
        
        $this->info('🔍 Đang phân tích và cập nhật product_type cho sản phẩm...');
        
        $products = Product::where('is_active', 1)->get();
        $total = $products->count();
        $updated = 0;
        $skipped = 0;
        $errors = 0;
        
        $bar = $this->output->createProgressBar($total);
        $bar->start();
        
        foreach ($products as $product) {
            try {
                // Nếu đã có product_type và không force, skip
                if (!$force && !empty($product->product_type)) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }
                
                // Phân tích và xác định product_type
                $productType = $this->detectProductType($product);
                
                if ($productType) {
                    $product->product_type = $productType;
                    $product->save();
                    $updated++;
                } else {
                    $skipped++;
                }
                
                $bar->advance();
            } catch (\Throwable $e) {
                $errors++;
                $this->newLine();
                $this->warn("Lỗi khi xử lý sản phẩm ID {$product->id}: {$e->getMessage()}");
                $bar->advance();
            }
        }
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info("✅ Hoàn tất!");
        $this->info("   - Tổng sản phẩm: {$total}");
        $this->info("   - Đã cập nhật: {$updated}");
        $this->info("   - Đã bỏ qua: {$skipped}");
        if ($errors > 0) {
            $this->warn("   - Lỗi: {$errors}");
        }
        
        // Hiển thị thống kê
        $this->newLine();
        $this->info("📊 Thống kê product_type:");
        $stats = Product::where('is_active', 1)
            ->selectRaw('product_type, COUNT(*) as count')
            ->groupBy('product_type')
            ->orderByDesc('count')
            ->get();
        
        foreach ($stats as $stat) {
            $type = $stat->product_type ?: '(NULL)';
            $this->line("   - {$type}: {$stat->count}");
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Phát hiện product_type dựa trên tên và mô tả
     */
    private function detectProductType(Product $product): ?string
    {
        // Lấy text để phân tích
        $text = Str::lower(
            ($product->name ?? '') . ' ' . 
            ($product->short_desc ?? '') . ' ' . 
            ($product->long_desc ?? '') . ' ' . 
            ($product->description ?? '')
        );
        
        // Kiểm tra theo thứ tự từ specific đến general
        // 1. Chống nắng (sunscreen) - check trước vì có thể có "kem chống nắng"
        if (Str::contains($text, ['chống nắng', 'sunscreen', 'spf', 'sun protection', 'uv protection', 'fotoprotector', 'anthelios'])) {
            return 'sunscreen';
        }
        
        // 2. Sữa rửa mặt (cleanser)
        if (Str::contains($text, ['sữa rửa mặt', 'rửa mặt', 'cleanser', 'cleansing', 'foam', 'gel moussant', 'gel rửa', 'foaming gel'])) {
            return 'cleanser';
        }
        
        // 3. Serum
        if (Str::contains($text, ['serum', 'booster', 'concentrate', 'power infusing'])) {
            return 'serum';
        }
        
        // 4. Essence
        if (Str::contains($text, ['essence', 'treatment essence', 'facial treatment'])) {
            return 'essence';
        }
        
        // 5. Kem mắt (eye cream)
        if (Str::contains($text, ['kem mắt', 'eye cream', 'eye serum', 'k-ox eyes', 'eye treatment'])) {
            return 'eye_cream';
        }
        
        // 6. Toner
        if (Str::contains($text, ['toner', 'toning', 'astringent'])) {
            return 'toner';
        }
        
        // 7. Mặt nạ (mask)
        if (Str::contains($text, ['mặt nạ', 'mask', 'sheet mask', 'clay mask'])) {
            return 'mask';
        }
        
        // 8. Dưỡng ẩm (moisturizer)
        if (Str::contains($text, ['dưỡng ẩm', 'moisturizer', 'moisturizing', 'moisture surge', 'water bank', 'moisture booster'])) {
            return 'moisturizer';
        }
        
        // 9. Kem (cream) - check sau các loại khác
        if (Str::contains($text, ['kem', 'cream', 'lotion', 'balm'])) {
            // Kiểm tra không phải chống nắng
            if (!Str::contains($text, ['chống nắng', 'sunscreen', 'spf'])) {
                return 'cream';
            }
        }
        
        // 10. Son môi (lipstick)
        if (Str::contains($text, ['son', 'lipstick', 'lip', 'rouge'])) {
            return 'other'; // Hoặc có thể tạo 'lipstick' nếu cần
        }
        
        // 11. Dầu gội (shampoo)
        if (Str::contains($text, ['dầu gội', 'shampoo', 'kelual'])) {
            return 'other';
        }
        
        // 12. Sữa tắm (body wash)
        if (Str::contains($text, ['sữa tắm', 'body wash', 'cleansing gel', 'gentle cleansing'])) {
            return 'other';
        }
        
        // Không xác định được
        return null;
    }
}
