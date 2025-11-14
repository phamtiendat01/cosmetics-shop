<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryAdjustment;
use App\Models\ProductVariant;
use App\Services\Blockchain\ChainMovementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function __construct(
        private ChainMovementService $chainMovementService
    ) {}

    public function adjust(Request $req, ProductVariant $variant)
    {
        $data = $req->validate([
            'mode'   => 'required|in:delta,set',
            'delta'  => 'nullable|integer',
            'qty'    => 'nullable|integer|min:0',
            'reason' => 'nullable|string|max:100',
            'note'   => 'nullable|string|max:2000',
        ]);

        DB::transaction(function () use ($variant, $data, $req) {
            // khóa hàng tồn của biến thể để tránh race condition
            $inv = $variant->inventory()->lockForUpdate()->firstOrCreate([
                'product_variant_id' => $variant->id,
            ], [
                'qty_in_stock' => 0,
                'low_stock_threshold' => 0,
            ]);

            // tính chênh lệch thực tế
            $delta = 0;
            if ($data['mode'] === 'delta') {
                $delta = (int)($data['delta'] ?? 0);
            } else { // set
                $target = (int)($data['qty'] ?? 0);
                $delta  = $target - (int)$inv->qty_in_stock;
            }
            if ($delta === 0) return;

            // cập nhật tồn kho: không để âm
            DB::table('inventories')
                ->where('product_variant_id', $variant->id)
                ->update([
                    'qty_in_stock' => DB::raw('GREATEST(qty_in_stock + (' . ($delta) . '), 0)'),
                    'updated_at'   => now(),
                ]);

            // ghi log
            InventoryAdjustment::create([
                'product_variant_id' => $variant->id,
                'user_id'            => optional($req->user())->id,
                'delta'              => $delta,
                'reason'             => $data['reason'] ?? null,
                'note'               => $data['note'] ?? null,
            ]);

            // Record blockchain movement nếu có certificate
            $certificate = $variant->blockchainCertificate;
            if ($certificate && $delta > 0) {
                // Nhập kho
                $this->chainMovementService->recordWarehouseIn(
                    $variant,
                    $certificate,
                    $data['warehouse_location'] ?? 'Main Warehouse',
                    $certificate->metadata['batch_number'] ?? null,
                    abs($delta)
                );
            } elseif ($certificate && $delta < 0) {
                // Xuất kho (hoặc điều chỉnh giảm)
                $this->chainMovementService->recordWarehouseOut(
                    $variant,
                    $certificate,
                    $data['warehouse_location'] ?? 'Main Warehouse',
                    $data['destination'] ?? 'Adjustment',
                    $certificate->metadata['batch_number'] ?? null,
                    abs($delta)
                );
            }
        });

        return back()->with('ok', 'Đã điều chỉnh tồn kho.');
    }
}
