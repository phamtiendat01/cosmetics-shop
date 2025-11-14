@props([
'images' => [],
'main' => null,
])

@php
$imgs = collect($images)->filter()->values();
if ($imgs->isEmpty() && $main) $imgs = collect([$main]);
if ($imgs->isEmpty()) $imgs = collect(['https://placehold.co/800x800?text=IMG']);
@endphp

<div x-data="pdpGallery(@js($imgs))" class="space-y-3" id="jsPdpGallery">
    {{-- Ảnh chính + kính lúp --}}
    <div class="relative rounded-2xl overflow-hidden border border-rose-100 bg-white md:max-w-[560px] mx-auto">
        <img
            :src="imgs[i]"
            class="w-full aspect-square object-contain select-none"
            @mousemove="zoom && move($event)"
            @mouseenter="zoom = true"
            @mouseleave="zoom = false"
            draggable="false" />
        {{-- kính lúp chỉ hiện trên md+ --}}
        <div
            x-show="zoom"
            class="hidden md:block absolute right-3 top-3 w-56 h-56 rounded-xl border border-rose-100 bg-white shadow-card overflow-hidden"
            :style="`background-image:url(${imgs[i]}); background-repeat:no-repeat; background-size:${zsize}% ${zsize}%; background-position:${zx}% ${zy}%;`"></div>
    </div>

    {{-- thumbnails --}}
    <div class="flex gap-2 overflow-x-auto no-scrollbar">
        @foreach($imgs as $k => $u)
        <button type="button"
            class="shrink-0 w-16 h-16 rounded-lg overflow-hidden border"
            :class="i === {{ $k }} ? 'border-brand-600' : 'border-rose-100'"
            @click="set({{ $k }})">
            <img src="{{ $u }}" class="w-full h-full object-cover" loading="lazy">
        </button>
        @endforeach
    </div>
</div>

<script>
    // Alpine component cho gallery + kính lúp
    function pdpGallery(imgs) {
        return {
            imgs,
            i: 0,
            zoom: false,
            zx: 50,
            zy: 50,
            zsize: 200, // % phóng to cho kính lúp
            set(i) {
                this.i = i;
            },
            move(e) {
                const box = e.currentTarget.getBoundingClientRect();
                this.zx = Math.min(100, Math.max(0, ((e.clientX - box.left) / box.width) * 100));
                this.zy = Math.min(100, Math.max(0, ((e.clientY - box.top) / box.height) * 100));
            }
        }
    }
</script>