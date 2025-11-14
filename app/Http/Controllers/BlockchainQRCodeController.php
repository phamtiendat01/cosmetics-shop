<?php

namespace App\Http\Controllers;

use App\Models\ProductQRCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BlockchainQRCodeController extends Controller
{
    /**
     * Download QR code image
     */
    public function download(ProductQRCode $qrCode): BinaryFileResponse
    {
        if (!$qrCode->qr_image_path || !Storage::disk('public')->exists($qrCode->qr_image_path)) {
            abort(404, 'QR code image not found');
        }

        return response()->download(
            Storage::disk('public')->path($qrCode->qr_image_path),
            'qr-code-' . $qrCode->qr_code . '.png',
            ['Content-Type' => 'image/png']
        );
    }

    /**
     * View QR code image
     */
    public function view(ProductQRCode $qrCode)
    {
        if (!$qrCode->qr_image_path || !Storage::disk('public')->exists($qrCode->qr_image_path)) {
            abort(404, 'QR code image not found');
        }

        return response()->file(Storage::disk('public')->path($qrCode->qr_image_path));
    }
}

