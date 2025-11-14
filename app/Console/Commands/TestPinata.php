<?php

namespace App\Console\Commands;

use App\Services\Blockchain\BlockchainService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestPinata extends Command
{
    protected $signature = 'blockchain:test-pinata';
    protected $description = 'Test Pinata IPFS connection';

    public function handle(BlockchainService $service)
    {
        $this->info('🔍 Testing Pinata IPFS connection...');
        $this->newLine();

        // Kiểm tra config
        $apiKey = config('blockchain.pinata.api_key');
        $secretKey = config('blockchain.pinata.secret_key');

        $this->info('📋 Configuration check:');
        $this->line('IPFS Enabled: ' . (config('blockchain.ipfs_enabled') ? '✅ Yes' : '❌ No'));
        $this->line('API Key: ' . ($apiKey ? '✅ Set (' . substr($apiKey, 0, 10) . '...)' : '❌ Not set'));
        $this->line('Secret Key: ' . ($secretKey ? '✅ Set (' . substr($secretKey, 0, 10) . '...)' : '❌ Not set'));
        $this->newLine();

        // Test data
        $testData = [
            'test' => 'CosmeChain',
            'timestamp' => now()->toIso8601String(),
            'message' => 'This is a test upload from CosmeChain',
            'project' => 'Blockchain Product Authenticity System',
        ];

        $this->info('📤 Uploading test data to IPFS...');
        $result = $service->uploadToIPFS($testData);

        if ($result) {
            $this->info('✅ SUCCESS!');
            $this->newLine();
            $this->info('IPFS Hash: ' . $result['ipfs_hash']);
            $this->info('IPFS URL: ' . $result['ipfs_url']);
            $this->newLine();
            $this->info('🌐 Mở URL này trong browser để xem:');
            $this->line($result['ipfs_url']);
        } else {
            $this->error('❌ FAILED!');
            $this->newLine();
            $this->error('Kiểm tra lại:');
            $this->line('1. PINATA_API_KEY và PINATA_SECRET_KEY trong .env');
            $this->line('2. Đã chạy: php artisan config:clear');
            $this->line('3. Keys có đúng format không (không có khoảng trắng)');
            $this->newLine();
            $this->info('💡 Xem chi tiết lỗi trong: storage/logs/laravel.log');
        }

        return 0;
    }
}
