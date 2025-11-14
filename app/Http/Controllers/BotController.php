<?php

namespace App\Http\Controllers;

use App\Services\Bot\BotAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BotController extends Controller
{
    public function __construct(
        private BotAgent $botAgent
    ) {}

    /**
     * GET /bot/tools
     * Lấy danh sách tools/questions để hiển thị trong chat widget
     */
    public function getTools()
    {
        $tools = \App\Models\BotTool::where('is_active', true)
            ->whereNotNull('question')
            ->whereNotNull('answer')
            ->orderBy('order')
            ->orderBy('category')
            ->orderBy('display_name')
            ->get(['id', 'question', 'answer', 'category', 'icon', 'order'])
            ->groupBy('category')
            ->map(function ($items) {
                return $items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'question' => $item->question,
                        'answer' => $item->answer,
                        'icon' => $item->icon,
                    ];
                });
            });

        return response()->json([
            'tools' => $tools,
            'categories' => $tools->keys()->toArray(),
        ]);
    }

    /**
     * POST /bot/chat
     * Endpoint chính cho chatbot
     */
    public function chat(Request $request)
    {
        try {
            $message = trim((string) $request->input('message', ''));
            $sessionId = $request->input('session_id') ?: session()->getId();
            $userId = auth()->id();
            $toolId = $request->input('tool_id'); // Nếu user chọn từ tool
            
            // Nếu có tool_id, trả về câu trả lời từ tool
            if ($toolId) {
                $tool = \App\Models\BotTool::where('id', $toolId)
                    ->where('is_active', true)
                    ->whereNotNull('answer')
                    ->first();
                
                if ($tool) {
                    return response()->json([
                        'reply' => $tool->answer,
                        'products' => [],
                        'suggestions' => $this->getDefaultSuggestions(),
                    ]);
                }
            }
            
            if (empty($message)) {
                return response()->json([
                    'reply' => 'Bạn cứ hỏi mình về *phí ship*, *đổi trả*, *thanh toán* hoặc nhờ tư vấn theo **loại da** và **ngân sách** nha ✨',
                    'products' => [],
                    'suggestions' => $this->getDefaultSuggestions(),
                ]);
            }
            
            // Handle /reset command
            if (str_starts_with(strtolower($message), '/reset')) {
                $this->botAgent->reset($sessionId, $userId);
                return response()->json([
                    'reply' => 'Đã làm mới hội thoại ✨ Bạn cần mình tư vấn gì nè?',
                    'products' => [],
                    'suggestions' => $this->getDefaultSuggestions(),
                ]);
            }
            
            // Process message
            $response = $this->botAgent->process($message, $sessionId, $userId);
            
            return response()->json($response);
            
        } catch (\Throwable $e) {
            Log::error('BotController::chat failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'reply' => 'Xin lỗi, mình gặp sự cố kỹ thuật. Bạn thử lại sau nhé.',
                'products' => [],
                'suggestions' => $this->getDefaultSuggestions(),
            ], 500);
        }
    }
    
    /**
     * Get default suggestions - Chỉ 2 nút: Tư vấn mỹ phẩm và Reset
     */
    private function getDefaultSuggestions(): array
    {
        return ['Tư vấn mỹ phẩm', '/reset'];
    }
}

