@extends('layouts.app')
@section('title','Đơn hàng của tôi')

@section('content')
@php
// ---- Pill classes theo theme hồng nhẹ ----
$pill = function($type, $val) {
$statusMap = [
'cho_xu_ly' => 'bg-amber-50 text-amber-700 border-amber-200',
'cho_thanh_toan' => 'bg-amber-50 text-amber-700 border-amber-200',
'cho_xac_nhan' => 'bg-sky-50 text-sky-700 border-sky-200',
'dang_giao' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
'hoan_tat' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
'huy' => 'bg-rose-50 text-rose-700 border-rose-200',
];
$payMap = [
'paid' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
'failed' => 'bg-rose-50 text-rose-700 border-rose-200',
'refunded' => 'bg-sky-50 text-sky-700 border-sky-200',
];
$map = $type==='status' ? $statusMap : $payMap;
return 'inline-flex items-center rounded-full border px-2 py-0.5 text-xs '.($map[$val] ?? 'bg-rose-50/60 text-ink/70 border-rose-200');
};

// build url, clear page
$url = function(array $kv = []) {
return request()->fullUrlWithQuery(array_merge(request()->except('page'), $kv));
};

// Chuẩn danh sách chip trạng thái (động theo backend)
$statusChips = collect($statusOptions ?? [])->map(function($label,$key){
return ['key'=>$key,'label'=>$label];
})->values();

// Chip thanh toán
$payChips = collect($payOptions ?? [])->only(['paid','pending'])->map(function($label,$key){
return ['key'=>$key,'label'=>$label];
})->values();

// đếm nếu controller có truyền; nếu không → null (ẩn)
$statusCounts = $statusCounts ?? null; // ['cho_xac_nhan'=>2, ...]
$paymentCounts = $paymentCounts ?? null; // ['paid'=>5,'pending'=>1]
@endphp

<div class="max-w-7xl mx-auto px-4 py-6">

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-rose-100 text-rose-600">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 6h18M3 6l2 12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2L21 6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                    </svg>
                </span>
                <h1 class="text-2xl font-semibold">Đơn hàng của tôi</h1>
            </div>
            <p class="text-ink/60 text-sm mt-1">Xem lịch sử mua hàng, lọc theo trạng thái & tiếp tục thanh toán nếu cần.</p>
        </div>
        <a href="{{ route('shop.index') }}"
            class="hidden md:inline-flex items-center rounded-xl bg-rose-600 text-white px-4 py-2 text-sm hover:bg-rose-500">
            Tiếp tục mua sắm
        </a>
    </div>

    {{-- Quick chips: Trạng thái + Thanh toán --}}
    <div class="mt-4 flex flex-wrap gap-2">
        @php
        $isAll = !request('status') && !request('payment');
        $chipBase = 'whitespace-nowrap inline-flex items-center rounded-full px-3 py-1 text-xs border';
        $chipActive = 'bg-rose-600 text-white border-rose-600';
        $chipNormal = 'bg-white text-ink border-rose-200 hover:bg-rose-50';
        @endphp

        <a href="{{ $url(['status'=>null,'payment'=>null]) }}"
            class="{{ $chipBase }} {{ $isAll ? $chipActive : $chipNormal }}">Tất cả
            @if(($statusCounts['all'] ?? null) !== null)
            <span class="ml-1 text-[11px] opacity-90">({{ $statusCounts['all'] }})</span>
            @endif
        </a>

        @foreach($statusChips as $c)
        @php $on = request('status')===$c['key']; @endphp
        <a href="{{ $url(['status'=>$c['key'],'payment'=>null]) }}"
            class="{{ $chipBase }} {{ $on ? $chipActive : $chipNormal }}">
            {{ $c['label'] }}
            @if($statusCounts && array_key_exists($c['key'],$statusCounts))
            <span class="ml-1 text-[11px] opacity-90">({{ $statusCounts[$c['key']] }})</span>
            @endif
        </a>
        @endforeach

        @foreach($payChips as $c)
        @php $on = request('payment')===$c['key']; @endphp
        <a href="{{ $url(['payment'=>$c['key'],'status'=>null]) }}"
            class="{{ $chipBase }} {{ $on ? $chipActive : $chipNormal }}">
            {{ $c['label'] }}
            @if($paymentCounts && array_key_exists($c['key'],$paymentCounts))
            <span class="ml-1 text-[11px] opacity-90">({{ $paymentCounts[$c['key']] }})</span>
            @endif
        </a>
        @endforeach
    </div>

    {{-- Form lọc --}}
    <form method="get" class="grid grid-cols-1 md:grid-cols-6 gap-3 mt-4 mb-5">
        <div class="col-span-1 md:col-span-2 relative">
            <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-ink/40" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <circle cx="11" cy="11" r="7" />
                <path d="M21 21l-4.3-4.3" />
            </svg>
            <input name="q" value="{{ $filters['q'] }}" placeholder="Tìm mã đơn (VD: CH-...)"
                class="w-full rounded-xl border border-rose-200 bg-white pl-9 pr-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-rose-300" />
        </div>

        <select name="status" class="col-span-1 md:col-span-1 w-full rounded-xl border border-rose-200 bg-white px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-rose-300">
            <option value="">Trạng thái</option>
            @foreach($statusOptions as $val => $label)
            <option value="{{ $val }}" @selected($filters['status']===$val)>{{ $label }}</option>
            @endforeach
        </select>

        <select name="payment" class="col-span-1 md:col-span-1 w-full rounded-xl border border-rose-200 bg-white px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-rose-300">
            <option value="">Thanh toán</option>
            @foreach($payOptions as $val => $label)
            <option value="{{ $val }}" @selected($filters['payment']===$val)>{{ $label }}</option>
            @endforeach
        </select>

        <input type="date" name="from" value="{{ $filters['from'] }}"
            class="col-span-1 md:col-span-1 w-full rounded-xl border border-rose-200 bg-white px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-rose-300" />
        <input type="date" name="to" value="{{ $filters['to'] }}"
            class="col-span-1 md:col-span-1 w-full rounded-xl border border-rose-200 bg-white px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-rose-300" />

        <div class="col-span-1 md:col-span-6 flex gap-2">
            <button class="inline-flex items-center rounded-xl bg-rose-600 text-white px-4 py-2.5 text-sm hover:bg-rose-500">Lọc</button>
            <a href="{{ route('account.orders.index') }}" class="inline-flex items-center rounded-xl border border-rose-200 px-4 py-2.5 text-sm hover:bg-rose-50">Xoá lọc</a>
        </div>
    </form>

    {{-- Desktop table --}}
    <div class="hidden md:block bg-white border border-rose-100 rounded-2xl shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-rose-50/60 text-ink/70">
                <tr>
                    <th class="px-6 py-3 text-left font-medium">Mã đơn</th>
                    <th class="px-6 py-3 text-left font-medium">Ngày đặt</th>
                    <th class="px-6 py-3 text-left font-medium">Trạng thái</th>
                    <th class="px-6 py-3 text-left font-medium">Thanh toán</th>
                    <th class="px-6 py-3 text-right font-medium">Tổng tiền</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-rose-100">
                @forelse($orders as $o)
                @php
                $statusLabel = $statusOptions[$o->status] ?? ucfirst($o->status);
                $payLabel = $payOptions[$o->payment_status] ?? ucfirst($o->payment_status);
                @endphp
                <tr class="hover:bg-rose-50/40 transition">
                    <td class="px-6 py-3 font-medium">#{{ $o->code }}</td>
                    <td class="px-6 py-3">{{ optional($o->created_at)->format('d/m/Y H:i') }}</td>
                    <td class="px-6 py-3"><span class="{{ $pill('status',$o->status) }}">{{ $statusLabel }}</span></td>
                    <td class="px-6 py-3"><span class="{{ $pill('pay',$o->payment_status) }}">{{ $payLabel }}</span></td>
                    <td class="px-6 py-3 text-right">₫{{ number_format($o->grand_total) }}</td>
                    <td class="px-6 py-3 text-right space-x-2">
                        @if($o->payment_status!=='paid' && \Illuminate\Support\Facades\Route::has('payment.vietqr.show') && ($o->payment_method ?? '')==='VIETQR')
                        <a href="{{ route('payment.vietqr.show', $o->id) }}"
                            class="inline-flex items-center rounded-xl bg-rose-600 text-white px-3 py-1.5 text-sm hover:bg-rose-500">
                            Thanh toán
                        </a>
                        @endif
                        <a href="{{ route('account.orders.show', $o->id) }}"
                            class="inline-flex items-center rounded-xl border border-rose-200 px-3 py-1.5 text-sm hover:bg-rose-50">
                            Chi tiết
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-10 text-center">
                        <div class="mx-auto w-12 h-12 rounded-full bg-rose-100 flex items-center justify-center mb-3">
                            <svg class="w-6 h-6 text-rose-600" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M3 3h2l.4 2M7 13h10l3-7H6.4M7 13l-1.2 5.2A1 1 0 007 20h10" />
                            </svg>
                        </div>
                        <div class="text-ink font-medium">Không tìm thấy đơn phù hợp.</div>
                        <a href="{{ route('shop.index') }}"
                            class="mt-3 inline-flex items-center rounded-xl bg-rose-600 text-white px-4 py-2 text-sm hover:bg-rose-500">
                            Tiếp tục mua sắm
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($orders->hasPages())
        <div class="px-6 py-3 border-t border-rose-100">
            {{ $orders->links() }}
        </div>
        @endif
    </div>

    {{-- Mobile card list --}}
    <div class="md:hidden space-y-3 mt-3">
        @forelse($orders as $o)
        @php
        $statusLabel = $statusOptions[$o->status] ?? ucfirst($o->status);
        $payLabel = $payOptions[$o->payment_status] ?? ucfirst($o->payment_status);
        @endphp
        <div class="bg-white border border-rose-100 rounded-2xl shadow-sm p-4">
            <div class="flex items-start justify-between">
                <div>
                    <div class="font-semibold">#{{ $o->code }}</div>
                    <div class="text-xs text-ink/60 mt-0.5">{{ optional($o->created_at)->format('d/m/Y H:i') }}</div>
                </div>
                <div class="text-right space-y-1">
                    <span class="{{ $pill('status',$o->status) }}">{{ $statusLabel }}</span>
                    <span class="{{ $pill('pay',$o->payment_status) }}">{{ $payLabel }}</span>
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between">
                <div class="text-ink/70 text-sm">Tổng:
                    <span class="font-semibold text-ink">₫{{ number_format($o->grand_total) }}</span>
                </div>
                <div class="space-x-2">
                    @if($o->payment_status!=='paid' && \Illuminate\Support\Facades\Route::has('payment.vietqr.show') && ($o->payment_method ?? '')==='VIETQR')
                    <a href="{{ route('payment.vietqr.show', $o->id) }}"
                        class="inline-flex items-center rounded-xl bg-rose-600 text-white px-3 py-1.5 text-xs hover:bg-rose-500">Thanh toán</a>
                    @endif
                    <a href="{{ route('account.orders.show', $o->id) }}"
                        class="inline-flex items-center rounded-xl border border-rose-200 px-3 py-1.5 text-xs hover:bg-rose-50">Chi tiết</a>
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white border border-rose-100 rounded-2xl shadow-sm p-6 text-center">
            <div class="mx-auto w-12 h-12 rounded-full bg-rose-100 flex items-center justify-center mb-3">
                <svg class="w-6 h-6 text-rose-600" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M3 3h2l.4 2M7 13h10l3-7H6.4M7 13l-1.2 5.2A1 1 0 007 20h10" />
                </svg>
            </div>
            <div class="text-ink font-medium">Không có đơn nào.</div>
            <a href="{{ route('shop.index') }}"
                class="mt-3 inline-flex items-center rounded-xl bg-rose-600 text-white px-4 py-2 text-sm hover:bg-rose-500">
                Tiếp tục mua sắm
            </a>
        </div>
        @endforelse

        @if($orders->hasPages())
        <div class="mt-2">
            {{ $orders->links() }}
        </div>
        @endif
    </div>
</div>
@endsection