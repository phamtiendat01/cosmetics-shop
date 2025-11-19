<?php

namespace App\Http\Controllers;

use App\Http\Requests\BotChatRequest;
use App\Models\BotTool;
use App\Services\Bot\BotAgent;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class BotController extends Controller
{
    public function __construct(private BotAgent $botAgent)
    {
    }

    /** GET /bot/tools - quick FAQ tools for widget */
    public function getTools(): JsonResponse
    {
        $tools = BotTool::where('is_active', true)
            ->whereNotNull('question')
            ->whereNotNull('answer')
            ->orderBy('order')
            ->orderBy('category')
            ->orderBy('display_name')
            ->get(['id', 'question', 'answer', 'category', 'icon', 'order'])
            ->groupBy('category')
            ->map(function ($items) {
                return $items->map(fn($item) => [
                    'id'       => $item->id,
                    'question' => $item->question,
                    'answer'   => $item->answer,
                    'icon'     => $item->icon,
                ]);
            });

        return response()->json($this->wrapPayload([
            'tools'      => $tools,
            'categories' => $tools->keys()->toArray(),
        ]));
    }

    /** POST /bot/chat - main chatbot endpoint */
    public function chat(BotChatRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $message   = trim((string) ($validated['message'] ?? ''));
            $sessionId = $validated['session_id'] ?? session()->getId();
            $userId    = auth()->id();
            $toolId    = $validated['tool_id'] ?? null; // user chọn tool sẵn

            // Nếu chọn tool nhanh, trả ngay câu trả lời FAQ
            if ($toolId) {
                $tool = BotTool::whereKey($toolId)
                    ->where('is_active', true)
                    ->whereNotNull('answer')
                    ->first(['id', 'answer']);

                if ($tool) {
                    return response()->json($this->wrapPayload([
                        'reply'       => $tool->answer,
                        'products'    => [],
                        'suggestions' => $this->getDefaultSuggestions(),
                        'intent'      => 'quick_tool',
                        'tools_used'  => [$tool->id],
                    ]));
                }
            }

            if ($message === '') {
                return response()->json($this->wrapPayload([
                    'reply'       => 'Bạn có thể hỏi về vận chuyển, đổi trả, thanh toán hoặc nhờ tư vấn sản phẩm theo loại da + ngân sách nhé!',
                    'products'    => [],
                    'suggestions' => $this->getDefaultSuggestions(),
                    'intent'      => 'smalltalk.greeting',
                ]));
            }

            // Handle /reset command
            if (str_starts_with(strtolower($message), '/reset')) {
                $this->botAgent->reset($sessionId, $userId);

                return response()->json($this->wrapPayload([
                    'reply'       => 'Đã làm mới cuộc hội thoại. Bạn cần mình tư vấn gì tiếp?',
                    'products'    => [],
                    'suggestions' => $this->getDefaultSuggestions(),
                    'intent'      => 'command.reset',
                ]));
            }

            // Process message
            $response = $this->botAgent->process($message, $sessionId, $userId);

            return response()->json($this->wrapPayload($response));
        } catch (\Throwable $e) {
            Log::error('BotController::chat failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json($this->wrapPayload([
                'reply'       => 'Xin lỗi, mình đang gặp sự cố kỹ thuật. Bạn thử lại sau nhé.',
                'products'    => [],
                'suggestions' => $this->getDefaultSuggestions(),
                'intent'      => 'error',
            ]), 500);
        }
    }

    /** Get default suggestions - chỉ 2 nút: Tư vấn mỹ phẩm và Reset */
    private function getDefaultSuggestions(): array
    {
        return ['Tư vấn mỹ phẩm', '/reset'];
    }

    /** Đảm bảo payload trả về đủ trường, tránh client vỡ UI */
    private function wrapPayload(array $payload): array
    {
        $wrapped = [
            'reply'       => $payload['reply']       ?? '',
            'products'    => $payload['products']    ?? [],
            'suggestions' => $payload['suggestions'] ?? $this->getDefaultSuggestions(),
            'intent'      => $payload['intent']      ?? null,
            'tools_used'  => $payload['tools_used']  ?? [],
            'meta'        => $payload['meta']        ?? [],
            'tools'       => $payload['tools']       ?? null,
            'categories'  => $payload['categories']  ?? null,
        ];
        
        // ✅ Nếu có redirect_url trong meta, thêm vào response để frontend có thể xử lý
        if (!empty($wrapped['meta']['redirect_url'])) {
            $wrapped['redirect_url'] = $wrapped['meta']['redirect_url'];
        }
        
        return $wrapped;
    }
}
