@props([
'min' => null, // giá min
'compare' => null, // giá gốc (min) nếu có
'class' => '',
])

@php
$sale = ($min && $compare && $compare > $min) ? round(100*(1-$min/$compare)) : null;
@endphp

<div class="{{ $class }}">
    @if($sale)
    <div class="flex items-baseline gap-2">
        <span class="text-2xl text-brand-600 font-semibold">{{ number_format($min) }}₫</span>
        <span class="text-sm line-through text-ink/50">{{ number_format($compare) }}₫</span>
        <span class="text-xs px-2 py-1 rounded-full bg-rose-600 text-white">-{{ $sale }}%</span>
    </div>
    @elseif($min)
    <div class="text-2xl text-brand-600 font-semibold">{{ number_format($min) }}₫</div>
    @else
    <div class="text-ink/50">Đang cập nhật</div>
    @endif
</div>