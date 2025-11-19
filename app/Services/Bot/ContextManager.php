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

        $metadata = $conversation->metadata ?? [];

        // ✅ Load checkout state và data
        $checkoutStateManager = app(\App\Services\Bot\CheckoutStateManager::class);
        $checkoutState = $checkoutStateManager->getState($conversation);
        $checkoutData = $checkoutStateManager->getData($conversation);

        $context = [
            'conversation_id' => $conversation->id,
            'user_id' => $conversation->user_id,
            'metadata' => $metadata,
            'history' => $messages->map(fn($m) => [
                'role' => $m->role,
                'content' => $m->content,
                'intent' => $m->intent,
            ])->toArray(),
            'entities' => $this->extractEntities($messages),
            'last_intent' => $messages->where('intent', '!=', null)->last()?->intent,
            // Lưu danh sách sản phẩm đã trả về trước đó (để hỏi về sản phẩm thứ nhất, thứ hai...)
            'last_products' => $metadata['last_products'] ?? [],
            // ✅ Checkout state và data
            'checkout_state' => $checkoutState,
            'checkout_data' => $checkoutData,
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
            'product_index' => null, // Sản phẩm thứ nhất, thứ hai...
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

        // Extract budget (check "trên" và "dưới" trước vì specific hơn)
        // ✅ Pattern 0: "bình dân", "sinh viên", "giá rẻ", "tầm trung", "cao cấp" (check TRƯỚC các pattern số)
        if (preg_match('/\b(bình dân|sinh viên|student|giá rẻ|rẻ|affordable|cheap)\b/u', $content)) {
            // Budget cho sinh viên/bình dân: 100k - 300k
            $entities['budget'] = ['min' => 100000, 'max' => 300000];
        } elseif (preg_match('/\b(tầm trung|trung bình|moderate|mid-range)\b/u', $content)) {
            // Budget tầm trung: 300k - 800k
            $entities['budget'] = ['min' => 300000, 'max' => 800000];
        } elseif (preg_match('/\b(cao cấp|premium|luxury|đắt|expensive)\b/u', $content)) {
            // Budget cao cấp: trên 1 triệu
            $entities['budget'] = ['min' => 1000000, 'max' => null];
        }
        // Pattern 1: "trên Xk" hoặc "từ Xk trở lên" hoặc "từ Xk" hoặc "Xk trở lên"
        elseif (preg_match('/(?:trên|từ)\s+(\d{2,5})\s*(?:k|nghìn|nghin)(?:\s+trở\s+lên)?\b/i', $content, $m)) {
            $v = (int)$m[1] * 1000;
            $entities['budget'] = ['min' => $v, 'max' => null]; // Chỉ có min, không có max
        }
        // Pattern 1.5: "trên X triệu" hoặc "từ X triệu trở lên" hoặc "từ X triệu" (QUAN TRỌNG: Phải check TRƯỚC pattern 5)
        elseif (preg_match('/(?:trên|từ)\s+(\d+)\s*(?:triệu|trieu)(?:\s+trở\s+lên)?\b/u', $content, $m)) {
            $v = (int)$m[1] * 1000000;
            $entities['budget'] = ['min' => $v, 'max' => null]; // Chỉ có min, không có max
        }
        // Pattern 2: "dưới Xk" hoặc "tài chính dưới XK" hoặc "dưới X nghìn" (cho phép có từ ở giữa)
        // QUAN TRỌNG: Phải check "dưới" TRƯỚC pattern "tài chính Xk" để tránh nhầm lẫn
        elseif (preg_match('/(?:tài chính\s+)?dưới\s+(\d{2,5})\s*(?:k|nghìn|nghin)\b/i', $content, $m)) {
            $v = (int)$m[1] * 1000;
            $entities['budget'] = ['min' => 0, 'max' => $v];
        }
        // Pattern 2.5: "dưới X triệu" hoặc "tài chính dưới X triệu" (QUAN TRỌNG: Phải check TRƯỚC pattern 5)
        elseif (preg_match('/(?:tài chính\s+)?dưới\s+(\d+)\s*(?:triệu|trieu)\b/u', $content, $m)) {
            $v = (int)$m[1] * 1000000;
            $entities['budget'] = ['min' => 0, 'max' => $v];
        }
        // Pattern 3: "khoảng X-Yk" hoặc "X-Yk" hoặc "X-Y nghìn"
        elseif (preg_match('/(?:khoảng\s+)?(\d{2,5})\s*-\s*(\d{2,5})\s*(?:k|nghìn|nghin)\b/i', $content, $m)) {
            $entities['budget'] = ['min' => (int)$m[1] * 1000, 'max' => (int)$m[2] * 1000];
        }
        // Pattern 4: "Xk" hoặc "giá Xk" hoặc "tài chính XK" (KHÔNG có "dưới") hoặc "X nghìn" (khoảng X ± 20%)
        // QUAN TRỌNG: Chỉ match nếu KHÔNG có "dưới" hoặc "trên" trước đó
        elseif (preg_match('/(?:giá|tài chính|budget|ngân sách)?\s*(\d{2,5})\s*(?:k|nghìn|nghin)\b/i', $content, $m)) {
            // Check xem có "dưới" hoặc "trên" trong message không (để tránh nhầm lẫn)
            $beforeMatch = substr($content, 0, strpos($content, $m[0]));
            if (!preg_match('/\b(?:dưới|trên|từ)\s*$/i', $beforeMatch)) {
                $v = (int)$m[1] * 1000;
                $entities['budget'] = ['min' => $v * 0.8, 'max' => $v * 1.2];
            }
        }
        // Pattern 5: "X triệu" (KHÔNG có "trên" hoặc "dưới") - khoảng X ± 20%
        // QUAN TRỌNG: Chỉ match nếu KHÔNG có "dưới" hoặc "trên" trước đó
        elseif (preg_match('/(\d+)\s*(?:triệu|trieu)\b/u', $content, $m)) {
            // Check xem có "dưới" hoặc "trên" trong message không (để tránh nhầm lẫn)
            $beforeMatch = substr($content, 0, strpos($content, $m[0]));
            if (!preg_match('/\b(?:dưới|trên|từ)\s+/u', $beforeMatch)) {
                $v = (int)$m[1] * 1000000;
                $entities['budget'] = ['min' => $v * 0.8, 'max' => $v * 1.2];
            }
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

        // ✅ Extract product_name từ message (khi user nói tên sản phẩm để đặt hàng)
        // Pattern: "Tôi muốn đặt [tên sản phẩm]", "Mua [tên sản phẩm]", "Đặt [tên sản phẩm]"
        if (preg_match('/\b(?:tôi\s+muốn\s+đặt|mua|đặt|cho\s+tôi)\s+([^,\.!?\n]+?)(?:\s+(?:với|cho|giá|giá|số lượng))?/u', $content, $m)) {
            $productNameCandidate = trim($m[1] ?? '');
            // Loại bỏ các từ không cần thiết
            $stopWords = ['sản phẩm', 'sp', 'product', 'cái', 'item', 'đầu tiên', 'thứ nhất', 'thứ hai'];
            foreach ($stopWords as $stopWord) {
                $productNameCandidate = str_ireplace($stopWord, '', $productNameCandidate);
            }
            $productNameCandidate = trim($productNameCandidate);

            // Nếu không phải là số (product_index) và có độ dài hợp lý
            if (!preg_match('/^\d+$/', $productNameCandidate) && mb_strlen($productNameCandidate) >= 3) {
                $entities['product_name'] = $productNameCandidate;
            }
        }

        // Extract product index (sản phẩm thứ nhất, thứ hai, số 1, số 2...)
        // Pattern: "sản phẩm thứ nhất", "sản phẩm số 2", "sản phẩm đầu tiên", "sản phẩm thứ 3"
        // ✅ Cải thiện: cũng match "đặt sản phẩm đầu tiên", "mua sản phẩm thứ hai"
        if (preg_match('/(?:đặt|mua|cho|tôi\s+muốn)\s+sản phẩm\s+(?:thứ\s+)?(?:số\s+)?(?:đầu tiên|nhất|hai|ba|bốn|năm|sáu|bảy|tám|chín|mười|1|2|3|4|5|6|7|8|9|10)\b/u', $content, $m) ||
            preg_match('/sản phẩm\s+(?:thứ\s+)?(?:số\s+)?(?:đầu tiên|nhất|hai|ba|bốn|năm|sáu|bảy|tám|chín|mười|1|2|3|4|5|6|7|8|9|10)\b/u', $content, $m)) {
            $match = $m[0];
            $index = null;

            // Map từ tiếng Việt sang số
            $numberMap = [
                'đầu tiên' => 1, 'nhất' => 1, 'một' => 1, '1' => 1,
                'hai' => 2, '2' => 2,
                'ba' => 3, '3' => 3,
                'bốn' => 4, '4' => 4,
                'năm' => 5, '5' => 5,
                'sáu' => 6, '6' => 6,
                'bảy' => 7, '7' => 7,
                'tám' => 8, '8' => 8,
                'chín' => 9, '9' => 9,
                'mười' => 10, '10' => 10,
            ];

            foreach ($numberMap as $word => $num) {
                if (Str::contains($match, $word)) {
                    $index = $num;
                    break;
                }
            }

            // Nếu không tìm thấy, thử extract số trực tiếp
            if ($index === null && preg_match('/(\d+)/', $match, $numMatch)) {
                $index = (int)$numMatch[1];
            }

            if ($index !== null && $index >= 1 && $index <= 10) {
                $entities['product_index'] = $index; // 1-based index
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

            // Extract product index (sản phẩm thứ nhất, thứ hai, số 1, số 2...)
            // Pattern: "sản phẩm thứ nhất", "sản phẩm số 2", "sản phẩm đầu tiên", "sản phẩm thứ 3"
            if (preg_match('/sản phẩm\s+(?:thứ\s+)?(?:số\s+)?(?:đầu tiên|nhất|hai|ba|bốn|năm|sáu|bảy|tám|chín|mười|1|2|3|4|5|6|7|8|9|10)\b/u', $content, $m)) {
                $match = $m[0];
                $index = null;

                // Map từ tiếng Việt sang số
                $numberMap = [
                    'đầu tiên' => 1, 'nhất' => 1, 'một' => 1, '1' => 1,
                    'hai' => 2, '2' => 2,
                    'ba' => 3, '3' => 3,
                    'bốn' => 4, '4' => 4,
                    'năm' => 5, '5' => 5,
                    'sáu' => 6, '6' => 6,
                    'bảy' => 7, '7' => 7,
                    'tám' => 8, '8' => 8,
                    'chín' => 9, '9' => 9,
                    'mười' => 10, '10' => 10,
                ];

                foreach ($numberMap as $word => $num) {
                    if (Str::contains($match, $word)) {
                        $index = $num;
                        break;
                    }
                }

                // Nếu không tìm thấy, thử extract số trực tiếp
                if ($index === null && preg_match('/(\d+)/', $match, $numMatch)) {
                    $index = (int)$numMatch[1];
                }

                if ($index !== null && $index >= 1 && $index <= 10) {
                    $entities['product_index'] = $index; // 1-based index
                }
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
        // Lưu danh sách sản phẩm đã trả về (để hỏi về sản phẩm thứ nhất, thứ hai...)
        if (!empty($context['last_products'])) {
            $metadata['last_products'] = $context['last_products'];
        }

        $conversation->update(['metadata' => $metadata]);
    }
}

