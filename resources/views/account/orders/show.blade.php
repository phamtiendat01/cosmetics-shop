@extends('layouts.app')
@section('title','Chi tiết đơn hàng')

@section('content')
@php
use Illuminate\Support\Str;

/* ---------- Chuẩn hoá trạng thái để đồng bộ với admin ---------- */
$raw = $order->status ?? '';
$norm = Str::snake($raw);
$aliases = [
'pending'=>'cho_xac_nhan','cho_thanh_toan'=>'cho_xac_nhan','cho_xu_ly'=>'cho_xac_nhan','cho_xac_nhan'=>'cho_xac_nhan',
'confirmed'=>'da_xac_nhan','da_xac_nhan'=>'da_xac_nhan',
'processing'=>'dang_xu_ly','dang_xu_ly'=>'dang_xu_ly',
'shipping'=>'dang_giao','dang_giao'=>'dang_giao',
'completed'=>'hoan_tat','hoan_thanh'=>'hoan_tat','hoan_tat'=>'hoan_tat',
'cancelled'=>'huy','da_huy'=>'huy','huy'=>'huy',
'refunded'=>'hoan_tien','da_hoan_tien'=>'hoan_tien','hoan_tien'=>'hoan_tien',
];
$canon = $aliases[$norm] ?? $norm;

$displaySteps = [
'dat_hang'=>'Đặt hàng',
'xac_nhan'=>'Xác nhận',
'xu_ly'=>'Xử lý',
'giao_hang'=>'Giao hàng',
'hoan_tat'=>'Hoàn tất',
];
$mapToIndex = [
'cho_xac_nhan'=>0, 'pending'=>0,
'da_xac_nhan'=>1,
'dang_xu_ly'=>2,
'dang_giao'=>3,
'hoan_tat'=>4,
];
$currentIndex = $mapToIndex[$canon] ?? 0;
$statusLabel = array_values($displaySteps)[$currentIndex] ?? ucfirst($canon);

$ended = in_array($canon, ['huy','hoan_tien'], true);
$endedLabel = $canon==='huy' ? 'Đã huỷ' : ($canon==='hoan_tien' ? 'Đã hoàn tiền' : null);
$endedPill = $canon==='huy'
? 'bg-rose-50 text-rose-700 border border-rose-200'
: 'bg-sky-50 text-sky-700 border border-sky-200';

$mainPill = match ($canon) {
'hoan_tat' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
'dang_giao' => 'bg-sky-50 text-sky-700 border border-sky-200',
'dang_xu_ly' => 'bg-amber-50 text-amber-700 border border-amber-200',
'da_xac_nhan' => 'bg-violet-50 text-violet-700 border border-violet-200',
'huy' => 'bg-rose-50 text-rose-700 border border-rose-200',
'hoan_tien' => 'bg-sky-50 text-sky-700 border border-sky-200',
default => 'bg-rose-50/60 text-ink/70 border border-rose-200',
};

$payStatus = Str::snake($order->payment_status ?? '');
$payLabel = match ($payStatus) {
'paid'=>'Đã thanh toán','refunded'=>'Đã hoàn tiền','failed'=>'Thanh toán thất bại','pending'=>'Chờ thanh toán',
default => Str::title(str_replace('_',' ',$payStatus ?: 'Chưa thanh toán')),
};
$payPill = match ($payStatus) {
'paid'=>'bg-emerald-50 text-emerald-700 border border-emerald-200',
'refunded'=>'bg-sky-50 text-sky-700 border border-sky-200',
'failed'=>'bg-rose-50 text-rose-700 border border-rose-200',
default=>'bg-rose-50/60 text-ink/70 border border-rose-200',
};
$methodMap = ['COD'=>'Thanh toán khi nhận hàng (COD)','VNPAY'=>'VNPay','MOMO'=>'Momo','VIETQR'=>'VietQR'];

/* ---------- Địa chỉ giao hàng ---------- */
$shipping = $shipping ?? (is_array($order->shipping_address) ? $order->shipping_address : []);
$receiverName = $shipping['name'] ?? $shipping['full_name'] ?? $order->shipping_name ?? $order->recipient_name ?? $order->customer_name ?? '—';
$receiverPhone = $shipping['phone'] ?? $order->shipping_phone ?? $order->recipient_phone ?? '—';
$addrLine1 = $shipping['address'] ?? $shipping['address_line1'] ?? $order->shipping_address_line1 ?? null;
$ward = $shipping['ward'] ?? $shipping['ward_name'] ?? null;
$district = $shipping['district'] ?? $shipping['district_name'] ?? null;
$city = $shipping['city'] ?? $shipping['province'] ?? $shipping['city_name'] ?? null;
$fullAddress = collect([$addrLine1,$ward,$district,$city])->filter()->implode(', ');

/* ---------- Icon cho step ---------- */
$icons = [
'dat_hang' => 'M3 6h18M3 6l2 12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2L21 6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2',
'xac_nhan' => 'M20 6L9 17l-5-5',
'xu_ly' => 'M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83',
'giao_hang' => 'M3 7h11v10H3zM14 10h5l2 3v4h-7zM5 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM17 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4z',
'hoan_tat' => 'M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 22 12 18.54 5.82 22 7 14.14l-5-4.87 6.91-1.01z',
];
@endphp

<div class="max-w-7xl mx-auto px-4 py-6">
    {{-- Header --}}
    <div class="flex items-start justify-between gap-6">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-semibold">Đơn #<span id="order-code">{{ $order->code }}</span></h1>
                <button id="btn-copy" class="text-xs px-2 py-1 rounded-md border border-rose-200 hover:bg-rose-50">Sao chép</button>
            </div>
            <div class="text-sm text-ink/60 mt-1">Đặt lúc {{ optional($order->created_at)->format('d/m/Y H:i') }}</div>
        </div>
        <div class="text-right space-y-1">
            <div class="text-sm">Trạng thái:
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs {{ $mainPill }}">{{ $statusLabel }}</span>
            </div>
            <div class="text-sm">Thanh toán:
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs {{ $payPill }}">{{ $payLabel }}</span>
            </div>
            @if($order->payment_status!=='paid' && Route::has('payment.vietqr.show') && ($order->payment_method ?? '')==='VIETQR')
            <a href="{{ route('payment.vietqr.show', $order) }}"
                class="inline-flex items-center rounded-md bg-gradient-to-r from-rose-600 to-pink-600 text-white px-4 py-2 text-sm hover:from-rose-500 hover:to-pink-500">
                Tiếp tục thanh toán
            </a>
            @endif
        </div>
    </div>

    {{-- Stepper WOW --}}
    <div class="mt-6 bg-white border border-rose-100 rounded-2xl shadow-sm p-5">
        <div class="flex items-center gap-4">
            @php $i=0; @endphp
            @foreach($displaySteps as $key => $label)
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center
                      {{ $i <= $currentIndex ? 'text-white shadow-md bg-gradient-to-br from-rose-600 to-pink-500' : 'text-rose-300 border border-rose-200 bg-white' }}">
                    <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2">{!! '
                        <path d="'.$icons[$key].'" />' !!}
                    </svg>
                </div>
                <div class="text-xs font-medium {{ $i <= $currentIndex ? 'text-ink' : 'text-ink/40' }}">{{ $label }}</div>
            </div>
            @if(!$loop->last)
            <div class="flex-1 h-1 rounded-full {{ $i < $currentIndex ? 'bg-gradient-to-r from-rose-500 to-pink-500' : 'bg-rose-100' }}"></div>
            @endif
            @php $i++; @endphp
            @endforeach
        </div>
        @if($endedLabel)
        <div class="mt-3">
            <span class="inline-flex rounded-full px-2 py-0.5 text-xs {{ $endedPill }}">{{ $endedLabel }}</span>
        </div>
        @endif
    </div>

    {{-- 2 cột: trái thông tin, phải sản phẩm --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
        {{-- Trái: Người nhận / Địa chỉ / Thanh toán --}}
        <div class="space-y-6">
            <div class="bg-white border border-rose-100 rounded-2xl shadow-sm p-5">
                <div class="text-sm font-semibold mb-2">Người nhận</div>
                <div class="text-ink">{{ $receiverName ?: '—' }}</div>
                <div class="text-ink/70 text-sm mt-1">{{ $receiverPhone ?: '—' }}</div>
            </div>

            <div class="bg-white border border-rose-100 rounded-2xl shadow-sm p-5">
                <div class="text-sm font-semibold mb-2">Địa chỉ giao hàng</div>
                <div class="text-ink">{{ $fullAddress ?: '—' }}</div>
            </div>

            <div class="bg-white border border-rose-100 rounded-2xl shadow-sm p-5 space-y-1">
                <div class="text-sm font-semibold">Thanh toán</div>
                <div class="text-sm">Phương thức:
                    <span class="font-medium">
                        {{ $methodMap[$order->payment_method ?? ''] ?? ($order->payment_method ?? '—') }}
                    </span>
                </div>
                <div class="text-sm">Trạng thái:
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs {{ $payPill }}">{{ $payLabel }}</span>
                </div>
            </div>
        </div>

        {{-- Phải: Sản phẩm + Tổng tiền --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white border border-rose-100 rounded-2xl shadow-sm overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-rose-50/60 text-ink/70">
                        <tr>
                            <th class="px-6 py-3 text-left font-medium">Sản phẩm</th>
                            <th class="px-6 py-3 text-center font-medium">SL</th>
                            <th class="px-6 py-3 text-right font-medium">Đơn giá</th>
                            <th class="px-6 py-3 text-right font-medium">Thành tiền</th>
                            <th class="px-6 py-3 text-right font-medium">Đánh giá</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-rose-100">
                        @foreach($order->items as $it)
                        @php
                        $variant = $it->variant;
                        if (is_string($variant)) { $decoded = json_decode($variant, true); if (json_last_error() === JSON_ERROR_NONE) $variant = $decoded; }
                        if (!is_array($variant)) $variant = [];
                        $variantLabel = $it->variant_name ?? ($variant['name'] ?? ($variant['title'] ?? null));

                        $thumb = $it->thumbnail ?? ($variant['image'] ?? null) ?? optional($it->product)->thumbnail;
                        if ($thumb && !Str::startsWith($thumb,['http://','https://'])) {
                        $thumb = asset(Str::startsWith($thumb,['storage/','/storage/']) ? ltrim($thumb,'/') : 'storage/'.ltrim($thumb,'/'));
                        }

                        $unit = (float)($it->unit_price ?? $it->price ?? 0);
                        if (!$unit && isset($variant['price'])) $unit = (float)$variant['price'];
                        if (!$unit && optional($it->product)->price) $unit = (float)$it->product->price;
                        $lineTotal = $unit * (int)$it->qty;

                        $rv = $it->review ?? null;
                        @endphp

                        <tr>
                            <td class="px-6 py-3">
                                <div class="flex items-center gap-3">
                                    @if($thumb)
                                    <img src="{{ $thumb }}" class="w-12 h-12 rounded object-cover border" alt="">
                                    @else
                                    <div class="w-12 h-12 rounded border flex items-center justify-center text-ink/40">IMG</div>
                                    @endif
                                    <div>
                                        <div class="font-medium">{{ $it->product_name_snapshot ?? optional($it->product)->name ?? 'Sản phẩm' }}</div>
                                        @if($variantLabel)
                                        <div class="mt-0.5 inline-flex rounded-full border px-2 py-0.5 text-xs text-ink/70">{{ $variantLabel }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-3 text-center">{{ $it->qty }}</td>
                            <td class="px-6 py-3 text-right">₫{{ number_format($unit) }}</td>
                            <td class="px-6 py-3 text-right">₫{{ number_format($lineTotal) }}</td>
                            <td class="px-6 py-3 text-right">
                                @can('create', $it)
                                <a href="{{ route('account.order-items.reviews.create', [$order, $it]) }}"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md border border-rose-200 bg-white
                              text-ink/80 hover:text-white hover:border-transparent
                              hover:bg-gradient-to-r hover:from-rose-600 hover:to-pink-600
                              hover:shadow-md transition">
                                    <i class="fa-regular fa-star"></i> Đánh giá
                                </a>
                                @else
                                @if($rv)
                                <span class="inline-flex items-center gap-1 text-amber-500">
                                    @for($s=1;$s<=5;$s++)
                                        <i class="fa-solid fa-star {{ $s <= ($rv->rating ?? 0) ? '' : 'opacity-30' }}"></i>
                                        @endfor
                                </span>
                                @elseif($order->payment_status !== 'paid' || !in_array(Str::snake($order->status), ['hoan_thanh','completed']))
                                <span class="text-ink/60 text-xs">Chưa đủ điều kiện</span>
                                @else
                                <span class="text-ink/60 text-xs">Đã đánh giá</span>
                                @endif
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="bg-white border border-rose-100 rounded-2xl shadow-sm p-5">
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div class="text-ink/70">Tạm tính</div>
                    <div class="text-right font-medium">
                        ₫{{ number_format($order->subtotal ?? ($order->grand_total - ($order->shipping_fee ?? 0) + ($order->discount_total ?? 0))) }}
                    </div>
                    <div class="text-ink/70">Giảm giá</div>
                    <div class="text-right">-₫{{ number_format($order->discount_total ?? 0) }}</div>
                    <div class="text-ink/70">Phí vận chuyển</div>
                    <div class="text-right">₫{{ number_format($order->shipping_fee ?? 0) }}</div>
                    @if(!empty($order->tax_total))
                    <div class="text-ink/70">Thuế</div>
                    <div class="text-right">₫{{ number_format($order->tax_total) }}</div>
                    @endif
                    <div class="col-span-2 border-t border-rose-100 my-1"></div>
                    <div class="text-ink font-semibold">Tổng cộng</div>
                    <div class="text-right text-lg font-semibold">₫{{ number_format($order->grand_total) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Toast copy --}}
<div id="toast" class="hidden fixed left-1/2 -translate-x-1/2 bottom-6 bg-slate-900 text-white text-sm px-4 py-2 rounded-xl shadow-xl">
    Đã sao chép mã đơn
</div>

<script>
    (function() {
        const btn = document.getElementById('btn-copy');
        const code = document.getElementById('order-code')?.textContent || '';
        const toast = document.getElementById('toast');
        btn?.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(code);
            } catch (_) {}
            toast.classList.remove('hidden');
            toast.animate([{
                transform: 'translateY(12px)',
                opacity: 0
            }, {
                transform: 'translateY(0)',
                opacity: 1
            }], {
                duration: 200,
                fill: 'forwards'
            });
            setTimeout(() => {
                toast.animate([{
                    opacity: 1
                }, {
                    opacity: 0
                }], {
                    duration: 200,
                    fill: 'forwards'
                });
                setTimeout(() => toast.classList.add('hidden'), 220);
            }, 1200);
        });
    })();
</script>
@endsection