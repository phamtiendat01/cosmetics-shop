@extends('layouts.app')
@section('title','Hồ sơ làn da')

@push('styles')
<style>
    .card {
        background: #fff;
        border-radius: 16px;
        border: 1px solid rgba(0, 0, 0, .05);
        box-shadow: 0 6px 24px -12px rgba(0, 0, 0, .25)
    }

    .soft {
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        background: #f9fafb
    }

    .bar {
        height: 8px;
        border-radius: 9999px;
        background: #e5e7eb;
        overflow: hidden
    }

    .bar>i {
        display: block;
        height: 8px;
        border-radius: 9999px;
        background: #111827
    }

    .gauge {
        --p: 0;
        --c: #f43f5e;
        width: 118px;
        height: 118px;
        border-radius: 9999px;
        background: conic-gradient(var(--c) calc(var(--p)*1%), #e5e7eb 0);
        position: relative
    }

    .gauge::after {
        content: "";
        position: absolute;
        inset: 9px;
        background: #fff;
        border-radius: 9999px;
        box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .05)
    }

    .gauge .val {
        position: absolute;
        inset: 0;
        display: grid;
        place-items: center;
        font-weight: 700;
        color: #111827
    }

    .grid-photos {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .75rem
    }

    .thumb {
        aspect-ratio: 1/1;
        border-radius: 12px;
        overflow: hidden;
        background: #fff;
        border: 1px solid #e5e7eb
    }

    .thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform .2s
    }

    .thumb:hover img {
        transform: scale(1.04)
    }

    .modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .7);
        display: grid;
        place-items: center;
        z-index: 999
    }

    .modal-img {
        max-width: min(92vw, 1000px);
        max-height: 86vh;
        border-radius: 12px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, .4)
    }
</style>
@endpush

@section('content')
@php
// $history: mảng [{id,time,type,metrics,photos[]}]
$historyData = $history ?? [];
@endphp

<div
    x-data='skinHistory({ history: JSON.parse($el.dataset.history) })'
    data-history='@json($historyData)'
    class="max-w-7xl mx-auto px-4 pt-6 pb-16">

    {{-- HERO --}}
    <section class="mb-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-brand-100 text-brand-700 text-xs">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> Hồ sơ làn da
                </div>
                <h1 class="text-2xl sm:text-[28px] font-bold mt-2">Tổng quan làn da của bạn</h1>
                <div class="text-[13px] text-ink/70 mt-1">Cập nhật: <span class="font-medium">{{ $latestUpdated ?? '—' }}</span></div>
            </div>
            <a href="{{ url('/skin-test') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-brand-600 text-white hover:bg-brand-700 shadow">
                <i class="fa-solid fa-rotate"></i> Quét lại
            </a>
        </div>
    </section>

    <section class="grid lg:grid-cols-12 gap-6">
        {{-- LEFT: timeline --}}
        <div class="lg:col-span-5 card p-4">
            <div class="flex items-center justify-between mb-2">
                <div class="font-semibold">Lịch sử quét</div>
                <div class="text-xs text-gray-500">Tối đa 12 bản gần nhất</div>
            </div>

            <template x-if="history.length===0">
                <div class="soft p-4 text-gray-600 text-sm">Chưa có lịch sử. Hãy quét da để bắt đầu theo dõi hành trình của bạn.</div>
            </template>

            <div class="mt-2 space-y-3 max-h-[70vh] overflow-auto pr-1">
                <template x-for="(h,idx) in history" :key="h.id">
                    <button
                        class="w-full text-left p-3 rounded-xl border flex gap-3"
                        @click="select(idx)"
                        :class="idx===selected ? 'border-brand-400 bg-rose-50' : 'border-gray-200 hover:bg-gray-50'">
                        <div class="w-[62px] h-[62px] rounded-lg overflow-hidden bg-white border border-gray-200 flex-shrink-0">
                            <template x-if="(h.photos||[]).length">
                                <img :src="h.photos[0]" alt="" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!(h.photos||[]).length">
                                <div class="w-full h-full grid place-items-center text-gray-400"><i class="fa-regular fa-image"></i></div>
                            </template>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-500" x-text="h.time"></div>
                                <div class="text-xs px-2 py-0.5 rounded-full" :class="badgeClass(h.type)">
                                    <span x-text="prettyType(h.type)"></span>
                                </div>
                            </div>
                            <div class="grid grid-cols-4 gap-2 mt-2 text-[12px]">
                                <div class="soft p-2">
                                    <div class="text-gray-500">Oil</div>
                                    <div class="font-semibold" x-text="pct(h.metrics?.oiliness)"></div>
                                </div>
                                <div class="soft p-2">
                                    <div class="text-gray-500">Dry</div>
                                    <div class="font-semibold" x-text="pct(h.metrics?.dryness)"></div>
                                </div>
                                <div class="soft p-2">
                                    <div class="text-gray-500">Red</div>
                                    <div class="font-semibold" x-text="pct(h.metrics?.redness)"></div>
                                </div>
                                <div class="soft p-2">
                                    <div class="text-gray-500">Acne</div>
                                    <div class="font-semibold" x-text="pct(h.metrics?.acne_score)"></div>
                                </div>
                            </div>
                        </div>
                    </button>
                </template>
            </div>
        </div>

        {{-- RIGHT: detail --}}
        <div class="lg:col-span-7 card p-5" x-show="current">
            <div class="flex items-center justify-between">
                <div class="font-semibold">Chi tiết lần quét</div>
                <div class="text-xs text-gray-500" x-text="current?.time || ''"></div>
            </div>

            <div class="mt-3 inline-flex items-center gap-2 px-2 py-1 rounded-full bg-gray-900 text-white text-sm">
                <i class="fa-regular fa-face-smile-beam"></i>
                <span x-text="prettyType(current?.type)"></span>
            </div>

            <div class="mt-4 grid md:grid-cols-2 gap-6">
                <div>
                    <div class="text-sm text-gray-500 mb-2">Ảnh đã quét</div>
                    <div class="grid-photos">
                        <template x-for="(p,i) in (current?.photos||[])" :key="i">
                            <div class="thumb cursor-zoom-in" @click="openPhoto(p)"><img :src="p" alt=""></div>
                        </template>
                        <template x-if="!(current?.photos||[]).length">
                            <div class="soft p-4 text-gray-600 text-sm col-span-3">Không có ảnh kèm lần quét này.</div>
                        </template>
                    </div>
                </div>

                <div>
                    <div class="text-sm text-gray-500 mb-2">Chỉ số lần quét</div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="soft p-3 flex items-center gap-3">
                            <div class="gauge" :style="`--p:${pctNum(current?.metrics?.oiliness)};--c:#ef4444`">
                                <div class="val" x-text="pct(current?.metrics?.oiliness)"></div>
                            </div>
                            <div>
                                <div class="font-semibold">Oiliness</div>
                                <div class="bar mt-2"><i :style="`width:${pctNum(current?.metrics?.oiliness)}%`"></i></div>
                            </div>
                        </div>
                        <div class="soft p-3 flex items-center gap-3">
                            <div class="gauge" :style="`--p:${pctNum(current?.metrics?.dryness)};--c:#0ea5e9`">
                                <div class="val" x-text="pct(current?.metrics?.dryness)"></div>
                            </div>
                            <div>
                                <div class="font-semibold">Dryness</div>
                                <div class="bar mt-2"><i :style="`width:${pctNum(current?.metrics?.dryness)}%`"></i></div>
                            </div>
                        </div>
                        <div class="soft p-3 flex items-center gap-3">
                            <div class="gauge" :style="`--p:${pctNum(current?.metrics?.redness)};--c:#f97316`">
                                <div class="val" x-text="pct(current?.metrics?.redness)"></div>
                            </div>
                            <div>
                                <div class="font-semibold">Redness</div>
                                <div class="bar mt-2"><i :style="`width:${pctNum(current?.metrics?.redness)}%`"></i></div>
                            </div>
                        </div>
                        <div class="soft p-3 flex items-center gap-3">
                            <div class="gauge" :style="`--p:${pctNum(current?.metrics?.acne_score)};--c:#22c55e`">
                                <div class="val" x-text="pct(current?.metrics?.acne_score)"></div>
                            </div>
                            <div>
                                <div class="font-semibold">Acne</div>
                                <div class="bar mt-2"><i :style="`width:${pctNum(current?.metrics?.acne_score)}%`"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Modal xem ảnh --}}
    <div x-show="viewer" x-transition.opacity class="modal-backdrop" @click="viewer=null">
        <img :src="viewer" class="modal-img" @click.stop>
    </div>
</div>
@endsection

@push('scripts')
@verbatim
<script>
    function skinHistory(init) {
        const badge = (t) => {
            const base = 'px-2 py-0.5';
            switch (t) {
                case 'oily':
                    return base + ' bg-rose-100 text-rose-700';
                case 'dry':
                    return base + ' bg-sky-100 text-sky-700';
                case 'combination':
                    return base + ' bg-emerald-100 text-emerald-700';
                case 'sensitive':
                    return base + ' bg-amber-100 text-amber-700';
                default:
                    return base + ' bg-gray-100 text-gray-700';
            }
        };
        return {
            history: Array.isArray(init.history) ? init.history : [],
            selected: 0,
            viewer: null,
            get current() {
                return this.history[this.selected] || null;
            },
            select(i) {
                this.selected = i;
            },
            openPhoto(u) {
                this.viewer = u;
            },
            pct(v) {
                const n = Math.round(((v || 0) * 100));
                return isFinite(n) ? `${n}%` : '—';
            },
            pctNum(v) {
                const n = Math.round(((v || 0) * 100));
                return isFinite(n) ? n : 0;
            },
            prettyType(t) {
                const map = {
                    oily: 'Da thiên dầu',
                    dry: 'Da khô',
                    combination: 'Da hỗn hợp',
                    sensitive: 'Da nhạy cảm'
                };
                return map[(t || '').toString()] || 'Đang xác định';
            },
            badgeClass(t) {
                return badge(t);
            }
        }
    }
</script>
@endverbatim
@endpush