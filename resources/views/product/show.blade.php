@extends('layouts.app')
@section('title', ($product->name ?? 'Sản phẩm').' | Cosme House')

@section('content')
@php
// Ảnh chính + gallery
$main = $product->image
? asset('storage/'.$product->image)
: ($product->thumbnail ? asset('storage/'.$product->thumbnail) : null);

$images = collect($product->gallery ?? [])
->map(fn($p) => str_starts_with($p,'http') ? $p : asset('storage/'.$p));
if ($main && $images->isEmpty()) $images = collect([$main]);

// Giá min từ variants (hoặc fallback)
$min = $product->variants->min('price');
$minCmp = $product->variants->min('compare_at_price');
$minForPrompt = $min ?? ($product->price ?? 0);

// Tóm tắt ngắn: ưu tiên short_desc; nếu trống -> rút gọn từ long_desc
$descBrief = trim($product->short_desc ?? '') !== ''
? trim(preg_replace('/\s+/', ' ', $product->short_desc))
: \Illuminate\Support\Str::limit(strip_tags($product->long_desc ?? ''), 160);

// HTML mô tả chi tiết cho tab: CHỈ dùng long_desc
$longHtml = $product->long_desc ?: null;

// Prompt cho chatbot
$consultPrompt = "Tư vấn cho sản phẩm: {$product->name}.\n"
. "• Thương hiệu: ".(optional($product->brand)->name ?? 'N/A')."\n"
. "• Danh mục: ".(optional($product->category)->name ?? 'N/A')."\n"
. "• Giá từ: ".number_format($minForPrompt)."đ\n"
. "• Mô tả ngắn: ".$descBrief."\n"
. "Hãy gợi ý công dụng, ai nên dùng/không nên dùng, cách sử dụng, và combo đi kèm.";
@endphp

<section class="max-w-7xl mx-auto px-4 mt-6">
    {{-- breadcrumb --}}
    <div class="text-sm text-ink/60 mb-3">
        <a href="{{ route('home') }}" class="hover:text-brand-600">Trang chủ</a> /
        @if($product->category)
        <a href="{{ route('category.show',$product->category->slug) }}" class="hover:text-brand-600">{{ $product->category->name }}</a> /
        @endif
        <span class="text-ink">{{ $product->name }}</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
        {{-- Gallery --}}
        <div class="md:col-span-5" id="jsPdpGallery">
            <x-product-gallery :images="$images" :main="$main" />
        </div>

        {{-- Summary --}}
        <div class="md:col-span-7">
            <div class="md:sticky md:top-[92px] space-y-4">
                <h1 class="text-2xl font-bold">{{ $product->name }}</h1>

                {{-- Brand + Category --}}
                <div class="text-sm text-ink/60 flex items-center gap-2">
                    @if($product->brand)
                    <a class="hover:text-brand-600" href="{{ route('brand.show',$product->brand->slug) }}">{{ $product->brand->name }}</a><span>•</span>
                    @endif
                    @if($product->category)
                    <a class="hover:text-brand-600" href="{{ route('category.show',$product->category->slug) }}">{{ $product->category->name }}</a>
                    @endif
                </div>

                {{-- Giá --}}
                <x-price-block :min="$min" :compare="$minCmp" />

                {{-- Variants --}}
                @if($product->variants->count())
                <div class="mt-2">
                    <div class="text-sm font-medium mb-2">Phiên bản</div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($product->variants as $v)
                        <label class="cursor-pointer">
                            <input type="radio" name="variant_id" class="peer sr-only"
                                value="{{ $v->id }}" {{ $loop->first ? 'checked' : '' }}>
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full border border-rose-200 bg-white text-sm
                                                 peer-checked:bg-brand-600 peer-checked:text-white">
                                {{ $v->name }} — {{ number_format($v->price) }}₫
                            </span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Qty + CTA --}}
                <div class="flex items-center gap-3">
                    {{-- Stepper + / - --}}
                    <div class="flex items-center rounded-xl border border-rose-200 overflow-hidden">
                        <button type="button" id="qtyDec"
                            class="w-10 h-10 grid place-items-center text-xl text-ink/70 hover:bg-rose-50">−</button>
                        <input id="qtyInput" name="qty" value="1" inputmode="numeric"
                            class="h-10 w-14 text-center outline-none border-x border-rose-100"
                            oninput="this.value=this.value.replace(/[^0-9]/g,'')" />
                        <button type="button" id="qtyInc"
                            class="w-10 h-10 grid place-items-center text-xl text-ink/70 hover:bg-rose-50">+</button>
                    </div>

                    <button id="btnAddToCart"
                        data-product-id="{{ (int) $product->id }}"
                        class="px-5 py-3 bg-brand-600 text-white rounded-xl hover:bg-brand-700">
                        Thêm vào giỏ
                    </button>

                    <button id="btnConsult"
                        class="px-5 py-3 border border-rose-200 rounded-xl hover:bg-rose-50">
                        Tư vấn
                    </button>
                </div>

                <x-badge-list class="mt-1" />

                {{-- Tóm tắt ngắn (đã chuẩn hoá) --}}
                @if($descBrief)
                <div class="text-sm text-ink/80 mt-2">{{ $descBrief }}</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Tabs: mô tả + đánh giá --}}
    <div id="desc" class="mt-10 bg-white border border-rose-100 rounded-2xl p-4">
        <div x-data="{tab:'desc'}">
            <div class="flex gap-4 border-b border-rose-100">
                <button class="pb-3 font-medium"
                    :class="tab==='desc' ? 'text-brand-600 border-b-2 border-brand-600' : 'text-ink/60'"
                    @click="tab='desc'">Mô tả</button>

                <button class="pb-3 font-medium"
                    :class="tab==='reviews' ? 'text-brand-600 border-b-2 border-brand-600' : 'text-ink/60'"
                    @click="tab='reviews'">Đánh giá</button>
            </div>

            <div class="pt-4" x-show="tab==='desc'">
                @if($longHtml)
                {{-- Admin nhập HTML -> render thẳng --}}
                <div class="prose max-w-none">{!! $longHtml !!}</div>
                @else
                <x-empty text="Chưa có mô tả chi tiết." />
                @endif
            </div>

            <div class="pt-4" x-show="tab==='reviews'">
                @include('product.reviews', ['product' => $product])
            </div>
        </div>
    </div>

    {{-- Related --}}
    @if(isset($related) && $related->count())
    <div class="mt-10">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-bold">Khách cũng mua</h2>
            <a href="{{ route('shop.index') }}" class="text-sm text-brand-600 hover:underline">Xem thêm</a>
        </div>
        <x-related-carousel :products="$related" />
    </div>
    @endif
</section>

{{-- =================== PAGE SCRIPTS =================== --}}
<script>
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const CART_ENDPOINT = "{{ route('cart.store') }}";
    const CONSULT_PROMPT = @json($consultPrompt);

    // Qty stepper
    (function() {
        const q = document.getElementById('qtyInput');
        document.getElementById('qtyDec').addEventListener('click', () => {
            const v = parseInt(q.value || '1', 10) || 1;
            q.value = Math.max(1, v - 1);
        });
        document.getElementById('qtyInc').addEventListener('click', () => {
            const v = parseInt(q.value || '1', 10) || 1;
            q.value = v + 1;
        });
    })();

    // Tư vấn → mở bot (fallback nếu widget chưa init)
    document.getElementById('btnConsult').addEventListener('click', () => {
        if (window.Bot?.open) window.Bot.open(CONSULT_PROMPT);
        else window.dispatchEvent(new CustomEvent('bot:open', {
            detail: {
                prompt: CONSULT_PROMPT
            }
        }));
    });

    // Hiệu ứng bay vào giỏ
    (() => {
        if (document.getElementById('flyCartCSS')) return;
        const s = document.createElement('style');
        s.id = 'flyCartCSS';
        s.textContent = `
          .fly-cart{position:fixed;left:0;top:0;pointer-events:none;z-index:99999;will-change:transform,opacity;
                    filter:drop-shadow(0 8px 16px rgba(0,0,0,.15))}
          .fly-cart .dot{width:56px;height:56px;border-radius:9999px;background:#fff;display:grid;place-items:center;
                         border:1px solid rgba(16,24,39,.12)}
          .fly-cart i{font-size:26px;color:#e11d48}
        `;
        document.head.appendChild(s);
    })();

    function flyToCart(originEl = document.getElementById('btnAddToCart')) {
        const target = document.getElementById('jsCartIcon');
        if (!target) return;

        const ob = (originEl || document.querySelector('#jsPdpGallery img'))?.getBoundingClientRect();
        const tb = target.getBoundingClientRect();
        if (!ob || !tb) return;

        const DOT = 56,
            HALF = DOT / 2;
        const n = document.createElement('div');
        n.className = 'fly-cart';
        n.innerHTML = `<div class="dot"><i class="fa-solid fa-bag-shopping"></i></div>`;
        document.body.appendChild(n);

        const start = {
            x: ob.left + ob.width / 2 - HALF,
            y: ob.top + ob.height / 2 - HALF
        };
        const mid = {
            x: (ob.left + tb.left) / 2 - HALF,
            y: (ob.top + tb.top) / 2 - 200
        };
        const end = {
            x: tb.left + tb.width / 2 - (DOT * 0.35),
            y: tb.top + tb.height / 2 - (DOT * 0.35)
        };

        n.animate([{
                transform: `translate(${start.x}px,${start.y}px) scale(1)`,
                opacity: 1
            },
            {
                transform: `translate(${mid.x}px,${mid.y}px) scale(.95)`,
                opacity: .9
            },
            {
                transform: `translate(${end.x}px,${end.y}px) scale(.30)`,
                opacity: .2
            }
        ], {
            duration: 1600,
            easing: 'cubic-bezier(.22,.61,.36,1)'
        }).onfinish = () => n.remove();

        target.animate([{
            transform: 'scale(1)'
        }, {
            transform: 'scale(1.15)'
        }, {
            transform: 'scale(1)'
        }], {
            duration: 360,
            easing: 'ease-out'
        });
    }

    // Badge giỏ ở header
    function setCartCount(n) {
        n = Number(n) || 0;
        const el = document.getElementById('jsCartCount');
        if (!el) return;
        el.textContent = n;
        el.classList.toggle('hidden', n <= 0);
    }

    // Thêm vào giỏ (fetch + fly + đồng bộ)
    document.getElementById('btnAddToCart').addEventListener('click', async () => {
        const pid = Number(document.getElementById('btnAddToCart').dataset.productId || 0);
        const vid = (document.querySelector('input[name=variant_id]:checked') || {}).value || null;
        const qty = parseInt((document.getElementById('qtyInput') || {}).value || 1, 10);
        if (!pid || qty < 1) return;

        flyToCart();

        try {
            const res = await fetch(CART_ENDPOINT, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    product_id: pid,
                    variant_id: vid,
                    qty
                })
            });
            const data = await res.json().catch(() => null);

            if (data?.ok) {
                if (typeof data.count === 'number') {
                    setCartCount(data.count);
                } else {
                    const cur = Number((document.getElementById('jsCartCount')?.textContent) || 0);
                    setCartCount(cur + qty);
                }
                localStorage.setItem('cart-sync', JSON.stringify({
                    ts: Date.now(),
                    count: Number(document.getElementById('jsCartCount')?.textContent || 0)
                }));
            }
        } catch (e) {
            /* ignore */
        }
    });

    // Đồng bộ giữa các tab
    window.addEventListener('storage', (ev) => {
        if (ev.key === 'cart-sync' && ev.newValue) {
            try {
                setCartCount(JSON.parse(ev.newValue).count ?? 0);
            } catch {}
        }
    });
</script>
@endsection