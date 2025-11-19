<?php

namespace App\Contracts\Bot;

/**
 * Bot Service Interface
 * Định nghĩa contract cho Bot Service
 */
interface BotServiceInterface
{
    /**
     * Xử lý tin nhắn từ user
     *
     * @param string $message
     * @param string|null $sessionId
     * @param int|null $userId
     * @return array {reply, products, suggestions, intent, tools_used}
     */
    public function process(string $message, ?string $sessionId = null, ?int $userId = null): array;

    /**
     * Reset conversation
     *
     * @param string|null $sessionId
     * @param int|null $userId
     * @return void
     */
    public function reset(?string $sessionId = null, ?int $userId = null): void;
}

