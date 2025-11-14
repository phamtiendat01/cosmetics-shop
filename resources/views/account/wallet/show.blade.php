{{-- resources/views/account/wallet/show.blade.php --}}
@extends('layouts.app')
@section('title','Ví Cosme')

@section('content')
@php
$fmt = fn($n) => '₫' . number_format((int)$n);

// Badge loại theo ext_type
$extBadge = function($t) {
$type = $t->ext_type ?? null;
$cls = 'bg-slate-50 text-slate-700 border-slate-200';
$txt = 'Khác';
$ico = 'fa-file-lines';

if ($type === 'order_return') {
$cls = 'bg-emerald-50 text-emerald-700 border-emerald-200';
$txt = 'Hoàn từ trả hàng';
$ico = 'fa-rotate-left';
}
return [$cls, $txt, $ico];
};
@endphp

<div class="max-w-6xl mx-auto px-4 py-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-rose-600 to-pink-500 text-white grid place-content-center shadow">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <div>
                <h1 class="text-2xl font-semibold">Ví Cosme</h1>
                <div class="text-sm text-ink/60">
                    Tiền hoàn từ các đơn sẽ tự động cộng vào đây và có thể dùng để thanh toán các lần mua tiếp theo.
                </div>
            </div>
        </div>
        <a href="{{ route('account.orders.index') }}" class="btn btn-outline">
            <i class="fa-solid fa-receipt mr-1"></i> Đơn hàng của tôi
        </a>
    </div>

    {{-- Summary --}}
    <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1">
            <div class="rounded-2xl border border-rose-100 bg-gradient-to-br from-rose-50 to-white p-5 shadow-sm">
                <div class="text-sm text-ink/70 mb-1">Số dư hiện tại</div>
                <div class="text-4xl font-extrabold tracking-tight">{{ $fmt($wallet->balance ?? 0) }}</div>
                <div class="text-xs text-ink/60 mt-2">Số dư sẽ tự trừ khi bạn chọn thanh toán bằng Ví Cosme.</div>
                <div class="mt-3 flex items-center gap-2 text-xs text-ink/50">
                    <i class="fa-solid fa-shield-heart"></i>
                    Giao dịch ví được lưu theo đơn/phiếu hoàn để bảo vệ người dùng.
                </div>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="rounded-2xl border border-rose-100 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold mb-1">Hướng dẫn nhanh</div>
                        <ul class="text-sm list-disc pl-5 space-y-1 text-ink/70">
                            <li>Tiền hoàn chỉ cộng vào ví sau khi yêu cầu trả hàng được duyệt và đơn được đánh dấu <b>Đã hoàn tiền</b>.</li>
                            <li>Ví có thể được chọn ở bước thanh toán để trừ trực tiếp vào tổng tiền.</li>
                            <li>Mục “Tham chiếu” giúp tra cứu lại đơn/yêu cầu tương ứng.</li>
                        </ul>
                    </div>
                    <div class="hidden sm:block">
                        <div class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-rose-700 text-xs">
                            <i class="fa-solid fa-circle-info mr-1"></i>
                            Ví chỉ dùng trong hệ thống Cosme — không phát sinh lãi/lỗ.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Transactions --}}
    <div class="mt-6 rounded-2xl border border-rose-100 bg-white shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b bg-rose-50/60 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="font-semibold text-sm">Lịch sử giao dịch</div>
                <div class="hidden sm:flex items-center gap-1 ml-2">
                    <a href="{{ route('account.wallet.show') }}"
                        class="px-2 py-1 rounded text-xs {{ !$type ? 'bg-white border border-rose-200' : 'hover:bg-white text-ink/70' }}">
                        Tất cả @isset($counts)<span class="ml-1 text-ink/50">({{ $counts['all'] }})</span>@endisset
                    </a>
                    <a href="{{ route('account.wallet.show', ['type' => 'credit']) }}"
                        class="px-2 py-1 rounded text-xs {{ $type==='credit' ? 'bg-white border border-emerald-200' : 'hover:bg-white text-ink/70' }}">
                        Cộng @isset($counts)<span class="ml-1 text-ink/50">({{ $counts['credit'] }})</span>@endisset
                    </a>
                    <a href="{{ route('account.wallet.show', ['type' => 'debit']) }}"
                        class="px-2 py-1 rounded text-xs {{ $type==='debit' ? 'bg-white border border-amber-200' : 'hover:bg-white text-ink/70' }}">
                        Trừ @isset($counts)<span class="ml-1 text-ink/50">({{ $counts['debit'] }})</span>@endisset
                    </a>
                </div>
            </div>
            @if($transactions->total() > 0)
            <div class="text-xs text-ink/50">Tổng {{ $transactions->total() }} giao dịch</div>
            @endif
        </div>

        @if($transactions->count() === 0)
        <div class="p-10 text-center text-ink/60">
            <div class="mx-auto w-16 h-16 rounded-full bg-rose-50 text-rose-600 grid place-content-center mb-3">
                <i class="fa-solid fa-receipt"></i>
            </div>
            Chưa có giao dịch ví.
        </div>
        @else
        <table class="min-w-full text-sm">
            <thead class="bg-rose-50/60 text-ink/70">
                <tr>
                    <th class="px-5 py-3 text-left font-medium">Thời gian</th>
                    <th class="px-5 py-3 text-left font-medium">Loại</th>
                    <th class="px-5 py-3 text-right font-medium">Số tiền</th>
                    <th class="px-5 py-3 text-right font-medium">Số dư sau</th>
                    <th class="px-5 py-3 text-left font-medium">Tham chiếu</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-rose-100">
                @foreach($transactions as $t)
                @php
                [$cls, $txt, $ico] = $extBadge($t);
                $isCredit = ($t->type === 'credit');
                @endphp
                <tr class="hover:bg-rose-50/40">
                    <td class="px-5 py-3 whitespace-nowrap">{{ optional($t->created_at)->format('d/m/Y H:i') }}</td>
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] border {{ $cls }}">
                                <i class="fa-solid {{ $ico }}"></i>{{ $txt }}
                            </span>
                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] border
                                    {{ $isCredit ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200' }}">
                                <i class="fa-solid {{ $isCredit ? 'fa-circle-arrow-down' : 'fa-circle-arrow-up' }}"></i>
                                {{ $isCredit ? 'Cộng' : 'Trừ' }}
                            </span>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-right {{ $isCredit ? 'text-emerald-600' : 'text-rose-600' }}">
                        {{ ($isCredit ? '+' : '-') . $fmt($t->amount) }}
                    </td>
                    <td class="px-5 py-3 text-right">{{ $fmt($t->balance_after) }}</td>
                    <td class="px-5 py-3">
                        @if($t->ref_title)
                        <div class="font-medium">{{ $t->ref_title }}</div>
                        @if($t->ref_code)
                        <div class="text-xs text-ink/60">Mã: {{ $t->ref_code }}</div>
                        @endif
                        @else
                        —
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="px-5 py-3 border-t">
            {{ $transactions->onEachSide(1)->links() }}
        </div>
        @endif
    </div>
</div>
@endsection