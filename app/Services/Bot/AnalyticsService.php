<?php

namespace App\Services\Bot;

use App\Models\BotConversation;
use App\Models\BotMessage;
use App\Models\BotAnalytic;
use Illuminate\Support\Facades\Log;

/**
 * AnalyticsService - Log và phân tích interactions
 */
class AnalyticsService
{
    /**
     * Log interaction
     */
    public function logInteraction(
        BotConversation $conversation,
        BotMessage $userMessage,
        BotMessage $assistantMessage,
        array $metadata = []
    ): void {
        try {
            // Log intent detected
            if (isset($metadata['intent'])) {
                BotAnalytic::create([
                    'conversation_id' => $conversation->id,
                    'message_id' => $userMessage->id,
                    'event_type' => 'intent_detected',
                    'data' => [
                        'intent' => $metadata['intent'],
                        'confidence' => $metadata['confidence'] ?? null,
                    ],
                    'session_id' => $conversation->session_id,
                    'user_id' => $conversation->user_id,
                ]);
            }
            
            // Log tools used
            if (!empty($metadata['tools_used'])) {
                foreach ($metadata['tools_used'] as $tool) {
                    BotAnalytic::create([
                        'conversation_id' => $conversation->id,
                        'message_id' => $assistantMessage->id,
                        'event_type' => 'tool_called',
                        'data' => ['tool' => $tool],
                        'session_id' => $conversation->session_id,
                        'user_id' => $conversation->user_id,
                    ]);
                }
            }
            
            // Log latency
            if (isset($metadata['latency_ms'])) {
                BotAnalytic::create([
                    'conversation_id' => $conversation->id,
                    'message_id' => $assistantMessage->id,
                    'event_type' => 'latency',
                    'data' => ['latency_ms' => $metadata['latency_ms']],
                    'session_id' => $conversation->session_id,
                    'user_id' => $conversation->user_id,
                ]);
            }
            
        } catch (\Throwable $e) {
            Log::warning('AnalyticsService::logInteraction failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log user satisfaction (thumbs up/down)
     */
    public function logSatisfaction(
        BotConversation $conversation,
        BotMessage $message,
        bool $positive
    ): void {
        try {
            BotAnalytic::create([
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
                'event_type' => 'user_satisfaction',
                'data' => ['positive' => $positive],
                'session_id' => $conversation->session_id,
                'user_id' => $conversation->user_id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('AnalyticsService::logSatisfaction failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log conversion (user clicked product, placed order, etc)
     */
    public function logConversion(
        BotConversation $conversation,
        string $type,
        array $data = []
    ): void {
        try {
            BotAnalytic::create([
                'conversation_id' => $conversation->id,
                'event_type' => 'conversion',
                'data' => array_merge(['type' => $type], $data),
                'session_id' => $conversation->session_id,
                'user_id' => $conversation->user_id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('AnalyticsService::logConversion failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}

