<?php

namespace App\Listeners;

use App\Events\OrderCompleted;
use App\Events\OrderConfirmed;
use App\Models\OrderItem;
use App\Services\Blockchain\ProductCertificateService;
use App\Services\Blockchain\QRCodeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateProductQRCodes
{
    public function __construct(
        private ProductCertificateService $certificateService,
        private QRCodeService $qrCodeService
    ) {}

    /**
     * Handle the event.
     * Generate QR codes khi order được confirmed/processing (TRƯỚC khi đóng gói)
     */
    public function handle(OrderCompleted|OrderConfirmed $event): void
    {
        $order = $event->order;

        try {
            DB::beginTransaction();

            // Kiểm tra xem QR codes đã được generate chưa (tránh duplicate)
            $existingQRCodes = \App\Models\ProductQRCode::whereHas('orderItem', function($q) use ($order) {
                $q->where('order_id', $order->id);
            })->count();

            if ($existingQRCodes > 0) {
                Log::info('GenerateProductQRCodes: QR codes already exist for this order', [
                    'order_id' => $order->id,
                    'existing_count' => $existingQRCodes,
                ]);
                DB::rollBack();
                return;
            }

            // Lấy tất cả order items
            $orderItems = $order->items()->with('variant.product.brand')->get();

            Log::info('GenerateProductQRCodes: Processing order', [
                'order_id' => $order->id,
                'items_count' => $orderItems->count(),
            ]);

            if ($orderItems->isEmpty()) {
                Log::warning('GenerateProductQRCodes: No items found', [
                    'order_id' => $order->id,
                ]);
                DB::rollBack();
                return;
            }

            $qrCodesCreated = 0;

            foreach ($orderItems as $orderItem) {
                // Kiểm tra có variant không
                if (!$orderItem->product_variant_id) {
                    Log::warning('GenerateProductQRCodes: OrderItem has no variant_id', [
                        'order_item_id' => $orderItem->id,
                        'order_id' => $order->id,
                    ]);
                    continue;
                }

                if (!$orderItem->variant) {
                    Log::warning('GenerateProductQRCodes: Variant not found', [
                        'order_item_id' => $orderItem->id,
                        'variant_id' => $orderItem->product_variant_id,
                        'order_id' => $order->id,
                    ]);
                    continue;
                }

                $variant = $orderItem->variant;

                // Mint certificate nếu chưa có
                $certificate = $this->certificateService->mintCertificate($variant);

                if (!$certificate) {
                    Log::warning('Failed to mint certificate', [
                        'variant_id' => $variant->id,
                        'order_id' => $order->id,
                    ]);
                    continue;
                }

                // Generate QR code cho mỗi item (mỗi qty = 1 QR code)
                for ($i = 0; $i < $orderItem->qty; $i++) {
                    try {
                        $qrData = $this->qrCodeService->generateForCertificate(
                            $certificate->certificate_hash,
                            $orderItem->id
                        );

                        // Lưu QR code
                        \App\Models\ProductQRCode::create([
                            'product_variant_id' => $variant->id,
                            'certificate_id' => $certificate->id,
                            'order_item_id' => $orderItem->id,
                            'qr_code' => $qrData['qr_code'],
                            'qr_image_path' => $qrData['qr_image_path'],
                            'qr_image_url' => $qrData['qr_image_url'],
                        ]);

                        $qrCodesCreated++;
                    } catch (\Exception $e) {
                        Log::error('Failed to create QR code', [
                            'order_item_id' => $orderItem->id,
                            'variant_id' => $variant->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Record sale movement (chỉ tạo 1 lần cho mỗi order_item)
                try {
                    // Kiểm tra xem đã có movement "sale" cho order_item này chưa (tránh duplicate)
                    $existingMovement = \App\Models\ProductChainMovement::where('certificate_id', $certificate->id)
                        ->where('movement_type', 'sale')
                        ->where('order_item_id', $orderItem->id)
                        ->first();

                    if (!$existingMovement) {
                        $this->certificateService->recordMovement(
                            $variant,
                            $certificate,
                            'sale',
                            'Warehouse',
                            'Customer',
                            $order->id,
                            $orderItem->id,
                            $certificate->metadata['batch_number'] ?? null,
                            $orderItem->qty
                        );
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to record movement', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            Log::info('QR codes generated successfully', [
                'order_id' => $order->id,
                'items_count' => $orderItems->count(),
                'qr_codes_created' => $qrCodesCreated,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to generate QR codes', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
