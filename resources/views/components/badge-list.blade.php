@props(['items' => []])
@php
$defaults = [
['icon'=>'fa-solid fa-truck-fast', 'text'=>'Freeship đơn từ 499K'],
['icon'=>'fa-solid fa-rotate-left', 'text'=>'Đổi trả 7 ngày'],
['icon'=>'fa-solid fa-shield-heart', 'text'=>'Hàng chính hãng'],
];
$items = count($items) ? $items : $defaults;
@endphp

<ul class="grid sm:grid-cols-3 gap-2">
    @foreach($items as $it)
    <li class="flex items-center gap-2 px-3 py-2 rounded-lg border border-rose-100 bg-rose-50/40">
        <i class="{{ $it['icon'] }} text-brand-600"></i>
        <span class="text-sm">{{ $it['text'] }}</span>
    </li>
    @endforeach
</ul>