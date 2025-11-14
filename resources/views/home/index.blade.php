@extends('layouts.app')
@section('title','Cosme House')

@section('content')
@php
// Ưu tiên slides từ $banners; nếu không có dùng $heroSlides (fallback)
$slides = collect($banners ?? [])->map(function ($b) {
return [
'image' => $b->image ?? null,
'mobile_image' => $b->mobile_image ?? null,
'url' => $b->url ?: '#',
'title' => $b->title ?? '',
];
})->values()->all();

if (empty($slides)) {
$slides = $heroSlides ?? [
['image'=>null,'url'=>'#','title'=>'Banner 1'],
['image'=>null,'url'=>'#','title'=>'Banner 2'],
['image'=>null,'url'=>'#','title'=>'Banner 3'],
];
}
@endphp

<section class="max-w-7xl mx-auto px-4 mt-6 space-y-10">

    {{-- ========== HERO SLIDER ========== --}}
    <div x-data="heroCarousel({{ json_encode($slides) }})"
        x-init="init()"
        @mouseenter="pause()" @mouseleave="play()"
        @keydown.left.prevent="prev()" @keydown.right.prevent="next()"
        tabindex="0"
        class="relative group rounded-2xl overflow-hidden border border-rose-100 focus:outline-none">

        <div class="relative w-full h-0 pb-[36%] sm:pb-[28%]">
            <template x-for="(s,idx) in items" :key="idx">
                <a :href="s.url || '#'" class="absolute inset-0" x-show="i===idx" x-transition.opacity>
                    <picture>
                        <source media="(max-width: 640px)" :srcset="toUrl(s.mobile_image || s.image)">
                        <img :src="toUrl(s.image) || 'https://placehold.co/1600x576?text=Hero+Slide'"
                            :alt="s.title || ''"
                            class="w-full h-full object-cover">
                    </picture>
                </a>
            </template>
        </div>

        {{-- Dots --}}
        <div class="absolute bottom-3 left-0 right-0 flex items-center justify-center gap-2 z-20">
            <template x-for="k in items.length" :key="k">
                <button @click="go(k-1)" class="w-2.5 h-2.5 rounded-full transition"
                    :class="i===k-1 ? 'bg-white' : 'bg-white/50'"></button>
            </template>
        </div>

        {{-- Prev / Next (nằm trong banner) --}}
        <button @click="prev"
            class="absolute left-3 top-1/2 -translate-y-1/2 grid place-items-center w-10 h-10 rounded-full
                   bg-white/90 shadow hover:bg-white transition z-20
                   opacity-0 group-hover:opacity-100 sm:opacity-0 sm:group-hover:opacity-100">
            <i class="fa-solid fa-chevron-left"></i>
        </button>
        <button @click="next"
            class="absolute right-3 top-1/2 -translate-y-1/2 grid place-items-center w-10 h-10 rounded-full
                   bg-white/90 shadow hover:bg-white transition z-20
                   opacity-0 group-hover:opacity-100 sm:opacity-0 sm:group-hover:opacity-100">
            <i class="fa-solid fa-chevron-right"></i>
        </button>
    </div>

    {{-- ========== BRAND STRIP (lướt sóng) ========== --}}
    <div class="bg-white border border-rose-100 rounded-2xl p-3 overflow-hidden">
        <div class="flex items-center gap-4 overflow-x-auto snap-x snap-mandatory no-scrollbar"
            id="brandWave" data-wave=".js-brand">
            @forelse(($topBrands ?? []) as $b)
            @php
            $logo = $b->logo
            ? (\Illuminate\Support\Str::startsWith($b->logo,'http') ? $b->logo : asset('storage/'.$b->logo))
            : null;
            @endphp
            <a href="{{ route('brand.show',$b->slug) }}"
                class="js-brand min-w-[140px] snap-start shrink-0 flex items-center gap-3 px-3 py-2
                          border border-rose-100 rounded-xl bg-white hover:shadow-card transition-transform duration-200">
                @if($logo)
                <img src="{{ $logo }}" alt="{{ $b->name }}" class="w-10 h-10 object-contain">
                @else
                <div class="w-10 h-10 rounded bg-rose-50 grid place-items-center">
                    {{ strtoupper(substr($b->name,0,1)) }}
                </div>
                @endif
                <span class="text-sm font-medium">{{ $b->name }}</span>
            </a>
            @empty
            @for($i=0;$i<8;$i++)
                <div class="min-w-[140px] snap-start shrink-0 h-[56px] rounded-xl bg-rose-50/60 border border-rose-100">
        </div>
        @endfor
        @endforelse
    </div>
    </div>

    {{-- ========== FLASH SALE ========== --}}
    @if(($flashSale ?? collect())->count())
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold flex items-center gap-2">
                <span class="text-amber-500"><i class="fa-solid fa-bolt"></i></span> Flash Sale
            </h2>
            <div x-data="countdown({{ now()->copy()->addHours(6)->timestamp }})"
                x-init="start()"
                class="px-3 py-1 rounded-lg bg-rose-50 text-brand-600 text-sm">
                Kết thúc sau: <span x-text="hhmmss"></span>
            </div>
        </div>

        <div class="flex gap-4 overflow-x-auto snap-x snap-mandatory no-scrollbar pb-2"
            id="flashWave" data-wave=".js-fs">
            @foreach($flashSale as $p)
            <div class="js-fs min-w-[180px] max-w-[180px] snap-start">
                <x-product-card :product="$p" />
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ========== GỢI Ý HÔM NAY (lướt sóng) ========== --}}
    @if(($suggested ?? collect())->count())
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold">Gợi ý hôm nay</h2>
            <a href="{{ route('shop.index') }}" class="text-sm text-brand-600 hover:underline">Xem tất cả</a>
        </div>
        <div class="flex gap-4 overflow-x-auto snap-x snap-mandatory no-scrollbar pb-2"
            id="suggestWave" data-wave=".js-sg">
            @foreach($suggested as $p)
            <div class="js-sg min-w-[180px] max-w-[180px] snap-start">
                <x-product-card :product="$p" />
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ========== LƯỚI "Sản phẩm mới" ========== --}}
    <div>
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-bold">Sản phẩm mới</h2>
            <a href="{{ route('shop.index') }}" class="text-sm text-brand-600 hover:underline">Xem tất cả</a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
            @forelse(($newProducts ?? []) as $p)
            <x-product-card :product="$p" />
            @empty
            <x-empty text="Chưa có sản phẩm." />
            @endforelse
        </div>
    </div>
    {{-- ====== FLAG + MODAL ====== --}}
    @php($__justLoggedIn = \Illuminate\Support\Facades\Session::has('just_logged_in'))
    <script>
        window.__JUST_LOGGED_IN__ = @json($__justLoggedIn);
    </script>

    @include('components.promo-modal', [
    'onlyWhenJustLoggedIn' => true,
    'posters' => [
    ['img'=>asset('images/promo/poster1.png'),'title'=>'SALE 50% – Skincare Hot','desc'=>'Giảm sâu cho bộ sưu tập chăm da bán chạy nhất.','cta'=>'Mua ngay','href'=>route('shop.sale')],
    ['img'=>asset('images/promo/poster2.png'),'title'=>'MUA 2 TẶNG 1 – Makeup','desc'=>'Săn deal son/phấn/cọ, số lượng có hạn.','cta'=>'Khám phá','href'=>route('shop.sale')],
    ['img'=>asset('images/promo/poster3.png'),'title'=>'Quay là trúng!','desc'=>'Thử vận may – nhận mã giảm giá tức thì.','cta'=>'Chơi ngay','href'=>route('spin.index')],
    ],
    ])

</section>


@push('scripts')
<script>
    /* ===== Hero carousel ===== */
    document.addEventListener('alpine:init', () => {
        Alpine.data('heroCarousel', (items) => ({
            items: Array.isArray(items) ? items : [],
            i: 0,
            timer: null,
            interval: 4500,

            toUrl(p) {
                if (!p) return '';
                p = String(p).trim().replace(/\\/g, '/');
                // đã là http(s) hoặc /storage
                if (/^https?:\/\//i.test(p)) return p;
                if (p.startsWith('/storage/')) return p;

                // chuẩn hoá đường dẫn lưu trong DB
                if (p.startsWith('public/')) p = p.replace(/^public\//, 'storage/');
                if (p.startsWith('storage/')) return '{{ url(' / ') }}/' + p;

                // còn lại: coi như trong storage/
                return '{{ url(' / ') }}/' + ('storage/' + p.replace(/^\/+/, ''));
            },

            init() {
                if (this.items.length > 1) this.play();
            },
            play() {
                this.pause();
                this.timer = setInterval(() => this.next(), this.interval);
            },
            pause() {
                if (this.timer) clearInterval(this.timer);
            },
            next() {
                this.i = (this.i + 1) % this.items.length;
            },
            prev() {
                this.i = (this.i - 1 + this.items.length) % this.items.length;
            },
            go(k) {
                this.i = k;
            }
        }));
    });

    /* ===== Countdown cho Flash Sale ===== */
    function countdown(targetTs) {
        return {
            hhmmss: '00:00:00',
            target: targetTs * 1000,
            timer: null,
            start() {
                const tick = () => {
                    const remain = this.target - Date.now();
                    if (remain <= 0) {
                        this.hhmmss = '00:00:00';
                        clearInterval(this.timer);
                        return;
                    }
                    const h = String(Math.floor(remain / 3600000)).padStart(2, '0');
                    const m = String(Math.floor(remain % 3600000 / 60000)).padStart(2, '0');
                    const s = String(Math.floor(remain % 60000 / 1000)).padStart(2, '0');
                    this.hhmmss = `${h}:${m}:${s}`;
                };
                tick();
                this.timer = setInterval(tick, 1000);
            }
        }
    }

    /* ===== Hiệu ứng "lướt sóng" – chỉ item hover + 2 hàng xóm ===== */
    function wave(group) {
        const selector = group.dataset.wave || '.card';
        const items = [...group.querySelectorAll(selector)];
        const shift = (el, dy) => el.style.transform = `translateY(${dy}px)`;
        const reset = (el) => el.style.transform = '';

        items.forEach((el, idx) => {
            el.addEventListener('mouseenter', () => {
                items.forEach(reset);
                shift(el, -8);
                if (items[idx - 1]) shift(items[idx - 1], -4);
                if (items[idx + 1]) shift(items[idx + 1], -4);
            });
            el.addEventListener('mouseleave', () => items.forEach(reset));
        });
    }

    /* Kích hoạt wave cho 3 cụm: Brand, Flash Sale, Gợi ý hôm nay */
    ['brandWave', 'flashWave', 'suggestWave'].forEach(id => {
        const el = document.getElementById(id);
        if (el) wave(el);
    });
</script>
@endpush

@endsection
