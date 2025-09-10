{{-- Toggle switch dùng cho bật/tắt --}}
@props(['name','checked'=>false,'label'=>null,'right'=>false])

<div class="flex items-center {{ $right ? 'justify-end' : '' }}">
    <input type="hidden" name="{{ $name }}" value="0">
    <label class="relative inline-flex items-center cursor-pointer">
        <input type="checkbox" class="sr-only peer" name="{{ $name }}" value="1" @checked($checked)>
        <div class="relative w-11 h-6 bg-slate-200 rounded-full peer-focus:outline-none
                after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                after:bg-white after:border after:rounded-full after:h-5 after:w-5 after:transition-all
                peer-checked:after:translate-x-full peer-checked:bg-rose-600"></div>
        @if($label)<span class="ml-3 text-sm text-slate-700">{{ $label }}</span>@endif
    </label>
</div>