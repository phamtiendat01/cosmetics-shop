<?php

namespace App\Services\Blockchain;

use App\Models\ProductQRCode;
use App\Models\ProductVerificationLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerificationService
{
    public function __construct(
        private BlockchainService $blockchainService
    ) {}

    /**
     * Verify QR code
     */
    public function verify(string $qrCode, Request $request): array
    {
        // Tìm QR code
        $qrCodeRecord = ProductQRCode::where('qr_code', $qrCode)->first();

        if (!$qrCodeRecord) {
            $this->logVerification(null, $qrCode, 'fake', $request);
            return [
                'result' => 'fake',
                'authentic' => false,
                'message' => '❌ QR code không tồn tại. Sản phẩm có thể là hàng giả.',
            ];
        }

        // Lấy config thresholds
        $suspiciousThreshold = config('blockchain.verification.suspicious_threshold', 5);
        $blockedThreshold = config('blockchain.verification.blocked_threshold', 15);
        $timeWindow = config('blockchain.verification.time_window_hours', 24);

        // Đếm số lần verify trong khoảng thời gian gần đây
        $recentVerifications = ProductVerificationLog::where('qr_code_id', $qrCodeRecord->id)
            ->where('created_at', '>=', now()->subHours($timeWindow))
            ->count();

        $currentCount = $qrCodeRecord->verification_count;
        $newCount = $currentCount + 1;

        // Check if blocked (khóa hoàn toàn) - >= 15 lần
        if ($currentCount >= $blockedThreshold) {
            if (!$qrCodeRecord->is_flagged) {
                $qrCodeRecord->update([
                    'is_flagged' => true,
                    'flag_reason' => "QR code đã bị khóa do verify quá {$blockedThreshold} lần (có thể bị sao chép)",
                ]);
            }

            $this->logVerification($qrCodeRecord->id, $qrCode, 'fake', $request);
            return [
                'result' => 'fake',
                'authentic' => false,
                'message' => '🚫 QR code đã bị khóa. Đã verify ' . $currentCount . ' lần (giới hạn: ' . $blockedThreshold . ' lần). Sản phẩm có thể là hàng giả.',
            ];
        }

        // Fraud detection: Kiểm tra nếu vượt ngưỡng khả nghi (5 lần trong 24h)
        $isSuspicious = false;
        if ($recentVerifications >= $suspiciousThreshold && $currentCount < $blockedThreshold) {
            // Đánh dấu khả nghi (nhưng vẫn cho verify)
            if (!$qrCodeRecord->is_flagged) {
                $qrCodeRecord->update([
                    'is_flagged' => true,
                    'flag_reason' => "Đã verify {$recentVerifications} lần trong {$timeWindow} giờ (có thể bị copy)",
                ]);
            }
            $isSuspicious = true;
        }

        // Verify certificate
        $certificate = $qrCodeRecord->certificate;
        if (!$certificate) {
            $this->logVerification($qrCodeRecord->id, $qrCode, 'fake', $request);
            return [
                'result' => 'fake',
                'authentic' => false,
                'message' => '❌ Certificate không tồn tại.',
            ];
        }

        // Verify hash
        $isValid = $this->blockchainService->verifyCertificate(
            $certificate->certificate_hash,
            $certificate->metadata
        );

        if (!$isValid) {
            $this->logVerification($qrCodeRecord->id, $qrCode, 'fake', $request);
            return [
                'result' => 'fake',
                'authentic' => false,
                'message' => '❌ Certificate không hợp lệ.',
            ];
        }

        // Update QR code
        $newCount = $qrCodeRecord->verification_count + 1;
        $qrCodeRecord->update([
            'is_verified' => true,
            'verified_at' => now(),
            'verified_by' => $request->ip(),
            'verification_count' => $newCount,
        ]);

        // Get supply chain history
        // Chỉ hiển thị movements liên quan đến QR code này:
        // - Movements không có order_item_id (manufacture, warehouse_in, warehouse_out) → hiển thị cho tất cả
        // - Movements có order_item_id → chỉ hiển thị nếu khớp với order_item_id của QR code này
        $history = $certificate->movements()
            ->where(function ($query) use ($qrCodeRecord) {
                $query->whereNull('order_item_id') // Movements chung (manufacture, warehouse)
                    ->orWhere('order_item_id', $qrCodeRecord->order_item_id); // Movements riêng của QR code này
            })
            ->orderBy('moved_at', 'asc')
            ->get()
            ->map(function ($movement) {
                return [
                    'type' => $movement->movement_type,
                    'from' => $movement->from_location,
                    'to' => $movement->to_location,
                    'date' => $movement->moved_at->format('d/m/Y H:i'),
                    'batch' => $movement->batch_number,
                ];
            });

        // Log verification
        $this->logVerification($qrCodeRecord->id, $qrCode, 'authentic', $request);

        // Refresh để lấy is_flagged mới nhất
        $qrCodeRecord->refresh();

        // Kiểm tra nếu đang ở mức khả nghi để thêm cảnh báo
        $warningMessage = '';
        if ($isSuspicious || ($qrCodeRecord->is_flagged && $newCount < $blockedThreshold)) {
            $remaining = $blockedThreshold - $newCount;
            $warningMessage = " ⚠️ Cảnh báo: QR code này đã được verify {$newCount} lần. Còn {$remaining} lần nữa sẽ bị khóa.";
        }

        return [
            'result' => 'authentic',
            'authentic' => true,
            'message' => '✅ Sản phẩm chính hãng' . $warningMessage,
            'certificate' => [
                'hash' => $certificate->certificate_hash,
                'ipfs_url' => $certificate->ipfs_url,
                'metadata' => $certificate->metadata,
            ],
            'history' => $history,
            'verification_count' => $newCount,
            'is_suspicious' => $isSuspicious || ($qrCodeRecord->is_flagged && $newCount < $blockedThreshold),
            'remaining_verifications' => max(0, $blockedThreshold - $newCount),
        ];
    }

    /**
     * Log verification
     */
    private function logVerification(?int $qrCodeId, string $qrCode, string $result, Request $request): void
    {
        // Advanced fraud detection: Check IP patterns
        $ip = $request->ip();
        $userAgent = $request->userAgent();

        // Check if same IP verified multiple different QR codes (suspicious)
        $ipVerificationCount = ProductVerificationLog::where('verifier_ip', $ip)
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        $metadata = [
            'ip_verification_count_24h' => $ipVerificationCount,
            'device_info' => $this->extractDeviceInfo($userAgent),
        ];

        // Flag if same IP verified too many different QR codes
        if ($ipVerificationCount > 20 && $result === 'authentic') {
            $metadata['fraud_risk'] = 'high';
            $metadata['fraud_reason'] = 'Same IP verified too many different QR codes';
        }

        ProductVerificationLog::create([
            'qr_code_id' => $qrCodeId,
            'qr_code' => $qrCode,
            'verification_result' => $result,
            'verifier_ip' => $ip,
            'user_agent' => $userAgent,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Extract device info from user agent
     */
    private function extractDeviceInfo(string $userAgent): array
    {
        return [
            'raw' => $userAgent,
            'is_mobile' => preg_match('/Mobile|Android|iPhone|iPad/', $userAgent),
            'is_bot' => preg_match('/bot|crawler|spider|crawling/i', $userAgent),
        ];
    }
}
