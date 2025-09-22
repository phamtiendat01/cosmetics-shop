<?php
// app/Services/ConversationMemory.php

namespace App\Services;

use Illuminate\Support\Str;

class ConversationMemory
{
    const SESSION_KEY = 'bot.mem';
    const MAX_TURNS = 10; // số lượt tin nhắn (user/model) giữ trong lịch sử

    public static function load(): array
    {
        $mem = session(self::SESSION_KEY, []);
        // cấu trúc mặc định
        $mem += [
            'prefs' => [
                'skin_type'  => null,        // 'dầu'|'khô'|'hỗn hợp'|'nhạy cảm'|...
                'concerns'   => [],          // ['mụn','thâm',...]
                'budget_min' => null,
                'budget_max' => null,
            ],
            'last_product_slug' => null,
            'last_product_name' => null,
            'last_order_code'   => null,
            'history'           => [],     // [['role'=>'user'|'model','text'=>'...']]
        ];
        return $mem;
    }

    public static function save(array $mem): void
    {
        // cắt lịch sử cho gọn
        if (count($mem['history']) > self::MAX_TURNS) {
            $mem['history'] = array_slice($mem['history'], -self::MAX_TURNS);
        }
        session([self::SESSION_KEY => $mem]);
    }

    public static function reset(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /** Thêm một lượt vào lịch sử */
    public static function pushTurn(array &$mem, string $role, string $text): void
    {
        $mem['history'][] = ['role' => $role, 'text' => trim($text)];
        if (count($mem['history']) > self::MAX_TURNS) {
            $mem['history'] = array_slice($mem['history'], -self::MAX_TURNS);
        }
    }

    /** Rút gọn lịch sử thành contents cho Gemini */
    public static function asGeminiHistory(array $mem): array
    {
        $out = [];
        foreach ($mem['history'] as $turn) {
            $out[] = [
                'role'  => $turn['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $turn['text']]],
            ];
        }
        return $out;
    }

    /** System prompt sinh từ memory (sở thích người dùng, tham chiếu gần nhất) */
    public static function systemFromMemory(array $mem): string
    {
        $p = $mem['prefs'];
        $prefs = [];
        if ($p['skin_type'])  $prefs[] = "Loại da: {$p['skin_type']}";
        if ($p['concerns'])   $prefs[] = "Concern: " . implode(', ', $p['concerns']);
        if ($p['budget_min'] || $p['budget_max']) {
            $prefs[] = "Ngân sách: " . self::fmtPriceRange($p['budget_min'], $p['budget_max']);
        }
        $prefsStr = $prefs ? ("Sở thích đã biết của user: " . implode(' • ', $prefs) . ".") : "Chưa có sở thích rõ ràng.";

        $refs = [];
        if ($mem['last_product_name']) $refs[] = "Sản phẩm gần nhất: {$mem['last_product_name']} (slug: {$mem['last_product_slug']})";
        if ($mem['last_order_code'])   $refs[] = "Mã đơn gần nhất: {$mem['last_order_code']}";
        $refStr = $refs ? ("Tham chiếu gần nhất: " . implode(' • ', $refs) . ".") : "Chưa có tham chiếu gần nhất.";

        return "Bạn là CosmeBot, trả lời TIẾNG VIỆT, ấm áp, gọn, có emoji khi phù hợp. "
            . "Luôn duy trì mạch hội thoại theo bộ nhớ dưới đây, trừ khi user thay đổi:\n"
            . "- {$prefsStr}\n- {$refStr}\n"
            . "Nếu user nói kiểu 'cái này/nó/loại này', hãy ưu tiên hiểu là đang nói tới tham chiếu gần nhất.";
    }

    /** Thêm gợi ý tham chiếu vào câu user nếu thấy đại từ */
    public static function applyCorefHint(string $userText, array $mem): string
    {
        $pronouns = ['cái này', 'cái đó', 'nó', 'loại này', 'em này', 'sản phẩm này', 'loại đó', 'em đó'];
        $hasPronoun = Str::contains(Str::lower($userText), $pronouns);

        if ($hasPronoun && $mem['last_product_name'] && $mem['last_product_slug']) {
            $hint = "(Ngữ cảnh: người dùng đang nói về sản phẩm gần nhất \"{$mem['last_product_name']}\"; slug={$mem['last_product_slug']}).";
            return $userText . "\n\n" . $hint;
        }
        return $userText;
    }

    /** Trích xuất sơ bộ prefs từ câu user (skin type, concern, ngân sách) */
    public static function extractUserSignals(string $text, array &$mem): void
    {
        $lower = Str::lower($text);

        // skin type
        foreach (['dầu', 'khô', 'hỗn hợp', 'nhạy cảm'] as $skin) {
            if (Str::contains($lower, "da {$skin}")) {
                $mem['prefs']['skin_type'] = $skin;
                break;
            }
        }

        // concerns
        $pool = ['mụn', 'thâm', 'nám', 'tàn nhang', 'lỗ chân lông', 'cháy nắng', 'lão hoá', 'đỏ rát', 'xỉn màu'];
        foreach ($pool as $c) {
            if (Str::contains($lower, $c) && !in_array($c, $mem['prefs']['concerns'])) {
                $mem['prefs']['concerns'][] = $c;
            }
        }

        // ngân sách
        if (preg_match_all('/(\d[\d\.]{2,})\s*(k|nghìn|ngàn|triệu|tr)/u', $lower, $ms, PREG_SET_ORDER)) {
            $nums = [];
            foreach ($ms as $m) {
                $n = (float) str_replace('.', '', $m[1]);
                $u = $m[2];
                if (in_array($u, ['k', 'nghìn', 'ngàn'])) $n *= 1000;
                if (in_array($u, ['triệu', 'tr']))      $n *= 1000000;
                $nums[] = $n;
            }
            sort($nums);
            if (count($nums) == 1) {
                $mem['prefs']['budget_min'] = null;
                $mem['prefs']['budget_max'] = $nums[0];
            } elseif (count($nums) >= 2) {
                $mem['prefs']['budget_min'] = $nums[0];
                $mem['prefs']['budget_max'] = end($nums);
            }
        }
    }

    /** Cập nhật tham chiếu từ kết quả tool */
    public static function updateRefsFromTool(array &$mem, string $toolName, array $toolResult): void
    {
        if ($toolName === 'getProductInfo') {
            $r = $toolResult['result'] ?? $toolResult;
            if (($r['found'] ?? false)) {
                $mem['last_product_slug'] = $r['slug'] ?? null;
                $mem['last_product_name'] = $r['name'] ?? null;
            }
        } elseif ($toolName === 'resolveProduct') {
            $r = $toolResult['result'] ?? $toolResult;
            if (($r['found'] ?? false)) {
                $mem['last_product_slug'] = $r['slug'] ?? null;
                $mem['last_product_name'] = $r['name'] ?? null;
            }
        } elseif ($toolName === 'getOrderStatus') {
            $r = $toolResult['result'] ?? $toolResult;
            if (($r['found'] ?? false)) {
                $mem['last_order_code'] = $r['code'] ?? null;
            }
        }
    }

    private static function fmtPriceRange($min, $max): string
    {
        if ($min && $max) return number_format($min) . '₫–' . number_format($max) . '₫';
        if ($max) return '≤ ' . number_format($max) . '₫';
        if ($min) return '≥ ' . number_format($min) . '₫';
        return 'chưa rõ';
    }
}
