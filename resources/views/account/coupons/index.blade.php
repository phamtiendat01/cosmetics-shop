@extends('layouts.app')
@section('title','Mã giảm giá')

@section('content')
<style>
    /* Card viền gradient + glass */
    .gcard {
        position: relative;
        border-radius: 1rem;
        padding: 1px;
        background: linear-gradient(135deg, #fecdd3, #fda4af, #fb7185);
        overflow: visible;
        /* đừng clip badge */
        isolation: isolate;
        /* để z-index của badge nổi hẳn lên */
    }

    .gcard>.inner {
        border-radius: inherit;
        background: rgba(255, 255, 255, .96);
        backdrop-filter: saturate(140%) blur(8px);
        overflow: visible;
    }

    /* BADGE ×N – tròn, hơi nghiêng, màu hồng; text luôn 1 dòng */
    .badge-x {
        position: absolute;
        right: 0;
        top: 0;
        transform: translate(12px, -12px) rotate(-12deg);
        width: 36px;
        height: 36px;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        /* ⟵ giữ “×4” cùng 1 hàng */
        white-space: nowrap;
        /* ⟵ không cho xuống dòng */
        font-weight: 800;
        font-size: 14px;
        letter-spacing: .2px;
        color: #ffffff;
        background: linear-gradient(135deg, #fb7185, #f43f5e);
        box-shadow:
            0 0 0 3px #fff,
            /* viền trắng nổi bật */
            0 10px 22px rgba(244, 63, 94, .35);
        z-index: 40;
        pointer-events: none;
    }

    /* Mini toast khi copy */
    .mini-toast {
        position: fixed;
        bottom: 24px;
        left: 50%;
        transform: translateX(-50%) translateY(10px);
        background: #111827;
        color: #fff;
        border-radius: 12px;
        padding: 8px 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .18);
        opacity: 0;
        z-index: 9999
    }
</style>

<div class="max-w-6xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-black tracking-tight mb-6">Mã giảm giá</h1>

    @if(($coupons ?? collect())->count())
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($coupons as $c)
        @php
        $isPercent = strtolower($c->discount_type ?? '') === 'percent';
        $valueStr = $isPercent ? (rtrim(rtrim(number_format($c->discount_value,2), '0'), '.') . '%')
        : number_format((float)$c->discount_value, 0, ',', '.') . 'đ';
        $maxStr = $c->max_discount ? ('Tối đa ' . number_format((float)$c->max_discount,0,',','.') . 'đ') : null;
        $minStr = $c->min_order_total ? ('Đơn từ ' . number_format((float)$c->min_order_total,0,',','.') . 'đ') : 'Không yêu cầu';
        $now = now();
        $validFrom = $c->starts_at ? \Carbon\Carbon::parse($c->starts_at) : null;
        $validTo = $c->ends_at ? \Carbon\Carbon::parse($c->ends_at) : null;
        $statusOk = (int)$c->is_active === 1 && (!$validFrom || $validFrom->lte($now)) && (!$validTo || $validTo->gte($now));

        $times = (int)($c->times ?? 1);
        $cap = min(3,$times); // xếp tối đa 3 vé cho gọn
        $uid = 'cp'.($loop->index ?? 0).'_'.($c->coupon_id ?? 'x');
        @endphp

        <div class="gcard shadow-sm hover:shadow-xl transition">
            @if($times>1)
            <!-- badge: luôn 1 dòng “×N” -->
            <div class="badge-x" aria-label="Số lượng">×{{ $times }}</div>
            @endif

            <div class="inner p-5">
                <div class="flex gap-4">
                    {{-- icon vé xếp lớp (đẹp & gọn) --}}
                    <div class="relative w-12 shrink-0">
                        @for($i=0;$i<$cap;$i++)
                            <svg width="48" height="48" viewBox="0 0 48 48"
                            class="absolute top-0 left-0 drop-shadow-sm"
                            style="transform: translate({{ $i*4 }}px, {{ -$i*4 }}px) rotate({{ -$i*2 }}deg)">
                            <defs>
                                <linearGradient id="g{{$uid}}{{$i}}" x1="0" x2="1" y1="0" y2="1">
                                    <stop offset="0%" stop-color="#fda4af" />
                                    <stop offset="100%" stop-color="#fb7185" />
                                </linearGradient>
                            </defs>
                            <path d="M8 10 h32 a2 2 0 0 1 2 2 v6 a4 4 0 0 0 0 12 v6 a2 2 0 0 1-2 2 H8 a2 2 0 0 1-2-2 v-6 a4 4 0 0 0 0-12 v-6 a2 2 0 0 1 2-2 z"
                                fill="url(#g{{$uid}}{{$i}})"></path>
                            <path d="M16 10 v28 M32 10 v28" stroke="rgba(255,255,255,.7)" stroke-width="2" stroke-dasharray="4 4" />
                            </svg>
                            @endfor
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="text-lg font-extrabold tracking-tight">{{ $c->code }}</span>
                            @if($statusOk)
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700">Đang hiệu lực</span>
                            @else
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">Không hiệu lực</span>
                            @endif
                        </div>

                        <div class="text-sm text-gray-700 mt-1">
                            <span class="font-semibold">{{ $isPercent ? 'Giảm' : 'Trừ' }} {{ $valueStr }}</span>
                            @if($maxStr) • <span>{{ $maxStr }}</span>@endif
                            • <span>{{ $minStr }}</span>
                        </div>

                        @if($c->name || $c->description)
                        <div class="text-sm text-gray-500 mt-1 line-clamp-2">
                            {{ $c->name ?? '' }} {{ $c->description ? '– '.$c->description : '' }}
                        </div>
                        @endif

                        <div class="text-xs text-gray-400 mt-2">
                            @if($validFrom) Bắt đầu: {{ $validFrom->format('d/m/Y H:i') }} @endif
                            @if($validTo) • Hết hạn: {{ $validTo->format('d/m/Y H:i') }} @endif
                        </div>

                        <div class="mt-3">
                            <button class="px-3 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-sm"
                                onclick="navigator.clipboard.writeText('{{ $c->code }}'); miniToast('Đã copy mã');">
                                Copy mã
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <x-empty text="Bạn chưa lưu mã nào." />
    @endif
</div>

<script>
    function miniToast(text) {
        const el = document.createElement('div');
        el.className = 'mini-toast';
        el.textContent = text;
        document.body.appendChild(el);
        el.animate([{
                opacity: 0,
                transform: 'translateX(-50%) translateY(10px)'
            },
            {
                opacity: 1,
                transform: 'translateX(-50%) translateY(0)'
            }
        ], {
            duration: 180,
            fill: 'forwards'
        });
        setTimeout(() => {
            el.animate([{
                opacity: 1
            }, {
                opacity: 0
            }], {
                duration: 220,
                fill: 'forwards'
            }).onfinish = () => el.remove();
        }, 1200);
    }
</script>
@endsection