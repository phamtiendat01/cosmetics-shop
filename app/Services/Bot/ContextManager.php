<?php

namespace App\Services\Bot;

use App\Models\BotConversation;
use App\Models\BotMessage;
use Illuminate\Support\Str;

/**
 * ContextManager - Quản lý ngữ cảnh hội thoại
 * Extract entities, lưu context vào database
 */
class ContextManager
{
    /**
     * Load context từ conversation
     */
    public function load(BotConversation $conversation): array
    {
        $messages = $conversation->messages()
            ->orderBy('created_at')
            ->limit(20) // Giới hạn 20 tin nhắn gần nhất
            ->get();

        $context = [
            'conversation_id' => $conversation->id,
            'user_id' => $conversation->user_id,
            'metadata' => $conversation->metadata ?? [],
            'history' => $messages->map(fn($m) => [
                'role' => $m->role,
                'content' => $m->content,
                'intent' => $m->intent,
            ])->toArray(),
            'entities' => $this->extractEntities($messages),
            'last_intent' => $messages->where('intent', '!=', null)->last()?->intent,
        ];

        return $context;
    }

    /**
     * Extract entities từ một message cụ thể (public method)
     */
    public function extractEntitiesFromMessage(string $message): array
    {
        $entities = [
            'skin_types' => [],
            'concerns' => [],
            'ingredients' => [],
            'product_type' => null,
            'budget' => ['min' => null, 'max' => null],
            'name' => null,
            'last_product' => null,
        ];

        $content = Str::lower($message);

        // Extract skin types
        $skinMap = [
            'da dầu' => 'oily',
            'da khô' => 'dry',
            'hỗn hợp' => 'combination',
            'nhạy cảm' => 'sensitive',
            'thường' => 'normal',
        ];
        foreach ($skinMap as $vi => $code) {
            if (Str::contains($content, $vi) && !in_array($code, $entities['skin_types'])) {
                $entities['skin_types'][] = $code;
            }
        }

        // Extract concerns
        $concernMap = [
            'mụn' => 'acne',
            'đầu đen' => 'blackheads',
            'thâm' => 'dark_spots',
            'nám' => 'melasma',
            'tàn nhang' => 'freckles',
            'lỗ chân lông' => 'pores',
            'lão hoá' => 'aging',
            'dưỡng ẩm' => 'hydration',
        ];
        foreach ($concernMap as $vi => $code) {
            if (Str::contains($content, $vi) && !in_array($code, $entities['concerns'])) {
                $entities['concerns'][] = $code;
            }
        }

        // Extract budget (check "dưới" trước vì specific hơn)
        if (preg_match('/dưới\s+(\d{2,5})\s*k\b/u', $content, $m)) {
            $v = (int)$m[1] * 1000;
            $entities['budget'] = ['min' => 0, 'max' => $v];
        } elseif (preg_match('/(\d{2,5})\s*-\s*(\d{2,5})\s*k\b/u', $content, $m)) {
            $entities['budget'] = ['min' => (int)$m[1] * 1000, 'max' => (int)$m[2] * 1000];
        } elseif (preg_match('/(\d{2,5})\s*k\b/u', $content, $m)) {
            $v = (int)$m[1] * 1000;
            $entities['budget'] = ['min' => $v * 0.8, 'max' => $v * 1.2];
        } elseif (preg_match('/(\d)\s*(triệu|trieu)/u', $content, $m)) {
            $v = (int)$m[1] * 1000000;
            $entities['budget'] = ['min' => $v * 0.8, 'max' => $v * 1.2];
        }

        // Extract product type - QUAN TRỌNG: map đúng với database
        // Check theo thứ tự từ specific đến general
        if (Str::contains($content, 'chống nắng') || Str::contains($content, 'sunscreen') || Str::contains($content, 'spf')) {
            // "kem chống nắng" phải là sunscreen, không phải cream
            $entities['product_type'] = 'sunscreen';
        } elseif (Str::contains($content, 'sữa rửa mặt') || Str::contains($content, 'rửa mặt') || Str::contains($content, 'cleanser')) {
            $entities['product_type'] = 'cleanser';
        } elseif (Str::contains($content, 'serum')) {
            $entities['product_type'] = 'serum';
        } elseif (Str::contains($content, 'kem mắt') || Str::contains($content, 'eye cream')) {
            $entities['product_type'] = 'eye_cream';
        } elseif (Str::contains($content, 'kem') && !Str::contains($content, 'chống nắng')) {
            // "kem" nhưng không phải "kem chống nắng"
            $entities['product_type'] = 'cream';
        } elseif (Str::contains($content, 'toner')) {
            $entities['product_type'] = 'toner';
        } elseif (Str::contains($content, 'dưỡng ẩm') || Str::contains($content, 'moisturizer')) {
            $entities['product_type'] = 'moisturizer';
        } elseif (Str::contains($content, 'mặt nạ') || Str::contains($content, 'mask')) {
            $entities['product_type'] = 'mask';
        } elseif (Str::contains($content, 'essence')) {
            $entities['product_type'] = 'essence';
        }

        // Extract ingredients
        $ingredientMap = [
            'hyaluronic' => 'hyaluronic_acid',
            'niacinamide' => 'niacinamide',
            'retinol' => 'retinol',
            'vitamin c' => 'vitamin_c',
            'salicylic' => 'salicylic_acid',
            'glycolic' => 'glycolic_acid',
            'peptide' => 'peptides',
            'ceramide' => 'ceramides',
            'snail' => 'snail_mucin',
            'centella' => 'centella',
            'tea tree' => 'tea_tree',
            'aloe' => 'aloe_vera',
        ];
        foreach ($ingredientMap as $vi => $code) {
            if (Str::contains($content, $vi) && !in_array($code, $entities['ingredients'])) {
                $entities['ingredients'][] = $code;
            }
        }

        return $entities;
    }

    /**
     * Extract entities từ messages
     */
    private function extractEntities($messages): array
    {
        $entities = [
            'skin_types' => [],
            'concerns' => [],
            'ingredients' => [],
            'product_type' => null,
            'budget' => ['min' => null, 'max' => null],
            'name' => null,
            'last_product' => null,
        ];

        foreach ($messages as $msg) {
            if ($msg->role !== 'user') continue;

            $content = Str::lower($msg->content);

            // Extract skin types - check cả "dầu" đơn lẻ
            $skinMap = [
                'da dầu' => 'oily',
                'dầu' => 'oily', // Check "dầu" đơn lẻ
                'da khô' => 'dry',
                'khô' => 'dry', // Check "khô" đơn lẻ
                'hỗn hợp' => 'combination',
                'da hỗn hợp' => 'combination',
                'nhạy cảm' => 'sensitive',
                'da nhạy cảm' => 'sensitive',
                'thường' => 'normal',
                'da thường' => 'normal',
            ];
            foreach ($skinMap as $vi => $code) {
                if (Str::contains($content, $vi) && !in_array($code, $entities['skin_types'])) {
                    $entities['skin_types'][] = $code;
                }
            }

            // Extract concerns
            $concernMap = [
                'mụn' => 'acne',
                'đầu đen' => 'blackheads',
                'thâm' => 'dark_spots',
                'nám' => 'melasma',
                'tàn nhang' => 'freckles',
                'lỗ chân lông' => 'pores',
                'lão hoá' => 'aging',
                'dưỡng ẩm' => 'hydration',
            ];
            foreach ($concernMap as $vi => $code) {
                if (Str::contains($content, $vi) && !in_array($code, $entities['concerns'])) {
                    $entities['concerns'][] = $code;
                }
            }

            // Extract budget
            if (preg_match('/(\d{2,5})\s*-\s*(\d{2,5})\s*k\b/u', $content, $m)) {
                $entities['budget'] = ['min' => (int)$m[1] * 1000, 'max' => (int)$m[2] * 1000];
            } elseif (preg_match('/(\d{2,5})\s*k\b/u', $content, $m)) {
                $v = (int)$m[1] * 1000;
                $entities['budget'] = ['min' => $v * 0.8, 'max' => $v * 1.2];
            } elseif (preg_match('/(\d)\s*(triệu|trieu)/u', $content, $m)) {
                $v = (int)$m[1] * 1000000;
                $entities['budget'] = ['min' => $v * 0.8, 'max' => $v * 1.2];
            }

            // Extract product type - QUAN TRỌNG: map đúng với database
            // Check theo thứ tự từ specific đến general
            if (Str::contains($content, 'chống nắng') || Str::contains($content, 'sunscreen') || Str::contains($content, 'spf')) {
                // "kem chống nắng" phải là sunscreen, không phải cream
                $entities['product_type'] = 'sunscreen';
            } elseif (Str::contains($content, 'sữa rửa mặt') || Str::contains($content, 'rửa mặt') || Str::contains($content, 'cleanser')) {
                $entities['product_type'] = 'cleanser';
            } elseif (Str::contains($content, 'serum')) {
                $entities['product_type'] = 'serum';
            } elseif (Str::contains($content, 'kem mắt') || Str::contains($content, 'eye cream')) {
                $entities['product_type'] = 'eye_cream';
            } elseif (Str::contains($content, 'kem') && !Str::contains($content, 'chống nắng')) {
                // "kem" nhưng không phải "kem chống nắng"
                $entities['product_type'] = 'cream';
            } elseif (Str::contains($content, 'toner')) {
                $entities['product_type'] = 'toner';
            } elseif (Str::contains($content, 'dưỡng ẩm') || Str::contains($content, 'moisturizer')) {
                $entities['product_type'] = 'moisturizer';
            } elseif (Str::contains($content, 'mặt nạ') || Str::contains($content, 'mask')) {
                $entities['product_type'] = 'mask';
            } elseif (Str::contains($content, 'essence')) {
                $entities['product_type'] = 'essence';
            }

            // Extract ingredients
            $ingredientMap = [
                'hyaluronic' => 'hyaluronic_acid',
                'niacinamide' => 'niacinamide',
                'retinol' => 'retinol',
                'vitamin c' => 'vitamin_c',
                'salicylic' => 'salicylic_acid',
                'glycolic' => 'glycolic_acid',
                'peptide' => 'peptides',
                'ceramide' => 'ceramides',
                'snail' => 'snail_mucin',
                'centella' => 'centella',
                'tea tree' => 'tea_tree',
                'aloe' => 'aloe_vera',
            ];
            foreach ($ingredientMap as $vi => $code) {
                if (Str::contains($content, $vi) && !in_array($code, $entities['ingredients'])) {
                    $entities['ingredients'][] = $code;
                }
            }

            // Extract name
            if (preg_match('/\b(mình|tôi|em|anh|chị)\s+(tên|là)\s+([a-zA-ZÀ-ỹ]+)\b/u', $content, $m)) {
                $entities['name'] = Str::title($m[3]);
            }
        }

        return $entities;
    }

    /**
     * Update intent trong context
     */
    public function updateIntent(BotConversation $conversation, string $intent, float $confidence): void
    {
        $metadata = $conversation->metadata ?? [];
        $metadata['last_intent'] = $intent;
        $metadata['last_intent_confidence'] = $confidence;

        $conversation->update(['metadata' => $metadata]);
    }

    /**
     * Save context
     */
    public function save(BotConversation $conversation, array $context): void
    {
        $metadata = $conversation->metadata ?? [];
        $metadata['entities'] = $context['entities'] ?? [];
        $metadata['last_updated'] = now()->toIso8601String();

        $conversation->update(['metadata' => $metadata]);
    }
}

