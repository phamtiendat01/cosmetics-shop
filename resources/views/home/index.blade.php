@extends('layouts.app')
@section('title','Cosme House')

@section('content')
<section class="max-w-7xl mx-auto px-4 mt-6 space-y-10" x-data>

    {{-- HERO SLIDER --}}
    <div x-data="{
      i: 0, n: {{ isset($heroSlides) ? count($heroSlides) : 3 }},
      next(){ this.i = (this.i+1)%this.n }, prev(){ this.i = (this.i-1+this.n)%this.n },
      go(k){ this.i = k }, autoplay: null
    }"
        x-init="autoplay = setInterval(()=>next(), 5000)" @mouseenter="clearInterval(autoplay)" @mouseleave="autoplay = setInterval(()=>next(),5000)"
        class="relative rounded-2xl overflow-hidden border border-rose-100">

        <div class="relative w-full h-0 pb-[36%] sm:pb-[28%]">
            {{-- Slides --}}
            <template x-for="(s,idx) in {{ json_encode($heroSlides ?? [
          ['image'=>null,'url'=>'#','title'=>'Banner 1'],
          ['image'=>null,'url'=>'#','title'=>'Banner 2'],
          ['image'=>null,'url'=>'#','title'=>'Banner 3'],
        ]) }}" :key="idx">
                <a :href="s.url" class="absolute inset-0"
                    x-show="i===idx" x-transition.opacity>
                    <img :src="s.image ?? 'https://placehold.co/1600x576?text=Hero+Slide'"
                        :alt="s.title ?? ''" class="w-full h-full object-cover" />
                </a>
            </template>
        </div>

        {{-- Dots --}}
        <div class="absolute bottom-3 left-0 right-0 flex items-center justify-center gap-2">
            <template x-for="k in n">
                <button @click="go(k-1)" class="w-2.5 h-2.5 rounded-full"
                    :class="i===k-1 ? 'bg-white' : 'bg-white/50'"></button>
            </template>
        </div>

        {{-- Prev/Next --}}
        <button @click="prev" class="absolute left-2 top-1/2 -translate-y-1/2 w-9 h-9 grid place-items-center rounded-full bg-white/80 hover:bg-white">
            <i class="fa-solid fa-chevron-left"></i>
        </button>
        <button @click="next" class="absolute right-2 top-1/2 -translate-y-1/2 w-9 h-9 grid place-items-center rounded-full bg-white/80 hover:bg-white">
            <i class="fa-solid fa-chevron-right"></i>
        </button>
    </div>

    {{-- BRAND STRIP --}}
    <div class="bg-white border border-rose-100 rounded-2xl p-3">
        <div class="flex items-center gap-4 overflow-x-auto snap-x snap-mandatory no-scrollbar">
            @forelse(($topBrands ?? []) as $b)
            <a href="{{ route('brand.show',$b->slug) }}" class="min-w-[120px] snap-start shrink-0 flex items-center gap-3 px-3 py-2 border border-rose-100 rounded-xl hover:shadow-card">
                @if($b->logo)
                <img src="{{ asset('storage/'.$b->logo) }}" class="w-10 h-10 object-contain" alt="{{ $b->name }}">
                @else
                <div class="w-10 h-10 rounded bg-rose-50 grid place-items-center">{{ strtoupper(substr($b->name,0,1)) }}</div>
                @endif
                <span class="text-sm font-medium">{{ $b->name }}</span>
            </a>
            @empty
            @for($i=0;$i<8;$i++)
                <div class="min-w-[120px] snap-start shrink-0 h-[56px] rounded-xl bg-rose-50/60 border border-rose-100">
        </div>
        @endfor
        @endforelse
    </div>
    </div>

    {{-- KỆ HÀNG NGANG: “Gợi ý hôm nay” --}}
    <div>
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-bold">Gợi ý hôm nay</h2>
            <a href="{{ route('shop.index') }}" class="text-sm text-brand-600 hover:underline">Xem tất cả</a>
        </div>
        <div class="relative">
            <div class="flex gap-4 overflow-x-auto snap-x snap-mandatory no-scrollbar pb-2">
                @forelse(($suggested ?? []) as $p)
                <div class="min-w-[180px] max-w-[180px] snap-start">
                    <x-product-card :product="$p" />
                </div>
                @empty
                @for($i=0;$i<10;$i++)
                    <div class="min-w-[180px] max-w-[180px] snap-start bg-white border border-rose-100 rounded-xl h-[280px]">
            </div>
            @endfor
            @endforelse
        </div>
    </div>
    </div>

    {{-- LƯỚI “Sản phẩm mới” --}}
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

</section>
@endsection