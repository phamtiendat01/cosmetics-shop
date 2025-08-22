@extends('layouts.app')
@section('title', ($product->name ?? 'Sản phẩm').' | Cosme House')

@section('content')
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
        <div class="md:col-span-5">
            @php
            $main = $product->image ? asset('storage/'.$product->image) : null;
            $images = collect($product->gallery ?? [])->map(fn($p)=>asset('storage/'.$p)); // nếu bạn có cột gallery dạng json
            @endphp
            <x-product-gallery :images="$images" :main="$main" />
        </div>

        {{-- Summary sticky --}}
        <div class="md:col-span-7">
            <div class="md:sticky md:top-[92px] space-y-4">
                <h1 class="text-2xl font-bold">{{ $product->name }}</h1>

                {{-- Brand + Category --}}
                <div class="text-sm text-ink/60 flex items-center gap-2">
                    @if($product->brand)
                    <a class="hover:text-brand-600" href="{{ route('brand.show',$product->brand->slug) }}">{{ $product->brand->name }}</a>
                    <span>•</span>
                    @endif
                    @if($product->category)
                    <a class="hover:text-brand-600" href="{{ route('category.show',$product->category->slug) }}">{{ $product->category->name }}</a>
                    @endif
                </div>

                {{-- Giá --}}
                @php
                $min = $product->variants->min('price');
                $minCmp = $product->variants->min('compare_at_price'); // nếu có cột này
                @endphp
                <x-price-block :min="$min" :compare="$minCmp" />

                {{-- Variant picker --}}
                @if($product->variants->count())
                <div class="mt-2">
                    <div class="text-sm font-medium mb-2">Phiên bản</div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($product->variants as $v)
                        <label class="cursor-pointer">
                            <input type="radio" name="variant_id" class="peer sr-only" value="{{ $v->id }}" {{ $loop->first ? 'checked' : '' }}>
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
                    <x-qty-stepper name="qty" />
                    <button class="px-5 py-3 bg-brand-600 text-white rounded-xl hover:bg-brand-700"
                        @click="
                    const vid = (document.querySelector('input[name=variant_id]:checked')||{}).value || null;
                    const qty = parseInt((document.querySelector('input[name=qty]')||{}).value||1);
                    $dispatch('cart:add',{ product_id: {{ $product->id }}, variant_id: vid, qty: qty });
                  ">
                        Thêm vào giỏ
                    </button>
                    <a href="#desc" class="px-5 py-3 border border-rose-200 rounded-xl hover:bg-rose-50">Tư vấn & mô tả</a>
                </div>

                {{-- Badge lợi ích --}}
                <x-badge-list class="mt-1" />

                {{-- Mô tả ngắn --}}
                @if($product->short_desc)
                <div class="text-sm text-ink/80 mt-2">{{ $product->short_desc }}</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Tabs: mô tả + đánh giá --}}
    <div id="desc" class="mt-10 bg-white border border-rose-100 rounded-2xl p-4">
        <div x-data="{tab:'desc'}">
            <div class="flex gap-4 border-b border-rose-100">
                <button class="pb-3 font-medium" :class="tab==='desc' ? 'text-brand-600 border-b-2 border-brand-600' : 'text-ink/60'"
                    @click="tab='desc'">Mô tả</button>
                <button class="pb-3 font-medium" :class="tab==='reviews' ? 'text-brand-600 border-b-2 border-brand-600' : 'text-ink/60'"
                    @click="tab='reviews'">Đánh giá</button>
            </div>

            <div class="pt-4" x-show="tab==='desc'">
                @if($product->long_desc || $product->description)
                <div class="prose max-w-none">{!! $product->long_desc ?? nl2br(e($product->description)) !!}</div>
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
@endsection