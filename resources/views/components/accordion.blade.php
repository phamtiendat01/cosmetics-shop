@props(['title'=>'', 'open'=>false])
<div x-data="{open: {{ $open ? 'true':'false' }} }" class="border border-rose-100 rounded-xl overflow-hidden">
    <button type="button" class="w-full flex items-center justify-between px-3 py-2 bg-rose-50/40"
        @click="open=!open">
        <span class="text-sm font-medium">{{ $title }}</span>
        <i class="fa-solid" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
    </button>
    <div x-show="open" x-collapse>
        <div class="p-3">
            {{ $slot }}
        </div>
    </div>
</div>