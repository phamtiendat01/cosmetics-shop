@props([
'images' => [], // mảng url ảnh (ưu tiên từ DB); nếu trống sẽ dùng ảnh chính
'main' => null, // ảnh fallback
])

@php
$imgs = collect($images)->filter()->values();
if ($imgs->isEmpty() && $main) $imgs = collect([$main]);
if ($imgs->isEmpty()) $imgs = collect(['https://placehold.co/1000x1000?text=IMG']);
@endphp

<div
    x-data="{
    i: 0,
    zoom:false, zx:50, zy:50,
    set(i){ this.i=i; },
    move(e){
      const box=e.currentTarget.getBoundingClientRect();
      this.zx = Math.min(100, Math.max(0, ((e.clientX-box.left)/box.width)*100));
      this.zy = Math.min(100, Math.max(0, ((e.clientY-box.top)/box.height)*100));
    }
  }"
    class="space-y-3">
    {{-- Ảnh chính + zoom --}}
    <div class="relative rounded-2xl overflow-hidden border border-rose-100 bg-white">
        <img :src="@js($imgs)[i]" class="w-full aspect-square object-cover select-none"
            @mousemove="zoom && move($event)" @mouseenter="zoom=true" @mouseleave="zoom=false">

        {{-- Kính lúp --}}
        <div x-show="zoom"
            class="hidden md:block absolute right-3 top-3 w-56 h-56 rounded-xl border border-rose-100 bg-white shadow-card overflow-hidden"
            :style="`background:url('${@js($imgs)}[${i}]') no-repeat; background-size: 200% 200%; background-position: ${zx}% ${zy}%;`">
        </div>
    </div>

    {{-- Thumbnails --}}
    <div class="flex gap-2 overflow-x-auto no-scrollbar">
        @foreach($imgs as $k=>$u)
        <button type="button" class="shrink-0 w-16 h-16 rounded-lg overflow-hidden border"
            :class="{{ $k }}===i ? 'border-brand-600' : 'border-rose-100'"
            @click="set({{ $k }})">
            <img src="{{ $u }}" class="w-full h-full object-cover">
        </button>
        @endforeach
    </div>
</div>