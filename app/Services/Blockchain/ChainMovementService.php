<?php

namespace App\Services\Blockchain;

use App\Models\ProductVariant;
use App\Models\ProductBlockchainCertificate;
use App\Models\ProductChainMovement;
use Illuminate\Support\Facades\Log;

class ChainMovementService
{
    /**
     * Record warehouse in (nhập kho)
     */
    public function recordWarehouseIn(
        ProductVariant $variant,
        ?ProductBlockchainCertificate $certificate,
        string $warehouseLocation,
        ?string $batchNumber = null,
        int $quantity = 1
    ): ProductChainMovement {
        return $this->recordMovement(
            $variant,
            $certificate,
            'warehouse_in',
            'Supplier',
            $warehouseLocation,
            null,
            null,
            $batchNumber,
            $quantity
        );
    }

    /**
     * Record warehouse out (xuất kho)
     */
    public function recordWarehouseOut(
        ProductVariant $variant,
        ?ProductBlockchainCertificate $certificate,
        string $warehouseLocation,
        string $destination,
        ?string $batchNumber = null,
        int $quantity = 1
    ): ProductChainMovement {
        return $this->recordMovement(
            $variant,
            $certificate,
            'warehouse_out',
            $warehouseLocation,
            $destination,
            null,
            null,
            $batchNumber,
            $quantity
        );
    }

    /**
     * Record sale (bán hàng)
     */
    public function recordSale(
        ProductVariant $variant,
        ?ProductBlockchainCertificate $certificate,
        int $orderId,
        int $orderItemId,
        ?string $batchNumber = null,
        int $quantity = 1
    ): ProductChainMovement {
        return $this->recordMovement(
            $variant,
            $certificate,
            'sale',
            'Warehouse',
            'Customer',
            $orderId,
            $orderItemId,
            $batchNumber,
            $quantity
        );
    }

    /**
     * Record return (trả hàng)
     */
    public function recordReturn(
        ProductVariant $variant,
        ?ProductBlockchainCertificate $certificate,
        int $orderId,
        int $orderItemId,
        string $warehouseLocation,
        ?string $batchNumber = null,
        int $quantity = 1
    ): ProductChainMovement {
        return $this->recordMovement(
            $variant,
            $certificate,
            'return',
            'Customer',
            $warehouseLocation,
            $orderId,
            $orderItemId,
            $batchNumber,
            $quantity
        );
    }

    /**
     * Record recall (thu hồi sản phẩm)
     */
    public function recordRecall(
        ProductVariant $variant,
        ?ProductBlockchainCertificate $certificate,
        string $reason,
        ?string $batchNumber = null,
        int $quantity = 1
    ): ProductChainMovement {
        return $this->recordMovement(
            $variant,
            $certificate,
            'recall',
            'Market',
            'Recall Center',
            null,
            null,
            $batchNumber,
            $quantity,
            ['reason' => $reason]
        );
    }

    /**
     * Record movement trong supply chain
     */
    private function recordMovement(
        ProductVariant $variant,
        ?ProductBlockchainCertificate $certificate,
        string $movementType,
        ?string $fromLocation,
        ?string $toLocation,
        ?int $orderId = null,
        ?int $orderItemId = null,
        ?string $batchNumber = null,
        int $quantity = 1,
        array $metadata = []
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
}

