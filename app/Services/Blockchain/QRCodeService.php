<?php

namespace App\Services\Blockchain;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QRCodeService
{
    /**
     * Generate QR code for certificate
     */
    public function generateForCertificate(string $certificateHash, int $orderItemId): array
    {
        // Generate unique QR string
        $qrString = strtoupper($certificateHash) . '-' . $orderItemId . '-' . Str::random(6);

        // Generate QR code image using Builder
        $builder = new Builder(
            writer: new PngWriter(),
            data: $qrString,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: config('blockchain.qr_code.size', 300),
            margin: config('blockchain.qr_code.margin', 10),
        );

        $result = $builder->build();

        // Save to storage
        $filename = 'qr_' . Str::random(10) . '.png';
        $storagePath = config('blockchain.qr_code.storage', 'public/qr_codes');
        $fullPath = $storagePath . '/' . $filename;

        Storage::put($fullPath, $result->getString());

        return [
            'qr_code' => $qrString,
            'qr_image_path' => $fullPath,
            'qr_image_url' => Storage::url($fullPath),
        ];
    }

    /**
     * Decode QR string
     */
    public function decodeQRString(string $qrString): ?array
    {
        $parts = explode('-', $qrString);
        if (count($parts) < 2) {
            return null;
        }

        return [
            'certificate_hash' => strtolower($parts[0]),
            'order_item_id' => (int) $parts[1],
            'random' => $parts[2] ?? null,
        ];
    }
}
