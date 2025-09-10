@extends('layouts.app')
@section('title','Tích điểm thành viên')

@section('content')
@php
$balance = (int) ($balance ?? 0);
$earnLast30 = (int) ($earnLast30 ?? 0); // đã tính ở controller
$burnTotal = (int) ($burnTotal ?? 0); // đã tính ở controller
$toNext = 1000 - ($balance % 1000 === 0 ? 1000 : $balance % 1000);
$pct = min(100, ($balance % 1000) / 10); // tiến độ mốc 1000 kế
$maxRedeemVnd = $balance * 10; // 1 xu = 10đ
@endphp

<style>
    /* ---- Nền + glass nhẹ ---- */
    .hero-grad {
        background:
            radial-gradient(1200px 600px at 10% -10%, rgba(253, 164, 175, .35), transparent 60%),
            radial-gradient(900px 500px at 90% 0%, rgba(244, 114, 182, .25), transparent 60%),
            linear-gradient(180deg, #fff, #fff);
    }

    .glass {
        background: rgba(255, 255, 255, .92);
        backdrop-filter: saturate(140%) blur(10px)
    }

    .shadow-soft {
        box-shadow: 0 10px 30px rgba(0, 0, 0, .06)
    }

    /* ---- Vòng tiến độ + đồng xu ---- */
    .ring-wrap {
        position: relative;
        width: 108px;
        height: 108px
    }

    .ring {
        position: absolute;
        inset: 0;
        border-radius: 999px;
        padding: 2px;
        background: conic-gradient(#fb7185 var(--p, 0%), #ffe4e6 0);
        box-shadow: 0 10px 30px rgba(251, 113, 133, .25);
    }

    .ring>.inner {
        border-radius: 999px;
        height: 100%;
        background: #fff;
        display: grid;
        place-items: center
    }

    .coin {
        width: 64px;
        height: 64px;
        border-radius: 999px;
        display: grid;
        place-items: center;
        background: radial-gradient(circle at 30% 30%, #fff7ed, #fde68a 40%, #f59e0b 70%);
        box-shadow: inset 0 2px 5px rgba(0, 0, 0, .06), 0 10px 20px rgba(245, 158, 11, .25);
    }

    /* ---- Badges & buttons (không dùng @apply) ---- */
    .pill {
        display: inline-block;
        padding: .25rem .5rem;
        border-radius: 999px;
        font-size: .75rem;
        font-weight: 700
    }

    .btn-cta {
        border: none;
        border-radius: 12px;
        padding: .75rem 1.25rem;
        color: #fff;
        font-weight: 600;
        cursor: pointer;
        background: linear-gradient(135deg, #f43f5e, #fb7185);
        transition: filter .15s, transform .15s;
        box-shadow: 0 12px 30px rgba(244, 63, 94, .35);
    }

    .btn-cta:hover {
        filter: brightness(1.05);
        transform: translateY(-1px)
    }

    .btn-ghost {
        border: none;
        border-radius: 12px;
        padding: .5rem .75rem;
        background: #ffe4e6;
        color: #be123c;
        font-weight: 600;
        cursor: pointer;
    }

    .btn-ghost:hover {
        background: #fecdd3
    }

    .chip {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: .375rem .625rem;
        font-size: .875rem;
        cursor: pointer;
    }

    .chip:hover {
        background: #f9fafb
    }

    .empty {
        padding: 2.5rem;
        text-align: center;
        color: #6b7280
    }
</style>

<div class="hero-grad">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-10">
        <!-- Hero -->
        <div class="glass rounded-3xl p-6 md:p-8 shadow-soft relative overflow-hidden">
            <div class="grid md:grid-cols-2 gap-6 items-center">
                <div class="min-w-0">
                    <div class="flex items-center gap-3">
                        <!-- Coin icon -->
                        <div class="coin">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle cx="12" cy="12" r="9.5" stroke="#a16207" opacity=".35" />
                                <path d="M12 7v10M7 12h10" stroke="#78350f" stroke-width="1.6" stroke-linecap="round" />
                            </svg>
                        </div>
                        <h1 class="text-2xl md:text-3xl font-black tracking-tight">Xu tích điểm</h1>
                    </div>
                    <div class="mt-2 text-gray-600">
                        Tỉ lệ: <b>1.000&nbsp;VND = 1 xu</b> &middot; Đổi: <b>1 xu = 10&nbsp;VND</b>. Tích khi đơn
                        <span class="font-medium">đã thanh toán & hoàn tất</span>.
                    </div>

                    <!-- Stats -->
                    <div class="mt-5 grid grid-cols-3 gap-3">
                        <div class="rounded-2xl border p-4 bg-white/90">
                            <div class="text-sm text-gray-500">Số dư</div>
                            <div class="text-2xl font-extrabold" id="balanceText">{{ number_format($balance) }} xu</div>
                        </div>
                        <div class="rounded-2xl border p-4 bg-white/90">
                            <div class="text-sm text-gray-500">Có thể đổi</div>
                            <div class="text-2xl font-extrabold" id="maxRedeemText">{{ number_format($maxRedeemVnd) }}đ</div>
                        </div>
                        <div class="rounded-2xl border p-4 bg-white/90">
                            <div class="text-sm text-gray-500">Điểm nhận 30 ngày qua</div>
                            <div class="text-2xl font-extrabold" style="color:#059669">+{{ number_format($earnLast30) }}</div>
                        </div>
                    </div>
                </div>

                <!-- Balance ring + redeem -->
                <div class="flex flex-col items-center md:items-end gap-3">
                    <div class="ring-wrap" title="Còn {{ number_format($toNext) }} xu tới mốc 1.000">
                        <div class="ring" style="--p: {{ $pct }}%">
                            <div class="inner">
                                <div class="coin">
                                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M12 7v10M7 12h10" stroke="#78350f" stroke-width="1.8" stroke-linecap="round" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="text-xs text-gray-500">Còn <b>{{ number_format($toNext) }}</b> xu để đạt mốc 1.000</div>

                    <form class="w-full md:w-auto flex flex-col sm:flex-row items-stretch md:items-center gap-3"
                        method="post" action="{{ route('account.points.redeem') }}">
                        @csrf
                        <div class="w-full sm:w-56">
                            <label class="block text-sm text-gray-600 mb-1">Nhập số xu muốn đổi</label>
                            <div class="flex items-center gap-2">
                                <input id="pointsInput" type="number" name="points" min="1" step="1" max="{{ $balance }}"
                                    class="w-full rounded-xl border px-3 py-2 focus:ring-rose-500 focus:border-rose-500"
                                    placeholder="vd: 200">
                                <button type="button" data-val="100" class="chip" aria-label="+100 xu">+100</button>
                            </div>
                            <input id="pointsRange" type="range" min="0" max="{{ $balance }}" value="0"
                                class="mt-2 w-full accent-rose-500">
                            <div class="mt-1 text-xs text-gray-500">
                                Sẽ tạo mã giảm: <b id="willDiscount">0đ</b> &nbsp;•&nbsp; Đơn tối thiểu: <b id="willMin">100.000đ</b>
                            </div>
                        </div>
                        <button class="btn-cta self-end sm:self-auto">Đổi ra mã giảm giá</button>
                    </form>

                    <div class="flex flex-wrap justify-center md:justify-end gap-2">
                        @foreach([200,500,1000,'max'] as $v)
                        <button type="button" class="btn-ghost" data-quick="{{ $v }}">{{ is_numeric($v) ? '+'.$v.' xu' : 'Tất cả' }}</button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- History -->
        <div class="mt-8">
            <div class="flex items-center justify-between mb-3">
                <div class="text-lg font-semibold">Lịch sử gần đây</div>
                <div class="text-sm text-gray-500">Đã đổi tổng: <b style="color:#e11d48">-{{ number_format($burnTotal) }} xu</b></div>
            </div>

            <div class="overflow-hidden rounded-2xl border bg-white shadow-soft">
                @if(($history ?? collect())->count() === 0)
                <div class="empty">Chưa có giao dịch.</div>
                @else
                <table class="w-full text-sm">
                    <thead style="background:#ffe4e6; color:#374151">
                        <tr>
                            <th class="text-left p-3">Thời gian</th>
                            <th class="text-left p-3">Loại</th>
                            <th class="text-right p-3">± Điểm</th>
                            <th class="text-left p-3">Trạng thái</th>
                            <th class="text-left p-3">Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($history as $tx)
                        @php
                        $delta = (int)$tx->delta;
                        $badgeType = $tx->type === 'earn' ? 'background:#ecfdf5;color:#047857'
                        : ($tx->type === 'burn' ? 'background:#ffe4e6;color:#be123c'
                        : 'background:#fffbeb;color:#b45309');
                        $badgeStatus = $tx->status === 'confirmed' ? 'background:#ecfdf5;color:#047857'
                        : ($tx->status === 'pending' ? 'background:#fffbeb;color:#b45309'
                        : 'background:#f3f4f6;color:#4b5563');
                        @endphp
                        <tr class="border-t hover:bg-gray-50/60">
                            <td class="p-3 text-gray-600">{{ optional($tx->created_at)->format('d/m/Y H:i') }}</td>
                            <td class="p-3"><span class="pill" style="{{ $badgeType }}">{{ $tx->type }}</span></td>
                            <td class="p-3 text-right font-semibold" style="color:{{ $delta>=0 ? '#059669' : '#e11d48' }}">
                                {{ $delta>=0 ? '+' : '' }}{{ number_format($delta) }}
                            </td>
                            <td class="p-3"><span class="pill" style="{{ $badgeStatus }}">{{ $tx->status }}</span></td>
                            <td class="p-3 text-gray-600">{{ $tx->meta['order_code'] ?? ($tx->meta['reason'] ?? '') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Đồng bộ slider/input + preview số tiền & min order --}}
<script>
    (function() {
        const fmt = n => n.toLocaleString('vi-VN') + 'đ';
        const input = document.getElementById('pointsInput');
        const range = document.getElementById('pointsRange');
        const willDiscount = document.getElementById('willDiscount');
        const willMin = document.getElementById('willMin');
        const chip = document.querySelector('[data-val="100"]');
        const quicks = document.querySelectorAll('[data-quick]');

        const clamp = (v, min, max) => Math.max(min, Math.min(max, v));

        function recalc() {
            const p = parseInt(input.value || 0, 10) || 0;
            const d = p * 10; // 1 xu = 10đ
            willDiscount.textContent = fmt(d);
            const minOrder = Math.max(100000, Math.round(d / 0.3)); // ~<=30%
            willMin.textContent = fmt(minOrder);
            range.value = clamp(p, parseInt(range.min, 10), parseInt(range.max, 10));
        }
        input.addEventListener('input', recalc);
        range.addEventListener('input', () => {
            input.value = range.value;
            recalc();
        });
        chip?.addEventListener('click', () => {
            const max = parseInt(range.max, 10);
            input.value = clamp((parseInt(input.value || 0, 10) || 0) + 100, 0, max);
            recalc();
        });
        quicks.forEach(q => q.addEventListener('click', () => {
            const v = q.dataset.quick === 'max' ? parseInt(range.max, 10) : parseInt(q.dataset.quick, 10);
            input.value = v;
            recalc();
        }));
        recalc();

        @if(session('ok'))
        setTimeout(() => {
            document.querySelector('.glass')?.animate(
                [{
                        boxShadow: '0 0 0 0 rgba(16,185,129,0)'
                    },
                    {
                        boxShadow: '0 0 0 14px rgba(16,185,129,.25)'
                    },
                    {
                        boxShadow: '0 0 0 0 rgba(16,185,129,0)'
                    }
                ], {
                    duration: 900,
                    easing: 'ease-out'
                }
            );
        }, 100);
        @endif
    })();
</script>
@endsection