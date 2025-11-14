<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductBlockchainCertificate;
use App\Models\ProductVariant;
use App\Services\Blockchain\ChainMovementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BlockchainRecallController extends Controller
{
    public function __construct(
        private ChainMovementService $chainMovementService
    ) {}

    /**
     * Show recall form
     */
    public function create(Request $request)
    {
        $variantId = $request->get('variant_id');
        $variant = $variantId ? ProductVariant::with('product')->find($variantId) : null;

        return view('admin.blockchain.recall.create', [
            'variant' => $variant,
            'variants' => ProductVariant::with('product.brand')
                ->whereHas('blockchainCertificate')
                ->latest()
                ->limit(100)
                ->get(),
        ]);
    }

    /**
     * Process recall
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'product_variant_id' => 'required|exists:product_variants,id',
            'batch_number' => 'nullable|string|max:255',
            'reason' => 'required|string|max:500',
            'quantity' => 'nullable|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $variant = ProductVariant::findOrFail($data['product_variant_id']);
            $certificate = $variant->blockchainCertificate;

            if (!$certificate) {
                return back()->withErrors('Sản phẩm này chưa có certificate.');
            }

            // Record recall movement
            $this->chainMovementService->recordRecall(
                $variant,
                $certificate,
                $data['reason'],
                $data['batch_number'] ?? $certificate->metadata['batch_number'] ?? null,
                $data['quantity'] ?? 1
            );

            DB::commit();

            return redirect()->route('admin.blockchain.certificates')
                ->with('ok', 'Đã ghi nhận thu hồi sản phẩm thành công.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Lỗi: ' . $e->getMessage())->withInput();
        }
    }
}

