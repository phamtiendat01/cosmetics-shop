<?php

namespace App\Console\Commands;

use App\Services\Bot\BotAgent;
use Illuminate\Console\Command;

class TestBotComplete extends Command
{
    protected $signature = 'bot:test-complete {message?}';
    protected $description = 'Test bot hoàn chỉnh với tất cả thông tin';

    public function handle()
    {
        $message = $this->argument('message') ?: 'serum cho da dầu';
        
        $this->info("=== TEST BOT HOÀN CHỈNH ===");
        $this->line("Message: {$message}");
        $this->line("");
        
        try {
            $botAgent = app(BotAgent::class);
            $result = $botAgent->process($message, 'test-session-' . time());
            
            $this->info("✅ Response:");
            $this->line("Reply: " . strip_tags($result['reply'] ?? 'N/A'));
            $this->line("");
            
            $this->info("Products: " . count($result['products'] ?? []));
            if (!empty($result['products'])) {
                foreach ($result['products'] as $i => $p) {
                    $this->line("  " . ($i + 1) . ". {$p['name']} - " . number_format($p['price_min']) . "₫");
                }
            } else {
                $this->warn("⚠️  Không có products!");
            }
            $this->line("");
            
            $this->info("Suggestions: " . count($result['suggestions'] ?? []));
            foreach ($result['suggestions'] ?? [] as $s) {
                $this->line("  - {$s}");
            }
            
        } catch (\Throwable $e) {
            $this->error("❌ Error: " . $e->getMessage());
            $this->error("File: " . $e->getFile() . ":" . $e->getLine());
            return 1;
        }
        
        return 0;
    }
}

