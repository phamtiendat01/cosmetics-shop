<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WalletController extends Controller
{
    public function show(Request $req)
    {
        $user = $req->user();

        /** @var Wallet $wallet */
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0]
        );

        $type = $req->query('type');
        if (!in_array($type, ['credit', 'debit'], true)) {
            $type = null; // all
        }

        $base = DB::table('wallet_transactions as wt')
            ->where('wt.wallet_id', $wallet->id)
            ->when($type, fn($q) => $q->where('wt.type', $type))

            // A) tiền hoàn: order_return -> orders
            ->leftJoin('order_returns as r', function ($j) {
                $j->on('r.id', '=', 'wt.ext_id')
                    ->where('wt.ext_type', '=', 'order_return');
            })
            ->leftJoin('orders as o_ret', 'o_ret.id', '=', 'r.order_id')

            // B) trừ ví chuẩn: ext_type = 'order'
            ->leftJoin('orders as o_ord', function ($j) {
                $j->on('o_ord.id', '=', 'wt.ext_id')
                    ->where('wt.ext_type', '=', 'order');
            })

            // C) fallback: một số bản ghi cũ có ext_type NULL/khác,
            // nhưng ext_id vẫn là orders.id -> join "any" không ràng buộc ext_type
            ->leftJoin('orders as o_any', 'o_any.id', '=', 'wt.ext_id')

            ->select([
                'wt.id',
                'wt.wallet_id',
                'wt.type',
                'wt.amount',
                'wt.balance_after',
                'wt.ext_type',
                'wt.ext_id',
                'wt.created_at',
                'wt.updated_at',

                // Tham chiếu hiển thị
                DB::raw("
                    CASE
                        WHEN wt.ext_type = 'order_return'
                            THEN CONCAT('Hoàn đơn ', o_ret.code)
                        WHEN wt.ext_type = 'order'
                            THEN CONCAT('Thanh toán đơn ', o_ord.code)
                        WHEN wt.type = 'debit' AND o_any.id IS NOT NULL
                            THEN CONCAT('Thanh toán đơn ', o_any.code)
                        ELSE NULL
                    END AS ref_title
                "),
                DB::raw("
                    CASE
                        WHEN wt.ext_type = 'order_return'
                            THEN o_ret.code
                        WHEN wt.ext_type = 'order'
                            THEN o_ord.code
                        WHEN wt.type = 'debit' AND o_any.id IS NOT NULL
                            THEN o_any.code
                        ELSE NULL
                    END AS ref_code
                "),
            ])
            ->orderByDesc('wt.id');

        $transactions = $base->paginate(10)->withQueryString()
            ->through(function ($row) {
                $row->created_at = $row->created_at ? Carbon::parse($row->created_at) : null;
                return $row;
            });

        $counts = [
            'all'    => DB::table('wallet_transactions')->where('wallet_id', $wallet->id)->count(),
            'credit' => DB::table('wallet_transactions')->where('wallet_id', $wallet->id)->where('type', 'credit')->count(),
            'debit'  => DB::table('wallet_transactions')->where('wallet_id', $wallet->id)->where('type', 'debit')->count(),
        ];

        return view('account.wallet.show', [
            'wallet'       => $wallet,
            'transactions' => $transactions,
            'type'         => $type,
            'counts'       => $counts,
        ]);
    }
}
