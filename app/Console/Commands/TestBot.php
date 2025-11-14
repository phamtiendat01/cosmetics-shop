<?php

namespace App\Console\Commands;

use App\Services\Bot\BotAgent;
use Illuminate\Console\Command;

class TestBot extends Command
{
    protected $signature = 'bot:test {message?}';
    protected $description = 'Test bot với message';

    public function handle()
    {
        $message = $this->argument('message') ?: 'sữa rửa mặt cho da dầu';
        
        $this->info("Testing bot with message: {$message}");
        
        try {
            $botAgent = app(BotAgent::class);
            
            // Debug: Test intent classification
            $intentClassifier = app(\App\Services\Bot\IntentClassifier::class);
            $intentResult = $intentClassifier->classify($message, []);
            $this->line("Intent: {$intentResult['intent']} (confidence: {$intentResult['confidence']})");
            
            // Debug: Test tool execution
            if ($intentResult['intent'] === 'product_search') {
                $toolExecutor = app(\App\Services\Bot\ToolExecutor::class);
                $toolsResult = $toolExecutor->execute($intentResult['intent'], $message, ['entities' => []]);
                $this->line("Tools executed: " . count($toolsResult));
                if (!empty($toolsResult)) {
                    foreach ($toolsResult as $toolName => $result) {
                        $count = is_array($result) ? count($result) : 0;
                        $this->line("  - {$toolName}: {$count} results");
                    }
                }
            }
            
            $result = $botAgent->process($message, 'test-session-' . time());
            
            $this->info("✅ Success!");
            $this->line("Reply: " . ($result['reply'] ?? 'N/A'));
            $this->line("Products: " . count($result['products'] ?? []));
            $this->line("Suggestions: " . count($result['suggestions'] ?? []));
            
            if (!empty($result['products'])) {
                $this->line("\nProducts found:");
                foreach ($result['products'] as $p) {
                    $this->line("  - {$p['name']} ({$p['price_min']}₫)");
                }
            }
            
        } catch (\Throwable $e) {
            $this->error("❌ Error: " . $e->getMessage());
            $this->error("File: " . $e->getFile() . ":" . $e->getLine());
            $this->error("Trace: " . substr($e->getTraceAsString(), 0, 500));
            return 1;
        }
        
        return 0;
    }
}

