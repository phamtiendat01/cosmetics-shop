<?php

namespace App\Services\Bot;

use Illuminate\Support\Str;

/**
 * SentimentAnalyzer - Phân tích cảm xúc của user message
 * Giúp bot điều chỉnh tone phù hợp
 */
class SentimentAnalyzer
{
    /**
     * Analyze sentiment của message
     * 
     * @return array {sentiment: 'positive'|'neutral'|'negative', score: float, tone: string}
     */
    public function analyze(string $message): array
    {
        $lower = Str::lower($message);
        
        // Positive keywords
        $positiveKeywords = ['cảm ơn', 'tốt', 'hay', 'đẹp', 'thích', 'ok', 'được', 'tuyệt', 'xuất sắc', 'hài lòng'];
        $positiveCount = 0;
        foreach ($positiveKeywords as $keyword) {
            if (Str::contains($lower, $keyword)) {
                $positiveCount++;
            }
        }
        
        // Negative keywords
        $negativeKeywords = ['tệ', 'xấu', 'không', 'sai', 'lỗi', 'hỏng', 'chậm', 'tức', 'bực', 'không hài lòng', 'thất vọng'];
        $negativeCount = 0;
        foreach ($negativeKeywords as $keyword) {
            if (Str::contains($lower, $keyword)) {
                $negativeCount++;
            }
        }
        
        // Determine sentiment
        if ($positiveCount > $negativeCount && $positiveCount > 0) {
            $sentiment = 'positive';
            $score = min(0.8 + ($positiveCount * 0.1), 1.0);
            $tone = 'friendly_enthusiastic';
        } elseif ($negativeCount > $positiveCount && $negativeCount > 0) {
            $sentiment = 'negative';
            $score = max(0.2 - ($negativeCount * 0.1), 0.0);
            $tone = 'empathetic_supportive';
        } else {
            $sentiment = 'neutral';
            $score = 0.5;
            $tone = 'professional_friendly';
        }
        
        return [
            'sentiment' => $sentiment,
            'score' => $score,
            'tone' => $tone,
        ];
    }
    
    /**
     * Get tone suggestion cho LLM prompt
     */
    public function getToneSuggestion(array $sentimentResult): string
    {
        $tone = $sentimentResult['tone'] ?? 'professional_friendly';
        
        $toneMap = [
            'friendly_enthusiastic' => 'Trả lời với tone vui vẻ, nhiệt tình, đồng cảm với sự hài lòng của khách hàng',
            'empathetic_supportive' => 'Trả lời với tone đồng cảm, hỗ trợ, cố gắng giải quyết vấn đề một cách tích cực',
            'professional_friendly' => 'Trả lời với tone chuyên nghiệp nhưng thân thiện, tự nhiên',
        ];
        
        return $toneMap[$tone] ?? $toneMap['professional_friendly'];
    }
}

