@props([
'title' => null,
'subtitle' => null,
])

<div {{ $attributes->class('bg-white rounded-xl border border-rose-100 shadow-sm overflow-hidden') }}>
    @if(isset($header) || $title || $subtitle)
    <div class="px-4 py-3 border-b border-rose-100 flex items-center justify-between">
        <div>
            @if($title)
            <h3 class="font-semibold text-ink">{{ $title }}</h3>
            @endif
            @if($subtitle)
            <p class="text-sm text-ink/60">{{ $subtitle }}</p>
            @endif
        </div>
        {{-- Slot header cho nút Actions (nếu có) --}}
        {{ $header ?? '' }}
    </div>
    @endif

    <div class="p-4">
        {{ $slot }}
    </div>

    @isset($footer)
    <div class="px-4 py-3 border-t border-rose-100 bg-rose-50/30">
        {{ $footer }}
    </div>
    @endisset
</div>