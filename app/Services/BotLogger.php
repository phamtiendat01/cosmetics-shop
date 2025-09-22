<?php
// app/Services/BotLogger.php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class BotLogger
{
    /** Gọi ở đầu request để lấy mốc thời gian */
    public static function start(): float
    {
        return microtime(true);
    }

    /**
     * Lưu 1 bản ghi log cho mỗi câu trả lời của bot
     * $ctx gợi ý key: t0, handled_by, intent, matched_slug, product_count, ok
     */
    public static function save(string $message, string $reply, array $ctx = []): void
    {
        $lat = 0;
        if (isset($ctx['t0'])) {
            $lat = (int) round((microtime(true) - (float)$ctx['t0']) * 1000);
        }

        DB::table('bot_logs')->insert([
            'session_id'    => session()->getId(),
            'user_id'       => auth()->id() ?: null,
            'message'       => $message,
            'reply'         => $reply,
            'handled_by'    => $ctx['handled_by'] ?? null,   // vd: fast_stock, fast_product, pick, search, llm, faq, guard, fallback
            'intent'        => $ctx['intent'] ?? null,       // vd: availability, product_info, consult, faq, order, coupon, generic
            'matched_slug'  => $ctx['matched_slug'] ?? null,
            'product_count' => (int)($ctx['product_count'] ?? 0),
            'latency_ms'    => $lat,
            'ok'            => (bool)($ctx['ok'] ?? true),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }
}
