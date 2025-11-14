<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\ProductQRCode;
use App\Services\Blockchain\VerificationService;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class TestBlockchainFlow extends Command
{
    protected $signature = 'blockchain:test-flow {--order= : Order ID to test}';
    protected $description = 'Test complete blockchain flow (certificate -> QR -> verification)';

    public function handle(VerificationService $verificationService)
    {
        $orderId = $this->option('order');

        if (!$orderId) {
            // Tìm order completed gần nhất có QR codes
            $order = Order::whereIn('status', ['completed', 'delivered'])
                ->whereHas('items', function ($q) {
                    $q->whereHas('variant', function ($v) {
                        $v->whereHas('qrCodes');
                    });
                })
                ->latest()
                ->first();

            if (!$order) {
                $this->error('❌ No completed orders with QR codes found.');
                $this->line('Please complete an order first or specify --order=ID');
                return 1;
            }

            $orderId = $order->id;
            $this->info("📦 Using order: {$orderId} ({$order->code})");
        } else {
            $order = Order::find($orderId);
            if (!$order) {
                $this->error("❌ Order {$orderId} not found");
                return 1;
            }
        }

        $this->newLine();
        $this->info('🔍 Testing Blockchain Flow...');
        $this->newLine();

        // 1. Check certificates
        $this->info('1️⃣ Checking Certificates...');
        $certificatesCount = 0;
        foreach ($order->items as $item) {
            if ($item->variant && $item->variant->blockchainCertificate) {
                $certificatesCount++;
                $cert = $item->variant->blockchainCertificate;
                $this->line("   ✅ Variant {$item->variant->id}: Certificate exists");
                $this->line("      Hash: " . substr($cert->certificate_hash, 0, 20) . '...');
            } else {
                $this->line("   ⚠️  Variant {$item->variant_id}: No certificate");
            }
        }

        // 2. Check QR codes
        $this->newLine();
        $this->info('2️⃣ Checking QR Codes...');
        $qrCodes = ProductQRCode::whereHas('orderItem', function ($q) use ($orderId) {
            $q->where('order_id', $orderId);
        })->get();

        if ($qrCodes->isEmpty()) {
            $this->error('   ❌ No QR codes found for this order');
            $this->line('   💡 Make sure order status is "completed" and event was fired');
            return 1;
        }

        $this->info("   ✅ Found {$qrCodes->count()} QR code(s)");
        foreach ($qrCodes as $qr) {
            $this->line("      QR: {$qr->qr_code}");
        }

        // 3. Test verification
        $this->newLine();
        $this->info('3️⃣ Testing Verification...');

        $testQr = $qrCodes->first();
        $this->line("   Testing QR: {$testQr->qr_code}");

        $request = Request::create('/verify', 'POST', [
            'qr_code' => $testQr->qr_code
        ]);

        $result = $verificationService->verify($testQr->qr_code, $request);

        if ($result['authentic']) {
            $this->info("   ✅ Verification successful!");
            $this->line("      Result: {$result['message']}");
            if (isset($result['certificate'])) {
                $productName = $result['certificate']['metadata']['product_name'] ?? 'N/A';
                $this->line("      Product: {$productName}");
            }
            if (isset($result['history'])) {
                $this->line("      History: " . count($result['history']) . " movement(s)");
            }
        } else {
            $this->error("   ❌ Verification failed!");
            $this->line("      Result: {$result['message']}");
        }

        $this->newLine();
        $this->info('✅ Test completed!');

        return 0;
    }
}
