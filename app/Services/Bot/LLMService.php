<?php

namespace App\Services\Bot;

use App\Services\Bot\RAGService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * LLMService - Integration với Gemini API
 * Support function calling và RAG
 */
class LLMService
{
    private ?string $apiKey = null;
    private string $baseUrl;
    private string $model;

    public function __construct(
        private RAGService $ragService
    ) {
        $this->apiKey = env('GEMINI_API_KEY');
        $this->baseUrl = rtrim(env('GEMINI_API_BASE', 'https://generativelanguage.googleapis.com'), '/');
        $this->model = env('GEMINI_MODEL', 'gemini-1.5-flash');
    }

    public function enabled(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Public fallback response (để BotAgent có thể gọi)
     * Note: Đây là wrapper, method thực tế là private fallbackResponse bên dưới
     */
    public function getFallbackResponse(string $message, string $intent): string
    {
        return $this->fallbackResponse($message, $intent);
    }

    /**
     * Generate response với LLM + RAG
     */
    public function generate(
        string $message,
        string $intent,
        array $context = [],
        array $toolsResult = []
    ): array {
        if (!$this->enabled()) {
            return ['content' => $this->fallbackResponse($message, $intent)];
        }

        try {
            $url = "{$this->baseUrl}/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

            // ✅ RAG: Retrieve relevant information (với error handling)
            $ragResults = [];
            $ragContext = '';
            try {
                Log::info('LLMService: Calling RAGService::retrieve', [
                    'query' => substr($message, 0, 100),
                    'has_context' => !empty($context),
                ]);
                $ragResults = $this->ragService->retrieve($message, $context, 5);
                $ragContext = $this->ragService->buildContextString($ragResults);

                Log::info('LLMService: RAG retrieval completed', [
                    'products_count' => count($ragResults['products'] ?? []),
                    'policies_count' => count($ragResults['policies'] ?? []),
                    'faqs_count' => count($ragResults['faqs'] ?? []),
                    'rag_context_length' => strlen($ragContext),
                ]);
            } catch (\Throwable $e) {
                Log::warning('RAG retrieval failed, continuing without RAG', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                // Continue without RAG if it fails
            }

            // Build system prompt với RAG context
            $systemPrompt = $this->buildSystemPrompt($intent, $context, $toolsResult, $ragContext);

            // Build history
            $history = $this->buildHistory($context['history'] ?? []);

            // Build current message
            $currentMessage = $this->buildCurrentMessage($message, $toolsResult, $ragResults);

            $contents = array_merge(
                [['role' => 'user', 'parts' => [['text' => $systemPrompt]]]],
                $history,
                [['role' => 'user', 'parts' => [['text' => $currentMessage]]]]
            );

            $payload = [
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => 0.5, // Giảm để response nhất quán và logic hơn
                    'topK' => 40,
                    'topP' => 0.8, // Giảm để tập trung hơn
                    'maxOutputTokens' => 600, // Giảm để response ngắn gọn, mạch lạc hơn
                ],
            ];

            $response = Http::timeout(15)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $payload);

            if (!$response->successful()) {
                Log::warning('Gemini API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return ['content' => $this->fallbackResponse($message, $intent)];
            }

            $json = $response->json();
            $parts = $json['candidates'][0]['content']['parts'] ?? [];
            $texts = [];
            foreach ($parts as $p) {
                if (isset($p['text'])) {
                    $texts[] = $p['text'];
                }
            }

            $content = trim(implode("\n\n", $texts));

            return ['content' => $content ?: $this->fallbackResponse($message, $intent)];

        } catch (\Throwable $e) {
            Log::error('LLMService::generate failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ['content' => $this->fallbackResponse($message, $intent)];
        }
    }

    /**
     * Classify intent với LLM
     */
    public function classifyIntent(string $message, array $context = []): ?array
    {
        if (!$this->enabled()) {
            return null;
        }

        // TODO: Implement LLM-based intent classification
        // Có thể dùng function calling hoặc prompt đặc biệt

        return null;
    }

    private function buildSystemPrompt(string $intent, array $context, array $toolsResult, string $ragContext = ''): string
    {
        $prompt = "Bạn là **CosmeBot**, trợ lý tư vấn mỹ phẩm thông minh và thân thiện của Cosme House.\n\n";

        // Intent-specific instructions
        $intentInstructions = [
            'product_search' => "**Khi user tìm sản phẩm:**\n" .
                "1. NHẮC LẠI thông tin user đã cung cấp (loại da, sản phẩm, ngân sách)\n" .
                "2. Nếu có sản phẩm từ tools → giới thiệu ngắn gọn và gợi ý xem chi tiết\n" .
                "3. Nếu không có sản phẩm → hỏi thêm thông tin cần thiết (loại da, ngân sách, vấn đề da)\n" .
                "4. Kết thúc bằng câu hỏi hoặc gợi ý tiếp tục\n",
            'product_recommendation' => "**Khi user cần gợi ý:**\n" .
                "1. Dựa vào thông tin user đã cung cấp (loại da, vấn đề da, ngân sách)\n" .
                "2. Nếu có sản phẩm từ tools → giới thiệu và giải thích tại sao phù hợp\n" .
                "3. Nếu thiếu thông tin → hỏi rõ thêm\n",
            'product_comparison' => "**Khi user so sánh sản phẩm:**\n" .
                "1. NHẮC LẠI các sản phẩm user muốn so sánh\n" .
                "2. So sánh các đặc điểm: giá, thành phần, phù hợp với loại da, lợi ích\n" .
                "3. Đưa ra gợi ý dựa trên nhu cầu của user (nếu có thông tin từ context)\n" .
                "4. Kết thúc bằng câu hỏi xem user muốn biết thêm gì\n",
            'ingredient_inquiry' => "**Khi user hỏi về thành phần:**\n" .
                "1. NHẮC LẠI thành phần user hỏi\n" .
                "2. Giải thích ngắn gọn về thành phần đó (công dụng, phù hợp với loại da nào)\n" .
                "3. Nếu có sản phẩm chứa thành phần đó → giới thiệu ngắn gọn\n" .
                "4. Kết thúc bằng câu hỏi xem user muốn tìm sản phẩm chứa thành phần đó không\n",
            'usage_inquiry' => "**Khi user hỏi về cách sử dụng:**\n" .
                "1. NHẮC LẠI sản phẩm user hỏi\n" .
                "2. Hướng dẫn cách sử dụng chi tiết (thời điểm, tần suất, lượng dùng, bước trong routine)\n" .
                "3. Lưu ý quan trọng (VD: dùng chống nắng sau retinol, patch test trước khi dùng)\n" .
                "4. Kết thúc bằng câu hỏi xem user còn thắc mắc gì không\n",
            'skin_concern_consultation' => "**Khi user tư vấn theo vấn đề da:**\n" .
                "1. NHẮC LẠI vấn đề da user đã nêu\n" .
                "2. Giải thích ngắn gọn về vấn đề đó và cách khắc phục\n" .
                "3. Nếu có sản phẩm phù hợp → giới thiệu và giải thích tại sao phù hợp\n" .
                "4. Đưa ra lời khuyên về routine skincare phù hợp\n" .
                "5. Kết thúc bằng câu hỏi xem user muốn tìm sản phẩm cụ thể không\n",
            'price_inquiry' => "**Khi user hỏi về giá:**\n" .
                "1. NHẮC LẠI sản phẩm user hỏi\n" .
                "2. Trả lời giá cụ thể (nếu có từ tools)\n" .
                "3. Nếu có khuyến mãi → thông báo\n" .
                "4. Kết thúc bằng câu hỏi xem user muốn mua không\n",
            'review_inquiry' => "**Khi user hỏi về review:**\n" .
                "1. NHẮC LẠI sản phẩm user hỏi\n" .
                "2. Tóm tắt đánh giá chung (nếu có từ tools hoặc RAG)\n" .
                "3. Điểm mạnh và điểm yếu của sản phẩm\n" .
                "4. Phù hợp với loại da nào\n" .
                "5. Kết thúc bằng câu hỏi xem user muốn biết thêm gì\n",
            'product_info' => "**Khi user hỏi về thông tin sản phẩm:**\n" .
                "1. NHẮC LẠI sản phẩm user hỏi\n" .
                "2. Cung cấp thông tin chi tiết: đặc điểm, lợi ích, phù hợp với loại da, thành phần chính\n" .
                "3. Nếu có từ tools → sử dụng thông tin đó\n" .
                "4. Kết thúc bằng câu hỏi xem user muốn biết thêm gì\n",
            'routine_suggestion' => "**Khi user hỏi về routine:**\n" .
                "1. NHẮC LẠI loại da hoặc vấn đề da user đã nêu (nếu có)\n" .
                "2. Đưa ra quy trình skincare phù hợp (theo thứ tự: cleanser → toner → serum → moisturizer → sunscreen)\n" .
                "3. Nếu có sản phẩm cụ thể → gợi ý sản phẩm cho từng bước\n" .
                "4. Lưu ý về thời điểm sử dụng (sáng/tối)\n" .
                "5. Kết thúc bằng câu hỏi xem user muốn tìm sản phẩm cho bước nào\n",
            'order_tracking' => "**Khi user tra cứu đơn hàng:**\n" .
                "1. Yêu cầu mã đơn hàng hoặc số điện thoại\n" .
                "2. Hướng dẫn cách tra cứu\n",
            'shipping_policy' => "**Khi user hỏi về ship:**\n" .
                "1. Trả lời rõ ràng về phí ship\n" .
                "2. Thời gian giao hàng\n" .
                "3. Các phương thức giao hàng\n",
            'return_policy' => "**Khi user hỏi về đổi trả:**\n" .
                "1. Trả lời rõ ràng về chính sách đổi trả\n" .
                "2. Điều kiện đổi trả\n" .
                "3. Cách thức đổi trả\n",
            'payment_policy' => "**Khi user hỏi về thanh toán:**\n" .
                "1. Liệt kê các phương thức thanh toán\n" .
                "2. Hướng dẫn cách thanh toán\n" .
                "3. Lưu ý về bảo mật\n",
            'greeting' => "**Khi user chào hỏi:**\n" .
                "1. Chào lại thân thiện\n" .
                "2. Giới thiệu ngắn gọn về khả năng của bot\n" .
                "3. Hỏi user cần hỗ trợ gì\n",
            'add_to_cart' => "**Khi user muốn đặt hàng:**\n" .
                "1. Nếu có addToCart từ tools result và success = true → SỬ DỤNG message từ tool result (KHÔNG tự generate)\n" .
                "2. Nếu có getUserCoupons từ tools result → list ra các mã giảm giá và hỏi user có muốn áp không\n" .
                "3. Nếu không có mã giảm giá → hỏi 'Bạn có muốn áp mã giảm giá không? (Bạn chưa có mã giảm giá nào. Bạn có thể bỏ qua bước này.)'\n" .
                "4. Nếu addToCart success = false → thông báo lỗi từ message của tool\n" .
                "5. Nếu cần đăng nhập → hướng dẫn đăng nhập\n" .
                "6. Nếu không tìm thấy sản phẩm → hỏi lại hoặc gợi ý sản phẩm khác\n" .
                "**QUAN TRỌNG:** Nếu có addToCart['message'] từ tools result → ƯU TIÊN dùng message đó, chỉ thêm câu hỏi về mã giảm giá nếu có.\n",
            'checkout_init' => "**Khi user muốn thanh toán:**\n" .
                "1. NHẮC LẠI yêu cầu thanh toán\n" .
                "2. Kiểm tra giỏ hàng (nếu có từ tools result)\n" .
                "3. Hướng dẫn user:\n" .
                "   - Nếu chưa đăng nhập: 'Bạn cần đăng nhập để thanh toán. Vui lòng đăng nhập tại [link]'\n" .
                "   - Nếu đã đăng nhập: 'Bạn có thể thanh toán tại [link checkout] hoặc mình sẽ hướng dẫn bạn điền thông tin'\n" .
                "4. Nếu user muốn điền thông tin qua chat → hướng dẫn từng bước (tên, SĐT, địa chỉ, phương thức thanh toán)\n" .
                "5. Kết thúc bằng link checkout hoặc hướng dẫn tiếp theo\n",
            'checkout_coupon_response' => "**BẠN ĐANG TRONG QUY TRÌNH ĐẶT HÀNG - Bước 2:**\n" .
                "User đang trả lời về mã giảm giá. Nếu có coupons từ tools result → list ra và hướng dẫn user chọn (số 1, số 2...).\n" .
                "Nếu user nói 'không' hoặc 'bỏ qua' → chuyển sang bước tiếp theo (hỏi địa chỉ).\n" .
                "Nếu user chọn mã → xác nhận đã áp mã và chuyển sang bước tiếp theo.\n",
            'checkout_skip_coupon' => "**BẠN ĐANG TRONG QUY TRÌNH ĐẶT HÀNG - Bước 2:**\n" .
                "User đã chọn bỏ qua mã giảm giá. Bạn CẦN chuyển sang bước tiếp theo: 'Bạn muốn giao hàng đến địa chỉ nào?'\n" .
                "Nếu có addresses từ tools result → list ra. Nếu không có → hướng dẫn user nhập địa chỉ mới.\n",
            'checkout_apply_coupon' => "**BẠN ĐANG TRONG QUY TRÌNH ĐẶT HÀNG - Bước 2:**\n" .
                "User đã chọn mã giảm giá. Nếu applyCoupon từ tools result có success = true → xác nhận đã áp mã và số tiền giảm.\n" .
                "Sau đó chuyển sang bước tiếp theo: 'Bạn muốn giao hàng đến địa chỉ nào?'\n",
            'checkout_select_address' => "**BẠN ĐANG TRONG QUY TRÌNH ĐẶT HÀNG - Bước 3:**\n" .
                "User đã chọn địa chỉ. Nếu calculateShipping từ tools result có success = true → xác nhận địa chỉ và phí ship.\n" .
                "Sau đó chuyển sang bước tiếp theo: 'Bạn có muốn áp mã vận chuyển không?'\n",
            'checkout_skip_shipping_voucher' => "**BẠN ĐANG TRONG QUY TRÌNH ĐẶT HÀNG - Bước 4:**\n" .
                "User đã chọn bỏ qua mã vận chuyển. Bạn CẦN:\n" .
                "1. Tóm tắt đơn hàng: Tổng sản phẩm, Giảm giá, Phí ship, Tổng cộng\n" .
                "2. Hỏi: 'Bạn muốn thanh toán bằng phương thức nào?'\n" .
                "3. List payment methods từ tools result.\n",
            'checkout_apply_shipping_voucher' => "**BẠN ĐANG TRONG QUY TRÌNH ĐẶT HÀNG - Bước 4:**\n" .
                "User đã chọn mã vận chuyển. Nếu applyShippingVoucher từ tools result có success = true → xác nhận đã áp mã và số tiền giảm.\n" .
                "Sau đó:\n" .
                "1. Tóm tắt đơn hàng: Tổng sản phẩm, Giảm giá, Phí ship, Giảm phí ship, Tổng cộng\n" .
                "2. Hỏi: 'Bạn muốn thanh toán bằng phương thức nào?'\n" .
                "3. List payment methods từ tools result.\n",
            'checkout_shipping_voucher_response' => "**BẠN ĐANG TRONG QUY TRÌNH ĐẶT HÀNG - Bước 4:**\n" .
                "User đang trả lời về mã vận chuyển. Nếu có vouchers từ tools result → list ra và hướng dẫn user chọn.\n" .
                "Nếu user nói 'không' hoặc 'bỏ qua' → chuyển sang bước tiếp theo (hỏi phương thức thanh toán).\n",
            'checkout_select_payment' => "**BẠN ĐANG TRONG QUY TRÌNH ĐẶT HÀNG - Bước 5:**\n" .
                "User đã chọn phương thức thanh toán. Bạn CẦN:\n" .
                "1. Xác nhận phương thức thanh toán user đã chọn\n" .
                "2. Tóm tắt lại toàn bộ đơn hàng: Sản phẩm, Giảm giá, Phí ship, Tổng cộng\n" .
                "3. Nếu có placeOrder từ tools result và success = true → hiển thị thông báo đặt hàng thành công với mã đơn hàng\n" .
                "4. Nếu chưa có placeOrder → tự động đặt hàng (tools sẽ tự động chạy)\n",
            'checkout_shipping_voucher_asked' => "**Khi đã hỏi về mã vận chuyển:**\n" .
                "1. NHẮC LẠI: 'Bạn có muốn áp mã vận chuyển không?'\n" .
                "2. Nếu có shipping vouchers từ tools → list ra cho user chọn (số 1, số 2...)\n" .
                "3. Nếu không có vouchers → nói 'Bạn chưa có mã vận chuyển nào. Bạn có thể bỏ qua bước này.'\n" .
                "4. Hướng dẫn: 'Bạn muốn áp mã nào? (Nói \"mã X\" hoặc \"số 1\", \"số 2\"...) Hoặc nói \"không\" nếu không muốn áp mã.'\n",
            'checkout_payment_method_asked' => "**Khi đã hỏi về phương thức thanh toán:**\n" .
                "1. NHẮC LẠI tổng tiền đơn hàng (sau khi giảm giá và phí ship)\n" .
                "2. Nếu có payment methods từ tools → list ra cho user chọn\n" .
                "3. Hướng dẫn: 'Bạn muốn thanh toán bằng cách nào? (Nói \"COD\", \"VietQR\", \"số 1\"...)'\n",
        ];

        // ✅ Checkout flow instructions
        $checkoutState = $context['checkout_state'] ?? null;
        if ($checkoutState) {
            $checkoutInstructions = [
                'cart_added' => "**BẠN ĐANG TRONG QUY TRÌNH ĐẶT HÀNG - Bước 1:**\n" .
                    "User vừa thêm sản phẩm vào giỏ hàng. Bạn CẦN hỏi: 'Bạn có muốn áp mã giảm giá không?'\n" .
                    "Nếu có coupons từ tools result → list ra. Nếu không có → nói 'Bạn chưa có mã giảm giá nào. Bạn có thể bỏ qua bước này.'\n",
                'coupon_asked' => "**BẠN ĐANG TRONG QUY TRÌNH ĐẶT HÀNG - Bước 2:**\n" .
                    "Bạn đã hỏi về mã giảm giá. Nếu có coupons từ tools result → list ra và hướng dẫn user chọn.\n" .
                    "Nếu user nói 'không' hoặc 'bỏ qua' → chuyển sang bước tiếp theo (hỏi địa chỉ).\n",
                'coupon_applied' => "**BẠN ĐANG TRONG QUY TRÌNH ĐẶT HÀNG - Bước 3:**\n" .
                    "Đã xử lý mã giảm giá (áp dụng hoặc bỏ qua). Bạn CẦN hỏi: 'Bạn muốn giao hàng đến địa chỉ nào?'\n" .
                    "Nếu có addresses từ tools result → list ra. Nếu không có → hướng dẫn user nhập địa chỉ mới.\n",
                'address_asked' => "**BẠN ĐANG TRONG QUY TRÌNH ĐẶT HÀNG - Bước 4:**\n" .
                    "Bạn đã hỏi về địa chỉ. Nếu có addresses từ tools result → list ra và hướng dẫn user chọn.\n" .
                    "Sau khi user chọn địa chỉ → tính phí ship và xác nhận.\n",
                'address_confirmed' => "**BẠN ĐANG TRONG QUY TRÌNH ĐẶT HÀNG - Bước 5:**\n" .
                    "Đã xác nhận địa chỉ và tính phí ship. Bạn CẦN hỏi: 'Bạn có muốn áp mã vận chuyển không?'\n" .
                    "Nếu có shipping vouchers từ tools result → list ra. Nếu không có → nói 'Bạn chưa có mã vận chuyển nào. Bạn có thể bỏ qua bước này.'\n",
                'shipping_voucher_asked' => "**BẠN ĐANG TRONG QUY TRÌNH ĐẶT HÀNG - Bước 6:**\n" .
                    "Bạn đã hỏi về mã vận chuyển. Nếu có vouchers từ tools result → list ra và hướng dẫn user chọn.\n" .
                    "Nếu user nói 'không' hoặc 'bỏ qua' → chuyển sang bước tiếp theo (hỏi phương thức thanh toán).\n",
                'shipping_voucher_applied' => "**BẠN ĐANG TRONG QUY TRÌNH ĐẶT HÀNG - Bước 7:**\n" .
                    "Đã xử lý mã vận chuyển (áp dụng hoặc bỏ qua). Bạn CẦN:\n" .
                    "1. Tóm tắt đơn hàng: Tổng sản phẩm, Giảm giá, Phí ship, Tổng cộng\n" .
                    "2. Hỏi: 'Bạn muốn thanh toán bằng phương thức nào?'\n" .
                    "3. List payment methods từ tools result.\n",
                'payment_method_asked' => "**BẠN ĐANG TRONG QUY TRÌNH ĐẶT HÀNG - Bước 8:**\n" .
                    "Bạn đã hỏi về phương thức thanh toán. Nếu có payment methods từ tools result → list ra và hướng dẫn user chọn.\n" .
                    "Sau khi user chọn → xác nhận và đặt hàng.\n",
                'payment_method_selected' => "**BẠN ĐANG TRONG QUY TRÌNH ĐẶT HÀNG - Bước 9:**\n" .
                    "User đã chọn phương thức thanh toán. Bạn CẦN xác nhận và đặt hàng.\n" .
                    "Nếu có placeOrder result → hiển thị thông báo đặt hàng thành công với mã đơn hàng.\n",
            ];

            if (isset($checkoutInstructions[$checkoutState])) {
                $prompt .= $checkoutInstructions[$checkoutState] . "\n";
            }
        }

        $prompt .= "**QUY TẮC TRẢ LỜI (QUAN TRỌNG):**\n";
        $prompt .= "- Trả lời TỰ NHIÊN, LIỀN MẠCH như đang chat với bạn thân, không cứng nhắc\n";
        $prompt .= "- Trả lời NGẮN GỌN (2-5 câu), lịch sự, thân thiện, dễ hiểu\n";
        $prompt .= "- Sử dụng markdown gọn nhẹ (**bold**, list) để làm nổi bật thông tin quan trọng\n";
        $prompt .= "- **LUÔN NHẮC LẠI** thông tin user đã cung cấp trong câu đầu tiên để tạo cảm giác được lắng nghe\n";
        $prompt .= "- **NHỚ CONTEXT**: Sử dụng thông tin từ các tin nhắn trước đó (loại da, vấn đề da, ngân sách) để trả lời chính xác\n";
        $prompt .= "- Ưu tiên thông tin từ RAG context và tools result\n";
        $prompt .= "- Trả lời có CẤU TRÚC RÕ RÀNG: (1) Xác nhận yêu cầu + nhắc lại thông tin, (2) Thông tin/giải pháp, (3) Câu hỏi tiếp theo\n";
        $prompt .= "- Nếu có sản phẩm từ tools → giới thiệu CỤ THỂ (tên, giá, đặc điểm nổi bật) và gợi ý xem chi tiết\n";
        $prompt .= "- Nếu thiếu thông tin → hỏi rõ thêm (loại da, ngân sách, vấn đề da...) một cách tự nhiên\n";
        $prompt .= "- **TẠO HỘI THOẠI LIỀN MẠCH**: Kết thúc bằng câu hỏi hoặc gợi ý để tiếp tục hội thoại, không để cuộc trò chuyện bị ngắt quãng\n";
        $prompt .= "- **SỬ DỤNG EMOJI MỘT CÁCH HỢP LÝ**: Dùng emoji để tạo cảm giác thân thiện (VD: ✨, 😊, 💡) nhưng không quá nhiều\n";
        $prompt .= "- **TRÁNH LẶP LẠI**: Nếu đã trả lời câu hỏi tương tự trước đó, tham khảo lại và trả lời ngắn gọn hơn\n";
        $prompt .= "- **CHECKOUT FLOW**: Nếu đang trong quy trình đặt hàng, LUÔN hỏi bước tiếp theo sau mỗi bước hoàn thành\n\n";

        // Add intent-specific instructions
        if (isset($intentInstructions[$intent])) {
            $prompt .= $intentInstructions[$intent] . "\n";
        }

        // User context
        if (!empty($context['entities'])) {
            $entities = $context['entities'];
            $prompt .= "**Thông tin người dùng:**\n";
            if (!empty($entities['skin_types'])) {
                $skinMap = ['oily' => 'da dầu', 'dry' => 'da khô', 'combination' => 'da hỗn hợp', 'sensitive' => 'da nhạy cảm', 'normal' => 'da thường'];
                $skinLabels = array_map(fn($s) => $skinMap[$s] ?? $s, $entities['skin_types']);
                $prompt .= "- Loại da: " . implode(', ', $skinLabels) . "\n";
            }
            if (!empty($entities['concerns'])) {
                $concernMap = ['acne' => 'mụn', 'blackheads' => 'đầu đen', 'dark_spots' => 'thâm', 'pores' => 'lỗ chân lông', 'aging' => 'lão hóa', 'hydration' => 'dưỡng ẩm'];
                $concernLabels = array_map(fn($c) => $concernMap[$c] ?? $c, $entities['concerns']);
                $prompt .= "- Vấn đề da: " . implode(', ', $concernLabels) . "\n";
            }
            if (!empty($entities['budget']['min'])) {
                $prompt .= "- Ngân sách: " . number_format($entities['budget']['min']) . " - " . number_format($entities['budget']['max'] ?? $entities['budget']['min']) . "₫\n";
            }
            $prompt .= "\n";
        }

        // RAG Context (thông tin từ knowledge base)
        if (!empty($ragContext)) {
            $prompt .= "**THÔNG TIN TỪ HỆ THỐNG (RAG):**\n";
            $prompt .= $ragContext . "\n";
            $prompt .= "**Lưu ý:** Sử dụng thông tin trên để trả lời chính xác. Nếu có sản phẩm, giới thiệu ngắn gọn và gợi ý xem chi tiết.\n\n";
        }

        // Tools result
        if (!empty($toolsResult)) {
            $prompt .= "**KẾT QUẢ TỪ TOOLS:**\n";
            foreach ($toolsResult as $toolName => $result) {
                if (is_array($result) && isset($result[0]['name'])) {
                    // Products array - show details
                    $productCount = count($result);
                    $prompt .= "- {$toolName}: Tìm thấy {$productCount} sản phẩm phù hợp\n";

                    // Show first 3-5 products with details
                    $productsToShow = array_slice($result, 0, min(5, $productCount));
                    foreach ($productsToShow as $idx => $p) {
                        $name = $p['name'] ?? 'N/A';
                        $price = isset($p['price_min']) ? number_format($p['price_min']) . '₫' : 'N/A';
                        $prompt .= "  " . ($idx + 1) . ". {$name} - {$price}\n";
                    }
                    if ($productCount > 5) {
                        $prompt .= "  ... và " . ($productCount - 5) . " sản phẩm khác\n";
                    }
                } elseif ($toolName === 'addToCart' && is_array($result)) {
                    // ✅ Format đặc biệt cho addToCart result
                    $success = $result['success'] ?? false;
                    $message = $result['message'] ?? '';
                    $productName = $result['product_name'] ?? '';
                    $cartCount = $result['cart_count'] ?? 0;
                    $prompt .= "- {$toolName}: success={$success}, message=\"{$message}\", product_name=\"{$productName}\", cart_count={$cartCount}\n";
                    $prompt .= "  **QUAN TRỌNG:** Nếu success=true, BẮT BUỘC sử dụng message từ tool result này, KHÔNG tự generate!\n";
                } else {
                    $prompt .= "- {$toolName}: " . json_encode($result, JSON_UNESCAPED_UNICODE) . "\n";
                }
            }
            $prompt .= "\n";
            $prompt .= "**QUAN TRỌNG:**\n";
            $prompt .= "- Nếu có sản phẩm từ tools, HÃY GIỚI THIỆU CỤ THỂ các sản phẩm này (tên, giá, đặc điểm nổi bật)\n";
            $prompt .= "- Đừng chỉ nói 'tìm thấy X sản phẩm' mà hãy giới thiệu tự nhiên như: 'Mình tìm thấy một số sản phẩm phù hợp như [tên sản phẩm] với giá [giá], bạn có thể xem chi tiết bên dưới'\n";
            $prompt .= "- Nếu không có sản phẩm nào, giải thích tại sao và hỏi thêm thông tin\n\n";
        }

        $prompt .= "**Intent hiện tại:** {$intent}\n";
        $prompt .= "\n**YÊU CẦU TRẢ LỜI (BẮT BUỘC):**\n";
        $prompt .= "1. **BẮT BUỘC** XÁC NHẬN yêu cầu của user bằng cách NHẮC LẠI thông tin họ đã cung cấp trong câu đầu tiên\n";
        $prompt .= "2. CUNG CẤP thông tin/giải pháp (nếu có sản phẩm thì giới thiệu CỤ THỂ, nếu không thì hỏi thêm)\n";
        $prompt .= "3. KẾT THÚC bằng câu hỏi hoặc gợi ý tiếp tục\n";
        $prompt .= "\n**VÍ DỤ CỤ THỂ:**\n";
        $prompt .= "- User: 'serum cho da dầu'\n";
        $prompt .= "- Bot: 'Mình sẽ tìm **serum phù hợp cho da dầu** cho bạn! [Nếu có sản phẩm: Mình tìm thấy một số sản phẩm như [tên] với giá [giá], bạn có thể xem chi tiết bên dưới. / Nếu không có: Bạn có thể cho mình biết thêm về vấn đề da hoặc ngân sách không?]'\n";
        $prompt .= "\n**LƯU Ý:** Đừng trả lời chung chung như 'Mình sẽ tìm sản phẩm phù hợp cho bạn' mà hãy cụ thể như 'Mình sẽ tìm **serum cho da dầu** cho bạn!'\n";

        return $prompt;
    }

    private function buildHistory(array $history): array
    {
        $result = [];
        foreach ($history as $turn) {
            $role = $turn['role'] === 'user' ? 'user' : 'model';
            $result[] = [
                'role' => $role,
                'parts' => [['text' => $turn['content'] ?? '']],
            ];
        }
        return $result;
    }

    private function buildCurrentMessage(string $message, array $toolsResult, array $ragResults = []): string
    {
        $text = "**CÂU HỎI CỦA KHÁCH HÀNG:** {$message}\n\n";

        // RAG results đã được inject vào system prompt, không cần lặp lại ở đây
        // Chỉ thêm tools result nếu có
        if (!empty($toolsResult)) {
            $text .= "**KẾT QUẢ TOOLS:**\n";
            foreach ($toolsResult as $toolName => $result) {
                if (is_array($result) && isset($result[0]['name'])) {
                    $text .= "- {$toolName}: Tìm thấy " . count($result) . " sản phẩm phù hợp\n";
                    // Show product names for context
                    $productNames = array_slice(array_map(fn($p) => $p['name'] ?? 'N/A', $result), 0, 3);
                    $text .= "  Sản phẩm: " . implode(', ', $productNames) . (count($result) > 3 ? '...' : '') . "\n";
                } else {
                    $text .= "- {$toolName}: " . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
                }
            }
            $text .= "\n";
            $text .= "**QUAN TRỌNG:** Nếu có sản phẩm từ tools, HÃY GIỚI THIỆU CỤ THỂ các sản phẩm này, đừng chỉ nói 'tìm thấy X sản phẩm'. Hãy nói về sản phẩm một cách tự nhiên.\n\n";
        }

        $text .= "**YÊU CẦU TRẢ LỜI (BẮT BUỘC):**\n";
        $text .= "1. **BẮT BUỘC NHẮC LẠI** thông tin từ câu hỏi của khách hàng trong câu đầu tiên (ví dụ: 'serum cho da dầu' → phải nói 'Mình sẽ tìm **serum cho da dầu** cho bạn!')\n";
        $text .= "2. Trả lời có **LOGIC và MẠCH LẠC** theo cấu trúc: (1) Xác nhận yêu cầu, (2) Thông tin/giải pháp, (3) Câu hỏi tiếp theo\n";
        $text .= "3. Nếu có sản phẩm → giới thiệu CỤ THỂ (tên, giá), nếu không có → hỏi thêm thông tin\n";
        $text .= "4. Đừng trả lời chung chung, hãy cụ thể và hữu ích\n";
        $text .= "5. **KHÔNG BAO GIỜ** bỏ qua việc nhắc lại thông tin user đã cung cấp\n";

        return $text;
    }

    private function fallbackResponse(string $message, string $intent): string
    {
        $lower = mb_strtolower($message);

        // Greeting
        if (preg_match('/\b(xin chào|chào|hello|hi|hey|alo)\b/u', $lower)) {
            return "Chào bạn 👋 Mình là CosmeBot! Bạn muốn tư vấn theo **loại da**/**ngân sách** hay tìm một sản phẩm cụ thể?";
        }

        // Product search - sữa rửa mặt, serum, kem, etc
        if (preg_match('/\b(sữa rửa mặt|rửa mặt|cleanser|foam|gel|serum|kem|cream|chống nắng|sunscreen|spf)\b/u', $lower)) {
            // Try to extract skin type
            $skinType = '';
            if (preg_match('/\b(da dầu|dầu|oily)\b/u', $lower)) {
                $skinType = 'da dầu';
            } elseif (preg_match('/\b(da khô|khô|dry)\b/u', $lower)) {
                $skinType = 'da khô';
            } elseif (preg_match('/\b(hỗn hợp|combination)\b/u', $lower)) {
                $skinType = 'da hỗn hợp';
            } elseif (preg_match('/\b(nhạy cảm|sensitive)\b/u', $lower)) {
                $skinType = 'da nhạy cảm';
            }

            $skinText = $skinType ? " cho {$skinType}" : '';
            return "Mình sẽ tìm sản phẩm phù hợp{$skinText} cho bạn! Bạn có thể cho mình biết thêm:\n- **Vấn đề da** (mụn, thâm, lỗ chân lông...)\n- **Ngân sách** (VD: 300-500k)\n\nHoặc mình có thể gợi ý ngay dựa trên thông tin hiện có!";
        }

        // Budget search
        if (preg_match('/\b(\d+[kK]|\d+\s*000|\d+\s*tr|ngân sách|budget|giá|price)\b/u', $lower)) {
            return "Mình hiểu bạn đang tìm sản phẩm theo ngân sách! Bạn có thể cho mình biết:\n- **Khoảng giá** (VD: 300-500k, dưới 1 triệu)\n- **Loại sản phẩm** (serum, kem, chống nắng...)\n- **Loại da** (dầu, khô, hỗn hợp, nhạy cảm)";
        }

        // Order tracking
        if (preg_match('/\b(đơn hàng|order|tra cứu|mã đơn|đơn|tracking)\b/u', $lower)) {
            return "Để tra cứu đơn hàng, bạn vui lòng cung cấp **mã đơn hàng** (VD: #DH123456) hoặc **số điện thoại** đặt hàng nhé!";
        }

        // Shipping
        if (preg_match('/\b(phí ship|ship|vận chuyển|giao hàng|shipping|phí vận chuyển)\b/u', $lower)) {
            return "**Phí vận chuyển:**\n- Miễn phí ship cho đơn từ 500.000₫\n- Phí ship 30.000₫ cho đơn dưới 500.000₫\n- Giao hàng toàn quốc trong 2-5 ngày làm việc\n- Hỗ trợ giao hàng nhanh (1-2 ngày) với phí bổ sung";
        }

        // Return/Exchange - check trước default
        if (preg_match('/\b(đổi|trả|hoàn|return|exchange|bảo hành|chính sách đổi|chính sách trả)\b/u', $lower)) {
            return "**Chính sách đổi trả:**\n- Đổi/trả trong 7 ngày kể từ ngày nhận hàng\n- Sản phẩm còn nguyên seal, chưa sử dụng\n- Miễn phí đổi trả nếu lỗi từ phía shop\n- Liên hệ hotline để được hỗ trợ nhanh nhất!";
        }

        // Payment
        if (preg_match('/\b(thanh toán|payment|pay|tiền|cod|chuyển khoản)\b/u', $lower)) {
            return "**Phương thức thanh toán:**\n- COD (Thanh toán khi nhận hàng)\n- Chuyển khoản qua ngân hàng\n- Ví điện tử (MoMo, ZaloPay)\n- Thẻ tín dụng/ghi nợ";
        }

        // Default - try to be helpful
        return "Mình hiểu bạn đang tìm kiếm thông tin! Bạn có thể:\n- **Tư vấn sản phẩm** theo loại da, ngân sách\n- **Tra cứu đơn hàng** bằng mã đơn\n- **Hỏi về chính sách** (ship, đổi trả, thanh toán)\n\nBạn muốn hỏi gì cụ thể nhỉ? 😊";
    }
}

