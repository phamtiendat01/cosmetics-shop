@extends('layouts.app')
@section('title','Mã vận chuyển')

@section('content')
<style>
    /* ====== Card (giữ style giống coupons, tinh chỉnh tông xanh ship) ====== */
    .gcard {
        position: relative;
        border-radius: 1rem;
        padding: 1px;
        background: linear-gradient(135deg, #bae6fd, #7dd3fc, #38bdf8);
        overflow: visible;
        isolation: isolate
    }

    .gcard>.inner {
        border-radius: inherit;
        background: rgba(255, 255, 255, .96);
        backdrop-filter: saturate(140%) blur(8px);
        overflow: visible
    }

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
        white-space: nowrap;
        font-weight: 800;
        font-size: 14px;
        letter-spacing: .2px;
        color: #fff;
        background: linear-gradient(135deg, #38bdf8, #0ea5e9);
        box-shadow: 0 0 0 3px #fff, 0 10px 22px rgba(14, 165, 233, .35);
        z-index: 40;
        pointer-events: none
    }

    .chip {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .15rem .5rem;
        border-radius: .6rem;
        font-size: .75rem;
        background: #f1f5f9;
        color: #334155;
        border: 1px solid #e2e8f0
    }

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
    <h1 class="text-2xl font-black tracking-tight mb-2">Mã vận chuyển</h1>
    <p class="text-gray-500 mb-6">Các mã phí ship bạn đã “bốc” được từ Hộp quà bí ẩn.</p>

    @if(($items ?? collect())->count())
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($items as $it)
        @php
        $isPercent = strtolower($it->discount_type ?? '') === 'percent';
        $valueStr = $isPercent
        ? (rtrim(rtrim(number_format($it->amount,2), '0'), '.') . '%')
        : number_format((float)$it->amount, 0, ',', '.') . 'đ';
        $maxStr = $it->max_discount ? ('Tối đa ' . number_format((float)$it->max_discount,0,',','.') . 'đ') : null;
        $minStr = $it->min_order ? ('Đơn từ ' . number_format((float)$it->min_order,0,',','.') . 'đ') : 'Không yêu cầu';

        $now = now();
        $start = $it->start_at ? \Carbon\Carbon::parse($it->start_at) : null;
        $end = $it->end_at ? \Carbon\Carbon::parse($it->end_at) : null;
        $statusOk = (int)$it->is_active === 1 && (!$start || $start->lte($now)) && (!$end || $end->gte($now));

        $times = max(1,(int)($it->times ?? 1));
        $used = max(0,(int)($it->used_count ?? 0));
        $left = max(0,$times - $used);

        $uid = 'sv'.($loop->index ?? 0).'_'.$it->id;
        $regions = collect(json_decode($it->regions ?? '[]', true) ?: []);
        $carriers = collect(json_decode($it->carriers ?? '[]', true) ?: []);
        @endphp

        <div class="gcard shadow-sm hover:shadow-xl transition">
            @if($times>1)
            <div class="badge-x" title="Số lần lưu">×{{ $times }}</div>
            @endif

            <div class="inner p-5">
                <div class="flex gap-4">
                    {{-- icon vé xếp lớp (reuse) --}}
                    <div class="relative w-12 shrink-0">
                        @for($i=0;$i<min(3,$times);$i++)
                            <svg width="48" height="48" viewBox="0 0 48 48"
                            class="absolute top-0 left-0 drop-shadow-sm"
                            style="transform: translate({{ $i*4 }}px, {{ -$i*4 }}px) rotate({{ -$i*2 }}deg)">
                            <defs>
                                <linearGradient id="g{{$uid}}{{$i}}" x1="0" x2="1" y1="0" y2="1">
                                    <stop offset="0%" stop-color="#7dd3fc" />
                                    <stop offset="100%" stop-color="#38bdf8" />
                                </linearGradient>
                            </defs>
                            <path d="M8 10 h32 a2 2 0 0 1 2 2 v6 a4 4 0 0 0 0 12 v6 a2 2 0 0 1-2 2 H8 a2 2 0 0 1-2-2 v-6 a4 4 0 0 0 0-12 v-6 a2 2 0 0 1 2-2 z"
                                fill="url(#g{{$uid}}{{$i}})"></path>
                            <path d="M16 10 v28 M32 10 v28" stroke="rgba(255,255,255,.7)" stroke-width="2" stroke-dasharray="4 4" />
                            </svg>
                            @endfor
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-lg font-extrabold tracking-tight">{{ $it->code }}</span>
                            @if($statusOk)
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700">Đang hiệu lực</span>
                            @else
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">Không hiệu lực</span>
                            @endif>
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-sky-50 text-sky-700">Mã vận chuyển</span>
                        </div>

                        <div class="text-sm text-gray-700 mt-1">
                            <span class="font-semibold">{{ $isPercent ? 'Giảm' : 'Trừ' }} {{ $valueStr }} phí ship</span>
                            @if($maxStr) • <span>{{ $maxStr }}</span>@endif
                            • <span>{{ $minStr }}</span>
                        </div>

                        {{-- carriers / regions --}}
                        <div class="flex flex-wrap gap-2 mt-2">
                            @if($carriers->count())
                            <span class="chip">Hãng: {{ $carriers->take(2)->implode(', ') }}@if($carriers->count()>2) +{{ $carriers->count()-2 }}@endif</span>
                            @endif
                            @if($regions->count())
                            <span class="chip">Khu vực: {{ $regions->take(2)->implode(', ') }}@if($regions->count()>2) +{{ $regions->count()-2 }}@endif</span>
                            @endif
                        </div>

                        {{-- dùng/giới hạn --}}
                        <div class="mt-2 text-xs text-gray-500">
                            Đã dùng: <b>{{ $used }}</b> / {{ $times }} @if($left>0) • <span class="text-emerald-600 font-semibold">Còn lại: {{ $left }}</span>@endif
                        </div>

                        {{-- thời gian --}}
                        <div class="text-xs text-gray-400 mt-1">
                            @if($start) Bắt đầu: {{ $start->format('d/m/Y H:i') }} @endif
                            @if($end) • Hết hạn: {{ $end->format('d/m/Y H:i') }} @endif
                        </div>

                        {{-- actions --}}
                        <div class="mt-3 flex gap-2">
                            <button class="px-3 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-sm"
                                onclick="navigator.clipboard.writeText('{{ $it->code }}'); miniToast('Đã copy mã');">
                                Copy mã
                            </button>
                            <a href="{{ route('checkout.index') }}"
                                class="px-3 py-2 rounded-lg bg-sky-600 hover:bg-sky-700 text-white text-sm">
                                Dùng ngay
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <x-empty text="Bạn chưa lưu mã vận chuyển nào." />
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
        }, {
            opacity: 1,
            transform: 'translateX(-50%) translateY(0)'
        }], {
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
            }).onfinish = () => el.remove()
        }, 1200);
    }
</script>
@endsection