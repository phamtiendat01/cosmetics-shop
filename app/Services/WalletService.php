<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\OrderReturn;
use Illuminate\Support\Facades\DB;

class WalletService
{
    /**
     * CỘNG VÍ idempotent: đảm bảo mỗi (wallet_id, ext_type, ext_id) chỉ có 1 giao dịch.
     * - Nếu đã tồn tại => trả về giao dịch cũ, không cộng lại.
     * - Nếu chưa => tạo credit + cập nhật wallets.balance (transaction + lock).
     *
     * YÊU CẦU DB:
     *   UNIQUE (wallet_id, ext_type, ext_id)  => uq_wallet_ext
     *
     * @return array{created:bool, tx:\Illuminate\Database\Eloquent\Model}
     */
    public static function creditOnce(
        Wallet $wallet,
        int $amount,
        string $extType,
        int $extId
    ): array {
        return DB::transaction(function () use ($wallet, $amount, $extType, $extId) {
            // Khóa theo (wallet_id, ext_type, ext_id) để chống race
            $existing = $wallet->transactions()
                ->where('ext_type', $extType)
                ->where('ext_id', $extId)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return ['created' => false, 'tx' => $existing];
            }

            // Khóa dòng ví & tính số dư sau
            /** @var Wallet $w */
            $w = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            $add   = max(0, (int) $amount);
            $after = (int) $w->balance + $add;

            // Tạo giao dịch
            $tx = $w->transactions()->create([
                'type'          => 'credit',
                'amount'        => $add,
                'balance_after' => $after,
                'ext_type'      => $extType,
                'ext_id'        => (int) $extId,
            ]);

            // Cập nhật số dư ví
            $w->update(['balance' => $after]);

            return ['created' => true, 'tx' => $tx];
        });
    }

    /**
     * TRỪ VÍ idempotent: đảm bảo mỗi (wallet_id, ext_type, ext_id) chỉ có 1 giao dịch.
     * - Nếu đã tồn tại => trả về giao dịch cũ (không trừ lần nữa).
     * - Nếu chưa => tạo debit + cập nhật wallets.balance (transaction + lock).
     *
     * @throws \RuntimeException khi số dư không đủ
     *
     * @return array{created:bool, tx:\Illuminate\Database\Eloquent\Model}
     */
    public static function debitOnce(
        Wallet $wallet,
        int $amount,
        string $extType,
        int $extId
    ): array {
        return DB::transaction(function () use ($wallet, $amount, $extType, $extId) {
            $existing = $wallet->transactions()
                ->where('ext_type', $extType)
                ->where('ext_id', $extId)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return ['created' => false, 'tx' => $existing];
            }

            /** @var Wallet $w */
            $w = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            $amt   = max(0, (int) $amount);
            $after = (int) $w->balance - $amt;

            if ($after < 0) {
                throw new \RuntimeException('Insufficient wallet balance');
            }

            $tx = $w->transactions()->create([
                'type'          => 'debit',
                'amount'        => $amt,
                'balance_after' => $after,
                'ext_type'      => $extType,
                'ext_id'        => (int) $extId,
            ]);

            $w->update(['balance' => $after]);

            return ['created' => true, 'tx' => $tx];
        });
    }

    /**
     * Helper: cộng ví theo phiếu trả hàng (luôn gắn tham chiếu).
     */
    public function creditFromOrderReturn(OrderReturn $return)
    {
        $wallet = Wallet::firstOrCreate(
            ['user_id' => (int) ($return->order->user_id ?? 0)],
            ['balance' => 0]
        );

        return static::creditOnce(
            $wallet,
            (int) $return->final_refund,
            'order_return',
            (int) $return->id
        )['tx'];
    }
    public static function debitForOrder(Wallet $wallet, int $amount, int $orderId): array
    {
        return DB::transaction(function () use ($wallet, $amount, $orderId) {
            /** @var Wallet $w */
            $w = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            $deduct = max(0, (int) $amount);
            $deduct = min($deduct, (int) $w->balance);   // không cho âm
            if ($deduct <= 0) {
                return ['created' => false, 'tx' => null];
            }

            $after = (int) $w->balance - $deduct;

            $tx = $w->transactions()->create([
                'type'          => 'debit',
                'amount'        => $deduct,
                'balance_after' => $after,
                'ext_type'      => 'order',
                'ext_id'        => (int) $orderId,
            ]);

            $w->update(['balance' => $after]);

            return ['created' => true, 'tx' => $tx];
        });
    }
}
