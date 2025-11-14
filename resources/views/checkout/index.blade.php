@extends('layouts.app')
@section('title','Thanh toán | Cosme House')

@section('content')
@php
/** @var array $cart */
$subtotal = (int)($cart['subtotal'] ?? 0);
$shipping = (int)($cart['shipping_fee'] ?? 0);

/* lấy voucher vận chuyển đã áp dụng (nếu có) */
$shipSession = session('applied_ship') ?? [];
$shipCode = $shipSession['code'] ?? null;
$shipDiscount = (int)($shipSession['discount'] ?? 0);

$grand = max(0, $subtotal + $shipping - $shipDiscount);

$addresses = $addresses ?? collect();
$selected = $selected ?? null;
$profile = $profile ?? ['name'=>null,'email'=>null,'phone'=>null];

$selWard = $selected?->ward ?? $selected?->commune ?? $selected?->ward_name ?? '';
$selDistrict = $selected?->district ?? $selected?->district_name ?? '';
$selCity = $selected?->city ?? $selected?->province ?? $selected?->province_name ?? $selected?->state ?? '';

$walletBalance = (int)($walletBalance ?? 0); // controller truyền sang (mặc định 0)
@endphp

{{-- ============ FIX UI & FX ============ --}}
<style>
    .checkout-scope input[type=radio] {
        appearance: auto !important;
        -webkit-appearance: auto !important;
        accent-color: #e11d48;
        width: 18px;
        height: 18px;
        flex: 0 0 18px
    }

    .checkout-scope label.option {
        display: flex;
        align-items: center;
        gap: .75rem;
        border: 1.5px solid #ffd6de;
        border-radius: 16px;
        padding: 12px 14px;
        background: #fff;
        cursor: pointer;
        transition: .18s ease
    }

    .checkout-scope label.option:hover {
        border-color: #fda4af;
        background: #fff1f2
    }

    .checkout-scope label.option:has(input[type=radio]:checked) {
        border-color: #e11d48;
        background: #fff5f7;
        box-shadow: 0 8px 22px rgba(225, 29, 72, .09)
    }

    .checkout-scope .pm-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: #fff1f2;
        border: 1px solid #ffe4ea;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #e11d48
    }

    .checkout-scope .addr-card {
        align-items: flex-start
    }

    .checkout-scope .item-thumb {
        width: 56px;
        height: 56px;
        border-radius: 12px;
        border: 1px solid #ececec;
        object-fit: cover;
        background: #fff
    }

    .checkout-scope .item-ph {
        width: 56px;
        height: 56px;
        border-radius: 12px;
        border: 1px dashed #e5e7eb;
        background: #fff7fb;
        color: #ef476f;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700
    }

    /* COD overlay */
    #codOverlay {
        position: fixed;
        inset: 0;
        background: rgba(255, 255, 255, .75);
        backdrop-filter: blur(6px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999
    }

    #codOverlay.show {
        display: flex
    }

    #codOverlay .card {
        background: #fff;
        border: 1px solid #fee2e2;
        border-radius: 18px;
        padding: 22px 26px;
        box-shadow: 0 24px 60px rgba(225, 29, 72, .12);
        transform: scale(.94);
        opacity: 0;
        transition: .28s ease
    }

    #codOverlay .card.on {
        transform: scale(1);
        opacity: 1
    }

    #codOverlay .tick {
        width: 56px;
        height: 56px;
        border-radius: 999px;
        background: conic-gradient(from 0deg, #f43f5e, #ec4899);
        display: grid;
        place-items: center;
        color: #fff;
        box-shadow: 0 10px 26px rgba(236, 72, 153, .35)
    }

    #codOverlay .title {
        font-weight: 700;
        margin-top: 12px;
        color: #111827
    }

    #codOverlay .sub {
        font-size: .92rem;
        color: #6b7280;
        margin-top: 2px
    }

    #codOverlay .bar {
        height: 4px;
        background: #fee2e2;
        border-radius: 999px;
        overflow: hidden;
        margin-top: 14px
    }

    #codOverlay .bar span {
        display: block;
        height: 100%;
        width: 0;
        background: linear-gradient(90deg, #f43f5e, #ec4899);
        animation: codbar 1.2s ease forwards
    }

    @keyframes codbar {
        to {
            width: 100%
        }
    }
</style>

<section class="max-w-7xl mx-auto px-4 mt-6 checkout-scope">
    <h1 class="text-2xl font-bold mb-4">Thanh toán</h1>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        {{-- LEFT --}}
        <div class="lg:col-span-8 space-y-6">
            {{-- Địa chỉ --}}
            @auth
            <div class="bg-white border border-rose-100 rounded-2xl p-4">
                <div class="text-base font-semibold mb-3">Địa chỉ nhận hàng</div>
                @if($addresses->isEmpty())
                <div class="text-sm text-ink/60">
                    Bạn chưa có địa chỉ. Hãy thêm ở mục
                    <a class="text-brand-600 underline" href="{{ route('account.addresses.index') }}">Sổ địa chỉ</a>.
                </div>
                @else
                <div class="space-y-2" id="addrList">
                    @foreach($addresses as $a)
                    @php
                    $ward = $a->ward ?? $a->commune ?? $a->ward_name ?? '';
                    $district = $a->district ?? $a->district_name ?? '';
                    $city = $a->city ?? $a->province ?? $a->province_name ?? $a->state ?? '';
                    $line1 = $a->line1 ?? $a->address ?? '';
                    $line2 = $a->line2 ?? '';
                    @endphp
                    <label class="option addr-card"
                        data-name="{{ $a->name }}"
                        data-phone="{{ $a->phone }}"
                        data-line1="{{ $line1 }}"
                        data-line2="{{ $line2 }}"
                        data-ward="{{ $ward }}"
                        data-district="{{ $district }}"
                        data-city="{{ $city }}">
                        <input type="radio" name="address_id" value="{{ $a->id }}" {{ ($selected?->id===$a->id)?'checked':'' }}>
                        <div class="text-sm">
                            <div class="font-medium text-rose-700">{{ $a->name }} • {{ $a->phone }}</div>
                            <div class="text-ink/70">
                                {{ $line1 }}{{ $line2 ? ', '.$line2 : '' }},
                                {{ $ward ? $ward.', ' : '' }}{{ $district }}, {{ $city }}
                            </div>
                            @if($a->is_default_shipping)
                            <span class="text-xs px-2 py-0.5 bg-emerald-50 text-emerald-700 rounded">Mặc định</span>
                            @endif
                        </div>
                    </label>
                    @endforeach
                </div>
                <div class="mt-2 text-xs">
                    <a href="{{ route('account.addresses.index') }}" class="text-brand-600 underline">Quản lý sổ địa chỉ</a>
                </div>
                @endif
            </div>
            @endauth

            {{-- Thông tin nhận hàng --}}
            <div class="bg-white border border-rose-100 rounded-2xl p-4">
                <div class="text-base font-semibold mb-3">Thông tin nhận hàng</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm">Họ và tên</label>
                        <input id="name" class="mt-1 w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-brand-300"
                            value="{{ $profile['name'] ?? '' }}" placeholder="Nguyễn Văn A">
                    </div>
                    <div>
                        <label class="text-sm">Số điện thoại</label>
                        <input id="phone" class="mt-1 w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-brand-300"
                            value="{{ $profile['phone'] ?? '' }}" placeholder="09xxxxxxxx">
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-sm">Email (không bắt buộc)</label>
                        <input id="email" type="email" class="mt-1 w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-brand-300"
                            value="{{ $profile['email'] ?? '' }}" placeholder="you@email.com">
                    </div>

                    <div class="md:col-span-2">
                        <label class="text-sm">Địa chỉ</label>
                        <input id="address" class="mt-1 w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-brand-300"
                            value="{{ trim(($selected?->line1 ?? $selected?->address ?? '').' '.($selected?->line2 ?? '')) }}"
                            placeholder="Số nhà, đường...">
                    </div>
                    <div>
                        <label class="text-sm">Phường/Xã</label>
                        <input id="ward" class="mt-1 w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-brand-300"
                            value="{{ $selWard }}" placeholder="Phường/Xã">
                    </div>
                    <div>
                        <label class="text-sm">Quận/Huyện</label>
                        <input id="district" class="mt-1 w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-brand-300"
                            value="{{ $selDistrict }}" placeholder="Quận/Huyện">
                    </div>
                    <div class="md:col-span-2 md:grid md:grid-cols-2 md:gap-3">
                        <div class="md:col-span-1">
                            <label class="text-sm">Tỉnh/Thành</label>
                            <input id="city" class="mt-1 w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-brand-300"
                                value="{{ $selCity }}" placeholder="Tỉnh/Thành phố">
                        </div>
                        <div class="md:col-span-1">
                            <label class="text-sm">Ghi chú</label>
                            <input id="note" class="mt-1 w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-brand-300"
                                placeholder="Ví dụ: Giao giờ hành chính">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Phương thức thanh toán --}}
            <div class="bg-white border border-rose-100 rounded-2xl p-4">
                <div class="text-base font-semibold mb-3">Phương thức thanh toán</div>
                <div class="space-y-2" id="payMethods">
                    @php
                    $methods = [
                    ['code'=>'COD', 'label'=>'COD (Thanh toán khi nhận hàng)', 'hint'=>'Thu tiền khi giao hàng', 'icon'=>'cod'],
                    ['code'=>'VIETQR', 'label'=>'Chuyển khoản VietQR', 'hint'=>'Quét mã & chuyển khoản nhanh', 'icon'=>'qr'],
                    ['code'=>'MOMO', 'label'=>'MoMo', 'hint'=>'Thanh toán qua ví MoMo', 'icon'=>'momo'],
                    ['code'=>'VNPAY', 'label'=>'VNPay', 'hint'=>'Cổng thanh toán VNPay', 'icon'=>'card'],
                    ];
                    @endphp
                    @foreach($methods as $i=>$m)
                    <label class="option">
                        <input type="radio" name="payment_method" value="{{ $m['code'] }}" {{ $i===0?'checked':'' }}>
                        <span class="pm-icon">
                            @switch($m['icon'])
                            @case('qr')
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <rect x="3" y="3" width="7" height="7" rx="1.5" />
                                <rect x="14" y="3" width="7" height="7" rx="1.5" />
                                <rect x="3" y="14" width="7" height="7" rx="1.5" />
                                <path d="M14 14h3v3h-3zM18 18h3v3h-3z" />
                            </svg>
                            @break
                            @case('momo')
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <rect x="3" y="4" width="18" height="16" rx="3" />
                                <path d="M7 8h10M7 12h10M7 16h6" />
                            </svg>
                            @break
                            @case('card')
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <rect x="2" y="5" width="20" height="14" rx="2" />
                                <path d="M2 10h20M7 15h3" />
                            </svg>
                            @break
                            @default
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M3 7h13l3 5v5a2 2 0 0 1-2 2H3z" />
                                <path d="M13 7v10" />
                                <circle cx="7.5" cy="17.5" r="1.5" />
                                <circle cx="16.5" cy="17.5" r="1.5" />
                            </svg>
                            @endswitch
                        </span>
                        <div class="min-w-0">
                            <div class="font-medium">{{ $m['label'] }}</div>
                            <div class="text-sm text-ink/60">{{ $m['hint'] }}</div>
                        </div>
                    </label>
                    @endforeach
                </div>

                <div id="methodHint" class="mt-3 text-sm text-ink/70"></div>

                <button id="btnPlace"
                    class="mt-4 w-full px-4 py-3 rounded-xl bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-500 hover:to-pink-500 text-white font-medium flex items-center justify-center gap-2 shadow-lg">
                    <span>Đặt hàng</span>
                    <svg id="btnSpin" class="hidden animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" d="M4 12a8 8 0 018-8v4" stroke="currentColor" stroke-width="4"></path>
                    </svg>
                </button>
                <div id="placeMsg" class="text-sm text-rose-600 mt-2"></div>
            </div>
        </div>

        {{-- ====== ĐƠN HÀNG CỦA BẠN ====== --}}
        <div class="lg:col-span-4">
            <div class="bg-white border border-rose-100 rounded-2xl p-4 space-y-4">
                <div class="text-base font-semibold">Đơn hàng của bạn</div>

                {{-- Items --}}
                <div class="divide-y">
                    @foreach(($cart['items'] ?? []) as $it)
                    @php
                    $line = (int)($it['price']??0) * (int)($it['qty']??1);
                    $img = $it['image'] ?? $it['img'] ?? $it['thumb'] ?? $it['thumbnail'] ?? $it['product_image'] ?? null;
                    if ($img && !\Illuminate\Support\Str::startsWith($img, ['http://','https://','/storage/'])) {
                    $img = asset($img);
                    }
                    @endphp
                    <div class="py-3 flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3 min-w-0">
                            @if($img)
                            <img class="item-thumb" src="{{ $img }}" alt="">
                            @else
                            <div class="item-ph">SP</div>
                            @endif
                            <div class="min-w-0">
                                <div class="font-medium line-clamp-1">{{ $it['name'] ?? ('SP #'.$it['product_id']) }}</div>
                                @if(!empty($it['variant_name']))
                                <div class="text-xs text-ink/60 mt-0.5">{{ $it['variant_name'] }}</div>
                                @endif
                                <div class="text-xs text-ink/60 mt-0.5">x{{ (int)($it['qty']??1) }}</div>
                            </div>
                        </div>
                        <div class="font-semibold whitespace-nowrap">{{ number_format($line,0,',','.') }}₫</div>
                    </div>
                    @endforeach
                </div>

                {{-- ===== Ví Cosme (store credit) ===== --}}
                @auth
                <div class="bg-rose-50/40 border border-rose-100 rounded-2xl p-3">
                    <div class="flex items-center justify-between">
                        <div class="text-sm font-medium">Ví Cosme</div>
                        <div class="text-sm text-ink/60">
                            Số dư:
                            <b id="wlBal" data-bal="{{ $walletBalance }}">{{ number_format($walletBalance) }}₫</b>
                        </div>
                    </div>

                    <div class="mt-2 flex items-center gap-2">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" id="wlUse" class="accent-rose-600" {{ $walletBalance>0 ? '' : 'disabled' }}>
                            <span class="text-sm">Dùng ví</span>
                        </label>
                        <div class="flex-1"></div>
                        <input id="wlAmount" type="number" min="0" step="1000"
                            class="w-32 px-2 py-1 border rounded text-right"
                            placeholder="0" disabled>
                        <button id="wlMax" type="button"
                            class="px-2 py-1 text-sm border rounded disabled:opacity-50" disabled>
                            Dùng tối đa
                        </button>
                    </div>
                    <div id="wlMsg" class="mt-1 text-xs text-rose-600"></div>
                </div>
                @endauth

                {{-- ===== Mã vận chuyển ===== --}}
                <div>
                    <div class="text-sm font-medium mb-1">Mã vận chuyển</div>

                    <div id="shipWrap" class="relative">
                        <div id="shipRow" class="flex gap-2 {{ $shipCode ? 'hidden' : '' }}">
                            <input id="shipInput"
                                class="flex-1 px-3 py-2 rounded-md border border-rose-200 outline-none focus:ring-2 focus:ring-rose-300"
                                placeholder="Nhập mã vận chuyển">
                            <button id="btnShipApply"
                                class="px-3 py-2 rounded-md bg-rose-600 text-white hover:bg-rose-500">Áp dụng</button>
                            <button id="btnShipMenu" type="button"
                                class="px-3 py-2 rounded-md border border-rose-200 text-ink/70 hover:bg-rose-50"
                                title="Chọn mã của bạn">
                                <i class="fa-solid fa-caret-down"></i>
                            </button>
                        </div>

                        <div id="shipMenu"
                            class="hidden absolute z-30 mt-1 left-0 right-0 bg-white border border-rose-100 rounded-md shadow max-h-60 overflow-auto"></div>

                        <div id="shipApplied" class="{{ $shipCode ? 'flex' : 'hidden' }} mt-2 items-center gap-2">
                            <span class="px-2 py-1 bg-emerald-50 text-emerald-700 rounded text-sm">
                                Đã áp dụng: <span id="shipCode">{{ $shipCode }}</span>
                            </span>
                            <button id="btnShipRemove" class="text-sm text-rose-600 hover:underline">Huỷ</button>
                        </div>

                        <div id="shipMsg" class="text-xs text-rose-600 mt-1"></div>
                    </div>
                </div>

                {{-- Totals --}}
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between">
                        <span>Tạm tính</span>
                        <span id="ckSubtotal" data-val="{{ $subtotal }}">{{ number_format($subtotal,0,',','.') }}₫</span>
                    </div>

                    <div class="flex justify-between">
                        <span>Phí vận chuyển</span>
                        <span id="ckShipFee" data-val="{{ $shipping }}">
                            {{ $shipping>0? number_format($shipping,0,',','.') .'₫' : 'Miễn phí' }}
                        </span>
                    </div>

                    <div class="flex justify-between text-emerald-700">
                        <span>Giảm phí vận chuyển</span>
                        <span id="ckShipDiscount" data-val="{{ $shipDiscount }}">{{ number_format($shipDiscount,0,',','.') }}₫</span>
                    </div>

                    {{-- Tổng trước khi trừ ví (để computeGrand dùng & cho khách nhìn rõ) --}}
                    <div class="flex justify-between">
                        <span>Tổng (trước khi trừ ví)</span>
                        <span id="ckGrand" data-val="{{ $grand }}">{{ number_format($grand,0,',','.') }}₫</span>
                    </div>

                    {{-- Số dùng ví --}}
                    @auth
                    <div class="flex justify-between text-rose-700">
                        <span>Dùng Ví Cosme</span>
                        <span id="ckWalletUse" data-val="0">-0₫</span>
                    </div>
                    @endauth

                    <div class="border-t border-rose-100 pt-2 text-base font-semibold flex justify-between">
                        <span>Còn phải thanh toán</span>
                        <span id="ckPayable" data-val="{{ $grand }}">{{ number_format($grand,0,',','.') }}₫</span>
                    </div>
                </div>

                <div class="text-sm text-emerald-700 flex items-center gap-2 pt-1">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                        <path d="M22 4L12 14.01l-3-3" />
                    </svg>
                    Thanh toán an toàn & bảo mật
                </div>
            </div>
        </div>
    </div>
</section>

{{-- COD overlay --}}
<div id="codOverlay">
    <div class="card">
        <div class="tick">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M20 6 9 17l-5-5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </div>
        <div class="title">Đặt hàng thành công</div>
        <div class="sub">Đang chuyển tới chi tiết đơn…</div>
        <div class="bar"><span></span></div>
    </div>
</div>

<script>
    const R = {
        place: @json(route('checkout.place')),
        quote: @json(route('shipping.quote')),
        ordersIndex: @json(route('account.orders.index')),
        /* APIs riêng cho MÃ VẬN CHUYỂN */
        shipMine: @json(route('ship.mine')), // GET
        shipApply: @json(route('ship.apply')), // POST {code, address_id?}
        shipRemove: @json(route('ship.remove')), // DELETE
        csrf: document.querySelector('meta[name=csrf-token]').content
    };

    // Helper
    const $ = (id) => document.getElementById(id);
    const F = {
        name: $('name'),
        phone: $('phone'),
        email: $('email'),
        address: $('address'),
        ward: $('ward'),
        district: $('district'),
        city: $('city'),
        note: $('note')
    };
    const fmt = (n) => (Number(n || 0)).toLocaleString('vi-VN') + '₫';

    function setHint(code) {
        const m = {
            COD: '<b>COD:</b> Thanh toán khi nhận hàng.',
            VIETQR: '<b>VietQR:</b> Quét mã để chuyển khoản nhanh.',
            MOMO: '<b>MoMo:</b> Chuyển hướng sang cổng MoMo.',
            VNPAY: '<b>VNPay:</b> Chuyển hướng sang cổng VNPay.'
        };
        document.getElementById('methodHint').innerHTML = m[code] || '';
    }
    setHint(document.querySelector('input[name=payment_method]:checked')?.value || 'COD');
    document.querySelectorAll('input[name=payment_method]').forEach(r => r.addEventListener('change', e => setHint(e.target.value)));

    // ====== Shipping voucher UI & totals ======
    const elSub = $('ckSubtotal');
    const elFee = $('ckShipFee');
    const elDis = $('ckShipDiscount');
    const elGrand = $('ckGrand');
    const elMsg = $('shipMsg');
    const elMenu = $('shipMenu');
    const elRow = $('shipRow');
    const elApplied = $('shipApplied');
    const elCodeSpan = document.querySelector('#shipApplied #shipCode');

    function computeGrand(discount) {
        const sub = Number(elSub.dataset.val || 0);
        const fee = Number(elFee.dataset.val || 0);
        const dis = Number(discount || 0);
        const grand = Math.max(0, sub + fee - dis);
        elDis.dataset.val = dis;
        elDis.textContent = fmt(dis);
        if (elGrand) {
            elGrand.dataset.val = grand;
            elGrand.textContent = fmt(grand);
        }
        return grand;
    }

    function currentAddressId() {
        const checked = document.querySelector('input[name="address_id"]:checked');
        return checked ? checked.value : null;
    }

    async function loadShipMine() {
        elMenu.classList.remove('hidden');
        elMenu.innerHTML = '<div class="px-3 py-2 text-ink/60 text-sm">Đang tải…</div>';
        try {
            const res = await fetch(R.shipMine, {
                headers: {
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            const list = Array.isArray(data?.data) ? data.data : (data || []);
            if (!list.length) {
                elMenu.innerHTML = '<div class="px-3 py-2 text-ink/60 text-sm">Bạn chưa có mã vận chuyển nào.</div>';
                return;
            }
            elMenu.innerHTML = list.map(v => {
                const disabled = v.usable === false;
                return `
                    <button class="w-full text-left px-3 py-2 hover:bg-rose-50 ${disabled?'opacity-50 cursor-not-allowed':''}"
                            data-code="${v.code}" ${disabled?'disabled':''}>
                        <div class="text-sm font-medium">${v.code} <span class="text-ink/60 font-normal">— ${v.discount_text||'Giảm phí ship'}</span></div>
                        <div class="text-xs text-ink/60">
                            ${v.min_order ? 'ĐH tối thiểu '+fmt(v.min_order)+' • ' : ''}${v.expires_at?('HSD: '+v.expires_at):''}
                            ${v.reason ? ' • '+v.reason : ''}
                        </div>
                    </button>
                `;
            }).join('');
            elMenu.querySelectorAll('button[data-code]').forEach(b => {
                b.onclick = () => {
                    $('shipInput').value = b.dataset.code;
                    elMenu.classList.add('hidden');
                };
            });
        } catch (e) {
            elMenu.innerHTML = '<div class="px-3 py-2 text-rose-600 text-sm">Không tải được mã vận chuyển.</div>';
        }
    }

    async function applyShip(code) {
        if (!code) {
            elMsg.textContent = 'Vui lòng nhập mã vận chuyển.';
            return;
        }
        elMsg.textContent = '';
        const res = await fetch(R.shipApply, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': R.csrf,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                code,
                address_id: currentAddressId()
            })
        });
        const data = await res.json().catch(() => null);
        if (!res.ok || !data || data.ok === false) {
            elMsg.textContent = data?.message || 'Mã vận chuyển không hợp lệ.';
            elApplied.classList.add('hidden');
            elRow.classList.remove('hidden');
            computeGrand(0);
            return;
        }
        elCodeSpan.textContent = data.code || code;
        elApplied.classList.remove('hidden');
        elRow.classList.add('hidden');
        computeGrand(data.discount || 0);
        if (data.message) elMsg.textContent = data.message;
    }

    async function removeShip() {
        await fetch(R.shipRemove, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': R.csrf
            }
        });
        elApplied.classList.add('hidden');
        elRow.classList.remove('hidden');
        elMsg.textContent = '';
        computeGrand(0);
    }

    // Bind ship UI
    document.getElementById('btnShipMenu')?.addEventListener('click', () => {
        elMenu.classList.toggle('hidden');
        if (!elMenu.dataset.loaded) {
            loadShipMine();
            elMenu.dataset.loaded = '1';
        }
    });
    document.getElementById('btnShipApply')?.addEventListener('click', () => applyShip(document.getElementById('shipInput').value.trim()));
    document.getElementById('btnShipRemove')?.addEventListener('click', removeShip);
    document.addEventListener('click', (e) => {
        if (!e.target.closest('#shipWrap')) elMenu.classList.add('hidden');
    });

    // Đổi địa chỉ → quote phí ship & nếu có mã vận chuyển thì re-apply âm thầm
    document.getElementById('addrList')?.addEventListener('change', async (e) => {
        if (e.target.name !== 'address_id') return;
        const label = e.target.closest('label');
        if (!label) return;
        F.name.value = label.dataset.name || '';
        F.phone.value = label.dataset.phone || '';
        F.address.value = [label.dataset.line1, label.dataset.line2].filter(Boolean).join(', ');
        F.ward.value = label.dataset.ward || '';
        F.district.value = label.dataset.district || '';
        F.city.value = label.dataset.city || '';

        try {
            const r = await fetch(R.quote + '?address_id=' + encodeURIComponent(e.target.value), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const j = await r.json();
            if (j?.ok) {
                elFee.dataset.val = j.fee;
                elFee.textContent = j.fee > 0 ? fmt(j.fee) : 'Miễn phí';
                // re-apply totals
                if (!elRow.classList.contains('hidden') && elApplied.classList.contains('hidden')) {
                    computeGrand(0);
                } else {
                    const cur = elCodeSpan?.textContent?.trim();
                    if (cur) {
                        try {
                            await applyShip(cur);
                        } catch (_) {
                            computeGrand(0);
                        }
                    } else {
                        computeGrand(0);
                    }
                }
            }
        } catch (_) {}
    });

    // Hiệu ứng redirect cho COD
    function fxCODRedirect(url) {
        const ov = document.getElementById('codOverlay');
        const card = ov.querySelector('.card');
        ov.classList.add('show');
        requestAnimationFrame(() => card.classList.add('on'));
        setTimeout(() => {
            window.location.href = url;
        }, 1200);
    }

    // ================= Ví Cosme =================
    const wl = {
        bal: Number(document.getElementById('wlBal')?.dataset.bal || 0),
        use: document.getElementById('wlUse'),
        amount: document.getElementById('wlAmount'),
        maxBtn: document.getElementById('wlMax'),
        msg: document.getElementById('wlMsg'),
        outSpan: document.getElementById('ckWalletUse'),
        payableSpan: document.getElementById('ckPayable')
    };

    function _grandRaw() {
        return Number(document.getElementById('ckGrand')?.dataset.val || 0);
    }

    function clampWalletAmount() {
        if (!wl.use) {
            return 0;
        }
        if (!wl.use.checked) {
            if (wl.amount) {
                wl.amount.value = '';
                wl.amount.disabled = true;
            }
            if (wl.maxBtn) {
                wl.maxBtn.disabled = true;
            }
            if (wl.outSpan) {
                wl.outSpan.dataset.val = 0;
                wl.outSpan.textContent = '-0₫';
            }
            if (wl.msg) {
                wl.msg.textContent = '';
            }
            return 0;
        }
        wl.amount.disabled = false;
        wl.maxBtn.disabled = false;

        const raw = _grandRaw();
        const req = Math.max(0, Math.min(Number(wl.amount.value || 0), wl.bal, raw));
        wl.amount.value = req > 0 ? req : '';
        wl.outSpan.dataset.val = req;
        wl.outSpan.textContent = '-' + (Number(req || 0)).toLocaleString('vi-VN') + '₫';
        wl.msg.textContent = (req > wl.bal) ? 'Vượt số dư ví.' : '';
        return req;
    }

    function recomputePayable() {
        if (!wl.payableSpan) {
            return;
        }
        const raw = _grandRaw();
        const use = clampWalletAmount();
        const pay = Math.max(0, raw - use);
        wl.payableSpan.dataset.val = pay;
        wl.payableSpan.textContent = (Number(pay || 0)).toLocaleString('vi-VN') + '₫';

        // Nếu ví đủ chi trả toàn bộ → khoá radio cổng
        const methods = document.querySelectorAll('input[name=payment_method]');
        if (methods?.length) {
            if (pay === 0) methods.forEach(r => r.disabled = true);
            else methods.forEach(r => r.disabled = false);
        }
        return pay;
    }

    wl?.use?.addEventListener('change', () => {
        clampWalletAmount();
        recomputePayable();
    });
    wl?.amount?.addEventListener('input', () => {
        clampWalletAmount();
        recomputePayable();
    });
    wl?.maxBtn?.addEventListener('click', () => {
        const raw = _grandRaw();
        wl.amount.value = Math.min(wl.bal, raw);
        clampWalletAmount();
        recomputePayable();
    });

    // Hook computeGrand → sau khi đổi ship/discount sẽ tính lại payable
    const __oldComputeGrand = computeGrand;
    computeGrand = function(discount) {
        const g = __oldComputeGrand(discount);
        // elGrand đã cập nhật → dựa vào đó tính payable
        recomputePayable();
        return g;
    };
    recomputePayable();

    // ===== Submit
    const btn = document.getElementById('btnPlace');
    const spin = document.getElementById('btnSpin');
    const msg = document.getElementById('placeMsg');

    btn.addEventListener('click', async () => {
        const payable = Number(document.getElementById('ckPayable')?.dataset.val || 0);
        const walletUse = !!wl.use?.checked;
        const walletAmt = Number(wl.outSpan?.dataset.val || 0);
        const method = payable === 0 ? 'WALLET' :
            (document.querySelector('input[name=payment_method]:checked')?.value || 'COD');

        if (!F.name.value.trim() || !F.phone.value.trim() || !F.address.value.trim() || !F.city.value.trim()) {
            msg.textContent = 'Vui lòng điền đủ Họ tên, SĐT, Địa chỉ, Tỉnh/Thành.';
            return;
        }

        const payload = {
            name: F.name.value.trim(),
            phone: F.phone.value.trim(),
            email: F.email.value.trim(),
            address: F.address.value.trim(),
            ward: F.ward.value.trim(),
            district: F.district.value.trim(),
            city: F.city.value.trim(),
            payment_method: method,
            note: F.note.value.trim(),
            wallet_use: walletUse,
            wallet_amount: walletAmt
        };

        btn.disabled = true;
        spin.classList.remove('hidden');
        msg.textContent = '';
        let data;
        try {
            const res = await fetch(R.place, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': R.csrf,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            data = await res.json();
        } catch (_) {
            data = {
                ok: false,
                message: 'Không thể kết nối máy chủ.'
            };
        }
        btn.disabled = false;
        spin.classList.add('hidden');

        if (!data?.ok) {
            msg.textContent = data?.message || 'Đặt hàng thất bại.';
            return;
        }
        const target = data.redirect_url || R.ordersIndex;
        if (method === 'COD') {
            // hiệu ứng với COD
            const ov = document.getElementById('codOverlay');
            const card = ov.querySelector('.card');
            ov.classList.add('show');
            requestAnimationFrame(() => card.classList.add('on'));
            setTimeout(() => window.location.href = target, 1200);
            return;
        }
        window.location = target;
    });
</script>
@endsection