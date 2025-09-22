@props([
'image' => null,
'mobile' => null,
'url' => null,
'title' => '',
'ratio' => 'aspect-[16/6]', // có thể đổi 16/9 tuỳ khu vực
])

@php
$img = $image ? asset('storage/'.$image) : 'https://placehold.co/1600x600?text=Banner';
$imgMb = $mobile ? asset('storage/'.$mobile) : $img;
@endphp

<a {{ $url ? 'href='.$url : '' }}
    class="block w-full overflow-hidden rounded-2xl border border-rose-100 bg-white">
    <picture>
        <source media="(max-width: 640px)" srcset="{{ $imgMb }}">
        <img src="{{ $img }}" alt="{{ $title }}" class="w-full object-cover {{ $ratio }}">
    </picture>
</a>