<?php

namespace App\Services\Bot;

use App\Models\BotIntent;
use App\Services\Bot\LLMService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * IntentClassifier - Phân loại ý định của user
 * Sử dụng LLM + Rule-based fallback
 */
class IntentClassifier
{
    private const MIN_CONFIDENCE = 0.6;

    public function __construct(
        private LLMService $llmService
    ) {}

    /**
     * Phân loại intent từ message
     * 
     * @param string $message
     * @param array $context
     * @return array {intent: string, confidence: float}
     */
    public function classify(string $message, array $context = []): array
    {
        // 1. Rule-based fast path (cho các intent rõ ràng)
        $ruleBased = $this->classifyByRules($message);
        if ($ruleBased['confidence'] >= 0.9) {
            return $ruleBased;
        }

        // 2. LLM-based classification (nếu có API key)
        if ($this->llmService->enabled()) {
            try {
                $llmResult = $this->llmService->classifyIntent($message, $context);
                if ($llmResult && ($llmResult['confidence'] ?? 0) >= self::MIN_CONFIDENCE) {
                    return $llmResult;
                }
            } catch (\Throwable $e) {
                // Fallback về rule-based nếu LLM fail
            }
        }

        // 3. Fallback về rule-based
        return $ruleBased;
    }

    /**
     * Rule-based classification (fallback)
     */
    private function classifyByRules(string $message): array
    {
        $lower = Str::lower($message);
        
        // Load intents từ database
        $intents = Cache::remember('bot.intents.active', 300, function () {
            return BotIntent::active()->orderedByPriority()->get();
        });

        // Check database intents first (check examples)
        foreach ($intents as $intent) {
            $examples = $intent->examples ?? [];
            foreach ($examples as $example) {
                $exampleLower = Str::lower($example);
                // Simple contains check
                if (Str::contains($lower, $exampleLower) || Str::contains($exampleLower, $lower)) {
                    return [
                        'intent' => $intent->name,
                        'confidence' => 0.85,
                    ];
                }
            }
        }
        
        // ========== INTENT PHỨC TẠP - XỬ LÝ TRƯỚC ==========
        
        // 1. So sánh sản phẩm (product_comparison)
        if (preg_match('/\b(so sánh|compare|khác nhau|giống nhau|nên chọn|nên mua|tốt hơn|hơn|vs|với)\b/u', $lower) 
            && preg_match('/\b(sản phẩm|product|serum|kem|cleanser|chống nắng|sunscreen)\b/u', $lower)) {
            return ['intent' => 'product_comparison', 'confidence' => 0.95];
        }
        
        // 2. Hỏi về thành phần (ingredient_inquiry)
        if (preg_match('/\b(thành phần|ingredient|ingredients|có gì|chứa|bao gồm|hyaluronic|niacinamide|retinol|vitamin c|salicylic|glycolic|peptide|ceramide|snail|centella|tea tree|aloe)\b/u', $lower)) {
            return ['intent' => 'ingredient_inquiry', 'confidence' => 0.9];
        }
        
        // 3. Hỏi về cách sử dụng (usage_inquiry)
        if (preg_match('/\b(cách dùng|hướng dẫn|sử dụng|dùng như thế nào|dùng khi nào|dùng bao nhiêu|dùng mấy lần|dùng trước hay sau|routine|quy trình)\b/u', $lower)) {
            return ['intent' => 'usage_inquiry', 'confidence' => 0.95];
        }
        
        // 4. Tư vấn theo vấn đề da (skin_concern_consultation)
        if (preg_match('/\b(mụn|acne|thâm|dark spot|nám|melasma|lỗ chân lông|pore|lão hóa|aging|nhăn|wrinkle|khô|dry|dầu|oily|nhạy cảm|sensitive|kích ứng|irritation)\b/u', $lower)
            && preg_match('/\b(tư vấn|advice|gợi ý|nên|phù hợp|giải quyết|khắc phục|điều trị|treatment)\b/u', $lower)) {
            return ['intent' => 'skin_concern_consultation', 'confidence' => 0.9];
        }
        
        // 5. Hỏi về giá/khuyến mãi (price_inquiry)
        if (preg_match('/\b(giá|price|bao nhiêu|cost|giảm giá|sale|khuyến mãi|promotion|discount|giảm|ưu đãi|deal)\b/u', $lower)) {
            return ['intent' => 'price_inquiry', 'confidence' => 0.9];
        }
        
        // 6. Hỏi về review/đánh giá (review_inquiry)
        if (preg_match('/\b(review|đánh giá|feedback|ý kiến|kinh nghiệm|dùng thử|test|review|đánh giá|tốt không|có tốt|hiệu quả)\b/u', $lower)) {
            return ['intent' => 'review_inquiry', 'confidence' => 0.9];
        }
        
        // 7. Hỏi về thông tin sản phẩm cụ thể (product_info)
        if (preg_match('/\b(thông tin|info|chi tiết|details|đặc điểm|features|tính năng|benefits|lợi ích|phù hợp với|dành cho|cho ai)\b/u', $lower)
            && preg_match('/\b(sản phẩm|product|serum|kem|cleanser|chống nắng|sunscreen)\b/u', $lower)) {
            return ['intent' => 'product_info', 'confidence' => 0.85];
        }
        
        // 8. Gợi ý routine (routine_suggestion)
        if (preg_match('/\b(routine|quy trình|skincare|chăm sóc da|chu trình|bước|step|thứ tự|order|nên dùng|dùng trước|dùng sau)\b/u', $lower)) {
            return ['intent' => 'routine_suggestion', 'confidence' => 0.9];
        }
        
        // ========== INTENT ĐƠN GIẢN ==========
        
        // Return/Exchange - check TRƯỚC product search để tránh conflict
        if (preg_match('/\b(đổi|trả|hoàn|return|exchange|bảo hành|chính sách đổi|chính sách trả)\b/u', $lower)) {
            return ['intent' => 'return_policy', 'confidence' => 0.95];
        }
        
        // Product search với loại da (check trước vì specific hơn)
        if (preg_match('/\b(da dầu|da khô|da hỗn hợp|da nhạy cảm|oily|dry|combination|sensitive)\b/u', $lower) 
            && preg_match('/\b(sữa rửa mặt|rửa mặt|serum|kem|cleanser|toner|essence|chống nắng|sunscreen|product|sản phẩm)\b/u', $lower)) {
            return ['intent' => 'product_search', 'confidence' => 0.95];
        }
        
        // Product search - high priority (check các từ khóa sản phẩm)
        // Check "sữa rửa mặt" trước (cụ thể hơn) - check cả "cho da"
        if (preg_match('/\b(sữa rửa mặt|rửa mặt)\b/u', $lower)) {
            return ['intent' => 'product_search', 'confidence' => 0.95];
        }
        
        // Check các từ khóa sản phẩm khác
        if (preg_match('/\b(cleanser|foam|gel|serum|kem|cream|chống nắng|sunscreen|spf|toner|essence|mask|mặt nạ)\b/u', $lower)) {
            return ['intent' => 'product_search', 'confidence' => 0.9];
        }
        
        // Product search với từ khóa tìm kiếm
        if (preg_match('/\b(tìm|search|mua|mua gì|sản phẩm|sp|product)\b/u', $lower)) {
            return ['intent' => 'product_search', 'confidence' => 0.85];
        }
        
        // Product search với ngân sách
        if (preg_match('/\b(\d+[kK]|\d+\s*000|\d+\s*tr|ngân sách|budget|giá|price|dưới)\b/u', $lower)) {
            return ['intent' => 'product_search', 'confidence' => 0.9];
        }
        
        // Greeting
        if (preg_match('/\b(xin chào|chào|hello|hi|hey|alo)\b/u', $lower)) {
            return ['intent' => 'greeting', 'confidence' => 0.95];
        }
        
        // Order tracking
        if (preg_match('/\b(đơn hàng|order|tra cứu|mã đơn|đơn|tracking)\b/u', $lower)) {
            return ['intent' => 'order_tracking', 'confidence' => 0.9];
        }
        
        // Shipping
        if (preg_match('/\b(phí ship|ship|vận chuyển|giao hàng|shipping|phí vận chuyển)\b/u', $lower)) {
            return ['intent' => 'shipping_policy', 'confidence' => 0.95];
        }
        
        // Payment
        if (preg_match('/\b(thanh toán|payment|pay|tiền|cod|chuyển khoản|ví điện tử|momo|zalopay)\b/u', $lower)) {
            return ['intent' => 'payment_policy', 'confidence' => 0.9];
        }
        
        // Default
        return ['intent' => 'unknown', 'confidence' => 0.5];
    }
}
