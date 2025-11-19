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
        // 0. ✅ Checkout flow intents - ƯU TIÊN CAO NHẤT (trước tất cả)
        $checkoutState = $context['checkout_state'] ?? null;
        if ($checkoutState && $checkoutState !== 'idle' && $checkoutState !== 'order_placed') {
            $lower = Str::lower(trim($message));
            
            // ✅ Apply/Skip coupon (khi đang ở coupon_asked hoặc cart_added)
            if ($checkoutState === 'coupon_asked' || $checkoutState === 'cart_added') {
                // Skip coupon
                if (preg_match('/\b(không|không có|bỏ qua|skip|không cần|thôi|không muốn|không dùng)\b/u', $lower)) {
                    return ['intent' => 'checkout_skip_coupon', 'confidence' => 0.98];
                }
                // Apply coupon với mã cụ thể hoặc số thứ tự
                if (preg_match('/\b(mã|code)\s+([A-Z0-9]+)\b/u', Str::upper($message)) || 
                    preg_match('/\b(số|thứ)\s*(\d+)\b/u', $lower)) {
                    return ['intent' => 'checkout_apply_coupon', 'confidence' => 0.98];
                }
                // Có muốn áp mã không?
                if (preg_match('/\b(có|muốn|áp|dùng|sử dụng)\b/u', $lower)) {
                    return ['intent' => 'checkout_coupon_response', 'confidence' => 0.95];
                }
                // Mặc định là hỏi về coupon
                return ['intent' => 'checkout_coupon_response', 'confidence' => 0.9];
            }
            
            // ✅ Select address (khi đang ở address_asked hoặc coupon_applied)
            if ($checkoutState === 'address_asked' || $checkoutState === 'coupon_applied') {
                // Chọn địa chỉ theo số
                if (preg_match('/\b(?:địa chỉ|address)\s*(?:số|thứ)\s*(\d+)\b/u', $lower) || 
                    preg_match('/\b(số|thứ)\s*(\d+)\b/u', $lower)) {
                    return ['intent' => 'checkout_select_address', 'confidence' => 0.98];
                }
                // Nói về địa chỉ
                if (preg_match('/\b(?:địa chỉ|address|giao hàng|ship)\b/u', $lower)) {
                    return ['intent' => 'checkout_select_address', 'confidence' => 0.95];
                }
            }
            
            // ✅ Apply/Skip shipping voucher (khi đang ở shipping_voucher_asked, shipping_calculated, hoặc address_confirmed)
            if ($checkoutState === 'shipping_voucher_asked' || $checkoutState === 'shipping_calculated' || $checkoutState === 'address_confirmed') {
                // Skip shipping voucher - bao gồm "Tiếp tục"
                if (preg_match('/\b(không|không có|bỏ qua|skip|không cần|thôi|không muốn|không dùng|tiếp tục|ok|được)\b/u', $lower)) {
                    return ['intent' => 'checkout_skip_shipping_voucher', 'confidence' => 0.98];
                }
                // Apply shipping voucher với mã cụ thể hoặc số thứ tự
                // ✅ Sửa regex để match cả chữ thường và chữ hoa
                if (preg_match('/\b(mã|code)\s+([A-Z0-9]{3,20})\b/ui', $message) || 
                    preg_match('/\b(số|thứ)\s*(\d+)\b/u', $lower)) {
                    return ['intent' => 'checkout_apply_shipping_voucher', 'confidence' => 0.98];
                }
                // Có muốn áp mã ship không?
                if (preg_match('/\b(có|muốn|áp|dùng|sử dụng)\b/u', $lower)) {
                    return ['intent' => 'checkout_shipping_voucher_response', 'confidence' => 0.95];
                }
                // Mặc định là hỏi về shipping voucher
                return ['intent' => 'checkout_shipping_voucher_response', 'confidence' => 0.9];
            }
            
            // ✅ Select payment method (khi đang ở payment_method_asked hoặc shipping_voucher_applied)
            if ($checkoutState === 'payment_method_asked' || $checkoutState === 'shipping_voucher_applied') {
                // Chọn payment method cụ thể
                if (preg_match('/\b(cod|thanh toán khi nhận|chuyển khoản|vietqr|qr|momo|vnpay|wallet|ví cosme|ví)\b/u', $lower)) {
                    return ['intent' => 'checkout_select_payment', 'confidence' => 0.98];
                }
                // Nói về thanh toán
                if (preg_match('/\b(?:thanh toán|payment|trả tiền)\b/u', $lower)) {
                    return ['intent' => 'checkout_select_payment', 'confidence' => 0.95];
                }
            }
        }
        
        // 0.1. Check context-aware intents (sau checkout flow)
        // Nếu có last_products và hỏi về sản phẩm trong danh sách
        if (!empty($context['last_products'])) {
            $lower = Str::lower(trim($message));
            // Hỏi về công dụng/tác dụng sản phẩm thứ X
            if (preg_match('/sản phẩm\s+(?:thứ\s+)?(?:số\s+)?(?:đầu tiên|nhất|hai|ba|bốn|năm|sáu|bảy|tám|chín|mười|\d+)\b/u', $lower)
                && preg_match('/\b(công dụng|tác dụng|dùng để|dùng để làm gì|dùng để làm|benefits|lợi ích|hiệu quả|effect)\b/u', $lower)) {
                return ['intent' => 'product_usage_inquiry', 'confidence' => 0.95];
            }
            // Hỏi về thông tin sản phẩm thứ X
            if (preg_match('/sản phẩm\s+(?:thứ\s+)?(?:số\s+)?(?:đầu tiên|nhất|hai|ba|bốn|năm|sáu|bảy|tám|chín|mười|\d+)\b/u', $lower)
                && preg_match('/\b(thông tin|info|chi tiết|details|đặc điểm|features|tính năng|phù hợp với|dành cho|cho ai)\b/u', $lower)) {
                return ['intent' => 'product_info', 'confidence' => 0.93];
            }
        }
        
        // 1. Rule-based fast path (cho các intent rõ ràng)
        $ruleBased = $this->classifyByRules($message, $context);
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
    private function classifyByRules(string $message, array $context = []): array
    {
        $lower = Str::lower(trim($message));
        $normalized = Str::lower(trim(Str::ascii($message)));
        if (empty($lower)) {
            return ['intent' => 'unknown', 'confidence' => 0.0];
        }
        
        // Load intents từ database với examples
        $intents = Cache::remember('bot.intents.active', 300, function () {
            return BotIntent::active()
                ->orderedByPriority()
                ->get()
                ->map(function ($intent) {
                    // Pre-process examples để match nhanh hơn
                    $intent->examples_lower = array_map(
                        fn($ex) => Str::lower(trim($ex)),
                        array_filter($intent->examples ?? [], fn($ex) => !empty(trim($ex)))
                    );
                    return $intent;
                });
        });

        // Check database intents với examples (ưu tiên cao hơn)
        $bestMatch = null;
        $bestConfidence = 0.0;
        
        foreach ($intents as $intent) {
            $examples = $intent->examples_lower ?? [];
            if (empty($examples)) {
                continue;
            }
            
            foreach ($examples as $example) {
                if (empty($example)) {
                    continue;
                }
                
                // 1. Exact match (highest confidence)
                if ($lower === $example) {
                    return [
                        'intent' => $intent->name,
                        'confidence' => 0.98,
                    ];
                }
                
                // 2. Contains match (high confidence)
                // Check nếu message chứa example hoặc example chứa message
                if (Str::contains($lower, $example) || Str::contains($example, $lower)) {
                    $confidence = 0.85;
                    // Nếu example dài hơn 10 ký tự và match tốt → tăng confidence
                    if (strlen($example) > 10) {
                        $confidence = 0.90;
                    }
                    // Nếu match gần như exact (chỉ khác dấu câu hoặc khoảng trắng) → tăng confidence
                    $normalizedLower = preg_replace('/[^\p{L}\p{N}]/u', '', $lower);
                    $normalizedExample = preg_replace('/[^\p{L}\p{N}]/u', '', $example);
                    if ($normalizedLower === $normalizedExample) {
                        $confidence = 0.95;
                    }
                    if ($confidence > $bestConfidence) {
                        $bestMatch = $intent->name;
                        $bestConfidence = $confidence;
                    }
                }
                
                // 3. Fuzzy matching với similarity (medium confidence)
                $similarity = $this->calculateSimilarity($lower, $example);
                // Giảm threshold xuống 0.65 để match tốt hơn với các câu dài
                if ($similarity > 0.65 && $similarity > $bestConfidence) {
                    $bestMatch = $intent->name;
                    $bestConfidence = min(0.85, $similarity); // Cap at 0.85 for fuzzy match
                }
            }
        }
        
        // Nếu có match tốt từ examples → return
        if ($bestMatch && $bestConfidence >= 0.75) {
            return [
                'intent' => $bestMatch,
                'confidence' => $bestConfidence,
            ];
        }
        
        // ========== INTENT PHỨC TẠP - XỬ LÝ TRƯỚC ==========
        
        // 0. ✅ Add to cart / Đặt hàng (check TRƯỚC product_search vì specific hơn)
        // Pattern: "Tôi/Mình muốn đặt sản phẩm đầu tiên/thứ hai..." hoặc "mua [tên sản phẩm]"
        // ✅ ƯU TIÊN: Nếu có "sản phẩm đầu tiên/thứ hai..." → chắc chắn là add_to_cart (check TRƯỚC pattern khác)
        if (preg_match('/\b(?:sản phẩm\s+)?(?:đầu tiên|thứ\s+(?:nhất|hai|ba|bốn|năm|sáu|bảy|tám|chín|mười|\d+)|số\s+\d+)\b/u', $lower) &&
            preg_match('/\b(?:tôi|mình|em|anh|chị)\s+muốn\s+(?:đặt|mua)\b/u', $lower)) {
            return ['intent' => 'add_to_cart', 'confidence' => 0.98];
        }

        // Hỗ trợ người dùng nói "chốt/chọn/lấy sản phẩm số X" sau khi có danh sách
        if (!empty($context['last_products'])) {
            $hasSelectionVerb = preg_match('/\b(chốt|chọn|lấy|đặt|gom)\b/u', $lower);
            $hasSelectionVerbAscii = preg_match('/\b(chot|chon|lay|dat|gom)\b/', $normalized);

            if (($hasSelectionVerb && preg_match('/sản phẩm\s+(?:thứ\s+)?(?:số\s+)?(?:đầu tiên|nhất|hai|ba|bốn|năm|sáu|bảy|tám|chín|mười|\d+)\b/u', $lower)) ||
                ($hasSelectionVerbAscii && preg_match('/san pham\s+(?:thu\s+)?(?:so\s+)?(?:dau tien|nhat|hai|ba|bon|nam|sau|bay|tam|chin|muoi|\d+)\b/', $normalized))) {
                return ['intent' => 'add_to_cart', 'confidence' => 0.97];
            }
        }
        
        if (preg_match('/\b(?:(?:tôi|mình|em|anh|chị)\s+muốn\s+(?:đặt|mua)|mua|đặt|cho\s+(?:tôi|mình|em|anh|chị)|thêm\s+vào\s+giỏ|add\s+to\s+cart|chốt|chọn|lấy)\b/u', $lower)) {
            // Nếu có "sản phẩm đầu tiên/thứ hai..." → chắc chắn là add_to_cart
            if (preg_match('/\b(?:sản phẩm\s+)?(?:đầu tiên|thứ\s+(?:nhất|hai|ba|bốn|năm|sáu|bảy|tám|chín|mười|\d+)|số\s+\d+)\b/u', $lower)) {
                return ['intent' => 'add_to_cart', 'confidence' => 0.98];
            }
            // Nếu có tên sản phẩm cụ thể → có thể là add_to_cart
            if (preg_match('/\b(serum|kem|cleanser|toner|essence|mask|sunscreen|chống nắng)\b/u', $lower)) {
                return ['intent' => 'add_to_cart', 'confidence' => 0.95];
            }
            // Nếu có last_products và user đang nói về đặt hàng → chắc chắn là add_to_cart
            if (!empty($context['last_products'])) {
                return ['intent' => 'add_to_cart', 'confidence' => 0.95];
            }
            // Fallback: nếu có pattern đặt/mua → có thể là add_to_cart
            if (preg_match('/\b(?:đặt|mua)\s+([^,\.!?\n]+?)(?:\s+(?:với|cho|giá))?/u', $lower)) {
                return ['intent' => 'add_to_cart', 'confidence' => 0.9];
            }
        }
        
        // 0.3. ✅ Checkout flow intents (nếu đang trong checkout flow) - ƯU TIÊN CAO NHẤT
        $checkoutState = $context['checkout_state'] ?? null;
        if ($checkoutState && $checkoutState !== 'idle' && $checkoutState !== 'order_placed') {
            // ✅ Apply/Skip coupon (khi đang ở coupon_asked hoặc cart_added)
            if ($checkoutState === 'coupon_asked' || $checkoutState === 'cart_added') {
                // Skip coupon
                if (preg_match('/\b(không|không có|bỏ qua|skip|không cần|thôi|không muốn|không dùng)\b/u', $lower)) {
                    return ['intent' => 'checkout_skip_coupon', 'confidence' => 0.98];
                }
                // Apply coupon với mã cụ thể
                if (preg_match('/\b(mã|code)\s+([A-Z0-9]+)\b/ui', $message) || 
                    preg_match('/\b(số|thứ)\s+(\d+)\b/u', $lower)) {
                    return ['intent' => 'checkout_apply_coupon', 'confidence' => 0.98];
                }
                // Có muốn áp mã không?
                if (preg_match('/\b(có|muốn|áp|dùng|sử dụng)\b/u', $lower)) {
                    return ['intent' => 'checkout_coupon_response', 'confidence' => 0.95];
                }
                // Mặc định là hỏi về coupon
                return ['intent' => 'checkout_coupon_response', 'confidence' => 0.9];
            }
            
            // ✅ Select address (khi đang ở address_asked hoặc coupon_applied)
            if ($checkoutState === 'address_asked' || $checkoutState === 'coupon_applied') {
                // Chọn địa chỉ theo số
                if (preg_match('/\b(?:địa chỉ|address)\s*(?:số|thứ)\s*(\d+)\b/u', $lower) || 
                    preg_match('/\b(số|thứ)\s*(\d+)\b/u', $lower)) {
                    return ['intent' => 'checkout_select_address', 'confidence' => 0.98];
                }
                // Nói về địa chỉ
                if (preg_match('/\b(?:địa chỉ|address|giao hàng|ship)\b/u', $lower)) {
                    return ['intent' => 'checkout_select_address', 'confidence' => 0.95];
                }
            }
            
            // ✅ Apply/Skip shipping voucher (khi đang ở shipping_voucher_asked hoặc shipping_calculated)
            if ($checkoutState === 'shipping_voucher_asked' || $checkoutState === 'shipping_calculated') {
                // Skip shipping voucher - bao gồm "Tiếp tục"
                if (preg_match('/\b(không|không có|bỏ qua|skip|không cần|thôi|không muốn|không dùng|tiếp tục|ok|được)\b/u', $lower)) {
                    return ['intent' => 'checkout_skip_shipping_voucher', 'confidence' => 0.98];
                }
                // Apply shipping voucher với mã cụ thể
                // ✅ Sửa regex để match cả chữ thường và chữ hoa
                if (preg_match('/\b(mã|code)\s+([A-Z0-9]{3,20})\b/ui', $message) || 
                    preg_match('/\b(số|thứ)\s+(\d+)\b/u', $lower)) {
                    return ['intent' => 'checkout_apply_shipping_voucher', 'confidence' => 0.98];
                }
                // Có muốn áp mã ship không?
                if (preg_match('/\b(có|muốn|áp|dùng|sử dụng)\b/u', $lower)) {
                    return ['intent' => 'checkout_shipping_voucher_response', 'confidence' => 0.95];
                }
                // Mặc định là hỏi về shipping voucher
                return ['intent' => 'checkout_shipping_voucher_response', 'confidence' => 0.9];
            }
            
            // ✅ Select payment method (khi đang ở payment_method_asked hoặc shipping_voucher_applied)
            if ($checkoutState === 'payment_method_asked' || $checkoutState === 'shipping_voucher_applied') {
                // Chọn payment method cụ thể
                if (preg_match('/\b(cod|thanh toán khi nhận|chuyển khoản|vietqr|qr|momo|vnpay|wallet|ví cosme|ví)\b/u', $lower)) {
                    return ['intent' => 'checkout_select_payment', 'confidence' => 0.98];
                }
                // Nói về thanh toán
                if (preg_match('/\b(?:thanh toán|payment|trả tiền)\b/u', $lower)) {
                    return ['intent' => 'checkout_select_payment', 'confidence' => 0.95];
                }
            }
        }
        
        // 0.5. ✅ Checkout / Thanh toán
        if (preg_match('/\b(?:thanh toán|checkout|đặt hàng|hoàn tất|tiến hành\s+đặt|xác nhận\s+đơn)\b/u', $lower)) {
            return ['intent' => 'checkout_init', 'confidence' => 0.95];
        }
        
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
        
        // 3.5. Hỏi về công dụng/tác dụng sản phẩm trong context (product_usage_inquiry)
        // Check nếu có "sản phẩm thứ X" hoặc "sản phẩm số X" + "công dụng"/"tác dụng"
        if (preg_match('/sản phẩm\s+(?:thứ\s+)?(?:số\s+)?(?:đầu tiên|nhất|hai|ba|bốn|năm|sáu|bảy|tám|chín|mười|\d+)\b/u', $lower)
            && preg_match('/\b(công dụng|tác dụng|dùng để|dùng để làm gì|dùng để làm|benefits|lợi ích|hiệu quả|effect)\b/u', $lower)) {
            return ['intent' => 'product_usage_inquiry', 'confidence' => 0.92];
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
        // Check với nhiều pattern để match tốt hơn
        if (preg_match('/\b(đổi|trả|hoàn|return|exchange|bảo hành|chính sách đổi|chính sách trả)\b/u', $lower) 
            || preg_match('/\b(đổi trả|trả hàng|hoàn tiền|đổi hàng|bảo hành)\b/u', $lower)) {
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
    
    /**
     * Calculate similarity between two strings using Levenshtein distance
     * Returns similarity score (0.0 - 1.0)
     */
    private function calculateSimilarity(string $str1, string $str2): float
    {
        if (empty($str1) || empty($str2)) {
            return 0.0;
        }
        
        // Normalize strings
        $str1 = Str::lower(trim($str1));
        $str2 = Str::lower(trim($str2));
        
        if ($str1 === $str2) {
            return 1.0;
        }
        
        // Use similar_text for similarity percentage
        similar_text($str1, $str2, $percent);
        $similarity = $percent / 100;
        
        // Also check Levenshtein distance for short strings
        if (strlen($str1) < 50 && strlen($str2) < 50) {
            $maxLen = max(strlen($str1), strlen($str2));
            if ($maxLen > 0) {
                $levenshtein = levenshtein($str1, $str2);
                $levSimilarity = 1 - ($levenshtein / $maxLen);
                // Combine both methods (weighted average)
                $similarity = ($similarity * 0.6) + ($levSimilarity * 0.4);
            }
        }
        
        return max(0.0, min(1.0, $similarity));
    }
}
