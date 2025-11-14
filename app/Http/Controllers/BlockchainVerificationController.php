<?php

namespace App\Http\Controllers;

use App\Services\Blockchain\VerificationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlockchainVerificationController extends Controller
{
    public function __construct(
        private VerificationService $verificationService
    ) {}

    /**
     * Show verification page
     */
    public function show(): View
    {
        return view('blockchain.verify');
    }

    /**
     * Verify QR code
     */
    public function verify(Request $request, ?string $qrCode = null)
    {
        // Nếu có QR code từ URL parameter
        if ($qrCode) {
            $request->merge(['qr_code' => $qrCode]);
        }

        $request->validate([
            'qr_code' => 'required|string|max:255',
        ]);

        $qrCode = trim($request->input('qr_code'));

        $result = $this->verificationService->verify($qrCode, $request);

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return view('blockchain.verify', [
            'result' => $result,
            'qr_code' => $qrCode,
        ]);
    }

    /**
     * Verify by certificate hash
     */
    public function verifyByHash(string $hash)
    {
        $certificate = \App\Models\ProductBlockchainCertificate::where('certificate_hash', $hash)->first();

        if (!$certificate) {
            return view('blockchain.verify', [
                'result' => [
                    'success' => false,
                    'message' => 'Certificate không tồn tại',
                    'verification_result' => 'not_found',
                ],
            ]);
        }

        // Tạo QR code giả để verify
        $qrCode = $certificate->certificate_hash;
        $result = $this->verificationService->verify($qrCode, request());

        return view('blockchain.verify', [
            'result' => $result,
            'qr_code' => $qrCode,
        ]);
    }
}
