<?php

namespace App\Console\Commands;

use App\Services\Bot\BotAgent;
use App\Services\Bot\ToolExecutor;
use App\Services\Bot\IntentClassifier;
use App\Services\Bot\ResponseGenerator;
use Illuminate\Console\Command;

class TestBotFull extends Command
{
    protected $signature = 'bot:test-full {message?}';
    protected $description = 'Test bot với debug chi tiết';

    public function handle()
    {
        $message = $this->argument('message') ?: 'tìm serum';
        
        $this->info("=== Testing bot với message: {$message} ===");
        
        try {
            // 1. Test Intent
            $intentClassifier = app(IntentClassifier::class);
            $intentResult = $intentClassifier->classify($message, []);
            $this->line("1. Intent: {$intentResult['intent']} (confidence: {$intentResult['confidence']})");
            
            // 2. Test Tools
            if ($intentResult['intent'] === 'product_search') {
                $toolExecutor = app(ToolExecutor::class);
                $toolsResult = $toolExecutor->execute($intentResult['intent'], $message, ['entities' => []]);
                $this->line("2. Tools executed: " . count($toolsResult));
                foreach ($toolsResult as $toolName => $result) {
                    $count = is_array($result) ? count($result) : 0;
                    $this->line("   - {$toolName}: {$count} results");
                    if (is_array($result) && isset($result[0])) {
                        $this->line("     First item keys: " . implode(', ', array_keys($result[0] ?? [])));
                    }
                }
                
                // 3. Test ResponseGenerator extractProducts
                $responseGen = app(ResponseGenerator::class);
                $reflection = new \ReflectionClass($responseGen);
                $method = $reflection->getMethod('extractProducts');
                $method->setAccessible(true);
                $products = $method->invoke($responseGen, $toolsResult);
                $this->line("3. Extracted products: " . count($products));
                if (!empty($products)) {
                    foreach ($products as $i => $p) {
                        $this->line("   - Product {$i}: {$p['name']} ({$p['price_min']}₫)");
                    }
                }
            }
            
            // 4. Test full BotAgent
            $this->line("\n4. Full BotAgent test:");
            $botAgent = app(BotAgent::class);
            $result = $botAgent->process($message, 'test-session-' . time());
            
            $this->info("✅ Success!");
            $this->line("Reply: " . substr($result['reply'] ?? 'N/A', 0, 100) . "...");
            $this->line("Products: " . count($result['products'] ?? []));
            $this->line("Suggestions: " . count($result['suggestions'] ?? []));
            
            if (!empty($result['products'])) {
                $this->line("\nProducts found:");
                foreach ($result['products'] as $p) {
                    $this->line("  - {$p['name']} ({$p['price_min']}₫)");
                }
            } else {
                $this->warn("⚠️  Không có products trong response!");
            }
            
        } catch (\Throwable $e) {
            $this->error("❌ Error: " . $e->getMessage());
            $this->error("File: " . $e->getFile() . ":" . $e->getLine());
            return 1;
        }
        
        return 0;
    }
}

