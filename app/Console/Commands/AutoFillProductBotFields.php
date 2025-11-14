<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class AutoFillProductBotFields extends Command
{
    protected $signature = 'products:auto-fill-bot-fields {--force : Overwrite existing data}';
    protected $description = 'Tự động điền thông tin bot fields cho tất cả sản phẩm dựa trên tên, mô tả và category';

    public function handle()
    {
        $force = $this->option('force');
        
        $this->info('🔍 Đang phân tích và điền thông tin cho sản phẩm...');
        
        $products = Product::with('category')->get();
        $total = $products->count();
        $updated = 0;
        $skipped = 0;
        
        $bar = $this->output->createProgressBar($total);
        $bar->start();
        
        foreach ($products as $product) {
            $data = $this->analyzeProduct($product);
            
            // Skip nếu đã có dữ liệu và không force
            if (!$force && !empty($product->skin_types)) {
                $skipped++;
                $bar->advance();
                continue;
            }
            
            // Update product
            $product->update($data);
            $updated++;
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info("✅ Hoàn tất!");
        $this->info("   - Tổng sản phẩm: {$total}");
        $this->info("   - Đã cập nhật: {$updated}");
        $this->info("   - Đã bỏ qua: {$skipped}");
        
        return Command::SUCCESS;
    }
    
    private function analyzeProduct(Product $product): array
    {
        $text = Str::lower($product->name . ' ' . ($product->short_desc ?? '') . ' ' . ($product->long_desc ?? '') . ' ' . ($product->description ?? ''));
        $categoryName = $product->category ? Str::lower($product->category->name) : '';
        
        return [
            'skin_types' => $this->detectSkinTypes($text, $categoryName),
            'concerns' => $this->detectConcerns($text, $categoryName),
            'ingredients' => $this->detectIngredients($text),
            'product_type' => $this->detectProductType($text, $categoryName),
            'texture' => $this->detectTexture($text),
            'age_range' => $this->detectAgeRange($text, $categoryName),
            'gender' => $this->detectGender($text),
            'spf' => $this->detectSPF($text),
            'benefits' => $this->generateBenefits($text, $categoryName),
            'usage_instructions' => $this->generateUsageInstructions($text, $categoryName),
            'fragrance_free' => $this->detectFragranceFree($text),
            'cruelty_free' => $this->detectCrueltyFree($text, $product->brand_id),
            'vegan' => $this->detectVegan($text, $product->brand_id),
        ];
    }
    
    private function detectSkinTypes(string $text, string $categoryName): array
    {
        $skinTypes = [];
        
        // Keywords cho từng loại da
        $patterns = [
            'oily' => ['da dầu', 'dầu', 'oily', 'nhờn', 'bóng dầu', 'kiểm soát dầu', 'oil', 'sebum'],
            'dry' => ['da khô', 'khô', 'dry', 'thiếu ẩm', 'mất nước', 'dehydration', 'khô căng'],
            'combination' => ['hỗn hợp', 'combination', 'mixed'],
            'sensitive' => ['nhạy cảm', 'sensitive', 'kích ứng', 'dị ứng', 'irritation', 'dịu nhẹ', 'gentle'],
            'normal' => ['thường', 'normal', 'mọi loại da', 'all skin types'],
        ];
        
        foreach ($patterns as $type => $keywords) {
            foreach ($keywords as $keyword) {
                if (Str::contains($text, $keyword)) {
                    $skinTypes[] = $type;
                    break;
                }
            }
        }
        
        // Default: nếu không tìm thấy, set 'normal' và 'combination'
        if (empty($skinTypes)) {
            $skinTypes = ['normal', 'combination'];
        }
        
        return array_unique($skinTypes);
    }
    
    private function detectConcerns(string $text, string $categoryName): array
    {
        $concerns = [];
        
        $patterns = [
            'acne' => ['mụn', 'acne', 'breakout', 'bacterial', 'proacne', 'sebiaclear', 'keracnyl'],
            'blackheads' => ['đầu đen', 'blackhead', 'comedone'],
            'dark_spots' => ['thâm', 'dark spot', 'đốm nâu', 'hyperpigmentation', 'uneven', 'radiance', 'brightening'],
            'melasma' => ['nám', 'melasma', 'pigmentation'],
            'freckles' => ['tàn nhang', 'freckle'],
            'pores' => ['lỗ chân lông', 'pore', 'se khít', 'smoothing', 'refining'],
            'aging' => ['lão hóa', 'aging', 'wrinkle', 'nhăn', 'anti-aging', 'retinol', 'correxion', 'time-filler'],
            'hydration' => ['dưỡng ẩm', 'hydration', 'moisture', 'cấp ẩm', 'cấp nước', 'hyaluronic', 'water', 'hydro'],
            'sunburn' => ['cháy nắng', 'sunburn', 'spf', 'chống nắng', 'sunscreen', 'uv'],
        ];
        
        foreach ($patterns as $concern => $keywords) {
            foreach ($keywords as $keyword) {
                if (Str::contains($text, $keyword)) {
                    $concerns[] = $concern;
                    break;
                }
            }
        }
        
        return array_unique($concerns);
    }
    
    private function detectIngredients(string $text): array
    {
        $ingredients = [];
        
        $patterns = [
            'hyaluronic_acid' => ['hyaluronic', 'ha', 'hyaluron', 'water bank'],
            'niacinamide' => ['niacinamide', 'vitamin b3'],
            'retinol' => ['retinol', 'retinoid', 'vitamin a'],
            'vitamin_c' => ['vitamin c', 'ascorbic', 'c e ferulic', 'professional-c'],
            'salicylic_acid' => ['salicylic', 'bha', 'beta hydroxy'],
            'glycolic_acid' => ['glycolic', 'aha', 'alpha hydroxy'],
            'peptides' => ['peptide', 'polypeptide'],
            'ceramides' => ['ceramide', 'cerave'],
            'snail_mucin' => ['snail', 'mucin', 'ốc sên'],
            'centella' => ['centella', 'cica', 'cicapair', 'asiatica'],
            'tea_tree' => ['tea tree', 'tràm trà'],
            'aloe_vera' => ['aloe', 'lô hội'],
        ];
        
        foreach ($patterns as $ingredient => $keywords) {
            foreach ($keywords as $keyword) {
                if (Str::contains($text, $keyword)) {
                    $ingredients[] = $ingredient;
                    break;
                }
            }
        }
        
        return array_unique($ingredients);
    }
    
    private function detectProductType(string $text, string $categoryName): ?string
    {
        // Priority: Check for sunscreen first (most specific)
        if (Str::contains($text, 'spf') || Str::contains($text, 'chống nắng') || 
            Str::contains($text, 'sunscreen') || Str::contains($text, 'sun') || 
            Str::contains($text, 'photoderm') || Str::contains($text, 'anthelios') ||
            Str::contains($text, 'uv clear') || Str::contains($text, 'fotoprotector')) {
            return 'sunscreen';
        }
        
        // Check category and text
        $categoryMap = [
            'serum' => ['serum', 'tinh chất', 'booster', 'concentrate', 'solution'],
            'cream' => ['cream', 'kem', 'crème', 'moisturizing cream', 'recovery cream'],
            'toner' => ['toner', 'nước hoa hồng'],
            'cleanser' => ['cleanser', 'rửa mặt', 'sữa rửa mặt', 'foam', 'foaming', 'gel moussant', 'purifying'],
            'moisturizer' => ['moisturizer', 'dưỡng ẩm', 'moisturizing', 'water gel', 'water cream'],
            'mask' => ['mask', 'mặt nạ'],
            'essence' => ['essence', 'tinh chất nước', 'treatment essence'],
            'eye_cream' => ['eye', 'mắt', 'k-ox eyes'],
        ];
        
        foreach ($categoryMap as $type => $keywords) {
            foreach ($keywords as $keyword) {
                if (Str::contains($categoryName, $keyword) || Str::contains($text, $keyword)) {
                    return $type;
                }
            }
        }
        
        // Fallback based on name patterns
        if (Str::contains($text, 'lipstick') || Str::contains($text, 'son') || Str::contains($text, 'lip balm')) {
            return 'other'; // Makeup
        }
        
        return null;
    }
    
    private function detectTexture(string $text): ?string
    {
        $patterns = [
            'gel' => ['gel', 'jelly', 'gél'],
            'cream' => ['cream', 'kem', 'crème'],
            'liquid' => ['liquid', 'lỏng', 'essence', 'toner'],
            'foam' => ['foam', 'bọt', 'foaming', 'moussant'],
            'oil' => ['oil', 'dầu'],
            'balm' => ['balm', 'bơ'],
            'powder' => ['powder', 'bột'],
            'spray' => ['spray', 'xịt'],
        ];
        
        foreach ($patterns as $texture => $keywords) {
            foreach ($keywords as $keyword) {
                if (Str::contains($text, $keyword)) {
                    return $texture;
                }
            }
        }
        
        return null;
    }
    
    private function detectAgeRange(string $text, string $categoryName): ?string
    {
        if (Str::contains($text, 'baby') || Str::contains($text, 'em bé') || Str::contains($text, 'mustela')) {
            return 'teen';
        }
        
        if (Str::contains($text, 'retinol') || Str::contains($text, 'anti-aging') || Str::contains($text, 'wrinkle')) {
            return 'mature';
        }
        
        return 'all';
    }
    
    private function detectGender(string $text): string
    {
        // Most cosmetics are unisex
        if (Str::contains($text, 'men') || Str::contains($text, 'nam')) {
            return 'male';
        }
        
        if (Str::contains($text, 'women') || Str::contains($text, 'nữ')) {
            return 'female';
        }
        
        return 'unisex';
    }
    
    private function detectSPF(string $text): ?int
    {
        // Extract SPF number
        if (preg_match('/spf\s*(\d+)/i', $text, $matches)) {
            $spf = (int)$matches[1];
            return min($spf, 100); // Cap at 100
        }
        
        // Check for specific SPF mentions
        if (Str::contains($text, 'spf50') || Str::contains($text, 'spf 50')) {
            return 50;
        }
        
        if (Str::contains($text, 'spf30') || Str::contains($text, 'spf 30')) {
            return 30;
        }
        
        return null;
    }
    
    private function generateBenefits(string $text, string $categoryName): ?string
    {
        $benefits = [];
        
        // Extract benefits from text
        if (Str::contains($text, 'dưỡng ẩm') || Str::contains($text, 'moisture') || Str::contains($text, 'hydration')) {
            $benefits[] = 'Dưỡng ẩm sâu';
        }
        
        if (Str::contains($text, 'mụn') || Str::contains($text, 'acne')) {
            $benefits[] = 'Hỗ trợ giảm mụn';
        }
        
        if (Str::contains($text, 'thâm') || Str::contains($text, 'dark spot') || Str::contains($text, 'brightening')) {
            $benefits[] = 'Làm mờ thâm';
        }
        
        if (Str::contains($text, 'lỗ chân lông') || Str::contains($text, 'pore')) {
            $benefits[] = 'Se khít lỗ chân lông';
        }
        
        if (Str::contains($text, 'chống nắng') || Str::contains($text, 'spf') || Str::contains($text, 'sunscreen')) {
            $benefits[] = 'Bảo vệ da khỏi tia UV';
        }
        
        if (Str::contains($text, 'lão hóa') || Str::contains($text, 'aging') || Str::contains($text, 'wrinkle')) {
            $benefits[] = 'Chống lão hóa';
        }
        
        if (Str::contains($text, 'làm sạch') || Str::contains($text, 'cleanser') || Str::contains($text, 'cleansing')) {
            $benefits[] = 'Làm sạch sâu';
        }
        
        return !empty($benefits) ? implode(', ', $benefits) : null;
    }
    
    private function generateUsageInstructions(string $text, string $categoryName): ?string
    {
        // Check if already has usage instructions in description
        if (Str::contains($text, 'hướng dẫn') || Str::contains($text, 'cách dùng')) {
            return null; // Let admin fill manually
        }
        
        $instructions = [];
        
        if (Str::contains($text, 'cleanser') || Str::contains($text, 'rửa mặt') || Str::contains($text, 'foam')) {
            $instructions[] = 'Dùng sáng và tối: làm ướt da, tạo bọt, massage 20-30 giây rồi rửa sạch';
        }
        
        if (Str::contains($text, 'serum') || Str::contains($text, 'essence')) {
            $instructions[] = 'Sau khi làm sạch và toner, thoa đều lên mặt, vỗ nhẹ để thấm';
        }
        
        if (Str::contains($text, 'cream') || Str::contains($text, 'kem dưỡng')) {
            $instructions[] = 'Thoa sau serum, sáng và tối. Ban ngày dùng kèm kem chống nắng';
        }
        
        if (Str::contains($text, 'spf') || Str::contains($text, 'chống nắng') || Str::contains($text, 'sunscreen')) {
            $instructions[] = 'Thoa đủ lượng 15-20 phút trước khi ra nắng. Bôi lại sau 2-3 giờ';
        }
        
        if (Str::contains($text, 'retinol')) {
            $instructions[] = 'Dùng buổi tối, bắt đầu 2-3 lần/tuần, tăng dần tần suất. Nhớ dùng chống nắng ban ngày';
        }
        
        return !empty($instructions) ? implode('. ', $instructions) . '.' : null;
    }
    
    private function detectFragranceFree(string $text): bool
    {
        return Str::contains($text, 'fragrance free') || 
               Str::contains($text, 'không mùi') ||
               Str::contains($text, 'unscented') ||
               Str::contains($text, 'sensitive') && Str::contains($text, 'gentle');
    }
    
    private function detectCrueltyFree(string $text, ?int $brandId): bool
    {
        // Some brands are known to be cruelty-free
        $crueltyFreeBrands = []; // Add brand IDs if known
        
        if (in_array($brandId, $crueltyFreeBrands)) {
            return true;
        }
        
        return Str::contains($text, 'cruelty free') || 
               Str::contains($text, 'không test động vật');
    }
    
    private function detectVegan(string $text, ?int $brandId): bool
    {
        // Some brands are known to be vegan
        $veganBrands = []; // Add brand IDs if known
        
        if (in_array($brandId, $veganBrands)) {
            return true;
        }
        
        return Str::contains($text, 'vegan') || 
               Str::contains($text, 'thuần chay');
    }
}
