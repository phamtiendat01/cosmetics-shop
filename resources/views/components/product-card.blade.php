@props(['product'])

@php
use Illuminate\Support\Str;

// Ảnh
$imgPath = $product->thumbnail ?: $product->image;
if (is_array($imgPath)) $imgPath = $imgPath[0] ?? null;

$imgUrl = $imgPath
? (Str::startsWith($imgPath, ['http', '/storage']) ? $imgPath : asset('storage/' . ltrim($imgPath, '/')))
: 'https://placehold.co/400x400?text=IMG';

// Giá/giá gốc
$price = optional($product->variants)->min('price');
$compare = optional($product->variants)->min('compare_at_price');

// % giảm
$discount = ($compare && $price && $compare > $price)
? max(1, (int) round(100 * ($compare - $price) / $compare))
: null;

$href = route('product.show', $product->slug);

// ===== Rating (được nạp từ withAvg/withCount) =====
// Hỗ trợ cả 2 alias: avg_rating|rating_avg và reviews_count|rating_count
$avg = $product->avg_rating ?? $product->rating_avg ?? null; // ví dụ 4.3
$count = (int) ($product->reviews_count ?? $product->rating_count ?? 0);

// Làm tròn về 0.5 sao để vẽ half-star đúng
$rounded = $avg !== null ? round(((float)$avg) * 2) / 2 : 0.0;
$full = (int) floor($rounded);
$half = ($rounded - $full) === 0.5 ? 1 : 0;
@endphp

<div {{ $attributes->merge(['class' => 'relative wave-card js-card']) }}>
    <div class="relative rounded-2xl border border-rose-100 bg-white hover:shadow-card transition-all">

        {{-- Nút tim yêu thích --}}
        <div x-data="{ faved: {{ in_array($product->id, (array)session('wishlist', [])) ? 'true' : 'false' }} }"
            class="absolute top-3 left-3 z-20">
            <button type="button"
                @click.stop="faved = !faved; $dispatch('wishlist:toggle', { id: {{ $product->id }}, added: faved })"
                :aria-pressed="faved"
                class="relative grid place-items-center w-10 h-10 rounded-full bg-white/95 backdrop-blur
                     ring-1 ring-rose-200/60 shadow hover:ring-rose-300 hover:shadow-md
                     focus:outline-none focus:ring-2 focus:ring-rose-400 transition">
                <i :class="faved ? 'fa-solid fa-heart text-rose-600 text-[18px]' : 'fa-regular fa-heart text-ink/70 text-[18px]'"></i>
            </button>
        </div>

        {{-- Băng-rôn % giảm --}}
        @if($discount)
        <div class="absolute top-3 right-3 z-10">
            <span class="inline-block bg-rose-600 text-white text-xs font-bold px-2 py-1 rounded-md shadow">
                -{{ $discount }}%
            </span>
        </div>
        @endif

        <a href="{{ $href }}" class="block p-3 group">
            {{-- Khung ảnh --}}
            <div class="relative rounded-xl bg-rose-50/40 h-44 md:h-48 grid place-items-center overflow-hidden">
                <img src="{{ $imgUrl }}" alt="{{ $product->name }}"
                    class="max-h-40 md:max-h-44 object-contain transition-transform duration-300 group-hover:scale-105"
                    loading="lazy">
            </div>

            {{-- Tên sản phẩm --}}
            <div class="mt-3 min-h-[44px]">
                <div class="line-clamp-2 text-[15px] leading-5 font-medium text-ink group-hover:text-brand-700">
                    {{ $product->name }}
                </div>
            </div>

            {{-- Đã bán + Sao đánh giá --}}
            <div class="mt-1 text-[12px] text-ink/60 flex items-center gap-2">
                <span>Đã bán <span class="font-semibold">{{ number_format($product->sold_count ?? 0) }}</span></span>

                <span class="inline-block w-px h-3 bg-slate-200"></span>

                <span class="flex items-center gap-0.5">
                    @if($count > 0 && $avg !== null)
                    {{-- Có review: sao vàng (có thể có nửa sao) + điểm trung bình --}}
                    @for($i = 1; $i <= 5; $i++)
                        @if($i <=$full)
                        <i class="fa-solid fa-star text-amber-500 text-[11px]"></i>
                        @elseif($half && $i === $full + 1)
                        <i class="fa-solid fa-star-half-stroke text-amber-500 text-[11px]"></i>
                        @else
                        <i class="fa-regular fa-star text-slate-300 text-[11px]"></i>
                        @endif
                        @endfor
                        <span class="ml-1 text-[11px] text-ink/60">{{ number_format((float)$avg, 1) }}</span>
                        @else
                        {{-- Chưa có review: 5 sao xám, không hiện điểm --}}
                        @for($i = 1; $i <= 5; $i++)
                            <i class="fa-regular fa-star text-slate-300 text-[11px]"></i>
                            @endfor
                            @endif
                </span>
            </div>

            {{-- Giá --}}
            <div class="mt-2">
                @if($discount)
                <div class="flex items-baseline gap-2">
                    <div class="text-[18px] font-extrabold text-rose-600">
                        {{ number_format($price) }}₫
                    </div>
                    <div class="text-xs text-slate-400 line-through">
                        {{ number_format($compare) }}₫
                    </div>
                </div>
                @elseif($price)
                <div class="text-[17px] font-semibold text-ink">
                    {{ number_format($price) }}₫
                </div>
                @else
                <div class="text-sm text-slate-500">Liên hệ</div>
                @endif
            </div>
        </a>
    </div>

    {{-- Lớp vệt sáng --}}
    <div class="shine pointer-events-none"></div>
</div>
