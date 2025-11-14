<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductBlockchainCertificate;
use App\Models\ProductQRCode;
use App\Models\ProductVerificationLog;
use Illuminate\Http\Request;

class BlockchainController extends Controller
{
    public function certificates(Request $request)
    {
        $query = ProductBlockchainCertificate::with('productVariant.product.brand')
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('certificate_hash', 'like', "%{$search}%")
                  ->orWhereHas('productVariant', function($v) use ($search) {
                      $v->where('sku', 'like', "%{$search}%")
                        ->orWhereHas('product', function($p) use ($search) {
                            $p->where('name', 'like', "%{$search}%");
                        });
                  });
            });
        }

        $certificates = $query->paginate(20)->withQueryString();

        return view('admin.blockchain.certificates', [
            'certificates' => $certificates,
            'search' => $request->search,
        ]);
    }

    public function qrCodes(Request $request)
    {
        $query = ProductQRCode::with([
            'productVariant.product.brand',
            'orderItem.order',
            'certificate'
        ])
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('qr_code', 'like', "%{$search}%")
                  ->orWhereHas('orderItem.order', function($o) use ($search) {
                      $o->where('code', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('verified')) {
            $query->where('is_verified', $request->verified === '1');
        }

        if ($request->filled('flagged')) {
            $query->where('is_flagged', $request->flagged === '1');
        }

        $qrCodes = $query->paginate(20)->withQueryString();

        return view('admin.blockchain.qr-codes', [
            'qrCodes' => $qrCodes,
            'filters' => $request->only('search', 'verified', 'flagged'),
        ]);
    }

    public function verifications(Request $request)
    {
        $query = ProductVerificationLog::with([
            'qrCode.productVariant.product.brand',
            'qrCode.certificate'
        ])
            ->latest();

        if ($request->filled('result')) {
            $query->where('verification_result', $request->result);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('qr_code', 'like', "%{$search}%")
                  ->orWhere('verifier_ip', 'like', "%{$search}%");
            });
        }

        $verifications = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => ProductVerificationLog::count(),
            'authentic' => ProductVerificationLog::where('verification_result', 'authentic')->count(),
            'fake' => ProductVerificationLog::where('verification_result', 'fake')->count(),
            'suspicious' => ProductVerificationLog::where('verification_result', 'suspicious')->count(),
        ];

        return view('admin.blockchain.verifications', [
            'verifications' => $verifications,
            'stats' => $stats,
            'filters' => $request->only('result', 'search'),
        ]);
    }

    public function statistics()
    {
        // Overall stats
        $totalCertificates = \App\Models\ProductBlockchainCertificate::count();
        $totalQRCodes = \App\Models\ProductQRCode::count();
        $totalVerifications = \App\Models\ProductVerificationLog::count();

        // Verification stats
        $verificationStats = [
            'authentic' => \App\Models\ProductVerificationLog::where('verification_result', 'authentic')->count(),
            'fake' => \App\Models\ProductVerificationLog::where('verification_result', 'fake')->count(),
            'suspicious' => \App\Models\ProductVerificationLog::where('verification_result', 'suspicious')->count(),
        ];

        // QR Code stats
        $qrStats = [
            'verified' => \App\Models\ProductQRCode::where('is_verified', true)->count(),
            'flagged' => \App\Models\ProductQRCode::where('is_flagged', true)->count(),
            'blocked' => \App\Models\ProductQRCode::where('is_flagged', true)
                ->where('verification_count', '>=', config('blockchain.verification.blocked_threshold', 15))
                ->count(),
        ];

        // Daily verification trend (last 30 days)
        // Lấy dữ liệu từ database
        $verificationData = \App\Models\ProductVerificationLog::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->date => (int)$item->count];
            })
            ->toArray();

        // Tạo array đầy đủ 30 ngày (fill 0 cho những ngày không có dữ liệu)
        $dailyVerifications = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dailyVerifications[$date] = $verificationData[$date] ?? 0;
        }

        // Top verified products
        $topProducts = \App\Models\ProductQRCode::with('productVariant.product')
            ->selectRaw('product_variant_id, COUNT(*) as verify_count')
            ->groupBy('product_variant_id')
            ->orderByDesc('verify_count')
            ->limit(10)
            ->get();

        // Fraud detection stats
        $fraudStats = [
            'suspicious_qr' => \App\Models\ProductQRCode::where('is_flagged', true)
                ->where('verification_count', '<', config('blockchain.verification.blocked_threshold', 15))
                ->count(),
            'blocked_qr' => \App\Models\ProductQRCode::where('is_flagged', true)
                ->where('verification_count', '>=', config('blockchain.verification.blocked_threshold', 15))
                ->count(),
            'high_risk_ips' => \App\Models\ProductVerificationLog::selectRaw('verifier_ip, COUNT(*) as count')
                ->groupBy('verifier_ip')
                ->having('count', '>', 10)
                ->count(),
        ];

        return view('admin.blockchain.statistics', [
            'totalCertificates' => $totalCertificates,
            'totalQRCodes' => $totalQRCodes,
            'totalVerifications' => $totalVerifications,
            'verificationStats' => $verificationStats,
            'qrStats' => $qrStats,
            'dailyVerifications' => $dailyVerifications,
            'topProducts' => $topProducts,
            'fraudStats' => $fraudStats,
        ]);
    }
}

