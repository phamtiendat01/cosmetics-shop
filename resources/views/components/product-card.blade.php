@props(['product'])
@php
// Giá min/max (đã có từ withMin/withMax)
$min = $product->min_price ?? null;
$max = $product->max_price ?? null;

// So sánh giá (nếu bạn thêm withMin/withMax compare_at_price ở controller – xem mục 4)
$minCmp = $product->min_compare_at_price ?? null;
$hasSale = $min && $minCmp && $minCmp > $min;
$salePercent = $hasSale ? round(100 * (1 - $min / $minCmp)) : null;

$img = $product->image ? asset('storage/'.$product->image) : 'https://placehold.co/600x600?text=IMG';
@endphp
@php
$variantsPayload = $product->relationLoaded('variants')
? $product->variants->map(fn($v)=>['id'=>$v->id,'name'=>$v->name,'price'=>$v->price])->values()
: collect();
@endphp
<a href="{{ route('product.show',$product->slug ?? '#') }}"
    class="group relative bg-white border border-rose-100 rounded-xl overflow-hidden hover:shadow-card transition">
    {{-- Ảnh --}}
    <div class="relative">
        <img src="{{ $img }}" class="w-full aspect-square object-cover" alt="{{ $product->name }}">
        {{-- Nút Quick-Add (góc phải dưới ảnh) --}}
        <div class="absolute bottom-2 right-2 opacity-0 group-hover:opacity-100 transition">
            <button type="button"
                class="px-3 py-2 rounded-lg bg-brand-600 text-white text-xs shadow hover:bg-brand-700"
                @click.stop="
      @if(isset($product->variants_count) && $product->variants_count==1)
        $dispatch('cart:add',{
          product_id: {{ $product->id }},
          variant_id: {{ optional($product->variants->first())->id ?? 'null' }},
          qty: 1
        });
      @else
        $store.qv.show({
          id: @js($product->id),
          name: @js($product->name),
          image: @js($img),
          url: @js(route('product.show',$product->slug ?? '#')),
          short: @js($product->short_desc ?? ''),
          min: @js($min),
          compare: @js($minCmp),
          variants: []
        });
      @endif
    ">
                Thêm nhanh
            </button>
        </div>

        {{-- Badge giảm giá --}}
        @if($hasSale)
        <span class="absolute top-2 left-2 text-xs px-2 py-1 rounded-full bg-rose-600 text-white shadow">
            -{{ $salePercent }}%
        </span>
        @endif
        {{-- Thêm dưới badge giảm giá --}}
        <button type="button" class="absolute bottom-2 left-2 right-2 opacity-0 group-hover:opacity-100 transition
               px-3 py-2 rounded-lg bg-white/95 border border-rose-100 text-sm"
            @click.stop="$store.qv.show({
          id: @js($product->id),
          name: @js($product->name),
          image: @js($img),
          url: @js(route('product.show',$product->slug ?? '#')),
          short: @js($product->short_desc ?? ''),
          min: @js($min),
          compare: @js($minCmp),
          variants: @js($variantsPayload)
        })">
            Xem nhanh
        </button>
        {{-- Nút yêu thích (UI) --}}
        <button type="button"
            class="absolute top-2 right-2 w-9 h-9 grid place-items-center rounded-full bg-white/90 border border-rose-100 text-ink/70 hover:text-rose-600">
            <i class="fa-regular fa-heart"></i>
        </button>
    </div>

    {{-- Nội dung --}}
    <div class="p-3">
        <div class="text-sm font-medium line-clamp-2 group-hover:text-brand-600">{{ $product->name }}</div>

        {{-- Rating (nếu có) --}}
        @if(isset($product->avg_rating) || isset($product->reviews_count))
        <div class="mt-1">
            <x-rating-stars :value="$product->avg_rating ?? 0" :count="$product->reviews_count ?? null" />
        </div>
        @endif

        {{-- Giá --}}
        <div class="mt-1">
            @if($hasSale)
            <div class="flex items-baseline gap-2">
                <span class="text-brand-600 font-semibold">{{ number_format($min) }}₫</span>
                <span class="text-xs line-through text-ink/50">{{ number_format($minCmp) }}₫</span>
            </div>
            @elseif($min && $max && $min != $max)
            <div class="text-brand-600 font-semibold">{{ number_format($min) }}₫ – {{ number_format($max) }}₫</div>
            @elseif($min)
            <div class="text-brand-600 font-semibold">{{ number_format($min) }}₫</div>
            @else
            <div class="text-ink/50">Đang cập nhật</div>
            @endif
        </div>

        {{-- Hover CTA --}}
        <div class="mt-2 opacity-0 group-hover:opacity-100 transition">
            <button type="button"
                class="w-full px-3 py-2 rounded-lg border border-rose-200 hover:bg-rose-50 text-sm">
                Thêm vào giỏ
            </button>
        </div>
    </div>
</a>