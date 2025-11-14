<?php

namespace App\Services\Blockchain;

use App\Models\ProductBlockchainCertificate;
use App\Models\ProductChainMovement;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductCertificateService
{
    public function __construct(
        private BlockchainService $blockchainService
    ) {}

    /**
     * Mint certificate cho product variant
     */
    public function mintCertificate(ProductVariant $variant): ?ProductBlockchainCertificate
    {
        // Kiểm tra đã có certificate chưa
        $existing = ProductBlockchainCertificate::where('product_variant_id', $variant->id)->first();
        if ($existing) {
            return $existing;
        }

        try {
            DB::beginTransaction();

            // Prepare metadata
            $metadata = [
                'product_id' => $variant->product_id,
                'product_name' => $variant->product->name ?? 'Unknown',
                'variant_id' => $variant->id,
                'sku' => $variant->sku,
                'brand' => $variant->product->brand->name ?? 'Unknown',
                'batch_number' => $this->generateBatchNumber($variant),
                'lot_number' => 'LOT-' . now()->format('Ymd') . '-' . str_pad($variant->id, 6, '0', STR_PAD_LEFT),
                'manufacturing_date' => now()->toDateString(),
                'created_at' => now()->toIso8601String(),
            ];

            // Upload to IPFS
            $ipfsResult = $this->blockchainService->uploadToIPFS($metadata);

            if (!$ipfsResult) {
                throw new \Exception('Failed to upload to IPFS');
            }

            // Generate certificate hash
            $certificateHash = $this->blockchainService->generateCertificateHash($metadata);

            // Create certificate
            $certificate = ProductBlockchainCertificate::create([
                'product_variant_id' => $variant->id,
                'certificate_hash' => $certificateHash,
                'ipfs_hash' => $ipfsResult['ipfs_hash'],
                'ipfs_url' => $ipfsResult['ipfs_url'],
                'metadata' => $metadata,
                'minted_at' => now(),
            ]);

            // Record initial movement (manufacture)
            $this->recordMovement(
                $variant,
                $certificate,
                'manufacture',
                null,
                'Manufacturer',
                null,
                null,
                $metadata['batch_number']
            );

            DB::commit();

            Log::info('Certificate minted successfully', [
                'variant_id' => $variant->id,
                'certificate_hash' => $certificateHash,
            ]);

            return $certificate;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to mint certificate', [
                'variant_id' => $variant->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Record movement trong supply chain
     */
    public function recordMovement(
        ProductVariant $variant,
        ?ProductBlockchainCertificate $certificate,
        string $movementType,
        ?string $fromLocation,
        ?string $toLocation,
        ?int $orderId = null,
        ?int $orderItemId = null,
        ?string $batchNumber = null,
        int $quantity = 1
    ): ProductChainMovement {
        return ProductChainMovement::create([
            'product_variant_id' => $variant->id,
            'certificate_id' => $certificate?->id,
            'movement_type' => $movementType,
            'from_location' => $fromLocation,
            'to_location' => $toLocation,
            'order_id' => $orderId,
            'order_item_id' => $orderItemId,
            'batch_number' => $batchNumber,
            'quantity' => $quantity,
            'moved_at' => now(),
        ]);
    }

    /**
     * Generate batch number
     */
    private function generateBatchNumber(ProductVariant $variant): string
    {
        $sku = strtoupper(str_replace([' ', '-'], '', $variant->sku));
        $date = now()->format('Ymd');
        $id = str_pad($variant->id, 4, '0', STR_PAD_LEFT);
        return $sku . '-' . $date . '-' . $id;
    }
}
