<?php

namespace App\Services\Bot;

use App\Models\BotConversation;
use App\Models\BotMessage;
use App\Services\Bot\IntentClassifier;
use App\Services\Bot\ContextManager;
use App\Services\Bot\ToolExecutor;
use App\Services\Bot\LLMService;
use App\Services\Bot\ResponseGenerator;
use App\Services\Bot\AnalyticsService;
use Illuminate\Support\Facades\Log;

/**
 * BotAgent - Orchestrator chính của chatbot
 * Quản lý toàn bộ flow: Intent → Tools → LLM → Response
 */
class BotAgent
{
    public function __construct(
        private IntentClassifier $intentClassifier,
        private ContextManager $contextManager,
        private ToolExecutor $toolExecutor,
        private LLMService $llmService,
        private ResponseGenerator $responseGenerator,
        private AnalyticsService $analytics
    ) {}

    /**
     * Xử lý tin nhắn từ user
     * 
     * @param string $message
     * @param string|null $sessionId
     * @param int|null $userId
     * @return array {reply, products, suggestions, intent, tools_used}
     */
    public function process(string $message, ?string $sessionId = null, ?int $userId = null): array
    {
        $startTime = microtime(true);
        
        try {
            // 1. Lấy hoặc tạo conversation
            $conversation = $this->getOrCreateConversation($sessionId, $userId);
            
            // 2. Load context TRƯỚC (để có entities từ messages cũ)
            try {
                $context = $this->contextManager->load($conversation);
            } catch (\Throwable $e) {
                Log::warning('ContextManager::load failed', ['error' => $e->getMessage()]);
                $context = ['entities' => [], 'history' => []];
            }
            
            // 3. Extract entities từ message hiện tại và merge vào context
            try {
                $currentEntities = $this->contextManager->extractEntitiesFromMessage($message);
                // Merge với entities cũ (ưu tiên entities mới cho product_type, budget)
                $oldEntities = $context['entities'] ?? [];
                $context['entities'] = [
                    'skin_types' => array_values(array_unique(array_merge($oldEntities['skin_types'] ?? [], $currentEntities['skin_types'] ?? []))),
                    'concerns' => array_values(array_unique(array_merge($oldEntities['concerns'] ?? [], $currentEntities['concerns'] ?? []))),
                    'ingredients' => array_values(array_unique(array_merge($oldEntities['ingredients'] ?? [], $currentEntities['ingredients'] ?? []))),
                    'product_type' => $currentEntities['product_type'] ?? $oldEntities['product_type'] ?? null,
                    'budget' => $currentEntities['budget']['min'] ? $currentEntities['budget'] : ($oldEntities['budget'] ?? ['min' => null, 'max' => null]),
                    'name' => $currentEntities['name'] ?? $oldEntities['name'] ?? null,
                    'last_product' => $oldEntities['last_product'] ?? null,
                ];
            } catch (\Throwable $e) {
                Log::warning('Entity extraction failed', ['error' => $e->getMessage()]);
            }
            
            // 4. Lưu tin nhắn user (SAU khi extract entities)
            $userMessage = BotMessage::create([
                'conversation_id' => $conversation->id,
                'role' => 'user',
                'content' => $message,
            ]);
            
            // 4. Phân loại intent (với error handling)
            try {
                $intentResult = $this->intentClassifier->classify($message, $context);
                $intent = $intentResult['intent'] ?? 'unknown';
                $confidence = $intentResult['confidence'] ?? 0.0;
            } catch (\Throwable $e) {
                Log::warning('IntentClassifier::classify failed', ['error' => $e->getMessage()]);
                $intent = 'unknown';
                $confidence = 0.0;
            }
            
            // 5. Update context với intent (không block nếu fail)
            try {
                $this->contextManager->updateIntent($conversation, $intent, $confidence);
            } catch (\Throwable $e) {
                Log::warning('ContextManager::updateIntent failed', ['error' => $e->getMessage()]);
            }
            
            // 6. Execute tools nếu cần (với error handling)
            $toolsResult = [];
            if ($intent !== 'unknown' && $intent !== 'greeting') {
                try {
                    $toolsResult = $this->toolExecutor->execute($intent, $message, $context);
                    // Debug: Log toolsResult ngay sau khi execute
                    if (!empty($toolsResult)) {
                        Log::info('BotAgent: toolsResult after execute', [
                            'tools_count' => count($toolsResult),
                            'tools_keys' => array_keys($toolsResult),
                            'first_tool' => array_key_first($toolsResult),
                            'first_tool_result_count' => is_array($toolsResult[array_key_first($toolsResult)] ?? null) 
                                ? count($toolsResult[array_key_first($toolsResult)]) 
                                : 0,
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::warning('ToolExecutor::execute failed', ['error' => $e->getMessage()]);
                    $toolsResult = [];
                }
            }
            
            // 7. Generate response với LLM + RAG (với error handling)
            try {
                $llmResponse = $this->llmService->generate(
                    message: $message,
                    intent: $intent,
                    context: $context,
                    toolsResult: $toolsResult
                );
            } catch (\Throwable $e) {
                Log::error('LLMService::generate failed', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
                // Fallback response
                $llmResponse = ['content' => $this->llmService->getFallbackResponse($message, $intent)];
            }
            
            // 8. Format response (với error handling)
            try {
                $response = $this->responseGenerator->generate(
                    content: $llmResponse['content'] ?? '',
                    intent: $intent,
                    toolsResult: $toolsResult,
                    context: $context
                );
            } catch (\Throwable $e) {
                Log::error('ResponseGenerator::generate failed', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
                // Ultimate fallback
                $response = [
                    'reply' => $this->llmService->getFallbackResponse($message, $intent),
                    'products' => [],
                    'suggestions' => ['Tư vấn mỹ phẩm', '/reset'],
                ];
            }
            
            // 9. Lưu tin nhắn assistant (không block nếu fail)
            try {
                $assistantMessage = BotMessage::create([
                    'conversation_id' => $conversation->id,
                    'role' => 'assistant',
                    'content' => $response['reply'],
                    'intent' => $intent,
                    'confidence' => $confidence,
                    'tools_used' => $toolsResult,
                    'metadata' => [
                        'products_count' => count($response['products'] ?? []),
                        'suggestions_count' => count($response['suggestions'] ?? []),
                    ],
                ]);
            } catch (\Throwable $e) {
                Log::warning('BotMessage::create failed', ['error' => $e->getMessage()]);
                $assistantMessage = null;
            }
            
            // 10. Update context với entities đã extract (không block nếu fail)
            try {
                $this->contextManager->save($conversation, $context);
            } catch (\Throwable $e) {
                Log::warning('ContextManager::save failed', ['error' => $e->getMessage()]);
            }
            
            // 11. Analytics (không block nếu fail)
            try {
                $latency = (microtime(true) - $startTime) * 1000;
                $this->analytics->logInteraction($conversation, $userMessage, $assistantMessage, [
                    'intent' => $intent,
                    'confidence' => $confidence,
                    'tools_used' => array_keys($toolsResult),
                    'latency_ms' => $latency,
                ]);
            } catch (\Throwable $e) {
                // Silent fail cho analytics
                Log::warning('Analytics logging failed', ['error' => $e->getMessage()]);
            }
            
            // 12. Update conversation
            try {
                $conversation->touch();
            } catch (\Throwable $e) {
                // Silent fail
            }
            
            return $response;
            
        } catch (\Throwable $e) {
            Log::error('BotAgent::process failed', [
                'message' => $message,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Fallback response với message để cố gắng trả lời
            try {
                // Try to get fallback từ LLMService
                $fallbackContent = $this->llmService->getFallbackResponse($message, 'unknown');
                return [
                    'reply' => $fallbackContent,
                    'products' => [],
                    'suggestions' => ['Tư vấn mỹ phẩm', '/reset'],
                ];
            } catch (\Throwable $e2) {
                Log::error('Fallback response also failed', [
                    'error' => $e2->getMessage(),
                ]);
                
                // Ultimate fallback - simple response
                $lower = mb_strtolower($message);
                $reply = "Mình hiểu bạn đang tìm kiếm thông tin! Bạn có thể:\n- **Tư vấn sản phẩm** theo loại da, ngân sách\n- **Tra cứu đơn hàng** bằng mã đơn\n- **Hỏi về chính sách** (ship, đổi trả, thanh toán)\n\nBạn muốn hỏi gì cụ thể nhỉ? 😊";
                
                if (preg_match('/\b(sữa rửa mặt|rửa mặt|cleanser)\b/u', $lower)) {
                    $reply = "Mình sẽ tìm sản phẩm rửa mặt phù hợp cho bạn! Bạn có thể cho mình biết:\n- **Loại da** (dầu, khô, hỗn hợp, nhạy cảm)\n- **Vấn đề da** (mụn, thâm, lỗ chân lông...)\n- **Ngân sách** (VD: 300-500k)";
                } elseif (preg_match('/\b(phí ship|ship|vận chuyển)\b/u', $lower)) {
                    $reply = "**Phí vận chuyển:**\n- Miễn phí ship cho đơn từ 500.000₫\n- Phí ship 30.000₫ cho đơn dưới 500.000₫\n- Giao hàng toàn quốc trong 2-5 ngày làm việc";
                }
                
                return [
                    'reply' => $reply,
                    'products' => [],
                    'suggestions' => ['Tư vấn mỹ phẩm', '/reset'],
                ];
            }
        }
    }

    /**
     * Lấy hoặc tạo conversation
     */
    private function getOrCreateConversation(?string $sessionId, ?int $userId): BotConversation
    {
        $sessionId = $sessionId ?: session()->getId();
        
        // Tìm conversation active gần nhất
        $conversation = BotConversation::where('session_id', $sessionId)
            ->where('status', 'active')
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->latest('updated_at')
            ->first();
        
        if (!$conversation) {
            $conversation = BotConversation::create([
                'session_id' => $sessionId,
                'user_id' => $userId,
                'status' => 'active',
                'metadata' => [],
                'started_at' => now(),
            ]);
        }
        
        return $conversation;
    }

    /**
     * Reset conversation
     */
    public function reset(?string $sessionId = null, ?int $userId = null): void
    {
        $sessionId = $sessionId ?: session()->getId();
        
        BotConversation::where('session_id', $sessionId)
            ->where('status', 'active')
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->update(['status' => 'completed', 'completed_at' => now()]);
    }
}

