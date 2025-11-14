<?php

namespace App\Console\Commands;

use App\Services\Bot\ToolExecutor;
use App\Tools\Bot\ProductSearchTool;
use Illuminate\Console\Command;

class DebugBotProducts extends Command
{
    protected $signature = 'bot:debug-products {message?}';
    protected $description = 'Debug product extraction';

    public function handle()
    {
        $message = $this->argument('message') ?: 'tìm serum';
        
        $this->info("Testing product extraction for: {$message}");
        
        // Test ProductSearchTool directly
        $tool = app(ProductSearchTool::class);
        $result = $tool->execute($message, ['entities' => []]);
        
        $this->line("ProductSearchTool result:");
        $this->line("  Type: " . gettype($result));
        $this->line("  Count: " . (is_array($result) ? count($result) : 'N/A'));
        
        if (is_array($result) && !empty($result)) {
            $this->line("  First item keys: " . implode(', ', array_keys($result[0] ?? [])));
            $this->line("  First item: " . json_encode($result[0] ?? null, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }
        
        // Test ToolExecutor
        $this->line("\nToolExecutor result:");
        $toolExecutor = app(ToolExecutor::class);
        $toolsResult = $toolExecutor->execute('product_search', $message, ['entities' => []]);
        
        $this->line("  Tools executed: " . count($toolsResult));
        foreach ($toolsResult as $toolName => $toolResult) {
            $this->line("  - {$toolName}:");
            $this->line("    Type: " . gettype($toolResult));
            $this->line("    Count: " . (is_array($toolResult) ? count($toolResult) : 'N/A'));
            if (is_array($toolResult) && !empty($toolResult)) {
                if (isset($toolResult[0])) {
                    $this->line("    First item keys: " . implode(', ', array_keys($toolResult[0] ?? [])));
                } else {
                    $this->line("    Keys: " . implode(', ', array_keys($toolResult)));
                }
            }
        }
        
        // Test ResponseGenerator
        $this->line("\nResponseGenerator extraction:");
        $responseGen = app(\App\Services\Bot\ResponseGenerator::class);
        $reflection = new \ReflectionClass($responseGen);
        $method = $reflection->getMethod('extractProducts');
        $method->setAccessible(true);
        $products = $method->invoke($responseGen, $toolsResult);
        
        $this->line("  Extracted products: " . count($products));
        if (!empty($products)) {
            foreach ($products as $i => $p) {
                $this->line("  - Product {$i}: {$p['name']} ({$p['price_min']}₫)");
            }
        }
        
        return 0;
    }
}

