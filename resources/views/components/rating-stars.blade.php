@props(['value' => 0, 'count' => null, 'size' => 'text-[13px]'])
@php $val = max(0, min(5, (float) $value)); @endphp
<div class="inline-flex items-center gap-1 {{ $size }}">
    <div class="relative">
        <div class="text-amber-400">
            @for($i=0;$i<5;$i++) <i class="fa-solid fa-star opacity-30"></i> @endfor
        </div>
        <div class="absolute inset-0 text-amber-400 overflow-hidden"
            style="width: {{ $val/5*100 }}%">
            @for($i=0;$i<5;$i++) <i class="fa-solid fa-star"></i> @endfor
        </div>
    </div>
    @if(!is_null($count))
    <span class="text-ink/50">({{ $count }})</span>
    @endif
</div>