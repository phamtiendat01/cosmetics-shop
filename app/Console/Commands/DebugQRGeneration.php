<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\ProductQRCode;
use App\Services\Blockchain\ProductCertificateService;
use App\Services\Blockchain\QRCodeService;
use Illuminate\Console\Command;

class DebugQRGeneration extends Command
{
    protected $signature = 'blockchain:debug-qr {order}';
    protected $description = 'Debug QR code generation for an order';

    public function handle(ProductCertificateService $certService, QRCodeService $qrService)
    {
        $orderId = $this->argument('order');

        $order = Order::where('id', $orderId)
            ->orWhere('code', $orderId)
            ->with('items.variant.product.brand')
            ->first();

        if (!$order) {
            $this->error('❌ Order not found');
            return 1;
        }

        $this->info("📦 Order: {$order->code} (ID: {$order->id})");
        $this->info("Status: {$order->status}");
        $this->newLine();

        $items = $order->items;
        $this->info("Items count: {$items->count()}");
        $this->newLine();

        $totalQRCreated = 0;

        foreach ($items as $item) {
            $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->line("Item ID: {$item->id}");
            $this->line("  Variant ID: " . ($item->product_variant_id ?? 'NULL'));
            $this->line("  Qty: {$item->qty}");

            if (!$item->product_variant_id) {
                $this->error("  ❌ No variant_id!");
                continue;
            }

            if (!$item->variant) {
                $this->error("  ❌ Variant not found!");
                continue;
            }

            $variant = $item->variant;
            $this->info("  ✅ Variant: {$variant->sku}");

            // Check certificate
            $cert = $variant->blockchainCertificate;
            if (!$cert) {
                $this->warn("  ⚠️  No certificate, trying to mint...");
                $cert = $certService->mintCertificate($variant);
                if (!$cert) {
                    $this->error("  ❌ Failed to mint certificate!");
                    continue;
                }
                $this->info("  ✅ Certificate minted!");
            } else {
                $this->info("  ✅ Certificate exists: " . substr($cert->certificate_hash, 0, 20) . '...');
            }

            // Try to generate QR
            $this->line("  Generating QR codes...");
            try {
                for ($i = 0; $i < $item->qty; $i++) {
                    $qrData = $qrService->generateForCertificate(
                        $cert->certificate_hash,
                        $item->id
                    );

                    $qr = ProductQRCode::create([
                        'product_variant_id' => $variant->id,
                        'certificate_id' => $cert->id,
                        'order_item_id' => $item->id,
                        'qr_code' => $qrData['qr_code'],
                        'qr_image_path' => $qrData['qr_image_path'],
                        'qr_image_url' => $qrData['qr_image_url'],
                    ]);

                    $this->info("  ✅ QR code created: {$qr->qr_code}");
                    $totalQRCreated++;
                }
            } catch (\Exception $e) {
                $this->error("  ❌ Error: {$e->getMessage()}");
                $this->line("  File: {$e->getFile()}:{$e->getLine()}");
            }
        }

        $this->newLine();
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("✅ Total QR codes created: {$totalQRCreated}");

        return 0;
    }
}
